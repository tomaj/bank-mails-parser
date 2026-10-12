# ČSOB Česká republika

Parser potvrdzujúcich e-mailov ČSOB Česká republika. Zvládne jednu transakciu aj niekoľko v tom istom e-maile. Odovzdajte dekódovaný čistý text; HTML sa nespracúva.

## Parser

### CsobMailParser

```php
<?php

use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

$parser = new CsobMailParser();
```

## Metódy

### parse(string $content): MailContent

Spracuje jednu transakciu. Keď text nie je platba ČSOB CZ, vyhodí `InvalidEmailFormatException`. `tryParse()` namiesto toho vráti `null`.

### parseAll(string $content): array

Spracuje každú transakciu v e-maile. Keď text nie je platba ČSOB CZ alebo sa transakcia nedá prečítať, vyhodí `InvalidEmailFormatException`. Prázdny zoznam nevracia.

## Podporované polia

- **Suma** (`money: ?Money`) – znamienková suma v najmenších jednotkách a mena (`toDecimalString()` pre desatinný zápis)
- **Číslo účtu** (`accountNumber: ?string`) – cieľový účet
- **Účet protistrany** (`sourceAccountNumber: ?string`) – token účtu z `Účet protistrany`. Koncový text ako ` (Fio banka)` sa odstráni, rovnako aj `<br>`.
- **Dátum transakcie** (`transactionDate: ?DateTimeImmutable`)
- **Variabilný symbol** (`vs: ?string`) – variabilný symbol
- **Konštantný symbol** (`ks: ?string`) – konštantný symbol
- **Správa pre príjemcu** (`receiverMessage: ?string`) – správa k platbe

## Formát e-mailu

Notifikácie ČSOB CZ sú český text. HTML pred spracovaním preveďte na text. Parser pozná tieto typy transakcií:

- Došlá platba (prichádzajúca platba)
- Příchozí úhrada (prichádzajúca úhrada)
- SEPA převod (SEPA prevod)
- Zahraniční transakce (zahraničná transakcia)
- Hotovostní transakce (hotovostná transakcia)
- Platební kartou (platba kartou)

### Ukážka e-mailu

```
Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB.

Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.

Název smlouvy: Company Name
Číslo smlouvy: 87654321
Účet: 123456789, CZK
Částka: +1 234,56 CZK
Účet protistrany: 1122334455/9999
Název protistrany: Sender Name
Variabilní symbol: 23456789
Konstantní symbol: 3456

Zůstatek na účtu po zaúčtování transakce: +1 234 567,89 CZK.
```

## Príklady použitia

### Jedna transakcia

```php
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

$parser = new CsobMailParser();

try {
    $mailContents = $parser->parseAll($emailBody);
} catch (InvalidEmailFormatException) {
    $mailContents = [];
}

foreach ($mailContents as $mailContent) {
    echo $mailContent->money?->toDecimalString(); // "1234.56"
    echo $mailContent->money?->currency->value;   // "CZK"
    echo $mailContent->accountNumber;       // "123456789"
    echo $mailContent->sourceAccountNumber; // "1122334455/9999"
    echo $mailContent->vs;                  // "23456789"
    echo $mailContent->ks;                  // "3456"
}
```

### Viac transakcií

```php
$emailWithMultipleTransactions = '...'; // e-mail s viacerými značkami „Dne“

$parser = new CsobMailParser();
$mailContents = $parser->parseAll($emailWithMultipleTransactions);

foreach ($mailContents as $mailContent) {
    printf(
        "Transaction: %s %s, VS: %s, Account: %s\n",
        $mailContent->money?->toDecimalString(),
        $mailContent->money?->currency->value,
        $mailContent->vs,
        $mailContent->accountNumber
    );
}
```

## Hľadanie variabilného symbolu

Parser používa poradie z verzie 4.1.0:

1. **Variabilní symbol:** Hodnota zo samých núl sa preskočí. Viac ako 10 číslic je `null`.
2. **Identifikace:** Nahradí hodnotu z kroku 1, rovnako ako vo verzii 4.1.0.
3. Token `VS` alebo `v.s.`.
4. Posledné číslo v **Zpráva příjemci**, vrátane čísla bez predpony `VS` a referencie veriteľa.
5. **Účel platby:** posledné číslo s 1 až 10 číslicami na hranici slova pred `Zůstatek`, vrátane zalomeného účelu a prázdneho riadku v tom úseku. `AB12`, `INV2026` a `FV2024001` symbolmi nie sú. Bez `Zůstatek` číslo v päte symbolom nie je.

`Název protistrany`, `Adresa protistrany`, `Adresa plátce`, `Zpráva příjemci`, `Identifikace`, `Účel platby` a `Reference plátce` píše platiteľ. Pole končí prázdny riadok, alebo riadok z medzier, tabulátorov či nezalomiteľných medzier. Ľubovoľné `Slovo:` ho neukončí. Pole sa skončí aj na vlastnom riadku banky: celá veta `Zůstatek na účtu po zaúčtování transakce: +1 234,56 CZK.`, riadok symbolu zložený len z číslic, alebo riadok účtu. Holé `Zůstatek` nie. Hlavička alebo znamienková suma v tom texte sa vymaže. Správu to nezamietne. `Částka: záloha 2 500 Kč` a `Dne 1.10.2026 byla na účtu chyba, vracím` ostanú správa. Riadok účtu je protistrana, aj keď je meno prvé. `sourceAccountNumber` si nechá token účtu: koncový text ako ` (Fio banka)` sa odstráni, rovnako aj `<br>`. Niekoľko transakcií bez riadku zostatku sa spracuje rovnako ako vo verzii 4.1.0. Je to známe obmedzenie: bez tých riadkov sa podvrhnutá transakcia v texte platiteľa nedá odlíšiť od skutočnej. Sumu overte voči API banky alebo voči výpisu. Keď e-mail zostatok obsahuje, každá transakcia potrebuje práve jeden. Druhý riadok zostatku v tej istej transakcii vyhodí `InvalidEmailFormatException` s textom `Transaction has more than one balance line`. Predchádzajúci zostatok plus suma sa musia rovnať nasledujúcemu zostatku. Osamotený CR, VT, FF, U+0085, U+2028 alebo U+2029 v správe, ktorá už má LF, ostane obyčajný znak a riadok nerozdelí. Hlavička nepotrebuje prázdny riadok pred sebou. Pri párovaní platby skontrolujte aj sumu.

## Osobitosti

### Suma

- Podporuje kladné (`+`) aj záporné (`-`) sumy
- Zvláda rôzne zápisy čísel s medzerami a čiarkami
- Normalizovanú sumu číta sama

### Orezanie účtu

Čísla zdrojových účtov sa samy orežú o biele znaky.

### Viacriadkové správy

Zvláda viacriadkové správy pre príjemcu a účely platby.

## Spracovanie chýb

```php
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

$parser = new CsobMailParser();

try {
    foreach ($parser->parseAll($emailBody) as $mailContent) {
        processTransaction($mailContent);
    }
} catch (InvalidEmailFormatException $exception) {
    error_log($exception->getMessage());
}
```

## Ďalšie kroky

- Ďalšie [príklady](/sk/guide/examples)
- Slovenská verzia: [ČSOB SK](/sk/guide/csob-sk)
- Úplná [referencia API](/sk/api/reference)
