<?php

declare(strict_types=1);

// A parser for a bank the library does not support, registered next to the built-in parsers.

require __DIR__ . '/vendor/autoload.php';

use App\ExampleBankMailParser;
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\ParseException;

$parser = new BankMailsParser();            // the built-in parsers
$parser->registerParser(new ExampleBankMailParser());

foreach ($parser->getParsers() as $registered) {
    echo 'parser: ', $registered::class, ' (', implode(', ', $registered->bankDomains()), ")\n";
}

$mail = $parser->parse((string) file_get_contents(__DIR__ . '/mails/example-bank.txt'), 'notify@bank.example');
echo "\nexample-bank.txt\n";
echo '  money            ', $mail->money?->format(), "\n";
echo '  transactionDate  ', $mail->transactionDate?->format('Y-m-d'), "\n";
echo '  accountNumber    ', $mail->accountNumber, "\n";
echo '  vs               ', $mail->vs, "\n";

try {
    $parser->parse((string) file_get_contents(__DIR__ . '/mails/example-bank-broken.txt'), 'notify@bank.example');
} catch (ParseException $e) {
    echo "\nexample-bank-broken.txt\n  ", $e->getMessage(), "\n";
}

// Only the parser for the sender's domain is asked: the same mail from another domain is not parsed.
$other = $parser->tryParse((string) file_get_contents(__DIR__ . '/mails/example-bank.txt'), 'notify@other.example');
echo "\nfrom other.example: ", $other === null ? 'null' : 'parsed', "\n";
