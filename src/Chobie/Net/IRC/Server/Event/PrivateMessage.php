<?php
namespace Chobie\Net\IRC\Server\Event;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Symfony\Component\EventDispatcher\Event;

class PrivateMessage extends Event
{
    protected $stream;
    protected $user;
    protected $message;

    public function __construct(OutputStream $stream, User $user, Message $payload)
    {
        $this->stream = $stream;
        $this->user = $user;
        $this->message = $payload;
    }

    /**
     * @return \Chobie\Net\IRC\Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return \Chobie\IO\OutputStream
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @return \Chobie\Net\IRC\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}