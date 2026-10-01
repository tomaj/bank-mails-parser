<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\Vub;

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

final readonly class VubMailParser implements ParserInterface
{
    #[\Override]
    public function parse(string $content): MailContent
    {
        $transactionDate = null;
        if (preg_match('/Dtum:.*?(.*)/m', $content, $result) === 1) {
            $transactionDate = strtotime(mb_trim($result[1]));
        }

        $accountNumber = null;
        if (preg_match('/Z tu:.*?([A-Z0-9]+)/m', $content, $result) === 1) {
            $accountNumber = mb_trim($result[1]);
        }

        $amount = null;
        if (preg_match('/Suma:.*?([0-9,]+)/m', $content, $result) === 1) {
            $normalized = preg_replace('/\s+/u', '', $result[1]);
            $amount = floatval(str_replace(',', '.', $normalized ?? ''));
        }

        $vs = null;
        if (preg_match('/VS:.*?([0-9,]+)/m', $content, $result) === 1) {
            $vs = $result[1];
        }

        $ks = null;
        if (preg_match('/KS:.*?([0-9,]+)/m', $content, $result) === 1) {
            $ks = $result[1];
        }

        return new MailContent(
            amount: $amount,
            transactionDate: $transactionDate,
            accountNumber: $accountNumber,
            vs: $vs,
            ks: $ks,
        );
    }
}
