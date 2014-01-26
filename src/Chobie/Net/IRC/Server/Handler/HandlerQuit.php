<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Chobie\Net\IRC\Server\Event\QuitUser;
use Chobie\Net\IRC\Server\World;

trait HandlerQuit
{
    public function onQuit(OutputStream $stream, User $user, Message $payload)
    {
        $world = World::getInstance();
        $world->removeUser($user);

        $world->getEventDispatcher()->dispatch("irc.event.quit_user", new QuitUser(
            $user
        ));
    }
}