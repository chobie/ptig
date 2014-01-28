<?php
namespace Chobie\Net\IRC;

class Message
{
    const COMMAND_UNKNOWN = 0x00;
    const COMMAND_NICK = 0x01;
    const COMMAND_USER = 0x02;
    const COMMAND_JOIN = 0x03;
    const COMMAND_CAP = 0x04;
    const COMMAND_MODE = 0x05;
    const COMMAND_WHO = 0x06;
    const COMMAND_PRIVMSG = 0x07;
    const COMMAND_PING = 0x08;
    const COMMAND_PONG = 0x09;
    const COMMAND_ME = 0x10;
    const COMMAND_TOPIC = 0x11;
    const COMMAND_PART = 0x12;
    const COMMAND_QUIT = 0x13;
    const COMMAND_ISON = 0x14;

    protected $command = "";
    protected $command_type = 0x00;
    protected $parameters = array();

    public function __construct()
    {
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function setCommand($command)
    {
        $this->command = $command;
        $this->command_type = $this->getCommandType2($command);
    }

    public function getCommandType()
    {
        return $this->command_type;
    }

    public function addParameter($parameter)
    {
        $this->parameters[] = $parameter;
    }

    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    public function getParameter($offset)
    {
        return $this->parameters[$offset];
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    protected function getCommandType2($command)
    {
        $command = strtolower($command);
        switch ($command) {
            case "nick":
                return self::COMMAND_NICK;
            case "user":
                return self::COMMAND_USER;
            case "join":
                return self::COMMAND_JOIN;
            case "cap":
                return self::COMMAND_CAP;
            case "mode":
                return self::COMMAND_MODE;
            case "who":
                return self::COMMAND_WHO;
            case "privmsg":
                return self::COMMAND_PRIVMSG;
            case "ping":
                return self::COMMAND_PING;
            case "pong":
                return self::COMMAND_PONG;
            case "me":
                return self::COMMAND_ME;
            case "topic":
                return self::COMMAND_TOPIC;
            case "part":
                return self::COMMAND_PART;
            case "quit":
                return self::COMMAND_QUIT;
            case "ison":
                return self::COMMAND_ISON;
            default:
                return self::COMMAND_UNKNOWN;
        }
    }
}