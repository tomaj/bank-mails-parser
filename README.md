BANK MAILs Parser
====================

Library for processing bank confirmation emails.
Right now only support of *Tatrabanka* two emails formats. 

If we will add more emails from other banks there will be need to do some refactoring (for example MailContent is strongly connected to TatraBanka)

[![CI](https://github.com/tomaj/bank-mails-parser/workflows/CI/badge.svg)](https://github.com/tomaj/bank-mails-parser/actions)
[![Latest Stable Version](https://poser.pugx.org/tomaj/bank-mails-parser/v/stable.svg)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![Latest Unstable Version](https://poser.pugx.org/tomaj/bank-mails-parser/v/unstable.svg)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![License](https://poser.pugx.org/tomaj/bank-mails-parser/license.svg)](https://packagist.org/packages/tomaj/bank-mails-parser)

Requirements
------------

- PHP 8.0 or higher
- Strong typing support
- DateTimeInterface support for dates

Installation
------------

Install package via composer:

``` bash
$ composer require tomaj/bank-mails-parser
```

Usage
-----

Basic usage in php:

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
echo $mailContent->getTransactionDate()?->format('Y-m-d H:i:s') . "\n";
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

Development
-----------

This package uses modern PHP development tools:

- **PHP 8.0+** with strict types
- **PHPStan** for static analysis (level max)
- **PHP CodeSniffer** for PSR-2 code style
- **PHPUnit 10** for testing
- **GitHub Actions** for CI/CD

### Development Commands

``` bash
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

# Run tests with coverage
make test-coverage

# Run all checks (CI pipeline)
make ci
```

### Code Coverage

Code coverage reports are automatically generated and published to GitHub Pages for each commit to the main branch. You can view the coverage report at: `https://tomaj.github.io/bank-mails-parser/coverage/`

Upgrade from 3.* to 4.*
-----------------------

For using version 4 you will need at least PHP 8.0.
Breaking changes:
1. Minimum PHP version is now 8.0
2. `getTransactionDate()` now returns `?DateTimeInterface` instead of timestamp
3. `setTransactionDate()` now accepts `?DateTimeInterface` instead of timestamp
4. All setter methods now return `self` for method chaining
5. Constructor property promotion is used in `MailContent` class
6. Removed reflection usage for better performance and type safety

Upgrade from 2.* to 3.*
-----------------------

For using version 3 you will need at least php 7.1.
There were introduced multiple breaking changes:
1. `Tomaj\BankMailsParser\Parser\ParserInterface` will no return false anymore, only `?MailContent`
2. Introduced strict types for whole project `declare(strict_types=1);`
3. All Tatrabanka related code was moved under `TatraBanka` folder with proper namespace
4. Added strict types to all methods and params
5. Upgrade phpunit to version 9   

Upgrade from 1.* to 2.*
-----------------------

There is one breaking change in version 2.0 - parser returns MailContent always when email is parsed. In version 1.0 - parser returns MailContent only when response from bank was OK. In version 2 you can read also FAIL emails.

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

TODO
----

Add parses for other banks confirmation emails.
Feel free to fork and create pull requests with other banks parsers.
