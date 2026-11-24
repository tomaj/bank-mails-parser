# Testing your integration

Your code sits between a mailbox and your orders. Test both halves: that the mails your banks really send are parsed the way you expect, and that your booking logic does the right thing with a `MailContent`, including the cases where it must not book anything.

## 1. Keep real mails as fixtures

Save a few real notifications from each bank you use, as the decoded plain-text body (what you pass to `parse()`). Replace names, account numbers and messages with fake values, but keep the layout: line breaks, spacing, labels and diacritics are what the parsers match. Then parse them in a test:

```php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;

final class BankMailFixturesTest extends TestCase
{
    #[Test]
    public function tatraBankaNotification(): void
    {
        $body = (string) file_get_contents(__DIR__ . '/fixtures/tatra-incoming.txt');

        $mail = new BankMailsParser()->parse($body, 'b-mail@tatrabanka.sk');

        self::assertSame(1231, $mail->money?->amount);
        self::assertSame('EUR', $mail->money?->currency->value);
        self::assertSame('1234056789', $mail->vs);
        self::assertSame('2015-01-16 12:51', $mail->transactionDate?->format('Y-m-d H:i'));
    }
}
```

Compare `money->amount` (an integer) or `money->toDecimalString()`, never a float. When a bank changes its template, this is the test that tells you, before payments stop being booked.

The library's own fixtures are a good starting point: [`tests/fixtures/`](https://github.com/tomaj/bank-mails-parser/tree/master/tests/fixtures) has close to a hundred mails, each with an `.expected.json` of every parsed field.

## 2. Test your booking logic with MailContent

`MailContent` is a plain value object, so your own code can be tested without any mail:

```php
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\ValueObject\Money;

$payment = new MailContent(
    money: Money::fromDecimalString('49.90', 'EUR'),
    transactionDate: new DateTimeImmutable('2026-11-24 10:15', new DateTimeZone('Europe/Bratislava')),
    vs: '2026001234',
);

echo $payment->money?->amount, ' ', $payment->money?->currency->value, ' ', $payment->vs, "\n";
```

<!-- snippet: output -->
```text
4990 EUR 2026001234
```

Every argument is optional and named, strings are trimmed and an empty string becomes `null`, the same as what a parser returns.

## 3. Test what must not be booked

Most of the risk is in the mails that should not move money. Cover at least:

| Case | Expected |
|------|----------|
| A bank mail from a sender outside the bank's domain | `UnsupportedBankException`, nothing booked |
| A newsletter or a delivery notice | `UnsupportedBankException` |
| The right variable symbol with a different amount | your code refuses to mark the order paid |
| A CardPay result with `RES=FAIL` | not paid |
| A CardPay result with a wrong `HMAC` | your signature check rejects it |
| A ČSOB mail with two transactions | two payments, via `parseAll()` |
| The same mail processed twice | booked once |
| A statement with a wrong passphrase | `ConfigurationException` reaches your monitoring, it is not swallowed |

The [mailbox example](/examples/mailbox) and the [error handling example](/examples/error-handling) show most of these with real mails and their output.

## 4. Run the examples against your environment

Every recipe in [Examples](/examples/) is a Composer project with an `e2e.sh` that compares its output with `expected-output.txt`. Running one on your production PHP version is a quick check that the extensions and the PGP dependency work there:

```bash
cd examples/encrypted-statement
composer install
./e2e.sh
```

## How the library itself is tested

- Every fixture in `tests/fixtures/` is parsed and compared with its `.expected.json` snapshot.
- Separate tests cover forged payer fields, balance chains, separators, dates around DST changes and invalid UTF-8.
- CI requires 97 % statement coverage and a 95 % mutation score (Infection), on PHP 8.4, 8.5 and 8.6.
- Every PHP block in the README and in these docs runs in `tests/DocsSnippetsTest.php`, and every example runs in the Examples workflow, so the code on this site works with the current release.
