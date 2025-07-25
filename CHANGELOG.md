# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- PHP 8.1+ support with modern features
- Constructor property promotion in `MailContent` class
- Strong typing throughout the entire codebase
- `DateTimeInterface` support for transaction dates
- GitHub Actions CI/CD pipeline with comprehensive testing
- PHPStan static analysis with maximum level checks
- Code coverage reports published to GitHub Pages
- Security scanning with Trivy vulnerability scanner
- Method chaining support for all setter methods
- PSR-4 autoloading standard

### Changed
- **BREAKING**: Minimum PHP version requirement increased to 8.1
- **BREAKING**: `getTransactionDate()` now returns `?DateTimeInterface` instead of timestamp
- **BREAKING**: `setTransactionDate()` now accepts `?DateTimeInterface` instead of timestamp
- **BREAKING**: All setter methods now return `self` for method chaining
- Upgraded PHPUnit to version 10 with modern configuration
- Updated all development dependencies to latest versions
- Improved error handling and type safety
- Enhanced code organization and documentation

### Removed
- **BREAKING**: Travis CI integration
- **BREAKING**: CodeClimate integration  
- Reflection usage for better performance and type safety
- PHP 7.x support

### Fixed
- Better DateTime handling with proper timezone support
- Improved email parsing with more robust error handling
- Enhanced test coverage and reliability

## [2.8.0] - 2024-05-13

### Added
- Source Account Number support for better transaction tracking

## [2.7.0] - 2022-08-04

### Added
- Support for parsing variable symbols from invalid/non-standard formats
- Additional email format parsing capabilities

### Fixed
- Variable symbol parsing from receiver message field
- Parsing from unique mandate reference field

## [2.6.0] - 2020-11-19

### Added
- ComfortPay RC parameter processing support

## [2.5.0] - 2020-11-18

### Added
- ComfortPay TXN parameter processing support

### Removed
- Dependency status icon from README

## [2.4.0] - 2020-03-30

### Added
- Support for parsing FAIL email responses
- Enhanced error handling for failed transactions

## [2.3.0] - 2020-03-24

### Added
- Improved FAIL email parsing capabilities
- Better error response handling

## [2.2.0] - 2017-05-16

### Changed
- Made HMAC and CC parameters optional for better compatibility

## [2.1.0] - 2017-03-24

### Added
- Enhanced email parsing capabilities
- Additional parameter support

## [2.0.0] - 2016-11-27

### Changed
- **BREAKING**: Parser now returns MailContent for all emails (including FAIL responses)
- In version 1.x, parser returned MailContent only for successful transactions

### Added
- Support for parsing failed transaction emails
- Enhanced error handling

## [1.1.0] - 2015-08-31

### Added
- AC parameter support for Tatrabanka emails

## [1.0.0] - 2015-02-02

### Added
- Initial release
- Basic Tatrabanka email parsing support
- Support for transaction details extraction
- Variable symbol, specific symbol, and constant symbol parsing
- Amount and currency parsing
- Transaction date parsing

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