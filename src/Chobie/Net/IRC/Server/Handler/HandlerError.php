<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Exception\UnsupportedCommandException;

trait HandlerError
{
    public function onError(\Chobie\IO\OutputStream $stream, User $user, \Exception $e)
    {
        if ($e instanceof UnsupportedCommandException) {
            $stream->writeln(":irc.example.net 421 `user` `command` :Unknown command",
                "user", $user->nick,
                "command", "command");
        }
    }
}