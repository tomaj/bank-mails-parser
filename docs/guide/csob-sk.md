# ČSOB Slovakia

Parser for ČSOB Slovakia bank confirmation emails. Similar to ČSOB CZ but with Slovak language and slightly different format.

## Parser

### SkCsobMailParser

```php
<?php

use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;

$parser = new SkCsobMailParser();
```

## Methods

### parse(string $content): ?MailContent

Parses a single transaction from the email.

### parseMulti(string $content): array

Parses multiple transactions. Returns array of `MailContent` objects.

## Supported Fields

- **Amount** (`getAmount(): ?float`) - Transaction amount
- **Currency** (`getCurrency(): ?string`) - Currency code
- **Variable Symbol** (`getVs(): ?string`) - Variable symbol (VS)
- **Constant Symbol** (`getKs(): ?string`) - Constant symbol (KS)
- **Receiver Message** (`getReceiverMessage(): ?string`) - Payment message

## Email Format

ČSOB SK uses Slovak language with slightly different field names:

```
Vážený klient,

dňa 10.11.2023 bola na Vašom účte zaevidovaná platba.

suma:                      +1 000,00 EUR
účet príjemcu:             SK9999999999999999999999
banka:                     CEKOSKBX
detaily platby:
názov protiúčtu:           TEST USER GAMMA
referencia platiteľa:      /VS212049000/SS964/KS0308
informácia pre príjemcu:   Payment message
```

## Usage Example

```php
$parser = new SkCsobMailParser();
$mailContents = $parser->parseMulti($emailBody);

foreach ($mailContents as $mailContent) {
    echo $mailContent->getAmount();         // 1000.00
    echo $mailContent->getCurrency();       // "EUR"
    echo $mailContent->getVs();             // "212049000"
    echo $mailContent->getKs();             // "0308"
    echo $mailContent->getReceiverMessage(); // "Payment message"
}
```

## Multi-Transaction Support

Like ČSOB CZ, the parser splits emails by the "dňa" marker to handle multiple transactions:

```php
$emailWithMultipleTransactions = '
dňa 10.11.2023 bola na Vašom účte zaevidovaná platba.
suma: +100,00 EUR
...

dňa 11.11.2023 bola na Vašom účte zaevidovaná platba.
suma: +200,00 EUR
...
';

$parser = new SkCsobMailParser();
$mailContents = $parser->parseMulti($emailWithMultipleTransactions);

echo count($mailContents); // 2
```

## Amount Parsing

The parser handles Slovak number formatting:
- Decimal separator: comma (`,`)
- Supports whitespace in amounts
- Handles positive (`+`) and negative (`-`) prefixes

## Next Steps

- See more [Examples](/guide/examples)
- Check [ČSOB CZ](/guide/csob-cz) for Czech version
- Read the complete [API Reference](/api/reference)
