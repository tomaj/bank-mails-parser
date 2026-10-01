# Contributing to Bank Mails Parser

Thank you for your interest in contributing! This document provides guidelines for contributing to the project.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing Requirements](#testing-requirements)
- [Adding a New Bank Parser](#adding-a-new-bank-parser)
- [Pull Request Process](#pull-request-process)

## Code of Conduct

This project adheres to the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git

### Setup Development Environment

```bash
# Clone the repository
git clone https://github.com/tomaj/bank-mails-parser.git
cd bank-mails-parser

# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer cs

# Run static analysis
composer phpstan

# Run all checks
composer check
```

## Development Workflow

1. **Fork the repository** and create a feature branch
2. **Make your changes** following coding standards
3. **Add tests** for new functionality
4. **Run all quality checks** locally before pushing
5. **Submit a pull request** with clear description

### Branch Naming

Use descriptive branch names:
- `feature/add-bank-xyz` - New features
- `bugfix/fix-parser-issue` - Bug fixes
- `docs/update-readme` - Documentation updates

## Coding Standards

### PHP Standards

- **PHP Version**: Minimum 8.4, use modern features (property hooks, readonly classes, typed constants, array_find/any/all, mb_trim)
- **Immutability**: MailContent is readonly - parsers create via constructor, never mutate
- **Code Style**: PER-CS + PHP 8.4 migration rules via PHP-CS-Fixer
- **Code Style**: PSR-12
- **Type Safety**: Strict types enabled, full type hints
- **Documentation**: PHPDoc for public APIs

### Code Quality Tools

```bash
# Fix code style automatically
composer cs-fix

# Run static analysis
composer phpstan

# Run mutation testing
composer infection
```

### Quality Requirements

- ✅ All tests must pass (PHPUnit)
- ✅ Code coverage should increase, not decrease
- ✅ PHPStan level max with strict rules
- ✅ PSR-12 code style compliance
- ✅ Mutation Score Indicator (MSI) ≥ 90%

## Testing Requirements

### Unit Tests

Every parser must have comprehensive tests covering:

- **Valid email formats** - Happy path scenarios
- **Edge cases** - Boundary conditions, special characters
- **Error handling** - Malformed emails, missing fields
- **Multiple encodings** - UTF-8, special characters
- **Multi-transaction emails** - If applicable

### Test Example

```php
#[Test]
public function parsesValidEmail(): void
{
    $parser = new YourBankMailParser();
    $content = 'email content here';
    
    $result = $parser->parse($content);
    
    $this->assertNotNull($result);
    $this->assertEquals(123.45, $result->getAmount());
    $this->assertEquals('EUR', $result->getCurrency());
}
```

### Coverage Requirements

- **Overall coverage**: Target 95%+
- **New parsers**: 100% coverage required
- **Edge cases**: Must include tests for failure paths

## Adding a New Bank Parser

### 1. Study Existing Parsers

Look at implementations in `src/Parser/`:
- `TatraBanka/` - Multiple parser types
- `Csob/` - Multi-transaction support
- `Vub/` - Simple parser example

### 2. Create Parser Class

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
        // Return null if format doesn't match
        // Return MailContent with parsed data
    }
}
```

### 3. Add Comprehensive Tests

Create `tests/Parser/YourBank/YourBankMailParserTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Parser\YourBank;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\YourBank\YourBankMailParser;

class YourBankMailParserTest extends TestCase
{
    #[Test]
    public function parsesValidEmail(): void
    {
        // Add your tests here
    }
}
```

### 4. Add Test Data

Create `tests/Parser/YourBank/data/` with:
- Anonymized sample emails (`.txt` or `.eml`)
- `README.md` explaining email formats
- **IMPORTANT**: Remove all real personal data!

### 5. Update Documentation

Update `README.md`:
- Add bank to supported banks table
- Add usage example
- Document special features

## Pull Request Process

### Before Submitting

Run all quality checks:

```bash
composer check      # Runs cs, phpstan, test
composer infection  # Runs mutation testing
```

All checks must pass locally.

### PR Checklist

- [ ] Tests added for new functionality
- [ ] All tests pass locally
- [ ] Code style (PSR-12) compliant
- [ ] PHPStan level max passes
- [ ] Documentation updated (README, CHANGELOG)
- [ ] No decrease in code coverage
- [ ] Commit messages are clear and descriptive

### PR Description

Include:

1. **Purpose**: What problem does this solve?
2. **Changes**: What was changed?
3. **Testing**: How was it tested?
4. **Email Format**: For new parsers, describe email structure
5. **Breaking Changes**: If any (increment major version)

### Review Process

- Maintainer will review within 48-72 hours
- Address feedback in separate commits (not force-push)
- Once approved, maintainer will squash and merge

## Commit Messages

Use conventional commits format:

```
feat: add parser for Bank XYZ
fix: handle null amounts in CSOB parser
docs: update README with new bank support
test: add edge case for VUB parser
refactor: improve regex patterns in TatraBanka parser
```

Breaking changes:

```
feat!: change parser return type

BREAKING CHANGE: parsers now return null instead of false
```

## Code Review Guidelines

### For Contributors

- Be responsive to feedback
- Ask questions if unclear
- Keep PRs focused and small

### For Reviewers

- Be constructive and respectful
- Focus on code quality, not style preferences
- Suggest improvements, don't demand perfection

## Questions or Problems?

- **Bug reports**: Open an issue with reproduction steps
- **Feature requests**: Open an issue with use case description
- **Questions**: Open a discussion or email tomasmajer@gmail.com

## License

By contributing, you agree that your contributions will be licensed under the LGPL-2.0-or-later license.

---

Thank you for contributing! 🎉
