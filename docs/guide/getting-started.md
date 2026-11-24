# Getting Started

Bank Mails Parser reads the payment notification emails of Slovak and Czech banks (Tatra banka, VÚB, ČSOB Slovensko and ČSOB Česká republika) and returns the transaction as a typed, immutable `MailContent` object: the amount as `Money` in minor units, the date, the accounts, the variable, specific and constant symbols, and the payer's message. Your application finds the order and books the payment.

## Requirements

- PHP 8.4 or newer (tested on 8.4, 8.5 and 8.6)
- `ext-mbstring`
- Composer

## Installation

```bash
composer require tomaj/bank-mails-parser
```

`singpolyma/openpgp-php` comes with it; it decrypts Tatra banka statements in pure PHP.

## Your first mail

A Tatra banka b-mail notification, as the decoded plain-text body:

```text
Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS/KS
Informacia pre prijemcu: test-sprava
```

Give it to `BankMailsParser` with the sender address. The facade finds the bank on its own:

```php
<?php

use Tomaj\BankMailsParser\BankMailsParser;

require 'vendor/autoload.php';

$parser = new BankMailsParser();
$mailContent = $parser->parse($emailBody, 'b-mail@tatrabanka.sk');

echo $mailContent->money?->format() . "\n";
echo $mailContent->vs . "\n";
echo $mailContent->accountNumber . "\n";
echo $mailContent->transactionDate?->format('Y-m-d H:i') . "\n";
```

<!-- snippet: output -->
```text
12.31 EUR
1234056789
SK9812353347235
2015-01-16 12:51
```

- `parse()` returns one transaction or throws: `UnsupportedBankException` when no bank recognises the mail, `InvalidEmailFormatException` when a bank recognises it but it cannot be read, `MultipleTransactionsException` when it holds several transactions.
- `tryParse()` returns `null` instead of throwing a parse error.
- `parseAll()` returns a list with every transaction. Use it when you process a mailbox: ČSOB mails and statements can contain more than one.

See [Bank Detection](/guide/bank-detection) and [Exceptions](/guide/exceptions).

## Supported banks

| Bank | Mail | Parser |
|------|------|--------|
| Tatra banka (SK) | b-mail notification of an account movement | `TatraBankaMailParser` |
| Tatra banka (SK) | CardPay / ComfortPay card payment result | `TatraBankaSimpleMailParser` |
| Tatra banka (SK) | PGP-encrypted card statement | `TatraBankaStatementMailParser` |
| ČSOB (CZ) | transaction notification, one or more per mail | `CsobMailParser` |
| ČSOB (SK) | SEPA transaction notification, one or more per mail | `SkCsobMailParser` |
| VÚB (SK) | payment notification | `VubMailParser` |

Pass the decoded plain text of the mail. ČSOB bodies with `<br>`, `<p>` or `<div>` line breaks are normalised; other HTML is not parsed.

## Before you book a payment

A parsed mail is not yet a verified payment. Check that the mail really comes from the bank (DKIM), verify CardPay signatures, and match the order by the variable symbol **and** the amount. [Security](/security) explains why.

## Next Steps

- [Examples](/examples/): runnable recipes for every bank, a whole mailbox and error handling
- [Configuration reference](/guide/configuration): every constructor and method argument
- Bank guides: [TatraBanka](/guide/tatrabanka), [ČSOB CZ](/guide/csob-cz), [ČSOB SK](/guide/csob-sk), [VÚB](/guide/vub)
- [API Reference](/api/reference) and [FAQ](/faq)
- [Add a new bank](/guide/adding-bank)
