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
    }

    public function process(NewMessage $message)
    {
        $class = $this->classifier->classify($message->getMessage());

        printf("%s: %s\n", $class, $message->getMessage());

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
            $this->source->addDocument($name, $history['text']);

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
