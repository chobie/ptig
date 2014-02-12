<?php
namespace Chobie\Net\Twitter\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DebugSubscriber
    implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            "debug" => array(
                array('printDebug', 500)
            ));
    }

    public static function printDebug(\Chobie\Net\IRC\Server\Event\DebugMessage $event)
    {
        echo "\e[32m# " . $event . "\e[m\n";
    }
}
