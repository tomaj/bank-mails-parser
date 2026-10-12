# Referencia API (4.x)

::: warning Staršia verzia
Toto je dokumentácia verzie **4.x**. Aktuálna verzia s nemennou architektúrou je v [dokumentácii 5.x](/sk/). Postup prechodu je v [návode na prechod na 5.0](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
:::

## MailContent

Trieda `MailContent` predstavuje spracovanú transakciu so všetkými vybratými poľami.

### Menný priestor

```php
Tomaj\BankMailsParser\MailContent
```

### Architektúra (4.x)

Verzia 4.x používa meniteľný objekt so súkromnými vlastnosťami a verejnými gettermami a settermami:

```php
class MailContent
{
    private $amount;
    private $currency;
    private $vs;
    // ... ďalšie súkromné vlastnosti
    
    public function getAmount(): ?float { ... }
    public function setAmount(float $amount): void { ... }
    
    public function getVs(): ?string { ... }
    public function setVs(string $vs): void { ... }
    // ... ďalšie gettre a settre
}
```

### Vytvorenie MailContent

Parsery vytvoria `MailContent` a naplnia ho settermami:

```php
$mailContent = new MailContent();
$mailContent->setAmount(123.45);
$mailContent->setCurrency('EUR');
$mailContent->setVs('1234567890');
```

### Finančné údaje

#### getAmount(): ?float

Vráti sumu transakcie, alebo `null`, keď v e-maile nie je.

```php
$amount = $mailContent->getAmount(); // 123.45
```

#### setAmount(float $amount): void

Nastaví sumu transakcie.

#### getCurrency(): ?string

Vráti kód meny (EUR, CZK, USD a ďalšie), alebo `null`.

```php
$currency = $mailContent->getCurrency(); // "EUR"
```

#### setCurrency(string $currency): void

Nastaví kód meny.

#### getTransactionDate(): int|false|null

Vráti dátum transakcie ako unixovú časovú pečiatku, `false` pri chybe čítania, alebo `null`, keď dátum nie je.

```php
$timestamp = $mailContent->getTransactionDate();
if ($timestamp !== null && $timestamp !== false) {
    echo date('Y-m-d H:i:s', $timestamp);
}
```

#### setTransactionDate(int|false $transactionDate): void

Nastaví časovú pečiatku transakcie.

### Údaje o účte

#### getAccountNumber(): ?string

Vráti číslo cieľového účtu príjemcu (IBAN alebo domáci formát), alebo `null`.

```php
$account = $mailContent->getAccountNumber(); // "SK9812353347235"
```

#### setAccountNumber(string $accountNumber): void

Nastaví číslo cieľového účtu.

#### getSourceAccountNumber(): ?string

Vráti číslo zdrojového účtu odosielateľa, alebo `null`.

```php
$source = $mailContent->getSourceAccountNumber(); // "1100/000000-261426464"
```

#### setSourceAccountNumber(string $sourceAccountNumber): void

Nastaví číslo zdrojového účtu.

### Bankové symboly

#### getVs(): ?string

Vráti variabilný symbol (VS), alebo `null`. Prázdne reťazce sa prevedú na `null`.

```php
$vs = $mailContent->getVs(); // "1234567890"
```

#### setVs(string $vs): void

Nastaví variabilný symbol. Prázdny reťazec sa sám prevedie na `null`.

#### getSs(): ?string

Vráti špecifický symbol (ŠS/SS), alebo `null`.

```php
$ss = $mailContent->getSs(); // "9876543210"
```

#### setSs(string $ss): void

Nastaví špecifický symbol. Prázdne reťazce sa prevedú na `null`.

#### getKs(): ?string

Vráti konštantný symbol (KS), alebo `null`.

```php
$ks = $mailContent->getKs(); // "0308"
```

#### setKs(string $ks): void

Nastaví konštantný symbol. Prázdne reťazce sa prevedú na `null`.

### Podrobnosti transakcie

#### getReceiverMessage(): ?string

Vráti správu alebo poznámku k platbe pre príjemcu, alebo `null`.

```php
$message = $mailContent->getReceiverMessage(); // "Invoice payment"
```

#### setReceiverMessage(string $receiverMessage): void

Nastaví správu pre príjemcu.

#### getDescription(): ?string

Vráti popis transakcie od banky, alebo `null`.

```php
$description = $mailContent->getDescription(); // "SEPA Credit Transfer"
```

#### setDescription(string $description): void

Nastaví popis transakcie.

### Polia ComfortPay a CardPay

#### getCid(): ?string

ID klienta (ComfortPay), alebo `null`.

```php
$cid = $mailContent->getCid(); // "824452"
```

#### getSign(): ?string

Podpis HMAC, alebo `null`.

```php
$sign = $mailContent->getSign(); // "C0CBF27F5D97841E"
```

#### getRes(): ?string

Kód výsledku (OK, FAIL a ďalšie), alebo `null`.

```php
$res = $mailContent->getRes(); // "OK"
```

#### getAc(): ?string

Autorizačný kód, alebo `null`.

```php
$ac = $mailContent->getAc(); // "558058"
```

#### getCc(): ?string

Maskované číslo karty, alebo `null`.

```php
$cc = $mailContent->getCc(); // "************1111"
```

#### getTid(): ?string

ID transakcie, alebo `null`.

```php
$tid = $mailContent->getTid(); // "11224444"
```

#### getTxn(): ?string

Typ transakcie (PA a podobne), alebo `null`.

```php
$txn = $mailContent->getTxn(); // "PA"
```

#### getRc(): ?string

Návratový kód, alebo `null`.

```php
$rc = $mailContent->getRc(); // "0"
```

## ParserInterface

Všetky parsery implementujú `ParserInterface`:

```php
namespace Tomaj\BankMailsParser\Parser;

interface ParserInterface
{
    public function parse(string $content): ?MailContent;
}
```

### Návratové hodnoty

- Vráti objekt `MailContent`, keď formát e-mailu spozná
- Vráti `null`, keď formát e-mailu nespozná

## Dostupné parsery

### TatraBanka

- **TatraBankaMailParser** – bežné notifikácie o transakcii
- **TatraBankaSimpleMailParser** – potvrdenia ComfortPay a CardPay
- **TatraBankaStatementMailParser** – výpisy šifrované cez PGP

### ČSOB

- **CsobMailParser** – e-maily ČSOB Česká republika
- **SkCsobMailParser** – e-maily ČSOB Slovensko

### VÚB

- **VubMailParser** – e-maily VÚB Slovensko

## Prechod na 5.x

Verzia 5.x zavádza nemenné value objekty s priamym prístupom k vlastnostiam:

```php
// 4.x (staré)
$amount = $mailContent->getAmount();
$mailContent->setAmount(100);

// 5.x (nové)
$amount = $mailContent->amount;
$mailContent = new MailContent(amount: 100);
```

Úplný postup prechodu je v [návode na prechod na 5.0](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
