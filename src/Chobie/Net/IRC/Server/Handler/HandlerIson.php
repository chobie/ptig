<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream2;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;

//ISON *status
// :irc.example.net 451 * :Connection not registered
trait HandlerIson
{
    public function onIson(OutputStream2 $stream, User $user, Message $payload)
    {
        if ($user->nick) {
            $stream->writeln(":irc.example.net 303 `nick` :", "nick", $user->nick);
        } else {
            $stream->writeln(":irc.example.net 451 * :Connection not registered");
        }
    }
}