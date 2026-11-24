# Referencia API

Úplná dokumentácia API knižnice Bank Mails Parser 5.x.

## MailContent

`MailContent` je value objekt `final readonly`. Prázdne reťazce v poliach symbolov a ComfortPay sa uložia ako `null`.

### Menný priestor

```text
Tomaj\BankMailsParser\MailContent
```

### Konštruktor

<!-- snippet: skip -->
```php
public function __construct(
    ?Money $money = null,
    ?DateTimeImmutable $transactionDate = null,
    ?string $accountNumber = null,
    ?string $sourceAccountNumber = null,
    ?string $ownAccountNumber = null,
    ?string $vs = null,
    ?string $ss = null,
    ?string $ks = null,
    ?string $receiverMessage = null,
    ?string $description = null,
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

Suma a mena spolu, alebo `null`, keď e-mail sumu nemá. Riadky ComfortPay bez meny nechajú tiež `null`, pretože `Money` menu má vždy.

```php
$mailContent->money?->amount;            // 12345 (najmenšie jednotky)
$mailContent->money?->toDecimalString(); // "123.45"
$mailContent->money?->currency->value;   // "EUR"
$mailContent->money?->format();          // "123.45 EUR"
```

Verejná suma je `int`. Desatinný zápis dá `toDecimalString()`. Vlastnosť typu float neexistuje.

### transactionDate: ?DateTimeImmutable

Dátum transakcie, alebo `null`, keď ho e-mail nemá alebo sa text nedá prečítať. Strážna hodnota `false` neexistuje.

```php
$mailContent->transactionDate?->format('Y-m-d H:i:s');
$mailContent->transactionDate?->getTimestamp();
```

### Účet a symboly

| Vlastnosť | Význam |
|----------|---------|
| `accountNumber: ?string` | Účet platiteľa v ČSOB SK (`z účtu`) a vo VÚB (`Z účtu`). ČSOB CZ a TatraBanka si nechajú účet z hlavičky banky |
| `sourceAccountNumber: ?string` | Protistrana v ČSOB CZ a v TatraBanke. V ČSOB SK a vo VÚB je null |
| `ownAccountNumber: ?string` | Účet v odosielajúcej banke: ČSOB SK `na účte`, VÚB `Na účet` |
| `vs: ?string` | Variabilný symbol. TatraBanka a ČSOB CZ ho berú aj z textu platiteľa (správa pre príjemcu, `Identifikace`, `Účel platby`, referencia mandátu alebo referencia veriteľa), rovnako ako verzia 4.1.0. `Účel platby` používa hranicu slova, takže `AB12` symbolom nie je. ČSOB SK číta `/VS` z `referencia platiteľa`, potom prvý token `VS` na riadku `informácia pre príjemcu`. Pri párovaní platby skontrolujte aj sumu. |
| `ss: ?string` | Špecifický symbol |
| `ks: ?string` | Konštantný symbol |
| `receiverMessage: ?string` | Správa pre príjemcu |
| `description: ?string` | Popis od banky |

### Polia ComfortPay / CardPay

| Vlastnosť | Význam |
|----------|---------|
| `cid: ?string` | ID klienta |
| `sign: ?string` | Podpis HMAC |
| `res: ?string` | Kód výsledku (`OK`, `FAIL`, …). Keď je prítomné `TRES`, vyhrá ono |
| `tres: ?string` | CardPay `TRES`, uložené aj samostatne |
| `ac: ?string` | Autorizačný kód |
| `cc: ?string` | Maskované číslo karty |
| `tid: ?string` | ID terminálu alebo transakcie |
| `txn: ?string` | Kód transakcie CardPay, napríklad `PA` |
| `rc: ?string` | Návratový kód |

`txn` je surový kód CardPay. Smer bankového prevodu je znamienko `Money::$amount`, nie samostatný enum.

## Money

```text
Tomaj\BankMailsParser\ValueObject\Money
```

<!-- snippet: skip -->
```php
final readonly class Money
{
    public function __construct(
        public int $amount,
        public Currency $currency,
    ) {}
}
```

`amount` sú najmenšie jednotky. `12.34 EUR` je `1234`.

```php
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

$money = Money::fromDecimalString('12,34', Currency::fromCode('EUR'), ',');
$money = Money::fromDecimalString('-1 234,56', 'EUR');
$money = Money::fromDecimalString('1.234,56', '978'); // číselné ISO → EUR

$money->toDecimalString(); // "-1234.56"
$money->format();          // "-1234.56 EUR"
$money->isPositive();
$money->isNegative();
$money->isZero();
$money->abs();
```

Prijímané desatinné reťazce sú `12.34`, `12,34`, `-1 234,56`, `1.234,56` a `+8`. Medzera, nezalomiteľná medzera, tenká medzera (U+2009), úzka nezalomiteľná medzera (U+202F) a tabulátor sú oddeľovače tisícov. Naviac desatinné číslice sa odmietnu, pokiaľ každá naviac nie je `0` (`1234,00` JPY je `1234`; `1234,01` JPY vyhodí výnimku). Zaokrúhľovanie nie je. Neplatný text vyhodí `InvalidArgumentException`.

## Currency

```text
Tomaj\BankMailsParser\ValueObject\Currency
```

`Currency` je `final class`, nie enum. `fromCode()` prijme každý aktívny abecedný kód ISO 4217 aj jeho číselný kód (`978` a `036` sú `EUR` a `AUD`) a k tomu stiahnuté `BGN` (`975`) a `HRK` (`191`). Aktívna tabuľka je zoznam ISO one zverejnený 17. 9. 2026. Krátky číselný kód sa doplní na tri číslice (`36` je `036`, AUD). Kódy s najmenšími jednotkami `N.A.` (vrátane `XXX`) používajú 2. `equals()` porovnáva abecedný kód. Identita objektu neprežije `unserialize`.

```php
use Tomaj\BankMailsParser\ValueObject\Currency;

Currency::fromCode('eur');    // EUR
Currency::fromCode('978');    // EUR
Currency::fromCode('208');    // DKK
Currency::fromCode('SEK');    // SEK, 2 najmenšie jednotky
Currency::fromCode('JPY')->minorUnits(); // 0
Currency::fromCode('CLF')->minorUnits(); // 4
Currency::tryFromCode('NO');  // null
Currency::fromCode('975'); // BGN, stiahnuté, ale prijaté pre starú poštu
Currency::fromCode('HRK'); // HRK, stiahnuté, ale prijaté pre starú poštu
```

`fromCode()` hodnotu oreže a prevedie na veľké písmená. Neznámy kód vyhodí `InvalidArgumentException`. `tryFromCode()` namiesto toho vráti `null`.

## BankMailsParser

```text
Tomaj\BankMailsParser\BankMailsParser
```

<!-- snippet: skip -->
```php
public function __construct(?array $parsers = null, ?TatraBankaMailDecryptor $decryptor = null)
public function registerParser(ParserInterface $parser): void
public function prependParser(ParserInterface $parser): void
public function parseAll(string $content, ?string $sender = null, ?string $subject = null): array
public function parse(string $content, ?string $sender = null, ?string $subject = null): MailContent
public function tryParse(string $content, ?string $sender = null, ?string $subject = null): ?MailContent
public function getParsers(): array
```

`null` zaregistruje vstavané parsery. `TatraBankaStatementMailParser` je v zozname len vtedy, keď je nastavený `$decryptor`. Pole, vrátane `[]`, použije presne ten zoznam. `prependParser()` vloží parser pred tie, ktoré už sú zaregistrované.

`parseAll()` vráti každú transakciu. `parse()` vráti jedinú transakciu. Viac ako jedna vyhodí `MultipleTransactionsException` a povie volajúcemu, aby použil `parseAll()`. `tryParse()` vráti `null` pri `ParseException` (vrátane tejto), `InvalidArgumentException`, `ValueError` a `DateMalformedStringException`. `TypeError` nezachytáva. `ConfigurationException` pustí ďalej. PGP správa bez dešifrátora je `UnsupportedBankException`, takže `tryParse()` vráti `null`.

Odovzdajte `$sender`. Keď je prítomné CardPay `TRES`, `$res` je táto hodnota a `$tres` ju uloží tiež. Knižnica neoveruje `HMAC`, `SIGN` ani DKIM.

Pozrite si [Rozpoznanie banky](/sk/guide/bank-detection).

## ParserInterface

<!-- snippet: skip -->
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

`parseAll()` vráti každú transakciu. `ParsesBankMail` ho implementuje ako `[$this->parse()]` a implementuje aj `tryParse()`. `bankDomains()` uvádza hostiteľov, ktorí sa použijú, keď volajúci odovzdá odosielateľa. Prázdny zoznam parser v tom prípade preskočí. Použite `$this->money()`, aby sa neplatná suma stala `InvalidEmailFormatException` a neunikla ako `InvalidArgumentException`. `BankDate` a `Iso4217` sú `@internal`.

```php
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

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

## Parsery

| Trieda | E-mail |
|-------|--------|
| `TatraBankaMailParser` | Notifikácie o zostatku v čistom texte |
| `TatraBankaSimpleMailParser` | Riadky ComfortPay / CardPay `KEY=value` |
| `TatraBankaStatementMailParser` | PGP výpisy (`parseMulti()`) a jeden riadok oddelený rúrou |
| `CsobMailParser` | ČSOB Česká republika |
| `SkCsobMailParser` | ČSOB Slovensko |
| `VubMailParser` | VÚB v čistom texte |

Hodnoty CardPay `TIMESTAMP` so 14 číslicami sú `dmYHis` v UTC (`26112016121631` je 26. november 2016 12:16:31). Hodnota s 10 číslicami je unixová časová pečiatka. Chýbajúca pečiatka je `null`. Neplatná pečiatka vyhodí `InvalidEmailFormatException`. Číselná mena `978` sa uloží ako `EUR`. Suma bez `CURR` nechá `money` ako null a ponechá `RES` aj `VS`. Neznáme `CURR` spraví to isté: notifikácia sa nezahodí. Slovenské a české bankové dátumy používajú `Europe/Bratislava`. Rok musí mať štyri číslice; `11.12.19` sa nestane rokom 19.

`TatraBankaStatementMailParser::parse()` na celom tele PGP dešifruje cez `parseAll()` a vráti prvú transakciu. Telo, ktoré nie je riadok oddelený rúrou, vyhodí `InvalidEmailFormatException`. `null` nevracia. `accountNumber` na riadku výpisu je stĺpec 19, hlavička `Číslo účtu` (vo vzorovom súbore uložené ako mojibake `Èíslo uètu`): účet obchodníka pre daný predaj kartou. Na každom riadku môže byť iný. Nie je to jeden účet pre celý výpis.

ČSOB `parseMulti()` a `parseAll()` výpisu majú jedno pravidlo. Text, ktorý nie je transakcia, sa ignoruje. Rozpoznaná transakcia, ktorá sa nedá prečítať, zhodí celú správu. Výnimka vymenúva zlé riadky (`transaction 2`, `row 2`). Nič, čo sedelo na transakciu, sa nezahodí.

Sumy VÚB bez kódu meny sa uložia ako EUR. Špecifický symbol sa číta z riadku `ŠS:`, `SS:` alebo `S:`. `VS:` a `KS:` sú vlastné polia.

## Výnimky

Menný priestor `Tomaj\BankMailsParser\Exception`. Každá trieda implementuje značkovacie rozhranie `BankMailsParserException` (od 5.1.0).

| Trieda | Dedí z | Kedy |
|--------|--------|------|
| `ParseException` | `RuntimeException` | Základný typ chýb spracovania |
| `UnsupportedBankException` | `ParseException` | E-mail nepodporuje žiadny zaregistrovaný parser |
| `AmbiguousBankException` | `ParseException` | E-mail prijme viac ako jeden parser |
| `MultipleTransactionsException` | `ParseException` | `parse()` našiel viac ako jednu transakciu |
| `InvalidEmailFormatException` | `ParseException` | Parser e-mail rozpoznal, ale nevedel ho prečítať |
| `DecryptionException` | `ParseException` | Dešifrovanie PGP zlyhalo |
| `ConfigurationException` | `RuntimeException` | Chýba súbor s kľúčom alebo je zlé heslo; `tryParse()` ju pustí ďalej |
| `InvalidArgumentException` | `\InvalidArgumentException` | Neplatná suma, oddeľovač alebo kód meny pre `Money` / `Currency` |

Pozri [Výnimky](/sk/guide/exceptions).

## Ďalšie kroky

- [Začíname](/sk/guide/getting-started)
- [Výnimky](/sk/guide/exceptions)
- [Pridanie novej banky](/sk/guide/adding-bank)
