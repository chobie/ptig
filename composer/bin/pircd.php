<?php
require dirname(dirname(__DIR__)) . "/vendor/autoload.php";

use \Chobie\IO\Stream;
use \Chobie\Net\IRC\Message;
use \Chobie\Net\IRC\Entity\User;
use \Chobie\Net\IRC\Server\Handler;
use \Chobie\Net\IRC\Server\World;
use \Chobie\Net\IRC\Server;

World::getInstance(function(World $world){
    $world->getEventDispatcher()->addListener("debug", function($event) {
        echo "\e[32m# " . $event . "\e[m\n";
    });

    $world->getEventDispatcher()->addListener("irc.event.private_message",
        function(Server\Event\PrivateMessage $event) {
    });
});

Server::createServer(function(
    Stream $stream, User $user, Message $message, Handler $handler) {
    try {
        $method = "on" . $message->getCommand();
        if (method_exists($handler, $method)) {
            call_user_func_array(array($handler, $method), array(
                $stream,
                $user,
                $message
            ));
        } else {
            throw new \Chobie\Net\IRC\Exception\UnsupportedCommandException();
        }
    } catch (Exception $e) {
        $handler->onError($stream, $user, $e);
    }
})->listen(6668);