# TatraBanka

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

- **Suma** (`money: ?Money`) – `amount` v najmenších jednotkách plus `Currency`. Desatinný text je `toDecimalString()`. Prichádzajúce sumy sú kladné. Tri nenulové desatinné miesta, napríklad `12,315` EUR, vyhodia `InvalidEmailFormatException`.
- **Hlavička** – `bol zostatok Vasho uctu` musí začínať riadok. Úvodné biele znaky sú povolené. `Dobry den, 16.1.2015 12:51 bol zostatok…` sa nespracuje.
- **Číslo účtu** (`accountNumber: ?string`) – cieľový účet (IBAN)
- **Zdrojový účet** (`sourceAccountNumber: ?string`) – číslo zdrojového účtu
- **Dátum transakcie** (`transactionDate: ?DateTimeImmutable`) – číta sa cez `j.n.Y G:i` alebo `j. n. Y G:i`
- **Variabilný symbol** (`vs: ?string`) – `/VS…/SS…/KS…`, potom token `VS`, potom posledné číslo v `Informacia pre prijemcu`, keď chýba `Referencia platitela` (rovnako ako vo verzii 4.1.0), a napokon holá `Referencia platitela`. Prázdny riadok pred tou referenciou ju nestratí. Správu píše platiteľ. Pri párovaní platby skontrolujte aj sumu a ďalší identifikátor, ak ho máte. Správa nemôže zmeniť sumu, účet ani dátum.
- **Špecifický symbol** (`ss: ?string`) – špecifický symbol (ŠS)
- **Konštantný symbol** (`ks: ?string`) – konštantný symbol (KS)
- **Správa pre príjemcu** (`receiverMessage: ?string`) – správa pre príjemcu
- **Popis** (`description: ?string`) – popis transakcie

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

echo $mailContent->money?->toDecimalString(); // "12.31"
echo $mailContent->money?->currency->value;   // "EUR"
echo $mailContent->accountNumber;             // "SK9812353347235"
echo $mailContent->sourceAccountNumber;       // "1100/000000-261426464"
echo $mailContent->vs;                        // "1234056789"
echo $mailContent->ss;                        // "9087654321"
echo $mailContent->ks;                        // "5428175648"
echo $mailContent->receiverMessage;           // "test-sprava"
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

- **Suma** (`money: ?Money`) – je vyplnená, keď sú nastavené `AMT` aj `CURR`. Číselné `978` sa stane `EUR`. Suma bez meny nechá `money` ako `null`. Desatinný znak je `.`.
- **Variabilný symbol** (`vs: ?string`) – variabilný symbol
- **Výsledok** (`res: ?string`) – výsledok transakcie (OK, FAIL)
- **Autorizačný kód** (`ac: ?string`) – autorizačný kód
- **ID klienta** (`cid: ?string`) – ID klienta (ComfortPay)
- **Údaj o karte** (`cc: ?string`) – maskované číslo karty
- **ID transakcie** (`tid: ?string`) – ID transakcie
- **Kód transakcie** (`txn: ?string`) – kód CardPay, napríklad `PA`
- **Návratový kód** (`rc: ?string`) – návratový kód
- **Podpis** (`sign: ?string`) – hodnota HMAC alebo SIGN. Uloží sa a neoveruje. Pred zaúčtovaním platby skontrolujte podpis aj DKIM. Keď je prítomné `TRES`, `res` je táto hodnota a `tres` ju uloží tiež. Duplicitný kľúč s inou hodnotou vyhodí `InvalidEmailFormatException`.
- **Dátum transakcie** (`transactionDate: ?DateTimeImmutable`) – 14-ciferné `TIMESTAMP` je `dmYHis`; 10 číslic je unixová časová pečiatka

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

echo $mailContent->money?->toDecimalString(); // "44.88"
echo $mailContent->money?->currency->value;   // "EUR"
echo $mailContent->vs;                        // "4444255333"
echo $mailContent->res;                       // "OK"
echo $mailContent->cid;                       // "824452"
echo $mailContent->cc;                        // "************1111"
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
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;

$decryptor = new TatraBankaMailDecryptor(
    '/path/to/your/private-key.asc',
    'your-passphrase'
);
$parser = new TatraBankaStatementMailParser($decryptor);

$mailContents = $parser->parseMulti($encryptedEmailBody);

foreach ($mailContents as $mailContent) {
    echo $mailContent->money?->toDecimalString() . ' ' . $mailContent->money?->currency->value . "\n";
    echo "VS: " . $mailContent->vs . "\n";
    echo "Account: " . $mailContent->accountNumber . "\n";
    echo "KS: " . $mailContent->ks . "\n";
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
public function decrypt(string $contents): string
```

Dešifruje správu vo formáte PGP armor a vráti čistý text. Chýbajúca cesta ku kľúču, nečitateľný kľúč alebo zlé heslo vyhodia `ConfigurationException` (nie `ParseException`). `tryParse()` ju pustí ďalej. Správa, ktorá sa nedá dešifrovať, vyhodí `DecryptionException` a parser výpisov ju zabalí do `InvalidEmailFormatException`.

#### Spracovanie chýb

```php
try {
    $decryptor = new TatraBankaMailDecryptor(
        '/path/to/private-key.asc',
        'passphrase'
    );
    
    $decrypted = $decryptor->decrypt($encryptedEmailBody);
    echo $decrypted;
} catch (\Exception $e) {
    // Ošetrenie chýb:
    // - chýbajúci alebo neplatný súbor súkromného kľúča
    // - nesprávne heslo kľúča
    // - poškodená správa PGP
    error_log('Decryption error: ' . $e->getMessage());
}
```

## Spracovanie chýb

`parse()` vyhodí `InvalidEmailFormatException`. `tryParse()` vráti `null`.

```php
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();

try {
    processTransaction($parser->parse($emailBody));
} catch (InvalidEmailFormatException $exception) {
    error_log($exception->getMessage());
}
```

## Podporované formáty dátumu

`TatraBankaMailParser` pozná viac formátov dátumu:

- `16.1.2015 12:51` a `16. 1. 2015 12:51`
- `16.1.2015 12:51:30` (so sekundami)
- `16.1.2015` a `16. 1. 2015` (len dátum)
- `2015-01-16 12:51` a `2015-01-16` (`Y-m-d`, s časom aj bez neho)

Sú to tvary, ktoré prijímala verzia 4.1.0, vrátane starého záložného `strtotime()`. Verzia 5.0 ich číta priamo v pásme `Europe/Bratislava` a `strtotime()` nevolá. Dvojciferný rok (`16.1.15`) sa odmietne a `transactionDate` ostane `null`. Neplatné dátumy sa neposúvajú dopredu. Čas, ktorý po jarnom posune neexistuje (`29.3.2015 2:30`), sa odmietne. Jesenný prekryv (`25.10.2015 2:30`) sa zachová.

## Ďalšie kroky

- Ďalšie [príklady](/sk/guide/examples)
- Ostatné banky: [ČSOB CZ](/sk/guide/csob-cz), [ČSOB SK](/sk/guide/csob-sk), [VÚB](/sk/guide/vub)
- Úplná [referencia API](/sk/api/reference)
