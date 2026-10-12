# Príklady

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

echo $mailContent->money?->toDecimalString(); // "12.31"
echo $mailContent->money?->currency->value;   // "EUR"
echo $mailContent->accountNumber;             // "SK9812353347235"
echo $mailContent->sourceAccountNumber;       // "1100/000000-261426464"
echo $mailContent->vs;                        // "1234056789"
echo $mailContent->receiverMessage;           // "test-sprava"
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
    echo $mailContent->money?->toDecimalString() . ' ' . $mailContent->money?->currency->value . "\n";
    echo "VS: " . $mailContent->vs . "\n";
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
    echo $mailContent->money?->toDecimalString() . ' ' . $mailContent->money?->currency->value . "\n";
    echo "VS: " . $mailContent->vs . "\n";
    echo "Account: " . $mailContent->accountNumber . "\n";
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

echo $mailContent->vs;    // "1152201233"
echo $mailContent->res;   // "OK"
echo $mailContent->ac;    // "558058"
echo $mailContent->sign;  // "C0CBF27F5D97841E"
```

## Spracovanie chýb

### Kontrola, či sa správa nepodarila prečítať

`parse()` vyhodí výnimku. `tryParse()` vráti `null`. Pozrite si [Výnimky](/sk/guide/exceptions).

```php
<?php

use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\ParseException;

$parser = new BankMailsParser();

try {
    processTransaction($parser->parse($emailBody, $sender, $subject));
} catch (ParseException $exception) {
    error_log($exception->getMessage());
}

$mailContent = $parser->tryParse($emailBody, $sender, $subject);
if ($mailContent === null) {
    error_log('Unable to parse email');
}
```

## Napojenie na IMAP

### Stiahnutie a spracovanie bankových e-mailov

```php
<?php

use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\ParseException;

// Príklad s bežnou knižnicou IMAP
$mailbox = imap_open('{imap.example.com:993/imap/ssl}INBOX', 'user@example.com', 'password');
$emails = imap_search($mailbox, 'FROM "nonstopbanking@tatrabanka.sk"');

if ($emails) {
    foreach ($emails as $emailId) {
        $emailBody = imap_body($mailbox, $emailId);
        $parser = new BankMailsParser();

        try {
            foreach ($parser->parseAll($emailBody, 'nonstopbanking@tatrabanka.sk') as $mailContent) {
                saveTransaction($mailContent);
            }
        } catch (ParseException) {
            continue;
        }

        imap_setflag_full($mailbox, $emailId, '\\Seen');
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

echo $mailContent->money?->toDecimalString(); // "13.37"
echo $mailContent->money?->currency->value;   // "EUR"
echo $mailContent->vs;                        // "9999999999"
echo $mailContent->ks;                        // "0308"
```

## Ďalšie kroky

- Parsery jednotlivých bánk: [TatraBanka](/sk/guide/tatrabanka), [ČSOB CZ](/sk/guide/csob-cz), [ČSOB SK](/sk/guide/csob-sk), [VÚB](/sk/guide/vub)
- Úplná [referencia API](/sk/api/reference)
- Ako [pridať parser novej banky](/sk/guide/adding-bank)
