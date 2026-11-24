<?php

declare(strict_types=1);

// Read every mail of a payments mailbox, let BankMailsParser pick the bank parser from the sender
// and the body, and match each transaction to an open order by its variable symbol and amount.

require __DIR__ . '/vendor/autoload.php';

use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\ParseException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;

/**
 * The sender address, the subject and the body of a plain-text RFC 5322 message.
 *
 * @return array{string, string, string}
 */
function readMail(string $path): array
{
    [$head, $body] = explode("\n\n", str_replace("\r\n", "\n", (string) file_get_contents($path)), 2);
    $headers = iconv_mime_decode_headers($head, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
    $from = (string) ($headers['From'] ?? '');
    $address = preg_match('/<([^>]+)>/', $from, $match) === 1 ? $match[1] : $from;

    return [$address, (string) ($headers['Subject'] ?? ''), $body];
}

// Open orders by variable symbol, with the amount the customer has to pay
$orders = [
    '1234056789' => '12.31 EUR',
    '5000001234' => '33.99 EUR',
    '9999999999' => '13.37 EUR',
    '212049000' => '1200.00 EUR',
];

$parser = new BankMailsParser();

foreach (glob(__DIR__ . '/mailbox/*.eml') ?: [] as $path) {
    [$sender, $subject, $body] = readMail($path);
    echo basename($path), " from {$sender}\n";

    try {
        // the sender restricts the choice to the parsers of that bank's domain
        $transactions = $parser->parseAll($body, $sender, $subject);
    } catch (UnsupportedBankException $e) {
        echo "  skipped: {$e->getMessage()}\n";
        continue;
    } catch (ParseException $e) {
        // a bank mail that could not be read: keep it for a human, never guess
        echo '  needs review: ', $e::class, ": {$e->getMessage()}\n";
        continue;
    }

    foreach ($transactions as $transaction) {
        $amount = $transaction->money?->format() ?? 'no amount';
        $vs = $transaction->vs ?? '-';
        $expected = $orders[$vs] ?? null;

        $verdict = match (true) {
            $transaction->res !== null && $transaction->res !== 'OK' => "card payment result {$transaction->res}",
            $expected === null => 'no open order with this variable symbol',
            $expected !== $amount => "amount differs, the order expects {$expected}",
            default => 'order paid',
        };
        echo "  VS {$vs}, {$amount}: {$verdict}\n";
    }
}
