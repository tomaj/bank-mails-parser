# Ako prispieť do Bank Mails Parser

Ďakujeme za záujem. Tento dokument zhŕňa, ako do projektu prispieť.

## Obsah

- [Kódex správania](#kodex-spravania)
- [Začíname](#zaciname)
- [Vývojový postup](#vyvojovy-postup)
- [Pravidlá pre kód](#pravidla-pre-kod)
- [Požiadavky na testy](#poziadavky-na-testy)
- [Pridanie parsera novej banky](#pridanie-parsera-novej-banky)
- [Postup pri pull requeste](#postup-pri-pull-requeste)

## Kódex správania

Projekt sa riadi [Kódexom správania Contributor Covenant](https://github.com/tomaj/bank-mails-parser/blob/master/CODE_OF_CONDUCT.md). Účasťou sa zaväzujete ho dodržiavať.

## Začíname

### Čo budete potrebovať

- PHP 8.4 alebo novšie
- Composer
- Git

### Príprava prostredia

```bash
# Naklonujte repozitár
git clone https://github.com/tomaj/bank-mails-parser.git
cd bank-mails-parser

# Nainštalujte závislosti
composer install

# Spustite testy. phpunit.xml.dist vždy zapíše report pokrytia, takže
# treba pcov alebo xdebug. Bez ovládača pokrytia PHPUnit skončí s kódom 1
# a hlási „No tests executed!“.
composer test

# Skontrolujte štýl kódu
composer cs

# Spustite statickú analýzu
composer phpstan

# Spustite všetky kontroly
composer check
```

## Vývojový postup

1. **Vytvorte fork** a vetvu pre zmenu
2. **Urobte zmeny** podľa pravidiel pre kód
3. **Doplňte testy** k novej funkcii
4. **Spustite kontroly kvality** lokálne, ešte pred pushom
5. **Otvorte pull request** s jasným opisom

### Názvy vetiev

Názov vetvy nech hovorí, čo obsahuje:

- `feature/add-bank-xyz` – nová funkcia
- `bugfix/fix-parser-issue` – oprava chyby
- `docs/update-readme` – úprava dokumentácie

## Pravidlá pre kód

### Štandardy PHP

- **Verzia PHP**: minimum 8.4, používajte súčasné možnosti (final readonly triedy, typované konštanty, `#[\Override]`, `mb_trim`)
- **Nemennosť**: MailContent je readonly. Parsery ho vytvárajú konštruktorom a nikdy ho nemenia
- **Štýl kódu**: PER-CS a migračné pravidlá PHP 8.4 cez PHP-CS-Fixer
- **Typová bezpečnosť**: zapnuté prísne typy, úplné typové deklarácie
- **Dokumentácia**: PHPDoc pre verejné API

### Nástroje kvality

```bash
# Automaticky opraví štýl kódu
composer cs-fix

# Statická analýza
composer phpstan

# Mutačné testy. Musí to byť xdebug: `composer infection` nastaví
# XDEBUG_MODE=coverage. pcov hlási falošne nižšie MSI (iný výber testov)
# a nie je to číslo, ktoré kontroluje CI.
composer infection
```

### Požiadavky na kvalitu

- Všetky testy musia prejsť (PHPUnit)
- Pokrytie kódu má rásť, nie klesať
- PHPStan na úrovni max s prísnymi pravidlami
- Súlad so štýlom (PHP-CS-Fixer s PER-CS a @PHP84Migration)
- Mutation Score Indicator (MSI) ≥ 89 %, covered MSI ≥ 90 %

## Požiadavky na testy

### Jednotkové testy

Každý parser potrebuje testy, ktoré pokryjú:

- **Platné formáty e-mailov** – bežná cesta
- **Hraničné prípady** – okrajové podmienky, špeciálne znaky
- **Chyby** – poškodené e-maily, chýbajúce polia
- **Viac kódovaní** – UTF-8, špeciálne znaky
- **E-maily s viacerými transakciami** – ak prichádzajú do úvahy

### Príklad testu

```php
#[Test]
public function parsesValidEmail(): void
{
    $parser = new YourBankMailParser();
    $content = 'email content here';
    
    $result = $parser->parse($content);

    $this->assertSame('123.45', $result->money?->toDecimalString());
    $this->assertSame('EUR', $result->money?->currency->value);
}
```

### Požiadavky na pokrytie

- **Celkové pokrytie**: cieľ je aspoň 97 % príkazov
- **Nové parsery**: vyžaduje sa 100 % pokrytie
- **Hraničné prípady**: musia byť testy ciest, ktoré zlyhajú

## Pridanie parsera novej banky

### 1. Pozrite si existujúce parsery

Implementácie sú v `src/Parser/`:

- `TatraBanka/` – viac typov parserov
- `Csob/` – podpora viacerých transakcií
- `Vub/` – jednoduchý príklad parsera

### 2. Vytvorte triedu parsera

```php
<?php
declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\YourBank;

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

final readonly class YourBankMailParser implements ParserInterface
{
    use ParsesBankMail;

    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return false;
    }

    public function parse(string $content): MailContent
    {
        throw new \Tomaj\BankMailsParser\Exception\InvalidEmailFormatException('Unrecognized email');
    }
}
```

### 3. Doplňte dôkladné testy

Vytvorte `tests/Parser/YourBank/YourBankMailParserTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Parser\YourBank;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\YourBank\YourBankMailParser;

class YourBankMailParserTest extends TestCase
{
    #[Test]
    public function parsesValidEmail(): void
    {
        // Sem doplňte testy
    }
}
```

### 4. Testovacie dáta

Anonymizované správy patria do `tests/fixtures/<bank>/<descriptive-name>.txt` (`tatrabanka`, `tatrabanka-cardpay`, `tatrabanka-statement`, `csob-cz`, `csob-sk`, `vub`). Názov je kebab-case (`zahranicna-platba-sepa.txt`, `utok-fantom-adresa.txt`). Úplný očakávaný výstup dajte do súrodeneckého `<descriptive-name>.expected.json`. Jeden snapshot test číta každé `tests/fixtures/**/*.txt` a zlyhá, keď očakávaný súbor chýba alebo sa líši.

```bash
composer fixtures:update
```

Príkaz znovu vygeneruje každé `.expected.json`. Diffy v pull requeste skontrolujte rovnako ako produkčný kód. Správa patrí do repozitára raz. Krátke syntetické úryvky na pár riadkov môžu ostať priamo v jednotkovom teste. Skutočné osobné údaje odstráňte.

### 5. Vydanie

Značky nemajú predponu `v` (`5.0.0`). Pred označením premenujte `## [Unreleased]` na `## [5.0.0] - YYYY-MM-DD` v `CHANGELOG.md` aj v `docs/changelog.md` a oba porovnávacie odkazy z `[Unreleased]: …/compare/4.1.0...HEAD` na `[5.0.0]: …/compare/4.1.0...5.0.0`. Sekcia s poznámkami k vydaniu nesmie obsahovať pokyny pre správcu.

### 6. Aktualizujte dokumentáciu

Aktualizujte `README.md`:

- doplňte banku do tabuľky podporovaných bánk
- doplňte príklad použitia
- opíšte osobitné funkcie

Anglickú stránku dajte do `docs/` a slovenskú do `docs/sk/`. Položky bočného menu sú v `docs/site-nav.ts`.

## Postup pri pull requeste

### Pred odoslaním

Spustite kontroly kvality:

```bash
composer check      # Spustí cs, phpstan a test
composer infection  # xdebug cez XDEBUG_MODE=coverage; pcov dáva falošne nižšie MSI
```

Všetky kontroly musia lokálne prejsť.

### Kontrolný zoznam pull requestu

- [ ] Testy pre novú funkciu sú doplnené
- [ ] Všetky testy lokálne prechádzajú
- [ ] Štýl kódu (PHP-CS-Fixer) sedí
- [ ] PHPStan na úrovni max prechádza
- [ ] Dokumentácia je aktualizovaná (README, CHANGELOG, anglická aj slovenská verzia)
- [ ] Pokrytie kódu nekleslo
- [ ] Správy commitov sú jasné a výstižné

### Opis pull requestu

Uveďte:

1. **Účel**: Aký problém to rieši?
2. **Zmeny**: Čo sa zmenilo?
3. **Testovanie**: Ako sa to testovalo?
4. **Formát e-mailu**: Pri novom parseri opíšte štruktúru e-mailu
5. **Zlomové zmeny**: Ak nejaké sú (zvýšte hlavnú verziu)

### Kontrola

- Správca sa ozve do 48 až 72 hodín
- Pripomienky zapracujte v samostatných commitoch (nie cez force-push)
- Po schválení správca zmeny zlisuje a zlúči

## Správy commitov

Používajte conventional commits:

```
feat: add parser for Bank XYZ
fix: handle null amounts in CSOB parser
docs: update README with new bank support
test: add edge case for VUB parser
refactor: improve regex patterns in TatraBanka parser
```

Zlomové zmeny:

```
feat!: change parser return type

BREAKING CHANGE: parsers now return null instead of false
```

## Pokyny ku kontrole kódu

### Pre prispievateľov

- Reagujte na pripomienky
- Pýtajte sa, keď niečo nie je jasné
- Pull requesty nechajte úzke a malé

### Pre tých, ktorí kontrolujú

- Buďte vecní a slušní
- Sledujte kvalitu kódu, nie osobný vkus na štýl
- Navrhujte vylepšenia, nepožadujte dokonalosť

## Otázky alebo problémy?

- **Hlásenie chyby**: otvorte issue s postupom, ako ju zopakovať
- **Návrh funkcie**: otvorte issue s opisom použitia
- **Otázky**: otvorte diskusiu alebo napíšte na tomasmajer@gmail.com

## Licencia

Prispením súhlasíte s tým, že váš príspevok bude licencovaný pod licenciou MIT.

---

Ďakujeme, že prispievate.
