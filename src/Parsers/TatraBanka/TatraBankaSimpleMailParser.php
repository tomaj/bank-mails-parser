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
    private array $intFields = ['RES'];

    private array $methodMap = [
        'VS' => 'setVs',
        'RES' => 'setRes',
        'AC' => 'setAc',
        'SIGN' => 'setSign',
        'TRES' => 'setTres',
        'CID' => 'setCid',
        'AMT' => 'setAmount',
        'CURR' => 'setCurrency',
        'CC' => 'setCc',
        'TID' => 'setTid',
        'TXN' => 'setTxn',
        'RC' => 'setRc',
        'HMAC' => 'setSign',
    ];

    public function parse(string $content): ?MailContent
    {
        $mailContent = new MailContent();

        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (strpos($line, '=') === false) {
                continue;
            }

            $parts = explode(' ', $line);
            if (empty($parts)) {
                continue;
            }

            foreach ($parts as $part) {
                $keyValue = array_map('trim', explode('=', $part, 2));
                if (count($keyValue) !== 2) {
                    continue;
                }

                [$key, $value] = $keyValue;

                if ($key === 'TIMESTAMP') {
                    $this->setTimestamp($mailContent, $value);
                    continue;
                }

                if (!isset($this->methodMap[$key])) {
                    continue;
                }

                $method = $this->methodMap[$key];
                $this->setValueByType($mailContent, $method, $key, $value);
            }
        }

        if ($mailContent->getRes() === null) {
            return null;
        }

        if ($mailContent->getTransactionDate() === null) {
            $mailContent->setTransactionDate(new DateTime());
        }

        return $mailContent;
    }

    private function setTimestamp(MailContent $mailContent, string $timestamp): void
    {
        try {
            // Check if it's ddMMyyyyHHmmss format (14 digits)
            if (strlen($timestamp) === 14 && ctype_digit($timestamp)) {
                $dateTime = DateTime::createFromFormat('dmYHis', $timestamp);
                if ($dateTime !== false) {
                    $mailContent->setTransactionDate($dateTime);
                }
            } else {
                // Try as unix timestamp
                $unixTimestamp = (int) $timestamp;
                $dateTime = DateTime::createFromFormat('U', (string) $unixTimestamp);
                if ($dateTime !== false) {
                    $mailContent->setTransactionDate($dateTime);
                }
            }
        } catch (Exception) {
            // Ignore invalid timestamp
        }
    }

    private function setValueByType(MailContent $mailContent, string $method, string $key, string $value): void
    {
        if (in_array($key, $this->stringFields, true)) {
            $mailContent->$method($value);
        } elseif (in_array($key, $this->floatFields, true)) {
            $mailContent->$method((float) $value);
        } elseif (in_array($key, $this->intFields, true)) {
            $mailContent->$method((int) $value);
        } else {
            // Fallback for other types, though ideally all fields should be explicitly handled
            $mailContent->$method($value);
        }
    }
}
