# ČSOB Česká republika (4.x)

::: warning Staršia verzia
Toto je dokumentácia verzie **4.x**. Aktuálna verzia s nemennou architektúrou je v [dokumentácii 5.x](/sk/). Postup prechodu je v [návode na prechod na 5.0](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
:::

Parser potvrdzujúcich e-mailov ČSOB Česká republika. Zvládne jednu transakciu aj viac transakcií v HTML e-maile.

## Parser

### CsobMailParser

```php
<?php

use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

$parser = new CsobMailParser();
```

## Metódy

### parse(string $content): ?MailContent

Spracuje jednu transakciu z e-mailu.

### parseMulti(string $content): array

Spracuje viac transakcií z jedného e-mailu. Vráti pole objektov `MailContent`.

## Podporované polia

- **Suma** (`getAmount(): ?float`) – suma transakcie (kladná aj záporná)
- **Mena** (`getCurrency(): ?string`) – kód meny (CZK, EUR a ďalšie)
- **Číslo účtu** (`getAccountNumber(): ?string`) – cieľový účet
- **Zdrojový účet** (`getSourceAccountNumber(): ?string`) – zdrojový účet alebo IBAN
- **Dátum transakcie** (`getTransactionDate(): int|false|null`) – unixová časová pečiatka
- **Variabilný symbol** (`getVs(): ?string`) – variabilný symbol
- **Konštantný symbol** (`getKs(): ?string`) – konštantný symbol
- **Správa pre príjemcu** (`getReceiverMessage(): ?string`) – správa k platbe

## Formát e-mailu

ČSOB CZ posiela HTML e-maily s českým textom. Parser pozná tieto typy transakcií:

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
$parser = new CsobMailParser();
$mailContents = $parser->parseMulti($emailBody);

if (!empty($mailContents)) {
    $mailContent = $mailContents[0];
    
    echo $mailContent->getAmount();              // 1234.56
    echo $mailContent->getCurrency();            // "CZK"
    echo $mailContent->getAccountNumber();       // "123456789"
    echo $mailContent->getSourceAccountNumber(); // "1122334455/9999"
    echo $mailContent->getVs();                  // "23456789"
    echo $mailContent->getKs();                  // "3456"
}
```

### Viac transakcií

```php
$emailWithMultipleTransactions = '...'; // e-mail s viacerými značkami „Dne“

$parser = new CsobMailParser();
$mailContents = $parser->parseMulti($emailWithMultipleTransactions);

foreach ($mailContents as $mailContent) {
    printf(
        "Transaction: %s %s, VS: %s, Account: %s\n",
        $mailContent->getAmount(),
        $mailContent->getCurrency(),
        $mailContent->getVs(),
        $mailContent->getAccountNumber()
    );
}
```

## Hľadanie variabilného symbolu

Parser skúša viac vzorov:

1. Pole **Variabilní symbol:** (hlavný zdroj)
2. Pole **Identifikace:** (SEPA prevody)
3. **Zpráva příjemci:** s predponou `vs` alebo `v.s.`
4. Pole **Účel platby:** (zahraničné prevody)

Ak je VS `0000000000`, nastaví sa na `null`.

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
$parser = new CsobMailParser();
$mailContents = $parser->parseMulti($emailBody);

if (empty($mailContents)) {
    error_log('No transactions found in ČSOB email');
} else {
    foreach ($mailContents as $mailContent) {
        processTransaction($mailContent);
    }
}
```

## Ďalšie kroky

- Ďalšie [príklady](/sk/4.x/guide/examples)
- Slovenská verzia: [ČSOB SK](/sk/4.x/guide/csob-sk)
- Úplná [referencia API](/sk/4.x/api/reference)
- [Prechod na 5.x](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md)
