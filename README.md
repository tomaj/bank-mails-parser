BANK MAILs Parser
====================

Library for processing bank confirmation emails.
Currently supports:
- **TatraBanka** - Two email formats (TatraBankaMailParser, TatraBankaSimpleMailParser, TatraBankaStatementMailParser)
- **ČSOB CZ** - Czech ČSOB emails (CsobMailParser)  
- **ČSOB SK** - Slovak ČSOB emails (SkCsobMailParser)


[![CI](https://github.com/tomaj/bank-mails-parser/actions/workflows/ci.yml/badge.svg)](https://github.com/tomaj/bank-mails-parser/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/badge/coverage-95.13%25-brightgreen.svg)](https://tomaj.github.io/bank-mails-parser/)
[![Latest Stable Version](https://poser.pugx.org/tomaj/bank-mails-parser/v/stable.svg)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![Latest Unstable Version](https://poser.pugx.org/tomaj/bank-mails-parser/v/unstable.svg)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![License](https://poser.pugx.org/tomaj/bank-mails-parser/license.svg)](https://packagist.org/packages/tomaj/bank-mails-parser)

Installation
------------

Install package via composer:

``` bash
$ composer require tomaj/bank-mails-parser
```

Usage
-----

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

Add parsers for other banks confirmation emails.
Feel free to fork and create pull requests with other banks parsers.

Available parsers:
- ✅ TatraBanka (Slovakia) - TatraBankaMailParser, TatraBankaSimpleMailParser, TatraBankaStatementMailParser
- ✅ ČSOB CZ (Czech Republic) - CsobMailParser
- ✅ ČSOB SK (Slovakia) - SkCsobMailParser
