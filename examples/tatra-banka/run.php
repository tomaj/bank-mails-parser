<?php

declare(strict_types=1);

// Parse Tatra banka b-mail notifications ("bol zostatok Vasho uctu ... zvyseny/znizeny o ...").

require __DIR__ . '/vendor/autoload.php';

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
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

$parser = new TatraBankaMailParser();

foreach (['incoming.txt', 'outgoing.txt', 'structured-reference.txt'] as $file) {
    $body = (string) file_get_contents(__DIR__ . '/mails/' . $file);

    echo $file, ' supported: ', $parser->supports($body) ? 'yes' : 'no', "\n";
    $mail = $parser->parse($body);
    show($mail);

    // Money is an integer number of minor units, never a float
    $direction = $mail->money?->isNegative() ? 'outgoing' : 'incoming';
    echo "  => {$direction} payment of {$mail->money?->amount} minor units\n\n";
}
