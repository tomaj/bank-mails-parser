# Error handling

Every parse method has the same contract: `parse()` returns one transaction or throws, `tryParse()` returns `null` instead of throwing a parse error, and `parseAll()` returns every transaction or throws. This recipe runs all three on four mails that cannot be read as one payment, then shows `Money` refusing amounts it would have to guess.

Source: [`examples/error-handling`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/error-handling)

## The mails

| File | Problem |
|------|---------|
| `not-a-bank.txt` | A parcel notice. No parser supports it |
| `two-transactions.txt` | A valid ČSOB CZ mail with two transactions |
| `broken-balance.txt` | The same mail with the second balance changed, so the balances no longer add up |
| `card-result-two-amounts.txt` | A CardPay result that contains `AMT` twice with different values |

::: details broken-balance.txt
<<< @/../examples/error-handling/mails/broken-balance.txt{text}
:::

<<< @/../examples/error-handling/mails/card-result-two-amounts.txt{text}

## The code

<<< @/../examples/error-handling/run.php

## The output

<div class="example-output">

<<< @/../examples/error-handling/expected-output.txt{text}

</div>

## Reading the result

| Situation | `parse()` | `tryParse()` | `parseAll()` |
|-----------|-----------|--------------|--------------|
| No parser supports the mail | `UnsupportedBankException` | `null` | `UnsupportedBankException` |
| Two parsers support it | `AmbiguousBankException` | `null` | `AmbiguousBankException` |
| Several transactions | `MultipleTransactionsException` | `null` | every transaction |
| A bank mail with broken or contradicting fields | `InvalidEmailFormatException` | `null` | `InvalidEmailFormatException` |
| Missing key file or wrong passphrase for a statement | `ConfigurationException` | `ConfigurationException` | `ConfigurationException` |

- All of them implement `Tomaj\BankMailsParser\Exception\BankMailsParserException`, so one `catch` takes everything from the package. The parse errors extend `ParseException`; `ConfigurationException` and `InvalidArgumentException` do not.
- The broken balance chain is the library refusing a mail where a payer could have typed a fake transaction into a message: when the bank's balance lines do not confirm the amounts, nothing from that mail is returned.
- `Money::fromDecimalString()` without a decimal separator rejects `1.234`: it could be 1.234 or 1234. Parsers always pass the bank's separator. `12.345` with `.` as the separator has three fraction digits, more than EUR allows, and is rejected rather than rounded. Both throw `Tomaj\BankMailsParser\Exception\InvalidArgumentException`, which a parser turns into `InvalidEmailFormatException`.

See [Exceptions](/guide/exceptions) for the full hierarchy.
