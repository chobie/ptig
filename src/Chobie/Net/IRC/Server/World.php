<?php
namespace Chobie\Net\IRC\Server;

use Chobie\Net\IRC\Entity\Room;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Server\Event\AppendRoom;
use Chobie\Net\IRC\Server\Event\CreateRoom;
use Chobie\Net\IRC\Server\Event\QuitUser;
use Symfony\Component\EventDispatcher\EventDispatcher;

class World
{
    protected static $instance;

    protected $users = array();
    protected $rooms = array();

    protected $owner = array();

    protected $extra;

    protected $last_gced = 0;

    protected $config = array();

    /** @var \Symfony\Component\EventDispatcher\EventDispatcher  */
    protected $event_dispatcher;

    protected $timer;

    protected $input_filters = array();
    protected $output_filters = array();

    protected $commands = array();

    public function removeUser(User $user)
    {
        foreach ($this->rooms as $room) {
            /** @var Room $room */
            $room->removeUser($user);
        }

        unset($this->user[$user->nick]);
    }

    public function __construct()
    {
        $this->event_dispatcher = new EventDispatcher();
        $this->timer = new Timer($this);
        $this->timer->start();
    }

    public function getTimer()
    {
        return $this->timer;
    }

    public function getEventDispatcher()
    {
        return $this->event_dispatcher;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function getConfigByKey($name)
    {
        $hit = false;
        $p = $this->config;
        foreach (explode(".", $name) as $key) {
            if (isset($p[$key])) {
                $p = $p[$key];
                $hit = true;
            } else {
                $hit = false;
            }
        }
        if (!$hit) {
            return false;
        }

        return $p;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function gcRooms()
    {
        if (time() - $this->last_gced > 180) {
            $this->last_gced = time();
        }

        foreach ($this->rooms as $key => $room) {
            if ($room->clear_when_no_user && !count($room->getUsers(array("dummy" => false)))) {
                echo 'REMOVE ROOMT!' . $room->getName() . PHP_EOL;
                unset($this->rooms[$key]);
            }
        }
    }

    public function removeRoom($room)
    {
        if (isset($this->rooms[$room])) {
            unset($this->rooms[$room]);
        }
    }

    public function setOwner(User $user)
    {
        $this->owner = $user;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwnerInfo($info)
    {
        $this->owner_info = $info;
    }

    public function getOwnerInfo()
    {
        return $this->owner_info;
    }

    public function setExtra($extra)
    {
        $this->extra = $extra;
    }

    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param null $callable
     * @return World
     */
    public static function getInstance($callable = null)
    {
        if (!self::$instance) {
            self::$instance = new self();
            if (is_callable($callable)) {
                $callable(self::$instance);
            }
        }
        return self::$instance;
    }

    public function addUser(User $user)
    {
        $this->users[$user->nick] = $user;
    }

    public function getUserByNick($nick, $make = false)
    {
        if (isset($this->users[$nick])) {
            return $this->users[$nick];
        } else {
            if ($make) {
                $u = new User();
                $u->nick = $nick;
                $u->dummy = true;
                $this->addUser($u);

                return $u;
            }
        }
    }

    public function getUsers($cond = array())
    {
        if ($cond) {
            $result = array();
            foreach ($this->users as $user) {
                foreach ($cond as $key => $value) {
                    if ($user->$key == $value) {
                        $result[$user->nick] = $user;
                    }
                }
            }
            return $result;
        } else {
            return $this->users;
        }
    }

    public function roomExists($room)
    {
        if (isset($this->rooms[$room])) {
            return true;
        } else {
            return false;
        }
    }

    public function addInputFilter($filter)
    {
        $this->input_filters[$filter->getName()] = $filter;
    }

    public function getInputFilters()
    {
        return $this->input_filters;
    }

    public function addOutputFilter($filter)
    {
        $this->output_filters[$filter->getName()] = $filter;
    }

    public function getOutputFilters()
    {
        return $this->output_filters;
    }

    public function appendRoom($callable)
    {
        $room = new Room();
        $callable($room);
        $this->rooms[$room->name] = $room;

        $this->getEventDispatcher()->dispatch("irc.kernel.create_room", new CreateRoom($room));

        // TODO:
        $this->getEventDispatcher()->dispatch("irc.kernel.append_room", new AppendRoom($room));
        return $room;
    }

    public function setRoom($room)
    {
        $this->rooms[$room->name] = $room;
    }

    public function getRoom($room)
    {
        if ($this->roomExists($room)) {
            return $this->rooms[$room];
        }
    }

    public function getRooms()
    {
        return $this->rooms;
    }

    public function addCommand($command)
    {
        $this->commands[] = $command;
    }

    public function getCommands()
    {
        return $this->commands;
    }
}