# Examples

Every recipe on these pages is a small Composer project in [`examples/`](https://github.com/tomaj/bank-mails-parser/tree/master/examples) of the repository. It parses anonymised bank mails that sit next to the code and prints the result. The pages include the code and the mails straight from those files, and the [Examples workflow](https://github.com/tomaj/bank-mails-parser/blob/master/.github/workflows/examples.yml) runs every recipe against each pull request and compares its output with the `expected-output.txt` shown here. No network, no IMAP server and no bank account are needed.

| Recipe | What it shows |
|--------|---------------|
| [Tatra banka b-mail](./tatra-banka) | Incoming and outgoing payment notifications, a structured creditor reference, `Money` in minor units |
| [CardPay and ComfortPay](./cardpay) | The `KEY=value` result of a card payment: `res`, `tres`, `cid`, `cc`, `sign`, UTC timestamp |
| [VÚB](./vub) | A VÚB notification: payer account vs. your own account, `VS`, `ŠS`, `KS` |
| [ČSOB SK and CZ](./csob) | ČSOB Slovensko and ČSOB Česká republika, a mail with two transactions and `parseAll()` |
| [A payments mailbox](./mailbox) | Bank detection by sender and body for a folder of `.eml` files, matching payments to orders, skipping foreign and forged senders |
| [Encrypted card statement](./encrypted-statement) | Decrypting a PGP statement with a private key and reading every row |
| [Error handling](./error-handling) | `parse()`, `tryParse()` and `parseAll()` side by side on mails that cannot be read, and `Money` refusing ambiguous amounts |
| [Custom parser](./custom-parser) | A parser for a bank the library does not know, registered next to the built-in ones |

## Running a recipe

You need PHP 8.4 or newer with `mbstring` and Composer.

```bash
git clone https://github.com/tomaj/bank-mails-parser.git
cd bank-mails-parser/examples/tatra-banka
composer install
php run.php     # prints the parsed mails
./e2e.sh        # runs run.php and diffs the output with expected-output.txt
```

`composer install` takes `tomaj/bank-mails-parser` from Packagist. To run a recipe against your local checkout instead, which is what CI does, run `examples/ci/recipe.sh tatra-banka` from the repository root (or `examples/ci/recipe.sh` for all of them). It copies the recipe to a temporary directory and points Composer at the checkout with a path repository.

## The mails

The mails are copies of fixtures from the library's own test suite (`tests/fixtures/`), where every parser is checked against an expected result. They keep the bank's layout, spacing and diacritics, which is what the parsers depend on. When you add a bank or a new mail shape, a real (anonymised) mail is the most useful thing you can contribute; see [Contributing](/contributing).
