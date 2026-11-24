<?php

declare(strict_types=1);

// ČSOB Slovakia (csob.sk) and ČSOB Czech Republic (csob.cz) can put several transactions in one mail:
// parseAll() returns every one of them.

require __DIR__ . '/vendor/autoload.php';

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;
use Tomaj\BankMailsParser\ValueObject\Money;

function show(MailContent $mail): void
{
    foreach (get_object_vars($mail) as $field => $value) {
        $value = match (true) {
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s P'),
            $value instanceof Money => $value->format(),
            default => $value,
        };
        if ($value !== null) {
            printf("  %-20s %s\n", $field, $value);
        }
    }
}

$mails = [
    'csob-sk-sepa.txt' => new SkCsobMailParser(),
    'csob-cz-instant.txt' => new CsobMailParser(),
    'csob-cz-two-transactions.txt' => new CsobMailParser(),
];

foreach ($mails as $file => $parser) {
    $transactions = $parser->parseAll((string) file_get_contents(__DIR__ . '/mails/' . $file));

    echo $file, ': ', count($transactions), " transaction(s)\n";
    foreach ($transactions as $index => $mail) {
        echo '  #', $index + 1, "\n";
        show($mail);
    }
    echo "\n";
}
