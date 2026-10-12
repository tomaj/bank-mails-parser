# Začíname

Bank Mails Parser je PHP knižnica na spracovanie potvrdzujúcich e-mailov slovenských a českých bánk. Zo správy sama vyťahuje sumu, číslo účtu, variabilný symbol a ďalšie údaje o transakcii.

## Inštalácia

Nainštalujte ju cez Composer:

```bash
composer require tomaj/bank-mails-parser
```

## Požiadavky

- PHP 8.4 alebo 8.5
- Composer

## Rýchly štart

Jednoduchý príklad so správou z TatraBanky:

```php
<?php

use Tomaj\BankMailsParser\BankMailsParser;

require 'vendor/autoload.php';

$parser = new BankMailsParser();
$mailContent = $parser->parse($emailBody, $sender, $subject);

echo $mailContent->money?->toDecimalString() . ' ' . $mailContent->money?->currency->value . "\n";
echo $mailContent->vs . "\n";
echo $mailContent->accountNumber . "\n";
echo $mailContent->transactionDate?->format('Y-m-d') . "\n";
```

`parse()` vyhodí výnimku, keď sa e-mail nedá prečítať. `tryParse()` v tom istom prípade vráti `null`. Pozrite si [Rozpoznanie banky](/sk/guide/bank-detection) a [Výnimky](/sk/guide/exceptions).

## Podporované banky

- **TatraBanka (SK)** – bežné notifikácie, ComfortPay a výpisy šifrované cez PGP
- **ČSOB (CZ)** – e-mail s jednou aj s viacerými transakciami. Odovzdajte dekódovaný čistý text; HTML sa nespracúva.
- **ČSOB (SK)** – e-mail s jednou aj s viacerými transakciami. Odovzdajte dekódovaný čistý text; HTML sa nespracúva.
- **VÚB (SK)** – notifikácie v čistom texte

## Ďalšie kroky

- [Príklady](/sk/guide/examples) ukazujú rôzne typy správ
- Banku vyberie [BankMailsParser](/sk/guide/bank-detection)
- Ako spracovať [výnimky](/sk/guide/exceptions)
- Príručky k bankám: [TatraBanka](/sk/guide/tatrabanka), [ČSOB CZ](/sk/guide/csob-cz), [ČSOB SK](/sk/guide/csob-sk), [VÚB](/sk/guide/vub)
- [Referencia API](/sk/api/reference) opisuje všetky metódy
- Ako [pridať parser novej banky](/sk/guide/adding-bank)
