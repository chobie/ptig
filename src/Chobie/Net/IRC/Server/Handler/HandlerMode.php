<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;

trait HandlerMode
{
    public function onMode(OutputStream $stream, User $user, Message $payload)
    {
        $stream->writeln(":irc.example.net 324 `nick` `mode`",
            "nick", $user->nick,
            "mode", "+"
        );
    }
}