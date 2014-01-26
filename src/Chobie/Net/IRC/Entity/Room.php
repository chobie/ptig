<?php
namespace Chobie\Net\IRC\Entity;

use Chobie\Net\IRC\Server\World;

class Room
{
    public $name;
    public $users = array();
    public $clear_when_no_user = false;

    protected $world;

    protected $payload;

    public $immidiately = false;

    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getWorld(World $world)
    {
        return $this->world;
    }

    public function setWorld(World $world)
    {
        $this->world = $world;
    }

    public function getName()
    {
        return $this->name;
    }

    public function removeUser(User $user)
    {
        if ($this->isJoined($user->getNick())) {
            unset($this->users[$user->getNick()]);
        }
    }

    public function addUser(User $user)
    {
        $this->users[$user->nick] = $user;
    }

    public function partUser($nick)
    {
        if (isset($this->users[$nick])) {
            unset($this->users[$nick]);
        }
    }

    public function getUsers()
    {
        $users = $this->users;
        $result = array();
        foreach ($users as $nick => $u) {
            if (!$u->dummy) {
                $result[$nick] = $u;
            }
        }
        return $result;
    }

    public function isJoined($nick)
    {
        if (isset($this->users[$nick])) {
            return true;
        } else {
            return false;
        }
    }

}