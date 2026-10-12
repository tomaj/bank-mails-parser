---
layout: home
title: Bank Mails Parser 4.x
---

::: warning Staršia verzia
Toto je dokumentácia verzie **4.x**. Používa staršie meniteľné API s gettermami a settermami.

Aktuálna verzia s nemennou architektúrou je v [dokumentácii 5.x](/sk/). Postup prechodu je v [návode na prechod na 5.0](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
:::

# Bank Mails Parser 4.x

PHP knižnica na spracovanie potvrdzujúcich e-mailov slovenských a českých bánk.

## Funkcie

- 📧 **Viac formátov e-mailov**: čistý text, HTML, šifrovanie PGP
- 💰 **Finančné údaje**: sumy, meny, čísla účtov
- 🔢 **Bankové symboly**: variabilný symbol (VS), konštantný symbol (KS), špecifický symbol (ŠS)
- 🔐 **Dešifrovanie PGP**: spracovanie šifrovaných výpisov
- 🔄 **Viac transakcií**: e-maily s viacerými platbami
- 🛡️ **Typová bezpečnosť**: prísne typovanie pre PHP 8.2+
- ✅ **Otestované**: pokrytie kódu nad 95 %

## Rýchly štart

```bash
composer require tomaj/bank-mails-parser ^4.0
```

```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent) {
    echo "Amount: " . $mailContent->getAmount() . "\n";
    echo "VS: " . $mailContent->getVs() . "\n";
}
```

## Požiadavky

- PHP 8.2, 8.3 alebo 8.4
- Composer

## Podporované banky

- **TatraBanka** (Slovensko) – 3 parsery
- **ČSOB CZ** (Česká republika) – HTML s viacerými transakciami
- **ČSOB SK** (Slovensko) – HTML s viacerými transakciami
- **VÚB** (Slovensko) – čistý text

## Ďalej

- [Začíname](/sk/4.x/guide/getting-started)
- [Referencia API](/sk/4.x/api/reference)
- [Prechod na 5.x](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md)
