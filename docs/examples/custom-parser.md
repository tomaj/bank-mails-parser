# Custom parser

A bank the library does not support yet needs a class that implements `ParserInterface`. Register it on the facade and it takes part in detection like the built-in parsers. This recipe adds a parser for the fictional "Example banka" in the application's own namespace.

Source: [`examples/custom-parser`](https://github.com/tomaj/bank-mails-parser/tree/master/examples/custom-parser)

## The mail

<<< @/../examples/custom-parser/mails/example-bank.txt{text}

## The parser

<<< @/../examples/custom-parser/src/ExampleBankMailParser.php

`ParserInterface` asks for three methods:

| Method | Contract |
|--------|----------|
| `bankDomains(): list<string>` | The hosts the bank mails from. With a sender, the facade asks this parser only when the sender's host is one of them or its subdomain. An empty list means the parser is used only when no sender is passed |
| `supports($content, $sender, $subject): bool` | Whether the body is this bank's mail. Look for a line only the bank writes, anchored at the start of a line, and do not throw |
| `parseAll($content): list<MailContent>` | Every transaction. Throw `InvalidEmailFormatException` when the mail is recognised but cannot be read |

The `ParsesBankMail` trait supplies `parseAll()` (one transaction from your `parse()`), `tryParse()`, an empty `bankDomains()`, and `money()`, which turns an invalid amount into `InvalidEmailFormatException` instead of leaking `InvalidArgumentException`. Its other methods are internal.

## The code

<<< @/../examples/custom-parser/run.php

## The output

<div class="example-output">

<<< @/../examples/custom-parser/expected-output.txt{text}

</div>

## Notes

- `registerParser()` appends, `prependParser()` inserts in front. Order does not decide anything when only one parser matches; it is the order of the list `getParsers()` returns.
- `new BankMailsParser([$parser])` uses only the parsers you pass; `new BankMailsParser()` (or `null`) registers the built-in ones.
- If your `supports()` also accepts another bank's mail, the facade throws `AmbiguousBankException` for it. Make the check specific, and pass the sender so the domain filter runs first.
- Worth sharing? [Adding a new bank](/guide/adding-bank) describes how to contribute a parser to the library, with fixtures and tests.
