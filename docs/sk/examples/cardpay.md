# CardPay a ComfortPay

Keď zákazník zaplatí kartou cez CardPay Tatra banky (alebo ComfortPay, variant s uloženou kartou), banka môže obchodníkovi poslať výsledok e-mailom ako jeden riadok párov `KEY=value`. Číta ho `TatraBankaSimpleMailParser`. Tento recept spracuje úspešnú platbu CardPay, úspešnú platbu ComfortPay a neúspešnú.

Zdroj: [`examples/cardpay`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/cardpay)

## E-maily

<<< @/../examples/cardpay/mails/comfortpay-ok.txt{text}

::: details Ďalšie dva e-maily
<<< @/../examples/cardpay/mails/cardpay-ok.txt{text}

<<< @/../examples/cardpay/mails/comfortpay-fail.txt{text}
:::

## Kód

<<< @/../examples/cardpay/run.php

## Výstup

<div class="example-output">

<<< @/../examples/cardpay/expected-output.txt{text}

</div>

## Polia

| Kľúč | Vlastnosť | Význam |
|------|-----------|--------|
| `AMT` + `CURR` | `money` | Suma s `.` ako desatinným oddeľovačom. `CURR` je číslo ISO 4217 (`978` je EUR); chýbajúce `CURR` je EUR, neznáme nechá `money` null a zvyšok ponechá |
| `VS` | `vs` | Variabilný symbol, ktorý ste poslali bráne |
| `RES` / `TRES` | `res`, `tres` | Výsledok, `OK` alebo `FAIL`. Keď je prítomné `TRES`, `res` je hodnota `TRES` a `tres` ju uloží tiež |
| `AC` | `ac` | Autorizačný kód, len pri úspešnej platbe |
| `CID` | `cid` | Identifikátor zákazníka ComfortPay (uloženej karty) |
| `CC` | `cc` | Maskované číslo karty |
| `TID` | `tid` | Identifikátor transakcie |
| `TXN` | `txn` | Typ transakcie CardPay, napríklad `PA` |
| `RC` | `rc` | Návratový kód |
| `HMAC` / `SIGN` | `sign` | Podpis tak, ako prišiel. Keď sú prítomné oba, `sign` je HMAC |
| `TIMESTAMP` | `transactionDate` | 14 číslic `dmYHis` v UTC; 10 číslic je Unix timestamp. Chýbajúci znamená `null` |

Číta sa len prvý súvislý blok riadkov `KEY=value`, slová na tomto riadku, ktoré nie sú `KEY=value`, sa preskočia (takže `Vysledok: VS=… RES=OK …` funguje), citované riadky začínajúce `>` sa ignorujú a kľúč zopakovaný s inou hodnotou vyhodí `InvalidEmailFormatException` (pozrite si [Ošetrenie chýb](./error-handling)).

::: warning Podpis sa neoveruje
Parser skopíruje `HMAC`/`SIGN` do `sign`; nekontroluje ho, pretože nemá kľúč vašej brány. Skôr než objednávku označíte ako zaplatenú, overte ho kľúčom brány rovnako, ako overujete návratovú URL CardPay (napríklad cez [omnipay-tatra](https://github.com/tomaj/omnipay-tatra)), a skontrolujte DKIM e-mailu. Pozrite si [Bezpečnosť](/sk/security).
:::
