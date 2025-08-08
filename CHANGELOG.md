# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/tomaj/bank-mails-parser/compare/2.8.0...HEAD
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