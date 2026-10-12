# TatraBanka (4.x)

::: warning Staršia verzia
Toto je dokumentácia verzie **4.x**. Aktuálna verzia s nemennou architektúrou je v [dokumentácii 5.x](/sk/). Postup prechodu je v [návode na prechod na 5.0](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
:::

TatraBanka je v tejto knižnici najširší bankový parser. Pozná tri formáty e-mailov: bežné notifikácie, platby ComfortPay a výpisy šifrované cez PGP.

## Prehľad parserov

| Parser | Typ e-mailu | Trieda |
|--------|-----------|-------|
| Bežné notifikácie | Transakčné e-maily v čistom texte | `TatraBankaMailParser` |
| Platby ComfortPay | Formát kľúč=hodnota (CardPay) | `TatraBankaSimpleMailParser` |
| Šifrované výpisy | Viac transakcií šifrovaných cez PGP | `TatraBankaStatementMailParser` |

## Bežné notifikácie

### TatraBankaMailParser

Spracúva bežné e-maily o transakcii, ktoré posiela TatraBanka.

```php
<?php

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);
```

### Podporované polia

- **Suma** (`getAmount(): ?float`) – suma transakcie (kladná pri príchode, záporná pri odchode)
- **Mena** (`getCurrency(): ?string`) – kód meny (EUR, USD a ďalšie)
- **Číslo účtu** (`getAccountNumber(): ?string`) – cieľový účet (IBAN)
- **Zdrojový účet** (`getSourceAccountNumber(): ?string`) – číslo zdrojového účtu
- **Dátum transakcie** (`getTransactionDate(): int|false|null`) – unixová časová pečiatka
- **Variabilný symbol** (`getVs(): ?string`) – variabilný symbol (VS)
- **Špecifický symbol** (`getSs(): ?string`) – špecifický symbol (ŠS)
- **Konštantný symbol** (`getKs(): ?string`) – konštantný symbol (KS)
- **Správa pre príjemcu** (`getReceiverMessage(): ?string`) – správa pre príjemcu
- **Popis** (`getDescription(): ?string`) – popis transakcie

### Ukážka formátu e-mailu

```
Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS9087654321/KS5428175648
Informacia pre prijemcu: test-sprava

S pozdravom
TATRA BANKA, a.s.
```

### Príklad spracovania

```php
$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent !== null) {
    echo $mailContent->getAmount();              // 12.31
    echo $mailContent->getCurrency();            // "EUR"
    echo $mailContent->getAccountNumber();       // "SK9812353347235"
    echo $mailContent->getSourceAccountNumber(); // "1100/000000-261426464"
    echo $mailContent->getVs();                  // "1234056789"
    echo $mailContent->getSs();                  // "9087654321"
    echo $mailContent->getKs();                  // "5428175648"
    echo $mailContent->getReceiverMessage();     // "test-sprava"
}
```

## Platby ComfortPay

### TatraBankaSimpleMailParser

Spracúva potvrdenia ComfortPay a CardPay vo formáte kľúč=hodnota.

```php
<?php

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;

$parser = new TatraBankaSimpleMailParser();
$mailContent = $parser->parse($emailBody);
```

### Podporované polia

- **Suma** (`getAmount(): ?float`) – suma transakcie
- **Mena** (`getCurrency(): ?string`) – kód meny (číselný, napríklad `"978"` pre EUR)
- **Variabilný symbol** (`getVs(): ?string`) – variabilný symbol
- **Výsledok** (`getRes(): ?string`) – výsledok transakcie (OK, FAIL)
- **Autorizačný kód** (`getAc(): ?string`) – autorizačný kód
- **ID klienta** (`getCid(): ?string`) – ID klienta (ComfortPay)
- **Údaj o karte** (`getCc(): ?string`) – maskované číslo karty
- **ID transakcie** (`getTid(): ?string`) – ID transakcie
- **Typ transakcie** (`getTxn(): ?string`) – typ transakcie (PA a podobne)
- **Návratový kód** (`getRc(): ?string`) – návratový kód
- **Podpis** (`getSign(): ?string`) – podpis HMAC
- **Dátum transakcie** (`getTransactionDate(): int|false|null`) – unixová časová pečiatka

### Ukážka formátu e-mailu

```
VS=1152201233 RES=OK AC=558058 SIGN=C0CBF27F5D97841E
```

```
AMT=44.88 CURR=978 VS=4444255333 RES=OK AC=644311 TRES=OK 
CID=824452 CC=************1111 TID=11224444 
TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1
```

### Príklad spracovania

```php
$emailBody = 'AMT=44.88 CURR=978 VS=4444255333 RES=OK AC=644311 ' .
             'TRES=OK CID=824452 CC=************1111 TID=11224444 ' .
             'TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1';

$parser = new TatraBankaSimpleMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent !== null) {
    echo $mailContent->getAmount();   // 44.88
    echo $mailContent->getCurrency(); // "978"
    echo $mailContent->getVs();       // "4444255333"
    echo $mailContent->getRes();      // "OK"
    echo $mailContent->getCid();      // "824452"
    echo $mailContent->getCc();       // "************1111"
}
```

## Výpisy šifrované cez PGP

### TatraBankaStatementMailParser

Spracúva bankové výpisy šifrované cez PGP s viacerými transakciami. Na dešifrovanie potrebuje `TatraBankaMailDecryptor`.

### Nastavenie

Najprv nastavte PGP dešifrátor so súkromným kľúčom:

```php
<?php

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;

// Inicializácia dešifrátora
$decryptor = new TatraBankaMailDecryptor(
    '/path/to/your/private-key.asc',  // Cesta k súkromnému kľúču PGP
    'your-passphrase'                  // Heslo k súkromnému kľúču
);

// Parser s dešifrátorom
$parser = new TatraBankaStatementMailParser($decryptor);
```

### Spracovanie šifrovaných výpisov

```php
// Viac transakcií zo šifrovaného e-mailu
$mailContents = $parser->parseMulti($encryptedEmailBody);

foreach ($mailContents as $mailContent) {
    echo $mailContent->getAmount() . " " . $mailContent->getCurrency() . "\n";
    echo "VS: " . $mailContent->getVs() . "\n";
    echo "Account: " . $mailContent->getAccountNumber() . "\n";
    echo "Bank: " . $mailContent->getKs() . "\n";
}
```

### TatraBankaMailDecryptor

Dešifrátor robí OpenPGP dešifrovanie výpisov TatraBanky.

#### Konštruktor

```php
public function __construct(
    string $privateKeyPath,  // Cesta k súboru súkromného kľúča PGP (.asc)
    string $passphrase       // Heslo, ktorým sa súkromný kľúč odomkne
)
```

#### Metóda

```php
public function decrypt(string $contents): ?string
```

Dešifruje správu vo formáte PGP armor a vráti čistý text, alebo `null` pri zlyhaní.

#### Spracovanie chýb

```php
try {
    $decryptor = new TatraBankaMailDecryptor(
        '/path/to/private-key.asc',
        'passphrase'
    );
    
    $decrypted = $decryptor->decrypt($encryptedContent);
    
    if ($decrypted === null) {
        throw new \RuntimeException('Decryption failed');
    }
    
} catch (\Exception $e) {
    // Ošetrenie chýb:
    // - chýbajúci alebo neplatný súbor súkromného kľúča
    // - nesprávne heslo kľúča
    // - poškodená správa PGP
    error_log('Decryption error: ' . $e->getMessage());
}
```

## Spracovanie chýb

Všetky parsery TatraBanky vrátia `null`, keď formát e-mailu nespoznajú:

```php
$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent === null) {
    // Formát e-mailu sa nepodarilo spoznať alebo chýbajú povinné polia
    error_log('Failed to parse TatraBanka email');
} else {
    // Spracovanie prebehlo
    processTransaction($mailContent);
}
```

## Podporované formáty dátumu

`TatraBankaMailParser` pozná viac formátov dátumu:

- `16.1.2015 12:51` (deň.mesiac.rok hodina:minúta)
- `16. 1. 2015 12:51` (s medzerami)

Parser skúsi viac formátov a keď žiadny nesedí, použije záložné `strtotime()`.

## Ďalšie kroky

- Ďalšie [príklady](/sk/4.x/guide/examples)
- Ostatné banky: [ČSOB CZ](/sk/4.x/guide/csob-cz), [ČSOB SK](/sk/4.x/guide/csob-sk), [VÚB](/sk/4.x/guide/vub)
- Úplná [referencia API](/sk/4.x/api/reference)
- [Prechod na 5.x](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md)
