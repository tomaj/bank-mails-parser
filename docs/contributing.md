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

This project adheres to the [Contributor Covenant Code of Conduct](https://github.com/tomaj/bank-mails-parser/blob/master/CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## Getting Started

### Prerequisites

- PHP 8.4 or higher
- Composer
- Git

### Setup Development Environment

```bash
# Clone the repository
git clone https://github.com/tomaj/bank-mails-parser.git
cd bank-mails-parser

# Install dependencies
composer install

# Run tests. phpunit.xml.dist always writes a coverage report, so this
# needs pcov or xdebug. Without a coverage driver PHPUnit exits 1 and
# reports "No tests executed!".
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

- **PHP Version**: Minimum 8.4, use modern features (final readonly classes, typed constants, `#[\Override]`, `mb_trim`)
- **Immutability**: MailContent is readonly - parsers create via constructor, never mutate
- **Code Style**: PER-CS + PHP 8.4 migration rules via PHP-CS-Fixer
- **Type Safety**: Strict types enabled, full type hints
- **Documentation**: PHPDoc for public APIs

### Code Quality Tools

```bash
# Fix code style automatically
composer cs-fix

# Run static analysis
composer phpstan

# Run mutation testing. This must be xdebug: `composer infection` sets
# XDEBUG_MODE=coverage. pcov reports a false lower MSI (test selection
# differs) and is not the number CI checks.
composer infection
```

### Quality Requirements

- ✅ All tests must pass (PHPUnit)
- ✅ Code coverage should increase, not decrease
- ✅ PHPStan level max with strict rules
- ✅ Code style compliance (PHP-CS-Fixer with PER-CS + @PHP84Migration)
- ✅ Mutation Score Indicator (MSI) ≥ 89%, Covered MSI ≥ 90%

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

    $this->assertSame('123.45', $result->money?->toDecimalString());
    $this->assertSame('EUR', $result->money?->currency->value);
}
```

### Coverage Requirements

- **Overall coverage**: Target 97%+ statement coverage
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
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

final readonly class YourBankMailParser implements ParserInterface
{
    use ParsesBankMail;

    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return false;
    }

    public function parse(string $content): MailContent
    {
        throw new \Tomaj\BankMailsParser\Exception\InvalidEmailFormatException('Unrecognized email');
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

Put anonymized mails in `tests/fixtures/<bank>/<descriptive-name>.txt` (`tatrabanka`, `tatrabanka-cardpay`, `tatrabanka-statement`, `csob-cz`, `csob-sk`, `vub`). The name is kebab-case (`zahranicna-platba-sepa.txt`, `utok-fantom-adresa.txt`). Put the full expected output in the sibling `<descriptive-name>.expected.json`. One snapshot test reads every `tests/fixtures/**/*.txt` and fails when the expected file is missing or differs.

```bash
composer fixtures:update
```

That command regenerates every `.expected.json`. Review those diffs in the pull request the same way as production code. A mail belongs in the repository once. Short synthetic snippets of a few lines may stay inline in a unit test. Remove real personal data.

### 5. Releasing

Tags have no `v` prefix (`5.0.0`). Before tagging, rename `## [Unreleased]` to `## [5.0.0] - YYYY-MM-DD` in `CHANGELOG.md` and `docs/changelog.md`, and rename both compare links from `[Unreleased]: …/compare/4.1.0...HEAD` to `[5.0.0]: …/compare/4.1.0...5.0.0`. The release notes section must not contain maintainer instructions.

### 6. Update Documentation

Update `README.md`:
- Add bank to supported banks table
- Add usage example
- Document special features

## Pull Request Process

### Before Submitting

Run all quality checks:

```bash
composer check      # Runs cs, phpstan, test
composer infection  # xdebug via XDEBUG_MODE=coverage; pcov gives a false lower MSI
```

All checks must pass locally.

### PR Checklist

- [ ] Tests added for new functionality
- [ ] All tests pass locally
- [ ] Code style (PHP-CS-Fixer) compliant
- [ ] PHPStan level max passes
- [ ] Documentation updated (README, CHANGELOG, English and Slovak docs)
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

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing! 🎉
