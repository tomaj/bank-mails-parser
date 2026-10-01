# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **PHP 8.5 support** - Added PHP 8.5 to CI test matrix
- **Mutation testing** - Integrated Infection for mutation testing (MSI: 79%, Covered MSI: 85%)
- **Enhanced CI/CD pipeline**:
  - Separate jobs for tests, code style, static analysis, security audit, and mutation testing
  - `--prefer-lowest` dependency testing on PHP 8.2
  - Composer security audit in dedicated job
  - Improved job naming and structure
- **Comprehensive documentation**:
  - `CONTRIBUTING.md` - Complete contribution guidelines with bank parser guide
  - `SECURITY.md` - Security policy and vulnerability reporting
  - `CODE_OF_CONDUCT.md` - Contributor Covenant 2.1
  - `CODEOWNERS` - Automatic reviewer assignment
  - Issue templates (bug report, feature request, new bank)
  - Pull request template with comprehensive checklist
  - `DEPENDABOT.yml` - Monthly dependency updates for Composer and GitHub Actions
- **Composer enhancements**:
  - Added `support` section with issues, source, and docs links
  - Added comprehensive `keywords` for better discoverability
  - Added `scripts-descriptions` for all commands
  - Added `archive.exclude` for cleaner package distribution
  - New scripts: `cs`, `cs-fix`, `phpstan`, `infection`, `check`
- **Type safety improvements**:
  - Full property type declarations in `MailContent`
  - Strict return type `void` for all setter methods
  - Proper handling of union types (`int|false|null` for transaction dates)
- **Enhanced test coverage**:
  - Added tests for whitespace trimming in account numbers
  - Added tests for multi-transaction parsing edge cases
  - Achieved 90.37% code coverage (291/322 lines)
  - 58 tests with 422 assertions
- **Static analysis**:
  - Upgraded PHPStan to level `max` with strict rules
  - Added `phpstan/phpstan-strict-rules` for enhanced type checking
  - All parsers now comply with strict boolean checks

### Changed
- **BREAKING (minor)**: `MailContent` properties now have explicit types (may affect reflection-based code)
- **BREAKING (minor)**: `setTransactionDate()` now requires `int|false` instead of mixed types
- **Code quality**:
  - Replaced loose comparisons (`==`) with strict comparisons (`===`) throughout
  - Replaced `empty()` checks with strict comparisons
  - Fixed `preg_match()` result checking to use `=== 1` for strict rules compliance
  - Added proper null handling in string replacement operations
  - Created `phpcs.xml.dist` to configure PSR-12 checks and handle test data line lengths
- **CI improvements**:
  - Updated all GitHub Actions to v4
  - Renamed job from `test` to `tests` with better matrix naming
  - Separated concerns into dedicated jobs
  - Enhanced PHP version matrix (8.2, 8.3, 8.4, 8.5)
- **Documentation improvements**:
  - Expanded `composer.json` description
  - Added author email for better contact
  - Updated `.gitattributes` with correct paths and additional exclusions

### Fixed
- `.gitattributes` typo: `phpunit.xmli.dist` → `phpunit.xml.dist`
- PHPStan compatibility with dynamic method calls in `TatraBankaSimpleMailParser`
- Proper type handling for OpenPGP library's dynamic types
- Code style compliance (PSR-12) across all files

## [4.1.0] - 2026-05-19

### Added
- VUB test data files from the original repository

### Changed
- **TatraBanka parser**: explicit date formats for predictable parsing of new Tatrabanka email date variants (`strtotime` preserved as fallback) ([#20](https://github.com/tomaj/bank-mails-parser/pull/20))

## [4.0.0] - 2025-08-29

### Added
- **VubMailParser** for VUB bank emails
- VUB parser test coverage and documentation
- Comprehensive README documentation improvements
- Professional bank support matrix with country flags
- Quick Start guide and error handling documentation
- Complete MailContent API documentation with all getters
- Security considerations and contributing guidelines
- **Enhanced test coverage** - Added 15+ new edge case and error handling tests
- **MailContentTest** - Dedicated test class for MailContent edge cases
- **Parser robustness tests** - Invalid input, empty content, malformed data handling
- **PHP 8 attributes support** - Modern #[Test] and #[CoversClass] attributes
- GitHub Pages coverage reporting with HTML reports
- Automatic coverage badge updates in README
- GitHub Actions CI badge

### Changed
- **BREAKING**: Minimum PHP version increased from 7.4 to 8.2
- **BREAKING**: Test method naming - Removed "test" prefixes, use #[Test] attributes instead
- **Directory structure consistency** - Moved `src/Parsers/` to `src/Parser/` to match namespace
- **Tests directory structure** - Moved `tests/Parsers/` to `tests/Parser/` for consistency
- **Test modernization** - Replaced @test annotations with PHP 8 #[Test] attributes
- Updated all GitHub Actions to v4 to fix deprecation warnings
- Improved PHPUnit configuration with proper coverage filters
- Updated PHPUnit from 9.6.25 to 11.5.35 (latest version)
- CI now tests only PHP 8.2, 8.3, and 8.4
- **Removed unused Makefile** - GitHub Actions uses direct vendor/bin commands

### Removed
- Code Climate badges (service discontinued)
- **BREAKING**: Dropped support for PHP 7.4, 8.0, and 8.1
- **BREAKING**: @test docblock annotations (replaced with #[Test] attributes)

## [3.0.0] - 2025-08-28

### Added
- **CsobMailParser** for Czech ČSOB bank emails with multi-transaction support
- **SkCsobMailParser** for Slovak ČSOB bank emails  
- **TatraBankaStatementMailParser** for encrypted PGP email statements
- **TatraBankaMailDecryptor** helper class for PGP decryption
- Comprehensive test coverage for all new parsers
- `singpolyma/openpgp-php` dependency for PGP functionality
- Updated README with usage examples for all parsers
- GitHub Actions CI/CD workflow with linter (PHP_CodeSniffer), static analysis (PHPStan), and tests (PHPUnit)
- PHPStan static analysis tool configuration

### Changed
- Expanded library support from TatraBanka only to multiple banks
- Updated project description to reflect multi-bank support
- **BREAKING**: Minimum PHP version requirement increased from 7.2 to 7.4

### Removed
- Travis CI configuration (replaced with GitHub Actions)

## [2.8.0] - 2021-12-01

### Added
- Source Account Number support ([#12](https://github.com/tomaj/bank-mails-parser/pull/12))

## [2.7.0] - 2021-09-15

### Added
- Support for parsing variable symbol from additional invalid formats ([#11](https://github.com/tomaj/bank-mails-parser/pull/11))
- Enhanced variable symbol detection in receiver message and payment purpose

### Fixed
- Test names and removed pattern numbering for better readability
- Variable symbol parsing from receiver message format

## [2.6.0] - 2020-05-20

### Added
- Trim functionality for parsed variables to remove whitespace
- Improved variable symbol parsing reliability

### Fixed
- Test for variable symbol in receiver message
- Removed unused variables

## [2.5.0] - 2019-08-15

### Added
- **RC parameter processing** for ComfortPay emails ([#9](https://github.com/mikoczy/mikoczy/comfortpay_rc))

### Changed
- Updated minimal PHP version requirements
- Improved Travis CI build configuration

## [2.4.0] - 2019-06-10

### Added
- **TXN parameter processing** for ComfortPay emails ([#8](https://github.com/mikoczy/comfortpay_txn))
- TXN parameter documentation in README

## [2.3.0] - 2019-03-20

### Fixed
- **CardPay HMAC regexp** improvements for optional fields ([#7](https://github.com/rootpd/fail-mails-2))
- Enhanced support for failed payment emails ([#6](https://github.com/rootpd/fail-mails))

### Changed
- Removed PHP 5.4 and 5.5 from Travis CI
- Updated build configuration

## [2.2.0] - 2018-12-01

### Added
- **Description field support** ([#5](https://github.com/danieljaniga/master))
- Enhanced email parsing capabilities

### Fixed
- Code style improvements
- Updated README documentation

## [2.1.0] - 2018-08-15

### Added
- **CC parameter** support within HMAC confirmation emails ([#4](https://github.com/rootpd/hmac-cc-param-optional))
- Made CC parameter optional in HMAC emails

### Fixed
- Variable symbol parsing from receiver message ([#2](https://github.com/davidkoberan/master))
- Enhanced VS detection in whole message content

## [2.0.0] - 2018-05-01

### Added
- **Strict types** support (`declare(strict_types=1)`)
- **PHP 7.1+** requirement
- Enhanced **TatraBanka HMAC** confirmation email support
- **AC parameter** for TatraBanka emails
- **Transaction date** support in simple mail parser
- **ComfortPay emails** parsing support

### Changed
- **BREAKING:** Moved all TatraBanka code to `TatraBanka` namespace
- **BREAKING:** Updated `ParserInterface` - no longer returns `false`, only `?MailContent`
- **BREAKING:** Minimum PHP version increased to 7.1
- Updated PHPUnit to version 9
- Modernized codebase with strict types

### Removed
- PHP 5.x and 7.0 support
- Legacy parsing methods

## [1.1.0] - 2017-03-15

### Added
- **TatraBanka SimpleMailParser** for ComfortPay emails
- Additional getter methods (CID, Sign, RES)
- Support for recurring payments
- Enhanced email format detection

### Improved
- Code quality and PSR compliance
- Test coverage
- Documentation

## [1.0.0] - 2016-08-20

### Added
- Initial release
- **TatraBankaMailParser** for basic TatraBanka email parsing
- **MailContent** class for parsed data
- **ParserInterface** for extensibility
- Support for Slovak bank email parsing
- Basic test coverage
- Travis CI integration
- Code Climate integration

### Features
- Variable Symbol (VS) parsing
- Specific Symbol (SS) parsing  
- Constant Symbol (KS) parsing
- Amount and currency parsing
- Transaction date parsing
- Account number parsing
- Receiver message parsing

[Unreleased]: https://github.com/tomaj/bank-mails-parser/compare/4.1.0...HEAD
[4.1.0]: https://github.com/tomaj/bank-mails-parser/compare/4.0.0...4.1.0
[4.0.0]: https://github.com/tomaj/bank-mails-parser/compare/3.0.0...4.0.0
[3.0.0]: https://github.com/tomaj/bank-mails-parser/compare/2.8.0...3.0.0
[2.8.0]: https://github.com/tomaj/bank-mails-parser/compare/2.7.0...2.8.0
[2.7.0]: https://github.com/tomaj/bank-mails-parser/compare/2.6.0...2.7.0
[2.6.0]: https://github.com/tomaj/bank-mails-parser/compare/2.5.0...2.6.0
[2.5.0]: https://github.com/tomaj/bank-mails-parser/compare/2.4.0...2.5.0
[2.4.0]: https://github.com/tomaj/bank-mails-parser/compare/2.3.0...2.4.0
[2.3.0]: https://github.com/tomaj/bank-mails-parser/compare/2.2.0...2.3.0
[2.2.0]: https://github.com/tomaj/bank-mails-parser/compare/2.1.0...2.2.0
[2.1.0]: https://github.com/tomaj/bank-mails-parser/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/tomaj/bank-mails-parser/compare/1.1.0...2.0.0
[1.1.0]: https://github.com/tomaj/bank-mails-parser/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/tomaj/bank-mails-parser/releases/tag/1.0.0