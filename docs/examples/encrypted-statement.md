# Encrypted card statement

Tatra banka can send the daily statement of card payments as a PGP message encrypted with your public key. Each row of the decrypted statement is one card transaction. The statement parser is registered on the facade only when you give it a `TatraBankaMailDecryptor` with your private key and passphrase.

Source: [`examples/encrypted-statement`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/encrypted-statement)

## The mail

The body is MIME with an ASCII-armoured PGP message:

::: details statement-body.txt
<<< @/../examples/encrypted-statement/mails/statement-body.txt{text}
:::

Decrypted, it is a pipe-separated table with a header row:

```text
Dátum zúčtovania|Dátum uzávierky POS|Dátum trans.|Čas trans.|Terminál ID|…|Suma transakcie|Mena transakcie|…|Variabilný symbol|Číslo účtu|Kód banky
20190315|20190314|20190314|85250|09071050|…|13.37|EUR|…|4169344603|2621234415|1100
```

## The code

<<< @/../examples/encrypted-statement/run.php

`private-key.asc` and the passphrase `heslo` belong to a throwaway test key that only exists to decrypt this sample. Never commit your real key: read its path and passphrase from your secret store.

## The output

<div class="example-output">

<<< @/../examples/encrypted-statement/expected-output.txt{text}

</div>

## What to notice

- **Columns.** `money` is `Suma transakcie` + `Mena transakcie` (with `.` as the decimal mark), `transactionDate` is `Dátum zúčtovania` (the settlement date) at midnight in `Europe/Bratislava`, `vs` is `Variabilný symbol`, and `accountNumber` is `Číslo účtu`, the merchant account credited for that sale. It can differ from row to row.
- **All or nothing.** If one row cannot be read, `parseAll()` throws one `InvalidEmailFormatException` that lists the bad rows; it never returns a statement with a row silently missing.
- **Setup errors are not parse errors.** A missing or unreadable key file and a wrong passphrase throw `ConfigurationException`, and `tryParse()` lets it through, so a broken deployment does not look like "no payments today".
- **Without a decryptor** the default facade has no statement parser, so the encrypted mail is just an unsupported mail: `parse()` throws `UnsupportedBankException` and `tryParse()` returns `null`.
- The decryptor uses [singpolyma/openpgp-php](https://github.com/singpolyma/openpgp-php), a dependency of this package, so no `gpg` binary or extension is needed.
