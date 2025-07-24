<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use DateTime;
use DateTimeInterface;
use Exception;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

class TatraBankaSimpleMailParser implements ParserInterface
{
    private array $stringFields = ['VS', 'AC', 'SIGN', 'TRES', 'CID', 'CURR', 'CC', 'TID', 'TXN', 'RC', 'HMAC'];
    private array $floatFields = ['AMT'];
    private array $intFields = ['RES', 'TIMESTAMP'];

    private array $methodMap = [
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

    public function parse(string $content): ?MailContent
    {
        $mailContent = new MailContent();

        if (empty($content)) {
            return null;
        }

        foreach (explode(' ', $content) as $part) {
            $keyValue = array_map('trim', explode('=', $part, 2));
            if (count($keyValue) !== 2) {
                continue;
            }
            
            [$key, $value] = $keyValue;

            if (!isset($this->methodMap[$key])) {
                continue;
            }

            $method = $this->methodMap[$key];
            $this->setValueByType($mailContent, $method, $key, $value);
        }

        if ($mailContent->getRes() === null) {
            return null;
        }

        if ($mailContent->getTransactionDate() === null) {
            $mailContent->setTransactionDate(new DateTime());
        }
        
        return $mailContent;
    }

    private function setValueByType(MailContent $mailContent, string $method, string $key, string $value): void
    {
        if (in_array($key, $this->stringFields, true)) {
            $mailContent->$method($value);
        } elseif (in_array($key, $this->floatFields, true)) {
            $mailContent->$method((float) $value);
        } elseif (in_array($key, $this->intFields, true)) {
            if ($key === 'TIMESTAMP') {
                $timestamp = (int) $value;
                try {
                    $dateTime = DateTime::createFromFormat('U', (string) $timestamp);
                    if ($dateTime !== false) {
                        $mailContent->$method($dateTime);
                    }
                } catch (Exception) {
                    // Ignore invalid timestamp
                }
            } else {
                $mailContent->$method((int) $value);
            }
        } else {
            $mailContent->$method($value);
        }
    }
}
