# Pridanie novej banky

Táto príručka opisuje, ako do knižnice pridať podporu novej banky.

## Prehľad

Nová banka znamená:

1. Vytvoriť triedu parsera, ktorá implementuje `ParserInterface`
2. Napísať regulárne výrazy, ktoré vyberú údaje o transakcii
3. Napísať dôkladné testy
4. Parser zdokumentovať

## Krok 1: Trieda parsera

Nový parser patrí do `src/Parser/YourBank/YourBankMailParser.php`:

```php
<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\YourBank;

use DateTimeImmutable;
use DateTimeZone;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

final readonly class YourBankMailParser implements ParserInterface
{
    use ParsesBankMail;

    #[\Override]
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return str_contains($content, 'Transaction amount:');
    }

    public function parse(string $content): MailContent
    {
        $pattern = '/Transaction amount: ([\d.]+) ([A-Z]{3})/';
        if (preg_match($pattern, $content, $result) !== 1) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Amount not found');
        }

        return new MailContent(
            money: $this->money($result[1], $result[2], '.'),
            accountNumber: $this->extractAccountNumber($content),
            vs: $this->extractVariableSymbol($content),
            transactionDate: $this->extractTransactionDate($content),
        );
    }

    private function extractAccountNumber(string $content): ?string
    {
        $pattern = '/Account: ([A-Z0-9]+)/';
        $res = preg_match($pattern, $content, $result);
        
        return $res === 1 ? $result[1] : null;
    }
    
    private function extractVariableSymbol(string $content): ?string
    {
        $pattern = '/Reference: (\d+)/';
        $res = preg_match($pattern, $content, $result);
        
        return $res === 1 ? $result[1] : null;
    }
    
    private function extractTransactionDate(string $content): ?DateTimeImmutable
    {
        $pattern = '/Date: ([\d.]+)/';
        if (preg_match($pattern, $content, $result) !== 1) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!j.n.Y', $result[1], new DateTimeZone('Europe/Bratislava'));

        return $date instanceof DateTimeImmutable ? $date : null;
    }
}
```

## Krok 2: ParserInterface

```php
public function supports(string $content, ?string $sender = null, ?string $subject = null): bool;
public function parseAll(string $content): array;
public function bankDomains(): array;
```

`parseAll()` vráti každú transakciu. `ParsesBankMail` dodáva `parseAll()`, `tryParse()` a `money()`. Tieto tri plus `bankDomains()` sú podporovaný povrch. `BankDate` a ostatné metódy traitu sú `@internal`. `tryParse()` vráti `null` pri zlom e-maile a `ConfigurationException` pustí ďalej. `TypeError` nezachytáva. `bankDomains()` implementujte s hostiteľmi banky. Prázdny zoznam znamená, že sa parser preskočí vždy, keď volajúci odovzdá odosielateľa, takže vlastný parser s `[]` je v tom prípade neviditeľný. Odosielateľa odovzdajte z aplikácie. Parser zaregistrujte na `BankMailsParser`, keď sa má zúčastniť rozpoznania. Pozrite si [Rozpoznanie banky](/sk/guide/bank-detection).

## Krok 3: Viac transakcií (voliteľné)

Prepíšte `parseAll()`. Transakciu, ktorá sedela a potom zlyhala, nezahadzujte. Zlyhania zozbierajte a vyhoďte jednu `InvalidEmailFormatException`, ktorá ich vymenúva. Text, ktorý nie je transakcia, sa dá preskočiť.

```php
public function parseAll(string $content): array
{
    // Hlavičku ukotvite na začiatok riadku. Nerobte explode() na frázu, ktorú vie napísať platiteľ.
    $parts = preg_split('/^Transaction date:/m', $content);
    if ($parts === false) {
        throw InvalidEmailFormatException::malformedContent(self::class, 'Could not split transactions');
    }

    $parsed = [];
    $errors = [];
    $index = 0;
    foreach ($parts as $part) {
        if (!str_contains($part, 'Transaction amount:')) {
            continue;
        }
        ++$index;
        try {
            $parsed[] = $this->parse('Transaction date:' . $part);
        } catch (InvalidEmailFormatException $exception) {
            $errors[] = 'transaction ' . $index . ': ' . $exception->getMessage();
        }
    }
    if ($errors !== []) {
        throw InvalidEmailFormatException::malformedContent(
            self::class,
            'Could not parse every transaction: ' . implode('; ', $errors),
        );
    }

    return $parsed;
}
```

## Krok 4: Testy

Vytvorte `tests/Parser/YourBank/YourBankMailParserTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\YourBank;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\YourBank\YourBankMailParser;

#[CoversClass(YourBankMailParser::class)]
class YourBankMailParserTest extends TestCase
{
    #[Test]
    public function parsesBasicTransaction()
    {
        $email = 'Dear Customer,
        
Transaction amount: 150.00 EUR
Account: SK9999999999999999999999
Reference: 1234567890
Date: 15.01.2024

Best regards,
Your Bank';
        
        $parser = new YourBankMailParser();
        $mailContent = $parser->parse($email);

        $this->assertSame('150.00', $mailContent->money?->toDecimalString());
        $this->assertSame('EUR', $mailContent->money?->currency->value);
        $this->assertEquals('SK9999999999999999999999', $mailContent->accountNumber);
        $this->assertEquals('1234567890', $mailContent->vs);
    }
    
    #[Test]
    public function returnsNullForInvalidFormat()
    {
        $parser = new YourBankMailParser();

        $this->assertNull($parser->tryParse('This is not a valid bank email'));
    }
}
```

## Krok 5: Pokrytie testami

Testy majú pokryť:

- úspešné spracovanie so všetkými poľami
- úspešné spracovanie len s povinnými poľami
- neplatný formát e-mailu (`tryParse()` vráti `null`, `parse()` vyhodí výnimku)
- hraničné prípady (špeciálne znaky, biele znaky a podobne)
- viac transakcií, ak to parser podporuje
- varianty formátu dátumu
- varianty formátu sumy

Spustenie testov:

```bash
composer test
```

## Krok 6: Statická analýza

PHPStan musí prejsť:

```bash
composer phpstan
```

Opravte typové chyby alebo doplňte správne anotácie PHPStan.

## Krok 7: Štýl kódu

Štýl skontroluje PHP-CS-Fixer:

```bash
composer cs
```

Automatická oprava:

```bash
composer cs-fix
```

## Krok 8: Dokumentácia

Doplňte dokumentáciu parsera:

1. Vytvorte `docs/guide/yourbank.md` s príkladmi použitia a slovenskú stránku `docs/sk/guide/yourbank.md`
2. Doplňte položku do anglického aj slovenského bočného menu v `docs/site-nav.ts`
3. Podľa potreby aktualizujte hlavný README.md

## Osvedčené postupy

### Regulárne výrazy

- Keď to pomôže, použite **pomenované skupiny**
- Vzory nech sú **čo najkonkrétnejšie**
- Testujte na skutočných e-mailoch banky
- Počítajte s **rôznymi bielymi znakmi**

### Typová bezpečnosť

- Používajte **prísne typy** (`declare(strict_types=1)`)
- Používajte **prísne porovnanie** (`===` namiesto `==`)
- Návratovú hodnotu `preg_match()` kontrolujte cez `=== 1`
- S hodnotami `null` pracujte zámerne

### Spracovanie chýb

- Z `parse()` vyhoďte `InvalidEmailFormatException`, keď chýbajú povinné polia
- `tryParse()` pri tých istých zlyhaniach vráti `null`
- Implementujte `supports()`, aby `BankMailsParser` vedel parser vybrať

### Testovanie

- Používajte **skutočné vzorky e-mailov** (anonymizované)
- **Hraničné prípady** otestujte dôkladne
- Cieľte na **pokrytie príkazov** aspoň 97 %
- Počítajte aj s **mutačným testovaním**

## Príklad: úplný parser

Úplné príklady sú existujúce parsery:

- **Jednoduchý formát**: [VubMailParser](/sk/guide/vub)
- **Viac transakcií**: [CsobMailParser](/sk/guide/csob-cz)
- **Zložitý formát**: [TatraBankaMailParser](/sk/guide/tatrabanka)

## Odoslanie parsera

1. Vytvorte fork repozitára
2. Založte vetvu pre funkciu
3. Implementujte parser a testy
4. Všetky kontroly kvality musia prejsť
5. Otvorte pull request

Podrobnosti sú v [návode, ako prispieť](/sk/contributing).

## Ďalšie kroky

- Vlastnosti `MailContent` sú v [referencii API](/sk/api/reference)
- V existujúcich parseroch sú vzory a osvedčené postupy
- Prečítajte si [návod, ako prispieť](/sk/contributing)
