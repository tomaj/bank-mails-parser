# Upgrade Guide to 4.0.0

This guide helps you upgrade from version 3.x to 4.0.0.

## Breaking Changes

### 1. PHP Version Requirement

**OLD**: PHP 7.4+
```json
{
  "require": {
    "php": ">= 7.4"
  }
}
```

**NEW**: PHP 8.2+
```json
{
  "require": {
    "php": ">= 8.2"
  }
}
```

**Action Required**: Update your environment to PHP 8.2 or higher.

### 2. Directory Structure Changes

**OLD**: Classes in `src/Parsers/` namespace
```php
use Tomaj\BankMailsParser\Parsers\TatraBanka\TatraBankaMailParser;
```

**NEW**: Classes in `src/Parser/` namespace
```php
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
```

**Action Required**: Update your import statements to use `Parser` instead of `Parsers`.

### 3. Test Framework Changes (For Contributors/Developers)

**OLD**: PHPUnit 9.x with @test annotations
```php
/**
 * @test
 */
public function testSomething()
{
    // test code
}
```

**NEW**: PHPUnit 11.x with PHP 8 attributes
```php
#[Test]
public function something()
{
    // test code
}
```

**Action Required**: 
- If extending tests: Use `#[Test]` attributes instead of `@test` docblocks
- Remove "test" prefixes from method names
- Add `#[CoversClass(YourClass::class)]` to test classes

### 4. Development Dependencies

**OLD**: PHPUnit 9.x
```json
{
  "require-dev": {
    "phpunit/phpunit": "^9"
  }
}
```

**NEW**: PHPUnit 11.x
```json
{
  "require-dev": {
    "phpunit/phpunit": "^11"
  }
}
```

**Action Required**: Update development dependencies if you're contributing to the project.

## New Features

### VUB Bank Support
```php
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

$parser = new VubMailParser();
$result = $parser->parse($vubEmail);
```

### Enhanced Test Coverage
- All parsers now handle edge cases gracefully
- Empty content and invalid input scenarios tested
- Comprehensive error handling validation

### Improved Documentation
- Complete API documentation for all methods
- Professional bank support matrix
- Security considerations and best practices

## Migration Steps

1. **Update PHP version** to 8.2 or higher
2. **Update composer dependencies**:
   ```bash
   composer update
   ```
3. **Update import statements** to use `Parser` instead of `Parsers`
4. **Test your implementation** with the new version
5. **Update any custom tests** to use PHP 8 attributes (if applicable)

## Compatibility

- **Backward compatible**: All existing parser APIs remain unchanged
- **Runtime compatible**: No behavior changes in parsing logic
- **New parsers**: Additional VUB support without affecting existing code

## Support

If you encounter issues during upgrade:
1. Check that your PHP version is 8.2+
2. Verify all imports use the new `Parser` namespace
3. Run your existing tests to ensure functionality
4. Report issues at: https://github.com/tomaj/bank-mails-parser/issues