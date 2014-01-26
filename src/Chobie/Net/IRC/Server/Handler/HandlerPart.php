<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream;
use Chobie\IO\OutputStream2;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Chobie\Net\IRC\Server\Event\PartRoom;
use Chobie\Net\IRC\Server\World;

trait HandlerPart
{
    public function onPart(OutputStream $stream, User $user, Message $payload)
    {
        @list($rooms, $message) = $payload->getParameters();
        $world = World::getInstance();

        foreach (explode(",", $rooms) as $room) {
            $room = trim($room);
            $_room = $world->getRoom($room);
            if (!$_room) {
                return;
            }

            foreach ($_room->getUsers(["dummy" => false]) as $u) {
                $s = new OutputStream2($u->socket);
                $stream->writeln(":`fq` PART `channel` :`users`",
                    "fq", $user->getFq(),
                    "channel", $_room->name,
                    "users", $user->nick);
            }
            $_room->partUser($user->nick);
            $world->getEventDispatcher()->dispatch("irc.event.part", new PartRoom(
                $_room,
                $user
            ));
        }

    }
}