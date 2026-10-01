<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser;

use Tomaj\BankMailsParser\MailContent;

interface ParserInterface
{
    /**
     * @param $content
     * @return ?MailContent
    */
    public function parse(string $content): ?MailContent;
}
