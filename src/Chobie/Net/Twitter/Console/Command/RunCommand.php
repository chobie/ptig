<?php
namespace Chobie\Net\Twitter\Console\Command;

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
        '\Chobie\Net\Twitter\Subscriber\CreateRoomSubscriber',
        '\Chobie\Net\Twitter\Subscriber\DebugSubscriber',
        '\Chobie\Net\Twitter\Subscriber\PrivMsgSubscriber',
        '\Chobie\Net\Twitter\Subscriber\PrivMsgSubscriber',
        '\Chobie\Net\Twitter\Subscriber\NaiveBayesSubscriber',
        '\Chobie\Net\Twitter\Subscriber\MentionSubscriber',
        '\Chobie\Net\Twitter\Subscriber\NewMessageSubscriber',
        '\Chobie\Net\Twitter\Subscriber\FilterSubscriber',
        '\Chobie\Net\Twitter\Subscriber\PartSubscriber',
    );

    protected function configure()
    {
        $this->setName('run')
            ->addOption("port", "p", InputOption::VALUE_OPTIONAL, "base dir", 6668)
            ->setDescription('specify port');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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


    protected function initiatePlugins(World $world)
    {
        foreach ((array)$world->getConfigByKey("plugins.input_filters") as $filter) {
            if (!isset($filter['class'])) {
                continue;
            }
            $klass = $filter["class"];
            $args = array();
            if (isset($filter['args'])) {
                $args = $filter['args'];
            }
            $f = new $klass($args);
            $world->addInputFilter($f);
        }

        foreach ((array)$world->getConfigByKey("plugins.output_filters") as $filter) {
            if (!isset($filter['class'])) {
                continue;
            }
            $klass = $filter["class"];
            $args = array();
            if (isset($filter['args'])) {
                $args = $filter['args'];
            }
            $f = new $klass($args);

            $world->addOutputFilter($f);
        }

        foreach ((array)$world->getConfigByKey("plugins.commands") as $filter) {
            if (!isset($filter['class'])) {
                continue;
            }

            $klass = $filter["class"];
            $args = array();
            if (isset($filter['args'])) {
                $args = $filter['args'];
            }
            $f = new $klass($args);

            $world->addCommand($f);
        }
    }


    protected function setupWorld()
    {
        World::getInstance(function(World $world){
            $conf = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(sprintf("%s/.ptig/config.yml", getenv("HOME"))));
            $world->setConfig($conf);

            $twObj = new \TwitterOAuth(
                CONSUMER_KEY,
                CONSUMER_SECRET,
                $conf['oauth_token'],
                $conf['oauth_token_secret']
            );

            $world->setExtra($twObj);
            $world->setOwnerInfo($twObj->get("account/verify_credentials"));

            // register subscribers
            foreach ($this->subscribers as $subscriber_class) {
                $world->getEventDispatcher()->addSubscriber(new $subscriber_class());
            }

            // register special channels.
            foreach (["#twitter", "#mention", "#favorites"] as $channel) {
                $world->appendRoom(function(\Chobie\Net\IRC\Entity\Room $room) use ($channel){
                    $room->name = $channel;
                });

            }

            // register list channels.
            $i = 0;
            foreach ($twObj->get("lists/list") as $list) {
                $world->getTimer()->runOnce(function() use ($list, $i, $world) {
                    $world->appendRoom(function(\Chobie\Net\IRC\Entity\Room $room) use ($list, $i) {
                        if (!isset($list['slug'])) {
                            return;
                        }

                        $room->name = "#" . $list['slug'];
                        $room->params = array(
                            "api" => "lists/statuses",
                            "params" => array(
                                "slug" => $list['slug'],
                                "owner_id" => $list['user']['id_str'],
                            ),
                            "options" => array(
                                "refresh" => 300,
                                "init" => time() - ($i * 30)
                            )
                        );
                    });
                }, time() + 30);
                $i++;
            }
            $this->initiatePlugins($world);
        });
    }
}

