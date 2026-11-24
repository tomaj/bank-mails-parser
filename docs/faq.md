---
title: FAQ
description: "Common questions about bank-mails-parser: supported banks, parseAll, time zones, null amounts, forwarded mail and payment safety."
---

# FAQ

## Which banks and mails are supported?

Tatra banka b-mail notifications (`TatraBankaMailParser`), CardPay and ComfortPay result mails (`TatraBankaSimpleMailParser`), PGP-encrypted Tatra banka card statements (`TatraBankaStatementMailParser`), ČSOB Česká republika (`CsobMailParser`), ČSOB Slovensko (`SkCsobMailParser`) and VÚB (`VubMailParser`). Other banks need a [custom parser](/examples/custom-parser), and a contributed one is welcome: see [Adding a new bank](/guide/adding-bank).

## Should I call parse() or parseAll()?

`parseAll()` in anything that processes a mailbox. ČSOB mails and statements can carry several transactions; for those `parse()` throws `MultipleTransactionsException` and `tryParse()` returns `null`, so a loop built on them would skip real payments. `parse()` is fine when you know the mail has one transaction, for example in a test.

## If a mail parses, is the payment real?

Not by itself. Parsing proves the mail has the bank's shape. Check that the mail is authentic (DKIM for the bank's domain), verify the CardPay `HMAC`, and pair a payment with an order by both the variable symbol and the amount, because a payer can type any symbol into a message. See [Security](/security).

## A real bank mail throws UnsupportedBankException. Why?

Usually the sender. With a sender, only parsers of the sender's domain are asked, so a notification forwarded from `accounting@your-company.example` matches no bank. Pass the bank's address (the original `From`), or set up the forward so the original sender is kept. Otherwise the bank may have changed the template: compare the mail with the examples in the bank's guide, and [open an issue](https://github.com/tomaj/bank-mails-parser/issues) with an anonymised copy.

## Why are the dates one or two hours off compared to 4.x?

5.x reads Slovak and Czech bank dates as `Europe/Bratislava` wall time, which is what the bank means. 4.x used the server's `date.timezone`, so on a UTC server every timestamp moves by +1 hour in winter and +2 hours in summer. A 14-digit CardPay `TIMESTAMP` is UTC. See [Upgrading](/upgrade).

## Why is money null?

The transaction was recognised but its amount could not be used: a CardPay `CURR` that is not an ISO 4217 code, or a ČSOB or VÚB amount that is missing, blank or unreadable. The rest of the transaction is still returned, as in 4.x. A Tatra banka notification, a CardPay `AMT` that is not a number and a statement row with a bad amount throw `InvalidEmailFormatException` instead.

## How do I tell incoming from outgoing payments?

By the sign: `$mail->money?->isNegative()` for an outgoing payment, `isPositive()` for an incoming one. There is no separate type field, because the mails do not label direction reliably.

## Can I pass the raw .eml file or an HTML mail?

Pass the decoded plain-text body. Decode base64 or quoted-printable and pick the `text/plain` part with a mail library first; the [mailbox example](/examples/mailbox) does it for simple plain-text messages. ČSOB bodies that use `<br>`, `<p>` or `<div>` as line breaks are normalised; other HTML is not supported.

## Why is the variable symbol taken from the payer's message?

Because 4.1.0 did, and integrations depend on it: when the bank's symbol field is empty, many payers write the symbol into the message. The bank guides list the exact order. It is also why you must compare the amount before you mark an order paid.

## Does it need gpg or a PHP extension for statements?

No. Decryption uses `singpolyma/openpgp-php`, a pure-PHP dependency installed with the package. You need the private key file and its passphrase; see the [encrypted statement example](/examples/encrypted-statement).

## Which PHP versions are supported?

PHP 8.4, 8.5 and 8.6, all required in CI, and PHP 8.7 (nightly) is tested without failing the build. 5.x requires `ext-mbstring`. 4.x supports older PHP and gets security fixes only, until 2027-04-12.

## Where are the 4.x docs?

In the version menu, or directly at [4.x](/4.x/). The [upgrade guide](/upgrade) lists every change from 4.x to 5.0.
