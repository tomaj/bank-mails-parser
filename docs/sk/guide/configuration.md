# Konfigurácia

Knižnica nemá konfiguračný súbor ani globálny stav. Všetko je argument konštruktora alebo metódy a všetko je uvedené tu.

## BankMailsParser

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;

$parser = new BankMailsParser(
    parsers: null,      // null: vstavané parsery; zoznam: presne tieto parsery
    decryptor: null,    // TatraBankaMailDecryptor zaregistruje parser PGP výpisov
);
```

| Argument | Typ | Predvolená hodnota | Význam |
|----------|-----|--------------------|--------|
| `$parsers` | `?list<ParserInterface>` | `null` | `null` zaregistruje vstavané parsery v tomto poradí: `TatraBankaMailParser`, `TatraBankaSimpleMailParser`, `TatraBankaStatementMailParser` (len s dešifrátorom), `CsobMailParser`, `SkCsobMailParser`, `VubMailParser`. Pole, aj `[]`, zaregistruje presne tieto parsery a nič iné. |
| `$decryptor` | `?TatraBankaMailDecryptor` | `null` | Použije sa len vtedy, keď je `$parsers` `null`: predvolený zoznam potom obsahuje `TatraBankaStatementMailParser` s týmto dešifrátorom. Bez neho je šifrovaný výpis nepodporovaný e-mail. |

| Metóda | Význam |
|--------|--------|
| `registerParser(ParserInterface $parser): void` | Pridá parser na koniec |
| `prependParser(ParserInterface $parser): void` | Vloží parser pred už zaregistrované |
| `getParsers(): list<ParserInterface>` | Zaregistrované parsery v poradí |

Poradie zoznamu je dôležité len pre `getParsers()`: rozpoznanie sa opýta každého vhodného parsera a odmietne vyberať, keď ich odpovie áno viac. Pozrite si [Rozpoznanie banky](./bank-detection).

## Argumenty spracovania

`parse()`, `tryParse()` a `parseAll()` na fasáde berú rovnaké tri argumenty:

| Argument | Typ | Predvolená hodnota | Význam |
|----------|-----|--------------------|--------|
| `$content` | `string` | | Dekódované textové telo e-mailu (nie surový MIME zdroj). CRLF, úvodný BOM a neplatné UTF-8 bajty v telách Tatra banky a CardPay sa ošetria. |
| `$sender` | `?string` | `null` | Adresa odosielateľa, so zobrazovaným menom alebo bez neho (`"Tatra banka" <b-mail@tatrabanka.sk>` funguje). Keď je nastavená, opýtajú sa len parsery, ktorých `bankDomains()` obsahuje hostiteľa odosielateľa alebo jeho nadradenú doménu, a parser bez domén sa preskočí. `null` alebo prázdny reťazec sa opýta každého parsera. **Vždy ho odovzdajte.** |
| `$subject` | `?string` | `null` | Číta ho len `TatraBankaMailParser`: e-mail, ktorého predmet obsahuje `tatra` (alebo ktorého odosielateľ je `tatrabanka.sk`) a ktorého telo obsahuje `zostatok Vasho uctu`, je podporovaný, aj keď hlavička nie je na začiatku riadku. |

Domény vstavaných parserov:

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
    passphrase: 'z vášho úložiska tajomstiev',
));
```

| Argument | Typ | Význam |
|----------|-----|--------|
| `$privateKeyPath` | `string` | Cesta k súkromnému kľúču OpenPGP v ASCII armor, pre ktorého verejnú časť Tatra banka výpis šifruje. Číta sa pri každom volaní `decrypt()`, nie v konštruktore. |
| `$passphrase` | `string` | Heslo kľúča |

Chýbajúci, nečitateľný alebo prázdny súbor kľúča a nesprávne heslo vyhodia `ConfigurationException` pri spracovaní výpisu, aj z `tryParse()`. Správa, ktorú sa nepodarí dešifrovať platným kľúčom, vyhodí `DecryptionException` a parser výpisov ju zabalí do `InvalidEmailFormatException`. Pozrite si [príklad šifrovaného výpisu](/sk/examples/encrypted-statement).

## Čo sa nastaviť nedá

Tieto veci zámerne nie sú konfigurovateľné:

| Čo | Hodnota |
|----|---------|
| Časové pásmo dátumov slovenských a českých bánk | `Europe/Bratislava`, nezávisle od `date.timezone` |
| Časové pásmo 14-ciferného `TIMESTAMP` z CardPay | UTC |
| Desatinný oddeľovač | `,` pre notifikácie Tatra banky, ČSOB a VÚB; `.` pre CardPay a výpisy |
| Mena bez kódu | EUR pri `Suma:` z VÚB a pri CardPay bez `CURR` |
| Meny | Každý platný kód ISO 4217 a stiahnuté `BGN` a `HRK` |
| Symboly | VS, SS a KS sú reťazce číslic; úvodné nuly zostávajú |

## Požiadavky

| Požiadavka | Prečo |
|------------|-------|
| PHP 8.4 alebo novšie | Testované na 8.4, 8.5 a 8.6 |
| `ext-mbstring` | `mb_trim()` a práca s UTF-8 |
| `singpolyma/openpgp-php` | Inštaluje sa s balíkom; dešifruje výpisy v čistom PHP |
