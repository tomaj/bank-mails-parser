# Šifrovaný výpis kariet

Tatra banka môže denný výpis platieb kartou posielať ako PGP správu zašifrovanú vaším verejným kľúčom. Každý riadok dešifrovaného výpisu je jedna transakcia kartou. Parser výpisov sa na fasáde zaregistruje, len keď jej odovzdáte `TatraBankaMailDecryptor` so súkromným kľúčom a heslom.

Zdroj: [`examples/encrypted-statement`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/encrypted-statement)

## E-mail

Telo je MIME s PGP správou v ASCII armor:

::: details statement-body.txt
<<< @/../examples/encrypted-statement/mails/statement-body.txt{text}
:::

Po dešifrovaní je to tabuľka oddelená zvislými čiarami s riadkom hlavičky:

```text
Dátum zúčtovania|Dátum uzávierky POS|Dátum trans.|Čas trans.|Terminál ID|…|Suma transakcie|Mena transakcie|…|Variabilný symbol|Číslo účtu|Kód banky
20190315|20190314|20190314|85250|09071050|…|13.37|EUR|…|4169344603|2621234415|1100
```

## Kód

<<< @/../examples/encrypted-statement/run.php

`private-key.asc` a heslo `heslo` patria jednorazovému testovaciemu kľúču, ktorý existuje len na dešifrovanie tejto ukážky. Skutočný kľúč nikdy necommitujte: jeho cestu a heslo čítajte z úložiska tajomstiev.

## Výstup

<div class="example-output">

<<< @/../examples/encrypted-statement/expected-output.txt{text}

</div>

## Čo si všimnúť

- **Stĺpce.** `money` je `Suma transakcie` + `Mena transakcie` (s `.` ako desatinným oddeľovačom), `transactionDate` je `Dátum zúčtovania` o polnoci v `Europe/Bratislava`, `vs` je `Variabilný symbol` a `accountNumber` je `Číslo účtu`, účet obchodníka pripísaný za daný predaj. Na každom riadku môže byť iný.
- **Všetko alebo nič.** Ak sa jeden riadok nedá prečítať, `parseAll()` vyhodí jednu `InvalidEmailFormatException` so zoznamom zlých riadkov; nikdy nevráti výpis, z ktorého potichu chýba riadok.
- **Chyby nastavenia nie sú chyby spracovania.** Chýbajúci alebo nečitateľný súbor kľúča a nesprávne heslo vyhodia `ConfigurationException` a `tryParse()` ju pustí ďalej, takže pokazené nasadenie nevyzerá ako „dnes žiadne platby“.
- **Bez dešifrátora** predvolená fasáda parser výpisov nemá, takže šifrovaný e-mail je len nepodporovaný e-mail: `parse()` vyhodí `UnsupportedBankException` a `tryParse()` vráti `null`.
- Dešifrátor používa [singpolyma/openpgp-php](https://github.com/singpolyma/openpgp-php), závislosť tohto balíka, takže netreba binárku `gpg` ani rozšírenie.
