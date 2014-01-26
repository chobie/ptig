<?php
require dirname(dirname(__DIR__)) . "/vendor/autoload.php";

require dirname(dirname(__DIR__)) . "/OAuth.php";
require dirname(dirname(__DIR__)) . "/twitteroauth.php";

use \Chobie\IO\Stream;
use \Chobie\Net\IRC\Message;
use \Chobie\Net\IRC\Entity\User;
use \Chobie\Net\IRC\Server\Handler;
use \Chobie\Net\IRC\Server\World;
use \Chobie\Net\IRC\Server;

define("CONSUMER_KEY", "uoSgZWThDlCDJA1G5GNZg");
define("CONSUMER_SECRET", "3nrp5n4evnJBOiT0ssPvtz7LZXaw8W5jFtBtBKUwG4");

World::getInstance(function(World $world){
    $conf = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(sprintf("%s/.ptig/config.yml", getenv("HOME"))));
    $world->setConfig($conf);

    $twObj = new TwitterOAuth(
        CONSUMER_KEY,
        CONSUMER_SECRET,
        $conf['oauth_token'],
        $conf['oauth_token_secret']
    );

    $world->setExtra($twObj);
    $world->setOwnerInfo($twObj->get("account/verify_credentials"));

    $world->getEventDispatcher()->addListener("irc.kernel.create_room", function(Server\Event\CreateRoom $event) use ($world, $conf) {
        if ($event->getRoom()->getName() == "#twitter") {
            // #twitter is registered as a special channel for streaming.
            $timeilne = new \Chobie\Net\Twitter\Timeline\StreamTimeline(
                new \Chobie\Net\Twitter\Stream(CONSUMER_KEY, CONSUMER_SECRET, $conf['oauth_token'], $conf['oauth_token_secret']), "#twitter", array(), 1
            );

            World::getInstance()->getTimer()
                ->addListener($event->getRoom()->getName(), function($time, World $world) use ($timeilne) {
                if ($timeilne->onCond($time)) {
                    $timeilne->update($world);
                }
            });
            $event->getRoom()->setPayload($timeilne);
        } else {
            $room_name = $event->getRoom()->getName();
            if (preg_match("/#search_(.+)/", $room_name, $match)) {
                $event->getRoom()->clear_when_no_user = true;

                if ($event->getRoom()->immidiately) {
                    $wait = 0;
                } else {
                    $wait = time()- mt_rand(0, 300);
                }

                $timeline = new \Chobie\Net\Twitter\Timeline($world->getExtra(), $room_name, array(
                    "api" => "search/tweets",
                    "params" => array(
                        "q" => $match[1],
                    )
                ), 300, $wait);
                World::getInstance()->getTimer()
                    ->addListener($event->getRoom()->getName(), function($time, World $world) use ($timeline) {
                        if ($timeline->onCond($time)) {
                            $timeline->update($world);
                        }
                    });
                $event->getRoom()->setPayload($timeline);
            } else if ("#eval" == $room_name) {
                // debug only

            } else {
                $room = $event->getRoom();
                if (property_exists($room, "params")) {
                    $params = $room->params;
                    $options = $params['options'];

                    $timeline = new \Chobie\Net\Twitter\Timeline(
                        $world->getExtra(), $room_name, $room->params, $options['refresh'], $options['init']
                    );

                    World::getInstance()->getTimer()
                        ->addListener($event->getRoom()->getName(), function($time, World $world) use ($timeline) {
                            if ($timeline->onCond($time)) {
                                $timeline->update($world);
                            }
                        });
                    $event->getRoom()->setPayload($timeline);
                }
            }
        }

    });

    $world->getEventDispatcher()->addListener("debug", function($event) {
        echo "\e[32m# " . $event . "\e[m\n";
    });

    // NOTE(chobie): sending tweet
    $world->getEventDispatcher()->addListener("irc.event.private_message",
        function(Server\Event\PrivateMessage $event) use ($world){
            $payload = $event->getMessage();
            if ($payload->getParameter(0) == "#eval") {
                ob_start();
                eval("?><?php " . ltrim($payload->getParameter(1), ":"));
                $buffer = ob_get_clean();

                foreach (preg_split("/\r?\n/", $buffer) as $line) {
                    $event->getStream()->writeln(":`host` PRIVMSG `room` :`msg`",
                        "host", "ptig",
                        "room", $payload->getParameter(0),
                        "msg", $line
                    );
                }
                return;
            }

            if (preg_match("/ACTION/", $payload->getParameter(1))) {
                var_dump($payload);
                if ($payload->getParameter(2) == "search") {
                    $params = $payload->getParameters();
                    array_shift($params);
                    array_shift($params);
                    array_shift($params);
                    $search = join(" ", $params);
                    if ($room = $world->getRoom("#" . $search)) {
                        return;
                    }

                    $room_name = "#search_" . $search;
                    $room = $world->appendRoom(function(\Chobie\Net\IRC\Entity\Room $room) use ($room_name){
                        $room->name = $room_name;
                        $room->clear_when_no_user = true;
                        $room->immidiately = true;
                    });

                    $room->addUser($event->getUser());
                    $event->getStream()->writeln(":`user` JOIN :`room`",
                        "user", $event->getUser()->getFQ(),
                        "room", $room->name
                    );
                }
                return;
            }

            if ($event->getMessage()->getParameter(0) == "#twitter") {
                $t = $world->getExtra();
                $t->post('statuses/update', ['status' => ltrim($event->getMessage()->getParameter(1), ":")]);

                $event->getStream()->writeln("NOTE :twitter updated");
                $event->getStream()->writeln(":`host` TOPIC `room` :`msg`",
                    "host", $event->getUser()->getFQ(),
                    "room", $event->getMessage()->getParameter(0),
                    "msg", ltrim($event->getMessage()->getParameter(1), ":")
                );
            }
    });

    // register special channel.
    $world->appendRoom(function(\Chobie\Net\IRC\Entity\Room $room) {
        $room->name = "#twitter";
    });

    // register list channels.
    $i = 0;
    foreach ($twObj->get("lists/list") as $list) {
        $world->appendRoom(function(\Chobie\Net\IRC\Entity\Room $room) use ($list, $i) {
            $room->name = "#" . $list['slug'];
            $room->params = array(
                "api" => "lists/statuses",
                "params" => array(
                    "slug" => $list['slug'],
                    "owner_id" => $list['user']['id_str'],
                ),
                "options" => array(
                    "refresh" => 300,
                    "init" => time() - $i
                )
            );
        });
        $i += 60;
    }
});

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
    } catch (Exception $e) {
        $handler->onError($stream, $user, $e);
    }
})->listen(6668);