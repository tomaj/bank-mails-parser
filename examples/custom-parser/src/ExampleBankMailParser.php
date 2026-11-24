<?php

declare(strict_types=1);

namespace App;

use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

/**
 * Parser for the (fictional) Example banka, whose notification looks like this:
 *
 *   Incoming payment 24.11.2026
 *   Amount: 49.90 EUR
 *   From account: SK3112000000198742637541
 *   Reference: 2026001234
 */
final readonly class ExampleBankMailParser implements ParserInterface
{
    // tryParse(), parseAll() (one transaction) and money() come from the trait
    use ParsesBankMail;

    private const string HEADER = '/^Incoming payment (\d{1,2}\.\d{1,2}\.\d{4})$/m';

    #[\Override]
    public function bankDomains(): array
    {
        return ['bank.example'];
    }

    #[\Override]
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return preg_match(self::HEADER, $content) === 1;
    }

    public function parse(string $content): MailContent
    {
        if (preg_match(self::HEADER, $content, $header) !== 1) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Header not found');
        }
        if (preg_match('/^Amount: ([\d.]+) ([A-Z]{3})$/m', $content, $amount) !== 1) {
            throw InvalidEmailFormatException::missingRequiredField(self::class, 'Amount');
        }

        $date = \DateTimeImmutable::createFromFormat('!j.n.Y', $header[1], new \DateTimeZone('Europe/Bratislava'));

        return new MailContent(
            money: $this->money($amount[1], $amount[2], '.'),
            transactionDate: $date === false ? null : $date,
            accountNumber: $this->line($content, 'From account'),
            vs: $this->line($content, 'Reference'),
        );
    }

    private function line(string $content, string $label): ?string
    {
        return preg_match('/^' . preg_quote($label, '/') . ': (.+)$/m', $content, $match) === 1 ? $match[1] : null;
    }
}
