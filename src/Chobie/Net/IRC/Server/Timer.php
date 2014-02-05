<?php
namespace Chobie\Net\IRC\Server;

use Chobie\IO\OutputStream2;

class Timer
{
    protected $timer;

    protected $world;

    protected $listeners = array();

    protected $tasks = array();

    public function __construct(World $world)
    {
        $this->world = $world;
        $this->timer = uv_timer_init();
    }

    public function getWorld()
    {
        return $this->world;
    }

    public function runOnce($callable, $time)
    {
        $this->tasks[] = array(
            "task" => $callable,
            "time" => $time,
        );
    }

    public function addListener($name, $callable)
    {
        $this->listeners[$name] = $callable;
    }

    public function removeListener($name)
    {
        if (isset($this->listeners[$name])) {
            unset($this->listeners[$name]);
        }
    }

    public function getListeners()
    {
        return $this->listeners;
    }

    public function start()
    {
        uv_timer_start($this->timer, 10, 1000, function($stat, $timer){
            $time = time();
            echo "\e[34m#" . " Execute Scheduled Task {$time}" . "\e[m\n";

            foreach ($this->getWorld()->getUsers(["dummy" => false]) as $u) {
                if ($time - $u->last > 180) {
                    $o = new OutputStream2($u->socket);
                    $o->writeln("PING :irc.example.net");
                    $u->last = $time;
                }
            }

            foreach ($this->getListeners() as $listener) {
                $listener($time, $this->getWorld());
            }

            foreach ($this->tasks as $offset => $task) {
                if ($time - $task['time'] < 0) {
                    $task['task']();
                    unset($this->tasks[$offset]);
                }
            }
        });
    }
}