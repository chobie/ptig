<?php

namespace Chobie\Net\Twitter\Plugin\InputFilter;

use Chobie\Net\IRC\Server\Event\NewMessage;

class DecodeHtmlEntities
{
    protected $rules = array();

    public function getName()
    {
        return "decode_html_entities";
    }

    public function process(NewMessage $message)
    {
        $message->setMessage(html_entity_decode($message->getMessage()));

        return true;
    }
}