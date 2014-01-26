<?php
namespace Chobie\Net\IRC\Server\Handler;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;

trait HandlerPong
{
    public function onPong(OutputStream $stream, User $user, Message $payload)
    {
    }
}