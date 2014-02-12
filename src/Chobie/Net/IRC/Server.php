<?php
namespace Chobie\Net\IRC;

use Chobie\IO\OutputStream;
use Chobie\IO\OutputStream2;
use Chobie\IO\Stream;
use Chobie\Net\IRC\Entity\Room;
use Chobie\Net\IRC\Entity\User;
use Chobie\Net\IRC\Exception\UnsupportedCommandException;
use Chobie\Net\IRC\Server\Connection;
use Chobie\Net\IRC\Server\ConnectionPool;
use Chobie\Net\IRC\Server\Event\JoinRoom;
use Chobie\Net\IRC\Server\Event\NewMessage;
use Chobie\Net\IRC\Server\Event\QuitUser;
use Chobie\Net\IRC\Server\Handler;
use Chobie\Net\IRC\Server\World;

class Server
{
    /** @var resource $server */
    protected $server;

    /** @var Callable $listener */
    protected $listener;

    protected $clients = array();

    protected $parsers = array();

    protected $world;

    public function __construct()
    {
        $this->server = uv_tcp_init();
        $this->world = World::getInstance();

        $this->world->getEventDispatcher()->addListener("irc.event.quit_user", function(QuitUser $event) {
            echo "QUIT User";
        });
        $this->world->getEventDispatcher()->addListener("irc.event.join", function(JoinRoom $event) {
            foreach ($event->getRoom()->getUsers(["dummy" => false]) as $u) {
                $o = new OutputStream2($u->socket);
                $o->writeln(":`user` JOIN :`room`",
                    "user", $event->getUser()->getFQ(),
                    "room", $event->getRoom()->name
                );
            }
        });

        $this->world->getEventDispatcher()->addListener("irc.kernel.new_message", function(NewMessage $event) {
            $room = $this->world->getRoom($event->getRoom());
            $sender = $this->world->getUserByNick($event->getNick(), true);

            /** @var Room $room */
            if (!$room->isJoined($sender->getNick())) {
                $room->addUser($sender);
            }

            foreach ($room->getUsers(["dummy" => false]) as $user) {
                /** @var User $user */
                if ($user->getNick() == $event->getNick()) {
                    continue;
                }

                $stream = new OutputStream2($user->socket);
                foreach (preg_split("/\r?\n/", $event->getMessage()) as $line) {
                    $stream->writeln(":`host` PRIVMSG `room` :`msg`",
                        "host", $sender->getFq(),
                        "room", $event->getRoom(),
                        "msg", $line
                    );
                }


            }
        }, 100);
    }

    public function onShutdown($handle, $status)
    {
        echo "SHUTDOWN\n";
        uv_close($handle, array($this, "onClose"));
    }

    public function onClose($handle)
    {
        echo "CLOSE\n";
    }

    public function addListener($listener)
    {
        $this->listener = $listener;
    }

    public function onRead($client, $nread, $buffer)
    {
        if ($nread < 0) {
            $object = ConnectionPool::$clients[(int)$client];

            unset(ConnectionPool::$clients[(int)$client]);
            unset($object);
            uv_shutdown($client, array($this, "onShutdown"));
            return;
        }

        $stream = new Stream($buffer);
        $conn = ConnectionPool::$clients[(int)$client];
        /** @var Connection $conn */

        $handler = $conn->getHandler();
        $user = $conn->getUser();
        $ostream = new OutputStream2($client);

        if ($user->getNick() && !World::getInstance()->getOwner()) {
            World::getInstance()->setOwner($user);
        }

        while (!$stream->isEmpty()) {
            try {
                $payload = $conn->getParser()->parse($stream);
                if (is_null($payload)) {
                    return;
                }

                $listener = $this->listener;
                $listener($ostream, $user, $payload, $handler);
            } catch (UnsupportedCommandException $e) {
                $handler->onError($ostream, $user, $e);
            }
        }
    }

    public function onConnect($server, $status)
    {
        $client = uv_tcp_init();
        uv_tcp_nodelay($client, 1);
        uv_accept($server,$client);

        ConnectionPool::$clients[(int)$client] = $conn = new Connection();
        $conn->setParser(new MessageParser());
        $conn->setUser(new User());
        $conn->setHandler(new Handler());

        $world = World::getInstance();
        $user = $conn->getUser();
        $user->socket = $client;
        $world->addUser($user);

        uv_read_start($client, array($this, "onRead"));
    }

    public function listen($port)
    {
        uv_tcp_nodelay($this->server, 1);
        uv_tcp_bind($this->server, uv_ip4_addr("127.0.0.1",$port));
        uv_listen($this->server, 511, array($this, "onConnect"));

        uv_run(uv_default_loop());
    }

    public static function createServer($callable = null)
    {
        $server = new self();
        $server->addListener($callable);

        return $server;
    }
}