<?php
namespace Chobie\Net\Twitter\Subscriber;

use Chobie\Net\IRC\Server\World;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PrivMsgSubscriber
    implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            "irc.event.private_message" => array(
                array('processPrivMsg', 500)
            ));
    }

    public static function processPrivMsg(\Chobie\Net\IRC\Server\Event\PrivateMessage $event)
    {
        $world = World::getInstance();
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
            } else if ($payload->getParameter(2) == "fav") {
                $params = $payload->getParameters();
                $__room = $world->getRoom($event->getMessage()->getParameter(0));

                array_shift($params);
                array_shift($params);
                array_shift($params);
                $id = join(" ", $params);

                if ($__room && $hist = $__room->getPayload()->getHistory($id)) {

                    $t = $world->getExtra();
                    $status = ltrim($event->getMessage()->getParameter(1), ":");
                    var_dump($t->post('favorites/create', ['id' => $hist['id']]));

                    $event->getStream()->writeln(":`fq` NOTICE `room` :you've favourited `nick`: `msg`",
                        "fq", "ptig!~ptig@irc.example.net",
                        "room", $__room->getName(),
                        "nick", $hist['nick'],
                        "msg", $hist['text']
                    );
                } else {
                    $event->getStream()->writeln(":`fq` NOTICE `room` : specified id does not find.",
                        "fq", "ptig!~ptig@irc.example.net",
                        "room", $__room->getName()
                    );
                }
            } else if ($payload->getParameter(2) == "rt") {
                $params = $payload->getParameters();
                $__room = $world->getRoom($event->getMessage()->getParameter(0));

                array_shift($params);
                array_shift($params);
                array_shift($params);
                $id = join(" ", $params);

                if ($__room && $hist = $__room->getPayload()->getHistory($id)) {

                    $t = $world->getExtra();
                    $status = ltrim($event->getMessage()->getParameter(1), ":");
                    var_dump($t->post('statuses/retweet/'. $hist['id']));

                    $event->getStream()->writeln(":`fq` NOTICE `room` :you've retweeted `nick`: `msg`",
                        "fq", "ptig!~ptig@irc.example.net",
                        "room", $__room->getName(),
                        "nick", $hist['nick'],
                        "msg", $hist['text']
                    );
                } else {
                    $event->getStream()->writeln(":`fq` NOTICE `room` : specified id does not find.",
                        "fq", "ptig!~ptig@irc.example.net",
                        "room", $__room->getName()
                    );
                }
            } else if ($payload->getParameter(2) == "re") {
                $params = $payload->getParameters();
                $__room = $world->getRoom($event->getMessage()->getParameter(0));

                array_shift($params);
                array_shift($params);
                array_shift($params);
                $id = array_shift($params);
                $msg = join(" ", $params);

                if ($__room && $hist = $__room->getPayload()->getHistory($id)) {

                    $t = $world->getExtra();

                    if (!preg_match("/@{$hist['nick']}/", $msg)) {
                        $msg = "@" . $hist['nick'] . " " . $msg;
                    }

                    $params = ['status' => $msg, "in_reply_to_status_id" => $hist["id"]];
                    $t->post('statuses/update', $params);

                } else {
                    $event->getStream()->writeln(":`fq` NOTICE `room` : specified id does not find.",
                        "fq", "ptig!~ptig@irc.example.net",
                        "room", $__room->getName()
                    );
                }
            } else if ($payload->getParameter(2) == "show") {
                $params = $payload->getParameters();
                $__room = $world->getRoom($event->getMessage()->getParameter(0));

                array_shift($params);
                array_shift($params);
                array_shift($params);
                $id = join(" ", $params);

                $t = $world->getExtra();
                $status = ltrim($event->getMessage()->getParameter(1), ":");
                $info = $t->get('users/show', ['screen_name' => $id]);
                var_dump($info);//
                $event->getStream()->writeln(":`fq` PRIVMSG `room` :`image`",
                    "fq", "ptig!~ptig@irc.example.net",
                    "room", $__room->getName(),
                    "image", $info['profile_image_url_https']
                );
                $event->getStream()->writeln(":`fq` NOTICE `room` :user `user`",
                    "fq", "ptig!~ptig@irc.example.net",
                    "room", $__room->getName(),
                    "user", $info['screen_name']
                );
                $event->getStream()->writeln(":`fq` NOTICE `room` :location  `location`",
                    "fq", "ptig!~ptig@irc.example.net",
                    "room", $__room->getName(),
                    "location", $info['location']
                );

                foreach (explode("\n", $info['description']) as $description) {
                    $event->getStream()->writeln(":`fq` NOTICE `room` :`description`",
                        "fq", "ptig!~ptig@irc.example.net",
                        "room", $__room->getName(),
                        "description", $description
                    );
                }
                $event->getStream()->writeln(":`fq` NOTICE `room` :url  `url`",
                    "fq", "ptig!~ptig@irc.example.net",
                    "room", $__room->getName(),
                    "url", $info['url']
                );
                $event->getStream()->writeln(":`fq` NOTICE `room` :followers  `count`",
                    "fq", "ptig!~ptig@irc.example.net",
                    "room", $__room->getName(),
                    "count", $info['followers_count']
                );
                $event->getStream()->writeln(":`fq` NOTICE `room` :friends  `count`",
                    "fq", "ptig!~ptig@irc.example.net",
                    "room", $__room->getName(),
                    "count", $info['friends_count']
                );
                $event->getStream()->writeln(":`fq` NOTICE `room` :favourites  `count`",
                    "fq", "ptig!~ptig@irc.example.net",
                    "room", $__room->getName(),
                    "count", $info['favourites_count']
                );
            } else if ($payload->getParameter(2) == "follow") {
                $params = $payload->getParameters();
                $__room = $world->getRoom($event->getMessage()->getParameter(0));

                array_shift($params);
                array_shift($params);
                array_shift($params);
                $id = join(" ", $params);

                $t = $world->getExtra();
                $status = ltrim($event->getMessage()->getParameter(1), ":");
                var_dump($t->post('friendsship/create', ['screen_name' => $id]));

                $event->getStream()->writeln(":`fq` NOTICE `room` :you've followed `nick`",
                    "fq", "ptig!~ptig@irc.example.net",
                    "room", $__room->getName(),
                    "nick", $id
                );
            } else if ($payload->getParameter(2) == "unfollow") {
                $params = $payload->getParameters();
                $__room = $world->getRoom($event->getMessage()->getParameter(0));

                array_shift($params);
                array_shift($params);
                array_shift($params);
                $id = join(" ", $params);

                $t = $world->getExtra();
                $status = ltrim($event->getMessage()->getParameter(1), ":");
                var_dump($t->post('friendsship/destroy', ['screen_name' => $id]));

                $event->getStream()->writeln(":`fq` NOTICE `room` :you've unfollowed `nick`",
                    "fq", "ptig!~ptig@irc.example.net",
                    "room", $__room->getName(),
                    "nick", $id
                );
            } else if ($payload->getParameter(2) == "block") {
                $params = $payload->getParameters();
                $__room = $world->getRoom($event->getMessage()->getParameter(0));

                array_shift($params);
                array_shift($params);
                array_shift($params);
                $id = join(" ", $params);

                $t = $world->getExtra();
                $status = ltrim($event->getMessage()->getParameter(1), ":");
                var_dump($t->post('blocks/create', ['screen_name' => $id]));

                $event->getStream()->writeln(":`fq` NOTICE `room` :you've blocked `nick`",
                    "fq", "ptig!~ptig@irc.example.net",
                    "room", $__room->getName(),
                    "nick", $id
                );
            } else if ($payload->getParameter(2) == "unblock") {
                $params = $payload->getParameters();
                $__room = $world->getRoom($event->getMessage()->getParameter(0));

                array_shift($params);
                array_shift($params);
                array_shift($params);
                $id = join(" ", $params);

                $t = $world->getExtra();
                $status = ltrim($event->getMessage()->getParameter(1), ":");
                var_dump($t->post('blocks/destroy', ['screen_name' => $id]));

                $event->getStream()->writeln(":`fq` NOTICE `room` :you've blocked `nick`",
                    "fq", "ptig!~ptig@irc.example.net",
                    "room", $__room->getName(),
                    "nick", $id
                );
            } else if ($payload->getParameter(2) == "list") {
                $__room = $world->getRoom($event->getMessage()->getParameter(0));

                if ($payload->getParameter(3) == "add") {
                    $params = $payload->getParameters();
                    $__room = $world->getRoom($event->getMessage()->getParameter(0));

                    array_shift($params);
                    array_shift($params);
                    array_shift($params);
                    array_shift($params);
                    $screen_name = array_shift($params);
                    $slug = array_shift($params);// slug

                    $t = $world->getExtra();
                    $info = $world->getOwnerInfo();
                    $status = ltrim($event->getMessage()->getParameter(1), ":");
                    var_dump($t->post('lists/members/create', [
                        'screen_name' => $screen_name,
                        "slug" => $slug,
                        "owner_screen_name" => $info['screen_name']
                    ]));

                    $event->getStream()->writeln(":`fq` NOTICE `room` :you've added `nick` to `slug`",
                        "fq", "ptig!~ptig@irc.example.net",
                        "room", $__room->getName(),
                        "nick", $screen_name,
                        "slug", $slug
                    );
                } else if ($payload->getParameter(3) == "create") {
                    $params = $payload->getParameters();
                    $__room = $world->getRoom($event->getMessage()->getParameter(0));

                    array_shift($params);
                    array_shift($params);
                    array_shift($params);
                    array_shift($params);
                    $name = array_shift($params);
                    $private = array_shift($params);

                    $t = $world->getExtra();
                    $info = $world->getOwnerInfo();
                    $status = ltrim($event->getMessage()->getParameter(1), ":");

                    $params = [
                        'name' => $name,
                    ];
                    if (!empty($private)) {
                        $params['mode'] = "private";
                    }

                    var_dump($t->post('lists/create', $params));

                    $event->getStream()->writeln(":`fq` NOTICE `room` :you've created `list`",
                        "fq", "ptig!~ptig@irc.example.net",
                        "list", $name
                    );
                } else if ($payload->getParameter(3) == "remove") {
                    $params = $payload->getParameters();
                    $__room = $world->getRoom($event->getMessage()->getParameter(0));

                    array_shift($params);
                    array_shift($params);
                    array_shift($params);
                    array_shift($params);
                    $name = array_shift($params);
                    $private = array_shift($params);

                    $t = $world->getExtra();
                    $info = $world->getOwnerInfo();
                    $status = ltrim($event->getMessage()->getParameter(1), ":");

                    $params = [
                        'slug' => $name,
                        'owner_screen_name' => $info['screen_name'],
                    ];
                    var_dump($t->post('lists/destroy', $params));

                    $event->getStream()->writeln(":`fq` NOTICE `room` :you've removed `list`",
                        "fq", "ptig!~ptig@irc.example.net",
                        "list", $name
                    );
                } else {
                    $event->getStream()->writeln(":`fq` NOTICE `room` :`act` sub command does not supported yet",
                        "fq", "ptig!~ptig@irc.example.net",
                        "room", $__room->getName(),
                        "act", $payload->getParameter(3)
                    );

                }
            } else {
                $hit = false;
                $__room = $world->getRoom($event->getMessage()->getParameter(0));

                $a = $world->getInputFilters();
                foreach ($world->getCommands() as $command) {
                    $a[] = $command;
                }
                foreach ($a as $filter) {
                    if (method_exists($filter, "matchAction")) {
                        if ($filter->matchAction($payload->getParameter(2))) {
                            $filter->executeAction($__room, $event);
                            $hit = true;
                            break;
                        }
                    }
                }

                if (!$hit) {
                    $event->getStream()->writeln(":`fq` NOTICE `room` :`act` command does not supported yet",
                        "fq", "ptig!~ptig@irc.example.net",
                        "room", $__room->getName(),
                        "act", $payload->getParameter(3)
                    );
                }
            }

            return;
        }

        if ($world->roomExists($event->getMessage()->getParameter(0))) {
            $__room = $world->getRoom($event->getMessage()->getParameter(0));

            $t = $world->getExtra();
            $status = ltrim($event->getMessage()->getParameter(1), ":");

            $t->post('statuses/update', ['status' => $status]);
            $event->getStream()->writeln("NOTE :twitter updated");
            $event->getStream()->writeln(":`host` TOPIC `room` :`msg`",
                "host", $event->getUser()->getFQ(),
                "room", $event->getMessage()->getParameter(0),
                "msg", ltrim($event->getMessage()->getParameter(1), ":")
            );
        }
    }
}
