# Adding a New Bank

This guide explains how to add support for a new bank to the library.

## Overview

Adding a new bank involves:
1. Creating a parser class that implements `ParserInterface`
2. Writing regex patterns to extract transaction data
3. Creating comprehensive tests
4. Documenting the parser

## Step 1: Create Parser Class

Create a new parser in `src/Parser/YourBank/YourBankMailParser.php`:

```php
<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\YourBank;

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

class YourBankMailParser implements ParserInterface
{
    public function parse(string $content): ?MailContent
    {
        $mailContent = new MailContent();
        
        // Extract required transaction field
        $pattern = '/Transaction amount: ([\d.]+) ([A-Z]{3})/';
        $res = preg_match($pattern, $content, $result);
        
        if ($res !== 1) {
            return null; // Email format not recognized
        }
        
        $mailContent->setAmount(floatval($result[1]));
        $mailContent->setCurrency($result[2]);
        
        // Extract optional fields
        $this->extractAccountNumber($content, $mailContent);
        $this->extractVariableSymbol($content, $mailContent);
        $this->extractTransactionDate($content, $mailContent);
        
        return $mailContent;
    }
    
    private function extractAccountNumber(string $content, MailContent $mailContent): void
    {
        $pattern = '/Account: ([A-Z0-9]+)/';
        $res = preg_match($pattern, $content, $result);
        
        if ($res === 1) {
            $mailContent->setAccountNumber($result[1]);
        }
    }
    
    private function extractVariableSymbol(string $content, MailContent $mailContent): void
    {
        $pattern = '/Reference: (\d+)/';
        $res = preg_match($pattern, $content, $result);
        
        if ($res === 1) {
            $mailContent->setVs($result[1]);
        }
    }
    
    private function extractTransactionDate(string $content, MailContent $mailContent): void
    {
        $pattern = '/Date: ([\d.]+)/';
        $res = preg_match($pattern, $content, $result);
        
        if ($res === 1) {
            $mailContent->setTransactionDate(strtotime($result[1]));
        }
    }
}
```

## Step 2: Implement ParserInterface

The `ParserInterface` defines one required method:

```php
public function parse(string $content): ?MailContent
```

**Return values:**
- `MailContent` object if parsing succeeds
- `null` if email format is not recognized or required fields are missing

## Step 3: Multi-Transaction Support (Optional)

If the bank sends multiple transactions in one email, implement `parseMulti()`:

```php
public function parseMulti(string $content): array
{
    // Split by transaction separator
    $transactions = array_slice(explode("Transaction Date:", $content), 1);
    
    $mailContents = [];
    foreach ($transactions as $transaction) {
        $mailContent = $this->parse("Transaction Date:" . $transaction);
        if ($mailContent !== null) {
            $mailContents[] = $mailContent;
        }
    }
    
    return $mailContents;
}
```

## Step 4: Write Tests

Create `tests/Parser/YourBank/YourBankMailParserTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\YourBank;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\YourBank\YourBankMailParser;

#[CoversClass(YourBankMailParser::class)]
class YourBankMailParserTest extends TestCase
{
    #[Test]
    public function parsesBasicTransaction()
    {
        $email = 'Dear Customer,
        
Transaction amount: 150.00 EUR
Account: SK9999999999999999999999
Reference: 1234567890
Date: 15.01.2024

Best regards,
Your Bank';
        
        $parser = new YourBankMailParser();
        $mailContent = $parser->parse($email);
        
        $this->assertNotNull($mailContent);
        $this->assertEquals(150.00, $mailContent->getAmount());
        $this->assertEquals('EUR', $mailContent->getCurrency());
        $this->assertEquals('SK9999999999999999999999', $mailContent->getAccountNumber());
        $this->assertEquals('1234567890', $mailContent->getVs());
    }
    
    #[Test]
    public function returnsNullForInvalidFormat()
    {
        $email = 'This is not a valid bank email';
        
        $parser = new YourBankMailParser();
        $mailContent = $parser->parse($email);
        
        $this->assertNull($mailContent);
    }
}
```

## Step 5: Test Coverage

Ensure your tests cover:
- ✅ Successful parsing with all fields
- ✅ Successful parsing with minimal required fields
- ✅ Invalid email format (returns `null`)
- ✅ Edge cases (special characters, whitespace, etc.)
- ✅ Multi-transaction parsing (if supported)
- ✅ Date format variations
- ✅ Amount format variations

Run tests:

```bash
composer test
```

## Step 6: Static Analysis

Ensure PHPStan passes:

```bash
composer phpstan
```

Fix any type errors or add proper PHPStan annotations.

## Step 7: Code Style

Check PSR-12 compliance:

```bash
composer cs
```

Fix automatically:

```bash
composer cs-fix
```

## Step 8: Documentation

Add documentation for your parser:

1. Create `docs/guide/yourbank.md` with usage examples
2. Add entry to sidebar in `docs/.vitepress/config.mts`
3. Update main README.md if needed

## Best Practices

### Regex Patterns

- Use **named capturing groups** when helpful
- Make patterns **as specific as possible**
- Test with actual bank emails
- Handle **whitespace variations**

### Type Safety

- Use **strict types** (`declare(strict_types=1)`)
- Use **strict comparisons** (`===` instead of `==`)
- Check `preg_match()` return value with `=== 1`
- Handle `null` values properly

### Error Handling

- Return `null` for unrecognized formats
- Don't throw exceptions for parsing failures
- Validate required fields before returning `MailContent`

### Testing

- Use **real email samples** (anonymized)
- Test **edge cases** thoroughly
- Aim for **high coverage** (≥95%)
- Include **mutation testing** consideration

## Example: Complete Parser

See existing parsers for complete examples:

- **Simple format**: [VubMailParser](/guide/vub)
- **Multi-transaction**: [CsobMailParser](/guide/csob-cz)
- **Complex format**: [TatraBankaMailParser](/guide/tatrabanka)

## Submitting Your Parser

1. Fork the repository
2. Create a feature branch
3. Implement parser and tests
4. Ensure all quality checks pass
5. Submit a pull request

See [Contributing Guide](/contributing) for details.

## Next Steps

- Check [API Reference](/api/reference) for `MailContent` methods
- Review existing parsers for patterns and best practices
- Read [Contributing Guide](/contributing)
