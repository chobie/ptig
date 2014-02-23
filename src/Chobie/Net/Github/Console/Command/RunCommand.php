<?php
namespace Chobie\Net\Github\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use \Chobie\IO\Stream;
use \Chobie\Net\IRC\Message;
use \Chobie\Net\IRC\Entity\User;
use \Chobie\Net\IRC\Server\Handler;
use \Chobie\Net\IRC\Server\World;
use \Chobie\Net\IRC\Server;

class RunCommand extends Command
{
    protected $subscribers = array(
    );

    protected function configure()
    {
        $this->setName('run')
            ->addArgument("url", InputArgument::REQUIRED, "feed url")
            ->addOption("port", "p", InputOption::VALUE_OPTIONAL, "base dir", 7000)
            ->setDescription('specify port');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        define("GITHUB_PRIVATE_FEED_URL", $input->getArgument("url"));
        $this->setupWorld();
        Server::createServer(function(
            Stream $stream, User $user, Message $message, Handler $handler) {

            try {
                $method = "on" . $message->getCommand();
                if (method_exists($handler, $method)) {
                    call_user_func_array(array($handler, $method), array(
                        $stream,
                        $user,
                        $message
                    ));
                } else {
                    throw new \Chobie\Net\IRC\Exception\UnsupportedCommandException();
                }
            } catch (\Exception $e) {
                $handler->onError($stream, $user, $e);
            }
        })->listen($input->getOption("port"));
    }

    protected function setupWorld()
    {
        World::getInstance(function(World $world){
            $world->getTimer()->addListener("updater", function($time, World $world) {
                static $last;
                static $last_id;

                if (is_null($last)) {
                    $last = $time;
                    return;
                }

                if (($time - $last) > (300 + (mt_rand(1, 5) * 60))) {
                //if (($time - $last) > 10) {
                    echo "EXECUTED\n";
                    $opts = array(
                        'http'=>array(
                            'method'=>"GET",
                            'header'=>"Accept: application/atom+xml\r\n"
                        )
                    );

                    $data = file_get_contents(GITHUB_PRIVATE_FEED_URL, null, stream_context_create($opts));
                    $data = simplexml_load_string($data);
                    $json = json_decode(json_encode($data), true);

                    $rev = array_reverse($json['entry']);
                    foreach ($rev as $entry) {
                        list($dummy, $id) = explode("/", $entry['id'], 2);
                        if (!is_null($last_id) && $id <= $last_id) {
                            continue;
                        }
                        $last_id = $id;

                        $content = array();
                        $content[] = preg_replace("/^{$entry['author']['name']}/", "", ltrim($entry['title']));
                        if (preg_match("/(PullRequestReview|Commit|Issue)CommentEvent/", $entry['id'])) {
                            $entry['content'] = preg_replace('!<img class="emoji" title="(.+?)" alt="(.+?)" src="(.+?)" height="\d+" width="\d+" align="absmiddle">!', "\$2", $entry['content']);
                            if (preg_match("!<p>(.+?)</p>!m", $entry['content'], $match)) {
                                $content[] = "  ". $match[1] . PHP_EOL;
                            }
                        }

                        if (preg_match("/(forked.+?to|starred|request|at|starred) ([a-zA-Z][a-zA-Z0-9_\/-]+)/", $entry['title'], $match)) {
                            @list($organization, $repository) = explode("/", $match[2]);
                            $channel = "#" . $organization;
                            if ($match[1] == "starred") {
                                $channel = "#starred";
                            }

                            $owner = $world->getOwner();

                            if (!$world->roomExists($channel)) {
                                $world->appendRoom(function(\Chobie\Net\IRC\Entity\Room $room) use ($channel, $owner){
                                    $room->name = $channel;
                                    $room->addUser($owner);
                                });
                            }

                            $content[] = "  " . $entry['link']['@attributes']['href'];

                            $world->getEventDispatcher()->dispatch("irc.kernel.new_message", new \Chobie\Net\IRC\Server\Event\NewMessage(
                                $channel,
                                $entry['author']['name'],
                                join("\n", $content),
                                $entry
                            ));
                        }
                    }

                    $last = $time;
                }
            });
        });
    }
}

