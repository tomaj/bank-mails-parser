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

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

require 'vendor/autoload.php';

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent !== null) {
    echo "Amount: " . $mailContent->getAmount() . " " . $mailContent->getCurrency() . "\n";
    echo "Variable Symbol: " . $mailContent->getVs() . "\n";
    echo "Account: " . $mailContent->getAccountNumber() . "\n";
    echo "Date: " . date('Y-m-d', $mailContent->getTransactionDate()) . "\n";
}
```

## Supported Banks

- **TatraBanka (SK)** - Standard notifications, ComfortPay, and PGP-encrypted statements
- **ČSOB (CZ)** - Multi-transaction HTML emails
- **ČSOB (SK)** - Multi-transaction HTML emails
- **VÚB (SK)** - Plain text notifications

## Next Steps

- Learn about [Examples](/guide/examples) to see various parsing scenarios
- Check bank-specific guides: [TatraBanka](/guide/tatrabanka), [ČSOB CZ](/guide/csob-cz), [ČSOB SK](/guide/csob-sk), [VÚB](/guide/vub)
- See the [API Reference](/api/reference) for complete method documentation
- Learn how to [add a new bank](/guide/adding-bank) parser
