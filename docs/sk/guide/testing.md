# Testovanie integrácie

Váš kód stojí medzi schránkou a objednávkami. Otestujte obe polovice: že e-maily, ktoré vaše banky naozaj posielajú, sa spracujú tak, ako čakáte, a že vaša logika účtovania urobí s `MailContent` správnu vec, vrátane prípadov, keď nesmie zaúčtovať nič.

## 1. Skutočné e-maily ako fixtures

Uložte si niekoľko skutočných notifikácií od každej banky, ktorú používate, ako dekódované textové telo (to, čo odovzdávate do `parse()`). Mená, čísla účtov a správy nahraďte vymyslenými hodnotami, ale rozloženie nechajte: parsery sa riadia zalomeniami riadkov, medzerami, popiskami a diakritikou. Potom ich spracujte v teste:

```php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;

final class BankMailFixturesTest extends TestCase
{
    #[Test]
    public function tatraBankaNotification(): void
    {
        $body = (string) file_get_contents(__DIR__ . '/fixtures/tatra-incoming.txt');

        $mail = new BankMailsParser()->parse($body, 'b-mail@tatrabanka.sk');

        self::assertSame(1231, $mail->money?->amount);
        self::assertSame('EUR', $mail->money?->currency->value);
        self::assertSame('1234056789', $mail->vs);
        self::assertSame('2015-01-16 12:51', $mail->transactionDate?->format('Y-m-d H:i'));
    }
}
```

Porovnávajte `money->amount` (celé číslo) alebo `money->toDecimalString()`, nikdy float. Keď banka zmení šablónu, práve tento test vám to povie skôr, než sa platby prestanú účtovať.

Dobrým začiatkom sú fixtures samotnej knižnice: [`tests/fixtures/`](https://github.com/tomaj/bank-mails-parser/tree/master/tests/fixtures) má takmer sto e-mailov a ku každému `.expected.json` so všetkými spracovanými poľami.

## 2. Logika účtovania s MailContent

`MailContent` je obyčajný hodnotový objekt, takže váš kód sa dá testovať bez akéhokoľvek e-mailu:

```php
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\ValueObject\Money;

$payment = new MailContent(
    money: Money::fromDecimalString('49.90', 'EUR'),
    transactionDate: new DateTimeImmutable('2026-11-24 10:15', new DateTimeZone('Europe/Bratislava')),
    vs: '2026001234',
);

echo $payment->money?->amount, ' ', $payment->money?->currency->value, ' ', $payment->vs, "\n";
```

<!-- snippet: output -->
```text
4990 EUR 2026001234
```

Každý argument je nepovinný a pomenovaný, reťazce sa orežú a prázdny reťazec sa zmení na `null`, rovnako ako vo výsledku parsera.

## 3. Čo sa zaúčtovať nesmie

Väčšina rizika je v e-mailoch, ktoré nemajú pohnúť peniazmi. Pokryte aspoň:

| Prípad | Očakávanie |
|--------|------------|
| Bankový e-mail od odosielateľa mimo domény banky | `UnsupportedBankException`, nič sa nezaúčtuje |
| Newsletter alebo avízo o zásielke | `UnsupportedBankException` |
| Správny variabilný symbol s inou sumou | váš kód odmietne označiť objednávku ako zaplatenú |
| Výsledok CardPay s `RES=FAIL` | nezaplatené |
| Výsledok CardPay so zlým `HMAC` | vaša kontrola podpisu ho odmietne |
| E-mail ČSOB s dvoma transakciami | dve platby, cez `parseAll()` |
| Ten istý e-mail spracovaný dvakrát | zaúčtovaný raz |
| Výpis s nesprávnym heslom kľúča | `ConfigurationException` sa dostane do monitoringu, nezahodí sa |

Väčšinu z nich so skutočnými e-mailmi a výstupom ukazujú [príklad schránky](/sk/examples/mailbox) a [príklad ošetrenia chýb](/sk/examples/error-handling).

## 4. Príklady vo vašom prostredí

Každý recept v časti [Príklady](/sk/examples/) je Composer projekt so skriptom `e2e.sh`, ktorý porovná výstup s `expected-output.txt`. Spustenie na produkčnej verzii PHP rýchlo overí, že rozšírenia a PGP závislosť tam fungujú:

```bash
cd examples/encrypted-statement
composer install
./e2e.sh
```

## Ako sa testuje samotná knižnica

- Každá fixture v `tests/fixtures/` sa spracuje a porovná so snímkou `.expected.json`.
- Samostatné testy pokrývajú podvrhnuté polia platiteľa, reťaz zostatkov, oddeľovače, dátumy okolo zmeny času a neplatné UTF-8.
- CI vyžaduje 97 % pokrytie príkazov a 95 % mutačné skóre (Infection) na PHP 8.4, 8.5 a 8.6.
- Každý PHP blok v README a v tejto dokumentácii sa spúšťa v `tests/DocsSnippetsTest.php` a každý príklad v workflow Examples, takže kód na tomto webe funguje s aktuálnou verziou.
