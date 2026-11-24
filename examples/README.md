# Examples

Runnable recipes for [tomaj/bank-mails-parser](https://github.com/tomaj/bank-mails-parser). Each directory is a small Composer project that parses the bank mails next to it and prints the result; `e2e.sh` compares that output with `expected-output.txt`. They are explained on the documentation site: https://tomaj.github.io/bank-mails-parser/examples/

| Recipe | What it shows |
|--------|---------------|
| [tatra-banka](tatra-banka) | Tatra banka b-mail notifications |
| [cardpay](cardpay) | CardPay and ComfortPay result mails |
| [vub](vub) | A VÚB payment notification |
| [csob](csob) | ČSOB SK and CZ, several transactions in one mail |
| [mailbox](mailbox) | Bank detection for a folder of `.eml` files, matching payments to orders |
| [encrypted-statement](encrypted-statement) | A PGP-encrypted Tatra banka card statement |
| [error-handling](error-handling) | `parse()`, `tryParse()` and `parseAll()` on mails that cannot be read |
| [custom-parser](custom-parser) | A parser for a bank the library does not know |

```bash
cd tatra-banka
composer install
php run.php
./e2e.sh
```

`examples/ci/recipe.sh <name>` runs a recipe against this checkout instead of Packagist (a Composer path repository); without a name it runs all of them. The Examples workflow does the same for every pull request.
