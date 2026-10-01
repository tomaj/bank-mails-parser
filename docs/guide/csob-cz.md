# ČSOB Czech Republic

Parser for ČSOB Czech Republic bank confirmation emails. Supports both single and multi-transaction HTML-formatted emails.

## Parser

### CsobMailParser

```php
<?php

use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

$parser = new CsobMailParser();
```

## Methods

### parse(string $content): ?MailContent

Parses a single transaction from the email.

### parseMulti(string $content): array

Parses multiple transactions from a single email. Returns array of `MailContent` objects.

## Supported Fields

- **Amount** (`getAmount(): ?float`) - Transaction amount (positive/negative)
- **Currency** (`getCurrency(): ?string`) - Currency code (CZK, EUR, etc.)
- **Account Number** (`getAccountNumber(): ?string`) - Destination account
- **Source Account** (`getSourceAccountNumber(): ?string`) - Source account/IBAN
- **Transaction Date** (`getTransactionDate(): int|false|null`) - Unix timestamp
- **Variable Symbol** (`getVs(): ?string`) - Variable symbol
- **Constant Symbol** (`getKs(): ?string`) - Constant symbol
- **Receiver Message** (`getReceiverMessage(): ?string`) - Payment message

## Email Format

ČSOB CZ sends HTML-formatted emails with Czech text. The parser handles various transaction types:

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
$parser = new CsobMailParser();
$mailContents = $parser->parseMulti($emailBody);

if (!empty($mailContents)) {
    $mailContent = $mailContents[0];
    
    echo $mailContent->getAmount();              // 1234.56
    echo $mailContent->getCurrency();            // "CZK"
    echo $mailContent->getAccountNumber();       // "123456789"
    echo $mailContent->getSourceAccountNumber(); // "1122334455/9999"
    echo $mailContent->getVs();                  // "23456789"
    echo $mailContent->getKs();                  // "3456"
}
```

### Multiple Transactions

```php
$emailWithMultipleTransactions = '...'; // Email with multiple "Dne" markers

$parser = new CsobMailParser();
$mailContents = $parser->parseMulti($emailWithMultipleTransactions);

foreach ($mailContents as $mailContent) {
    printf(
        "Transaction: %s %s, VS: %s, Account: %s\n",
        $mailContent->getAmount(),
        $mailContent->getCurrency(),
        $mailContent->getVs(),
        $mailContent->getAccountNumber()
    );
}
```

## Variable Symbol Detection

The parser tries multiple patterns to find the variable symbol:

1. **Variabilní symbol:** field (primary)
2. **Identifikace:** field (SEPA transfers)
3. **Zpráva příjemci:** with `vs` or `v.s.` prefix
4. **Účel platby:** field (foreign transfers)

If VS is `0000000000`, it's automatically set to `null`.

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
$parser = new CsobMailParser();
$mailContents = $parser->parseMulti($emailBody);

if (empty($mailContents)) {
    error_log('No transactions found in ČSOB email');
} else {
    foreach ($mailContents as $mailContent) {
        processTransaction($mailContent);
    }
}
```

## Next Steps

- See more [Examples](/guide/examples)
- Check [ČSOB SK](/guide/csob-sk) for Slovak version
- Read the complete [API Reference](/api/reference)
