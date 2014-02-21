<?php
namespace Chobie\Net\Twitter\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NaiveBayesSubscriber
    implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            "irc.kernel.append_room" => array(
                array('appendRoom', 500)
            ));
    }

    public static function appendRoom(\Chobie\Net\IRC\Server\Event\AppendRoom $event)
    {
        $room = $event->getRoom();

        if (preg_match("/#classified/", $room->getName())) {
            if (!$room->getPayload()) {
                // for now
                $room->setPayload(new \Chobie\Net\Twitter\Timeline\PseudoTimeline(null, $room, []));
            }

            $users = array();
            foreach ($room->getUsers() as $user) {
                $users[] = $user->getNick();
            }

            echo "\e[32m# append new room {$room->getName()}\e[m\n";
            foreach ($room->getUsers(["dummy" => false]) as $user) {
                $stream = new \Chobie\IO\OutputStream2($user->socket);

                $stream->writeln(":irc.example.net 353 `nick` = `room` :`users`",
                    "nick", $user->getNick(),
                    "room", $event->getRoom()->name,
                    "users", join(",", $users));
                $stream->writeln(":irc.example.net 366 `nick` `room` :End of NAMES list",
                    "nick", $user->getNick(),
                    "room", $event->getRoom()->name);

            }
        }
    }
}
