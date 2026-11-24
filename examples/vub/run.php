<?php

declare(strict_types=1);

// VÚB mails one payment as "Label: value" lines.

require __DIR__ . '/vendor/autoload.php';

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;
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

$parser = new VubMailParser();
$mail = $parser->parse((string) file_get_contents(__DIR__ . '/mails/payment.txt'));

echo "payment.txt\n";
show($mail);

// accountNumber is the payer ("Z účtu"), ownAccountNumber is your account ("Na účet").
echo "  => {$mail->money?->format()} from {$mail->accountNumber} to {$mail->ownAccountNumber}\n";
