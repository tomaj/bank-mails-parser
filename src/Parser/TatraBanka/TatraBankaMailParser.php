<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\BankDate;
use Tomaj\BankMailsParser\Parser\MailWhitespace;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

final readonly class TatraBankaMailParser implements ParserInterface
{
    use ParsesBankMail;

    private const string TRANSACTION = '/^' . MailWhitespace::WS . '(\d{1,2}\.[ \t\x{00A0}\x{2009}\x{202F}]*\d{1,2}\.[ \t\x{00A0}\x{2009}\x{202F}]*\d{2,4}(?:[ \t\x{00A0}\x{2009}\x{202F}]+\d{1,2}:\d{2}(?::\d{2})?)?|\d{4}-\d{2}-\d{2}(?:[ \t\x{00A0}\x{2009}\x{202F}]+\d{1,2}:\d{2}(?::\d{2})?)?|\d{1,2}[ \t\x{00A0}\x{2009}\x{202F}]+[A-Za-z]+[ \t\x{00A0}\x{2009}\x{202F}]+\d{4}(?:[ \t\x{00A0}\x{2009}\x{202F}]+\d{1,2}:\d{2}(?::\d{2})?)?)[ \t\x{00A0}\x{2009}\x{202F}]+bol zostatok Vasho uctu ([A-Za-z0-9]+) (zvyseny|znizeny) o ([0-9 \x{00A0}\x{2009}\x{202F}\t]+,[0-9]+) ([A-Za-z]+)/mu';

    /**
     * @return list<string>
     */
    #[\Override]
    public function bankDomains(): array
    {
        return ['tatrabanka.sk'];
    }

    #[\Override]
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return $this->supportsQuietly(function () use ($content, $sender, $subject): bool {
            $content = $this->withoutFreeText($this->scrubUtf8($this->withoutByteOrderMark($content)));
            if ($this->matched(self::TRANSACTION, $content)) {
                return true;
            }

            $senderMatches = $sender !== null && $this->senderDomain($sender, 'tatrabanka.sk');
            $subjectMatches = $subject !== null && str_contains(strtolower($subject), 'tatra');

            return ($senderMatches || $subjectMatches) && str_contains($content, 'zostatok Vasho uctu');
        });
    }

    public function parse(string $content): MailContent
    {
        $original = $this->scrubUtf8($this->requireUniformLineBreaks($content));
        $description = $this->lineValue($original, 'Popis transakcie');
        $receiverMessage = $this->lineValue($original, 'Informacia pre prijemcu');
        $sourceAccountNumber = null;
        if ($description !== null) {
            $descriptionParts = explode(' ', $description, 2);
            $sourceAccountNumber = count($descriptionParts) === 2 ? $descriptionParts[1] : $descriptionParts[0];
        }

        $content = $this->withoutFreeText($original);
        if (!$this->matched(self::TRANSACTION, $content, $matches)) {
            throw InvalidEmailFormatException::malformedContent(
                self::class,
                'Could not find required transaction pattern',
            );
        }

        $rawDate = trim($this->group($matches, 1));
        $transactionDate = BankDate::parseFirst($rawDate, [
            '!j. n. Y G:i:s',
            '!j.n.Y G:i:s',
            '!j. n. Y G:i',
            '!j.n.Y G:i',
            '!Y-m-d G:i:s',
            '!Y-m-d H:i:s',
            '!Y-m-d G:i',
            '!Y-m-d H:i',
            '!j. n. Y',
            '!j.n.Y',
            '!Y-m-d',
        ], BankDate::bratislava());
        if ($transactionDate === null && preg_match('/[A-Za-z]/', $rawDate) === 1) {
            $transactionDate = BankDate::parseEnglish($rawDate, BankDate::bratislava());
        }

        $accountNumber = $this->group($matches, 2);
        $amount = $this->group($matches, 4);
        if ($this->group($matches, 3) === 'znizeny') {
            $amount = '-' . $amount;
        }

        $money = $this->money($amount, $this->group($matches, 5), ',');

        $vs = null;
        $ss = null;
        $ks = null;
        if ($this->matched($this->field('Referencia platitela:', '\/VS(\d*)\/SS(\d*)\/KS(\d*)'), $original, $refMatches)) {
            $vs = $this->paymentSymbol($this->group($refMatches, 1), null);
            $ss = $this->paymentSymbol($this->group($refMatches, 2), null);
            $ks = $this->paymentSymbol($this->group($refMatches, 3), null);
        }

        // 4.1.0 fallbacks. They read payer text and never the amount, account, or date.
        if ($vs === null && $this->matched('/vs([0-9]{1,10})(?!\d)/i', $original, $prefixed)) {
            $vs = $this->group($prefixed, 1);
        }
        if ($vs === null && $this->matched('/Informacia pre prijemcu:.*\b([0-9]{1,10})\b.*/i', $original, $fromMessage)) {
            $vs = $this->group($fromMessage, 1);
        }
        if ($vs === null && $this->matched('/Referencia platitela:.*\b([0-9]{1,10})\b.*/i', $original, $fromReference)) {
            $vs = $this->group($fromReference, 1);
        }

        return new MailContent(
            money: $money,
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
