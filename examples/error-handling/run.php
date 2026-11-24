<?php

declare(strict_types=1);

// What each parse method does with a mail it cannot read. parse() throws, tryParse() returns null,
// parseAll() returns every transaction. Every exception implements BankMailsParserException.

require __DIR__ . '/vendor/autoload.php';

use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\BankMailsParserException;
use Tomaj\BankMailsParser\Exception\InvalidArgumentException;
use Tomaj\BankMailsParser\ValueObject\Money;

$parser = new BankMailsParser();
$mail = static fn(string $file): string => (string) file_get_contents(__DIR__ . '/mails/' . $file);

$cases = [
    // [file, sender]
    ['not-a-bank.txt', null],
    ['two-transactions.txt', 'info@csob.cz'],
    ['broken-balance.txt', 'info@csob.cz'],
    ['card-result-two-amounts.txt', 'cardpay@tatrabanka.sk'],
];

foreach ($cases as [$file, $sender]) {
    echo $file, "\n";

    try {
        $result = $parser->parse($mail($file), $sender);
        echo '  parse():    ', $result->money?->format(), "\n";
    } catch (BankMailsParserException $e) {
        echo '  parse():    ', new ReflectionClass($e)->getShortName(), ': ', $e->getMessage(), "\n";
    }

    $result = $parser->tryParse($mail($file), $sender);
    echo '  tryParse(): ', $result === null ? 'null' : $result->money?->format(), "\n";

    try {
        $all = $parser->parseAll($mail($file), $sender);
        echo '  parseAll(): ', implode(', ', array_map(static fn($t): string => (string) $t->money?->format(), $all)), "\n";
    } catch (BankMailsParserException $e) {
        echo '  parseAll(): ', new ReflectionClass($e)->getShortName(), "\n";
    }
    echo "\n";
}

// Money never guesses: "1.234" could be 1.234 or 1234, so it is rejected without a decimal separator.
foreach ([['1 234,56', null], ['1.234', null], ['1.234', ','], ['12.345', '.']] as [$amount, $separator]) {
    try {
        echo "Money '{$amount}' (separator ", $separator ?? 'auto', '): ', Money::fromDecimalString($amount, 'EUR', $separator)->format(), "\n";
    } catch (InvalidArgumentException $e) {
        echo 'InvalidArgumentException: ', $e->getMessage(), "\n";
    }
}
