<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Server\World;

trait HandlerUser
{
    public function onUser(OutputStream $stream, User $user)
    {
        $world = World::getInstance();
        $world->addUser($user);

        $stream->writeln(":irc.example.net 001 `user` :Welcome to the Internet relay network!",
            "user", $user->nick
        );
    }
}