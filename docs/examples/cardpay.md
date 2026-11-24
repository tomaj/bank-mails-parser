# CardPay and ComfortPay

When a customer pays by card through Tatra banka's CardPay (or ComfortPay, the stored-card variant), the bank can mail the result to the merchant as one line of `KEY=value` pairs. `TatraBankaSimpleMailParser` reads it. This recipe parses a successful CardPay payment, a successful ComfortPay payment, and a failed one.

Source: [`examples/cardpay`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/cardpay)

## The mails

<<< @/../examples/cardpay/mails/comfortpay-ok.txt{text}

::: details The other two mails
<<< @/../examples/cardpay/mails/cardpay-ok.txt{text}

<<< @/../examples/cardpay/mails/comfortpay-fail.txt{text}
:::

## The code

<<< @/../examples/cardpay/run.php

## The output

<div class="example-output">

<<< @/../examples/cardpay/expected-output.txt{text}

</div>

## The fields

| Key | Property | Meaning |
|-----|----------|---------|
| `AMT` + `CURR` | `money` | Amount with `.` as the decimal mark. `CURR` is the ISO 4217 number (`978` is EUR); a missing `CURR` is EUR, an unknown one leaves `money` null and keeps the rest |
| `VS` | `vs` | Variable symbol you sent to the gateway |
| `RES` / `TRES` | `res`, `tres` | Result, `OK` or `FAIL`. When `TRES` is present, `res` is the `TRES` value and `tres` stores it too |
| `AC` | `ac` | Authorisation code, only on a successful payment |
| `CID` | `cid` | ComfortPay customer (stored card) id |
| `CC` | `cc` | Masked card number |
| `TID` | `tid` | Transaction id |
| `TXN` | `txn` | CardPay transaction type, such as `PA` |
| `RC` | `rc` | Return code |
| `HMAC` / `SIGN` | `sign` | The signature as sent. When both are present, `sign` is the HMAC |
| `TIMESTAMP` | `transactionDate` | 14 digits `dmYHis` in UTC; 10 digits are a Unix timestamp. Missing means `null` |

Only the first contiguous block of `KEY=value` lines is read, words that are not `KEY=value` on that line are skipped (so `Vysledok: VS=… RES=OK …` works), quoted lines starting with `>` are ignored, and a key repeated with a different value throws `InvalidEmailFormatException` (see [Error handling](./error-handling)).

::: warning The signature is not verified
The parser copies `HMAC`/`SIGN` into `sign`; it does not check it, because it does not have your gateway key. Verify it with your gateway key before you mark an order paid, the same way you verify the CardPay return URL (for example with [omnipay-tatra](https://github.com/tomaj/omnipay-tatra)), and check the mail's DKIM. See [Security](/security).
:::
