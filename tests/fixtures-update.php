<?php

declare(strict_types=1);

use Tomaj\BankMailsParser\Tests\FixtureCatalog;

if (!isset($argv[0]) || realpath($argv[0]) !== __FILE__) {
    return;
}

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$paths = glob($root . '/tests/fixtures/*/*.txt') ?: [];
sort($paths);
if ($paths === []) {
    fwrite(STDERR, "No fixtures found\n");
    exit(1);
}

foreach ($paths as $path) {
    $relative = substr($path, strlen($root) + 1);
    $expected = FixtureCatalog::expected($root, $relative);
    $json = json_encode($expected, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        fwrite(STDERR, "Could not encode $relative\n");
        exit(1);
    }
    file_put_contents(substr($path, 0, -4) . '.expected.json', $json . "\n");
    echo $relative, "\n";
}
