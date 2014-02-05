<?php

namespace Chobie\Net\Twitter\Plugin\InputFilter;

use Chobie\Net\IRC\Entity\Room;
use Chobie\Net\IRC\Server\Event\NewMessage;
use Chobie\Net\IRC\Server\Event\PrivateMessage;

use Camspiers\StatisticalClassifier\Classifier\ComplementNaiveBayes;
use Camspiers\StatisticalClassifier\DataSource\DataArray;
use Camspiers\StatisticalClassifier\Tokenizer\TokenizerInterface;
use Camspiers\StatisticalClassifier\Normalizer\Token\NormalizerInterface;
use Camspiers\StatisticalClassifier\Model\CachedModel;
use Chobie\Net\IRC\Server\World;
use Chobie\Net\Twitter\Timeline\PseudoTimeline;


class MecabWord implements TokenizerInterface
{
    protected $last;

    public function getLastNodes()
    {
        return $this->last;
    }

    /**
     * @{inheritdoc}
     */
    public function tokenize($document)
    {
        $mecab = new \Mecab();
        $nodes = $mecab->parseToNode($document);
        $this->last = $nodes;

        $result = array();
        foreach ($nodes as $node) {
            $tmp = $node->surface;
            if ($tmp) {
                $result[] = $tmp;
            }
        }

        return $result;
    }
}

class MecabNoneNormalizer implements NormalizerInterface
{
    protected $mecab;

    public function __construct($mecab)
    {
        $this->mecab = $mecab;
    }

    public function normalize(array $tokens)
    {
        $result = array();
        foreach ($this->mecab->getLastNodes() as $offset => $node) {
            if (strpos($node->feature, "名詞") !== false) {
                $result[] = $node->surface;
            }
        }
        if (empty($result)) {
            // NOTE(chobie): at least required 1 value.
            $result[] = ".";
        }

        return $result;
    }
}

class NaiveBayes
{
    protected $rules = array();

    /** @var \Camspiers\StatisticalClassifier\DataSource\DataArray $source */
    protected $source;

    protected $classifier;

    public function __construct($args = array())
    {
        $this->source = new DataArray();
        $mecab = new MecabWord();
        $this->classifier = new ComplementNaiveBayes($this->source,
            null,
            null,
            $mecab,
            new MecabNoneNormalizer($mecab)
        );
        $this->file_name = $args['file'];

        $fp = fopen($this->file_name, "r");
        while (!feof($fp)) {
            $line = trim(fgets($fp));
            if (empty($line)) {
                continue;
            }
            list($key, $value) = explode("\t", $line);
            if (empty($value)) {
                continue;
            }

            $value = str_replace("\\n", " ", $value);
            $this->source->addDocument($key, $value);
        }
    }

    public function process(NewMessage $message)
    {
        if (preg_match("/classified/", $message->getRoom())) {
            return true;
        }

        try {
            $class = $this->classifier->classify($message->getMessage());
            $text = sprintf("(%s) %s", $class, $message->getMessage());
            $message->setMessage($text);

            if ($class) {
                $world = World::getInstance();
                $room_name = "#classified@$class";

                $user = $world->getUserByNick($message->getNick(), true);
                $owner = $world->getOwner();

                if (!$world->roomExists($room_name)) {
                    $room = $world->appendRoom(function(Room $room) use ($user, $room_name, $owner){
                        $room->name = $room_name;
                        $room->setPayload(new PseudoTimeline(null, $room, []));
                        $room->addUser($user);
                        if ($owner) {
                            $room->addUser($owner);
                        }
                    });
                } else {
                    $room = $world->getRoom($room_name);
                }

                $world->getEventDispatcher()->dispatch("irc.kernel.new_message", new NewMessage(
                    $room_name,
                    $user->getNick(),
                    $text
                ));
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        return true;
    }

    public function matchAction($action)
    {
        if ($action == "train") {
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
        $id = array_shift($params);

        $timeline = $room->getPayload();
        /** @var \Chobie\Net\Twitter\Timeline $timeline */

        if ($history = $timeline->getHistory($id)) {
            var_dump($history);
            $this->source->addDocument($name, $history['text']);
            file_put_contents($this->file_name,
                sprintf("%s\t%s\n", $name, str_replace("\n", "\\n", $history['text'])),
                FILE_APPEND);

            $event->getStream()->writeln(":`fq` NOTICE `room` :naive_bayes `msg` added to `category`",
                "fq", "ptig!~ptig@irc.example.net",
                "room", $room->getName(),
                "msg", $history['text'],
                "category", $name
            );
        }




    }

    public function getName()
    {
        return "naive_bayes";
    }
}
