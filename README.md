BANK MAILs Parser
====================

Library for processing bank confirmation emails.
Right now only support of *Tatrabanka* two emails formats.




Instalation
-----------

Install package via composer:

```
$ composer require tomaj/bank-mails-parser
```

Usage
-----

Basic usage in php:

```
use Tomaj\BankMailsParser\Parser\TatraBankaMailParser;

$tatraBankaMailParser = new TatraBankaMailParser();
$mailContent = $tatraBankaMailParser->parse('mail content');

echo $mailContent->getKs() . "\n";
echo $mailContent->getSs() . "\n";
echo $mailContent->getVs() . "\n";
echo $mailContent->getReceiverMessage() . "\n";
echo $mailContent->getCurrency() . "\n";
echo $mailContent->getTransactionDate() . "\n";
echo $mailContent->getAccountNumber() . "\n";
echo $mailContent->getAmount() . "\n";
echo $mailContent->getAccountNumber() . "\n";

```

Usage with imap mail downlaoder
-------------------------------

Example how to use with [imap email download](https://github.com/tomaj/imap-mail-downloader):

```
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


TODO
----

Add parses for other banks confirmation emails.
Feel free to fork and create pull requests with other banks parsers.
