<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream;
use Chobie\IO\OutputStream2;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Chobie\Net\IRC\Server\World;

trait HandlerTopic
{
    public function onTopic(OutputStream $stream, User $user, Message $payload)
    {
        $world = World::getInstance();

        $room = $world->getRoom($payload->getParameter(0));
        foreach ($room->getUsers() as $u) {
            if ($user->dummy) {
                continue;
            }

            $s = new OutputStream2($u->socket);
            $s->writeln(":`host` TOPIC `room` :`msg`",
                "host", $user->getFQ(),
                "room", $payload->getParameter(0),
                "msg", ltrim($payload->getParameter(1), ":")
            );
        }
    }

}