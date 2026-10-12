<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Exception;

/**
 * More than one parser accepted the email. The facade does not pick the first one.
 */
final class AmbiguousBankException extends ParseException
{
    public static function forParsers(string ...$parsers): self
    {
        return new self('Email matches more than one bank parser: ' . implode(', ', $parsers));
    }
}
