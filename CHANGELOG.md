# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Breaking Changes

- Minimum PHP version is 8.4. PHP 8.2 and 8.3 are no longer supported. PHP 8.5 is tested.
- `MailContent` is a `final readonly` class. Getters and setters are removed.
- Amount and currency are `?Money`. `Money::$amount` is an integer in minor units. `toDecimalString()` returns the decimal text. There is no float amount on the public API.
- `transactionDate` is `?DateTimeImmutable`. The `int|false|null` timestamp is gone.
- `parse()` throws `InvalidEmailFormatException` or, on `BankMailsParser`, `UnsupportedBankException` or `AmbiguousBankException`. `tryParse()` returns `null`. There is no `parseOrFail()`.
- `Currency` is an ISO 4217 value object for every active code (list one, 2026-09-17). Numeric codes such as `978` are stored as `EUR`. An unknown CardPay `CURR` keeps the notification and sets `money` to null.
- `accountNumber` on ČSOB SK and VÚB is the payer account (`z účtu` / `Z účtu`), as in 4.x. `ownAccountNumber` is the new property for `na účte` / `Na účet`. `sourceAccountNumber` is the ČSOB CZ or Tatra counterparty.
- A variable symbol follows 4.1.0. Tatra reads `/VS…/SS…/KS…` and keeps more than 10 digits, then a `VS` token, then the last number in `Informacia pre prijemcu` when `Referencia platitela` is absent (4.1.0), then a bare `Referencia platitela` including one that follows a blank line. ČSOB CZ reads `Variabilní symbol` (all zeros are skipped), then `Identifikace` replaces it, then a `VS` or `v.s.` token, then the last number in `Zpráva příjemci`, then `Účel platby`. More than 10 digits on ČSOB CZ is `null`, not a truncated symbol. ČSOB SK reads `/VS`, `/SS`, and `/KS` from `referencia platiteľa` with no length cap, then `VS` or `KS` from the `informácia pre príjemcu` line only, not from the payer name. The payer writes the message, so a matched payment must also be checked against the amount.
- Parser classes and `TatraBankaMailDecryptor` are `final readonly`. `decrypt()` returns `string` and throws; it never returns `null`. Statement `parseMulti()` returns the rows or throws; it does not return `null`.
- Dates for Slovak and Czech bank emails use `Europe/Bratislava`. On a UTC server every such timestamp is +1 h or +2 h versus 4.x. `16.1.2015 24:00` is the next day at 00:00. Tatra also accepts an English month (`16 January 2015 12:51`). The Tatra header must start the line: `Dobry den, 16.1.2015 12:51 bol zostatok…` does not parse. `12,315` EUR is rejected. A two-digit year, `31.2`, and a DST gap stay null. CardPay `TIMESTAMP` of 14 digits is UTC. A missing CardPay timestamp stays `null` (4.x used `time()`). Invalid UTF-8 in a Tatra or CardPay body is scrubbed and parsed.
- `Money::fromDecimalString()` rejects ambiguous separators, non-zero extra fraction digits, and values that do not fit in an integer, including `PHP_INT_MIN`. Trailing zeros beyond the currency scale are accepted (`1234,00` JPY). Space, NBSP, U+2009, U+202F and tab are thousands separators only in groups of three (`1 234,56` is accepted, `1 2 3,45` is not). Overflow is `InvalidArgumentException`, wrapped by parsers as `InvalidEmailFormatException`.
- `tryParse()` returns `null` for invalid input and does not swallow `ConfigurationException`.
- `parseAll()` returns every transaction. ČSOB and Tatra statements throw one exception listing any transaction that matched and could not be parsed. A PGP statement without a decryptor, with a missing or unreadable key, or with a wrong passphrase throws `ConfigurationException`.
- VÚB detection requires the date, both account labels, the amount, `VS`, and `Stav` or `SIGN`, each at the start of a line. EUR is the fallback only when the email does not name a currency. An empty `Na účet:` does not read the next line.
- CardPay reads `KEY=value` from the first contiguous notification block. A word that is not `KEY=value` on that line is skipped, so `Vysledok: VS=… RES=OK …` parses. A later line that mixes prose is not part of the block. Quoted lines that start with `>` are ignored, so a notice that exists only as quoted history is not a payment. A repeated key with a different value throws `InvalidEmailFormatException`. When both `SIGN` and `HMAC` are present, `sign` is the HMAC. When `TRES` is present, `res` is that value, as in 4.x, and `tres` stores it too. A missing `CURR` is EUR. An unknown `CURR` leaves `money` null. `supports()` returns false when a pattern cannot run; it does not throw.
- Payer-written fields are fail-closed only for a real bank line. ČSOB CZ: `Název protistrany`, `Adresa protistrany`, `Adresa plátce`, `Zpráva příjemci`, `Účel platby`, `Identifikace`, `Reference plátce`. ČSOB SK: `názov protiúčtu`, `meno protiúčtu`, `informácia pre príjemcu`, `referencia platiteľa`. Tatra: `Informacia pre prijemcu`, `Popis transakcie`, `popis`, `Referencia platitela`. A blank line, or a line of spaces, tabs or NBSP, ends the field. An arbitrary `Word:` does not. A closing line has to be the bank's own shape: the full balance sentence (`Zůstatek na účtu po zaúčtování transakce: +1 234,56 CZK.` or `Zostatok na účte po zaúčtovaní sumy platobnej operácie: +14050,70 EUR`), a symbol line whose value is digits only, an account line, or a Tatra `aktualny zostatok` / `uctovny zostatok` / `disponibilny zostatok` line. Bare `Zůstatek`, `zostatok`, and `banka:` do not close it. Lines inside the field are blanked when bank fields are read. A full transaction header or a signed amount in that text does not reject the mail and does not become a transaction. `Částka: záloha 2 500 Kč`, `Dne 1.10.2026 byla na účtu chyba, vracím`, `Suma: 50 EUR za obed`, and `suma: 12,31 EUR za januar` stay payer text. When the mail already contains LF, a lone CR, VT, FF, U+0085, U+2028 or U+2029 stays an ordinary character: it does not split a line and it does not open a transaction. Those separators become newlines only when the mail has no LF. When a ČSOB mail has more than one transaction and at least one balance line, each transaction needs exactly one balance. That line is the bank's. A second balance line in the same transaction throws `InvalidEmailFormatException` with `Transaction has more than one balance line`. The previous balance plus the amount must equal the next balance. A mail with no balance line still parses several transactions, the same as 4.1.0. That is a known limitation: without balance lines a forged transaction in payer text cannot be distinguished from a real one. Check the amount against the bank API or a statement. ČSOB CZ `Účel platby` uses the 4.1.0 word-boundary number up to `Zůstatek`. Without `Zůstatek` a footer number is not a symbol. `AB12`, `INV2026`, and `FV2024001` are not symbols. More than 10 digits is `null`. ČSOB SK reads the first `VS` token on the `informácia pre príjemcu` line. A counterparty account is the account token only. Trailing prose such as ` (Fio banka)` is dropped, and so is `<br>`. `supports()` returns false instead of throwing. A ČSOB header does not need a blank line in front of it. Splits do not use `explode()`.
- `BankMailsParser::parse()` throws `MultipleTransactionsException` when the mail has more than one transaction. Use `parseAll()`. `tryParse()` returns `null`.
- The default facade registers `TatraBankaStatementMailParser` only when a decryptor is passed. A PGP mail without one is `UnsupportedBankException` (`tryParse()` returns `null`).
- When ČSOB CZ, ČSOB SK, or VÚB recognizes a transaction and the amount is missing, unreadable, or in an unknown currency, `money` is `null`. The row is returned.
- Leading indentation and NBSP, U+2009, and U+202F are whitespace in every parser.
- ČSOB SK reads a structured symbol from `referencia platiteľa`, then `VS` or `KS` from the `informácia pre príjemcu` line only. A payer name such as `ADVS2` is not a symbol. Digits longer than 10 are kept.
- When a sender is passed, `BankMailsParser` uses only parsers whose `bankDomains()` match that host. Two parsers accepting one mail throw `AmbiguousBankException` instead of keeping the first match. The library does not verify CardPay `HMAC`/`SIGN` or DKIM. The caller must.
- The release attestation covers the git archive tarball, not the Composer GitHub zipball. Verify it with `gh attestation verify bank-mails-parser-<version>.tar.gz --repo tomaj/bank-mails-parser`.

### Added

- `Money::fromDecimalString()` accepts `12.34`, `12,34`, `-1 234,56`, and `1.234,56`.
- `Currency::fromCode()`, `Currency::tryFromCode()`, and `Currency::minorUnits()`. Known scales include JPY/ISK 0 and BHD/KWD/OMR/TND 3. `XXX` (`N.A.`) is stored with 2 minor units. An unknown code throws `InvalidArgumentException`; it does not fall back to 2.
- `AmbiguousBankException`, `MailContent::$tres`, and `ParserInterface::bankDomains()`.
- `ext-mbstring` is a required extension. `src/` calls `mb_trim()`.
- `BankMailsParser` selects a parser with `supports()`. `null` registers the built-in parsers. The statement parser is included only when a decryptor is passed. An array uses exactly that list.
- `ParserInterface` is `supports()`, `parseAll()`, and `bankDomains()`. `parse()` and `tryParse()` stay on the concrete parser via `ParsesBankMail`. `BankDate`, `Iso4217`, and the trait helpers other than `money()` are `@internal`.
- Withdrawn `BGN` (`975`) and `HRK` (`191`) are accepted so older mail can be parsed. `Money::equals()` and `Currency::equals()` compare by value.
- VÚB `SIGN` is stored on `MailContent::$sign`. VÚB `ŠS`, `SS`, and `S` are `ss`.
- Release tags have no `v` prefix (`5.0.0`, same as `4.1.0`).
- Exception hierarchy: `ParseException`, `UnsupportedBankException`, `InvalidEmailFormatException`, `AmbiguousBankException`, `DecryptionException`, and `ConfigurationException` (not a `ParseException`).
- Release workflow reads the matching section from this changelog, publishes a `.tar.gz` plus SHA256, and attests the archive with `actions/attest-build-provenance`. Packagist updates through the repository webhook. This is not release-please.
- OpenSSF Scorecard (including pull requests) and a README badge.
- CodeQL for `actions` and `javascript-typescript` with `build-mode: none`. PHP is not a CodeQL language.
- Guides: `docs/guide/bank-detection.md`, `docs/guide/exceptions.md`. 4.x docs stay under `docs/4.x/`.
- CI coverage gate: statement coverage must be at least 97% on PHP 8.4. Infection thresholds stay at MSI 89 and covered MSI 90.

### Changed

- The declared license is MIT. `composer.json` previously said `LGPL-2.0-or-later`. The `LICENCE` file already contained the MIT text.
- PHP-CS-Fixer (PER-CS and PHP 8.4 migration rules) replaces PHP_CodeSniffer.
- PHPStan level max.
- Infection 0.35, PHPStan 2.1 (installed 2.3), PHPUnit 13.0 (installed 13.4).
- GitHub Actions are pinned to commit SHAs.

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