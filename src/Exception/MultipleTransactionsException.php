<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Exception;

/**
 * The email contains more than one transaction. Call parseAll().
 */
final class MultipleTransactionsException extends ParseException
{
    public static function useParseAll(int $count): self
    {
        return new self(sprintf('Email contains %d transactions. Use parseAll().', $count));
    }
}
