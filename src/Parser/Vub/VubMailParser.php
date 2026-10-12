<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\Vub;

use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\BankDate;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

final readonly class VubMailParser implements ParserInterface
{
    use ParsesBankMail;

    /**
     * @return list<string>
     */
    #[\Override]
    public function bankDomains(): array
    {
        return ['vub.sk'];
    }

    #[\Override]
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return $this->supportsQuietly(function () use ($content): bool {
            $content = $this->withoutByteOrderMark($content);
            $hasDate = $this->matched($this->field('D(?:á|a)?tum', ':'), $content);
            $hasSource = $this->matched($this->field('Z[ \t\x{00A0}\x{2009}\x{202F}]*(?:(?:ú|u)?čtu|tu)', ':'), $content);
            $hasDestination = $this->matched($this->field('Na[ \t\x{00A0}\x{2009}\x{202F}]+(?:účet|et)', ':'), $content);
            $hasStatus = $this->matched($this->field('(?:Stav|SIGN)', ':'), $content);
            $hasAmount = $this->matched($this->field('Suma', ':'), $content);
            $hasVs = $this->matched($this->field('VS', ':'), $content);

            return $hasDate && $hasSource && $hasDestination && $hasStatus && $hasAmount && $hasVs;
        });
    }

    public function parse(string $content): MailContent
    {
        $content = $this->requireUniformLineBreaks($content);
        $signals = 0;

        $transactionDate = null;
        if ($this->matched($this->field('D(?:á|a)?tum:', '(.*)$'), $content, $result)) {
            ++$signals;
            $transactionDate = BankDate::parseFirst(mb_trim($this->group($result, 1), " \t\n\r\0\x0B\u{00A0}\u{2009}\u{202F}"), ['!j.n.Y', '!Y-m-d', '!j.n.Y G:i'], BankDate::bratislava());
        }

        $ownAccountNumber = null;
        if ($this->matched($this->field('Na[ \t\x{00A0}\x{2009}\x{202F}]+(?:účet|et):', '([A-Z0-9 \x{00A0}\x{2009}\x{202F}\t]*)'), $content, $result)) {
            $account = $this->normalizeAccount($this->group($result, 1));
            if ($account !== '') {
                ++$signals;
                $ownAccountNumber = $account;
            }
        }

        $accountNumber = null;
        if ($this->matched($this->field('Z[ \t\x{00A0}\x{2009}\x{202F}]*(?:(?:ú|u)?čtu|tu):', '([A-Z0-9 \x{00A0}\x{2009}\x{202F}\t]*)'), $content, $result)) {
            $account = $this->normalizeAccount($this->group($result, 1));
            if ($account !== '') {
                ++$signals;
                $accountNumber = $account;
            }
        }

        $money = null;
        if ($this->matched($this->field('Suma:', '(.*)$'), $content, $result)) {
            ++$signals;
            $raw = trim(str_replace(["\u{00A0}", "\u{2009}", "\u{202F}", "\t"], ' ', $this->group($result, 1)));
            if ($raw !== '' && preg_match('/^(-?\d(?:[\d \x{00A0}\x{2009}\x{202F}\t.,]*\d)?)(?:[ \t]*([A-Z]{3}))?$/u', $raw, $amount) === 1) {
                $currency = $amount[2] ?? 'EUR';
                $money = $this->moneyOrNull($amount[1], $currency, ',');
            }
        }

        $vs = null;
        if ($this->matched($this->field('VS:', '([0-9]+)'), $content, $result)) {
            ++$signals;
            $vs = $this->paymentSymbol($this->group($result, 1));
        }

        $ks = null;
        if ($this->matched($this->field('KS:', '([0-9]+)'), $content, $result)) {
            ++$signals;
            $ks = $this->paymentSymbol($this->group($result, 1));
        }

        $ss = null;
        if ($this->matched($this->field('(?:ŠS|SS|S):', '([0-9]+)'), $content, $result)) {
            ++$signals;
            $ss = $this->paymentSymbol($this->group($result, 1));
        }

        $sign = null;
        if ($this->matched($this->field('SIGN:', '(.*)$'), $content, $result)) {
            ++$signals;
            $value = mb_trim($this->group($result, 1), " \t\n\r\0\x0B\u{00A0}\u{2009}\u{202F}");
            if ($value !== '') {
                $sign = $value;
            }
        }

        if ($signals < 2) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'No VÚB fields found');
        }

        return new MailContent(
            money: $money,
            transactionDate: $transactionDate,
            accountNumber: $accountNumber,
            ownAccountNumber: $ownAccountNumber,
            vs: $vs,
            ss: $ss,
            ks: $ks,
            sign: $sign,
        );
    }
}
