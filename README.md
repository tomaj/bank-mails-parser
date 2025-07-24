# Bank Mails Parser

A modern PHP library for parsing Slovak bank confirmation emails with strong typing and comprehensive testing.

Currently supports **Tatrabanka** email formats including standard transaction confirmations and ComfortPay notifications.

## Features

- 🏦 **Tatrabanka Support**: Parse standard and ComfortPay confirmation emails
- 🔒 **Type Safe**: Built with PHP 8.0+ strict typing throughout
- 📅 **Modern DateTime**: Uses `DateTimeInterface` for proper date handling  
- 🧪 **Well Tested**: Comprehensive test suite with code coverage
- 📊 **Quality Assured**: Static analysis with PHPStan at maximum level
- 🔄 **Method Chaining**: Fluent interface for all setter methods

## Installation

Install via Composer:

```bash
composer require tomaj/bank-mails-parser
```

## Requirements

- PHP 8.0 or higher

## Quick Start

### Standard Tatrabanka Emails

```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent !== null) {
    echo "Amount: " . $mailContent->getAmount() . " " . $mailContent->getCurrency() . "\n";
    echo "Variable Symbol: " . $mailContent->getVs() . "\n";
    echo "Account: " . $mailContent->getAccountNumber() . "\n";
    echo "Date: " . $mailContent->getTransactionDate()?->format('Y-m-d H:i:s') . "\n";
    echo "Message: " . $mailContent->getReceiverMessage() . "\n";
}
```

### ComfortPay Emails

```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;

$parser = new TatraBankaSimpleMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent !== null) {
    echo "VS: " . $mailContent->getVs() . "\n";
    echo "Result: " . $mailContent->getRes() . "\n";
    echo "CID: " . $mailContent->getCid() . "\n";
    echo "Sign: " . $mailContent->getSign() . "\n";
}
```

### Method Chaining

All setter methods support fluent interface:

```php
$mailContent = new MailContent();
$mailContent
    ->setAmount(100.50)
    ->setCurrency('EUR')
    ->setVs('1234567890')
    ->setTransactionDate(new DateTime());
```

## Available Data Fields

The `MailContent` object provides access to all parsed transaction data:

| Method | Description |
|--------|-------------|
| `getAmount()` | Transaction amount |
| `getCurrency()` | Currency code (EUR, etc.) |
| `getVs()` | Variable symbol |
| `getSs()` | Specific symbol |  
| `getKs()` | Constant symbol |
| `getTransactionDate()` | Transaction date as DateTimeInterface |
| `getAccountNumber()` | Destination account number |
| `getSourceAccountNumber()` | Source account number |
| `getReceiverMessage()` | Message for recipient |
| `getDescription()` | Transaction description |

**ComfortPay specific fields:**
| Method | Description |
|--------|-------------|
| `getCid()` | ComfortPay ID |
| `getSign()` | Security signature |
| `getRes()` | Result code |
| `getTxn()` | Transaction ID |
| `getRc()` | Return code |

## Integration Example

Example integration with [IMAP Email Downloader](https://github.com/tomaj/imap-email-downloader):

```php
use Tomaj\ImapMailDownloader\Downloader;
use Tomaj\ImapMailDownloader\MailCriteria;
use Tomaj\ImapMailDownloader\Email;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$downloader = new Downloader('imap.server.com', 993, 'username', 'password');
$parser = new TatraBankaMailParser();

$criteria = new MailCriteria();
$criteria->setFrom('notification@tatrabanka.sk');

$downloader->fetch($criteria, function(Email $email) use ($parser) {
    $mailContent = $parser->parse($email->getBody());
    
    if ($mailContent !== null) {
        // Process the transaction data
        processTransaction($mailContent);
    }
    
    return true;
});
```

## Development

### Development Tools

This package uses modern PHP development practices:

- **PHPStan**: Static analysis at maximum level
- **PHP CodeSniffer**: PSR-2 code style enforcement  
- **PHPUnit 10**: Comprehensive testing framework
- **GitHub Actions**: Automated CI/CD pipeline

### Commands

```bash
# Install dependencies
composer install

# Run code style check
make sniff

# Fix code style issues  
make fix

# Run static analysis
make phpstan

# Run tests
make test

# Generate coverage report
make test-coverage

# Run all quality checks
make ci
```

### Code Coverage

Detailed code coverage reports are automatically generated and published for every commit:

**📊 [View Code Coverage Report](https://tomaj.github.io/bank-mails-parser/coverage/)**

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history and migration guides.

## Upgrading

### From 3.x to 4.x

**Breaking Changes:**
- Minimum PHP version: 8.0+
- `getTransactionDate()` returns `?DateTimeInterface` (was timestamp)
- `setTransactionDate()` accepts `?DateTimeInterface` (was timestamp)  
- All setters now return `self` for method chaining

**Migration:**
```php
// Before (3.x)
$timestamp = $mailContent->getTransactionDate();
$date = new DateTime('@' . $timestamp);

// After (4.x)  
$date = $mailContent->getTransactionDate();
```

## Contributing

We welcome contributions! Please feel free to submit pull requests for additional bank parsers or improvements.

## License

This project is licensed under the LGPL-2.0-or-later license.
