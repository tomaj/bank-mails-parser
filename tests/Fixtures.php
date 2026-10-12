<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use RuntimeException;

final class Fixtures
{
    public static function load(string $name): string
    {
        $path = dirname(__DIR__) . '/tests/fixtures/' . $name . '.txt';
        $body = file_get_contents($path);
        if ($body === false) {
            throw new RuntimeException('Missing fixture ' . $name);
        }

        return $body;
    }
}
