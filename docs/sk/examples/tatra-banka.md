# b-mail Tatra banky

Tatra banka posiela notifikáciu b-mail o každom pohybe na účte, ktorý má notifikácie zapnuté. Číta ju `TatraBankaMailParser`. Tento recept spracuje tri: prichádzajúcu platbu, odchádzajúcu platbu v inej mene a platbu, ktorej variabilný symbol existuje len ako štruktúrovaná referencia veriteľa.

Zdroj: [`examples/tatra-banka`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/tatra-banka)

## E-maily

Prichádzajúca platba. Riadok hlavičky (`… bol zostatok Vasho uctu … zvyseny o …`) je to, čo hľadá `supports()`:

<<< @/../examples/tatra-banka/mails/incoming.txt{text}

::: details Ďalšie dva e-maily
Odchádzajúca platba v USD so všetkými tromi symbolmi:

<<< @/../examples/tatra-banka/mails/outgoing.txt{text}

Platba, kde `Referencia platitela` je meno a variabilný symbol je `(Ref)` štruktúrovanej referencie veriteľa v `Informacia pre prijemcu`:

<<< @/../examples/tatra-banka/mails/structured-reference.txt{text}
:::

## Kód

<<< @/../examples/tatra-banka/run.php

## Výstup

<div class="example-output">

<<< @/../examples/tatra-banka/expected-output.txt{text}

</div>

## Čo si všimnúť

- **Suma je `Money`, nie float.** `money->amount` je celé číslo v najmenších jednotkách (`1231` pre 12.31 EUR) a `money->format()` ho vypíše s menou. Smer určuje znamienko: `znizeny o` dá zápornú sumu. Samostatné pole „prichádzajúca/odchádzajúca“ neexistuje.
- **Dátum je v `Europe/Bratislava`**, podľa hodín banky, bez ohľadu na `date.timezone` vášho servera.
- **`accountNumber` je váš účet** (ten, ktorého zostatok sa zmenil). `sourceAccountNumber` je protistrana z `Popis transakcie` a `description` si ponechá celý riadok.
- **Symboly** pochádzajú z `Referencia platitela: /VS…/SS…/KS…`. Prázdne `/SS` zostane `null`. Bez tohto tvaru parser hľadá token `VS`, potom posledné číslo z `Informacia pre prijemcu` (v treťom e-maile `(Ref)` štruktúrovanej referencie veriteľa) a potom posledné číslo z `Referencia platitela`, v rovnakom poradí ako 4.1.0. Správu píše platiteľ, takže platbu párujte aj podľa sumy, nielen podľa symbolu. Celé poradie uvádza [príručka k Tatra banke](/sk/guide/tatrabanka).
- **`receiverMessage`** je správa platiteľa tak, ako ju napísal, aj so značkami štruktúrovanej referencie.

Ďalej: aj [CardPay a ComfortPay](./cardpay) výsledky posiela Tatra banka, v inom formáte.
