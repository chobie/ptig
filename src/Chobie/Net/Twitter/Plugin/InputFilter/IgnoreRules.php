<?php

namespace Chobie\Net\Twitter\Plugin\InputFilter;

use Chobie\Net\IRC\Entity\Room;
use Chobie\Net\IRC\Server\Event\NewMessage;
use Chobie\Net\IRC\Server\Event\PrivateMessage;
use Chobie\Net\IRC\Server\World;

class IgnoreRules
{
    protected $rules = array();

    public function __construct($args = array())
    {
        foreach ($args as $arg) {
            $this->addRule($arg);
        }
    }

    public function matchAction($action)
    {
        if ($action == "ignore") {
            return true;
        } else {
            return false;
        }
    }

    public function executeAction(Room $room, PrivateMessage $event)
    {
        $payload = $event->getMessage();
        $params = $payload->getParameters();

        array_shift($params);
        array_shift($params);
        array_shift($params);
        $name = array_shift($params);

        $this->addRule($name);

        $event->getStream()->writeln(":`fq` NOTICE `room` :regexp `regexp` added to ignore list",
            "fq", "ptig!~ptig@irc.example.net",
            "room", $room->getName(),
            "regexp", $name
        );

        $world = World::getInstance();
        $config = $world->getConfig();
        foreach ($config['plugins']['input_filters'] as &$filter) {
            if (isset($filter['class']) && preg_match("/IgnoreRules/", $filter['class'])) {
                $filter['args'][] = $name;
            }
        }
        $world->setConfig($config);
    }

    public function getName()
    {
        return "ignore_rules";
    }

    public function addRule($rule)
    {
        $this->rules[] = $rule;
    }

    public function process(NewMessage $message)
    {
        foreach ($this->rules as $rule) {
            if (preg_match("/$rule/", $message->getMessage())) {
                echo "Message Ignored: matches $rule\n";
                return false;
            }
        }

        return true;
    }
}