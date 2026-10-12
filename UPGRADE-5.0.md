# Upgrading from 4.x to 5.0

## Requirements

Minimum PHP is **8.4**. PHP 8.2 and 8.3 are not supported. PHP 8.5 is supported.

```diff
- "php": ">=8.2"
+ "php": "^8.4",
+ "ext-mbstring": "*"
```

`ext-mbstring` is required. Parsers call `mb_trim()`.

## 1. Money replaces float amount and string currency

4.x exposed `$mailContent->getAmount()` (`float`) and `$mailContent->getCurrency()` (`string`).

5.0 stores both on `MailContent::$money`:

```php
$mailContent->money?->amount;            // int minor units, 1234 for 12.34
$mailContent->money?->toDecimalString(); // "12.34"
$mailContent->money?->currency->value;   // "EUR"
$mailContent->money?->format();          // "12.34 EUR"
```

Create values with `Money::fromDecimalString()`. The first argument is a decimal string (`"12.34"`, `"12,34"`, `"-1 234,56"`, `"1.234,56"`). The second argument is a `Currency` or an ISO code, including numeric codes such as `"978"`.

There is no float amount on the public API, no `getDecimalAmount()`, and no `amountInMinorUnits` property. The integer property is `amount`.

Getters and setters from 4.x are gone. There is no compatibility layer.

## 2. Dates are DateTimeImmutable

4.x used `getTransactionDate(): int|false|null`.

5.0 uses `?DateTimeImmutable`:

```php
$mailContent->transactionDate?->format('Y-m-d H:i:s');
$mailContent->transactionDate?->getTimestamp();
```

`null` means the email had no usable date. `false` is no longer returned. There is no `getTransactionDateTimestamp()`.

**Breaking change for UTC servers.** Slovak and Czech bank emails are parsed as `Europe/Bratislava` wall time, not as `date.timezone`. On a server whose `date.timezone` is UTC, every 4.x timestamp moves by **+1 hour** (CET) or **+2 hours** (CEST). The instant is the bank's local time. CardPay `TIMESTAMP` values of 14 digits stay UTC (`dmYHis`). A 10-digit value is a Unix timestamp.

A missing CardPay timestamp is `null`. 4.x used `time()`. `null` is safer: "now" changes between reads and cannot be asserted. Invalid calendar dates stay `null`: `31.2.2015` does not roll forward, a missing Bratislava wall time such as `29.3.2015 2:30` stays null, and a two-digit year stays null. `16.1.2015 24:00` is the next day at `00:00`, as in 4.x. `TIMESTAMP=99999999999999` throws `InvalidEmailFormatException`.

Tatra accepts the numeric formats from 4.1.0 (`j.n.Y`, with time and seconds, and `Y-m-d`; spaces around the dots are allowed) and an English month the way `strtotime()` did (`16 January 2015 12:51`). `31 February` stays null. Invalid UTF-8 in a Tatra or CardPay body is scrubbed (`mb_scrub`) and the mail is parsed. It is not rejected for the bad bytes.

## 3. MailContent is immutable

```php
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

$mailContent = new MailContent(
    money: Money::fromDecimalString('100.50', Currency::fromCode('EUR')),
    transactionDate: new DateTimeImmutable('2024-01-15'),
    vs: '1234567890',
    accountNumber: 'SK1234567890',
);
```

Every string property is trimmed. An empty string becomes `null`, including `accountNumber`, `sourceAccountNumber`, `ownAccountNumber`, `receiverMessage`, and `description`.

| 4.x getter | 5.0 |
|------------|-----|
| `getAmount()` | `$mailContent->money?->toDecimalString()` |
| `getCurrency()` | `$mailContent->money?->currency->value` |
| `getTransactionDate()` | `$mailContent->transactionDate` |
| `getAccountNumber()` | `$accountNumber` (payer account on ČSOB SK and VÚB, same as 4.x) |
| `getSourceAccountNumber()` | `$sourceAccountNumber` |
| — | `$ownAccountNumber` (new: ČSOB SK `na účte`, VÚB `Na účet`) |
| `getVs()` / `getSs()` / `getKs()` | `$vs` / `$ss` / `$ks` |
| `getReceiverMessage()` | `$receiverMessage` |
| `getDescription()` | `$description` |
| `getCid()` / `getSign()` / `getRes()` | `$cid` / `$sign` / `$res` |
| `getAc()` / `getCc()` / `getTid()` | `$ac` / `$cc` / `$tid` |
| `getTxn()` / `getRc()` | `$txn` / `$rc` |

`accountNumber` keeps the 4.x meaning. On ČSOB SK it is `z účtu` (the payer). On VÚB it is `Z účtu` / `Z tu` (the payer). `sourceAccountNumber` stays null for those two banks, as in 4.x. The account at the bank that sent the mail is the new property `ownAccountNumber` (`na účte` on ČSOB SK, `Na účet` / `Na et` on VÚB). ČSOB CZ `accountNumber` is still `na účtu`, and `sourceAccountNumber` is still `Účet protistrany`.

## 4. parse() throws, tryParse() returns null

4.x `parse()` returned `null` when the email did not match.

5.0 `BankMailsParser::parse()` returns one `MailContent` or throws. If the mail contains more than one transaction it throws `MultipleTransactionsException` (a `ParseException`) and the message points at `parseAll()`. `tryParse()` then returns `null`. Use `parseAll()` for ČSOB and for statement files. There is no `parseOrFail()`.

`ParserInterface` is `supports()`, `parseAll()`, and `bankDomains()`. The trait still provides `tryParse()` and `money()` on the concrete class.

`new BankMailsParser()` does not register the PGP statement parser. Pass a `TatraBankaMailDecryptor` to register it. Without that decryptor a PGP mail is `UnsupportedBankException`, and `tryParse()` returns `null`. It is not `ConfigurationException`. A decryptor that cannot open its key still throws `ConfigurationException`.

Pass `$sender` on every call. A parser whose `bankDomains()` is empty is skipped when a sender is set. Two parsers accepting one body throw `AmbiguousBankException`.

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;

$parser = new BankMailsParser();

try {
    $mailContent = $parser->parse($emailBody, $sender, $subject);
} catch (UnsupportedBankException) {
    // no parser accepted the email
} catch (InvalidEmailFormatException) {
    // a parser accepted it and then rejected the contents
}

$mailContent = $parser->tryParse($emailBody, $sender, $subject);
```

`BankMailsParser` with no arguments registers TatraBanka (plain and ComfortPay), ČSOB CZ, ČSOB SK, and VÚB. The PGP statement parser is registered only when the constructor receives a decryptor. Pass an array to use exactly those parsers.

## 5. Currency is an ISO value object

`Currency` is a `final class`, not an enum. `Currency::fromCode()` accepts every active ISO 4217 alphabetic code and its numeric code (`EUR`, `978`, `208` for DKK, `036` for AUD, `SEK`), plus the withdrawn lev and kuna (`BGN` / `975`, `HRK` / `191`) so older mail can be parsed again. `Currency::tryFromCode()` returns `null` for anything else. Compare with `equals()`. CardPay `CURR=978` is stored as `EUR`. CardPay with an unknown `CURR`, or with `AMT` and no `CURR`, keeps `RES`, `VS` and the other fields and sets `money` to `null`. It does not discard the notification.

`minorUnits()` comes from the ISO table: 0 for JPY and ISK, 3 for BHD, KWD, OMR and TND, 4 for CLF and UYW, and 2 otherwise (including codes whose ISO scale is `N.A.`, such as `XXX`). Extra fraction digits are rejected unless they are all zeros, so `1234,00` JPY is `1234` and `1234,01` JPY throws. `fromDecimalString('1.234')` without a decimal separator is ambiguous and throws. Pass `','` from Tatra, ČSOB, and VÚB, and `'.'` from CardPay and Tatra statements. Space, NBSP, U+2009, U+202F and tab are thousands separators only in groups of three (`1 234,56` is accepted, `1 2 3,45` and `81 5` are not). Dot and comma groups have the same shape (`1.234,56`). Values that do not fit in an integer, including `PHP_INT_MIN`, throw `InvalidArgumentException`, which parsers wrap as `InvalidEmailFormatException`. `tryParse()` then returns `null`.

## 5a. Accounts, symbols, and detection

`accountNumber` on ČSOB SK is `z účtu` and on VÚB is `Z účtu` / `Z tu`. `ownAccountNumber` is `na účte` or `Na účet`. `sourceAccountNumber` is the ČSOB CZ or Tatra counterparty. ČSOB SK reads `/SS…/`. VÚB uses EUR only when the amount line does not name a currency. On a Tatra card statement, `accountNumber` is column 19 (`Číslo účtu`), the merchant account credited for that card sale. It can differ on every row. It is not one account for the whole file.

A variable symbol matches 4.1.0. Tatra uses `/VS…/SS…/KS…` and keeps every digit, including more than 10, then a `VS` token anywhere in the mail, then the last number on `Informacia pre prijemcu` when `Referencia platitela` is absent (including a creditor reference; this is 4.1.0), then the last number on a bare `Referencia platitela`. A blank line before that reference does not drop the symbol. ČSOB CZ uses `Variabilní symbol` and skips an all-zero value. `Identifikace` then replaces that value, as in 4.1.0. After that come a `VS` or `v.s.` token, the last number on `Zpráva příjemci`, and `Účel platby`. `Účel platby` uses `\b([0-9]{1,10})\b` up to `Zůstatek`, so a number glued to letters (`AB12`, `INV2026`, `FV2024001`) is not a symbol. Without `Zůstatek`, a number in the footer is not a symbol. More than 10 digits on ČSOB CZ is `null` (4.x truncated the symbol, which could pair the wrong payment). ČSOB SK reads `/VS`, `/SS`, and `/KS` from `referencia platiteľa` with no 10-digit cap, then the first `VS` or `KS` digits on the `informácia pre príjemcu` line only when the reference did not set that symbol (`VS111 nahrada za VS222` is `111`). A payer name such as `ADVS2` or `BKS1` is not a symbol.

VÚB reads `ŠS`, `SS`, and `S` into `ss` (the 4.x example `vub_payment_example.txt` has `ss` `910`). `SIGN` is stored on `$sign`. The caller still verifies it.

Leading spaces, tabs, NBSP, U+2009, and U+202F are allowed before a label and after a colon. When ČSOB CZ, ČSOB SK, or VÚB has recognized the transaction and the amount is missing, unreadable, or in an unknown currency, `money` is `null` and the row is returned. A blank `Suma:` is `money = null`.

The recipient message, `Identifikace`, `Účel platby`, `Adresa protistrany`, `Název protistrany`, `Reference plátce`, and the Slovak `informácia pre príjemcu`, `názov protiúčtu`, and `referencia platiteľa` lines are written by the payer. A blank line, or a line of spaces, tabs or NBSP, ends the field. An arbitrary `Word:` does not. The field also ends on a bank line in that bank's exact shape: the full balance sentence, a digits-only symbol line, or an account line. Bare `Zůstatek`, `zostatok`, and `banka:` do not end it. Lines inside the field are blanked when the amount, account, and date are read. They do not reject the mail. `Částka: záloha 2 500 Kč` and `Dne 1.10.2026 byla na účtu chyba, vracím` stay the message text. A counterparty account is read from an account-shaped line, including one that follows the name. `<br>`, `</p>`, and `</div>` become newlines before a ČSOB parse. `sourceAccountNumber` keeps the account token only: trailing prose such as ` (Fio banka)` is dropped, and so is `<br>`. A ČSOB mail with several transactions and at least one balance line is checked: each transaction has exactly one balance line, the bank's, and the previous balance plus the next amount must equal the next balance. A second exact balance line in the same transaction is payer text and parsing throws. A mail with no balance line can contain several payments, the same as 4.1.0. That is a known limitation: without balance lines a forged transaction after a blank line cannot be distinguished from a real one. Check the amount against the bank API or a statement. When the mail already has LF, a lone CR, VT, FF, U+0085, U+2028 or U+2029 stays an ordinary character and does not split a line. Those separators become newlines only when the mail has no LF. A ČSOB header is recognized at the start of a line and does not need a blank line before it. When you pair a payment, also check the amount. `supports()` returns false when a pattern cannot run. It does not throw.

A Tatra or ČSOB notice that exists only as quoted history (lines starting with `>`) is not a payment. 4.1.0 parsed some of those lines. 5.0 does not.

Passing a sender makes `BankMailsParser` use only parsers whose `bankDomains()` contain that host (`tatrabanka.sk`, `csob.cz`, `csob.sk`, `vub.sk`). `supports()` itself does not require the host. An unknown host throws `UnsupportedBankException`. If more than one eligible parser accepts the body, `parse()` throws `AmbiguousBankException` and `tryParse()` returns `null`. The facade does not keep the first match.

`BankMailsParser::parseAll()` returns every transaction. `parse()` throws `MultipleTransactionsException` when there is more than one. An empty `parseAll()` throws `InvalidEmailFormatException` instead of reading index 0. ČSOB and Tatra statements do not drop a transaction that matched and then failed: they throw one exception that lists the bad rows. Statement `parseMulti()` returns that list or throws; it does not return `null`. Construct `new BankMailsParser(decryptor: $decryptor)` to read PGP statements with the default parsers. Without a decryptor the statement parser is not registered, so a PGP mail is `UnsupportedBankException` and `tryParse()` returns `null`. A decryptor with a missing or unreadable key, or a wrong passphrase, throws `ConfigurationException`, including from `tryParse()`. `decrypt()` returns `string` and throws; it never returns `null`. CardPay reads the first contiguous `KEY=value` block that has `RES` or `TRES` and `AMT`, `VS`, `HMAC` or `SIGN`. A word on that line that is not `KEY=value` is skipped (`Vysledok: VS=… RES=OK …` parses). A later line that mixes prose is not part of the block. Lines starting with `>` are not part of that block. When `TRES` is present, `$res` is the `TRES` value, as in 4.x, and `$tres` stores it as well. `RES` alone fills `$res`. A missing `CURR` with `AMT` is EUR. An unknown `CURR` keeps the notification and sets `money` to `null`. A duplicate key with a different value throws `InvalidEmailFormatException`. When both `SIGN` and `HMAC` are present, `$sign` is the HMAC. The library stores the value and does not check it. VÚB `supports()` requires the date, both account labels, the amount, `VS`, and `Stav` or `SIGN`, each at the start of its line. Neither check proves who sent the mail. Verify DKIM yourself.

## Behavioral differences vs 4.x

API shape (properties, `Money` / `Currency`, `DateTimeImmutable`, exceptions, the facade) is listed above. These are the field differences that remain after matching 4.1.0 everywhere else. Each one is intentional.

- **Time zone.** Bank dates are `Europe/Bratislava`. On a UTC server every Slovak or Czech timestamp is +1 h or +2 h versus 4.x. CardPay `TIMESTAMP` is a UTC `DateTimeImmutable`, not the raw 14-digit string.
- **Invalid dates are null.** `31.2`, a two-digit year, and a DST gap such as `29.3.2015 2:30` do not roll forward. `24:00` is the next day at `00:00`. A Tatra date written as `16 January 2015 12:51` parses. `31 February` stays null.
- **Missing CardPay `TIMESTAMP` is null**, not `time()`.
- **Quoted lines that start with `>`** are not a payment. 4.x booked some of them.
- **Trim.** Trailing CR and an empty `AC=` become null.
- **ČSOB CZ.** `parse()` returns the date (4.x returned `false` from the single-message parser). `1.440,00` is 1440, not 1.44. A message that contains the word `Dne` is kept. `sourceAccountNumber` is the account token only. Trailing prose such as ` (Fio banka)` is dropped, and so is `<br>`. A one-line HTML body is split on `<br>`, `</p>`, and `</div>` before parsing. VS/KS longer than 10 digits are null, not truncated.
- **Tatra header.** The sentence `bol zostatok Vasho uctu` must start the line (leading whitespace is allowed). `Dobry den, 16.1.2015 12:51 bol zostatok…` does not parse. `parse()` throws `InvalidEmailFormatException`. An amount with a third non-zero decimal, such as `12,315` EUR, is rejected the same way.
- **ČSOB SK.** `accountNumber` is `z účtu`. `ownAccountNumber` is `na účte` (new). `ss` is read. VS and KS are not taken from the payer name. KS falls back to `informácia pre príjemcu` when the reference has no KS. More than 10 digits are kept.
- **VÚB.** Thousands separators, a leading minus, the date on a UTF-8 `Dátum`, the currency (EUR when the line names none), `ss`, and `SIGN` are read. 4.x left the date and the account null on real UTF-8 mail and mis-read `1 234,90` as 1. `accountNumber` is the payer. `ownAccountNumber` is `Na účet`.
- **Tatra and CardPay amounts.** `1,234.50` is 1234.50. `12,315` (three non-zero decimals) is rejected. `CURR=978` is `EUR`. A missing `CURR` is EUR. An unknown `CURR` leaves `money` null. Invalid UTF-8 bytes are scrubbed and the mail parses. `TRES` wins for `res` when it is present; `tres` is also stored. HMAC wins over SIGN. The first `KEY=value` block is used, including a line that starts with prose. A duplicate CardPay key with a different value throws `InvalidEmailFormatException`.
- **Statements.** Grouped decimals are read. An invalid statement date is null.
- **Payer text and the balance chain.** A header or amount written inside a payer field is blanked. It does not become a second transaction and it does not reject the mail. When the mail has more than one transaction and at least one balance line, each transaction needs exactly one balance and the balances must chain. A second balance line in the same transaction throws `InvalidEmailFormatException` with `Transaction has more than one balance line`. A mail with **no** balance line still returns every line-start transaction, including a forged one that sits after a blank line. That matches 4.1.0 and is a known limitation.
- **Unknown or unreadable amount** on ČSOB CZ, ČSOB SK, and VÚB: the row is returned with `money` null. The mail is not rejected.

`UnsupportedBankException::withSender()` does not include the email address.

## 6. Custom parsers

`ParserInterface` is `supports()`, `parseAll()`, and `bankDomains()`. Implement `parse()` on the class. `ParsesBankMail` provides `parseAll()` (one call to `parse()`), `tryParse()`, and `bankDomains()` (an empty list, so the parser runs only when the caller omits the sender). Override `bankDomains()` when the parser belongs to a host. `parse()` throws `InvalidEmailFormatException` instead of returning `null`. Build amounts with `$this->money()` so `InvalidArgumentException` does not escape. Build `MailContent` in the constructor. Mark the class `final readonly`. Put `#[\Override]` on `supports()`, `parseAll()`, and `bankDomains()`. Do not put it on `parse()`: that method is not on the interface. The built-in parsers are already `final readonly`, so 4.x code that extended `CsobMailParser` no longer compiles.

## 7. Releasing 5.0.0

`CHANGELOG.md` keeps the current top heading until the tag. Before tagging, rename it to `## [5.0.0] - YYYY-MM-DD` in `CHANGELOG.md` and in `docs/changelog.md`. Rename the compare link in both files from `[Unreleased]: …/compare/4.1.0...HEAD` to `[5.0.0]: …/compare/4.1.0...5.0.0`. `release.yml` reads `## [5.0.0]` and rejects the pre-release heading. The section body must not contain the words TBD or Unreleased. Tags have no `v` prefix (`5.0.0`, same as `4.1.0`).

## 8. What stayed out

- No `TransactionType` enum. Use `Money::isPositive()` and `Money::isNegative()`.
- No moneyphp/money dependency. Amounts are integers in minor units.
- `docs/4.x/` still documents the 4.x getter API.

## Checks

```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
```
