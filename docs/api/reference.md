# API Reference

Complete API documentation for Bank Mails Parser 5.x.

## MailContent

`MailContent` is a `final readonly` value object. Empty strings passed for symbol and ComfortPay fields are stored as `null`.

### Namespace

```php
Tomaj\BankMailsParser\MailContent
```

### Constructor

```php
public function __construct(
    public ?Money $money = null,
    public ?DateTimeImmutable $transactionDate = null,
    public ?string $accountNumber = null,
    public ?string $sourceAccountNumber = null,
    ?string $vs = null,
    ?string $ss = null,
    ?string $ks = null,
    public ?string $receiverMessage = null,
    public ?string $description = null,
    ?string $cid = null,
    ?string $sign = null,
    ?string $res = null,
    ?string $ac = null,
    ?string $cc = null,
    ?string $tid = null,
    ?string $txn = null,
    ?string $rc = null,
    ?string $tres = null,
)
```

```php
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

$mailContent = new MailContent(
    money: Money::fromDecimalString('123.45', Currency::fromCode('EUR')),
    transactionDate: new DateTimeImmutable('2015-01-16 12:51:00'),
    accountNumber: 'SK9812353347235',
    vs: '1234567890',
    ks: '0308',
);
```

### money: ?Money

Amount and currency together, or `null` when the email has no amount. ComfortPay rows without a currency also leave this `null`, because `Money` always has a currency.

```php
$mailContent->money?->amount;            // 12345 (minor units)
$mailContent->money?->toDecimalString(); // "123.45"
$mailContent->money?->currency->value;   // "EUR"
$mailContent->money?->format();          // "123.45 EUR"
```

The public amount is an `int`. Use `toDecimalString()` when you need a decimal. There is no float property.

### transactionDate: ?DateTimeImmutable

Transaction date, or `null` when the email has none or the text cannot be parsed. There is no `false` sentinel.

```php
$mailContent->transactionDate?->format('Y-m-d H:i:s');
$mailContent->transactionDate?->getTimestamp();
```

### Account and symbols

| Property | Meaning |
|----------|---------|
| `accountNumber: ?string` | Payer account on ČSOB SK (`z účtu`) and VÚB (`Z účtu`). ČSOB CZ and Tatra keep the account from the bank header |
| `sourceAccountNumber: ?string` | Counterparty on ČSOB CZ and Tatra. Null on ČSOB SK and VÚB |
| `ownAccountNumber: ?string` | Account at the sending bank: ČSOB SK `na účte`, VÚB `Na účet` |
| `vs: ?string` | Variable symbol. Tatra and ČSOB CZ also take it from payer text (the recipient message, `Identifikace`, `Účel platby`, a mandate reference, or a creditor reference), matching 4.1.0. `Účel platby` uses a word boundary, so `AB12` is not a symbol. ČSOB SK reads `/VS` from `referencia platiteľa`, then the first `VS` token on the `informácia pre príjemcu` line. When pairing a payment, also check the amount. |
| `ss: ?string` | Specific symbol |
| `ks: ?string` | Constant symbol |
| `receiverMessage: ?string` | Message for the recipient |
| `description: ?string` | Bank description |

### ComfortPay / CardPay fields

| Property | Meaning |
|----------|---------|
| `cid: ?string` | Client ID |
| `sign: ?string` | HMAC signature |
| `res: ?string` | Result code (`OK`, `FAIL`, …). `TRES` wins when it is present |
| `tres: ?string` | CardPay `TRES`, also stored on its own |
| `ac: ?string` | Authorization code |
| `cc: ?string` | Masked card number |
| `tid: ?string` | Terminal or transaction id |
| `txn: ?string` | CardPay transaction code such as `PA` |
| `rc: ?string` | Return code |

`txn` is the raw CardPay code. Direction of a bank transfer is the sign of `Money::$amount`, not a separate enum.

## Money

```php
Tomaj\BankMailsParser\ValueObject\Money
```

```php
final readonly class Money
{
    public function __construct(
        public int $amount,
        public Currency $currency,
    ) {}
}
```

`amount` is minor units. `12.34 EUR` is `1234`.

```php
$money = Money::fromDecimalString('12,34', Currency::fromCode('EUR'), ',');
$money = Money::fromDecimalString('-1 234,56', 'EUR');
$money = Money::fromDecimalString('1.234,56', '978'); // numeric ISO → EUR

$money->toDecimalString(); // "-1234.56"
$money->format();          // "-1234.56 EUR"
$money->isPositive();
$money->isNegative();
$money->isZero();
$money->abs();
```

Accepted decimal strings include `12.34`, `12,34`, `-1 234,56`, `1.234,56`, and `+8`. Space, NBSP, thin space (U+2009), narrow NBSP (U+202F) and tab are thousands separators. Extra fraction digits are rejected unless every extra digit is `0` (`1234,00` JPY is `1234`; `1234,01` JPY throws). There is no rounding. Invalid text throws `InvalidArgumentException`.

## Currency

```php
Tomaj\BankMailsParser\ValueObject\Currency
```

`Currency` is a `final class`, not an enum. `fromCode()` accepts every active ISO 4217 alphabetic code and its numeric code (`978` and `036` are `EUR` and `AUD`), plus withdrawn `BGN` (`975`) and `HRK` (`191`). The active table is ISO list one published 2026-09-17. A short numeric code is padded to three digits (`36` is `036`, AUD). Codes with minor units `N.A.` (including `XXX`) use 2. `equals()` compares the alphabetic code. Object identity does not survive `unserialize`.

```php
Currency::fromCode('eur');    // EUR
Currency::fromCode('978');    // EUR
Currency::fromCode('208');    // DKK
Currency::fromCode('SEK');    // SEK, minor units 2
Currency::fromCode('JPY')->minorUnits(); // 0
Currency::fromCode('CLF')->minorUnits(); // 4
Currency::tryFromCode('NO');  // null
Currency::fromCode('NO');     // InvalidArgumentException
Currency::fromCode('975'); // BGN, withdrawn but accepted for old mail
Currency::fromCode('HRK'); // HRK, withdrawn but accepted for old mail
```

`fromCode()` trims and uppercases the value. Unknown codes throw `InvalidArgumentException`. `tryFromCode()` returns `null` instead.

## BankMailsParser

```php
Tomaj\BankMailsParser\BankMailsParser
```

```php
public function __construct(?array $parsers = null, ?TatraBankaMailDecryptor $decryptor = null)
public function registerParser(ParserInterface $parser): void
public function prependParser(ParserInterface $parser): void
public function parseAll(string $content, ?string $sender = null, ?string $subject = null): array
public function parse(string $content, ?string $sender = null, ?string $subject = null): MailContent
public function tryParse(string $content, ?string $sender = null, ?string $subject = null): ?MailContent
public function getParsers(): array
```

`null` registers the built-in parsers. `TatraBankaStatementMailParser` is included only when `$decryptor` is set. An array, including `[]`, uses exactly that list. `prependParser()` inserts a parser ahead of the ones already registered.

`parseAll()` returns every transaction. `parse()` returns the only transaction. More than one throws `MultipleTransactionsException` and tells the caller to use `parseAll()`. `tryParse()` returns `null` for `ParseException` (including that one), `InvalidArgumentException`, `ValueError`, and `DateMalformedStringException`. It does not catch `TypeError`. It rethrows `ConfigurationException`. A PGP mail with no decryptor is `UnsupportedBankException`, so `tryParse()` returns `null`.

Pass `$sender`. When CardPay `TRES` is present, `$res` is that value and `$tres` stores it too. The library does not verify `HMAC`, `SIGN`, or DKIM.

See [Bank Detection](/guide/bank-detection).

## ParserInterface

```php
namespace Tomaj\BankMailsParser\Parser;

interface ParserInterface
{
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool;

    /** @return list<MailContent> */
    public function parseAll(string $content): array;

    /** @return list<string> */
    public function bankDomains(): array;
}
```

`parseAll()` returns every transaction. `ParsesBankMail` implements it as `[$this->parse()]` and implements `tryParse()`. `bankDomains()` lists the hosts used when the caller passes a sender. An empty list skips the parser in that case. Use `$this->money()` so an invalid amount becomes `InvalidEmailFormatException` instead of leaking `InvalidArgumentException`. `BankDate` and `Iso4217` are `@internal`.

```php
final readonly class CustomBankParser implements ParserInterface
{
    use ParsesBankMail;

    #[\Override]
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return str_contains($content, 'Amount:');
    }

    public function parse(string $content): MailContent
    {
        if (preg_match('/Amount: ([\d.]+) ([A-Z]{3})/', $content, $match) !== 1) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Amount not found');
        }

        return new MailContent(
            money: $this->money($match[1], $match[2], '.'),
        );
    }
}
```

## Parsers

| Class | Email |
|-------|--------|
| `TatraBankaMailParser` | Plain-text balance notifications |
| `TatraBankaSimpleMailParser` | ComfortPay / CardPay `KEY=value` rows |
| `TatraBankaStatementMailParser` | PGP statements (`parseMulti()`) and one pipe-separated row |
| `CsobMailParser` | ČSOB Czech Republic |
| `SkCsobMailParser` | ČSOB Slovakia |
| `VubMailParser` | VÚB plain text |

CardPay `TIMESTAMP` values of 14 digits are `dmYHis` in UTC (`26112016121631` is 26 November 2016 12:16:31). A 10-digit value is a Unix timestamp. A missing timestamp is `null`. An invalid timestamp throws `InvalidEmailFormatException`. Numeric currency `978` is stored as `EUR`. An amount without `CURR` leaves `money` null and keeps `RES` and `VS`. An unknown `CURR` does the same: the notification is not discarded. Slovak and Czech bank dates use `Europe/Bratislava`. Years must be four digits; `11.12.19` does not become year 19.

`TatraBankaStatementMailParser::parse()` on a full PGP body decrypts through `parseAll()` and returns the first transaction. A body that is not a pipe-separated row throws `InvalidEmailFormatException`. It does not return `null`. `accountNumber` on a statement row is column 19, header `Číslo účtu` (stored mojibake `Èíslo uètu` in the sample file): the merchant account for that card sale. It can differ on every row. It is not one account for the whole statement.

ČSOB `parseMulti()` and statement `parseAll()` use one rule. Text that is not a transaction is ignored. A recognized transaction that cannot be parsed fails the whole message. The exception lists the bad rows (`transaction 2`, `row 2`). Nothing that matched a transaction is dropped.

VÚB amounts without a currency code are stored as EUR. The specific symbol is read from a line `ŠS:`, `SS:`, or `S:`. `VS:` and `KS:` are their own fields.

## Next steps

- [Getting Started](/guide/getting-started)
- [Exceptions](/guide/exceptions)
- [Adding a New Bank](/guide/adding-bank)
