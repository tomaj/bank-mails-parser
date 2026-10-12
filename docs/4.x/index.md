---
layout: home
title: Bank Mails Parser 4.x
---

::: warning Older Version Documentation
You are viewing documentation for version **4.x**. This version uses the older mutable API with getters/setters.

For the latest version with immutable architecture, see [5.x documentation](/) or read the [5.0 Upgrade Guide](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
:::

# Bank Mails Parser 4.x

Professional PHP library for parsing bank confirmation emails from Slovak and Czech banks.

## Features

- 📧 **Multiple email formats**: Plain text, HTML, PGP encrypted
- 💰 **Financial data extraction**: Amounts, currencies, account numbers  
- 🔢 **Banking symbols**: Variable Symbol (VS), Constant Symbol (KS), Specific Symbol (SS)
- 🔐 **PGP decryption**: Handle encrypted bank statements
- 🔄 **Multi-transaction support**: Process emails with multiple payments
- 🛡️ **Type safety**: Full PHP 8.2+ strict typing
- ✅ **Well tested**: 95%+ code coverage

## Quick Start

```bash
composer require tomaj/bank-mails-parser ^4.0
```

```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent) {
    echo "Amount: " . $mailContent->getAmount() . "\n";
    echo "VS: " . $mailContent->getVs() . "\n";
}
```

## Requirements

- PHP 8.2, 8.3, or 8.4
- Composer

## Supported Banks

- **TatraBanka** (Slovakia) - 3 parsers
- **ČSOB CZ** (Czech Republic) - HTML multi-transaction
- **ČSOB SK** (Slovakia) - HTML multi-transaction  
- **VÚB** (Slovakia) - Plain text

## Learn More

- [Getting Started Guide](/4.x/guide/getting-started)
- [API Reference](/4.x/api/reference)
- [Upgrade to 5.x](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md)
