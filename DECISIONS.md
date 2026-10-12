# Implementation Decisions for Version 5.0.0

This document records key architectural and implementation decisions made during the 5.0.0 upgrade.

## 1. Value Objects

### Money Implementation
**Decision**: Implement custom `Money` value object instead of using moneyphp/money

**Reasoning**:
- **Simplicity**: moneyphp/money is a comprehensive library (200+ KB) but we only need basic money representation
- **No external dependencies**: Keeps the library lightweight and reduces maintenance burden
- **Banking context**: Our use case is simple - parse amounts from emails, no complex calculations needed
- **Performance**: Custom implementation is faster with less overhead
- **Minor units storage**: Stores amount as integers (cents) to avoid floating point precision issues
- **Immutability**: `final readonly` class ensures value object semantics

**Trade-offs**:
- Don't get advanced features like currency conversion, formatting locales, complex arithmetic
- These features are not needed for email parsing use case

### Currency Implementation
**Decision**: `Currency` is a `final class` backed by the active ISO 4217 list, not an enum.

**Reasoning**:
- A closed enum cannot represent every active code, and CardPay sends numeric codes such as `208` (DKK) and `036` (AUD).
- `fromCode()` / `tryFromCode()` accept alphabetic and zero-padded numeric codes from list one published 2026-09-17.
- An unknown CardPay `CURR` leaves `money` null and keeps the rest of the notification.
- Root `tsconfig.json` stays so CodeQL can extract `docs/site-nav.ts`. It is not an unused leftover.

### DateTimeImmutable
**Decision**: Replace `int|false|null` timestamps with `?DateTimeImmutable`

**Reasoning**:
- **Type safety**: Clear semantics, no ambiguous `false` return
- **Modern PHP**: DateTimeImmutable is the standard for immutable dates in PHP 8+
- **API consistency**: Better than mixing timestamps and false values
- **Timezone support**: Proper timezone handling built-in

**Migration**: Callers use `DateTimeImmutable`. There is no timestamp getter.

### Transaction Type Enum
**Decision**: NOT implemented

**Reasoning**:
- **Unreliable detection**: Transaction direction (incoming/outgoing) is already encoded in amount sign
- **Bank-specific**: Different banks use different terminology and patterns
- **Ambiguous cases**: Some transactions (transfers between own accounts) don't fit incoming/outgoing classification
- **Insufficient data**: Email content often lacks explicit transaction type field
- **Money.isPositive()/isNegative()**: Amount sign already provides this information

**Conclusion**: Not enough reliable signals in email content to warrant enum. Amount sign is sufficient.

## 2. Exception Hierarchy

**Decision**: Implement exception-based error handling with `ParseException` hierarchy

**Structure**:
```
ParseException (base)
├── UnsupportedBankException (no parser found)
└── InvalidEmailFormatException (format recognized but invalid)
```

**Reasoning**:
- **Clear error semantics**: Exceptions make it explicit why parsing failed
- **Type-safe error handling**: Catch specific exception types
- **One pair of methods**: `parse()` throws. `tryParse()` returns `null`. There is no `parseOrFail()`, and `parse()` does not return `null`.
- **Same pair everywhere**: `ParserInterface` and `BankMailsParser` both expose `parse()` and `tryParse()`.

## 3. Bank Detection / Parser Resolution

**Decision**: Implement `BankMailsParser` facade with automatic parser selection

**Architecture**:
- **ParserInterface.supports()**: Each parser reports if it can handle an email
- **Detection signals**: Email content patterns, sender address, subject line
- **Registration**: Parsers can be registered or use defaults
- **Fallthrough**: Tries all parsers in order until one matches

**Benefits**:
- **User-friendly**: Single entry point, no need to know which parser to use
- **Extensible**: Easy to add custom parsers
- **Maintainable**: Parser-specific logic stays in parser classes

**Implementation details**:
- TatraBanka: Checks for "@tatrabanka.sk" sender or "TATRA BANKA" content
- ČSOB CZ: Czech language patterns ("Dne ... byl... zaúčtovan")
- ČSOB SK: Slovak language patterns ("dňa ... bola ... zaúčtovaná")
- VÚB: Slovak field patterns ("Dátum:", "Suma:")
- TatraBanka Simple: Key=value format with RES/TRES fields
- TatraBanka Statement: PGP encrypted message detection

## 4. Release Automation

**Decision**: Use GitHub Actions for release automation, not release-please

**Reasoning**:
- **Simpler**: Direct workflow triggered by git tags
- **Control**: Full control over release notes format
- **CHANGELOG integration**: Extracts notes from CHANGELOG.md
- **Artifact attestations**: Uses actions/attest-build-provenance for supply chain security
- **Packagist**: Auto-updates via webhook (configured in repository settings)

**Workflow**:
1. Developer creates a tag without a `v` prefix (e.g., `5.0.0`, consistent with `4.1.0`)
2. Rename `## [Unreleased]` to `## [5.0.0] - YYYY-MM-DD` and change `[Unreleased]: …/compare/4.1.0...HEAD` to `[5.0.0]: …/compare/4.1.0...5.0.0` in `CHANGELOG.md` and `docs/changelog.md`
3. GitHub Actions extracts version from tag
4. Reads corresponding section from CHANGELOG.md
5. Creates release archive with SHA256 checksum
6. Generates build provenance attestation
7. Creates GitHub Release with notes and artifacts
8. Packagist webhook triggers automatic update

## 4b. ParserInterface is three methods

**Decision**: `ParserInterface` is `supports()`, `parseAll()`, and `bankDomains()`. `parse()` and `tryParse()` are not on the interface.

**Reasoning**: The facade only needs to decide whether a parser accepts a mail, which hosts it belongs to, and how to read every transaction. A single-transaction `parse()` on the interface would freeze a five-method contract. `ParsesBankMail` still supplies `tryParse()` and a default `parseAll()` that returns `[$this->parse()]`. `money()` stays as the documented helper. `BankDate`, `Iso4217`, `SenderHost`, and the other trait helpers are `@internal`, so a later change to them is not a supported break. A custom parser must implement `bankDomains()`. An empty list is used only when the caller omits the sender. Pass the sender.

## 5. Security Workflows

### CodeQL
**Decision**: Analyze GitHub Actions and JavaScript/TypeScript. PHP is not a CodeQL language.

**Reasoning**:
- **Language support**: CodeQL has no PHP extractor
- **What runs**: `actions` and `javascript-typescript`, both with `build-mode: none`
- **Queries**: `security-and-quality` (`security-extended` is a subset and is not listed separately)
- **TypeScript project**: `docs/tsconfig.json` is the docs project. The repository root `tsconfig.json` stays so CodeQL can see `docs/site-nav.ts`. A docs-only config previously left the JavaScript extractor with no input.

### OpenSSF Scorecard
**Decision**: Implement with action version pinning

**Benefits**:
- **Supply chain security**: Tracks security best practices
- **Action pinning**: All actions pinned to SHA for security
- **Badge**: Shows security score on README
- **SARIF upload**: Integrates with GitHub Security tab

**Requirements met**:
- ✅ Actions pinned to SHA with comments showing version
- ✅ Weekly automated runs
- ✅ Branch protection (configured separately)
- ✅ SARIF results uploaded to code scanning

### Artifact Attestations
**Decision**: Implement for release archives

**Benefits**:
- **Provenance**: Cryptographic proof of build origin
- **Verification**: Users can verify archive authenticity
- **Supply chain**: SLSA compliance for software supply chain

## 6. Testing Strategy

**Coverage targets**:
- Line coverage: ≥97% (maintained)
- MSI (Mutation Score Indicator): ≥89%
- Covered MSI: ≥90%

**Test additions**:
- Currency enum tests
- Money value object tests  
- BankMailsParser auto-detection tests
- Exception hierarchy tests
- All parsers tested with supports() method

## 7. Backward Compatibility

**Breaking changes**:
1. `MailContent` now has `Money $money` instead of `float $amount` + `string $currency`
2. `MailContent` now has `?DateTimeImmutable $transactionDate` instead of `int|false|null`
3. Minimum PHP version: 8.2 → 8.4

**Migration aids**:
- `UPGRADE-5.0.md`
- 4.x documentation kept under `docs/4.x/`
- No deprecated getters and no `parseOrFail()`

## 8. Documentation

**Structure**:
- **5.x docs**: Shows new immutable API with Money, DateTimeImmutable, auto-detection
- **4.x docs**: Preserved showing old getter/setter API for reference
- **New pages**: Bank detection guide, exceptions guide
- **API reference**: Updated with value objects and new methods

**Format**: VitePress with path-based sidebar for version switching
