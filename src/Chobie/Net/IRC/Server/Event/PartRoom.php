<?php
namespace Chobie\Net\IRC\Server\Event;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\Room;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Symfony\Component\EventDispatcher\Event;

class PartRoom extends Event
{
    protected $room;

    protected $user;

    public function __construct(Room $room, User $user)
    {
        $this->room = $room;
        $this->user = $user;
    }

    public function getRoom()
    {
        return $this->room;
    }

    public function getUsers()
    {
        return $this->user;
    }
}