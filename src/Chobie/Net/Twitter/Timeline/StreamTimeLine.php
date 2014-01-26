<?php
namespace Chobie\Net\Twitter\Timeline;

use Chobie\Net\IRC\Server\Event\NewMessage;
use Chobie\Net\IRC\Server\World;
use Chobie\Net\Twitter\Timeline;

class StreamTimeline extends Timeline
{
    public function update(World $world)
    {
        $this->last = time();
        $params = $this->params["params"];
        if ($this->since_id) {
            $params["since_id"] = $this->since_id;
        }

        $tweet = null;
        $this->client->consume(10, function($tweet) use($world) {
            if (isset($tweet['friends'])) {
                return;
            }
            if (isset($tweet['event'])) {
                return;
            }
            if (isset($tweet['delete'])) {
                // IRC doesn't support delete
                return;
            }

            $tweet = $this->processTweet($tweet);
            $world->getEventDispatcher()->dispatch("irc.kernel.new_message", new NewMessage(
                $this->room,
                $tweet['user']['screen_name'],
                $tweet['text']
            ));
        });
    }
}