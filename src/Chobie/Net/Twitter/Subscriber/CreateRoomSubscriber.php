<?php
namespace Chobie\Net\Twitter\Subscriber;

use Chobie\Net\IRC\Server\World;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateRoomSubscriber
    implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            "irc.kernel.create_room" => array(
                array('printDebug', 500)
            ));
    }

    public static function printDebug(\Chobie\Net\IRC\Server\Event\CreateRoom $event)
    {
        $world = World::getInstance();
        $conf = $world->getConfig();

        if ($event->getRoom()->getName() == "#twitter") {
            // #twitter is registered as a special channel for streaming.
            $timeilne = new \Chobie\Net\Twitter\Timeline\StreamTimeLine(
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
    }
}
