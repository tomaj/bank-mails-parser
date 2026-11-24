# VÚB

VÚB banka posiela jednu platbu ako riadky `Popisok: hodnota`. Číta ju `VubMailParser`.

Zdroj: [`examples/vub`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/vub)

## E-mail

<<< @/../examples/vub/mails/payment.txt{text}

## Kód

<<< @/../examples/vub/run.php

## Výstup

<div class="example-output">

<<< @/../examples/vub/expected-output.txt{text}

</div>

## Čo si všimnúť

- **Dva účty.** `accountNumber` je platiteľ (`Z účtu`), ako vo verzii 4.x. `ownAccountNumber` je váš účet (`Na účet`).
- **Mena.** `Suma: 13,37` menu neuvádza, takže je to EUR. Riadok ako `Suma: 13,37 CZK` si CZK ponechá. Desatinný oddeľovač je čiarka; `13.37` na tomto riadku nie je suma a nechá `money` null.
- **Symboly.** `VS`, `KS` a špecifický symbol z `ŠS:`, `SS:` alebo `S:`.
- **`sign`** je riadok `SIGN`, uložený a neoverený.
- **Rozpoznanie je prísne.** `supports()` potrebuje `Dátum`, `Z účtu`, `Na účet`, `Suma`, `VS` a `Stav` alebo `SIGN`, každé na začiatku riadku, takže obyčajná faktúra so `Suma` a `VS` sa za platbu VÚB nepovažuje.

Všetky polia a vzory uvádza [príručka k VÚB](/sk/guide/vub).
