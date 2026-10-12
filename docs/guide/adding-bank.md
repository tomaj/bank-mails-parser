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

use DateTimeImmutable;
use DateTimeZone;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

final readonly class YourBankMailParser implements ParserInterface
{
    use ParsesBankMail;

    #[\Override]
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return str_contains($content, 'Transaction amount:');
    }

    public function parse(string $content): MailContent
    {
        $pattern = '/Transaction amount: ([\d.]+) ([A-Z]{3})/';
        if (preg_match($pattern, $content, $result) !== 1) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Amount not found');
        }

        return new MailContent(
            money: $this->money($result[1], $result[2], '.'),
            accountNumber: $this->extractAccountNumber($content),
            vs: $this->extractVariableSymbol($content),
            transactionDate: $this->extractTransactionDate($content),
        );
    }

    private function extractAccountNumber(string $content): ?string
    {
        $pattern = '/Account: ([A-Z0-9]+)/';
        $res = preg_match($pattern, $content, $result);
        
        return $res === 1 ? $result[1] : null;
    }
    
    private function extractVariableSymbol(string $content): ?string
    {
        $pattern = '/Reference: (\d+)/';
        $res = preg_match($pattern, $content, $result);
        
        return $res === 1 ? $result[1] : null;
    }
    
    private function extractTransactionDate(string $content): ?DateTimeImmutable
    {
        $pattern = '/Date: ([\d.]+)/';
        if (preg_match($pattern, $content, $result) !== 1) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!j.n.Y', $result[1], new DateTimeZone('Europe/Bratislava'));

        return $date instanceof DateTimeImmutable ? $date : null;
    }
}
```

## Step 2: Implement ParserInterface

```php
public function supports(string $content, ?string $sender = null, ?string $subject = null): bool;
public function parseAll(string $content): array;
public function bankDomains(): array;
```

`parseAll()` returns every transaction. `ParsesBankMail` supplies `parseAll()`, `tryParse()`, and `money()`. Those three, plus `bankDomains()`, are the supported surface. `BankDate` and the other trait methods are `@internal`. `tryParse()` returns `null` for a bad email and rethrows `ConfigurationException`. It does not catch `TypeError`. Implement `bankDomains()` with the bank's hosts. An empty list means the parser is skipped whenever the caller passes a sender, so a custom parser with `[]` is invisible in that case. Pass the sender from the application. Register the parser on `BankMailsParser` when it should participate in detection. See [Bank Detection](/guide/bank-detection).

## Step 3: Multi-Transaction Support (Optional)

Override `parseAll()`. Do not drop a transaction that matched and then failed. Collect the failures and throw one `InvalidEmailFormatException` that lists them. Text that is not a transaction can be skipped.

```php
public function parseAll(string $content): array
{
    // Anchor the header at the start of a line. Do not explode() on a phrase the payer can type.
    $parts = preg_split('/^Transaction date:/m', $content);
    if ($parts === false) {
        throw InvalidEmailFormatException::malformedContent(self::class, 'Could not split transactions');
    }

    $parsed = [];
    $errors = [];
    $index = 0;
    foreach ($parts as $part) {
        if (!str_contains($part, 'Transaction amount:')) {
            continue;
        }
        ++$index;
        try {
            $parsed[] = $this->parse('Transaction date:' . $part);
        } catch (InvalidEmailFormatException $exception) {
            $errors[] = 'transaction ' . $index . ': ' . $exception->getMessage();
        }
    }
    if ($errors !== []) {
        throw InvalidEmailFormatException::malformedContent(
            self::class,
            'Could not parse every transaction: ' . implode('; ', $errors),
        );
    }

    return $parsed;
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

        $this->assertSame('150.00', $mailContent->money?->toDecimalString());
        $this->assertSame('EUR', $mailContent->money?->currency->value);
        $this->assertEquals('SK9999999999999999999999', $mailContent->accountNumber);
        $this->assertEquals('1234567890', $mailContent->vs);
    }
    
    #[Test]
    public function returnsNullForInvalidFormat()
    {
        $parser = new YourBankMailParser();

        $this->assertNull($parser->tryParse('This is not a valid bank email'));
    }
}
```

## Step 5: Test Coverage

Ensure your tests cover:
- ✅ Successful parsing with all fields
- ✅ Successful parsing with minimal required fields
- ✅ Invalid email format (`tryParse()` returns `null`, `parse()` throws)
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

Check code style with PHP-CS-Fixer:

```bash
composer cs
```

Fix automatically:

```bash
composer cs-fix
```

## Step 8: Documentation

Add documentation for your parser:

1. Create `docs/guide/yourbank.md` with usage examples and the Slovak page `docs/sk/guide/yourbank.md`
2. Add the English and Slovak sidebar entries in `docs/site-nav.ts`
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

- Throw `InvalidEmailFormatException` from `parse()` when required fields are missing
- Return `null` from `tryParse()` for the same failures
- Implement `supports()` so `BankMailsParser` can select the parser

### Testing

- Use **real email samples** (anonymized)
- Test **edge cases** thoroughly
- Aim for **statement coverage** of at least 97%
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

- Check [API Reference](/api/reference) for `MailContent` properties
- Review existing parsers for patterns and best practices
- Read [Contributing Guide](/contributing)
