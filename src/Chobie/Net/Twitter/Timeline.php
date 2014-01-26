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

    public function getRoom()
    {
        return $this->room;
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
            var_dump($tweet);
            $tweet = $this->processTweet($tweet);
            $world->getEventDispatcher()->dispatch("irc.kernel.new_message", new NewMessage(
                $this->room,
                $tweet['user']['screen_name'],
                $tweet['text']
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
        return $tweet;
    }
}
