# Upgrading from 4.x to 5.0

This guide helps you upgrade from Bank Mails Parser 4.x to 5.0.

## Requirements

### PHP Version

**Breaking Change:** Minimum PHP version increased from 8.2 to **8.4**.

```diff
- "php": ">=8.2"
+ "php": "^8.4"
```

**Why:** Version 5.0 leverages PHP 8.4 features including property hooks and asymmetric visibility for a cleaner, more modern API.

**Migration:** Upgrade your PHP version to 8.4 or 8.5.

## Breaking Changes

### 1. MailContent API Changed from Methods to Properties

**Breaking Change:** All getter and setter methods on `MailContent` have been replaced with public properties.

#### Before (4.x):

```php
$mailContent = $parser->parse($emailBody);

// Reading values
$amount = $mailContent->getAmount();
$currency = $mailContent->getCurrency();
$vs = $mailContent->getVs();
$account = $mailContent->getAccountNumber();

// Parsers setting values
$mailContent->setAmount(100.50);
$mailContent->setCurrency('EUR');
$mailContent->setVs('1234567890');
```

#### After (5.0):

```php
$mailContent = $parser->parse($emailBody);

// Reading values - direct property access
$amount = $mailContent->amount;
$currency = $mailContent->currency;
$vs = $mailContent->vs;  // Empty strings automatically converted to null
$account = $mailContent->accountNumber;

// Note: Properties are public, so external code CAN modify them,
// but parsers should be the only ones setting values
```

**Why:** PHP 8.4's property hooks provide a cleaner API with automatic empty string normalization. Direct property access is more intuitive and performant than method calls.

**Migration:**

1. Replace all `$mailContent->getX()` calls with `$mailContent->x`
2. If you're writing custom parsers, replace `$mailContent->setX($value)` with `$mailContent->x = $value`
3. Empty strings assigned to properties with hooks (vs, ss, ks, etc.) are automatically converted to `null`

### 2. All Properties Are Now Properly Typed

All properties are public with explicit type declarations:

```php
public ?float $amount = null;
public ?string $currency = null;
public int|false|null $transactionDate = null;
```

Properties with empty string normalization use PHP 8.4 property hooks:

```php
public ?string $vs = null {
    set {
        $this->vs = $value === '' ? null : $value;
    }
}
```

These properties (vs, ss, ks, cid, sign, res, ac, cc, tid, txn, rc) automatically convert empty strings to `null` when assigned.

## Complete Property Mapping

| Old Method               | New Property              |
|--------------------------|---------------------------|
| `getAmount()`            | `$amount`                 |
| `getCurrency()`          | `$currency`               |
| `getAccountNumber()`     | `$accountNumber`          |
| `getSourceAccountNumber()` | `$sourceAccountNumber`  |
| `getVs()`                | `$vs`                     |
| `getSs()`                | `$ss`                     |
| `getKs()`                | `$ks`                     |
| `getTransactionDate()`   | `$transactionDate`        |
| `getReceiverMessage()`   | `$receiverMessage`        |
| `getDescription()`       | `$description`            |
| `getCid()`               | `$cid`                    |
| `getSign()`              | `$sign`                   |
| `getRes()`               | `$res`                    |
| `getAc()`                | `$ac`                     |
| `getCc()`                | `$cc`                     |
| `getTid()`               | `$tid`                    |
| `getTxn()`               | `$txn`                    |
| `getRc()`                | `$rc`                     |

## Automated Migration

### Using Regular Expressions

You can use these regex patterns to automatically update your code:

```bash
# Replace getter calls
find . -type f -name "*.php" -exec sed -i 's/->getAmount()/->amount/g' {} \;
find . -type f -name "*.php" -exec sed -i 's/->getCurrency()/->currency/g' {} \;
find . -type f -name "*.php" -exec sed -i 's/->getVs()/->vs/g' {} \;
# ... repeat for all properties
```

### Using IDE Refactoring

Most modern IDEs (PHPStorm, VS Code with PHP extensions) can help:

1. Find usages of old methods (e.g., `getAmount`)
2. Refactor/Replace in project
3. Replace with property access (e.g., `amount`)

## Example Migration

### Full Example Before:

```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent !== null) {
    $transaction = [
        'amount' => $mailContent->getAmount(),
        'currency' => $mailContent->getCurrency(),
        'account' => $mailContent->getAccountNumber(),
        'vs' => $mailContent->getVs(),
        'ks' => $mailContent->getKs(),
        'ss' => $mailContent->getSs(),
        'date' => $mailContent->getTransactionDate(),
        'message' => $mailContent->getReceiverMessage(),
    ];
    
    processTransaction($transaction);
}
```

### Full Example After:

```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent !== null) {
    $transaction = [
        'amount' => $mailContent->amount,
        'currency' => $mailContent->currency,
        'account' => $mailContent->accountNumber,
        'vs' => $mailContent->vs,
        'ks' => $mailContent->ks,
        'ss' => $mailContent->ss,
        'date' => $mailContent->transactionDate,
        'message' => $mailContent->receiverMessage,
    ];
    
    processTransaction($transaction);
}
```

## Custom Parsers

If you've written custom parsers implementing `ParserInterface`, update them:

### Before (4.x):

```php
class CustomBankParser implements ParserInterface
{
    public function parse(string $content): ?MailContent
    {
        $mailContent = new MailContent();
        $mailContent->setAmount(100.50);
        $mailContent->setCurrency('EUR');
        $mailContent->setVs('123456');
        return $mailContent;
    }
}
```

### After (5.0):

```php
final readonly class CustomBankParser implements ParserInterface
{
    #[\Override]
    public function parse(string $content): ?MailContent
    {
        // Extract values first
        $amount = 100.50;
        $currency = 'EUR';
        $vs = '123456';
        
        // Create immutable MailContent via constructor
        return new MailContent(
            amount: $amount,
            currency: $currency,
            vs: $vs,
        );
    }
}
```

**Key changes:**
- Parser should be `final readonly class`
- Use `#[\Override]` attribute
- Build MailContent via constructor with named arguments
- No more property mutation

## Benefits of Upgrading

1. **Cleaner API**: Direct property access is more intuitive than method calls
2. **Better Performance**: Properties are faster than method calls
3. **Automatic Normalization**: PHP 8.4's property hooks automatically convert empty strings to `null`
4. **Type Safety**: Explicit type declarations ensure data consistency
5. **Modern Code**: Leverages latest PHP 8.4 features for better developer experience

## Testing Your Migration

After migrating, run your test suite:

```bash
# Run tests
vendor/bin/phpunit

# Check static analysis
vendor/bin/phpstan analyse

# Check code style
vendor/bin/phpcs
```

## Need Help?

- Check the [API Reference](https://tomaj.github.io/bank-mails-parser/api/reference)
- See [Examples](https://tomaj.github.io/bank-mails-parser/guide/examples)
- Open an [issue on GitHub](https://github.com/tomaj/bank-mails-parser/issues)

## Summary

The main change in 5.0 is the migration from methods to properties for `MailContent`. This is a straightforward find-and-replace operation that results in cleaner, more modern code. The functionality remains the same - only the syntax changes.
