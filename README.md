# Bank Mails Parser

Professional PHP library for parsing bank confirmation emails from Slovak and Czech banks. Extract transaction details, account numbers, amounts, and banking symbols automatically from email notifications.

[![CI](https://github.com/tomaj/bank-mails-parser/actions/workflows/ci.yml/badge.svg)](https://github.com/tomaj/bank-mails-parser/actions/workflows/ci.yml)
[![OpenSSF Scorecard](https://api.scorecard.dev/projects/github.com/tomaj/bank-mails-parser/badge)](https://scorecard.dev/viewer/?uri=github.com/tomaj/bank-mails-parser)
[![OpenSSF Best Practices](https://www.bestpractices.dev/projects/15407/badge)](https://www.bestpractices.dev/projects/15407)
[![Latest Stable Version](https://poser.pugx.org/tomaj/bank-mails-parser/v/stable)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![PHP Version Require](https://poser.pugx.org/tomaj/bank-mails-parser/require/php)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENCE)
[![Documentation](https://img.shields.io/badge/docs-latest-blue.svg)](https://tomaj.github.io/bank-mails-parser/)

## Why This Library?

Manual processing of bank notification emails is error-prone and time-consuming. This library provides a robust, tested solution for automatically extracting payment information from bank emails, enabling seamless integration with accounting systems, payment verification workflows, and financial automation.

## Features

- **Multiple Bank Support**: TatraBanka, ČSOB (CZ/SK), VÚB
- **Multiple Email Formats**: Decoded plain text and PGP encrypted statements. HTML must be converted to text before parsing.
- **Comprehensive Data Extraction**: Amounts, currencies, account numbers, banking symbols (VS, KS, SS)
- **Multi-Transaction Support**: Process emails containing multiple payments
- **PGP Decryption**: Handle encrypted bank statements
- **Type Safety**: Full PHP 8.4+ strict typing with final readonly classes
- **Well Tested**: High code coverage, comprehensive test suite, mutation tested
- **Production Ready**: PHPStan level max, PER-CS + PHP 8.4 migration rules compliant

## Supported Banks

| Bank | Country | Parser Classes | Email Types |
|------|---------|----------------|-------------|
| **TatraBanka** | Slovakia | `TatraBankaMailParser`<br>`TatraBankaSimpleMailParser`<br>`TatraBankaStatementMailParser` | Plain text<br>ComfortPay<br>PGP encrypted |
| **ČSOB** | Czech Republic | `CsobMailParser` | Decoded text, possibly several transactions |
| **ČSOB** | Slovakia | `SkCsobMailParser` | Decoded text, possibly several transactions |
| **VÚB** | Slovakia | `VubMailParser` | Plain text |

## Requirements

- PHP 8.4 or 8.5
- `ext-mbstring`
- Composer

## Installation

```bash
composer require tomaj/bank-mails-parser ^5.0
```

### Upgrading?

- **From 4.x to 5.0**: See the [5.0 Upgrade Guide](UPGRADE-5.0.md) for breaking changes and migration steps
- **From 3.x to 4.0**: See the [4.0 Upgrade Guide](UPGRADE-4.0.md)

## Quick Start

```php
<?php
use Tomaj\BankMailsParser\BankMailsParser;

$parser = new BankMailsParser();
$mailContent = $parser->parse($emailBody, $sender, $subject);

echo $mailContent->money?->toDecimalString() . ' ' . $mailContent->money?->currency->value . "\n";
echo $mailContent->vs . "\n";
echo $mailContent->accountNumber . "\n";

// ČSOB mail and statement files can contain more than one transaction.
foreach ($parser->parseAll($emailBody, $sender, $subject) as $row) {
    echo $row->money?->toDecimalString(), "\n";
}
```

Pass `$sender` (the From address). `parse()` throws `UnsupportedBankException`, `InvalidEmailFormatException`, `AmbiguousBankException`, or `MultipleTransactionsException` when the mail has more than one transaction. `tryParse()` returns `null` for those. Use `parseAll()` for ČSOB and for statements.

## Usage Examples

### TatraBanka

#### Standard Notification Emails

```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

$mailContent->money?->amount;            // int minor units, or null money
$mailContent->money?->toDecimalString(); // "12.31"
$mailContent->money?->currency->value;   // "EUR"
$mailContent->accountNumber;             // string|null
$mailContent->sourceAccountNumber;       // string|null
$mailContent->vs;                        // string|null (Variable Symbol)
$mailContent->ks;                        // string|null (Constant Symbol)
$mailContent->ss;                        // string|null (Specific Symbol)
$mailContent->transactionDate;           // ?DateTimeImmutable
$mailContent->receiverMessage;           // string|null
$mailContent->description;               // string|null
```

#### ComfortPay Payments

```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;

$parser = new TatraBankaSimpleMailParser();
$mailContent = $parser->parse($emailBody);

// Additional ComfortPay fields
$mailContent->cid;   // string|null (Client ID)
$mailContent->sign;  // string|null (HMAC or SIGN)
$mailContent->res;   // string|null (Result code: OK, FAIL)
$mailContent->tres;  // string|null (also copied into res when present)
$mailContent->ac;    // string|null
$mailContent->cc;    // string|null (masked card)
$mailContent->tid;   // string|null
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
    echo $mailContent->money?->toDecimalString() . ' ' . $mailContent->money?->currency->value . "\n";
}
```

### ČSOB Czech Republic

```php
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

$parser = new CsobMailParser();
$mailContents = $parser->parseMulti($emailBody);

foreach ($mailContents as $mailContent) {
    echo "VS: " . $mailContent->vs . "\n";
    echo 'Amount: ' . $mailContent->money?->toDecimalString() . ' ' . $mailContent->money?->currency->value . "\n";
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
    echo "VS: " . $mailContent->vs . "\n";
    echo "KS: " . $mailContent->ks . "\n";
    echo 'Amount: ' . $mailContent->money?->toDecimalString() . ' ' . $mailContent->money?->currency->value . "\n";
}
```

### VÚB

```php
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

$parser = new VubMailParser();
$mailContent = $parser->parse($emailBody);

echo $mailContent->vs . "\n";
echo $mailContent->ks . "\n";
echo $mailContent->money?->toDecimalString() . "\n";
echo $mailContent->transactionDate?->format('Y-m-d') . "\n";
```

## MailContent API

The `MailContent` object is an immutable value object (PHP 8.4+ `final readonly class`) with public readonly properties:

```php
// Financial Information
$mailContent->money: ?Money                     // amount (int minor units) + Currency
$mailContent->money?->toDecimalString();        // decimal string, e.g. "12.34"
$mailContent->transactionDate: ?DateTimeImmutable

// Account Information
$mailContent->accountNumber: ?string            // Destination account
$mailContent->sourceAccountNumber: ?string      // Source account (when available)

// Banking Symbols
$mailContent->vs: ?string                       // Variable Symbol
$mailContent->ks: ?string                       // Constant Symbol
$mailContent->ss: ?string                       // Specific Symbol

// Transaction Details
$mailContent->receiverMessage: ?string          // Payment message
$mailContent->description: ?string              // Transaction description
$mailContent->txn: ?string                      // Transaction ID

// TatraBanka ComfortPay Specific
$mailContent->cid: ?string                      // Client ID
$mailContent->sign: ?string                     // HMAC signature
$mailContent->res: ?string                      // Result code (OK, FAIL)
$mailContent->ac: ?string                       // Authorization code
$mailContent->cc: ?string                       // Credit card info
$mailContent->rc: ?string                       // Return code
```

All properties return `null` if the data is not present in the email.

## Integration with IMAP

Example integration with [tomaj/imap-email-downloader](https://github.com/tomaj/imap-email-downloader):

```php
use Tomaj\ImapMailDownloader\Downloader;
use Tomaj\ImapMailDownloader\MailCriteria;
use Tomaj\ImapMailDownloader\Email;
use Tomaj\BankMailsParser\BankMailsParser;

$downloader = new Downloader('imap.example.com', 993, 'user@example.com', 'password');

$criteria = new MailCriteria();
$criteria->setFrom('notifications@tatrabanka.sk');

$downloader->fetch($criteria, function(Email $email) {
    $parser = new BankMailsParser();

    try {
        foreach ($parser->parseAll($email->getBody(), 'notifications@tatrabanka.sk') as $mailContent) {
            processPayment($mailContent);
        }
    } catch (\Tomaj\BankMailsParser\Exception\ParseException) {
        // unrecognized mail, or payer text that looks like a second transaction
    }

    return true;
});
```

## Error Handling

`parse()` throws. `tryParse()` returns `null`. Both methods exist on `BankMailsParser` and on each parser.

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;

$parser = new BankMailsParser();

try {
    processTransaction($parser->parse($emailBody, $sender, $subject));
} catch (UnsupportedBankException $exception) {
    log($exception->getMessage());
} catch (InvalidEmailFormatException $exception) {
    log($exception->getMessage());
}
```

## Security Considerations

- Store PGP private keys outside the web root
- Use environment variables for sensitive configuration
- Validate all extracted amounts before processing payments
- Never expose raw email content in error messages
- Log parsing failures for security monitoring
- This library does not verify CardPay `HMAC` or `SIGN`, and it does not verify DKIM. Check both before you book a payment. A passed sender limits parsing to that bank's parsers, and a mail claimed by two parsers throws `AmbiguousBankException`. Free-text fields cannot supply the amount, currency, account, or date. The variable symbol can come from a message the payer wrote (`Informacia pre prijemcu`, `Zpráva příjemci`, `Identifikace`, `Účel platby`, a mandate reference, or a creditor reference). When `Referencia platitela` is absent, Tatra still takes the last number in that message, as in 4.1.0. When you pair a payment, always check the amount as well, and another identifier when you have one. A ČSOB mail with several transactions and no balance line is parsed the same way as 4.1.0. That is a known limitation: without those lines a forged transaction in payer text cannot be distinguished from a real one. Check the amount against the bank API or a statement. DKIM alone does not make a parsed amount safe.

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for:

- How to add support for new banks
- Development setup and coding standards
- Testing requirements and quality checks
- Pull request process

## License

This library is licensed under the [MIT License](LICENCE).

## Support

- **Documentation**: [https://tomaj.github.io/bank-mails-parser/](https://tomaj.github.io/bank-mails-parser/)
- **Coverage Report**: [https://tomaj.github.io/bank-mails-parser/coverage/](https://tomaj.github.io/bank-mails-parser/coverage/)
- **Issues**: [GitHub Issues](https://github.com/tomaj/bank-mails-parser/issues)
- **Email**: tomasmajer@gmail.com
- **Discussions**: [GitHub Discussions](https://github.com/tomaj/bank-mails-parser/discussions)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed history of changes.
