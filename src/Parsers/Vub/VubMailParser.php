<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\Vub;

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

class VubMailParser implements ParserInterface
{
    public function parse(string $content): ?MailContent
    {
        $mailContent = new MailContent();

        $pattern1 = '/Dtum:.*?(.*)/m';
        $res = preg_match($pattern1, $content, $result);
        if ($res) {
            $mailContent->setTransactionDate(strtotime(trim($result[1])));
        }

        $pattern2 = '/Z tu:.*?([A-Z0-9]+)/m';
        $res = preg_match($pattern2, $content, $result);
        if ($res) {
            $iban = trim($result[1]);
            $mailContent->setAccountNumber($iban);
        }

        $pattern3 = '/Suma:.*?([0-9,]+)/m';
        $res = preg_match($pattern3, $content, $result);
        if ($res) {
            // there's unicode non-breaking space (u00A0) in mime encoded version of email, unicode regex switched is necessary
            $amount = floatval(str_replace(',', '.', preg_replace('/\s+/u', '', $result[1])));
            $mailContent->setAmount($amount);
        }

        $pattern4 = '/VS:.*?([0-9,]+)/m';
        $res = preg_match($pattern4, $content, $result);
        if ($res) {
            $mailContent->setVs($result[1]);
        }

        $pattern5 = '/KS:.*?([0-9,]+)/m';
        $res = preg_match($pattern5, $content, $result);
        if ($res) {
            $mailContent->setKs($result[1]);
        }

        return $mailContent;
    }
}