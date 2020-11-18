BANK MAILs Parser
====================

Library for processing bank confirmation emails.
Right now only support of *Tatrabanka* two emails formats.


[![Build Status](https://secure.travis-ci.org/tomaj/bank-mails-parser.png)](http://travis-ci.org/tomaj/bank-mails-parser)
[![Test Coverage](https://codeclimate.com/github/tomaj/bank-mails-parser/badges/coverage.svg)](https://codeclimate.com/github/tomaj/bank-mails-parser/coverage)
[![Code Climate](https://codeclimate.com/github/tomaj/bank-mails-parser/badges/gpa.svg)](https://codeclimate.com/github/tomaj/bank-mails-parser)
[![Dependency Status](https://www.versioneye.com/user/projects/54cf3fe4de7924b7ed000621/badge.svg?style=flat)](https://www.versioneye.com/user/projects/54cf3fe4de7924b7ed000621)

[![Latest Stable Version](https://poser.pugx.org/tomaj/bank-mails-parser/v/stable.svg)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![Latest Unstable Version](https://poser.pugx.org/tomaj/bank-mails-parser/v/unstable.svg)](https://packagist.org/packages/tomaj/bank-mails-parser)
[![License](https://poser.pugx.org/tomaj/bank-mails-parser/license.svg)](https://packagist.org/packages/tomaj/bank-mails-parser)

Instalation
-----------

Install package via composer:

``` bash
$ composer require tomaj/bank-mails-parser
```

Usage
-----

Basic usage in php:

``` php
use Tomaj\BankMailsParser\Parser\TatraBankaMailParser;

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
use Tomaj\BankMailsParser\Parser\TatraBankaMailParser;

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

*Note*: You have to include package *imap-email-downloader*: ```composer require tomaj/bank-mails-parser```


TODO
----

Add parses for other banks confirmation emails.
Feel free to fork and create pull requests with other banks parsers.
