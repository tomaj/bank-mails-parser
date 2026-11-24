# ČSOB Slovensko a ČSOB Česká republika

ČSOB na Slovensku (`csob.sk`) a v Česku (`csob.cz`) posielajú rôzne e-maily, ktoré čítajú `SkCsobMailParser` a `CsobMailParser`. Oba môžu dať do jedného e-mailu viac transakcií, preto používajte `parseAll()`: vráti zoznam s jedným `MailContent` na každú transakciu. (`parse()` na fasáde pri takom e-maile vyhodí `MultipleTransactionsException`, namiesto toho aby vrátil len prvú.)

Zdroj: [`examples/csob`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/csob)

## E-maily

SEPA platba z ČSOB Slovensko:

<<< @/../examples/csob/mails/csob-sk-sepa.txt{text}

E-mail z ČSOB CZ s dvoma transakciami, za každou nasleduje zostatok účtu:

<<< @/../examples/csob/mails/csob-cz-two-transactions.txt{text}

::: details Okamžitá platba z ČSOB CZ
<<< @/../examples/csob/mails/csob-cz-instant.txt{text}
:::

## Kód

<<< @/../examples/csob/run.php

## Výstup

<div class="example-output">

<<< @/../examples/csob/expected-output.txt{text}

</div>

## Čo si všimnúť

- **Účty.** Pri ČSOB SK je `accountNumber` platiteľ (`z účtu`) a `ownAccountNumber` váš účet (`na účte`). Pri ČSOB CZ je `accountNumber` váš účet (`na účtu`) a `sourceAccountNumber` protistrana (`Účet protistrany`).
- **Reťaz zostatkov.** V e-maile s dvoma transakciami sa prvý zostatok plus druhá suma musí rovnať druhému zostatku (1 234 567,89 + 987,65 = 1 235 555,54). Keď e-mail s viacerými transakciami má riadky zostatku a tie nesedia, odmietne sa celý; pozrite si [Ošetrenie chýb](./error-handling). E-mail bez akéhokoľvek riadku zostatku sa spracuje ako vo verzii 4.1.0, takže sumy porovnajte s výpisom alebo cez API banky.
- **Text platiteľa nie sú údaje banky.** `Název protistrany`, `Zpráva příjemci`, `informácia pre príjemcu` a ďalšie polia platiteľa sa vymažú skôr, než sa prečíta suma, účet a dátum, takže platiteľ nemôže podvrhnúť druhú transakciu v správe. Variabilný symbol z takého poľa pochádzať stále môže, ako vo verzii 4.1.0 (pri ČSOB CZ `Identifikace` dokonca nahradí `Variabilní symbol`), preto platbu párujte aj podľa sumy.
- **Dátumy** v týchto e-mailoch nemajú čas, takže `transactionDate` je polnoc v `Europe/Bratislava`.

Celé pravidlá uvádzajú [ČSOB CZ](/sk/guide/csob-cz) a [ČSOB SK](/sk/guide/csob-sk).
