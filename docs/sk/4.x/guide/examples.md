# Príklady (4.x)

::: warning Staršia verzia
Toto je dokumentácia verzie **4.x**. Aktuálna verzia s nemennou architektúrou je v [dokumentácii 5.x](/sk/). Postup prechodu je v [návode na prechod na 5.0](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
:::

Praktické príklady spracovania rôznych bankových e-mailov. Všetky používajú skutočné parsery z knižnice.

## Základné spracovanie e-mailu

### Jednoduchá transakcia

```php
<?php

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$emailBody = 'Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS/KS
Informacia pre prijemcu: test-sprava

S pozdravom
TATRA BANKA, a.s.';

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent !== null) {
    echo $mailContent->getAmount();              // 12.31
    echo $mailContent->getCurrency();            // "EUR"
    echo $mailContent->getAccountNumber();       // "SK9812353347235"
    echo $mailContent->getSourceAccountNumber(); // "1100/000000-261426464"
    echo $mailContent->getVs();                  // "1234056789"
    echo $mailContent->getReceiverMessage();     // "test-sprava"
}
```

## E-maily s viacerými transakciami

### ČSOB Česká republika

```php
<?php

use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

$emailBody = 'Vážený kliente,

Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +1 234,56 CZK
Účet protistrany: 1122334455/9999
Variabilní symbol: 23456789

Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +987,65 CZK
Účet protistrany: 9988776655/1111
Variabilní symbol: 78787878';

$parser = new CsobMailParser();
$mailContents = $parser->parseMulti($emailBody);

echo count($mailContents);  // 2

foreach ($mailContents as $mailContent) {
    echo $mailContent->getAmount() . " " . $mailContent->getCurrency() . "\n";
    echo "VS: " . $mailContent->getVs() . "\n";
}
```

## Výpisy šifrované cez PGP

### Šifrovaný výpis TatraBanky

```php
<?php

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;

// Dešifrátor sa inicializuje súkromným kľúčom
$decryptor = new TatraBankaMailDecryptor(
    '/path/to/private-key.asc',
    'your-passphrase'
);

// Spracovanie šifrovaného e-mailu
$parser = new TatraBankaStatementMailParser($decryptor);
$mailContents = $parser->parseMulti($encryptedEmailBody);

// Viac transakcií z jedného výpisu
foreach ($mailContents as $mailContent) {
    echo $mailContent->getAmount() . " " . $mailContent->getCurrency() . "\n";
    echo "VS: " . $mailContent->getVs() . "\n";
    echo "Account: " . $mailContent->getAccountNumber() . "\n";
}
```

## Platby ComfortPay

### TatraBanka ComfortPay

```php
<?php

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;

$emailBody = 'VS=1152201233 RES=OK AC=558058 SIGN=C0CBF27F5D97841E';

$parser = new TatraBankaSimpleMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent !== null) {
    echo $mailContent->getVs();    // "1152201233"
    echo $mailContent->getRes();   // "OK"
    echo $mailContent->getAc();    // "558058"
    echo $mailContent->getSign();  // "C0CBF27F5D97841E"
}
```

## Spracovanie chýb

### Kontrola, či sa správa nepodarila prečítať

```php
<?php

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent === null) {
    // Formát e-mailu sa nepodarilo spoznať alebo spracovanie zlyhalo
    error_log("Unable to parse email - format not recognized");
} else {
    // Spracovanie prebehlo, transakciu možno zaúčtovať
    processTransaction($mailContent);
}
```

## Napojenie na IMAP

### Stiahnutie a spracovanie bankových e-mailov

```php
<?php

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

// Príklad s bežnou knižnicou IMAP
$mailbox = imap_open('{imap.example.com:993/imap/ssl}INBOX', 'user@example.com', 'password');
$emails = imap_search($mailbox, 'FROM "nonstopbanking@tatrabanka.sk"');

if ($emails) {
    foreach ($emails as $emailId) {
        $emailBody = imap_body($mailbox, $emailId);
        
        $parser = new TatraBankaMailParser();
        $mailContent = $parser->parse($emailBody);
        
        if ($mailContent !== null) {
            // Spracovanie transakcie
            saveTransaction($mailContent);
            
            // Označenie ako vybavené
            imap_setflag_full($mailbox, $emailId, '\\Seen');
        }
    }
}

imap_close($mailbox);
```

## Parser VÚB

### E-mail VÚB v čistom texte

```php
<?php

use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

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
}
```

## Ďalšie kroky

- Parsery jednotlivých bánk: [TatraBanka](/sk/4.x/guide/tatrabanka), [ČSOB CZ](/sk/4.x/guide/csob-cz), [ČSOB SK](/sk/4.x/guide/csob-sk), [VÚB](/sk/4.x/guide/vub)
- Úplná [referencia API](/sk/4.x/api/reference)
- Ako [pridať parser novej banky](/sk/4.x/guide/adding-bank)
- [Prechod na 5.x](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md)
