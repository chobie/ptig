<?php
namespace Chobie\Net\IRC\Entity;

class User
{
    public $nick;
    public $name;
    public $host;
    public $server = "localhost";
    public $realname;
    public $socket;
    public $last;
    public $registered;
    public $dummy = false;

    public function __construct()
    {
        $this->registered = $this->last = time();
    }

    public function getNick()
    {
        return $this->nick;
    }

    public function getFQ()
    {
        return sprintf("%s!~%s@%s", $this->nick, $this->nick, $this->server);
    }
}