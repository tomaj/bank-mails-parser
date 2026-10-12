# ČSOB Slovensko

Parser potvrdzujúcich e-mailov ČSOB Slovensko. Podobá sa na ČSOB CZ, ale text je slovenský a formát sa mierne líši.

## Parser

### SkCsobMailParser

```php
<?php

use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;

$parser = new SkCsobMailParser();
```

## Metódy

### parse(string $content): MailContent

Spracuje jednu transakciu. Keď text nie je platba ČSOB SK, vyhodí `InvalidEmailFormatException`. `tryParse()` namiesto toho vráti `null`.

### parseAll(string $content): array

Spracuje každú transakciu. Keď text nie je platba alebo sa transakcia nedá prečítať, vyhodí `InvalidEmailFormatException`. Prázdny zoznam nevracia.

## Podporované polia

- **Suma** (`money: ?Money`) – suma v najmenších jednotkách a mena. Desatinný text je `toDecimalString()`.
- **Variabilný symbol** (`vs: ?string`) – `/VS`, `/SS` a `/KS` na riadku `referencia platiteľa` (ľubovoľná dĺžka), potom prvý `VS` alebo `KS` na riadku `informácia pre príjemcu`, ale len keď ho referencia nenastavila. Meno platiteľa ako `ADVS2` symbolom nie je. `VS111 nahrada za VS222` je `111`. Správa pre príjemcu sumu ani dátum nezmení. `referencia platiteľa` je text platiteľa. Hlavička v tom texte sa ignoruje, nezaúčtuje sa. E-mail s viacerými transakciami a bez riadku zostatku sa spracuje ako vo verzii 4.1.0; bez tých riadkov sa podvrhnutá transakcia za prázdnym riadkom nedá odlíšiť od skutočnej, preto sumu overte voči banke. `Suma: 50 EUR za obed` ostane správa. Pri párovaní platby skontrolujte aj sumu.
- **Účet** (`accountNumber: ?string`) – účet platiteľa z `z účtu`.
- **Vlastný účet** (`ownAccountNumber: ?string`) – účet za `na účte`.
- **Konštantný symbol** (`ks: ?string`) – konštantný symbol (KS)
- **Správa pre príjemcu** (`receiverMessage: ?string`) – správa k platbe

## Formát e-mailu

ČSOB SK používa slovenčinu a mierne iné názvy polí:

```
dňa 6.9.2019 bola na účte SK13 7500 0000 0040 1942 5381 PETIT PRESS, A.S. zaúčtovaná suma SEPA platobného príkazu:
suma:                    +1150,00 EUR
z účtu:                  SK91 7500 0000 0030 2072 9058
banka:                   CEKOSKBX
detaily platby:
názov protiúčtu:         NOVAK JOZEF
referencia platiteľa:    /VS212049000/SS964/KS0308
informácia pre príjemcu: Maroko 2019 rodicia
```

## Príklad použitia

```php
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;

$emailBody = 'dňa 6.9.2019 bola na účte SK13 7500 0000 0040 1942 5381 PETIT PRESS, A.S. zaúčtovaná suma SEPA platobného príkazu:
suma:                    +1150,00 EUR
z účtu:                  SK91 7500 0000 0030 2072 9058
referencia platiteľa:    /VS212049000/SS964/KS0308
informácia pre príjemcu: Maroko 2019 rodicia';

$parser = new SkCsobMailParser();

try {
    $mailContents = $parser->parseAll($emailBody);
} catch (InvalidEmailFormatException) {
    $mailContents = [];
}

foreach ($mailContents as $mailContent) {
    echo $mailContent->money?->toDecimalString(); // "1150.00"
    echo $mailContent->money?->currency->value;   // "EUR"
    echo $mailContent->accountNumber;             // "SK91 7500 0000 0030 2072 9058"
    echo $mailContent->ownAccountNumber;          // "SK13 7500 0000 0040 1942 5381"
    echo $mailContent->vs;                        // "212049000"
    echo $mailContent->ss;                        // "964"
    echo $mailContent->ks;                        // "0308"
    echo $mailContent->receiverMessage;           // "Maroko 2019 rodicia"
}
```

## Viac transakcií

Rovnako ako ČSOB CZ parser delí e-mail podľa značky „dňa“, aby zvládol viac transakcií:

```php
$emailWithMultipleTransactions = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: +100,00 EUR

dňa 7.9.2019 bola na účte SK92 7500 0000 0030 2072 9059 zaúčtovaná suma SEPA platobného príkazu:
suma: +200,00 EUR
';

$parser = new SkCsobMailParser();
$mailContents = $parser->parseAll($emailWithMultipleTransactions);

echo count($mailContents); // 2
```

Úsek, ktorý nie je SEPA transakcia, sa ignoruje. Transakcia, ktorá sedí a potom má nečitateľnú sumu, sa vráti s `money` rovné `null`. Ostatné riadky sa vrátia aj tak.

Viac transakcií bez riadku zostatku sa spracuje rovnako ako vo verzii 4.1.0. Je to známe obmedzenie: bez `Zostatok na účte po zaúčtovaní sumy platobnej operácie` sa podvrhnutá transakcia v texte platiteľa nedá odlíšiť od skutočnej. Sumu overte voči API banky alebo voči výpisu. Keď e-mail zostatok obsahuje, každá transakcia potrebuje práve jeden. Druhý riadok zostatku v tej istej transakcii vyhodí `InvalidEmailFormatException` s textom `Transaction has more than one balance line`. Predchádzajúci zostatok plus suma sa musia rovnať nasledujúcemu zostatku. Osamotený CR, VT, FF, U+0085, U+2028 alebo U+2029 v správe, ktorá už má LF, ostane obyčajný znak a riadok nerozdelí.

## Čítanie sumy

Parser zvláda slovenský zápis čísel:

- desatinný oddeľovač: čiarka (`,`)
- v sume môže byť biely znak
- predpona môže byť kladná (`+`) aj záporná (`-`)

## Ďalšie kroky

- Ďalšie [príklady](/sk/guide/examples)
- Česká verzia: [ČSOB CZ](/sk/guide/csob-cz)
- Úplná [referencia API](/sk/api/reference)
