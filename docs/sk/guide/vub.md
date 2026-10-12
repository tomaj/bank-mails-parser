# VÚB

Parser e-mailov VÚB (Všeobecná úverová banka). Spracúva notifikácie v čistom texte.

## Parser

### VubMailParser

```php
<?php

use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

$parser = new VubMailParser();
$mailContent = $parser->parse($emailBody);
```

## Metódy

### parse(string $content): MailContent

Spracuje jednu transakciu. Keď nie je prítomné žiadne pole VÚB, vyhodí `InvalidEmailFormatException`. `tryParse()` namiesto toho vráti `null`.

## Podporované polia

- **Suma** (`money: ?Money`) – suma z `Suma:`. EUR, keď riadok menu nepomenuje. Pomenovaný kód, napríklad `CZK`, sa zachová.
- **Číslo účtu** (`accountNumber: ?string`) – účet platiteľa z `Z účtu` / `Z tu`
- **Vlastný účet** (`ownAccountNumber: ?string`) – hodnota z `Na účet` / `Na et`
- **Variabilný symbol** (`vs: ?string`) – riadok `VS:`. Pri párovaní platby skontrolujte aj sumu. Poznámka v e-maile sumu ani účet nezmení.
- **Špecifický symbol** (`ss: ?string`) – hodnota z `S:` alebo `ŠS:` (nie z `VS` ani `KS`)
- **Konštantný symbol** (`ks: ?string`)
- **Dátum transakcie** (`transactionDate: ?DateTimeImmutable`) – `Dátum` / `Dtum`, formáty `j.n.Y`, `Y-m-d` a `j.n.Y G:i`, v pásme `Europe/Bratislava`. Dvojciferný rok sa odmietne a dátum ostane `null`.

## Formát e-mailu

Čítajú sa aj ASCII popisy zo starších vzoriek, aj popisy s diakritikou:

```
Dátum:   11.12.2019
Na účet: SK9999999999999999999999
Suma:    13,37
Z účtu:  SK1111111111111111111111
VS:      9999999999
ŠS:      910
KS:      0308
Stav:    zrealizovaný
SIGN:    5CB8A45E42ABC48539E672B9F8E1B3F8E62F97FABDBCF880D0913B5A0C8431CE
```

`supports()` vyžaduje naraz `Dátum`, `Z účtu`, `Na účet`, `Suma`, `VS` a `Stav` alebo `SIGN`. Všeobecná faktúra, ktorá má len `Suma`, `VS` a `Na účet`, nie je notifikácia VÚB. Knižnica nekontroluje, že správa prišla z VÚB: pred zaúčtovaním platby overte odosielateľa a DKIM alebo SPF.

## Príklad použitia

```php
$emailBody = 'Dátum:   11.12.2019
Na účet: SK9999999999999999999999
Suma:    13,37
Z účtu:  SK1111111111111111111111
VS:      9999999999
ŠS:      910
KS:      0308
Stav:    zrealizovaný';

$parser = new VubMailParser();
$mailContent = $parser->parse($emailBody);

echo $mailContent->money?->toDecimalString(); // "13.37"
echo $mailContent->money?->currency->value;   // "EUR"
echo $mailContent->accountNumber;             // "SK1111111111111111111111"
echo $mailContent->ownAccountNumber;          // "SK9999999999999999999999"
echo $mailContent->vs;                        // "9999999999"
echo $mailContent->ss;                        // "910"
echo $mailContent->ks;                        // "0308"
echo $mailContent->transactionDate?->format('Y-m-d'); // "2019-12-11"
```

## Výber polí

| Vlastnosť | Vzor |
|----------|---------|
| `transactionDate` | `Dátum:` alebo `Dtum:` |
| `accountNumber` | `Z účtu:` alebo `Z tu:` (platiteľ) |
| `ownAccountNumber` | `Na účet:` alebo `Na et:` |
| `money` | `Suma:` (EUR, keď mena nie je uvedená) |
| `vs` | `VS:` |
| `ss` | `^(?:ŠS\|SS\|S):` s voliteľnými úvodnými bielymi znakmi |
| `ks` | `KS:` |

## Formát sumy

Sumu číta `Money::fromDecimalString()` s desatinnou čiarkou. `1 234,56`, nezalomiteľná medzera, tenká medzera, úzka nezalomiteľná medzera a tabulátor sú oddeľovače tisícov. `13,37` je `"13.37"`. `-13,37` je záporné. Nečitateľná suma (`abc`, alebo `13.37`, keď je desatinný znak čiarka) nechá `money` ako `null`. Prázdne `Suma:` nechá `money` ako `null`. `SIGN` sa skopíruje do `$sign`; overte ho sami.

## Spracovanie chýb

```php
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

$parser = new VubMailParser();

try {
    processTransaction($parser->parse($emailBody));
} catch (InvalidEmailFormatException $exception) {
    error_log($exception->getMessage());
}
```

## Ďalšie kroky

- Ďalšie [príklady](/sk/guide/examples)
- Ostatné banky: [TatraBanka](/sk/guide/tatrabanka), [ČSOB CZ](/sk/guide/csob-cz), [ČSOB SK](/sk/guide/csob-sk)
- Úplná [referencia API](/sk/api/reference)
