<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FixtureSnapshotTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string}>
     */
    public static function fixtures(): iterable
    {
        $root = dirname(__DIR__);
        $paths = glob($root . '/tests/fixtures/*/*.txt') ?: [];
        sort($paths);
        if ($paths === []) {
            throw new RuntimeException('No fixtures found');
        }

        foreach ($paths as $path) {
            $relative = substr($path, strlen($root) + 1);
            yield $relative => [$relative];
        }
    }

    #[Test]
    #[DataProvider('fixtures')]
    public function everyFixtureMatchesItsExpectedFile(string $relative): void
    {
        $root = dirname(__DIR__);
        $expectedPath = $root . '/' . substr($relative, 0, -4) . '.expected.json';
        $this->assertFileExists($expectedPath, $relative . ' is missing .expected.json');
        $expected = json_decode((string) file_get_contents($expectedPath), true);
        $this->assertIsArray($expected, $relative);
        $this->assertSame($expected, FixtureCatalog::expected($root, $relative), $relative);
    }
}
