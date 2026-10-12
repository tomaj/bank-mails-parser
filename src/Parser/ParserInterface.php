<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser;

use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;

/**
 * What a bank parser must provide so the facade can select it and read every transaction.
 *
 * Single-transaction parse() and tryParse() stay on the concrete parser (the ParsesBankMail
 * trait supplies tryParse()). They are not part of this interface: a custom parser only has
 * to detect a mail, name its domains, and return every transaction.
 */
interface ParserInterface
{
    /**
     * Check if this parser can handle the given email content.
     */
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool;

    /**
     * Parse every transaction in the email.
     *
     * @return list<\Tomaj\BankMailsParser\MailContent>
     * @throws InvalidEmailFormatException if email format is recognized but invalid
     */
    public function parseAll(string $content): array;

    /**
     * Hosts this parser may read when the caller passes a sender.
     *
     * An empty list means the parser is used only when the sender is omitted.
     * Pass the sender. A parser with an empty list is skipped when a sender is set.
     *
     * @return list<string>
     */
    public function bankDomains(): array;
}
