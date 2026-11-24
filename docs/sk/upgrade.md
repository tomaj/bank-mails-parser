---
title: Prechod zo 4.x na 5.0
description: "Každá nekompatibilná zmena medzi bank-mails-parser 4.x a 5.0 a kód, ktorý treba zmeniť."
---

# Prechod zo 4.x na 5.0

Toto je preklad súboru [UPGRADE-5.0.md](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).

## Požiadavky

Minimálne PHP je **8.4**. PHP 8.2 a 8.3 podporované nie sú. PHP 8.5 podporované je.

```diff
- "php": ">=8.2"
+ "php": "^8.4",
+ "ext-mbstring": "*"
```

`ext-mbstring` je povinné. Parsery volajú `mb_trim()`.

## 1. Money nahrádza float sumu a reťazec meny

4.x poskytovala `$mailContent->getAmount()` (`float`) a `$mailContent->getCurrency()` (`string`).

5.0 ukladá oboje do `MailContent::$money`:

```php
$mailContent->money?->amount;            // int najmenšie jednotky, 1234 pre 12.34
$mailContent->money?->toDecimalString(); // "12.34"
$mailContent->money?->currency->value;   // "EUR"
$mailContent->money?->format();          // "12.34 EUR"
```

Hodnoty vytvárajte cez `Money::fromDecimalString()`. Prvý argument je desatinný reťazec (`"12.34"`, `"12,34"`, `"-1 234,56"`, `"1.234,56"`). Druhý argument je `Currency` alebo kód ISO, aj číselný, napríklad `"978"`.

Verejné API nemá žiadnu float sumu, žiadne `getDecimalAmount()` a žiadnu vlastnosť `amountInMinorUnits`. Celočíselná vlastnosť je `amount`.

Gettery a settery zo 4.x zmizli. Vrstva kompatibility neexistuje.

## 2. Dátumy sú DateTimeImmutable

4.x používala `getTransactionDate(): int|false|null`.

5.0 používa `?DateTimeImmutable`:

```php
$mailContent->transactionDate?->format('Y-m-d H:i:s');
$mailContent->transactionDate?->getTimestamp();
```

`null` znamená, že e-mail nemal použiteľný dátum. `false` sa už nevracia. `getTransactionDateTimestamp()` neexistuje.

**Nekompatibilná zmena pre servery v UTC.** E-maily slovenských a českých bánk sa čítajú ako miestny čas `Europe/Bratislava`, nie podľa `date.timezone`. Na serveri, ktorého `date.timezone` je UTC, sa každý časový údaj zo 4.x posunie o **+1 hodinu** (SEČ) alebo **+2 hodiny** (SELČ). Okamih je miestny čas banky. 14-ciferné hodnoty CardPay `TIMESTAMP` zostávajú v UTC (`dmYHis`). 10-ciferná hodnota je Unix timestamp.

Chýbajúci CardPay timestamp je `null`. 4.x použila `time()`. `null` je bezpečnejšie: „teraz“ sa medzi čítaniami mení a nedá sa overiť. Neplatné kalendárne dátumy zostanú `null`: `31.2.2015` sa neposunie dopredu, chýbajúci bratislavský čas ako `29.3.2015 2:30` zostane null a dvojciferný rok zostane null. `16.1.2015 24:00` je nasledujúci deň o `00:00`, ako vo verzii 4.x. `TIMESTAMP=99999999999999` vyhodí `InvalidEmailFormatException`.

Tatra prijme číselné formáty z 4.1.0 (`j.n.Y`, s časom aj sekundami, a `Y-m-d`; medzery okolo bodiek sú povolené) a anglický mesiac tak, ako to robilo `strtotime()` (`16 January 2015 12:51`). `31 February` zostane null. Neplatné UTF-8 v tele Tatra banky alebo CardPay sa vyčistí (`mb_scrub`) a e-mail sa spracuje. Kvôli zlým bajtom sa neodmietne.

## 3. MailContent je nemenný

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

Každá reťazcová vlastnosť sa oreže. Prázdny reťazec sa zmení na `null`, aj pri `accountNumber`, `sourceAccountNumber`, `ownAccountNumber`, `receiverMessage` a `description`.

| Getter 4.x | 5.0 |
|------------|-----|
| `getAmount()` | `$mailContent->money?->toDecimalString()` |
| `getCurrency()` | `$mailContent->money?->currency->value` |
| `getTransactionDate()` | `$mailContent->transactionDate` |
| `getAccountNumber()` | `$accountNumber` (účet platiteľa pri ČSOB SK a VÚB, rovnako ako 4.x) |
| `getSourceAccountNumber()` | `$sourceAccountNumber` |
| — | `$ownAccountNumber` (nové: ČSOB SK `na účte`, VÚB `Na účet`) |
| `getVs()` / `getSs()` / `getKs()` | `$vs` / `$ss` / `$ks` |
| `getReceiverMessage()` | `$receiverMessage` |
| `getDescription()` | `$description` |
| `getCid()` / `getSign()` / `getRes()` | `$cid` / `$sign` / `$res` |
| `getAc()` / `getCc()` / `getTid()` | `$ac` / `$cc` / `$tid` |
| `getTxn()` / `getRc()` | `$txn` / `$rc` |

`accountNumber` si zachováva význam zo 4.x. Pri ČSOB SK je to `z účtu` (platiteľ). Pri VÚB je to `Z účtu` / `Z tu` (platiteľ). `sourceAccountNumber` zostáva pri týchto dvoch bankách null, ako vo verzii 4.x. Účet v banke, ktorá e-mail poslala, je nová vlastnosť `ownAccountNumber` (`na účte` pri ČSOB SK, `Na účet` / `Na et` pri VÚB). `accountNumber` pri ČSOB CZ je stále `na účtu` a `sourceAccountNumber` je stále `Účet protistrany`.

## 4. parse() vyhadzuje výnimky, tryParse() vracia null

`parse()` vo verzii 4.x vrátil `null`, keď e-mail nesedel.

`BankMailsParser::parse()` v 5.0 vráti jeden `MailContent` alebo vyhodí výnimku. Ak e-mail obsahuje viac ako jednu transakciu, vyhodí `MultipleTransactionsException` (je to `ParseException`) a správa odkáže na `parseAll()`. `tryParse()` potom vráti `null`. Pre ČSOB a pre súbory výpisov používajte `parseAll()`. `parseOrFail()` neexistuje.

`ParserInterface` tvoria `supports()`, `parseAll()` a `bankDomains()`. Trait na konkrétnej triede stále poskytuje `tryParse()` a `money()`.

`new BankMailsParser()` parser PGP výpisov nezaregistruje. Zaregistruje ho až odovzdaný `TatraBankaMailDecryptor`. Bez tohto dešifrátora je PGP e-mail `UnsupportedBankException` a `tryParse()` vráti `null`. Nie je to `ConfigurationException`. Dešifrátor, ktorý nevie otvoriť svoj kľúč, stále vyhodí `ConfigurationException`.

Pri každom volaní odovzdajte `$sender`. Parser s prázdnym `bankDomains()` sa pri nastavenom odosielateľovi preskočí. Dva parsery, ktoré prijmú to isté telo, vyhodia `AmbiguousBankException`.

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;

$parser = new BankMailsParser();

try {
    $mailContent = $parser->parse($emailBody, $sender, $subject);
} catch (UnsupportedBankException) {
    // e-mail neprijal žiadny parser
} catch (InvalidEmailFormatException) {
    // parser ho prijal a potom odmietol obsah
}

$mailContent = $parser->tryParse($emailBody, $sender, $subject);
```

`BankMailsParser` bez argumentov zaregistruje Tatra banku (obyčajné notifikácie a ComfortPay), ČSOB CZ, ČSOB SK a VÚB. Parser PGP výpisov sa zaregistruje len vtedy, keď konštruktor dostane dešifrátor. Odovzdajte pole, ak chcete používať presne tieto parsery.

## 5. Currency je hodnotový objekt ISO

`Currency` je `final class`, nie enum. `Currency::fromCode()` prijme každý platný abecedný kód ISO 4217 a jeho číselný kód (`EUR`, `978`, `208` pre DKK, `036` pre AUD, `SEK`) a navyše stiahnutý lev a kunu (`BGN` / `975`, `HRK` / `191`), aby sa dali znova spracovať staršie e-maily. `Currency::tryFromCode()` pre čokoľvek iné vráti `null`. Porovnávajte cez `equals()`. CardPay `CURR=978` sa uloží ako `EUR`. CardPay s neznámym `CURR` alebo s `AMT` bez `CURR` si ponechá `RES`, `VS` a ostatné polia a `money` nastaví na `null`. Notifikáciu nezahodí.

`minorUnits()` pochádza z tabuľky ISO: 0 pre JPY a ISK, 3 pre BHD, KWD, OMR a TND, 4 pre CLF a UYW a inak 2 (aj pre kódy, ktorých mierka v ISO je `N.A.`, napríklad `XXX`). Nadbytočné desatinné miesta sa odmietnu, ak nie sú všetky nulové, takže `1234,00` JPY je `1234` a `1234,01` JPY vyhodí výnimku. `fromDecimalString('1.234')` bez desatinného oddeľovača je nejednoznačné a vyhodí výnimku. Z Tatra banky, ČSOB a VÚB odovzdávajte `','`, z CardPay a výpisov Tatra banky `'.'`. Medzera, NBSP, U+2009, U+202F a tabulátor sú oddeľovače tisícov len v skupinách po troch (`1 234,56` sa prijme, `1 2 3,45` a `81 5` nie). Skupiny s bodkou a čiarkou majú rovnaký tvar (`1.234,56`). Hodnoty, ktoré sa nezmestia do celého čísla, vrátane `PHP_INT_MIN`, vyhodia `InvalidArgumentException`, ktorú parsery zabalia do `InvalidEmailFormatException`. `tryParse()` potom vráti `null`.

## 5a. Účty, symboly a rozpoznanie

`accountNumber` pri ČSOB SK je `z účtu` a pri VÚB `Z účtu` / `Z tu`. `ownAccountNumber` je `na účte` alebo `Na účet`. `sourceAccountNumber` je protistrana pri ČSOB CZ alebo Tatra banke. ČSOB SK číta `/SS…/`. VÚB použije EUR len vtedy, keď riadok so sumou menu neuvádza. Na výpise kariet Tatra banky je `accountNumber` stĺpec 19 (`Číslo účtu`), účet obchodníka pripísaný za daný predaj kartou. Na každom riadku môže byť iný. Nie je to jeden účet pre celý súbor.

Variabilný symbol zodpovedá verzii 4.1.0. Tatra použije `/VS…/SS…/KS…` a ponechá každú číslicu, aj viac ako 10, potom token `VS` kdekoľvek v e-maile, potom posledné číslo na riadku `Informacia pre prijemcu` (aj zo štruktúrovanej referencie veriteľa; tak to robila 4.1.0) a potom posledné číslo na holom riadku `Referencia platitela`. Tieto kroky sa použijú len vtedy, keď `Referencia platitela` nemá tvar `/VS…/SS…/KS…`. Prázdny riadok pred touto referenciou symbol nestratí. ČSOB CZ použije `Variabilní symbol` a preskočí hodnotu zo samých núl. `Identifikace` potom túto hodnotu nahradí, ako vo verzii 4.1.0. Ďalej nasleduje token `VS` alebo `v.s.`, posledné číslo na riadku `Zpráva příjemci` a `Účel platby`. `Účel platby` používa `\b([0-9]{1,10})\b` až po `Zůstatek`, takže číslo zlepené s písmenami (`AB12`, `INV2026`, `FV2024001`) nie je symbol. Bez `Zůstatek` nie je číslo v pätičke symbol. Viac ako 10 číslic pri ČSOB CZ je `null` (4.x symbol orezala, čo mohlo spárovať nesprávnu platbu). ČSOB SK číta `/VS`, `/SS` a `/KS` z `referencia platiteľa` bez obmedzenia na 10 číslic, potom prvé číslice `VS` alebo `KS` len na riadku `informácia pre príjemcu`, keď referencia tento symbol nenastavila (`VS111 nahrada za VS222` je `111`). Meno platiteľa ako `ADVS2` alebo `BKS1` nie je symbol.

VÚB číta `ŠS`, `SS` a `S` do `ss` (príklad zo 4.x `vub_payment_example.txt` má `ss` `910`). `SIGN` sa uloží do `$sign`. Overiť ho musí stále volajúci.

Úvodné medzery, tabulátory, NBSP, U+2009 a U+202F sú povolené pred popiskom a za dvojbodkou. Keď ČSOB CZ, ČSOB SK alebo VÚB transakciu rozpoznali a suma chýba, je nečitateľná alebo v neznámej mene, `money` je `null` a riadok sa vráti. Prázdne `Suma:` je `money = null`.

Správu pre príjemcu, `Identifikace`, `Účel platby`, `Adresa protistrany`, `Název protistrany`, `Reference plátce` a slovenské riadky `informácia pre príjemcu`, `názov protiúčtu` a `referencia platiteľa` píše platiteľ. Pole končí prázdnym riadkom alebo riadkom zo samých medzier, tabulátorov či NBSP. Ľubovoľné `Slovo:` ho neukončí. Pole skončí aj na riadku banky v presnom tvare tejto banky: celá veta so zostatkom, riadok symbolu len s číslicami alebo riadok s účtom. Holé `Zůstatek`, `zostatok` a `banka:` ho neukončia. Riadky vnútri poľa sa pri čítaní sumy, účtu a dátumu vymažú. E-mail neodmietnu. `Částka: záloha 2 500 Kč` a `Dne 1.10.2026 byla na účtu chyba, vracím` zostanú textom správy. Účet protistrany sa číta z riadku v tvare účtu, aj z takého, ktorý nasleduje za menom. `<br>`, `</p>` a `</div>` sa pred spracovaním ČSOB zmenia na nové riadky. `sourceAccountNumber` si ponechá len token účtu: text za ním, napríklad ` (Fio banka)`, sa zahodí, rovnako ako `<br>`. E-mail ČSOB s viacerými transakciami a aspoň jedným riadkom zostatku sa kontroluje: každá transakcia má presne jeden riadok zostatku, ten od banky, a predchádzajúci zostatok plus ďalšia suma sa musia rovnať ďalšiemu zostatku. Druhý presný riadok zostatku v tej istej transakcii je text platiteľa a spracovanie vyhodí výnimku. E-mail bez riadku zostatku môže obsahovať viac platieb, rovnako ako vo verzii 4.1.0. Je to známe obmedzenie: bez riadkov zostatku sa podvrhnutá transakcia za prázdnym riadkom nedá odlíšiť od skutočnej. Sumu overte cez API banky alebo podľa výpisu. Keď e-mail už obsahuje LF, osamotené CR, VT, FF, U+0085, U+2028 alebo U+2029 zostane obyčajným znakom a riadok nerozdelí. Na nové riadky sa tieto oddeľovače zmenia len vtedy, keď e-mail LF nemá. Hlavička ČSOB sa rozpozná na začiatku riadku a nepotrebuje pred sebou prázdny riadok. Pri párovaní platby skontrolujte aj sumu. `supports()` vráti false, keď sa vzor nedá vykonať. Výnimku nevyhodí.

Notifikácia Tatra banky alebo ČSOB, ktorá existuje len ako citovaná história (riadky začínajúce `>`), nie je platba. 4.1.0 niektoré z týchto riadkov spracovala. 5.0 nie.

Odovzdaný odosielateľ spôsobí, že `BankMailsParser` použije len parsery, ktorých `bankDomains()` obsahuje daného hostiteľa (`tatrabanka.sk`, `csob.cz`, `csob.sk`, `vub.sk`). Samotné `supports()` hostiteľa nevyžaduje. Neznámy hostiteľ vyhodí `UnsupportedBankException`. Ak telo prijme viac ako jeden vhodný parser, `parse()` vyhodí `AmbiguousBankException` a `tryParse()` vráti `null`. Fasáda si nenechá prvú zhodu.

`BankMailsParser::parseAll()` vráti každú transakciu. `parse()` vyhodí `MultipleTransactionsException`, keď ich je viac. Prázdne `parseAll()` vyhodí `InvalidEmailFormatException` namiesto čítania indexu 0. Výpisy ČSOB a Tatra banky nezahodia transakciu, ktorá sa rozpoznala a potom zlyhala: vyhodia jednu výnimku so zoznamom zlých riadkov. `parseMulti()` výpisu vráti tento zoznam alebo vyhodí výnimku; `null` nevracia. Na čítanie PGP výpisov predvolenými parsermi vytvorte `new BankMailsParser(decryptor: $decryptor)`. Bez dešifrátora sa parser výpisov nezaregistruje, takže PGP e-mail je `UnsupportedBankException` a `tryParse()` vráti `null`. Dešifrátor s chýbajúcim alebo nečitateľným kľúčom alebo s nesprávnym heslom vyhodí `ConfigurationException`, aj z `tryParse()`. `decrypt()` vracia `string` a vyhadzuje výnimky; `null` nevracia nikdy. CardPay číta prvý súvislý blok `KEY=value`, ktorý má `RES` alebo `TRES` a `AMT`, `VS`, `HMAC` alebo `SIGN`. Slovo na tomto riadku, ktoré nie je `KEY=value`, sa preskočí (`Vysledok: VS=… RES=OK …` sa spracuje). Neskorší riadok, ktorý mieša text, do bloku nepatrí. Riadky začínajúce `>` do bloku nepatria. Keď je prítomné `TRES`, `$res` je hodnota `TRES`, ako vo verzii 4.x, a `$tres` ju uloží tiež. Samotné `RES` vyplní `$res`. Chýbajúce `CURR` pri `AMT` je EUR. Neznáme `CURR` notifikáciu ponechá a `money` nastaví na `null`. Duplicitný kľúč s inou hodnotou vyhodí `InvalidEmailFormatException`. Keď sú prítomné `SIGN` aj `HMAC`, `$sign` je HMAC. Knižnica hodnotu uloží a nekontroluje ju. `supports()` pri VÚB vyžaduje dátum, oba popisky účtov, sumu, `VS` a `Stav` alebo `SIGN`, každé na začiatku svojho riadku. Ani jedna kontrola nedokazuje, kto e-mail poslal. DKIM overte sami.

## Rozdiely v správaní oproti 4.x

Tvar API (vlastnosti, `Money` / `Currency`, `DateTimeImmutable`, výnimky, fasáda) je uvedený vyššie. Toto sú rozdiely v poliach, ktoré zostali po zladení s 4.1.0 všade inde. Každý z nich je zámerný.

- **Časové pásmo.** Dátumy bánk sú `Europe/Bratislava`. Na serveri v UTC je každý slovenský alebo český časový údaj oproti 4.x o +1 h alebo +2 h. CardPay `TIMESTAMP` je `DateTimeImmutable` v UTC, nie surový 14-ciferný reťazec.
- **Neplatné dátumy sú null.** `31.2`, dvojciferný rok a medzera pri zmene času ako `29.3.2015 2:30` sa neposunú dopredu. `24:00` je nasledujúci deň o `00:00`. Dátum Tatra banky zapísaný ako `16 January 2015 12:51` sa spracuje. `31 February` zostane null.
- **Chýbajúci CardPay `TIMESTAMP` je null**, nie `time()`.
- **Citované riadky začínajúce `>`** nie sú platba. 4.x niektoré z nich zaúčtovala.
- **Orezanie.** Koncové CR a prázdne `AC=` sa zmenia na null.
- **ČSOB CZ.** `parse()` vráti dátum (4.x vrátila z parsera jednej správy `false`). `1.440,00` je 1440, nie 1.44. Správa so slovom `Dne` sa ponechá. `sourceAccountNumber` je len token účtu. Text za ním, napríklad ` (Fio banka)`, sa zahodí, rovnako ako `<br>`. Jednoriadkové HTML telo sa pred spracovaním rozdelí na `<br>`, `</p>` a `</div>`. VS/KS dlhšie ako 10 číslic sú null, nie orezané.
- **Hlavička Tatra banky.** Veta `bol zostatok Vasho uctu` musí začínať riadok (úvodné medzery sú povolené). `Dobry den, 16.1.2015 12:51 bol zostatok…` sa nespracuje. `parse()` vyhodí `InvalidEmailFormatException`. Rovnako sa odmietne suma s tretím nenulovým desatinným miestom, napríklad `12,315` EUR.
- **ČSOB SK.** `accountNumber` je `z účtu`. `ownAccountNumber` je `na účte` (nové). `ss` sa číta. VS a KS sa neberú z mena platiteľa. KS sa vráti k `informácia pre príjemcu`, keď referencia KS nemá. Viac ako 10 číslic sa ponechá.
- **VÚB.** Číta sa oddeľovač tisícov, úvodné mínus, dátum z UTF-8 `Dátum`, mena (EUR, keď ju riadok neuvádza), `ss` a `SIGN`. 4.x pri skutočnom UTF-8 e-maile nechala dátum a účet null a `1 234,90` prečítala ako 1. `accountNumber` je platiteľ. `ownAccountNumber` je `Na účet`.
- **Sumy Tatra banky a CardPay.** `1,234.50` je 1234.50. `12,315` (tri nenulové desatinné miesta) sa odmietne. `CURR=978` je `EUR`. Chýbajúce `CURR` je EUR. Neznáme `CURR` nechá `money` null. Neplatné UTF-8 bajty sa vyčistia a e-mail sa spracuje. Keď je `TRES` prítomné, vyhrá pre `res`; uloží sa aj `tres`. HMAC vyhrá nad SIGN. Použije sa prvý blok `KEY=value`, aj riadok, ktorý začína textom. Duplicitný kľúč CardPay s inou hodnotou vyhodí `InvalidEmailFormatException`.
- **Výpisy.** Sumy so skupinami číslic sa prečítajú. Neplatný dátum výpisu je null.
- **Text platiteľa a reťaz zostatkov.** Hlavička alebo suma napísaná do poľa platiteľa sa vymaže. Nestane sa z nej druhá transakcia a e-mail neodmietne. Keď má e-mail viac ako jednu transakciu a aspoň jeden riadok zostatku, každá transakcia potrebuje presne jeden zostatok a zostatky musia nadväzovať. Druhý riadok zostatku v tej istej transakcii vyhodí `InvalidEmailFormatException` so správou `Transaction has more than one balance line`. E-mail **bez** riadku zostatku stále vráti každú transakciu zo začiatku riadku, aj podvrhnutú, ktorá stojí za prázdnym riadkom. To zodpovedá 4.1.0 a je to známe obmedzenie.
- **Neznáma alebo nečitateľná suma** pri ČSOB CZ, ČSOB SK a VÚB: riadok sa vráti s `money` null. E-mail sa neodmietne.

`UnsupportedBankException::withSender()` e-mailovú adresu neobsahuje.

## 6. Vlastné parsery

`ParserInterface` tvoria `supports()`, `parseAll()` a `bankDomains()`. `parse()` implementujte na triede. `ParsesBankMail` poskytuje `parseAll()` (jedno volanie `parse()`), `tryParse()` a `bankDomains()` (prázdny zoznam, takže parser beží len vtedy, keď volajúci odosielateľa vynechá). Keď parser patrí k hostiteľovi, prepíšte `bankDomains()`. `parse()` vyhodí `InvalidEmailFormatException` namiesto vrátenia `null`. Sumy vytvárajte cez `$this->money()`, aby `InvalidArgumentException` neunikla. `MailContent` vytvárajte konštruktorom. Triedu označte `final readonly`. `#[\Override]` dajte na `supports()`, `parseAll()` a `bankDomains()`. Na `parse()` nie: táto metóda nie je v rozhraní. Vstavané parsery už sú `final readonly`, takže kód zo 4.x, ktorý dedil z `CsobMailParser`, sa už neskompiluje.

## 7. Vydanie 5.0.0

`CHANGELOG.md` si do značky ponechá aktuálny horný nadpis. Pred označením ho v `CHANGELOG.md` aj v `docs/changelog.md` premenujte na `## [5.0.0] - YYYY-MM-DD`. Porovnávací odkaz v oboch súboroch premenujte z `[Unreleased]: …/compare/4.1.0...HEAD` na `[5.0.0]: …/compare/4.1.0...5.0.0`. `release.yml` číta `## [5.0.0]` a nadpis pred vydaním odmietne. Telo sekcie nesmie obsahovať slová TBD ani Unreleased. Značky nemajú predponu `v` (`5.0.0`, rovnako ako `4.1.0`).

## 8. Čo zostalo mimo

- Žiadny enum `TransactionType`. Používajte `Money::isPositive()` a `Money::isNegative()`.
- Žiadna závislosť na moneyphp/money. Sumy sú celé čísla v najmenších jednotkách.
- `docs/4.x/` stále opisuje API getterov zo 4.x.

## Kontroly

```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
```
