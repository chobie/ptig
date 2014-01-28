<?php
namespace Chobie\Net\IRC\Server\Event;

use Chobie\IO\OutputStream;
use Chobie\Net\IRC\Entity\Room;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Message;
use Symfony\Component\EventDispatcher\Event;

class JoinRoom extends Event
{
    protected $room;

    protected $user;

    protected $stream;

    public function __construct(OutputStream $stream, Room $room, User $user)
    {
        $this->stream = $stream;
        $this->room = $room;
        $this->user = $user;
    }

    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @return mixed
     */
    public function getRoom()
    {
        return $this->room;
    }

    public function getUser()
    {
        return $this->user;
    }
}