# Bank Mails Parser

A professional PHP library for processing bank confirmation emails from Slovak and Czech banks. Extract transaction details, account numbers, amounts, and symbols automatically from bank notification emails.

## 🏦 Supported Banks

| Bank | Country | Parsers | Email Formats | Features |
|------|---------|---------|---------------|----------|
| TatraBanka | 🇸🇰 Slovakia | 3 parsers | Plain text, ComfortPay, PGP encrypted | Multi-format support, PGP decryption |
| ČSOB CZ | 🇨🇿 Czech Republic | 1 parser | Multi-transaction HTML | Batch processing |
| ČSOB SK | 🇸🇰 Slovakia | 1 parser | Multi-transaction HTML | Batch processing |
| VUB | 🇸🇰 Slovakia | 1 parser | Plain text | Simple format |

## ✨ Features

- 📧 **Multiple email formats**: Plain text, HTML, PGP encrypted
- 💰 **Financial data extraction**: Amounts, currencies, account numbers
- 🔢 **Banking symbols**: Variable Symbol (VS), Constant Symbol (KS), Specific Symbol (SS)
- 🔐 **PGP decryption**: Handle encrypted bank statements
- 🔄 **Multi-transaction support**: Process emails with multiple payments
- 🛡️ **Type safety**: Full PHP 8.2+ strict typing
- ✅ **Well tested**: 95%+ code coverage with comprehensive test suite


[![CI](https://github.com/tomaj/bank-mails-parser/actions/workflows/ci.yml/badge.svg)](https://github.com/tomaj/bank-mails-parser/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/badge/coverage-95.53%25-brightgreen.svg)](https://tomaj.github.io/bank-mails-parser/)
[![PHP Version Require](http://poser.pugx.org/tomaj/bank-mails-parser/require/php)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![Latest Stable Version](https://poser.pugx.org/tomaj/bank-mails-parser/v/stable.svg)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![License](https://poser.pugx.org/tomaj/bank-mails-parser/license.svg)](https://packagist.org/packages/tomaj/bank-mails-parser)

## 📋 Requirements

- **PHP 8.2+** (PHP 8.3, 8.4 recommended)
- **Composer** for installation

## ⚡ Quick Start

### Installation

```bash
composer require tomaj/bank-mails-parser
```

### Basic Usage

```php
<?php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

// Initialize parser
$parser = new TatraBankaMailParser();

// Parse bank email content
$mailContent = $parser->parse($emailBodyContent);

// Extract transaction details
if ($mailContent) {
    echo "Amount: " . $mailContent->getAmount() . " " . $mailContent->getCurrency() . "\n";
    echo "Variable Symbol: " . $mailContent->getVs() . "\n";
    echo "Account: " . $mailContent->getAccountNumber() . "\n";
}
```

## 📊 Compatibility Matrix

| PHP Version | Library Version | Status |
|-------------|----------------|---------|
| 8.4 | 4.0+ | ✅ Fully supported |
| 8.3 | 4.0+ | ✅ Fully supported |  
| 8.2 | 4.0+ | ✅ Fully supported |
| 8.1 | 3.0 only | ⚠️ Legacy support |
| 7.4 | 3.0 only | ⚠️ Legacy support |

## 📧 Email Format Examples

### TatraBanka Plain Text Format
```
Váš zostatok po transakcii je 1234.56 EUR
Suma: 50.00 EUR
VS: 1234567890
KS: 0308
SS: 123
```

### ČSOB Multi-transaction HTML
```html
<tr>
  <td>15.12.2023</td>
  <td>+1,234.50 CZK</td>
  <td>CZ1234567890</td>
  <td>VS: 987654321</td>
</tr>
```

### VUB Plain Text Format
```
Dtum: 11.12.2019
Suma: 34,90
Z tu: SK4502000000001123100000
VS: 9911929700
KS: 0308
```

## 📖 Detailed Usage

### TatraBanka parsers

Basic usage with TatraBanka parser:

``` php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$tatraBankaMailParser = new TatraBankaMailParser();
$mailContent = $tatraBankaMailParser->parse('mail content');

echo $mailContent->getKs() . "\n";
echo $mailContent->getSs() . "\n";
echo $mailContent->getVs() . "\n";
echo $mailContent->getReceiverMessage() . "\n";
echo $mailContent->getDescription() . "\n";
echo $mailContent->getCurrency() . "\n";
echo $mailContent->getTransactionDate() . "\n";
echo $mailContent->getAccountNumber() . "\n";
echo $mailContent->getAmount() . "\n";
echo $mailContent->getAccountNumber() . "\n";
echo $mailContent->getTxn() . "\n";
```

With *TatraBankaSimpleMailParser* you can parse comforpay emails. There are other getters like CID for reccurent payments.

``` php
echo $mailContent->getCid() . "\n";
echo $mailContent->getSign() . "\n";
echo $mailContent->getRes() . "\n";
```

With *TatraBankaStatementMailParser* you can parse encrypted PGP emails containing payment statements:

``` php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;

$decryptor = new TatraBankaMailDecryptor('/path/to/private-key.asc', 'passphrase');
$parser = new TatraBankaStatementMailParser($decryptor);
$mailContents = $parser->parseMulti('encrypted mail content');

foreach ($mailContents as $mailContent) {
    echo $mailContent->getVs() . "\n";
    echo $mailContent->getAmount() . "\n";
    echo $mailContent->getCurrency() . "\n";
}
```

### ČSOB parsers

For Czech ČSOB emails:

``` php
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

$csobMailParser = new CsobMailParser();
$mailContents = $csobMailParser->parseMulti('mail content');

foreach ($mailContents as $mailContent) {
    echo $mailContent->getVs() . "\n";
    echo $mailContent->getKs() . "\n";
    echo $mailContent->getAmount() . "\n";
    echo $mailContent->getCurrency() . "\n";
    echo $mailContent->getAccountNumber() . "\n";
    echo $mailContent->getSourceAccountNumber() . "\n";
}
```

For Slovak ČSOB emails:

``` php
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;

$skCsobMailParser = new SkCsobMailParser();
$mailContents = $skCsobMailParser->parseMulti('mail content');

foreach ($mailContents as $mailContent) {
    echo $mailContent->getVs() . "\n";
    echo $mailContent->getKs() . "\n";
    echo $mailContent->getAmount() . "\n";
    echo $mailContent->getCurrency() . "\n";
    echo $mailContent->getAccountNumber() . "\n";
}
```

### VUB parser

For VUB bank emails:

``` php
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

$vubMailParser = new VubMailParser();
$mailContent = $vubMailParser->parse('mail content');

echo $mailContent->getVs() . "\n";
echo $mailContent->getKs() . "\n";
echo $mailContent->getAmount() . "\n";
echo $mailContent->getAccountNumber() . "\n";
echo $mailContent->getTransactionDate() . "\n";
```

## 🔍 MailContent API

The `MailContent` object provides access to all extracted transaction data:

### Core Methods
```php
// Financial data
$mailContent->getAmount(): ?float           // Transaction amount
$mailContent->getCurrency(): ?string        // Currency code (EUR, CZK, etc.)
$mailContent->getTransactionDate(): ?int    // Unix timestamp of transaction

// Account information  
$mailContent->getAccountNumber(): ?string        // Destination account
$mailContent->getSourceAccountNumber(): ?string  // Source account (if available)

// Banking symbols
$mailContent->getVs(): ?string              // Variable Symbol
$mailContent->getKs(): ?string              // Constant Symbol  
$mailContent->getSs(): ?string              // Specific Symbol

// Additional data
$mailContent->getReceiverMessage(): ?string // Payment message
$mailContent->getDescription(): ?string     // Transaction description
$mailContent->getTxn(): ?string            // Transaction ID
```

### TatraBanka-specific Methods
```php
$mailContent->getCid(): ?string             // ComfortPay Client ID
$mailContent->getSign(): ?string            // HMAC signature
$mailContent->getRes(): ?string             // Result code
$mailContent->getRc(): ?string              // Return code
```

## ⚠️ Error Handling

### Parser Return Values
```php
$parser = new TatraBankaMailParser();
$result = $parser->parse($emailContent);

if ($result === null) {
    // Email format not recognized or parsing failed
    echo "Unable to parse email content";
} else {
    // Successfully parsed - $result is MailContent object
    echo "Amount: " . ($result->getAmount() ?? 'N/A');
}
```

### Common Error Scenarios
- **Unknown email format**: Parser returns `null`
- **Partial data**: Some MailContent getters may return `null` 
- **Invalid amounts**: Non-numeric values are handled gracefully
- **Date parsing failures**: Invalid dates result in `null` timestamp

### Validation Example
```php
function validateTransaction(MailContent $content): bool {
    return $content->getAmount() !== null 
        && $content->getAmount() > 0
        && $content->getVs() !== null
        && strlen($content->getVs()) > 0;
}
```

## 📚 Migration Guides

### Upgrade from 3.x to 4.x

**⚠️ Breaking Changes:**
- **PHP 8.2+ required** (dropped PHP 7.4, 8.0, 8.1 support)
- **PHPUnit 11** for development (if extending library)

**✅ Non-breaking Changes:**
- All existing parser APIs remain unchanged
- Same method signatures and return types
- Improved test coverage and GitHub Pages reporting

```bash
# Update your composer.json
composer require tomaj/bank-mails-parser:^4.0
```

### Upgrade from 2.x to 3.x

**⚠️ Breaking Changes:**
- **PHP 7.4+ required** (dropped PHP 7.1, 7.2, 7.3)
- **ParserInterface changes**: Returns `?MailContent` instead of `false`
- **Strict types**: Added `declare(strict_types=1)` throughout codebase
- **Namespace changes**: TatraBanka parsers moved to `\TatraBanka` subfolder

**Migration Steps:**
```php
// Before (v2.x)
if ($parser->parse($content) === false) {
    // Handle parsing failure
}

// After (v3.x)  
if ($parser->parse($content) === null) {
    // Handle parsing failure
}
```

### Upgrade from 1.x to 2.x

**⚠️ Breaking Changes:**
- Parser now returns `MailContent` for both successful and failed bank responses
- In v1.x, parser returned `MailContent` only for successful transactions

**💡 See full changelog**: [CHANGELOG.md](CHANGELOG.md)


Usage with imap mail downlaoder
-------------------------------

Example how to use with [imap mail downloader](https://github.com/tomaj/bank-mails-parser):

``` php
use Tomaj\ImapMailDownloader\Downloader;
use Tomaj\ImapMailDownloader\MailCriteria;
use Tomaj\ImapMailDownloader\Email;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$downloader = new Downloader('*imap host*', *port*, '*username*', '*password*');

$criteria = new MailCriteria();
$criteria->setFrom('some@email.com');
$downloader->fetch($criteria, function(Email $email) {
    $tatraBankaMailParser = new TatraBankaMailParser();
	$mailContent = $tatraBankaMailParser->parse($email->getBody());
	
	// process $mailContent data...
	
	return true;
});
```

*Note*: You have to include package *imap-email-downloader*: ```composer require tomaj/imap-email-downloader```

## 🔒 Security Considerations

### Production Recommendations
- Store PGP keys outside web root
- Use environment variables for sensitive configuration
- Log parsing failures for security monitoring
- Validate all extracted amounts before processing payments
- Never expose raw email content in error messages

## 🤝 Contributing

We welcome contributions! Here's how you can help:

### Adding New Bank Parsers

1. **Fork the repository** and create feature branch
2. **Study existing parsers** in `src/Parser/` for patterns
3. **Create parser class** implementing `ParserInterface`:
   ```php
   namespace Tomaj\BankMailsParser\Parser\YourBank;
   
   class YourBankMailParser implements ParserInterface 
   {
       public function parse(string $content): ?MailContent { /* ... */ }
   }
   ```
4. **Add comprehensive tests** in `tests/Parser/YourBank/`
5. **Update documentation** - README, CHANGELOG
6. **Submit Pull Request** with example email formats

### Development Setup
```bash
git clone https://github.com/tomaj/bank-mails-parser.git
cd bank-mails-parser
composer install
./vendor/bin/phpunit        # Run tests
./vendor/bin/phpstan analyse # Static analysis
```

### Coding Standards
- PHP 8.2+ with strict types
- PSR-12 coding standard
- 100% test coverage for new parsers
- Comprehensive PHPDoc comments

### Bank Parser Requirements
- Must handle both successful and failed transactions
- Support for multi-transaction emails (if applicable)
- Robust regex patterns with proper escaping
- Currency and amount parsing with locale support

**💡 Need help?** Open an issue or check existing parser implementations for guidance!
