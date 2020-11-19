<?php

namespace Tomaj\BankMailsParser\Parser;

use Tomaj\BankMailsParser\MailContent;

class TatraBankaSimpleMailParser implements ParserInterface
{
    private $map = [
        'VS' => 'setVs',
        'RES' => 'setRes',
        'AC' => 'setAc',
        'SIGN' => 'setSign',
        'TRES' => 'setRes',
        'CID' => 'setCid',
        'AMT' => 'setAmount',
        'CURR' => 'setCurrency',
        'CC' => 'setCc',
        'TID' => 'setTid',
        'TIMESTAMP' => 'setTransactionDate',
        'TXN' => 'setTxn',
        'RC' => 'setRc',
        'HMAC' => 'setSign',
    ];

    /**
     * @param $content
     * @return bool|MailContent
     */
    public function parse($content)
    {
        $mailContent = new MailContent();

        if (empty($content)) {
            return false;
        }

        $parsed = [];
        foreach (explode(' ', $content) as $part) {
            list($key, $value) = explode('=', $part);
            $parsed[$key] = $value;
        }

        foreach ($this->map as $key => $fn) {
            if (empty($parsed[$key])) {
                continue;
            }
            $mailContent->$fn($parsed[$key]);
        }

        if ($mailContent->getRes() === null) {
            return false;
        }

        if ($mailContent->getTransactionDate() === null) {
            $mailContent->setTransactionDate(time());
        }
        return $mailContent;
    }
}
