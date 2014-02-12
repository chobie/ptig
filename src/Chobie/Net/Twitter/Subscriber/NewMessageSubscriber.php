<?php
namespace Chobie\Net\Twitter\Subscriber;

use Chobie\Net\IRC\Server\World;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NewMessageSubscriber
    implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            "irc.kernel.new_message" => array(
                array('processMessage', 500)
            ));
    }

    public static function processMessage(\Chobie\Net\IRC\Server\Event\NewMessage $event)
    {
        $payload = $event->getPayload();

        $world = World::getInstance();
        $info = $world->getOwnerInfo();
        if ($payload['in_reply_to_screen_name'] == $info['screen_name'] && $event->getRoom() != "#mention") {
            $room_name = "#mention";

            $user = $world->getUserByNick($event->getNick(), true);
            $owner = $world->getOwner();

            if (!$world->roomExists($room_name)) {
                $room = $world->appendRoom(function(\Chobie\Net\IRC\Entity\Room $room) use ($user, $room_name, $owner){
                    $room->name = $room_name;
                    $room->setPayload(new \Chobie\Net\Twitter\Timeline\PseudoTimeline(null, $room, []));
                    $room->addUser($user);
                    if ($owner) {
                        $room->addUser($owner);
                    }
                });
            } else {
                $room = $world->getRoom($room_name);
            }

            $timeline = $room->getPayload();
            $tweet = $event->getPayload();

            $tweet['shorten_id'] = base_convert($timeline->incrementCount(), 10, 32);
            $timeline->setHistory((string)$tweet['shorten_id'], $tweet['id'], $tweet['user']['screen_name'], $tweet['text']);

            $tweet = $timeline->processTweet($tweet);
            $newevent = clone $event;
            $newevent->setRoom("#mention");
            $newevent->setMessage($tweet['text']);
            $newevent->setPayload($tweet);

            $world->getEventDispatcher()->dispatch("irc.kernel.new_message", $newevent);
        }

    }
}
