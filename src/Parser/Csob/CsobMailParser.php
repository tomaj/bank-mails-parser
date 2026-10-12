<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\Csob;

use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\BankDate;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

final readonly class CsobMailParser implements ParserInterface
{
    use ParsesBankMail;

    /**
     * A header is the start of a line. It does not need a blank line in front of it.
     * A header inside a payer field is blanked with that field, so it does not open a transaction.
     */
    private const string TRANSACTION = '/^[ \t\x{00A0}\x{2009}\x{202F}]*Dne[ \t\x{00A0}\x{2009}\x{202F}]+(\d{1,2}\.\d{1,2}\.\d{4}(?:[ T\x{00A0}\x{2009}\x{202F}]\d{1,2}:\d{2})?)[ \t\x{00A0}\x{2009}\x{202F}]+byl(?:a)?[ \t\x{00A0}\x{2009}\x{202F}]+na účtu[ \t\x{00A0}\x{2009}\x{202F}]+([A-Za-z0-9]+)[ \t\x{00A0}\x{2009}\x{202F}]+(?:zaúčtovaná|zaúčtována|zaúčtovaný)[ \t\x{00A0}\x{2009}\x{202F}]+(?:zahraniční transakce\.|hotovostní transakce\.|(?:transakce typu:[ \t\x{00A0}\x{2009}\x{202F}]*|transakce[ \t\x{00A0}\x{2009}\x{202F}]+)?(?:Došlá platba|Příchozí úhrada|Došlá úhrada|SEPA převod|platební kartou))/mu';

    private const string HEADER = '/^[ \t\x{00A0}\x{2009}\x{202F}]*Dne[ \t\x{00A0}\x{2009}\x{202F}]+\d{1,2}\.\d{1,2}\.\d{4}(?:[ T\x{00A0}\x{2009}\x{202F}]\d{1,2}:\d{2})?[ \t\x{00A0}\x{2009}\x{202F}]+byl(?:a)?[ \t\x{00A0}\x{2009}\x{202F}]+na účtu/mu';

    private const string BALANCE = '/Zůstatek na účtu po zaúčtování transakce:[ \t\x{00A0}\x{2009}\x{202F}]*([+-])[ \t\x{00A0}\x{2009}\x{202F}]*((?:\d{1,3}(?:[ \t\x{00A0}\x{2009}\x{202F}]\d{3})*|\d+),\d{2})[ \t\x{00A0}\x{2009}\x{202F}]+([A-Z]{3})\./u';

    private const string ACCOUNT = '[A-Za-z0-9][A-Za-z0-9 \t\x{00A0}\x{2009}\x{202F}\\/-]*';

    /**
     * @return list<string>
     */
    #[\Override]
    public function bankDomains(): array
    {
        return ['csob.cz'];
    }

    #[\Override]
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return $this->supportsQuietly(
            fn(): bool => $this->matched(self::HEADER, $this->withoutFreeText($this->normalizeHtmlLineBreaks($this->withoutByteOrderMark($content)))),
        );
    }

    /**
     * @return list<MailContent>
     */
    public function parseMulti(string $content): array
    {
        $content = $this->normalizeHtmlLineBreaks($this->requireUniformLineBreaks($content));
        $chunks = $this->splitOutsideFreeText($content, self::HEADER);
        $parsed = [];
        $balances = [];
        $errors = [];
        $first = null;
        $index = 0;
        foreach ($chunks as $chunk) {
            if (!$this->matched(self::TRANSACTION, $this->withoutFreeText($chunk))) {
                continue;
            }
            ++$index;
            try {
                $parsed[] = $this->parse($chunk);
                $balances[] = $this->balanceAmount($chunk, self::BALANCE);
            } catch (InvalidEmailFormatException $exception) {
                $first ??= $exception;
                $errors[] = sprintf('transaction %d: %s', $index, $exception->getMessage());
            }
        }

        $parsed = $this->transactionsOrFail($parsed, $errors, $first);
        if (count($parsed) > 1 && $this->matched(self::BALANCE, $content)) {
            $this->assertBalanceChain($balances, $parsed);
        }

        return $parsed;
    }

    #[\Override]
    public function parseAll(string $content): array
    {
        $parsed = $this->parseMulti($content);
        if ($parsed === []) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Could not find transaction pattern');
        }

        return $parsed;
    }

    public function parse(string $content): MailContent
    {
        $original = $this->normalizeHtmlLineBreaks($this->requireUniformLineBreaks($content));
        $receiverMessage = $this->lineValue($original, 'Zpráva příjemci');
        $content = $this->withoutFreeText($original);
        if (!$this->matched(self::TRANSACTION, $content, $result)) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Could not find transaction pattern');
        }

        $transactionDate = BankDate::parseFirst($this->group($result, 1), ['!j.n.Y G:i', '!j.n.Y'], BankDate::bratislava());
        $accountNumber = $this->group($result, 2);

        $sourceAccountNumber = null;
        if ($this->matched($this->field('Účet protistrany(?:\/IBAN)*:', '(' . self::ACCOUNT . ')'), $content, $counterparty)) {
            $sourceAccountNumber = mb_trim($this->group($counterparty, 1), " \t\n\r\0\x0B\u{00A0}\u{2009}\u{202F}");
        }

        $money = null;
        if ($this->matched($this->field('Částka:', '([+-])[ \t\x{00A0}\x{2009}\x{202F}]*(.*?)[ \t\x{00A0}\x{2009}\x{202F}]+([A-Z]{3})\b'), $content, $amount)) {
            $signed = ($this->group($amount, 1) === '-' ? '-' : '') . $this->group($amount, 2);
            $money = $this->moneyOrNull($signed, $this->group($amount, 3), ',');
        }

        $vs = $this->multilineSymbol($original, 'variable');
        $identified = $this->multilineSymbol($original, 'identification');
        if ($identified !== null) {
            $vs = $identified;
        }
        // 4.1.0 fallbacks. Payer text can supply vs only. Amount, account, and date stay on the bank lines.
        if ($vs === null && $this->matched('/vs([0-9]{1,10})(?!\d)/i', $original, $symbol)) {
            $vs = $this->group($symbol, 1);
        }
        if ($vs === null && $this->matched('/v\.s\.([0-9]{1,10})(?!\d)/i', $original, $symbol)) {
            $vs = $this->group($symbol, 1);
        }
        if ($vs === null && $this->matched('/Zpráva příjemci:.*\b([0-9]{1,10})\b.*/i', $original, $symbol)) {
            $vs = $this->group($symbol, 1);
        }
        if ($vs === null) {
            $vs = $this->multilineSymbol($original, 'purpose') ?? $this->lastNumberInPurpose($original);
        }

        $ks = null;
        if ($this->matched($this->field('Konstantní symbol:', '([0-9]+)'), $original, $symbol)) {
            $ks = $this->paymentSymbol($this->group($symbol, 1));
        }

        return new MailContent(
            money: $money,
            transactionDate: $transactionDate,
            accountNumber: $accountNumber,
            sourceAccountNumber: $sourceAccountNumber,
            vs: $vs,
            ks: $ks,
            receiverMessage: $receiverMessage,
        );
    }

    /**
     * Last 1–10 digit number between Účel platby and Zůstatek, as in 4.1.0.
     * Without Zůstatek the footer is ignored. A number glued to letters is not a symbol.
     * A blank line does not stop the search: 4.1.0 uses dotall up to Zůstatek.
     */
    private function lastNumberInPurpose(string $original): ?string
    {
        if (!str_contains($original, 'Zůstatek') || preg_match('/Účel platby:(.*?)Zůstatek/su', $original, $match) !== 1) {
            return null;
        }
        $text = $match[1];
        if (preg_match_all('/\b([0-9]{1,10})\b/u', $text, $numbers) < 1) {
            return null;
        }

        $last = $numbers[1][array_key_last($numbers[1])] ?? null;

        return is_string($last) ? $last : null;
    }

    /**
     * `/m` on these patterns has no effect: they do not use `^` or `$`.
     * Infection ignores PregMatchRemoveFlags for this method only.
     */
    private function multilineSymbol(string $original, string $which): ?string
    {
        $pattern = match ($which) {
            'variable' => '/Variabilní symbol: ([0-9]{1,10})(?!\d)/m',
            'identification' => '/Identifikace: ([0-9]{1,10})(?!\d)/m',
            'purpose' => '/Účel platby: ([0-9]{1,10})(?!\d)/m',
            default => null,
        };
        if ($pattern === null || !$this->matched($pattern, $original, $symbol)) {
            return null;
        }
        $value = $this->group($symbol, 1);
        if ($which === 'variable' && (int) $value === 0) {
            return null;
        }

        return $value;
    }
}
