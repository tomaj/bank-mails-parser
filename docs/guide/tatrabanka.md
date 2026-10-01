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

- **Amount** (`getAmount(): ?float`) - Transaction amount (positive for incoming, negative for outgoing)
- **Currency** (`getCurrency(): ?string`) - Currency code (EUR, USD, etc.)
- **Account Number** (`getAccountNumber(): ?string`) - Destination account (IBAN)
- **Source Account** (`getSourceAccountNumber(): ?string`) - Source account number
- **Transaction Date** (`getTransactionDate(): int|false|null`) - Unix timestamp
- **Variable Symbol** (`getVs(): ?string`) - Variable symbol (VS)
- **Specific Symbol** (`getSs(): ?string`) - Specific symbol (ŠS)
- **Constant Symbol** (`getKs(): ?string`) - Constant symbol (KS)
- **Receiver Message** (`getReceiverMessage(): ?string`) - Message for recipient
- **Description** (`getDescription(): ?string`) - Transaction description

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

if ($mailContent !== null) {
    echo $mailContent->getAmount();              // 12.31
    echo $mailContent->getCurrency();            // "EUR"
    echo $mailContent->getAccountNumber();       // "SK9812353347235"
    echo $mailContent->getSourceAccountNumber(); // "1100/000000-261426464"
    echo $mailContent->getVs();                  // "1234056789"
    echo $mailContent->getSs();                  // "9087654321"
    echo $mailContent->getKs();                  // "5428175648"
    echo $mailContent->getReceiverMessage();     // "test-sprava"
}
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

- **Amount** (`getAmount(): ?float`) - Transaction amount
- **Currency** (`getCurrency(): ?string`) - Currency code (numeric, e.g., "978" for EUR)
- **Variable Symbol** (`getVs(): ?string`) - Variable symbol
- **Result** (`getRes(): ?string`) - Transaction result (OK, FAIL)
- **Authorization Code** (`getAc(): ?string`) - Authorization code
- **Client ID** (`getCid(): ?string`) - Client ID (ComfortPay)
- **Card Info** (`getCc(): ?string`) - Masked card number
- **Transaction ID** (`getTid(): ?string`) - Transaction ID
- **Transaction Type** (`getTxn(): ?string`) - Transaction type (PA, etc.)
- **Return Code** (`getRc(): ?string`) - Return code
- **Signature** (`getSign(): ?string`) - HMAC signature
- **Transaction Date** (`getTransactionDate(): int|false|null`) - Unix timestamp

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

if ($mailContent !== null) {
    echo $mailContent->getAmount();   // 44.88
    echo $mailContent->getCurrency(); // "978"
    echo $mailContent->getVs();       // "4444255333"
    echo $mailContent->getRes();      // "OK"
    echo $mailContent->getCid();      // "824452"
    echo $mailContent->getCc();       // "************1111"
}
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
// Parse multiple transactions from encrypted email
$mailContents = $parser->parseMulti($encryptedEmailBody);

foreach ($mailContents as $mailContent) {
    echo $mailContent->getAmount() . " " . $mailContent->getCurrency() . "\n";
    echo "VS: " . $mailContent->getVs() . "\n";
    echo "Account: " . $mailContent->getAccountNumber() . "\n";
    echo "Bank: " . $mailContent->getKs() . "\n";
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
public function decrypt(string $contents): ?string
```

Decrypts PGP-armored message and returns plain text content, or `null` on failure.

#### Error Handling

```php
try {
    $decryptor = new TatraBankaMailDecryptor(
        '/path/to/private-key.asc',
        'passphrase'
    );
    
    $decrypted = $decryptor->decrypt($encryptedContent);
    
    if ($decrypted === null) {
        throw new \RuntimeException('Decryption failed');
    }
    
} catch (\Exception $e) {
    // Handle errors:
    // - Missing or invalid private key file
    // - Incorrect passphrase
    // - Malformed PGP message
    error_log('Decryption error: ' . $e->getMessage());
}
```

## Error Handling

All TatraBanka parsers return `null` when the email format is not recognized:

```php
$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent === null) {
    // Email format not recognized or required fields missing
    error_log('Failed to parse TatraBanka email');
} else {
    // Successfully parsed
    processTransaction($mailContent);
}
```

## Date Format Support

`TatraBankaMailParser` supports multiple date formats automatically:

- `16.1.2015 12:51` (day.month.year hour:minute)
- `16. 1. 2015 12:51` (with spaces)

The parser tries multiple formats and falls back to `strtotime()` if none match.

## Next Steps

- See more [Examples](/guide/examples)
- Check other banks: [ČSOB CZ](/guide/csob-cz), [ČSOB SK](/guide/csob-sk), [VÚB](/guide/vub)
- Read the complete [API Reference](/api/reference)
