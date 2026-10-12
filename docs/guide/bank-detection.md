# Bank Detection

`BankMailsParser` picks a parser from the email body. Sender and subject are extra signals. You do not have to choose a bank class yourself.

## Quick start

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;

$parser = new BankMailsParser();

try {
    foreach ($parser->parseAll($emailBody, $sender, $subject) as $mailContent) {
        echo $mailContent->money?->toDecimalString();
        echo $mailContent->money?->currency->value;
    }
} catch (UnsupportedBankException $exception) {
    // No registered parser accepted this email.
} catch (InvalidEmailFormatException $exception) {
    // A parser accepted the email, then rejected the contents.
}
```

`parse()` throws. `tryParse()` catches `ParseException` and returns `null`.

## Default parsers

Always pass `$sender`. Without it, a sentence the payer typed can match a second bank and `parse()` throws `AmbiguousBankException`.

`new BankMailsParser()` (or `null`) registers five parsers. The PGP statement parser is inserted after ComfortPay, and only when the constructor receives a decryptor. Without a decryptor, ČSOB CZ is third. A ČSOB header is the start of a line and does not need a blank line before it. The date may include `HH:MM`. A full header inside a payer field is rejected. `supports()` returns false when a pattern cannot run.

| Order | Parser | Content signal | Extra signal |
|------:|--------|----------------|--------------|
| 1 | `TatraBankaMailParser` | a line that starts with a date and `bol zostatok Vasho uctu … zvyseny\|znizeny o` | `tatrabanka.sk` |
| 2 | `TatraBankaSimpleMailParser` | the first contiguous `KEY=value` block with `RES` or `TRES` plus `AMT`, `VS`, `HMAC` or `SIGN`. Lines starting with `>` are ignored | `tatrabanka.sk` |
| 3 | `TatraBankaStatementMailParser` | a line `-----BEGIN PGP MESSAGE-----` | `tatrabanka.sk`; needs a decryptor |
| 4 | `CsobMailParser` | a line `Dne d.m.yyyy … byl(a) na účtu … zaúčtov` | `csob.cz` |
| 5 | `SkCsobMailParser` | `dňa d.m.yyyy … bola na účte … zaúčtovaná` (also after `že`) | `csob.sk` |
| 6 | `VubMailParser` | line-anchored `Dátum`, `Z účtu`, `Na účet`, `Suma`, `VS`, and `Stav` or `SIGN` | `vub.sk` |

Recipient messages, payment purpose, counterparty names, and descriptions are removed before detection. They cannot choose the bank or supply the amount.

If you pass a sender, only parsers for that host are considered. An unknown host throws `UnsupportedBankException`. If more than one eligible parser accepts the body, `parse()` throws `AmbiguousBankException`. The facade does not keep the first match. A single match that throws `InvalidEmailFormatException` is rethrown. Any other `ParseException` stops the walk.

## Custom parsers

Pass an array to use exactly those parsers. An empty array registers none. `null` is the only value that loads the defaults.

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

$custom = new class implements ParserInterface {
    use ParsesBankMail;

    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return str_contains($content, 'ACME BANK');
    }

    public function parse(string $content): MailContent
    {
        return new MailContent(description: 'ACME BANK');
    }
};

$parser = new BankMailsParser([$custom]);
$parser->registerParser(new \Tomaj\BankMailsParser\Parser\Vub\VubMailParser());
```

`getParsers()` returns the registered list.

## Direct parser use

You can still construct a bank parser yourself. Each one implements the same pair:

- `supports(string $content, ?string $sender = null, ?string $subject = null): bool`
- `parse(string $content): MailContent` — throws `InvalidEmailFormatException`. On a concrete parser this reads one transaction. On `BankMailsParser` more than one transaction throws `MultipleTransactionsException`.
- `parseAll(string $content): list<MailContent>` — every row. Use this for ČSOB and for statements.
- `tryParse(string $content): ?MailContent` — `null` for invalid input, including `MultipleTransactionsException`. It does not catch `TypeError`.

Pass `new BankMailsParser(decryptor: $decryptor)` so the default list can read PGP statements. `parseAll()` returns every decrypted row. Without a decryptor the statement parser is not registered, so a PGP mail is `UnsupportedBankException` and facade `tryParse()` returns `null`. A decryptor that cannot open its key throws `ConfigurationException`, including from `tryParse()`. Direct `TatraBankaStatementMailParser` still throws `ConfigurationException` when it has no working decryptor.

Slovak and Czech email dates use `Europe/Bratislava`. CardPay `TIMESTAMP` values of 14 digits are UTC.

The library does not verify CardPay `HMAC` or `SIGN`, and it does not verify DKIM. Do both before you book a payment. When a notification has both, `$sign` is the HMAC. Always pass the sender. A sender host only selects which parsers may run. DKIM proves the sending domain; it does not prove that the amount in a free-text field is the bank's amount. Payer text is ignored for the amount, currency, and date. A full transaction header or a signed amount line inside that text is blanked. It does not reject the mail. An account-shaped line is still the counterparty account. Tatra and ČSOB can still read a variable symbol from payer text, the same way 4.1.0 did. When you pair a payment, also check the amount. Quoted `>` history is not parsed as a payment. Use `parseAll()` when a mailbox loop might see ČSOB or a statement: `tryParse()` returns `null` for more than one transaction, so those payments would be skipped.

See [Exceptions](/guide/exceptions) and [Adding a New Bank](/guide/adding-bank).
