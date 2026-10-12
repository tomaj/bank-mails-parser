<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Exception;

/**
 * Thrown when no parser can handle the provided email content.
 */
final class UnsupportedBankException extends ParseException
{
    public static function noParserFound(): self
    {
        return new self('No parser found that supports this email format. The email may be from an unsupported bank or have an unrecognized format.');
    }

    public static function withSender(?string $sender): self
    {
        if ($sender !== null) {
            return new self('No parser found for the supplied sender.');
        }

        return self::noParserFound();
    }
}
