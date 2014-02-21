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
            if (isset($tweet['direct_message'])) {
                $target = $tweet['direct_message'];

                $target['shorten_id'] = base_convert(++$this->count, 10, 32);
                $this->setHistory((string)$target['shorten_id'], $target['id'], $target['sender']['screen_name'], $target['text']);
                $target = $this->processTweet($target);

                $room_name = $target['sender']['screen_name'];
                $info = $world->getOwnerInfo();
                if ($room_name == $info['screen_name']) {
                    return;
                }

                if (!$world->roomExists($room_name)) {
                    $user = $world->getUserByNick($target['sender']['screen_name'], true);
                    $room = $world->appendRoom(function(\Chobie\Net\IRC\Entity\Room $room) use ($user, $room_name, $owner){
                        $room->name = $room_name;
                        $room->setPayload(new \Chobie\Net\Twitter\Timeline\PseudoTimeline(null, $room, []));
                        $room->addUser($user);
                        if ($owner) {
                            $room->addUser($owner);
                        }
                    });
                } else {
                    $room = $world->getRoom($room_name);
                }

                $world->getEventDispatcher()->dispatch("irc.kernel.new_message", new NewMessage(
                    $target['sender']['screen_name'],
                    $target['sender']['screen_name'],
                    $target['text'],
                    $target
                ));
                return;
            }
            if (isset($tweet['event'])) {
                if ($tweet['event'] == 'favorite') {
                    $target = $tweet['target_object'];

                    $target['shorten_id'] = base_convert(++$this->count, 10, 32);
                    $this->setHistory((string)$target['shorten_id'], $target['id'], $target['user']['screen_name'], $target['text']);
                    $target = $this->processTweet($target);
                    $world->getEventDispatcher()->dispatch("irc.kernel.new_message", new NewMessage(
                        "#favorites",
                        $tweet['source']['screen_name'],
                        "favoried: " . $target['text'],
                        $target
                    ));
                }
                return;
            }

            if (isset($tweet['delete'])) {
                // IRC doesn't support delete
                return;
            }

            $tweet['shorten_id'] = base_convert(++$this->count, 10, 32);
            $this->setHistory((string)$tweet['shorten_id'], $tweet['id'], $tweet['user']['screen_name'], $tweet['text']);

            $tweet = $this->processTweet($tweet);
            $world->getEventDispatcher()->dispatch("irc.kernel.new_message", new NewMessage(
                $this->room,
                $tweet['user']['screen_name'],
                $tweet['text'],
                $tweet
            ));
        });
    }
}