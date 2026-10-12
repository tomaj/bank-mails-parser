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

### parse(string $content): MailContent

Parses one transaction. Throws `InvalidEmailFormatException` when the text is not a ČSOB SK payment. `tryParse()` returns `null` instead.

### parseAll(string $content): array

Parses every transaction. Throws `InvalidEmailFormatException` when the text is not a payment or a transaction cannot be read. It does not return an empty list.

## Supported Fields

- **Money** (`money: ?Money`) - Minor-unit amount and currency. Decimal text is `toDecimalString()`.
- **Variable Symbol** (`vs: ?string`) - `/VS`, `/SS`, and `/KS` on the `referencia platiteľa` line (any length), then the first `VS` or `KS` on the `informácia pre príjemcu` line only when the reference did not set it. A payer name such as `ADVS2` is not a symbol. `VS111 nahrada za VS222` is `111`. The recipient message cannot change the amount or the date. `referencia platiteľa` is payer text. A header inside that text is ignored, not booked. A mail with several transactions and no balance line parses like 4.1.0; without those lines a forged transaction after a blank line cannot be told from a real one, so check the amount against the bank. `Suma: 50 EUR za obed` stays the message. When you pair a payment, also check the amount.
- **Account** (`accountNumber: ?string`) - Payer account from `z účtu`.
- **Own account** (`ownAccountNumber: ?string`) - The account after `na účte`.
- **Constant Symbol** (`ks: ?string`) - Constant symbol (KS)
- **Receiver Message** (`receiverMessage: ?string`) - Payment message

## Email Format

ČSOB SK uses Slovak language with slightly different field names:

```
dňa 6.9.2019 bola na účte SK13 7500 0000 0040 1942 5381 PETIT PRESS, A.S. zaúčtovaná suma SEPA platobného príkazu:
suma:                    +1150,00 EUR
z účtu:                  SK91 7500 0000 0030 2072 9058
banka:                   CEKOSKBX
detaily platby:
názov protiúčtu:         NOVAK JOZEF
referencia platiteľa:    /VS212049000/SS964/KS0308
informácia pre príjemcu: Maroko 2019 rodicia
```

## Usage Example

```php
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;

$emailBody = 'dňa 6.9.2019 bola na účte SK13 7500 0000 0040 1942 5381 PETIT PRESS, A.S. zaúčtovaná suma SEPA platobného príkazu:
suma:                    +1150,00 EUR
z účtu:                  SK91 7500 0000 0030 2072 9058
referencia platiteľa:    /VS212049000/SS964/KS0308
informácia pre príjemcu: Maroko 2019 rodicia';

$parser = new SkCsobMailParser();

try {
    $mailContents = $parser->parseAll($emailBody);
} catch (InvalidEmailFormatException) {
    $mailContents = [];
}

foreach ($mailContents as $mailContent) {
    echo $mailContent->money?->toDecimalString(); // "1150.00"
    echo $mailContent->money?->currency->value;   // "EUR"
    echo $mailContent->accountNumber;             // "SK91 7500 0000 0030 2072 9058"
    echo $mailContent->ownAccountNumber;          // "SK13 7500 0000 0040 1942 5381"
    echo $mailContent->vs;                        // "212049000"
    echo $mailContent->ss;                        // "964"
    echo $mailContent->ks;                        // "0308"
    echo $mailContent->receiverMessage;           // "Maroko 2019 rodicia"
}
```

## Multi-Transaction Support

Like ČSOB CZ, the parser splits emails by the "dňa" marker to handle multiple transactions:

```php
$emailWithMultipleTransactions = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: +100,00 EUR

dňa 7.9.2019 bola na účte SK92 7500 0000 0030 2072 9059 zaúčtovaná suma SEPA platobného príkazu:
suma: +200,00 EUR
';

$parser = new SkCsobMailParser();
$mailContents = $parser->parseAll($emailWithMultipleTransactions);

echo count($mailContents); // 2
```

A chunk that is not a SEPA transaction is ignored. A transaction that matches and then has an unreadable amount is returned with `money` null. The other rows are still returned.

Several transactions with no balance line still parse, the same as 4.1.0. That is a known limitation: without `Zostatok na účte po zaúčtovaní sumy platobnej operácie` a forged transaction in payer text cannot be distinguished from a real one. Check the amount against the bank API or a statement. When the mail does include a balance, each transaction needs exactly one. A second balance line in the same transaction throws `InvalidEmailFormatException` with `Transaction has more than one balance line`. The previous balance plus the amount must equal the next balance. A lone CR, VT, FF, U+0085, U+2028 or U+2029 inside a mail that already has LF stays an ordinary character and does not split a line.

## Amount Parsing

The parser handles Slovak number formatting:
- Decimal separator: comma (`,`)
- Supports whitespace in amounts
- Handles positive (`+`) and negative (`-`) prefixes

## Next Steps

- See more [Examples](/guide/examples)
- Check [ČSOB CZ](/guide/csob-cz) for Czech version
- Read the complete [API Reference](/api/reference)
