<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser;

/**
 * Matches a sender address against a bank domain.
 *
 * @internal
 */
final class SenderHost
{
    public static function matches(string $sender, string $domain): bool
    {
        $address = strtolower(trim($sender, " \t<>\"'"));

        return preg_match('/@(?:[a-z0-9-]+\.)*' . preg_quote(strtolower($domain), '/') . '$/', $address) === 1;
    }
}
