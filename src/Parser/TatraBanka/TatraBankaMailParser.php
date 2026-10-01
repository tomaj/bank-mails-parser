<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use DateTimeImmutable;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

final readonly class TatraBankaMailParser implements ParserInterface
{
    #[\Override]
    public function parse(string $content): ?MailContent
    {
        $pattern1 = '/(.*) bol zostatok Vasho uctu ([a-zA-Z0-9]+) (zvyseny|znizeny) o ([0-9 ]+,[0-9]+) ([a-zA-Z]+)/m';
        if (preg_match($pattern1, $content, $matches) !== 1) {
            return null;
        }

        $transactionDateFormats = [
            'j. n. Y G:i',
            'j.n.Y G:i',
        ];
        $transactionDate = false;
        foreach ($transactionDateFormats as $format) {
            $parsedDate = DateTimeImmutable::createFromFormat($format, $matches[1]);
            if ($parsedDate !== false) {
                $transactionDate = $parsedDate->getTimestamp();
                break;
            }
        }
        if ($transactionDate === false) {
            $transactionDate = strtotime($matches[1]);
        }

        $accountNumber = $matches[2];
        $amount = floatval(str_replace(',', '.', str_replace(' ', '', $matches[4])));
        if ($matches[3] === 'znizeny') {
            $amount = -$amount;
        }
        $currency = $matches[5];

        $vs = null;
        $ss = null;
        $ks = null;
        $hasStructuredRef = false;
        if (preg_match('/Referencia platitela: \/VS(.*)\/SS(.*)\/KS(.*)/m', $content, $refMatches) === 1) {
            $hasStructuredRef = true;
            $vs = $refMatches[1] !== '' ? $refMatches[1] : null;
            $ss = $refMatches[2] !== '' ? $refMatches[2] : null;
            $ks = $refMatches[3] !== '' ? $refMatches[3] : null;
        }

        if ($vs === null && preg_match('/vs([0-9]{1,10})/i', $content, $vsMatches) === 1) {
            $vs = $vsMatches[1];
        }

        $receiverMessage = null;
        if (preg_match('/Informacia pre prijemcu: (.*)/m', $content, $recvMatches) === 1) {
            $receiverMessage = $recvMatches[1];
        }

        if ($vs === null && preg_match('/Informacia pre prijemcu:.*?([0-9]{1,10})/i', $content, $recvVsMatches) === 1) {
            $vs = $recvVsMatches[1];
        }

        if (!$hasStructuredRef && $vs === null && preg_match('/Referencia platitela:.*?([0-9]{1,10})/i', $content, $refVsMatches) === 1) {
            $vs = $refVsMatches[1];
        }

        $description = null;
        $sourceAccountNumber = null;
        if (preg_match('/Popis transakcie: (.*)/m', $content, $descMatches) === 1) {
            $description = $descMatches[1];
            $descriptionParts = explode(' ', $descMatches[1], 2);
            $sourceAccountNumber = count($descriptionParts) === 2 ? $descriptionParts[1] : $descriptionParts[0];
        }

        return new MailContent(
            amount: $amount,
            currency: $currency,
            transactionDate: $transactionDate,
            accountNumber: $accountNumber,
            sourceAccountNumber: $sourceAccountNumber,
            vs: $vs,
            ss: $ss,
            ks: $ks,
            receiverMessage: $receiverMessage,
            description: $description,
        );
    }
}
