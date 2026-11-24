# Exceptions

Parsing failures are exceptions. There is one pair of methods on `ParserInterface` and on `BankMailsParser`:

| Method | Success | Failure |
|--------|---------|---------|
| `parse()` | `MailContent` | throws |
| `tryParse()` | `MailContent` | `null` |

`tryParse()` returns `null` for invalid input (`ParseException`, `InvalidArgumentException`, `ValueError`, `DateMalformedStringException`). It does not catch `TypeError`. It rethrows `ConfigurationException`. A missing decryptor on the default facade is not that error: the statement parser is not registered, so the mail is `UnsupportedBankException` and `tryParse()` returns `null`.

If more than one eligible parser accepts the email, the facade throws `AmbiguousBankException` before it parses. It does not keep the first match. When exactly one parser accepts it, an exception from that parser (`InvalidEmailFormatException`, `ConfigurationException`) reaches the caller unchanged; no other parser is tried.

## Hierarchy

```text
BankMailsParserException (interface, implemented by every class below)

RuntimeException
├── ConfigurationException
└── ParseException
    ├── UnsupportedBankException
    ├── InvalidEmailFormatException
    ├── AmbiguousBankException
    ├── MultipleTransactionsException
    └── DecryptionException

\InvalidArgumentException
└── Exception\InvalidArgumentException
```

### BankMailsParserException

Every exception the package throws implements `Tomaj\BankMailsParser\Exception\BankMailsParserException` (since 5.1.0). Catch it when anything from this package should take the same path, including configuration errors:

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\BankMailsParserException;

$parser = new BankMailsParser();

try {
    $rows = $parser->parseAll($emailBody, $sender);
} catch (BankMailsParserException $exception) {
    // A parsing, configuration or Money/Currency error from this package.
}
```

### ParseException

Base type for parsing errors. Catch it when any parsing failure should take the same path.

### UnsupportedBankException

Thrown by `BankMailsParser::parse()` when no registered parser supports the email.

- `UnsupportedBankException::noParserFound()` — no sender was available
- `UnsupportedBankException::withSender(?string $sender)` — says that no parser matched the supplied sender, without putting the address in the message

### AmbiguousBankException

Thrown by `BankMailsParser::parse()` when two or more parsers accept the same email. `tryParse()` returns `null`. `AmbiguousBankException::forParsers()` names the classes. Pass the sender so a payer-controlled sentence cannot make a second parser match.

### MultipleTransactionsException

Thrown by `BankMailsParser::parse()` when `parseAll()` returned more than one transaction. The message says to call `parseAll()`. `tryParse()` returns `null`. ČSOB mail and Tatra statements are the usual cases.

### InvalidEmailFormatException

Thrown when a parser recognizes the email and then cannot read it.

- `InvalidEmailFormatException::missingRequiredField(string $parserClass, string $fieldName)`
- `InvalidEmailFormatException::malformedContent(string $parserClass, string $reason)`

Individual parsers throw this from `parse()` and `parseAll()`. The facade passes it on unchanged.

`TatraBankaStatementMailParser` wraps `DecryptionException` in `InvalidEmailFormatException` and keeps the original as `$previous`. A missing decryptor, a missing or unreadable key file, and a wrong passphrase throw `ConfigurationException` instead. `tryParse()` rethrows it. `parseAll()` returns every statement row, or throws one exception that lists the rows it could not parse.

## Examples

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\ParseException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;

$parser = new BankMailsParser();

try {
    $mailContent = $parser->parse($emailBody, $sender, $subject);
} catch (UnsupportedBankException $exception) {
    // Unknown bank.
} catch (InvalidEmailFormatException $exception) {
    // Known shape, unreadable fields.
} catch (ParseException $exception) {
    // Any parsing failure.
}

if ($parser->tryParse($emailBody) === null) {
    // Same failures, without an exception.
}
```

Invalid money or currency values thrown while building a `Money` or `Currency` object are `Tomaj\BankMailsParser\Exception\InvalidArgumentException`, not `ParseException`. It extends PHP's `\InvalidArgumentException`, so a `catch (\InvalidArgumentException)` written for 5.0 still catches it. Parsers that accept user-facing amount strings catch that and throw `InvalidEmailFormatException` instead, so `tryParse()` still returns `null`.

## What is not an exception type

There is no transaction-type enum. Incoming and outgoing payments are the sign of `Money::$amount`. Bank emails do not label direction reliably enough for a separate type.
