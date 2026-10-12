# Rozpoznanie banky

`BankMailsParser` vyberie parser podľa tela e-mailu. Odosielateľ a predmet sú ďalšie signály. Triedu banky nemusíte vyberať sami.

## Rýchly štart

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;

$parser = new BankMailsParser();

try {
    foreach ($parser->parseAll($emailBody, $sender, $subject) as $mailContent) {
        echo $mailContent->money?->toDecimalString();
        echo $mailContent->money?->currency->value;
    }
} catch (UnsupportedBankException $exception) {
    // Žiadny zaregistrovaný parser tento e-mail neprijal.
} catch (InvalidEmailFormatException $exception) {
    // Parser e-mail prijal a potom obsah odmietol.
}
```

`parse()` vyhodí výnimku. `tryParse()` zachytí `ParseException` a vráti `null`.

## Predvolené parsery

`$sender` odovzdávajte vždy. Bez neho môže veta, ktorú napísal platiteľ, sedieť na druhú banku a `parse()` vyhodí `AmbiguousBankException`.

`new BankMailsParser()` (alebo `null`) zaregistruje päť parserov. Parser PGP výpisov sa vkladá za ComfortPay a len vtedy, keď konštruktor dostane dešifrátor. Bez dešifrátora je ČSOB CZ tretia. Hlavička ČSOB je začiatok riadku a prázdny riadok pred ňou netreba. Dátum môže obsahovať `HH:MM`. Celá hlavička v poli platiteľa sa odmietne. `supports()` vráti false, keď vzor nemôže bežať.

| Poradie | Parser | Signál v obsahu | Ďalší signál |
|------:|--------|----------------|--------------|
| 1 | `TatraBankaMailParser` | riadok, ktorý začína dátumom a `bol zostatok Vasho uctu … zvyseny\|znizeny o` | `tatrabanka.sk` |
| 2 | `TatraBankaSimpleMailParser` | prvý súvislý blok `KEY=value` s `RES` alebo `TRES` a k tomu `AMT`, `VS`, `HMAC` alebo `SIGN`. Riadky začínajúce `>` sa ignorujú | `tatrabanka.sk` |
| 3 | `TatraBankaStatementMailParser` | riadok `-----BEGIN PGP MESSAGE-----` | `tatrabanka.sk`; potrebuje dešifrátor |
| 4 | `CsobMailParser` | riadok `Dne d.m.yyyy … byl(a) na účtu … zaúčtov` | `csob.cz` |
| 5 | `SkCsobMailParser` | `dňa d.m.yyyy … bola na účte … zaúčtovaná` (aj po `že`) | `csob.sk` |
| 6 | `VubMailParser` | na riadok ukotvené `Dátum`, `Z účtu`, `Na účet`, `Suma`, `VS` a `Stav` alebo `SIGN` | `vub.sk` |

Správy pre príjemcu, účel platby, názvy protistrany a popisy sa pred rozpoznaním odstránia. Banku nevyberú a sumu nedodajú.

Ak odovzdáte odosielateľa, uvažuje sa len o parseroch pre daného hostiteľa. Neznámy hostiteľ vyhodí `UnsupportedBankException`. Ak telo prijme viac ako jeden vhodný parser, `parse()` vyhodí `AmbiguousBankException`. Fasáda si nenechá prvú zhodu. Jediná zhoda, ktorá vyhodí `InvalidEmailFormatException`, sa pustí ďalej. Akákoľvek iná `ParseException` prehľadávanie zastaví.

## Vlastné parsery

Pole znamená, že sa použijú presne tie parsery. Prázdne pole nezaregistruje nič. Jedine `null` načíta predvolené.

```php
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

$custom = new class implements ParserInterface {
    use ParsesBankMail;

    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return str_contains($content, 'ACME BANK');
    }

    public function parse(string $content): MailContent
    {
        return new MailContent(description: 'ACME BANK');
    }
};

$parser = new BankMailsParser([$custom]);
$parser->registerParser(new \Tomaj\BankMailsParser\Parser\Vub\VubMailParser());
```

`getParsers()` vráti zaregistrovaný zoznam.

## Priame použitie parsera

Parser banky si stále môžete zostaviť sami. Každý má tú istú dvojicu:

- `supports(string $content, ?string $sender = null, ?string $subject = null): bool`
- `parse(string $content): MailContent` – vyhodí `InvalidEmailFormatException`. Na konkrétnom parseri číta jednu transakciu. Na `BankMailsParser` viac ako jedna transakcia vyhodí `MultipleTransactionsException`.
- `parseAll(string $content): list<MailContent>` – každý riadok. Použite to pre ČSOB a pre výpisy.
- `tryParse(string $content): ?MailContent` – `null` pri neplatnom vstupe, vrátane `MultipleTransactionsException`. `TypeError` nezachytáva.

Odovzdajte `new BankMailsParser(decryptor: $decryptor)`, aby predvolený zoznam vedel čítať PGP výpisy. `parseAll()` vráti každý dešifrovaný riadok. Bez dešifrátora sa parser výpisov neregistruje, takže PGP správa je `UnsupportedBankException` a `tryParse()` na fasáde vráti `null`. Dešifrátor, ktorý neotvorí svoj kľúč, vyhodí `ConfigurationException`, a to aj z `tryParse()`. Priamy `TatraBankaStatementMailParser` stále vyhodí `ConfigurationException`, keď nemá funkčný dešifrátor.

Slovenské a české dátumy v e-mailoch používajú `Europe/Bratislava`. Hodnoty CardPay `TIMESTAMP` so 14 číslicami sú UTC.

Knižnica neoveruje CardPay `HMAC` ani `SIGN` a neoveruje ani DKIM. Oboje skontrolujte skôr, ako platbu zaúčtujete. Ak notifikácia obsahuje obe hodnoty, `$sign` je HMAC. Odosielateľa odovzdávajte vždy. Hostiteľ odosielateľa len vyberie, ktoré parsery smú bežať. DKIM dokazuje odosielajúcu doménu, nie to, že suma vo voľnom texte je suma banky. Text platiteľa sa pri sume, mene a dátume ignoruje. Celá hlavička transakcie alebo riadok s podpísanou sumou v tom texte sa vymaže. Správu to nezamietne. Riadok v tvare účtu je aj tak účet protistrany. TatraBanka aj ČSOB vedia variabilný symbol z textu platiteľa prečítať rovnako ako vo verzii 4.1.0. Pri párovaní platby skontrolujte aj sumu. Citovaná história s `>` sa ako platba nespracuje. Ak cyklus nad schránkou môže dostať ČSOB alebo výpis, použite `parseAll()`: `tryParse()` pri viacerých transakciách vráti `null`, takže by sa tieto platby preskočili.

Pozrite si [Výnimky](/sk/guide/exceptions) a [Pridanie novej banky](/sk/guide/adding-bank).
