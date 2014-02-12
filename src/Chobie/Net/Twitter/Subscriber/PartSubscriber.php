<?php
namespace Chobie\Net\Twitter\Subscriber;

use Chobie\Net\IRC\Server\World;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PartSubscriber
    implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            "irc.event.part" => array(
                array('processPart', 500)
            ));
    }

    public static function processPart(\Chobie\Net\IRC\Server\Event\PartRoom $event)
    {
        if ($event->getRoom()->clear_when_no_user && !count($event->getRoom()->getUsers(['dummy' => false]))) {
            World::getInstance()->getTimer()->removeListener($event->getRoom()->getName());
            World::getInstance()->removeRoom($event->getRoom()->getName());
        }
    }
}
