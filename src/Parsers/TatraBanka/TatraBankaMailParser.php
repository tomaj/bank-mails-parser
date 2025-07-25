<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use DateTime;
use DateTimeInterface;
use Exception;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

class TatraBankaMailParser implements ParserInterface
{
    public function parse(string $content): ?MailContent
    {
        $mailContent = new MailContent();

        $pattern = '/(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{1,2}) bol zostatok Vasho uctu (.*) (zvyseny|znizeny) o (.*) (.*)./';
        $result = [];
        $res = preg_match($pattern, $content, $result);
        if (!$res) {
            return null;
        }

        $transactionDate = $this->parseTransactionDate($result[1]);
        if ($transactionDate !== null) {
            $mailContent->setTransactionDate($transactionDate);
        }

        $mailContent->setAccountNumber($result[2]);

        $amount = $this->parseAmount($result[4]);
        $currency = $result[5];
        if ($result[3] === 'znizeny') {
            $amount = -$amount;
        }

        $mailContent->setAmount($amount);
        $mailContent->setCurrency($currency);

        $pattern2 = '/Referencia platitela: (.*)$/m';
        $res = preg_match($pattern2, $content, $result);
        if ($res) {
            $reference = trim($result[1]);
            $pattern3 = '/\/VS([0-9]+)\/SS([0-9]*)\/KS([0-9]*)/';
            $res = preg_match($pattern3, $reference, $result);
            if ($res) {
                $mailContent->setVs($result[1]);
                if (!empty($result[2])) {
                    $mailContent->setSs($result[2]);
                }
                if (!empty($result[3])) {
                    $mailContent->setKs($result[3]);
                }
            }
        }

        $pattern5 = '/Informacia pre prijemcu: (.*)/m';
        $res = preg_match($pattern5, $content, $result);
        if ($res) {
            $mailContent->setReceiverMessage(trim($result[1]));
        }

        // if no VS found in main reference line and receiver message doesn't contain VS as prefix
        // - some email templates have separate field for "Informacia pre prijemcu"
        // - other email templates move VS to this field when reference field is empty
        if ($mailContent->getVs() === null && $mailContent->getReceiverMessage() !== null) {
            $receiverMessage = $mailContent->getReceiverMessage();
            $pattern = '/VS:([0-9]{1,10})/i';
            $res = preg_match($pattern, $receiverMessage, $result);
            if ($res) {
                $mailContent->setVs($result[1]);
            }
        }

        // if still no number found, check receiver message
        // - some payers incorrectly set this field with VS number but without "VS" prefix
        // - some banks send here variable symbol in Creditor Reference Information - SEPA XML format
        // loads VS provided in formats:
        // - Informacia pre prijemcu: 1234056789
        // - Informacia pre prijemcu: (CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789
        //   (/Ref)(/CdtrRefInf)
        if ($mailContent->getVs() === null) {
            $pattern = '/Informacia pre prijemcu:.*\b([0-9]{1,10})\b.*/i';
            $res = preg_match($pattern, $content, $result);
            if ($res) {
                $mailContent->setVs($result[1]);
            }
        }

        // if still no number found, check unique mandate reference
        // some payers incorrectly set this field without correct prefixes /VS/SS/KS
        // loads VS provided in format:
        // - Referencia platitela: 1234056789
        if ($mailContent->getVs() === null) {
            $pattern = '/Referencia platitela:.*\b([0-9]{1,10})\b.*/i';
            $res = preg_match($pattern, $content, $result);
            if ($res) {
                $mailContent->setVs($result[1]);
            }
        }

        $pattern4 = '/Popis transakcie: (.*)/m';
        $res = preg_match($pattern4, $content, $result);
        if ($res) {
            $mailContent->setDescription($result[1]);

            $descriptionParts = explode(' ', $result[1], 2);
            if (count($descriptionParts) > 1) {
                $mailContent->setSourceAccountNumber($descriptionParts[1]);
            } else {
                $mailContent->setSourceAccountNumber($descriptionParts[0]);
            }
        }

        return $mailContent;
    }

    private function parseTransactionDate(string $dateString): ?DateTimeInterface
    {
        $timestamp = strtotime($dateString);
        if ($timestamp === false) {
            return null;
        }

        try {
            $dateTime = DateTime::createFromFormat('U', (string) $timestamp);
            return $dateTime !== false ? $dateTime : null;
        } catch (Exception) {
            return null;
        }
    }

    private function parseAmount(string $amountString): float
    {
        return (float) str_replace(',', '.', str_replace(' ', '', $amountString));
    }
}
