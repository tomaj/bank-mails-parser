# Tatra banka b-mail

Tatra banka sends a b-mail notification for every movement on an account that has notifications turned on. `TatraBankaMailParser` reads it. This recipe parses three of them: an incoming payment, an outgoing payment in another currency, and a payment whose variable symbol only exists as a structured creditor reference.

Source: [`examples/tatra-banka`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/tatra-banka)

## The mails

An incoming payment. The header line (`… bol zostatok Vasho uctu … zvyseny o …`) is what `supports()` looks for:

<<< @/../examples/tatra-banka/mails/incoming.txt{text}

::: details The other two mails
An outgoing payment in USD with all three symbols:

<<< @/../examples/tatra-banka/mails/outgoing.txt{text}

A payment where `Referencia platitela` is a name and the variable symbol is the `(Ref)` of a structured creditor reference in `Informacia pre prijemcu`:

<<< @/../examples/tatra-banka/mails/structured-reference.txt{text}
:::

## The code

<<< @/../examples/tatra-banka/run.php

## The output

<div class="example-output">

<<< @/../examples/tatra-banka/expected-output.txt{text}

</div>

## What to notice

- **The amount is `Money`, not a float.** `money->amount` is an integer in minor units (`1231` for 12.31 EUR) and `money->format()` prints it with the currency. The direction is the sign: `znizeny o` (decreased by) gives a negative amount. There is no separate "incoming/outgoing" field.
- **The date is in `Europe/Bratislava`**, the bank's wall clock, whatever `date.timezone` your server uses.
- **`accountNumber` is your account** (the one whose balance changed). `sourceAccountNumber` is the counterparty from `Popis transakcie`, and `description` keeps the whole line.
- **Symbols** come from `Referencia platitela: /VS…/SS…/KS…`. An empty `/SS` stays `null`. Without that shape the parser looks for a `VS` token, then for the last number of `Informacia pre prijemcu` (in the third mail the `(Ref)` of a structured creditor reference), then for the last number of `Referencia platitela`, the same order as 4.1.0. The payer writes that message, so match a payment by its amount too, not only by the symbol. See the [Tatra banka guide](/guide/tatrabanka) for the full order.
- **`receiverMessage`** is the payer's message as written, including the structured reference markup.

Next: [CardPay and ComfortPay](./cardpay) results come from Tatra banka too, in a different format.
