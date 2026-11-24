# Ošetrenie chýb

Každá metóda spracovania má rovnakú zmluvu: `parse()` vráti jednu transakciu alebo vyhodí výnimku, `tryParse()` namiesto výnimky pri chybe spracovania vráti `null` a `parseAll()` vráti každú transakciu alebo vyhodí výnimku. Tento recept spustí všetky tri na štyroch e-mailoch, ktoré sa nedajú prečítať ako jedna platba, a potom ukáže, ako `Money` odmietne sumy, ktoré by musel hádať.

Zdroj: [`examples/error-handling`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/error-handling)

## E-maily

| Súbor | Problém |
|-------|---------|
| `not-a-bank.txt` | Avízo o zásielke. Nepodporuje ho žiadny parser |
| `two-transactions.txt` | Platný e-mail ČSOB CZ s dvoma transakciami |
| `broken-balance.txt` | Ten istý e-mail so zmeneným druhým zostatkom, takže zostatky už nesedia |
| `card-result-two-amounts.txt` | Výsledok CardPay, ktorý obsahuje `AMT` dvakrát s rôznymi hodnotami |

::: details broken-balance.txt
<<< @/../examples/error-handling/mails/broken-balance.txt{text}
:::

<<< @/../examples/error-handling/mails/card-result-two-amounts.txt{text}

## Kód

<<< @/../examples/error-handling/run.php

## Výstup

<div class="example-output">

<<< @/../examples/error-handling/expected-output.txt{text}

</div>

## Ako čítať výsledok

| Situácia | `parse()` | `tryParse()` | `parseAll()` |
|----------|-----------|--------------|--------------|
| E-mail nepodporuje žiadny parser | `UnsupportedBankException` | `null` | `UnsupportedBankException` |
| Podporujú ho dva parsery | `AmbiguousBankException` | `null` | `AmbiguousBankException` |
| Viac transakcií | `MultipleTransactionsException` | `null` | každá transakcia |
| Bankový e-mail s pokazenými alebo protirečivými poľami | `InvalidEmailFormatException` | `null` | `InvalidEmailFormatException` |
| Chýbajúci súbor kľúča alebo nesprávne heslo k výpisu | `ConfigurationException` | `ConfigurationException` | `ConfigurationException` |

- Všetky implementujú `Tomaj\BankMailsParser\Exception\BankMailsParserException`, takže jeden `catch` zachytí všetko z balíka. Chyby spracovania dedia z `ParseException`; `ConfigurationException` a `InvalidArgumentException` nie.
- Pokazená reťaz zostatkov je knižnica, ktorá odmieta e-mail, do ktorého mohol platiteľ napísať falošnú transakciu: keď riadky zostatku od banky sumy nepotvrdia, z e-mailu sa nevráti nič.
- `Money::fromDecimalString()` bez desatinného oddeľovača odmietne `1.234`: môže to byť 1,234 aj 1234. Parsery vždy odovzdávajú oddeľovač banky. `12.345` s `.` ako oddeľovačom má tri desatinné miesta, viac, ako EUR dovoľuje, a namiesto zaokrúhlenia sa odmietne. Obe vyhodia `Tomaj\BankMailsParser\Exception\InvalidArgumentException`, ktorú parser zmení na `InvalidEmailFormatException`.

Celú hierarchiu uvádzajú [Výnimky](/sk/guide/exceptions).
