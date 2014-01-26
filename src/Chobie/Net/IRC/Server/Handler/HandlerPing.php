<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;

trait HandlerPing
{
    public function onPing(OutputStream $stream, User $user, Message $payload)
    {
        $stream->writeln("PONG irc.example.net");
    }
}