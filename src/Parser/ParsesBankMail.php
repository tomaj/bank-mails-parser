<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser;

use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\ParseException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

trait ParsesBankMail
{
    abstract public function parse(string $content): MailContent;

    /**
     * Invalid input returns null. ConfigurationException is a setup error and is rethrown.
     * TypeError and Error are programmer errors and are not caught.
     */
    public function tryParse(string $content): ?MailContent
    {
        try {
            return $this->parse($content);
        } catch (ConfigurationException $exception) {
            throw $exception;
        } catch (ParseException) {
            return null;
        } catch (\InvalidArgumentException|\ValueError|\DateMalformedStringException) {
            return null;
        }
    }

    /**
     * @return list<MailContent>
     */
    public function parseAll(string $content): array
    {
        return [$this->parse($content)];
    }

    /**
     * Drop a leading BOM. CRLF becomes LF. Lone CR, VT, FF, U+0085, U+2028 and U+2029
     * become LF only when the mail has no LF at all. A mail that already has LF keeps
     * those characters as ordinary text: they do not split a line and they do not open
     * a new transaction. supports() uses this and does not throw.
     *
     * The `/m` flag is required: a BOM after a header is still a BOM.
     *
     * @internal
     */
    protected function withoutByteOrderMark(string $content): string
    {
        $stripped = preg_replace('/^\x{FEFF}/mu', '', $content);
        $content = is_string($stripped) ? $stripped : $content;
        $content = str_replace("\r\n", "\n", $content);
        if (str_contains($content, "\n")) {
            return $content;
        }

        return str_replace(["\r", "\v", "\f", "\u{0085}", "\u{2028}", "\u{2029}"], "\n", $content);
    }

    /**
     * Normalize line breaks. Exotic separators stay in a mail that already has LF.
     * They are ordinary characters there and cannot open a transaction.
     *
     * @internal
     */
    protected function requireUniformLineBreaks(string $content): string
    {
        return $this->withoutByteOrderMark($content);
    }

    /**
     * A single-line HTML ČSOB body uses `<br>`, `</p>` and `</div>` as line breaks.
     * Turn those into newlines, then drop the remaining tags, so a header can still
     * start a line. `<br>` does not stay inside an account number.
     *
     * @internal
     */
    protected function normalizeHtmlLineBreaks(string $content): string
    {
        $broken = preg_replace('/<br\s*\/?>|<\/(?:p|div|tr)\s*>|<(?:p|div|tr)\b[^>]*>/iu', "\n", $content);
        $content = is_string($broken) ? $broken : $content;
        $stripped = preg_replace('/<[^>\n]*>/', '', $content);

        return is_string($stripped) ? $stripped : $content;
    }

    /**
     * Tatra and CardPay patterns use `/u`. Invalid bytes are replaced so a
     * Windows-1250 body still parses, the way 4.x did without `/u`.
     *
     * @internal
     */
    protected function scrubUtf8(string $content): string
    {
        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        return mb_scrub($content, 'UTF-8');
    }

    /**
     * supports() never throws. A PCRE limit on a huge mail is "not this bank".
     *
     * @param \Closure(): bool $probe
     *
     * @internal
     */
    protected function supportsQuietly(\Closure $probe): bool
    {
        try {
            return $probe();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Turn a decimal string into Money. Invalid amounts become InvalidEmailFormatException.
     */
    protected function money(string $amount, Currency|string $currency, ?string $decimalSeparator = null): Money
    {
        try {
            return Money::fromDecimalString($amount, $currency, $decimalSeparator);
        } catch (\InvalidArgumentException $exception) {
            throw InvalidEmailFormatException::malformedContent(static::class, $exception->getMessage(), $exception);
        }
    }

    /**
     * Same as money(), except a missing, unreadable, or unknown-currency amount is null.
     * The rest of the transaction is still returned, as in 4.x.
     */
    protected function moneyOrNull(string $amount, Currency|string $currency, ?string $decimalSeparator = null): ?Money
    {
        if (is_string($currency) && Currency::tryFromCode($currency) === null) {
            return null;
        }

        try {
            return $this->money($amount, $currency, $decimalSeparator);
        } catch (InvalidEmailFormatException) {
            return null;
        }
    }

    /**
     * @param array<int|string, string>|null $matches
     *
     * @internal
     */
    protected function matched(string $pattern, string $subject, ?array &$matches = null): bool
    {
        $captured = [];
        $result = preg_match($pattern, $subject, $captured);
        if ($result === false) {
            throw InvalidEmailFormatException::malformedContent(static::class, 'Regular expression failed: ' . preg_last_error_msg());
        }

        if ($result === 1) {
            /** @var array<int|string, string> $captured */
            $matches = $captured;

            return true;
        }

        return false;
    }

    /**
     * @param array<int|string, string>|null $matches
     *
     * @internal
     */
    protected function group(?array $matches, int $index): string
    {
        $value = $matches[$index] ?? null;
        if (!is_string($value)) {
            throw InvalidEmailFormatException::malformedContent(static::class, 'Missing regular expression capture');
        }

        return $value;
    }

    /**
     * @param array<int|string, string>|null $matches
     *
     * @internal
     */
    private function optionalGroup(?array $matches, int $index): ?string
    {
        $value = $matches[$index] ?? null;
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    /** @internal */
    private function normalizeAccount(string $value): string
    {
        $spaced = str_replace(["\u{00A0}", "\u{2009}", "\u{202F}", "\t"], ' ', $value);
        $collapsed = preg_replace('/ {2,}/', ' ', trim($spaced));

        return is_string($collapsed) ? $collapsed : trim($spaced);
    }

    /**
     * @param list<MailContent> $parsed
     * @param list<string> $errors
     *
     * @return list<MailContent>
     *
     * @internal
     */
    private function transactionsOrFail(array $parsed, array $errors, ?\Throwable $previous = null): array
    {
        if ($errors !== []) {
            throw InvalidEmailFormatException::malformedContent(
                static::class,
                'Could not parse every transaction: ' . implode('; ', $errors),
                $previous,
            );
        }

        return $parsed;
    }

    /** @internal */
    protected function senderDomain(string $sender, string $domain): bool
    {
        return SenderHost::matches($sender, $domain);
    }

    /**
     * @return list<string>
     */
    public function bankDomains(): array
    {
        return [];
    }

    /**
     * Payer-controlled text. Amount, currency, account and date are never read from it.
     *
     * @internal
     */
    protected function withoutFreeText(string $content): string
    {
        return $this->rewriteFreeText($content, false);
    }

    /**
     * When a ČSOB mail has more than one transaction and at least one balance line,
     * each transaction must carry a balance and balance[n-1] + amount[n] = balance[n].
     *
     * @param list<?Money> $balances
     * @param list<MailContent> $parsed
     *
     * @internal
     */
    private function assertBalanceChain(array $balances, array $parsed): void
    {
        $count = count($parsed);
        for ($index = 0; $index < $count; ++$index) {
            if (!$balances[$index] instanceof Money) {
                throw InvalidEmailFormatException::malformedContent(static::class, 'Transaction is missing a balance line');
            }
        }

        for ($index = 1; $index < $count; ++$index) {
            $previous = $balances[$index - 1];
            $current = $balances[$index];
            $amount = $parsed[$index]->money;
            if (!$previous instanceof Money || !$current instanceof Money || !$amount instanceof Money) {
                throw InvalidEmailFormatException::malformedContent(static::class, 'Balance does not match the previous transaction');
            }
            if (!$previous->currency->equals($current->currency) || !$previous->currency->equals($amount->currency)) {
                throw InvalidEmailFormatException::malformedContent(static::class, 'Balance does not match the previous transaction');
            }
            if ($previous->amount + $amount->amount !== $current->amount) {
                throw InvalidEmailFormatException::malformedContent(static::class, 'Balance does not match the previous transaction');
            }
        }
    }

    /**
     * The bank prints one balance at the end of the transaction. A second exact
     * balance line in the same chunk is rejected.
     *
     * @internal
     */
    private function balanceAmount(string $chunk, string $pattern): ?Money
    {
        $found = preg_match_all($pattern, $chunk, $matches, PREG_SET_ORDER);
        if ($found === false) {
            throw InvalidEmailFormatException::malformedContent(static::class, 'Regular expression failed: ' . preg_last_error_msg());
        }
        if ($found > 1) {
            throw InvalidEmailFormatException::malformedContent(static::class, 'Transaction has more than one balance line');
        }
        if ($found !== 1) {
            return null;
        }

        $row = $matches[0];
        $signed = (($row[1] ?? '') === '-' ? '-' : '') . ($row[2] ?? '');

        return $this->money($signed, $row[3] ?? '', ',');
    }

    /**
     * Same byte length as $content. Free-text lines become spaces so a later match offset stays valid.
     *
     * @internal
     */
    private function maskFreeText(string $content): string
    {
        return $this->rewriteFreeText($content, true);
    }

    /**
     * @return list<string>
     *
     * @internal
     */
    private function splitOutsideFreeText(string $content, string $pattern): array
    {
        $masked = $this->maskFreeText($content);
        $found = preg_match_all($pattern, $masked, $matches, PREG_OFFSET_CAPTURE);
        if ($found === false || $found === 0) {
            return [$content];
        }

        $chunks = [];
        $total = count($matches[0]);
        for ($index = 0; $index < $total; ++$index) {
            $start = $matches[0][$index][1] ?? null;
            $next = $matches[0][$index + 1][1] ?? null;
            if (!is_int($start)) {
                continue;
            }
            $end = is_int($next) ? $next : strlen($content);
            $chunks[] = substr($content, $start, $end - $start);
        }

        return $chunks === [] ? [$content] : $chunks;
    }

    /**
     * A label at the start of a line, with the shared whitespace class on both sides.
     * One builder, so a duplicated `*` class is not copied into every parser.
     *
     * @internal
     */
    private function field(string $label, string $value): string
    {
        $space = MailWhitespace::WS;

        return '/^' . $space . $label . $space . $value . '/mu';
    }

    /** @internal */
    private function lineValue(string $content, string $labelPattern): ?string
    {
        if (!$this->matched($this->field($labelPattern . ':', '(.*)$'), $content, $matches)) {
            return null;
        }

        $value = mb_trim($this->group($matches, 1), " \t\n\r\0\x0B\u{00A0}\u{2009}\u{202F}");

        return $value === '' ? null : $value;
    }

    /** @internal */
    private function paymentSymbol(string $digits, ?int $maxDigits = 10): ?string
    {
        if ($digits === '' || preg_match('/^\d+$/', $digits) !== 1) {
            return null;
        }
        if ($maxDigits !== null && strlen($digits) > $maxDigits) {
            return null;
        }

        return $digits;
    }

    /**
     * Payer-written fields.
     *
     * ČSOB CZ: Název protistrany, Adresa protistrany, Adresa plátce, Zpráva příjemci,
     * Účel platby, Identifikace, Reference plátce.
     * ČSOB SK: názov protiúčtu, meno protiúčtu, informácia pre príjemcu, referencia platiteľa.
     * Tatra: Informacia pre prijemcu, Popis transakcie, popis, Referencia platitela.
     * VÚB fixtures have no payer prose field.
     *
     * A blank line, or a line of spaces, tabs or NBSP, ends the field.
     * An arbitrary "Word:" does not. A closing line has to be the bank's own shape:
     * the full balance sentence, a digits-only symbol line, an account line, or a Tatra balance line.
     * Lines inside the field are blanked when bank data is read. They do not reject the mail.
     */
    private function rewriteFreeText(string $content, bool $mask): string
    {
        $parts = preg_split('/(\n)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $content;
        }

        $active = null;
        $out = '';
        foreach ($parts as $part) {
            if ($part === "\n") {
                if ($mask || $active === null) {
                    $out .= $part;
                }

                continue;
            }

            if ($active !== null && $this->isBlankLine($part)) {
                $active = null;
                $out .= $part;

                continue;
            }

            // A continuation such as "a" cannot be a label, a closer, or a forged bank line.
            // Skipping the regexes keeps a long purpose field from scanning every pattern per line.
            if ($active !== null && !$this->couldBeBankLine($part)) {
                $out .= $mask ? str_repeat(' ', strlen($part)) : '';

                continue;
            }

            $next = $this->payerKind($part);
            if ($active !== null && $next !== null) {
                $active = $next;
                $out .= $mask ? str_repeat(' ', strlen($part)) : '';

                continue;
            }

            if ($active !== null && $this->isBankCloser($part, $active)) {
                $active = null;
                $out .= $part;

                continue;
            }

            if ($next !== null) {
                $active = $next;
                $out .= $mask ? str_repeat(' ', strlen($part)) : '';

                continue;
            }

            if ($active !== null) {
                $out .= $mask ? str_repeat(' ', strlen($part)) : '';

                continue;
            }

            $out .= $part;
        }

        return $out;
    }

    /** @internal */
    private function isFreeTextLabel(string $line): bool
    {
        return $this->payerKind($line) !== null;
    }

    private function payerKind(string $line): ?string
    {
        $label = $this->leadingLabel($line);
        if ($label === null) {
            return null;
        }

        return match (true) {
            preg_match('/^(?:název protistrany|adresa plátce|adresa platcu|názov protiúčtu|meno protiúčtu)$/ui', $label) === 1 => 'name',
            preg_match('/^adresa protistrany$/ui', $label) === 1 => 'address',
            preg_match('/^zpráva příjemci$/ui', $label) === 1 => 'message',
            preg_match('/^účel platby$/ui', $label) === 1 => 'purpose',
            preg_match('/^identifikace$/ui', $label) === 1 => 'identification',
            preg_match('/^informácia pre príjemcu$/ui', $label) === 1 => 'sk-message',
            preg_match('/^informacia pre prijemcu$/ui', $label) === 1 => 'tatra-message',
            preg_match('/^(?:popis transakcie|popis)$/ui', $label) === 1 => 'tatra-description',
            preg_match('/^(?:reference plátce|referencia platiteľa|referencia platitela)$/ui', $label) === 1 => 'reference',
            default => null,
        };
    }

    /**
     * Possessive leading whitespace: a multi-megabyte line of spaces must not backtrack.
     */
    private function leadingLabel(string $line): ?string
    {
        if (preg_match('/^[ \t\x{00A0}\x{2009}\x{202F}\x{FEFF}]*+([^\n:]{1,80})[ \t\x{00A0}\x{2009}\x{202F}]*:/u', $line, $match) !== 1) {
            return null;
        }

        return $match[1];
    }

    private function isBlankLine(string $line): bool
    {
        if ($line === '') {
            return true;
        }

        return preg_match('/^[ \t\x{00A0}\x{2009}\x{202F}\x{FEFF}]*$/u', $line) === 1;
    }

    /**
     * A line with no colon and no date or "Dne"/"dňa" cannot be a bank closer or a forged header.
     */
    private function couldBeBankLine(string $line): bool
    {
        if (str_contains($line, ':') || str_contains($line, 'Dne') || str_contains($line, 'dňa')) {
            return true;
        }

        return preg_match('/\d/u', $line) === 1;
    }

    /**
     * A closing line is the bank's own line, not a word the payer can type.
     * Bare `Zůstatek`, `zostatok`, or `banka:` do not close the field.
     * Tatra `uctovny zostatok` / `aktualny zostatok` / `disponibilny zostatok` do.
     */
    private function isBankCloser(string $line, string $kind): bool
    {
        if ($this->isExactBalanceLine($line) || $this->isDigitSymbolLine($line) || $this->isExactAccountLine($line)) {
            return true;
        }

        if ($kind === 'tatra-message' || $kind === 'tatra-description') {
            return $this->isTatraBalanceLine($line);
        }

        return false;
    }

    private function isExactBalanceLine(string $line): bool
    {
        $ws = MailWhitespace::WS;
        $amount = '[+-]' . $ws . '(?:\d{1,3}(?:[ \t\x{00A0}\x{2009}\x{202F}]\d{3})*|\d+),\d{2}';
        $cz = 'zůstatek na účtu po zaúčtování transakce:' . $ws . $amount . $ws . '[A-Z]{3}\.';
        $sk = 'zostatok na účte po zaúčtovaní sumy platobnej operácie:' . $ws . $amount . $ws . '[A-Z]{3}\.?';

        return preg_match('/^' . $ws . '(?:' . $cz . '|' . $sk . ')' . $ws . '$/ui', $line) === 1;
    }

    private function isDigitSymbolLine(string $line): bool
    {
        $ws = MailWhitespace::WS;

        return preg_match('/^' . $ws . '(?:variabilní symbol|konstantní symbol|specifický symbol)' . $ws . ':' . $ws . '\d+' . $ws . '$/ui', $line) === 1;
    }

    private function isExactAccountLine(string $line): bool
    {
        $ws = MailWhitespace::WS;
        $domestic = '\d{1,10}(?:-\d{1,10})?\/\d{4}';
        $iban = '[A-Z]{2}\d{2}(?:[ \t\x{00A0}\x{2009}\x{202F}]?[A-Z0-9]{1,4})+';
        $account = '(?:' . $iban . '|' . $domestic . ')';
        $labels = 'účet protistrany(?:\/iban)?|protiúčet|z účtu';

        return preg_match('/^' . $ws . '(?:' . $labels . ')' . $ws . ':' . $ws . $account . $ws . '$/ui', $line) === 1;
    }

    /**
     * Tatra prints the balance without a sign: `aktualny zostatok: 142,11 EUR`.
     * Deleting this rule leaves a later transaction sentence inside the payer message.
     */
    private function isTatraBalanceLine(string $line): bool
    {
        $ws = MailWhitespace::WS;
        $amount = '[+-]?' . $ws . '(?:\d{1,3}(?:[ \t\x{00A0}\x{2009}\x{202F}]\d{3})*|\d+),\d{2}';

        return preg_match('/^' . $ws . '(?:uctovny zostatok|aktualny zostatok|disponibilny zostatok)' . $ws . ':' . $ws . $amount . $ws . '[A-Za-z]{3}' . $ws . '$/u', $line) === 1;
    }
}
