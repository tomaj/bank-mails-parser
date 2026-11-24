# A payments mailbox

The usual integration: a mailbox (or a mail forward) receives the notifications of every bank you use, and a job reads the new mails, finds the bank, and pairs each payment with an open order. `BankMailsParser` does the detection. This recipe reads a folder of `.eml` files: four bank mails, a newsletter, and a Tatra banka notification sent from a look-alike domain.

Source: [`examples/mailbox`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/mailbox)

## The mailbox

| File | From | Content |
|------|------|---------|
| `01-tatra-banka.eml` | `b-mail@tatrabanka.sk` | b-mail notification, 12.31 EUR |
| `02-cardpay.eml` | `cardpay@tatrabanka.sk` | CardPay result, 33.99 EUR |
| `03-vub.eml` | `notifikacie@vub.sk` | VÚB payment, 13.37 EUR |
| `04-csob-sk.eml` | `info24@csob.sk` | ČSOB SK SEPA payment, 1150.00 EUR |
| `05-newsletter.eml` | `news@shop.example` | not a bank mail |
| `06-forged-sender.eml` | `b-mail@tatrabanka.sk.evil.example` | the Tatra banka mail again, from another domain |

The sender addresses are examples; use the ones your bank really sends from. One of the mails, headers included:

<<< @/../examples/mailbox/mailbox/01-tatra-banka.eml{text}

## The code

<<< @/../examples/mailbox/run.php

## The output

<div class="example-output">

<<< @/../examples/mailbox/expected-output.txt{text}

</div>

## How the facade decides

1. **The sender limits the candidates.** Every parser lists its bank's domains (`bankDomains()`). With a sender, only parsers whose domain matches the sender's host (the domain itself or a subdomain) are asked. `b-mail@tatrabanka.sk.evil.example` matches no bank, so the forged mail is `UnsupportedBankException` even though its body is a perfect Tatra banka notification.
2. **The body picks the parser.** Each remaining parser's `supports()` checks the bank's own header lines. `tatrabanka.sk` has three parsers (b-mail, CardPay, statements); the body decides between them.
3. **Exactly one must match.** No match is `UnsupportedBankException`, more than one is `AmbiguousBankException`. The facade never takes "the first that fits".

<div class="diagram">
  <img class="diagram-light" src="/diagrams/parse-flow-light.svg" alt="Flow of BankMailsParser: filter parsers by sender domain, ask each supports(), throw UnsupportedBankException for none and AmbiguousBankException for more than one, otherwise return the parser's parseAll() result">
  <img class="diagram-dark" src="/diagrams/parse-flow-dark.svg" alt="Flow of BankMailsParser: filter parsers by sender domain, ask each supports(), throw UnsupportedBankException for none and AmbiguousBankException for more than one, otherwise return the parser's parseAll() result">
</div>

## Matching payments to orders

The recipe pairs a transaction with an order only when **both** the variable symbol and the amount match, and treats a CardPay result other than `OK` as unpaid. The ČSOB payment above has the right symbol but the wrong amount, so it is reported instead of booked. That is deliberate:

- The variable symbol can come from a message the payer wrote. Any payer can type your order's symbol.
- The From header is not authenticated by this library. Check the mail's DKIM/SPF result (your mail server adds it as `Authentication-Results`) before you trust a sender, and verify CardPay signatures. See [Security](/security).
- `parseAll()`, not `tryParse()`, in a mailbox loop: a ČSOB mail with two transactions makes `parse()` throw and `tryParse()` return `null`, and you would silently skip two payments.
- Keep a mail that throws `InvalidEmailFormatException` for a human. It is a bank mail the parser could not read completely, and the library does not guess.

Reading real mail: the `readMail()` function above only handles plain-text messages. For MIME mails (base64 or quoted-printable bodies, HTML parts) use a mail library such as `php-mime-mail-parser` or `zbateson/mail-mime-parser`, or [tomaj/imap-mail-downloader](https://github.com/tomaj/imap-mail-downloader) for IMAP, and pass the decoded `text/plain` part.
