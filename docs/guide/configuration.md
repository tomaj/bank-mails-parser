# Configuration reference

The library has no configuration file and no global state. Everything is a constructor argument or a method argument, listed here.

## BankMailsParser

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;

$parser = new BankMailsParser(
    parsers: null,      // null: the built-in parsers; a list: exactly those parsers
    decryptor: null,    // a TatraBankaMailDecryptor registers the PGP statement parser
);
```

| Argument | Type | Default | Meaning |
|----------|------|---------|---------|
| `$parsers` | `?list<ParserInterface>` | `null` | `null` registers the built-in parsers in this order: `TatraBankaMailParser`, `TatraBankaSimpleMailParser`, `TatraBankaStatementMailParser` (only with a decryptor), `CsobMailParser`, `SkCsobMailParser`, `VubMailParser`. An array, including `[]`, registers exactly those parsers and nothing else. |
| `$decryptor` | `?TatraBankaMailDecryptor` | `null` | Used only when `$parsers` is `null`: the default list then includes `TatraBankaStatementMailParser` with this decryptor. Without it, an encrypted statement is an unsupported mail. |

| Method | Meaning |
|--------|---------|
| `registerParser(ParserInterface $parser): void` | Appends a parser |
| `prependParser(ParserInterface $parser): void` | Inserts a parser in front of the registered ones |
| `getParsers(): list<ParserInterface>` | The registered parsers, in order |

The order of the list only matters for `getParsers()`: detection asks every eligible parser and refuses to choose when more than one says yes. See [Bank detection](./bank-detection).

## Parse arguments

`parse()`, `tryParse()` and `parseAll()` on the facade take the same three arguments:

| Argument | Type | Default | Meaning |
|----------|------|---------|---------|
| `$content` | `string` | | The decoded plain-text body of the mail (not the raw MIME source). CRLF, a leading BOM and invalid UTF-8 bytes in Tatra and CardPay bodies are handled. |
| `$sender` | `?string` | `null` | The sender address, with or without a display name (`"Tatra banka" <b-mail@tatrabanka.sk>` works). When it is set, only parsers whose `bankDomains()` contain the sender's host or a parent domain of it are asked, and a parser with no domains is skipped. `null` or an empty string asks every parser. **Always pass it.** |
| `$subject` | `?string` | `null` | Only `TatraBankaMailParser` reads it: a mail whose subject contains `tatra` (or whose sender is `tatrabanka.sk`) and whose body contains `zostatok Vasho uctu` is supported even when the header line is not at the start of a line. |

The bank domains of the built-in parsers:

| Parser | `bankDomains()` |
|--------|-----------------|
| `TatraBankaMailParser`, `TatraBankaSimpleMailParser`, `TatraBankaStatementMailParser` | `tatrabanka.sk` |
| `CsobMailParser` | `csob.cz` |
| `SkCsobMailParser` | `csob.sk` |
| `VubMailParser` | `vub.sk` |

## TatraBankaMailDecryptor

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;

$parser = new BankMailsParser(decryptor: new TatraBankaMailDecryptor(
    privateKeyPath: '/run/secrets/tatrabanka-statement.asc',
    passphrase: 'from your secret store',
));
```

| Argument | Type | Meaning |
|----------|------|---------|
| `$privateKeyPath` | `string` | Path to the ASCII-armoured OpenPGP private key whose public part Tatra banka encrypts the statement for. It is read on every `decrypt()` call, not in the constructor. |
| `$passphrase` | `string` | The key's passphrase |

A missing, unreadable or empty key file and a wrong passphrase throw `ConfigurationException` when a statement is parsed, also from `tryParse()`. A message that cannot be decrypted with a valid key throws `DecryptionException`, which the statement parser wraps in `InvalidEmailFormatException`. See the [encrypted statement example](/examples/encrypted-statement).

## Things that are fixed

These are not configurable, on purpose:

| What | Value |
|------|-------|
| Time zone of Slovak and Czech bank dates | `Europe/Bratislava`, independent of `date.timezone` |
| Time zone of a 14-digit CardPay `TIMESTAMP` | UTC |
| Decimal separator | `,` for Tatra banka notifications, ČSOB and VÚB; `.` for CardPay and statements |
| Currency without a code | EUR on VÚB `Suma:` and CardPay without `CURR` |
| Currencies | Every active ISO 4217 code, plus withdrawn `BGN` and `HRK` |
| Symbols | VS, SS and KS are strings of digits; leading zeros are kept |

## Requirements

| Requirement | Why |
|-------------|-----|
| PHP 8.4 or newer | Tested on 8.4, 8.5 and 8.6 |
| `ext-mbstring` | `mb_trim()` and UTF-8 handling |
| `singpolyma/openpgp-php` | Installed with the package; decrypts statements in pure PHP |
