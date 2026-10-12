<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Exception;

/**
 * Thrown when email format is recognized but required fields are missing or malformed.
 */
final class InvalidEmailFormatException extends ParseException
{
    public static function missingRequiredField(string $parserClass, string $fieldName, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Parser %s: required field "%s" is missing or invalid', $parserClass, $fieldName), 0, $previous);
    }

    public static function malformedContent(string $parserClass, string $reason, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Parser %s: %s', $parserClass, $reason), 0, $previous);
    }
}
