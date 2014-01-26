<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Chobie\Net\IRC\Server\World;

trait HandlerNick
{
    public function onNick(OutputStream $stream, User $user, Message $payload)
    {
        $user->nick = $payload->getParameter(0);
    }
}