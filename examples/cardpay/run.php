<?php

declare(strict_types=1);

// Tatra banka mails the result of a CardPay or ComfortPay card payment as one line of KEY=value pairs.

require __DIR__ . '/vendor/autoload.php';

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;
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

$parser = new TatraBankaSimpleMailParser();

foreach (['cardpay-ok.txt', 'comfortpay-ok.txt', 'comfortpay-fail.txt'] as $file) {
    $mail = $parser->parse((string) file_get_contents(__DIR__ . '/mails/' . $file));

    echo $file, "\n";
    show($mail);

    // res is the bank's result (TRES when present). The mail is not signed for you: verify
    // sign (HMAC) with your gateway key before you trust it, for example with omnipay-tatra.
    echo '  => ', $mail->res === 'OK' ? 'paid' : 'not paid', ", variable symbol {$mail->vs}\n\n";
}
