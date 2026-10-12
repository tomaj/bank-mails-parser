# Getting Started (4.x)

::: warning Older Version Documentation
You are viewing documentation for version **4.x**. For the latest version with immutable architecture, see [5.x documentation](/) or read the [5.0 Upgrade Guide](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
:::

## Installation

```bash
composer require tomaj/bank-mails-parser ^4.0
```

## Requirements

- PHP 8.2, 8.3, or 8.4
- Composer

## Quick Start

### TatraBanka Parser

```php
<?php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

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

### TatraBanka (Slovakia)

- **TatraBankaMailParser** - Standard transaction notifications
- **TatraBankaSimpleMailParser** - ComfortPay/CardPay confirmations
- **TatraBankaStatementMailParser** - PGP-encrypted statements

### ČSOB

- **CsobMailParser** - ČSOB Czech Republic emails
- **SkCsobMailParser** - ČSOB Slovakia emails

### VÚB

- **VubMailParser** - VÚB Slovakia emails

## MailContent API (4.x)

Version 4.x uses getters and setters for accessing transaction data:

```php
// Reading values
$amount = $mailContent->getAmount();           // float|null
$currency = $mailContent->getCurrency();       // string|null
$vs = $mailContent->getVs();                   // string|null
$account = $mailContent->getAccountNumber();   // string|null

// All available getters
$mailContent->getAmount(): ?float
$mailContent->getCurrency(): ?string
$mailContent->getVs(): ?string
$mailContent->getSs(): ?string
$mailContent->getKs(): ?string
$mailContent->getAccountNumber(): ?string
$mailContent->getSourceAccountNumber(): ?string
$mailContent->getTransactionDate(): int|false|null
$mailContent->getReceiverMessage(): ?string
$mailContent->getDescription(): ?string
```

## Multi-Transaction Parsing

Some banks send multiple transactions in one email:

```php
$parser = new CsobMailParser();
$mailContents = $parser->parseMulti($emailBody);

foreach ($mailContents as $mailContent) {
    echo $mailContent->getAmount() . " " . $mailContent->getCurrency() . "\n";
    echo "VS: " . $mailContent->getVs() . "\n";
}
```

## Error Handling

Parsers return `null` when the email format is not recognized:

```php
$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent === null) {
    // Email format not recognized
    error_log("Unable to parse email");
} else {
    // Successfully parsed
    processTransaction($mailContent);
}
```

## Upgrading to 5.x

Version 5.x introduces a major API change to immutable value objects. See the [5.0 Upgrade Guide](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md) for migration instructions.

## Next Steps

- [API Reference](/4.x/api/reference) - Complete API documentation
- [Upgrade to 5.x](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md) - Migration guide
