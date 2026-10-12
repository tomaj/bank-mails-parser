# ČSOB Slovensko (4.x)

::: warning Staršia verzia
Toto je dokumentácia verzie **4.x**. Aktuálna verzia s nemennou architektúrou je v [dokumentácii 5.x](/sk/). Postup prechodu je v [návode na prechod na 5.0](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
:::

Parser potvrdzujúcich e-mailov ČSOB Slovensko. Podobá sa na ČSOB CZ, ale text je slovenský a formát sa mierne líši.

## Parser

### SkCsobMailParser

```php
<?php

use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;

$parser = new SkCsobMailParser();
```

## Metódy

### parse(string $content): ?MailContent

Spracuje jednu transakciu z e-mailu.

### parseMulti(string $content): array

Spracuje viac transakcií. Vráti pole objektov `MailContent`.

## Podporované polia

- **Suma** (`getAmount(): ?float`) – suma transakcie
- **Mena** (`getCurrency(): ?string`) – kód meny
- **Variabilný symbol** (`getVs(): ?string`) – variabilný symbol (VS)
- **Konštantný symbol** (`getKs(): ?string`) – konštantný symbol (KS)
- **Správa pre príjemcu** (`getReceiverMessage(): ?string`) – správa k platbe

## Formát e-mailu

ČSOB SK používa slovenčinu a mierne iné názvy polí:

```
Vážený klient,

dňa 10.11.2023 bola na Vašom účte zaevidovaná platba.

suma:                      +1 000,00 EUR
účet príjemcu:             SK9999999999999999999999
banka:                     CEKOSKBX
detaily platby:
názov protiúčtu:           TEST USER GAMMA
referencia platiteľa:      /VS212049000/SS964/KS0308
informácia pre príjemcu:   Payment message
```

## Príklad použitia

```php
$parser = new SkCsobMailParser();
$mailContents = $parser->parseMulti($emailBody);

foreach ($mailContents as $mailContent) {
    echo $mailContent->getAmount();         // 1000.00
    echo $mailContent->getCurrency();       // "EUR"
    echo $mailContent->getVs();             // "212049000"
    echo $mailContent->getKs();             // "0308"
    echo $mailContent->getReceiverMessage(); // "Payment message"
}
```

## Viac transakcií

Rovnako ako ČSOB CZ parser delí e-mail podľa značky „dňa“, aby zvládol viac transakcií:

```php
$emailWithMultipleTransactions = '
dňa 10.11.2023 bola na Vašom účte zaevidovaná platba.
suma: +100,00 EUR
...

dňa 11.11.2023 bola na Vašom účte zaevidovaná platba.
suma: +200,00 EUR
...
';

$parser = new SkCsobMailParser();
$mailContents = $parser->parseMulti($emailWithMultipleTransactions);

echo count($mailContents); // 2
```

## Čítanie sumy

Parser zvláda slovenský zápis čísel:

- desatinný oddeľovač: čiarka (`,`)
- v sume môže byť biely znak
- predpona môže byť kladná (`+`) aj záporná (`-`)

## Ďalšie kroky

- Ďalšie [príklady](/sk/4.x/guide/examples)
- Česká verzia: [ČSOB CZ](/sk/4.x/guide/csob-cz)
- Úplná [referencia API](/sk/4.x/api/reference)
- [Prechod na 5.x](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md)
