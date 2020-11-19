<?php
declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use ReflectionParameter;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

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
     * @return ?MailContent
     */
    public function parse(string $content): ?MailContent
    {
        $mailContent = new MailContent();

        if (empty($content)) {
            return null;
        }

        $parsed = [];
        foreach (explode(' ', $content) as $part) {
            list($key, $value) = explode('=', $part);
            $parsed[$key] = $value;

            if (!isset($this->map[$key])) {
                continue;
            }

            $method = $this->map[$key];

            $param = new ReflectionParameter([MailContent::class, $method], 0);
            if ($param->getType()) {
                $type = $param->getType()->getName();
                if ($type == 'string') {
                    $mailContent->$method($value);
                } elseif ($type == 'int') {
                    $mailContent->$method(intval($value));
                } elseif ($type == 'float') {
                    $mailContent->$method(floatval($value));
                } else {
                    $mailContent->$method($value);
                }
            } else {
                $mailContent->$method($value);
            }
        }

        if ($mailContent->getRes() === null) {
            return null;
        }

        if ($mailContent->getTransactionDate() === null) {
            $mailContent->setTransactionDate(time());
        }
        return $mailContent;
    }
}
