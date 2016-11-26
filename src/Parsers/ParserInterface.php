<?php

namespace Tomaj\BankMailsParser\Parser;

interface ParserInterface
{
    /**
     * @param $content
     * @return bool|MailContent
    */
    public function parse($content);
}
