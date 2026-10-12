# Pridanie novej banky (4.x)

::: warning Staršia verzia
Toto je dokumentácia verzie **4.x**. Aktuálna verzia s nemennou architektúrou je v [dokumentácii 5.x](/sk/). Postup prechodu je v [návode na prechod na 5.0](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
:::

Táto príručka opisuje, ako do verzie 4.x knižnice pridať podporu novej banky.

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

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

class YourBankMailParser implements ParserInterface
{
    public function parse(string $content): ?MailContent
    {
        // Výber povinného poľa transakcie
        $pattern = '/Transaction amount: ([\d.]+) ([A-Z]{3})/';
        $res = preg_match($pattern, $content, $result);
        
        if ($res !== 1) {
            return null; // Formát e-mailu sa nepodarilo spoznať
        }
        
        // Vytvorenie MailContent
        $mailContent = new MailContent();
        
        // Povinné polia
        $mailContent->setAmount((float) $result[1]);
        $mailContent->setCurrency($result[2]);
        
        // Voliteľné polia
        $accountNumber = $this->extractAccountNumber($content);
        if ($accountNumber !== null) {
            $mailContent->setAccountNumber($accountNumber);
        }
        
        $vs = $this->extractVariableSymbol($content);
        if ($vs !== null) {
            $mailContent->setVs($vs);
        }
        
        $transactionDate = $this->extractTransactionDate($content);
        if ($transactionDate !== null) {
            $mailContent->setTransactionDate($transactionDate);
        }
        
        return $mailContent;
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
    
    private function extractTransactionDate(string $content): int|false|null
    {
        $pattern = '/Date: ([\d.]+)/';
        $res = preg_match($pattern, $content, $result);
        
        if ($res === 1) {
            return strtotime($result[1]);
        }
        return null;
    }
}
```

## Krok 2: ParserInterface

`ParserInterface` vyžaduje jednu metódu:

```php
public function parse(string $content): ?MailContent
```

**Návratové hodnoty:**

- objekt `MailContent`, keď spracovanie prebehne
- `null`, keď sa formát e-mailu nespozná alebo chýbajú povinné polia

## Krok 3: Viac transakcií (voliteľné)

Ak banka posiela viac transakcií v jednom e-maile, implementujte `parseMulti()`:

```php
public function parseMulti(string $content): array
{
    // Rozdelenie podľa oddeľovača transakcií
    $transactions = array_slice(explode("Transaction Date:", $content), 1);
    
    $mailContents = [];
    foreach ($transactions as $transaction) {
        $mailContent = $this->parse("Transaction Date:" . $transaction);
        if ($mailContent !== null) {
            $mailContents[] = $mailContent;
        }
    }
    
    return $mailContents;
}
```

## Krok 4: Testy

Vytvorte `tests/Parser/YourBank/YourBankMailParserTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\YourBank;

use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\YourBank\YourBankMailParser;

class YourBankMailParserTest extends TestCase
{
    public function testParsesBasicTransaction()
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
        
        $this->assertNotNull($mailContent);
        $this->assertEquals(150.00, $mailContent->getAmount());
        $this->assertEquals('EUR', $mailContent->getCurrency());
        $this->assertEquals('SK9999999999999999999999', $mailContent->getAccountNumber());
        $this->assertEquals('1234567890', $mailContent->getVs());
    }
    
    public function testReturnsNullForInvalidFormat()
    {
        $email = 'This is not a valid bank email';
        
        $parser = new YourBankMailParser();
        $mailContent = $parser->parse($email);
        
        $this->assertNull($mailContent);
    }
}
```

## Krok 5: Pokrytie testami

Testy majú pokryť:

- úspešné spracovanie so všetkými poľami
- úspešné spracovanie len s povinnými poľami
- neplatný formát e-mailu (návrat `null`)
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

Štýl skontroluje PHP_CodeSniffer (phpcs):

```bash
composer phpcs
```

Automatická oprava cez phpcbf:

```bash
composer phpcbf
```

## Krok 8: Dokumentácia

Doplňte dokumentáciu parsera:

1. Vytvorte `docs/guide/yourbank.md` s príkladmi použitia
2. Doplňte položku do bočného menu v `docs/.vitepress/config.mts`
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

- Pri nespoznanom formáte vráťte `null`
- Pri zlyhaní spracovania nehádžte výnimky
- Povinné polia skontrolujte skôr, ako vrátite `MailContent`

### Testovanie

- Používajte **skutočné vzorky e-mailov** (anonymizované)
- **Hraničné prípady** otestujte dôkladne
- Cieľte na **vysoké pokrytie** (≥ 95 %)
- Počítajte aj s **mutačným testovaním**

## Príklad: úplný parser

Úplné príklady sú existujúce parsery:

- **Jednoduchý formát**: [VubMailParser](/sk/4.x/guide/vub)
- **Viac transakcií**: [CsobMailParser](/sk/4.x/guide/csob-cz)
- **Zložitý formát**: [TatraBankaMailParser](/sk/4.x/guide/tatrabanka)

## Odoslanie parsera

1. Vytvorte fork repozitára
2. Založte vetvu pre funkciu
3. Implementujte parser a testy
4. Všetky kontroly kvality musia prejsť
5. Otvorte pull request

Podrobnosti sú v [návode, ako prispieť](/sk/contributing).

## Ďalšie kroky

- Gettre a settre `MailContent` sú v [referencii API](/sk/4.x/api/reference)
- V existujúcich parseroch sú vzory a osvedčené postupy
- Prečítajte si [návod, ako prispieť](/sk/contributing)
- [Prechod na 5.x](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md)
