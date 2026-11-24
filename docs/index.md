---
layout: home

hero:
  name: Bank Mails Parser
  text: Bank notification emails, parsed
  tagline: Turn the payment emails of Tatra banka, VÚB and ČSOB into typed PHP objects. Amount in minor units, variable symbol, accounts and date, and a clear exception when a mail cannot be trusted.
  image:
    src: /illustrations/hero-light.svg
    alt: A Tatra banka notification email is parsed into a MailContent object with the amount 12.31 EUR and the variable symbol 1234056789
  actions:
    - theme: brand
      text: Get started
      link: /guide/getting-started
    - theme: alt
      text: Examples
      link: /examples/
    - theme: alt
      text: View on GitHub
      link: https://github.com/tomaj/bank-mails-parser

features:
  - icon:
      src: /icons/banks.svg
      alt: Bank
      width: 42px
      height: 42px
    title: Four banks, six mail formats
    details: Tatra banka b-mail, CardPay and ComfortPay results and PGP card statements, VÚB, ČSOB Slovensko and ČSOB Česká republika.
    link: /guide/tatrabanka
    linkText: Bank guides
  - icon:
      src: /icons/detection.svg
      alt: Envelope with a magnifier
      width: 42px
      height: 42px
    title: Bank detection
    details: BankMailsParser picks the parser from the sender's domain and the body, and refuses to guess when two parsers match.
    link: /guide/bank-detection
    linkText: How detection works
  - icon:
      src: /icons/money.svg
      alt: Coins
      width: 42px
      height: 42px
    title: Money without floats
    details: Amounts are integers in minor units with an ISO 4217 currency. Ambiguous separators such as 1.234 are rejected, never guessed.
    link: /api/reference#money
    linkText: Money and Currency
  - icon:
      src: /icons/transactions.svg
      alt: Rows of transactions
      width: 42px
      height: 42px
    title: Several transactions per mail
    details: parseAll() returns every transaction of a ČSOB mail or a statement, and checks the ČSOB balance chain between them.
    link: /examples/csob
    linkText: ČSOB example
  - icon:
      src: /icons/pgp.svg
      alt: Padlock
      width: 42px
      height: 42px
    title: PGP card statements
    details: Give the facade a decryptor with your private key and it reads the encrypted Tatra banka card statement row by row.
    link: /examples/encrypted-statement
    linkText: Statement example
  - icon:
      src: /icons/tested.svg
      alt: Shield with a check mark
      width: 42px
      height: 42px
    title: Tested on real mail shapes
    details: 92 anonymised bank mails with expected results, forged-field cases, 97 % coverage, 95 % mutation score, and every example on this site runs in CI.
    link: /guide/testing
    linkText: Testing your integration
---

<div class="home-more">

## What it looks like

A Tatra banka b-mail notification:

```text
16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS/KS
Informacia pre prijemcu: test-sprava
```

Pass the body and the sender, read typed properties:

```php
use Tomaj\BankMailsParser\BankMailsParser;

$mail = new BankMailsParser()->parse($emailBody, 'b-mail@tatrabanka.sk');

echo $mail->money?->format(), "\n";                          // amount and currency
echo $mail->money?->amount, "\n";                            // integer, minor units
echo $mail->vs, "\n";                                        // variable symbol
echo $mail->transactionDate?->format('Y-m-d H:i P'), "\n";   // Europe/Bratislava
echo $mail->accountNumber, "\n";
```

<!-- snippet: output -->
```text
12.31 EUR
1231
1234056789
2015-01-16 12:51 +01:00
SK9812353347235
```

## Where to go next

- [Getting started](/guide/getting-started) installs the package and parses a first mail.
- [Examples](/examples/) are complete, runnable recipes: one per bank, a whole mailbox matched to orders, an encrypted statement, error handling and a custom parser.
- [Bank detection](/guide/bank-detection) explains how the facade picks a parser and why you should always pass the sender.
- [Security](/security) lists what the library checks and what you still have to check before you book a payment.
- [API reference](/api/reference) documents every public class and property.

</div>
