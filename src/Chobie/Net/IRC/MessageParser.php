<?php
namespace Chobie\Net\IRC;

use Chobie\IO\Stream;
use Chobie\Net\IRC\Exception\UnsupportedCommandException;
use Chobie\Net\IRC\Server\Event\DebugMessage;
use Chobie\Net\IRC\Server\World;

class MessageParser
{
    public function __construct()
    {
    }

    public function subparse($line, $count, &$advanced)
    {
        $result = array();
        $offset = 0;
        for ($i = 0; $i < strlen($line); $i++) {
            if ($line[$i] == " ") {
                $result[] = substr($line, $offset, $i - $offset);
                $offset = ++$i;

                if (count($result)+1 >= $count) {
                    $result[] = substr($line, $offset);
                    $offset = strlen($line);
                    break;
                }
            }
        }
        if ($offset < strlen($line)) {
            $result[] = $line;
            $offset = strlen($line);
        }

        $advanced = $offset;
        return $result;
    }

    public function hasAction($line)
    {
        if (preg_match("/ACTION/", $line)) {
            return true;
        }
        return false;
    }

    public function parse(Stream $stream)
    {
        $line = $stream->getLine();
        $world = World::getInstance();
        $world->getEventDispatcher()->dispatch("debug", new DebugMessage($line));

        if (!$line) {
            return;
        }
        $command = null;

        $payload = new Message();
        $state = 0;
        $offset = 0;
        for ($i = 0; $i < strlen($line); $i++) {
            $code = ord($line[$i]);
            if ($state == 0) {
                if (!((0x41 <= $code && $code <= 0x5a) ||
                    (0x61 <= $code && $code <= 0x7a))
                ) {
                    $command = substr($line, $offset, $i);
                    $payload->setCommand($command);
                    $state = 1;
                }
            } else if ($state == 1) {
                $advanced = 0;
                $parts = array();
                switch ($payload->getCommandType()) {
                    case Message::COMMAND_NICK:
                        $parts = $this->subparse(substr($line, $i), 2, $advanced);
                        break;
                    case Message::COMMAND_USER:
                        $parts = $this->subparse(substr($line, $i), 4, $advanced);
                        break;
                    case Message::COMMAND_JOIN:
                        $parts = $this->subparse(substr($line, $i), 2, $advanced);
                        var_dump($parts);
                        break;
                    case Message::COMMAND_CAP:
                        $parts = $this->subparse(substr($line, $i), 4, $advanced);
                        break;
                    case Message::COMMAND_MODE:
                        $parts = $this->subparse(substr($line, $i), 4, $advanced);
                        break;
                    case Message::COMMAND_WHO:
                        $parts = $this->subparse(substr($line, $i), 4, $advanced);
                        break;
                    case Message::COMMAND_PRIVMSG:
                        $parts = $this->subparse(substr($line, $i), 2, $advanced);
                        if ($this->hasAction($parts[1])) {
                            $_parts = explode(" ", $parts[1]);
                            array_pop($parts);
                            foreach ($_parts as $p) {
                                $parts[] = $p;
                            }
                        }
                        break;
                    case Message::COMMAND_PING:
                        $parts = $this->subparse(substr($line, $i), 2, $advanced);
                        break;
                    case Message::COMMAND_PONG:
                        $parts = $this->subparse(substr($line, $i), 2, $advanced);
                        break;
                    case Message::COMMAND_ME:
                        $parts = $this->subparse(substr($line, $i), 2, $advanced);
                        break;
                    case Message::COMMAND_TOPIC:
                        $parts = $this->subparse(substr($line, $i), 2, $advanced);
                        break;
                    case Message::COMMAND_PART:
                        $parts = $this->subparse(substr($line, $i), 2, $advanced);
                        break;
                    case Message::COMMAND_QUIT:
                        $parts = $this->subparse(substr($line, $i), 2, $advanced);
                        break;
                    default:
                        throw new UnsupportedCommandException("Not supported");
                }
                $payload->setParameters($parts);

                $i = $i + $advanced;
            }
        }

        return $payload;
    }
}