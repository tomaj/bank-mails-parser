# ČSOB Slovensko and ČSOB Česká republika

ČSOB in Slovakia (`csob.sk`) and in the Czech Republic (`csob.cz`) send different mails, read by `SkCsobMailParser` and `CsobMailParser`. Both can put several transactions in one mail, so use `parseAll()`: it returns a list with one `MailContent` per transaction. (`parse()` on the facade throws `MultipleTransactionsException` for such a mail instead of returning only the first one.)

Source: [`examples/csob`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/csob)

## The mails

A ČSOB Slovensko SEPA payment:

<<< @/../examples/csob/mails/csob-sk-sepa.txt{text}

A ČSOB CZ mail with two transactions, each followed by the account balance:

<<< @/../examples/csob/mails/csob-cz-two-transactions.txt{text}

::: details An instant payment from ČSOB CZ
<<< @/../examples/csob/mails/csob-cz-instant.txt{text}
:::

## The code

<<< @/../examples/csob/run.php

## The output

<div class="example-output">

<<< @/../examples/csob/expected-output.txt{text}

</div>

## What to notice

- **Accounts.** On ČSOB SK, `accountNumber` is the payer (`z účtu`) and `ownAccountNumber` your account (`na účte`). On ČSOB CZ, `accountNumber` is your account (`na účtu`) and `sourceAccountNumber` the counterparty (`Účet protistrany`).
- **The balance chain.** In the two-transaction mail, the first balance plus the second amount must equal the second balance (1 234 567,89 + 987,65 = 1 235 555,54). When a mail with several transactions has balance lines and they do not add up, the whole mail is rejected; see [Error handling](./error-handling). A mail without any balance line is parsed as in 4.1.0, so check the amounts against a statement or the bank's API.
- **Payer text is not bank data.** `Název protistrany`, `Zpráva příjemci`, `informácia pre príjemcu` and the other payer fields are blanked before the amount, account and date are read, so a payer cannot forge a second transaction in a message. The variable symbol can still come from such a field, as in 4.1.0 (on ČSOB CZ, `Identifikace` even replaces `Variabilní symbol`), so match a payment by its amount too.
- **Dates** have no time on these mails, so `transactionDate` is midnight in `Europe/Bratislava`.

See [ČSOB CZ](/guide/csob-cz) and [ČSOB SK](/guide/csob-sk) for the full rules.
