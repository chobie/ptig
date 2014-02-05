<?php
namespace Chobie\Net\Twitter\Timeline;


use Chobie\Net\IRC\Server\Event\NewMessage;
use Chobie\Net\Twitter\Timeline;

class PseudoTimeline extends Timeline
{
    protected $time;
    protected $since_id;

    protected $client;

    protected $room;

    protected $interval;

    protected $params = array();

    protected $histories = array();

    protected $count = 0;

    public function __construct($t, $room, $params, $interval = 180, $default = null)
    {
        if (is_null($default)) {
            $this->last = time();
        } else {
            $this->last = $default;
        }

        $this->client = $t;
        $this->room = $room;
        $this->interval = $interval;
    }

    public function onCond($time)
    {
        return false;
    }

    public function setHistory($shorten_id, $id, $nick, $message)
    {
        $this->histories[$shorten_id] = array(
            "id" => $id,
            "nick" => $nick,
            "text" => $message
        );
    }

    public function update($world)
    {
    }
}
