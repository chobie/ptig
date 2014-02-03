<?php

namespace Chobie\Net\Twitter\Plugin\OutputFilter;

use Chobie\Net\IRC\Server\Event\NewMessage;

class EncodeHtmlEntities
{
    protected $rules = array();

    public function getName()
    {
        return "encode_html_entities";
    }

    public function process(NewMessage $message)
    {
        $message->setMessage(htmlspecialchars($message->getMessage()));

        return true;
    }
}