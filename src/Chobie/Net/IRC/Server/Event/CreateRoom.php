<?php
namespace Chobie\Net\IRC\Server\Event;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\Room;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Symfony\Component\EventDispatcher\Event;

class CreateRoom extends Event
{
    protected $room;

    public function __construct(Room $room)
    {
        $this->room = $room;
    }

    public function getRoom()
    {
        return $this->room;
    }
}