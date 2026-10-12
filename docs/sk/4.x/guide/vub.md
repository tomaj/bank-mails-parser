# VÚB (4.x)

::: warning Staršia verzia
Toto je dokumentácia verzie **4.x**. Aktuálna verzia s nemennou architektúrou je v [dokumentácii 5.x](/sk/). Postup prechodu je v [návode na prechod na 5.0](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
:::

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

### parse(string $content): ?MailContent

Spracuje jednu transakciu. Vráti `MailContent` alebo `null`.

## Podporované polia

- **Suma** (`getAmount(): ?float`) – suma transakcie
- **Číslo účtu** (`getAccountNumber(): ?string`) – cieľový účet (IBAN)
- **Variabilný symbol** (`getVs(): ?string`) – variabilný symbol (VS)
- **Špecifický symbol** (`getSs(): ?string`) – špecifický symbol (ŠS)
- **Konštantný symbol** (`getKs(): ?string`) – konštantný symbol (KS)
- **Dátum transakcie** (`getTransactionDate(): int|false|null`) – unixová časová pečiatka

## Formát e-mailu

VÚB posiela čistý text so slovenskými názvami polí:

```
Vážený klient,

v prílohe e-mailu Vám zasiela informácia o realizácii prevodu.

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

if ($mailContent !== null) {
    echo $mailContent->getAmount();         // 13.37
    echo $mailContent->getAccountNumber();  // "SK9999999999999999999999"
    echo $mailContent->getVs();             // "9999999999"
    echo $mailContent->getSs();             // "910"
    echo $mailContent->getKs();             // "0308"
    
    $date = $mailContent->getTransactionDate();
    if ($date !== null && $date !== false) {
        echo date('Y-m-d', $date); // 2019-12-12
    }
}
```

## Výber polí

Parser vyberá polia regulárnymi výrazmi:

| Pole | Vzor | Príklad |
|-------|---------|---------|
| Dátum | `Dátum:.*?(.*)` | `Dátum:   11.12.2019` |
| Účet | `Na účet:.*?([A-Z0-9]+)` | `Na účet: SK99...` |
| Suma | `Suma:.*?([0-9,]+)` | `Suma:    13,37` |
| Zdroj | `Z účtu:.*?([A-Z0-9]+)` | `Z účtu:  SK11...` |
| VS | `VS:.*?([0-9]+)` | `VS:      9999999999` |
| ŠS | `ŠS:.*?([0-9]+)` | `ŠS:      910` |
| KS | `KS:.*?([0-9]+)` | `KS:      0308` |

## Formát sumy

VÚB používa čiarku (`,`) ako desatinný oddeľovač. Parser sám prevedie:

- `13,37` → `13.37`
- `1 000,50` → `1000.50`

## IBAN

Čísla účtov sa predvolene neorežú o biele znaky. Ostane presný tvar z e-mailu.

## Spracovanie chýb

```php
$parser = new VubMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent === null) {
    // Chýbajú povinné polia alebo formát nesedí
    error_log('Failed to parse VÚB email');
} else {
    processTransaction($mailContent);
}
```

## Ďalšie kroky

- Ďalšie [príklady](/sk/4.x/guide/examples)
- Ostatné banky: [TatraBanka](/sk/4.x/guide/tatrabanka), [ČSOB CZ](/sk/4.x/guide/csob-cz), [ČSOB SK](/sk/4.x/guide/csob-sk)
- Úplná [referencia API](/sk/4.x/api/reference)
- [Prechod na 5.x](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md)
