<?php
namespace Chobie\Net\IRC\Server\Event;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\Room;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Symfony\Component\EventDispatcher\Event;

class DebugMessage extends Event
{
    /** @var  string $message */
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function __toString()
    {
        return $this->message;
    }

}