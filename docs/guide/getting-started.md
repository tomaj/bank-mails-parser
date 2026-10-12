# Getting Started

Bank Mails Parser is a professional PHP library for parsing confirmation emails from Slovak and Czech banks. It automatically extracts transaction details like amounts, account numbers, variable symbols, and more.

## Installation

Install via Composer:

```bash
composer require tomaj/bank-mails-parser
```

## Requirements

- PHP 8.4 or 8.5
- Composer

## Quick Start

Here's a simple example parsing a TatraBanka notification email:

```php
<?php

use Tomaj\BankMailsParser\BankMailsParser;

require 'vendor/autoload.php';

$parser = new BankMailsParser();
$mailContent = $parser->parse($emailBody, $sender, $subject);

echo $mailContent->money?->toDecimalString() . ' ' . $mailContent->money?->currency->value . "\n";
echo $mailContent->vs . "\n";
echo $mailContent->accountNumber . "\n";
echo $mailContent->transactionDate?->format('Y-m-d') . "\n";
```

`parse()` throws when the email cannot be read. `tryParse()` returns `null` instead. See [Bank Detection](/guide/bank-detection) and [Exceptions](/guide/exceptions).

## Supported Banks

- **TatraBanka (SK)** - Standard notifications, ComfortPay, and PGP-encrypted statements
- **ČSOB (CZ)** - Multi-transaction mail. Pass the decoded plain text; HTML is not parsed.
- **ČSOB (SK)** - Multi-transaction mail. Pass the decoded plain text; HTML is not parsed.
- **VÚB (SK)** - Plain text notifications

## Next Steps

- Learn about [Examples](/guide/examples) to see various parsing scenarios
- Detect the bank with [BankMailsParser](/guide/bank-detection)
- Handle [exceptions](/guide/exceptions)
- Check bank-specific guides: [TatraBanka](/guide/tatrabanka), [ČSOB CZ](/guide/csob-cz), [ČSOB SK](/guide/csob-sk), [VÚB](/guide/vub)
- See the [API Reference](/api/reference) for complete method documentation
- Learn how to [add a new bank](/guide/adding-bank) parser
