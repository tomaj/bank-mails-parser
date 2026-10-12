# Začíname (4.x)

::: warning Staršia verzia
Toto je dokumentácia verzie **4.x**. Aktuálna verzia s nemennou architektúrou je v [dokumentácii 5.x](/sk/). Postup prechodu je v [návode na prechod na 5.0](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
:::

## Inštalácia

```bash
composer require tomaj/bank-mails-parser ^4.0
```

## Požiadavky

- PHP 8.2, 8.3 alebo 8.4
- Composer

## Rýchly štart

### Parser TatraBanky

```php
<?php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent !== null) {
    echo "Amount: " . $mailContent->getAmount() . " " . $mailContent->getCurrency() . "\n";
    echo "Variable Symbol: " . $mailContent->getVs() . "\n";
    echo "Account: " . $mailContent->getAccountNumber() . "\n";
    echo "Date: " . date('Y-m-d', $mailContent->getTransactionDate()) . "\n";
}
```

## Podporované banky

### TatraBanka (Slovensko)

- **TatraBankaMailParser** – bežné notifikácie o transakcii
- **TatraBankaSimpleMailParser** – potvrdenia ComfortPay a CardPay
- **TatraBankaStatementMailParser** – výpisy šifrované cez PGP

### ČSOB

- **CsobMailParser** – e-maily ČSOB Česká republika
- **SkCsobMailParser** – e-maily ČSOB Slovensko

### VÚB

- **VubMailParser** – e-maily VÚB Slovensko

## API MailContent (4.x)

Verzia 4.x číta a zapisuje údaje o transakcii cez gettre a settre:

```php
// Čítanie hodnôt
$amount = $mailContent->getAmount();           // float|null
$currency = $mailContent->getCurrency();       // string|null
$vs = $mailContent->getVs();                   // string|null
$account = $mailContent->getAccountNumber();   // string|null

// Všetky dostupné gettre
$mailContent->getAmount(): ?float
$mailContent->getCurrency(): ?string
$mailContent->getVs(): ?string
$mailContent->getSs(): ?string
$mailContent->getKs(): ?string
$mailContent->getAccountNumber(): ?string
$mailContent->getSourceAccountNumber(): ?string
$mailContent->getTransactionDate(): int|false|null
$mailContent->getReceiverMessage(): ?string
$mailContent->getDescription(): ?string
```

## Viac transakcií v jednom e-maile

Niektoré banky posielajú viac transakcií v jednej správe:

```php
$parser = new CsobMailParser();
$mailContents = $parser->parseMulti($emailBody);

foreach ($mailContents as $mailContent) {
    echo $mailContent->getAmount() . " " . $mailContent->getCurrency() . "\n";
    echo "VS: " . $mailContent->getVs() . "\n";
}
```

## Spracovanie chýb

Parser vráti `null`, keď formát e-mailu nespozná:

```php
$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent === null) {
    // Formát e-mailu sa nepodarilo spoznať
    error_log("Unable to parse email");
} else {
    // Spracovanie prebehlo
    processTransaction($mailContent);
}
```

## Prechod na 5.x

Verzia 5.x mení API na nemenné value objekty. Postup prechodu je v [návode na prechod na 5.0](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).

## Ďalšie kroky

- [Referencia API](/sk/4.x/api/reference)
- [Prechod na 5.x](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md)
