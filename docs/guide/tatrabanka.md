# TatraBanka

TatraBanka is the most comprehensive bank parser in this library, supporting three different email formats: standard notifications, ComfortPay payments, and PGP-encrypted statements.

## Parsers Overview

| Parser | Email Type | Class |
|--------|-----------|-------|
| Standard Notifications | Plain text transaction emails | `TatraBankaMailParser` |
| ComfortPay Payments | Key=value format (CardPay) | `TatraBankaSimpleMailParser` |
| Encrypted Statements | PGP-encrypted multi-transaction | `TatraBankaStatementMailParser` |

## Standard Notifications

### TatraBankaMailParser

Parses standard transaction notification emails sent by TatraBanka.

```php
<?php

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);
```

### Supported Fields

- **Money** (`money: ?Money`) - Minor-unit `amount` plus `Currency`. Decimal text is `toDecimalString()`. Incoming amounts are positive. Three non-zero decimals, such as `12,315` EUR, throw `InvalidEmailFormatException`.
- **Header** - `bol zostatok Vasho uctu` must start the line. Leading whitespace is allowed. `Dobry den, 16.1.2015 12:51 bol zostatok…` does not parse.
- **Account Number** (`accountNumber: ?string`) - Destination account (IBAN)
- **Source Account** (`sourceAccountNumber: ?string`) - Source account number
- **Transaction Date** (`transactionDate: ?DateTimeImmutable`) - Parsed with `j.n.Y G:i` or `j. n. Y G:i`
- **Variable Symbol** (`vs: ?string`) - `/VS…/SS…/KS…`, then a `VS` token, then the last number in `Informacia pre prijemcu` when `Referencia platitela` is absent (the same as 4.1.0), then a bare `Referencia platitela`. A blank line before that reference does not drop it. The payer writes the message. When you pair a payment, also check the amount, and another identifier when you have one. The message cannot change the amount, account, or date.
- **Specific Symbol** (`ss: ?string`) - Specific symbol (ŠS)
- **Constant Symbol** (`ks: ?string`) - Constant symbol (KS)
- **Receiver Message** (`receiverMessage: ?string`) - Message for recipient
- **Description** (`description: ?string`) - Transaction description

### Email Format Example

```
Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS9087654321/KS5428175648
Informacia pre prijemcu: test-sprava

S pozdravom
TATRA BANKA, a.s.
```

### Parsing Example

```php
$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

echo $mailContent->money?->toDecimalString(); // "12.31"
echo $mailContent->money?->currency->value;   // "EUR"
echo $mailContent->accountNumber;             // "SK9812353347235"
echo $mailContent->sourceAccountNumber;       // "1100/000000-261426464"
echo $mailContent->vs;                        // "1234056789"
echo $mailContent->ss;                        // "9087654321"
echo $mailContent->ks;                        // "5428175648"
echo $mailContent->receiverMessage;           // "test-sprava"
```

## ComfortPay Payments

### TatraBankaSimpleMailParser

Parses ComfortPay and CardPay confirmation emails with key=value format.

```php
<?php

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;

$parser = new TatraBankaSimpleMailParser();
$mailContent = $parser->parse($emailBody);
```

### Supported Fields

- **Money** (`money: ?Money`) - Present when both `AMT` and `CURR` are set. Numeric `978` becomes `EUR`. Amount without currency leaves `money` null. The decimal mark is `.`.
- **Variable Symbol** (`vs: ?string`) - Variable symbol
- **Result** (`res: ?string`) - Transaction result (OK, FAIL)
- **Authorization Code** (`ac: ?string`) - Authorization code
- **Client ID** (`cid: ?string`) - Client ID (ComfortPay)
- **Card Info** (`cc: ?string`) - Masked card number
- **Transaction ID** (`tid: ?string`) - Transaction ID
- **Transaction code** (`txn: ?string`) - CardPay code such as `PA`
- **Return Code** (`rc: ?string`) - Return code
- **Signature** (`sign: ?string`) - HMAC or SIGN value, stored and not verified. Check the signature and DKIM before booking the payment. When `TRES` is present, `res` is that value and `tres` stores it too. A duplicate key with a different value throws `InvalidEmailFormatException`.
- **Transaction Date** (`transactionDate: ?DateTimeImmutable`) - 14-digit `TIMESTAMP` is `dmYHis`; 10 digits are a Unix timestamp

### Email Format Example

```
VS=1152201233 RES=OK AC=558058 SIGN=C0CBF27F5D97841E
```

```
AMT=44.88 CURR=978 VS=4444255333 RES=OK AC=644311 TRES=OK 
CID=824452 CC=************1111 TID=11224444 
TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1
```

### Parsing Example

```php
$emailBody = 'AMT=44.88 CURR=978 VS=4444255333 RES=OK AC=644311 ' .
             'TRES=OK CID=824452 CC=************1111 TID=11224444 ' .
             'TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1';

$parser = new TatraBankaSimpleMailParser();
$mailContent = $parser->parse($emailBody);

echo $mailContent->money?->toDecimalString(); // "44.88"
echo $mailContent->money?->currency->value;   // "EUR"
echo $mailContent->vs;                        // "4444255333"
echo $mailContent->res;                       // "OK"
echo $mailContent->cid;                       // "824452"
echo $mailContent->cc;                        // "************1111"
```

## PGP-Encrypted Statements

### TatraBankaStatementMailParser

Parses PGP-encrypted bank statement emails containing multiple transactions. Requires the `TatraBankaMailDecryptor` to decrypt the email first.

### Setup

First, you need to configure the PGP decryptor with your private key:

```php
<?php

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;

// Initialize decryptor
$decryptor = new TatraBankaMailDecryptor(
    '/path/to/your/private-key.asc',  // Path to PGP private key
    'your-passphrase'                  // Passphrase for the key
);

// Create parser with decryptor
$parser = new TatraBankaStatementMailParser($decryptor);
```

### Parsing Encrypted Statements

```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;

$decryptor = new TatraBankaMailDecryptor(
    '/path/to/your/private-key.asc',
    'your-passphrase'
);
$parser = new TatraBankaStatementMailParser($decryptor);

$mailContents = $parser->parseMulti($encryptedEmailBody);

foreach ($mailContents as $mailContent) {
    echo $mailContent->money?->toDecimalString() . ' ' . $mailContent->money?->currency->value . "\n";
    echo "VS: " . $mailContent->vs . "\n";
    echo "Account: " . $mailContent->accountNumber . "\n";
    echo "KS: " . $mailContent->ks . "\n";
}
```

### TatraBankaMailDecryptor

The decryptor handles OpenPGP decryption of TatraBanka statement emails.

#### Constructor

```php
public function __construct(
    string $privateKeyPath,  // Path to PGP private key file (.asc)
    string $passphrase       // Passphrase to unlock the private key
)
```

#### Method

```php
public function decrypt(string $contents): string
```

Decrypts a PGP-armored message and returns the plain text. A missing key path, an unreadable key, or a wrong passphrase throws `ConfigurationException` (not a `ParseException`). `tryParse()` rethrows that. A message that cannot be decrypted throws `DecryptionException`, which the statement parser wraps as `InvalidEmailFormatException`.

#### Error Handling

```php
try {
    $decryptor = new TatraBankaMailDecryptor(
        '/path/to/private-key.asc',
        'passphrase'
    );
    
    $decrypted = $decryptor->decrypt($encryptedEmailBody);
    echo $decrypted;
} catch (\Exception $e) {
    // Handle errors:
    // - Missing or invalid private key file
    // - Incorrect passphrase
    // - Malformed PGP message
    error_log('Decryption error: ' . $e->getMessage());
}
```

## Error Handling

`parse()` throws `InvalidEmailFormatException`. `tryParse()` returns `null`.

```php
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();

try {
    processTransaction($parser->parse($emailBody));
} catch (InvalidEmailFormatException $exception) {
    error_log($exception->getMessage());
}
```

## Date Format Support

`TatraBankaMailParser` supports multiple date formats automatically:

- `16.1.2015 12:51` and `16. 1. 2015 12:51`
- `16.1.2015 12:51:30` (with seconds)
- `16.1.2015` and `16. 1. 2015` (date only)
- `2015-01-16 12:51` and `2015-01-16` (`Y-m-d`, with or without a time)

These are the shapes 4.1.0 accepted, including the old `strtotime()` fallback. 5.0 parses them explicitly in `Europe/Bratislava` and does not call `strtotime()`. A two-digit year (`16.1.15`) is rejected and `transactionDate` stays `null`. Invalid dates are not rolled forward. A wall time that does not exist after the spring-forward change (`29.3.2015 2:30`) is rejected. The autumn overlap (`25.10.2015 2:30`) is kept.

## Next Steps

- See more [Examples](/guide/examples)
- Check other banks: [ČSOB CZ](/guide/csob-cz), [ČSOB SK](/guide/csob-sk), [VÚB](/guide/vub)
- Read the complete [API Reference](/api/reference)
