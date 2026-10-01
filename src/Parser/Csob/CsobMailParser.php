<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\Csob;

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

final readonly class CsobMailParser implements ParserInterface
{
    /**
     * @return MailContent[]
     */
    public function parseMulti(string $content): array
    {
        $transactions = array_slice(explode('Dne ', $content), 1);

        return array_filter(
            array_map($this->parse(...), $transactions),
            static fn(?MailContent $mc): bool => $mc !== null,
        );
    }

    #[\Override]
    public function parse(string $content): ?MailContent
    {
        $pattern1 = '/(.*) byl(?:a)? na účtu ([a-zA-Z0-9]+) (?:zaúčtovaná|zaúčtována|zaúčtovaný) (?:zahraniční transakce\.|hotovostní transakce\.|(?:transakce typu: |transakce )?(Došlá platba|Příchozí úhrada|Došlá úhrada|SEPA převod|platební kartou))/mu';
        if (preg_match($pattern1, $content, $result) !== 1) {
            return null;
        }

        $transactionDate = strtotime($result[1]);
        $accountNumber = $result[2];

        $sourceAccountNumber = null;
        if (preg_match('/Účet protistrany(?:\/IBAN)*: (.*)/mu', $content, $result) === 1) {
            $sourceAccountNumber = mb_trim($result[1]);
        }

        $amount = null;
        $currency = null;
        if (preg_match('/Částka: ([+-])(.*?) ([A-Z]+)/mu', $content, $result) === 1) {
            $normalized = preg_replace('/\s+/u', '', $result[2]);
            $amount = floatval(str_replace(',', '.', $normalized ?? ''));
            $currency = $result[3];
            if ($result[1] === '-') {
                $amount = -$amount;
            }
        }

        $receiverMessage = null;
        if (preg_match('/Zpráva příjemci: (.*)/mu', $content, $result) === 1) {
            $receiverMessage = mb_trim($result[1]);
        }

        $vs = null;
        if (preg_match('/Variabilní symbol: ([0-9]{1,10})/m', $content, $result) === 1 && (int) $result[1] !== 0) {
            $vs = $result[1];
        }
        if ($vs === null && preg_match('/Identifikace: ([0-9]{1,10})/m', $content, $result) === 1) {
            $vs = $result[1];
        }
        if ($vs === null && preg_match('/vs([0-9]{1,10})/i', $content, $result) === 1) {
            $vs = $result[1];
        }
        if ($vs === null && preg_match('/v\.s\.([0-9]{1,10})/i', $content, $result) === 1) {
            $vs = $result[1];
        }
        if ($vs === null && preg_match('/Zpráva příjemci:.*\b([0-9]{1,10})\b.*/i', $content, $result) === 1) {
            $vs = $result[1];
        }
        if ($vs === null) {
            if (preg_match('/Účel platby: ([0-9]{1,10})/m', $content, $result) === 1) {
                $vs = $result[1];
            } elseif (preg_match('/Účel platby:.*\b([0-9]{1,10})\b.*Zůstatek/s', $content, $result) === 1) {
                $vs = $result[1];
            }
        }

        $ks = null;
        if (preg_match('/Konstantní symbol: ([0-9]{1,10})/mu', $content, $result) === 1) {
            $ks = $result[1];
        }

        return new MailContent(
            amount: $amount,
            currency: $currency,
            transactionDate: $transactionDate,
            accountNumber: $accountNumber,
            sourceAccountNumber: $sourceAccountNumber,
            vs: $vs,
            ks: $ks,
            receiverMessage: $receiverMessage,
        );
    }
}
