# ČSOB Czech Republic

Parser for ČSOB Czech Republic bank confirmation emails. Supports one transaction and several in the same mail. Pass the decoded plain text; HTML is not parsed.

## Parser

### CsobMailParser

```php
<?php

use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

$parser = new CsobMailParser();
```

## Methods

### parse(string $content): MailContent

Parses one transaction. Throws `InvalidEmailFormatException` when the text is not a ČSOB CZ payment. `tryParse()` returns `null` instead.

### parseAll(string $content): array

Parses every transaction in the mail. Throws `InvalidEmailFormatException` when the text is not a ČSOB CZ payment or a transaction cannot be read. It does not return an empty list.

## Supported Fields

- **Money** (`money: ?Money`) - Signed minor-unit amount and currency (`toDecimalString()` for a decimal)
- **Account Number** (`accountNumber: ?string`) - Destination account
- **Source Account** (`sourceAccountNumber: ?string`) - Account token from `Účet protistrany`. Trailing prose such as ` (Fio banka)` is dropped, and so is `<br>`.
- **Transaction Date** (`transactionDate: ?DateTimeImmutable`)
- **Variable Symbol** (`vs: ?string`) - Variable symbol
- **Constant Symbol** (`ks: ?string`) - Constant symbol
- **Receiver Message** (`receiverMessage: ?string`) - Payment message

## Email Format

ČSOB CZ notifications are Czech text. Convert HTML to text before parsing. The parser handles these transaction types:

- Došlá platba (Incoming payment)
- Příchozí úhrada (Incoming credit)
- SEPA převod (SEPA transfer)
- Zahraniční transakce (Foreign transaction)
- Hotovostní transakce (Cash transaction)
- Platební kartou (Card payment)

### Example Email

```
Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB.

Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.

Název smlouvy: Company Name
Číslo smlouvy: 87654321
Účet: 123456789, CZK
Částka: +1 234,56 CZK
Účet protistrany: 1122334455/9999
Název protistrany: Sender Name
Variabilní symbol: 23456789
Konstantní symbol: 3456

Zůstatek na účtu po zaúčtování transakce: +1 234 567,89 CZK.
```

## Usage Examples

### Single Transaction

```php
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

$parser = new CsobMailParser();

try {
    $mailContents = $parser->parseAll($emailBody);
} catch (InvalidEmailFormatException) {
    $mailContents = [];
}

foreach ($mailContents as $mailContent) {
    echo $mailContent->money?->toDecimalString(); // "1234.56"
    echo $mailContent->money?->currency->value;   // "CZK"
    echo $mailContent->accountNumber;       // "123456789"
    echo $mailContent->sourceAccountNumber; // "1122334455/9999"
    echo $mailContent->vs;                  // "23456789"
    echo $mailContent->ks;                  // "3456"
}
```

### Multiple Transactions

```php
$emailWithMultipleTransactions = '...'; // Email with multiple "Dne" markers

$parser = new CsobMailParser();
$mailContents = $parser->parseAll($emailWithMultipleTransactions);

foreach ($mailContents as $mailContent) {
    printf(
        "Transaction: %s %s, VS: %s, Account: %s\n",
        $mailContent->money?->toDecimalString(),
        $mailContent->money?->currency->value,
        $mailContent->vs,
        $mailContent->accountNumber
    );
}
```

## Variable Symbol Detection

The parser uses the 4.1.0 order:

1. **Variabilní symbol:** An all-zero value is skipped. More than 10 digits is `null`.
2. **Identifikace:** Replaces the value from step 1, as in 4.1.0.
3. A `VS` or `v.s.` token.
4. The last number on **Zpráva příjemci**, including a number without a `VS` prefix and a creditor reference.
5. **Účel platby:** the last 1–10 digit number on a word boundary before `Zůstatek`, including a wrapped purpose and a blank line inside that span. `AB12`, `INV2026`, and `FV2024001` are not symbols. Without `Zůstatek`, a number in the footer is not a symbol.

`Název protistrany`, `Adresa protistrany`, `Adresa plátce`, `Zpráva příjemci`, `Identifikace`, `Účel platby`, and `Reference plátce` are written by the payer. A blank line, or a line of spaces, tabs or NBSP, ends the field. An arbitrary `Word:` does not. The field also ends on the bank's own line: the full `Zůstatek na účtu po zaúčtování transakce: +1 234,56 CZK.` sentence, a digits-only symbol line, or an account line. Bare `Zůstatek` does not. A header or a signed amount inside that text is blanked. It does not reject the mail. `Částka: záloha 2 500 Kč` and `Dne 1.10.2026 byla na účtu chyba, vracím` stay the message. An account line is the counterparty, including when the name comes first. `sourceAccountNumber` keeps the account token: trailing prose such as ` (Fio banka)` is dropped, and so is `<br>`. Several transactions with no balance line still parse, the same as 4.1.0. That is a known limitation: without balance lines a forged transaction in payer text cannot be distinguished from a real one. Check the amount against the bank API or a statement. When the mail does include a balance, each transaction needs exactly one. A second balance line in the same transaction throws `InvalidEmailFormatException` with `Transaction has more than one balance line`. The previous balance plus the amount must equal the next balance. A lone CR, VT, FF, U+0085, U+2028 or U+2029 inside a mail that already has LF stays an ordinary character and does not split a line. A header does not need a blank line before it. When you pair a payment, also check the amount.

## Special Features

### Amount Handling

- Supports both positive (`+`) and negative (`-`) amounts
- Handles various number formats with spaces and commas
- Automatically parses normalized amounts

### Account Trimming

Source account numbers are automatically trimmed of whitespace.

### Multi-line Messages

Handles multi-line receiver messages and payment purposes.

## Error Handling

```php
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

$parser = new CsobMailParser();

try {
    foreach ($parser->parseAll($emailBody) as $mailContent) {
        processTransaction($mailContent);
    }
} catch (InvalidEmailFormatException $exception) {
    error_log($exception->getMessage());
}
```

## Next Steps

- See more [Examples](/guide/examples)
- Check [ČSOB SK](/guide/csob-sk) for Slovak version
- Read the complete [API Reference](/api/reference)
