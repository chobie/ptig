<?php
namespace Chobie\Net\IRC\Server\Event;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Symfony\Component\EventDispatcher\Event;

class NewMessage extends Event
{
    public function __construct($room, $nick, $message, $payload = array())
    {
        $this->room = $room;
        $this->nick = $nick;
        $this->message = $message;
        $this->payload = $payload;
    }

    public function setRoom($room)
    {
        $this->room = $room;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function getNick()
    {
        return $this->nick;
    }

    /**
     * @return mixed
     */
    public function getRoom()
    {
        return $this->room;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

}