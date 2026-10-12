# Výnimky

Neúspešné spracovanie je výnimka. `ParserInterface` aj `BankMailsParser` majú tú istú dvojicu metód:

| Metóda | Úspech | Neúspech |
|--------|--------|----------|
| `parse()` | `MailContent` | vyhodí výnimku |
| `tryParse()` | `MailContent` | `null` |

`tryParse()` vráti `null` pri neplatnom vstupe (`ParseException`, `InvalidArgumentException`, `ValueError`, `DateMalformedStringException`). `TypeError` nezachytáva. `ConfigurationException` pustí ďalej. Chýbajúci dešifrátor na predvolenej fasáde touto chybou nie je: parser výpisov sa neregistruje, správa je `UnsupportedBankException` a `tryParse()` vráti `null`.

Ak e-mail prijme viac ako jeden vhodný parser, fasáda vyhodí `AmbiguousBankException` ešte pred čítaním. Prvú zhodu si nenechá. Jediná zhoda, ktorá vyhodí `InvalidEmailFormatException`, sa pustí ďalej. Akákoľvek iná `ParseException` prehľadávanie zastaví, takže zlyhanie konfigurácie sa neschová za neskorší parser.

## Hierarchia

```text
RuntimeException
├── ConfigurationException
└── ParseException
    ├── UnsupportedBankException
    ├── InvalidEmailFormatException
    ├── AmbiguousBankException
    ├── MultipleTransactionsException
    └── DecryptionException
```

### ParseException

Základný typ chýb spracovania. Zachyťte ju, keď má každá taká chyba ísť tou istou cestou.

### UnsupportedBankException

Vyhadzuje ju `BankMailsParser::parse()`, keď e-mail nepodporuje žiadny zaregistrovaný parser.

- `UnsupportedBankException::noParserFound()` – odosielateľ nebol k dispozícii
- `UnsupportedBankException::withSender(?string $sender)` – povie, že k zadanému odosielateľovi sa nenašiel parser, a adresu do textu správy nedá

### AmbiguousBankException

Vyhadzuje ju `BankMailsParser::parse()`, keď ten istý e-mail prijme dva alebo viac parserov. `tryParse()` vráti `null`. `AmbiguousBankException::forParsers()` uvedie triedy. Odosielateľa odovzdajte, aby veta, ktorú ovláda platiteľ, nemohla spustiť druhý parser.

### MultipleTransactionsException

Vyhadzuje ju `BankMailsParser::parse()`, keď `parseAll()` vráti viac ako jednu transakciu. Text výnimky hovorí, že treba zavolať `parseAll()`. `tryParse()` vráti `null`. Typický prípad je e-mail ČSOB a výpis TatraBanky.

### InvalidEmailFormatException

Vyhodí sa, keď parser e-mail spozná a potom ho nevie prečítať.

- `InvalidEmailFormatException::missingRequiredField(string $parserClass, string $fieldName)`
- `InvalidEmailFormatException::malformedContent(string $parserClass, string $reason)`

Jednotlivé parsery ju hádžu z `parse()`. Fasáda pustí poslednú takú výnimku ďalej, keď parser e-mail podporoval, ale každý pokus zlyhal.

`TatraBankaStatementMailParser` balí `DecryptionException` do `InvalidEmailFormatException` a pôvodnú výnimku necháva v `$previous`. Chýbajúci dešifrátor, chýbajúci alebo nečitateľný súbor kľúča a zlé heslo kľúča hádžu `ConfigurationException`. `tryParse()` ju pustí ďalej. `parseAll()` vráti každý riadok výpisu, alebo vyhodí jednu výnimku so zoznamom riadkov, ktoré nešlo prečítať.

## Príklady

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\ParseException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;

$parser = new BankMailsParser();

try {
    $mailContent = $parser->parse($emailBody, $sender, $subject);
} catch (UnsupportedBankException $exception) {
    // Neznáma banka.
} catch (InvalidEmailFormatException $exception) {
    // Známy tvar, polia sa nedajú prečítať.
} catch (ParseException $exception) {
    // Ľubovoľné zlyhanie spracovania.
}

if ($parser->tryParse($emailBody) === null) {
    // Tie isté zlyhania, bez výnimky.
}
```

Neplatná suma alebo mena, ktorú vyhodí skladanie objektu `Money`, je `InvalidArgumentException`, nie `ParseException`. Parsery, ktoré prijímajú sumu v tvare pre používateľa, túto výnimku zachytia a vyhodia `InvalidEmailFormatException`, takže `tryParse()` aj tak vráti `null`.

## Čo nie je typ výnimky

Enum typu transakcie neexistuje. Príchodzia a odchádzajúca platba je znamienko `Money::$amount`. Bankové e-maily smer nedávajú tak spoľahlivo, aby na to stačil samostatný typ.
