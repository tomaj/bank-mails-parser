<?php

declare(strict_types=1);

// Tatra banka sends the daily card statement as a PGP message encrypted for your key. The decryptor
// opens it with the private key and its passphrase; every statement row becomes one transaction.
// private-key.asc is a throwaway test key, use your own key from a secret store in production.

require __DIR__ . '/vendor/autoload.php';

use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;

$body = (string) file_get_contents(__DIR__ . '/mails/statement-body.txt');
$sender = 'vypis@tatrabanka.sk';

// The statement parser is registered only when the facade gets a decryptor.
$parser = new BankMailsParser(decryptor: new TatraBankaMailDecryptor(__DIR__ . '/private-key.asc', 'heslo'));

$rows = $parser->parseAll($body, $sender);
echo count($rows), " card transactions\n";
foreach ($rows as $row) {
    printf(
        "  settled %s  %10s  VS %s  merchant account %s\n",
        $row->transactionDate?->format('Y-m-d'),
        $row->money?->format(),
        $row->vs,
        $row->accountNumber,
    );
}

// A wrong passphrase is a setup error: ConfigurationException, even from tryParse().
$wrongKey = new BankMailsParser(decryptor: new TatraBankaMailDecryptor(__DIR__ . '/private-key.asc', 'wrong'));
try {
    $wrongKey->tryParse($body, $sender);
} catch (ConfigurationException $e) {
    echo "\nwrong passphrase: ", $e::class, ': ', $e->getMessage(), "\n";
}

// Without a decryptor no parser accepts the encrypted mail.
$rows = new BankMailsParser()->tryParse($body, $sender);
echo 'without a decryptor: ', $rows === null ? 'null' : 'parsed', "\n";
