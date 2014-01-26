<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Chobie\Net\IRC\Server\World;

trait HandlerWho
{
    public function onWho(OutputStream $stream, User $user, Message $payload)
    {
        $stream->writeln(":irc.example.net 315 `nick` `room` :End of WHO list",
            "nick", $user->nick,
            "room", $payload->getParameter(0)
        );
    }

}