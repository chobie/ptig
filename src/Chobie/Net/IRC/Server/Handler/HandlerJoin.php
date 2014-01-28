<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream;
use Chobie\IO\OutputStream2;
use Chobie\Net\IRC\Entity\Room;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Chobie\Net\IRC\Server\Event\JoinRoom;
use Chobie\Net\IRC\Server\World;

trait HandlerJoin
{
    public function onJoin(OutputStream $stream, User $user, Message $payload)
    {
        @list($rooms, $secrets) = $payload->getParameters();
        $world = World::getInstance();

        foreach (explode(",", $rooms) as $room_name) {
            $room_name = trim($room_name);

            if (!$world->roomExists($room_name)) {
                $_room = $world->appendRoom(function(Room $room) use ($user, $room_name){
                    $room->name = $room_name;
                    $room->addUser($user);
                });
            } else {
                $_room = $world->getRoom($room_name);
                $_room->addUser($user);
            }

            $event = new JoinRoom(
                $stream,
                $_room,
                $user
            );

            $users = join(" ", array_keys($event->getRoom()->getUsers()));
            $event->getStream()->writeln(":irc.example.net 353 `nick` = `room` :`users`",
                "nick", $event->getUser()->getNick(),
                "room", $event->getRoom()->name,
                "users", $users);
            $event->getStream()->writeln(":irc.example.net 366 `nick` `room` :End of NAMES list",
                "nick", $event->getUser()->getNick(),
                "room", $event->getRoom()->name);

        }
    }
}