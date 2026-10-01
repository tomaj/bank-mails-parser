<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\Csob;

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

final readonly class SkCsobMailParser implements ParserInterface
{
    /**
     * @return MailContent[]
     */
    public function parseMulti(string $content): array
    {
        $transactions = array_slice(explode('dňa ', $content), 1);

        return array_filter(
            array_map($this->parse(...), $transactions),
            static fn(?MailContent $mc): bool => $mc !== null,
        );
    }

    #[\Override]
    public function parse(string $content): ?MailContent
    {
        if (preg_match('/(.*) bola na účte (.*) zaúčtovaná suma SEPA platobného príkazu/m', $content, $result) !== 1) {
            return null;
        }

        $transactionDate = strtotime($result[1]);

        $amount = null;
        $currency = null;
        if (preg_match('/suma:.*?([+-])(.*?) ([A-Z]+)/m', $content, $result) === 1) {
            $normalized = preg_replace('/\s+/u', '', $result[2]);
            $amount = floatval(str_replace(',', '.', $normalized ?? ''));
            $currency = $result[3];
            if ($result[1] === '-') {
                $amount = -$amount;
            }
        }

        $receiverMessage = null;
        if (preg_match('/informácia pre príjemcu: (.*)/m', $content, $result) === 1) {
            $receiverMessage = mb_trim($result[1]);
        }

        $vs = null;
        if (preg_match('/VS([0-9]+)/m', $content, $result) === 1) {
            $vs = $result[1];
        }

        $ks = null;
        if (preg_match('/KS([0-9]+)/m', $content, $result) === 1) {
            $ks = $result[1];
        }

        $accountNumber = null;
        if (preg_match('/z účtu:.*?([A-Z0-9 ]+)/m', $content, $result) === 1) {
            $accountNumber = mb_trim($result[1]);
        }

        return new MailContent(
            amount: $amount,
            currency: $currency,
            transactionDate: $transactionDate,
            accountNumber: $accountNumber,
            vs: $vs,
            ks: $ks,
            receiverMessage: $receiverMessage,
        );
    }
}
