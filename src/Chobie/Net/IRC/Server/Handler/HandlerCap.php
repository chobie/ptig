<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream2;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;

//:irc.example.net CAP * LS :multi-prefix

trait HandlerCap
{
    public function onCap(OutputStream2 $stream, User $user, Message $payload)
    {
        $stream->writeln(":irc.example.net CAP * LS :multi-prefix");
    }
}