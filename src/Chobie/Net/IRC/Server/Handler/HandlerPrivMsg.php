<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream;
use Chobie\IO\OutputStream2;
use Chobie\Net\IRC\Entity\Room;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Chobie\Net\IRC\Server\Event\PrivateMessage;
use Chobie\Net\IRC\Server\World;
use Symfony\Component\EventDispatcher\Event;

trait HandlerPrivMsg
{
    public function onPrivMsg(OutputStream $stream, User $user, Message $payload)
    {
        $world = World::getInstance();
        $owner = $world->getOwnerInfo();

        $room = $world->getRoom($payload->getParameter(0));
        foreach ($room->getUsers(["dummy" => false]) as $u) {
            if ($u == $user) {
                continue;
            }

            $s = new OutputStream2($u->socket);
            $s->writeln(":`host` PRIVMSG `room` :`msg`",
                "host", $user->getFQ(),
                "room", $payload->getParameter(0),
                "msg", ltrim($payload->getParameter(1), ":")
            );
        }

        $world->getEventDispatcher()->dispatch("irc.event.private_message", new PrivateMessage(
            $stream,
            $user,
            $payload
        ));
    }
}