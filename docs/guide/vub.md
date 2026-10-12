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

### parse(string $content): MailContent

Parses one transaction. Throws `InvalidEmailFormatException` when none of the VÚB fields are present. `tryParse()` returns `null` instead.

## Supported Fields

- **Money** (`money: ?Money`) - Amount from `Suma:`. EUR when the line names no currency. A named code such as `CZK` is kept.
- **Account Number** (`accountNumber: ?string`) - Payer account from `Z účtu` / `Z tu`
- **Own account** (`ownAccountNumber: ?string`) - Value from `Na účet` / `Na et`
- **Variable Symbol** (`vs: ?string`) - The `VS:` line. When you pair a payment, also check the amount. A note in the email cannot change the amount or the account.
- **Specific Symbol** (`ss: ?string`) - Value from `S:` or `ŠS:` (not from `VS` or `KS`)
- **Constant Symbol** (`ks: ?string`)
- **Transaction Date** (`transactionDate: ?DateTimeImmutable`) - `Dátum` / `Dtum`, formats `j.n.Y`, `Y-m-d` and `j.n.Y G:i`, in `Europe/Bratislava`. A two-digit year is rejected and the date stays `null`.

## Email Format

Both the ASCII labels from older samples and the accented labels are read:

```
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

`supports()` requires `Dátum`, `Z účtu`, `Na účet`, `Suma`, `VS`, and `Stav` or `SIGN` together. A generic invoice that only has `Suma`, `VS` and `Na účet` is not a VÚB notification. The library does not check that the mail came from VÚB: verify the sender and DKIM or SPF before booking the payment.

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

echo $mailContent->money?->toDecimalString(); // "13.37"
echo $mailContent->money?->currency->value;   // "EUR"
echo $mailContent->accountNumber;             // "SK1111111111111111111111"
echo $mailContent->ownAccountNumber;          // "SK9999999999999999999999"
echo $mailContent->vs;                        // "9999999999"
echo $mailContent->ss;                        // "910"
echo $mailContent->ks;                        // "0308"
echo $mailContent->transactionDate?->format('Y-m-d'); // "2019-12-11"
```

## Field Extraction

| Property | Pattern |
|----------|---------|
| `transactionDate` | `Dátum:` or `Dtum:` |
| `accountNumber` | `Z účtu:` or `Z tu:` (payer) |
| `ownAccountNumber` | `Na účet:` or `Na et:` |
| `money` | `Suma:` (EUR when no currency is named) |
| `vs` | `VS:` |
| `ss` | `^(?:ŠS\|SS\|S):` with optional leading whitespace |
| `ks` | `KS:` |

## Amount Format

The amount is parsed by `Money::fromDecimalString()` with a comma decimal mark. `1 234,56`, NBSP, thin space, narrow NBSP and tab are thousands separators. `13,37` becomes `"13.37"`. `-13,37` is negative. An unreadable amount (`abc`, or `13.37` when the decimal mark is a comma) leaves `money` null. A blank `Suma:` leaves `money` null. `SIGN` is copied to `$sign`; verify it yourself.

## Error Handling

```php
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

$parser = new VubMailParser();

try {
    processTransaction($parser->parse($emailBody));
} catch (InvalidEmailFormatException $exception) {
    error_log($exception->getMessage());
}
```

## Next Steps

- See more [Examples](/guide/examples)
- Check other banks: [TatraBanka](/guide/tatrabanka), [ČSOB CZ](/guide/csob-cz), [ČSOB SK](/guide/csob-sk)
- Read the complete [API Reference](/api/reference)
