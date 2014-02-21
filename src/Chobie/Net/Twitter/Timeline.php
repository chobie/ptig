<?php
namespace Chobie\Net\Twitter;


use Chobie\Net\IRC\Server\Event\NewMessage;

class Timeline
{
    protected $time;
    protected $since_id;

    protected $client;

    protected $room;

    protected $interval;

    protected $params = array();

    protected $histories = array();

    protected $count = 0;

    protected $max_history = 300;

    public function incrementCount()
    {
        return ++$this->count;
    }

    public function getRoom()
    {
        return $this->room;
    }

    public function getHistory($id)
    {
        if (array_key_exists($id, $this->histories)) {
            return $this->histories[$id];
        } else {
            return false;
        }
    }

    public function setHistory($shorten_id, $id, $nick, $message)
    {
        $this->histories[$shorten_id] = array(
            "id" => $id,
            "nick" => $nick,
            "text" => $message
        );

        if (count($this->histories) > $this->max_history) {
            array_splice($this->histories, 0, 100);
        }
    }

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
        $this->params = array_merge(array(
            "params"=> array(),
            "api" => "statuses/home_timeline"
        ), $params);
    }

    public function onCond($time)
    {
        if ($time - $this->last > $this->interval) {
            return true;
        }
    }

    public function update($world)
    {
        echo "UPDATE: " . $this->room . PHP_EOL;
        $this->last = time();

        $params = $this->params["params"];
        if ($this->since_id) {
            $params["since_id"] = $this->since_id;
        }

        $tweet = null;
        $a = $this->client->get($this->params['api'], $params);
        if ($this->params['api'] == "search/tweets" && isset($a['statuses'])) {
            $a = $a['statuses'];
        }
        $a = array_reverse($a);
        foreach ($a as $tweet) {
            $tweet['shorten_id'] = base_convert(++$this->count, 10, 32);
            $this->histories[(string)$tweet['shorten_id']] = array(
                "id" => $tweet['id'],
                "nick" => $tweet['user']['screen_name'],
                "text" => $tweet['text']
            );


            $tweet = $this->processTweet($tweet);
            $world->getEventDispatcher()->dispatch("irc.kernel.new_message", new NewMessage(
                $this->room,
                $tweet['user']['screen_name'],
                $tweet['text'],
                $tweet
            ));
        }
        if ($tweet) {
            $this->since_id = $tweet['id'];
        }
    }

    public function processTweet($tweet)
    {
        if (isset($tweet['retweeted_status'])) {
            $tweet['text'] = "RT @" . $tweet['retweeted_status']['user']['screen_name'] . " " . $tweet['retweeted_status']['text'];
        }
        if (isset($tweet['entities']['urls'])) {
            foreach ($tweet['entities']['urls'] as $url) {

                // instgram
                if (preg_match("!^http://instagram.com/p/(.+?)/$!", $url['expanded_url'])) {
                    $context  = stream_context_create(array('http' =>array('method'=>'GET')));
                    $fd = fopen($url['expanded_url'] . 'media?size=t', 'rb', false, $context);
                    $meta = stream_get_meta_data($fd);
                    fclose($fd);
                    foreach (array_reverse($meta['wrapper_data']) as $value) {
                        if (preg_match("/^Location: (.+)/", $value, $match)) {
                            $url['expanded_url'] = $match[1];
                            break;
                        }
                    }
                }
                $tweet['text'] = str_replace($url['url'], $url['expanded_url'], $tweet['text']);
            }
        }

        if (isset($tweet['entities']['media'])) {
            foreach ($tweet['entities']['media'] as $url) {
                $tweet['text'] = str_replace($url['url'], $url['media_url_https'], $tweet['text']);
            }
        }

        $tweet['text'] .= sprintf(" [%s]", $tweet['shorten_id']);
        return $tweet;
    }
}
