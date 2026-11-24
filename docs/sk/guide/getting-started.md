# Začíname

Bank Mails Parser číta e-mailové notifikácie o platbách slovenských a českých bánk (Tatra banka, VÚB, ČSOB Slovensko a ČSOB Česká republika) a transakciu vráti ako typový nemenný objekt `MailContent`: sumu ako `Money` v najmenších jednotkách, dátum, účty, variabilný, špecifický a konštantný symbol a správu platiteľa. Vaša aplikácia nájde objednávku a zaúčtuje platbu.

## Požiadavky

- PHP 8.4 alebo novšie (testované na 8.4, 8.5 a 8.6)
- `ext-mbstring`
- Composer

## Inštalácia

```bash
composer require tomaj/bank-mails-parser
```

Spolu s ním sa nainštaluje `singpolyma/openpgp-php`, ktorý v čistom PHP dešifruje výpisy Tatra banky.

## Prvý e-mail

Notifikácia b-mail z Tatra banky ako dekódované textové telo:

```text
Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS/KS
Informacia pre prijemcu: test-sprava
```

Odovzdajte ho `BankMailsParser` spolu s adresou odosielateľa. Banku fasáda nájde sama:

```php
<?php

use Tomaj\BankMailsParser\BankMailsParser;

require 'vendor/autoload.php';

$parser = new BankMailsParser();
$mailContent = $parser->parse($emailBody, 'b-mail@tatrabanka.sk');

echo $mailContent->money?->format() . "\n";
echo $mailContent->vs . "\n";
echo $mailContent->accountNumber . "\n";
echo $mailContent->transactionDate?->format('Y-m-d H:i') . "\n";
```

<!-- snippet: output -->
```text
12.31 EUR
1234056789
SK9812353347235
2015-01-16 12:51
```

- `parse()` vráti jednu transakciu alebo vyhodí výnimku: `UnsupportedBankException`, keď e-mail nespozná žiadna banka, `InvalidEmailFormatException`, keď ho banka spozná, ale nedá sa prečítať, a `MultipleTransactionsException`, keď obsahuje viac transakcií.
- `tryParse()` namiesto výnimky pri chybe spracovania vráti `null`.
- `parseAll()` vráti zoznam všetkých transakcií. Použite ho pri spracovaní schránky: e-maily ČSOB a výpisy môžu obsahovať viac ako jednu.

Pozrite si [Rozpoznanie banky](/sk/guide/bank-detection) a [Výnimky](/sk/guide/exceptions).

## Podporované banky

| Banka | E-mail | Parser |
|-------|--------|--------|
| Tatra banka (SK) | b-mail notifikácia o pohybe na účte | `TatraBankaMailParser` |
| Tatra banka (SK) | výsledok platby kartou CardPay / ComfortPay | `TatraBankaSimpleMailParser` |
| Tatra banka (SK) | PGP šifrovaný výpis kariet | `TatraBankaStatementMailParser` |
| ČSOB (CZ) | notifikácia o transakcii, jedna alebo viac v e-maile | `CsobMailParser` |
| ČSOB (SK) | notifikácia o SEPA transakcii, jedna alebo viac v e-maile | `SkCsobMailParser` |
| VÚB (SK) | notifikácia o platbe | `VubMailParser` |

Odovzdajte dekódovaný text e-mailu. Telá ČSOB so zalomeniami `<br>`, `<p>` alebo `<div>` sa znormalizujú; iné HTML sa nespracúva.

## Skôr než zaúčtujete platbu

Spracovaný e-mail ešte nie je overená platba. Overte, že e-mail naozaj prišiel z banky (DKIM), overte podpisy CardPay a objednávku párujte podľa variabilného symbolu **aj** sumy. Prečo, vysvetľuje [Bezpečnosť](/sk/security).

## Ďalšie kroky

- [Príklady](/sk/examples/): spustiteľné recepty pre každú banku, celú schránku a ošetrenie chýb
- [Konfigurácia](/sk/guide/configuration): každý argument konštruktora a metód
- Príručky k bankám: [TatraBanka](/sk/guide/tatrabanka), [ČSOB CZ](/sk/guide/csob-cz), [ČSOB SK](/sk/guide/csob-sk), [VÚB](/sk/guide/vub)
- [Referencia API](/sk/api/reference) a [Časté otázky](/sk/faq)
- [Pridanie novej banky](/sk/guide/adding-bank)
