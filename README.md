# Bank Mails Parser

Professional PHP library for parsing bank confirmation emails from Slovak and Czech banks. Extract transaction details, account numbers, amounts, and banking symbols automatically from email notifications.

[![CI](https://github.com/tomaj/bank-mails-parser/actions/workflows/ci.yml/badge.svg)](https://github.com/tomaj/bank-mails-parser/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/tomaj/bank-mails-parser/v/stable)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![PHP Version Require](https://poser.pugx.org/tomaj/bank-mails-parser/require/php)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![License](https://poser.pugx.org/tomaj/bank-mails-parser/license)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![Documentation](https://img.shields.io/badge/docs-latest-blue.svg)](https://tomaj.github.io/bank-mails-parser/)

## Why This Library?

Manual processing of bank notification emails is error-prone and time-consuming. This library provides a robust, tested solution for automatically extracting payment information from bank emails, enabling seamless integration with accounting systems, payment verification workflows, and financial automation.

## Features

- **Multiple Bank Support**: TatraBanka, ČSOB (CZ/SK), VÚB
- **Multiple Email Formats**: Plain text, HTML, PGP encrypted
- **Comprehensive Data Extraction**: Amounts, currencies, account numbers, banking symbols (VS, KS, SS)
- **Multi-Transaction Support**: Process emails containing multiple payments
- **PGP Decryption**: Handle encrypted bank statements
- **Type Safety**: Full PHP 8.4+ strict typing with property hooks
- **Well Tested**: High code coverage, comprehensive test suite, mutation tested
- **Production Ready**: PHPStan level max, PSR-12 compliant

## Supported Banks

| Bank | Country | Parser Classes | Email Types |
|------|---------|----------------|-------------|
| **TatraBanka** | Slovakia | `TatraBankaMailParser`<br>`TatraBankaSimpleMailParser`<br>`TatraBankaStatementMailParser` | Plain text<br>ComfortPay<br>PGP encrypted |
| **ČSOB** | Czech Republic | `CsobMailParser` | HTML multi-transaction |
| **ČSOB** | Slovakia | `SkCsobMailParser` | HTML multi-transaction |
| **VÚB** | Slovakia | `VubMailParser` | Plain text |

## Requirements

- PHP 8.4 or 8.5
- Composer

## Installation

```bash
composer require tomaj/bank-mails-parser
```

### Upgrading from 3.x?

See the [4.0 Upgrade Guide](UPGRADE-4.0.md) for breaking changes and migration steps.

## Quick Start

```php
<?php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent) {
    echo "Amount: " . $mailContent->amount . " " . $mailContent->currency . "\n";
    echo "Variable Symbol: " . $mailContent->vs . "\n";
    echo "Account: " . $mailContent->accountNumber . "\n";
}
```

## Usage Examples

### TatraBanka

#### Standard Notification Emails

```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

// Access transaction details
$mailContent->amount;              // float|null
$mailContent->currency;            // string|null (EUR, USD, etc.)
$mailContent->accountNumber;       // string|null
$mailContent->sourceAccountNumber; // string|null
$mailContent->vs;                  // string|null (Variable Symbol)
$mailContent->ks;                  // string|null (Constant Symbol)
$mailContent->ss;                  // string|null (Specific Symbol)
$mailContent->transactionDate;     // int|false|null (Unix timestamp)
$mailContent->receiverMessage;     // string|null
$mailContent->description;         // string|null
```

#### ComfortPay Payments

```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;

$parser = new TatraBankaSimpleMailParser();
$mailContent = $parser->parse($emailBody);

// Additional ComfortPay fields
$mailContent->cid;   // string|null (Client ID)
$mailContent->sign;  // string|null (HMAC signature)
$mailContent->res;   // string|null (Result code: OK, FAIL)
$mailContent->ac;    // string|null
$mailContent->txn;   // string|null (Transaction ID)
$mailContent->rc;    // string|null (Return code)
```

#### PGP Encrypted Statements

```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;

$decryptor = new TatraBankaMailDecryptor(
    '/path/to/private-key.asc',
    'your-passphrase'
);
$parser = new TatraBankaStatementMailParser($decryptor);
$mailContents = $parser->parseMulti($encryptedEmailBody);

foreach ($mailContents as $mailContent) {
    echo $mailContent->amount . " " . $mailContent->currency . "\n";
}
```

### ČSOB Czech Republic

```php
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

$parser = new CsobMailParser();
$mailContents = $parser->parseMulti($emailBody);

foreach ($mailContents as $mailContent) {
    echo "VS: " . $mailContent->vs . "\n";
    echo "Amount: " . $mailContent->amount . " " . $mailContent->currency . "\n";
    echo "Account: " . $mailContent->accountNumber . "\n";
    echo "From: " . $mailContent->sourceAccountNumber . "\n";
}
```

### ČSOB Slovakia

```php
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;

$parser = new SkCsobMailParser();
$mailContents = $parser->parseMulti($emailBody);

foreach ($mailContents as $mailContent) {
    echo "VS: " . $mailContent->getVs() . "\n";
    echo "KS: " . $mailContent->getKs() . "\n";
    echo "Amount: " . $mailContent->getAmount() . " " . $mailContent->getCurrency() . "\n";
}
```

### VÚB

```php
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

$parser = new VubMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent) {
    echo "VS: " . $mailContent->vs . "\n";
    echo "KS: " . $mailContent->ks . "\n";
    echo "Amount: " . $mailContent->amount . "\n";
    echo "Date: " . date('Y-m-d', $mailContent->transactionDate) . "\n";
}
```

## MailContent API

The `MailContent` object provides access to all extracted transaction data:

```php
// Financial Information
$mailContent->getAmount(): ?float           // Transaction amount
$mailContent->getCurrency(): ?string        // Currency code (EUR, CZK, USD, etc.)
$mailContent->getTransactionDate(): int|false|null  // Unix timestamp

// Account Information
$mailContent->getAccountNumber(): ?string        // Destination account
$mailContent->getSourceAccountNumber(): ?string  // Source account (when available)

// Banking Symbols
$mailContent->getVs(): ?string              // Variable Symbol
$mailContent->getKs(): ?string              // Constant Symbol
$mailContent->getSs(): ?string              // Specific Symbol

// Transaction Details
$mailContent->getReceiverMessage(): ?string // Payment message
$mailContent->getDescription(): ?string     // Transaction description
$mailContent->getTxn(): ?string            // Transaction ID

// TatraBanka ComfortPay Specific
$mailContent->getCid(): ?string             // Client ID
$mailContent->getSign(): ?string            // HMAC signature
$mailContent->getRes(): ?string             // Result code (OK, FAIL)
$mailContent->getAc(): ?string             // Authorization code
$mailContent->getCc(): ?string             // Credit card info
$mailContent->getRc(): ?string             // Return code
```

All getters return `null` if the data is not present in the email.

## Integration with IMAP

Example integration with [tomaj/imap-email-downloader](https://github.com/tomaj/imap-email-downloader):

```php
use Tomaj\ImapMailDownloader\Downloader;
use Tomaj\ImapMailDownloader\MailCriteria;
use Tomaj\ImapMailDownloader\Email;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$downloader = new Downloader('imap.example.com', 993, 'user@example.com', 'password');

$criteria = new MailCriteria();
$criteria->setFrom('notifications@tatrabanka.sk');

$downloader->fetch($criteria, function(Email $email) {
    $parser = new TatraBankaMailParser();
    $mailContent = $parser->parse($email->getBody());
    
    if ($mailContent) {
        // Process the transaction data
        processPayment($mailContent);
    }
    
    return true;
});
```

## Error Handling

Parsers return `null` when the email format is not recognized:

```php
$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent === null) {
    // Email format not recognized or parsing failed
    log("Unable to parse email");
} else {
    // Successfully parsed
    processTransaction($mailContent);
}
```

## Security Considerations

- Store PGP private keys outside the web root
- Use environment variables for sensitive configuration
- Validate all extracted amounts before processing payments
- Never expose raw email content in error messages
- Log parsing failures for security monitoring

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for:

- How to add support for new banks
- Development setup and coding standards
- Testing requirements and quality checks
- Pull request process

## License

This library is licensed under the [LGPL-2.0-or-later](https://www.gnu.org/licenses/old-licenses/lgpl-2.0.html) license.

## Support

- **Documentation**: [https://tomaj.github.io/bank-mails-parser/](https://tomaj.github.io/bank-mails-parser/)
- **Coverage Report**: [https://tomaj.github.io/bank-mails-parser/coverage/](https://tomaj.github.io/bank-mails-parser/coverage/)
- **Issues**: [GitHub Issues](https://github.com/tomaj/bank-mails-parser/issues)
- **Email**: tomasmajer@gmail.com
- **Discussions**: [GitHub Discussions](https://github.com/tomaj/bank-mails-parser/discussions)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed history of changes.
