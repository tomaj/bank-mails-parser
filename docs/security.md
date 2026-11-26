---
title: Security
description: "How to report a vulnerability in bank-mails-parser, and what the library does and does not check before you book a payment."
---

# Security

A notification mail is input from the internet. Anybody can send you a mail that looks like a bank's, and part of every real bank mail is text the payer wrote. This page lists what the library protects against, what it leaves to you, and how to report a vulnerability.

## What the library checks

- **Only the bank's own lines are bank data.** Payer-written fields (`Informacia pre prijemcu`, `Zpráva příjemci`, `Účel platby`, `Identifikace`, `názov protiúčtu`, `informácia pre príjemcu`, references and the others listed in the bank guides) are blanked before the amount, the currency, the accounts and the date are read. A transaction header or an amount typed into a message cannot become a payment, and it cannot choose the bank during detection.
- **Ambiguity is an error.** When two parsers accept the same mail, the facade throws `AmbiguousBankException` instead of picking one. A CardPay key repeated with a different value, a ČSOB mail whose balances do not add up, and a ČSOB transaction with a second balance line are rejected with `InvalidEmailFormatException`.
- **No guessing with money.** Amounts are integers in minor units. `Money` rejects ambiguous separators (`1.234`), extra non-zero fraction digits and values that do not fit in an integer; nothing is rounded.
- **Quoted history is not a payment.** Lines starting with `>` are ignored.
- **The sender narrows detection.** With a sender, only parsers whose domains match the sender's host are asked.

## What you have to check

- **Who sent the mail.** The library does not verify DKIM, SPF or DMARC; the From header is just a string. Accept bank notifications only when your mail server's `Authentication-Results` says DKIM passed for the bank's domain, or receive them on an address only the bank knows. Then pass the sender so detection is limited to that bank.
- **CardPay and ComfortPay signatures.** `sign` holds the `HMAC` (or `SIGN`) value as sent. It is not verified, because the library does not have your gateway key. Verify it with your gateway key the same way you verify the CardPay return URL (for example with [omnipay-tatra](https://github.com/tomaj/omnipay-tatra)).
- **The amount, not only the symbol.** The variable symbol can come from a message the payer wrote (Tatra banka `Informacia pre prijemcu`, ČSOB CZ `Identifikace` and `Zpráva příjemci`, ČSOB SK `informácia pre príjemcu`), exactly as in 4.1.0. Pair a payment with an order only when the symbol **and** the amount match, plus another identifier when you have one. See the [mailbox example](/examples/mailbox).
- **ČSOB mails without balance lines.** A mail with several transactions and no balance line is parsed as in 4.1.0. Without the bank's balances, a transaction forged into payer text after a blank line cannot be told apart from a real one. Reconcile such payments with a statement or the bank's API.
- **Idempotency.** The same payment can arrive twice (a forwarded mail, a re-run job). Book it once, for example by a unique key on the bank's transaction data.

## Keys and personal data

- Keep the PGP private key for Tatra banka statements outside the web root and out of the repository, readable only by the process that parses mail; read its path and passphrase from your secret store.
- The passphrase stays out of error reports. `TatraBankaMailDecryptor` keeps it in a `\SensitiveParameterValue`, so `var_dump()`, `print_r()`, `var_export()`, Tracy and Symfony's VarDumper show an empty object instead of it, and its constructor argument is marked `#[\SensitiveParameter]`, so exception traces show `Object(SensitiveParameterValue)`. An exception thrown inside the OpenPGP library while the key is decrypted is replaced by a `DecryptionException` with the same message, so the library's trace (which has the passphrase as an argument) is not kept. Your own code that passes the passphrase around (a config array, a DI container definition) is not covered: set `zend.exception_ignore_args = On` in production (the default of `php.ini-production`), so traces contain no arguments at all. `serialize()` of a decryptor (or of a `BankMailsParser` holding one) contains the passphrase in clear text, as in 5.0, so do not put it into a cache or a queue.
- Bank mails contain account numbers, names and payment messages. Store and log them as personal data under GDPR, and do not put raw mail bodies into error messages that reach users or third-party error trackers. The exception messages of this library do not include the sender address or the mail body (an invalid amount is quoted).

<!--@include: ../SECURITY.md{3,}-->
