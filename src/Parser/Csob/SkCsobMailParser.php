<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\Csob;

use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\BankDate;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

final readonly class SkCsobMailParser implements ParserInterface
{
    use ParsesBankMail;

    private const string TRANSACTION = '/(?:^[ \t\x{00A0}\x{2009}\x{202F}]*|že[ \t\x{00A0}\x{2009}\x{202F}]+)dňa[ \t\x{00A0}\x{2009}\x{202F}]+(\d{1,2}\.\d{1,2}\.\d{4})[ \t\x{00A0}\x{2009}\x{202F}]+bola na účte[ \t\x{00A0}\x{2009}\x{202F}]+(SK[0-9][0-9 \x{00A0}\x{2009}\x{202F}\t]*).*?zaúčtovaná suma SEPA platobného príkazu/mu';

    private const string HEADER = '/(?:^[ \t\x{00A0}\x{2009}\x{202F}]*|že[ \t\x{00A0}\x{2009}\x{202F}]+)dňa[ \t\x{00A0}\x{2009}\x{202F}]+\d{1,2}\.\d{1,2}\.\d{4}[ \t\x{00A0}\x{2009}\x{202F}]+bola na účte/mu';

    private const string BALANCE = '/Zostatok na účte po zaúčtovaní sumy platobnej operácie:[ \t\x{00A0}\x{2009}\x{202F}]*([+-])[ \t\x{00A0}\x{2009}\x{202F}]*((?:\d{1,3}(?:[ \t\x{00A0}\x{2009}\x{202F}]\d{3})*|\d+),\d{2})[ \t\x{00A0}\x{2009}\x{202F}]+([A-Z]{3})/u';

    /**
     * @return list<string>
     */
    #[\Override]
    public function bankDomains(): array
    {
        return ['csob.sk'];
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
        $receiverMessage = $this->lineValue($original, 'informácia pre príjemcu');
        $content = $this->withoutFreeText($original);
        if (!$this->matched(self::TRANSACTION, $content, $result)) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Could not find transaction pattern');
        }

        $transactionDate = BankDate::parse($this->group($result, 1), '!j.n.Y', BankDate::bratislava());
        $ownAccountNumber = $this->normalizeAccount($this->group($result, 2));

        $money = null;
        if ($this->matched($this->field('suma:', '([+-])[ \t\x{00A0}\x{2009}\x{202F}]*(.*?)[ \t\x{00A0}\x{2009}\x{202F}]+([A-Z]{3})\b'), $content, $amount)) {
            $signed = ($this->group($amount, 1) === '-' ? '-' : '') . $this->group($amount, 2);
            $money = $this->moneyOrNull($signed, $this->group($amount, 3), ',');
        }

        $vs = null;
        $ss = null;
        $ks = null;
        if ($this->matched($this->field('referencia platiteľa:', '\/?VS(\d*)(?:\/SS(\d*))?(?:\/KS(\d*))?'), $original, $symbol)) {
            $vs = $this->paymentSymbol($this->group($symbol, 1), null);
            $ssRaw = $this->optionalGroup($symbol, 2);
            $ksRaw = $this->optionalGroup($symbol, 3);
            $ss = $ssRaw === null ? null : $this->paymentSymbol($ssRaw, null);
            $ks = $ksRaw === null ? null : $this->paymentSymbol($ksRaw, null);
        }
        // 4.1.0 reads the first VS token in the mail. That also matched the payer name (ADVS2).
        // The owner kept the 4.x value when it comes from this line only. Length is not capped.
        if ($vs === null && $this->matched($this->field('informácia pre príjemcu:', '.*?(?<![A-Za-z])VS([0-9]+)(?!\d)'), $original, $fromMessage)) {
            $vs = $this->group($fromMessage, 1);
        }
        if ($ks === null && $this->matched($this->field('informácia pre príjemcu:', '.*?(?<![A-Za-z])KS([0-9]+)(?!\d)'), $original, $fromMessage)) {
            $ks = $this->group($fromMessage, 1);
        }

        $accountNumber = null;
        if ($this->matched($this->field('z účtu:', '([A-Z0-9 \x{00A0}\x{2009}\x{202F}\t]+)'), $content, $source)) {
            $accountNumber = $this->normalizeAccount($this->group($source, 1));
        }

        return new MailContent(
            money: $money,
            transactionDate: $transactionDate,
            accountNumber: $accountNumber,
            ownAccountNumber: $ownAccountNumber,
            vs: $vs,
            ss: $ss,
            ks: $ks,
            receiverMessage: $receiverMessage,
        );
    }
}
