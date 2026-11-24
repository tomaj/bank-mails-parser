# VÚB

VÚB banka mails one payment as `Label: value` lines. `VubMailParser` reads it.

Source: [`examples/vub`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/vub)

## The mail

<<< @/../examples/vub/mails/payment.txt{text}

## The code

<<< @/../examples/vub/run.php

## The output

<div class="example-output">

<<< @/../examples/vub/expected-output.txt{text}

</div>

## What to notice

- **Two accounts.** `accountNumber` is the payer (`Z účtu`, "from account"), as in 4.x. `ownAccountNumber` is your account (`Na účet`, "to account").
- **Currency.** `Suma: 13,37` names no currency, so it is EUR. A line such as `Suma: 13,37 CZK` keeps CZK. The decimal mark is a comma; `13.37` on this line is not an amount and leaves `money` null.
- **Symbols.** `VS`, `KS` and the specific symbol from `ŠS:`, `SS:` or `S:`.
- **`sign`** is the `SIGN` line, stored and not verified.
- **Detection is strict.** `supports()` needs `Dátum`, `Z účtu`, `Na účet`, `Suma`, `VS` and `Stav` or `SIGN`, each at the start of a line, so an ordinary invoice with `Suma` and `VS` is not taken for a VÚB payment.

See the [VÚB guide](/guide/vub) for every field and pattern.
