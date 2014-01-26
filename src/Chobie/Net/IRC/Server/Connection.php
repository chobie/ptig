<?php
namespace Chobie\Net\IRC\Server;

use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\MessageParser;

class Connection
{
    protected $parser;

    protected $handler;

    protected $user;

    public function setParser(MessageParser $parser)
    {
        $this->parser = $parser;
    }

    public function getParser()
    {
        return $this->parser;
    }

    public function setHandler(Handler $handler)
    {
        $this->handler = $handler;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

}