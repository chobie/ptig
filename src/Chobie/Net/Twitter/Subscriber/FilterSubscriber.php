<?php
namespace Chobie\Net\Twitter\Subscriber;

use Chobie\Net\IRC\Server\World;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FilterSubscriber
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
        $world = World::getInstance();
        foreach ($world->getInputFilters() as $filter) {
            if (!$filter->process($event)) {
                $event->stopPropagation();
                return;
            }
        }
    }
}
