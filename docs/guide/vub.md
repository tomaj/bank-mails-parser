# VÚB

Parser for VÚB (Všeobecná úverová banka) Slovakia bank emails. Handles plain text notification format.

## Parser

### VubMailParser

```php
<?php

use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

$parser = new VubMailParser();
$mailContent = $parser->parse($emailBody);
```

## Methods

### parse(string $content): ?MailContent

Parses a single transaction. Returns `MailContent` or `null`.

## Supported Fields

- **Amount** (`getAmount(): ?float`) - Transaction amount
- **Account Number** (`getAccountNumber(): ?string`) - Destination account (IBAN)
- **Variable Symbol** (`getVs(): ?string`) - Variable symbol (VS)
- **Specific Symbol** (`getSs(): ?string`) - Specific symbol (ŠS)
- **Constant Symbol** (`getKs(): ?string`) - Constant symbol (KS)
- **Transaction Date** (`getTransactionDate(): int|false|null`) - Unix timestamp

## Email Format

VÚB sends plain text emails with Slovak field names:

```
Vážený klient,

v prílohe e-mailu Vám zasiela informácia o realizácii prevodu.

Dátum:   11.12.2019
Na účet: SK9999999999999999999999
Suma:    13,37
Z účtu:  SK1111111111111111111111
VS:      9999999999
ŠS:      910
KS:      0308
Stav:    zrealizovaný
SIGN:    5CB8A45E42ABC48539E672B9F8E1B3F8E62F97FABDBCF880D0913B5A0C8431CE
```

## Usage Example

```php
$emailBody = 'Dátum:   11.12.2019
Na účet: SK9999999999999999999999
Suma:    13,37
Z účtu:  SK1111111111111111111111
VS:      9999999999
ŠS:      910
KS:      0308
Stav:    zrealizovaný';

$parser = new VubMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent !== null) {
    echo $mailContent->getAmount();         // 13.37
    echo $mailContent->getAccountNumber();  // "SK9999999999999999999999"
    echo $mailContent->getVs();             // "9999999999"
    echo $mailContent->getSs();             // "910"
    echo $mailContent->getKs();             // "0308"
    
    $date = $mailContent->getTransactionDate();
    if ($date !== null && $date !== false) {
        echo date('Y-m-d', $date); // 2019-12-12
    }
}
```

## Field Extraction

The parser uses regex patterns to extract fields:

| Field | Pattern | Example |
|-------|---------|---------|
| Date | `Dátum:.*?(.*)` | `Dátum:   11.12.2019` |
| Account | `Na účet:.*?([A-Z0-9]+)` | `Na účet: SK99...` |
| Amount | `Suma:.*?([0-9,]+)` | `Suma:    13,37` |
| Source | `Z účtu:.*?([A-Z0-9]+)` | `Z účtu:  SK11...` |
| VS | `VS:.*?([0-9]+)` | `VS:      9999999999` |
| ŠS | `ŠS:.*?([0-9]+)` | `ŠS:      910` |
| KS | `KS:.*?([0-9]+)` | `KS:      0308` |

## Amount Format

VÚB uses comma (`,`) as decimal separator. The parser automatically converts:
- `13,37` → `13.37`
- `1 000,50` → `1000.50`

## IBAN Handling

Account numbers are extracted without whitespace trimming by default, preserving the exact format from the email.

## Error Handling

```php
$parser = new VubMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent === null) {
    // Required fields missing or invalid format
    error_log('Failed to parse VÚB email');
} else {
    processTransaction($mailContent);
}
```

## Next Steps

- See more [Examples](/guide/examples)
- Check other banks: [TatraBanka](/guide/tatrabanka), [ČSOB CZ](/guide/csob-cz), [ČSOB SK](/guide/csob-sk)
- Read the complete [API Reference](/api/reference)
