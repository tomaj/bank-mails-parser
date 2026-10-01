# API Reference

Complete API documentation for Bank Mails Parser.

## MailContent

The `MailContent` class represents a parsed transaction with all extracted fields.

### Namespace

```php
Tomaj\BankMailsParser\MailContent
```

### Properties

All properties are private with public getter/setter methods.

### Financial Information

#### getAmount(): ?float

Returns the transaction amount, or `null` if not present.

```php
$amount = $mailContent->getAmount(); // 123.45
```

#### setAmount(float $amount): void

Sets the transaction amount.

#### getCurrency(): ?string

Returns the currency code (EUR, CZK, USD, etc.), or `null`.

```php
$currency = $mailContent->getCurrency(); // "EUR"
```

#### setCurrency(string $currency): void

Sets the currency code.

#### getTransactionDate(): int|false|null

Returns the transaction date as Unix timestamp, `false` on parse error, or `null` if not present.

```php
$timestamp = $mailContent->getTransactionDate();
if ($timestamp !== null && $timestamp !== false) {
    echo date('Y-m-d H:i:s', $timestamp);
}
```

#### setTransactionDate(int|false $transactionDate): void

Sets the transaction date timestamp.

### Account Information

#### getAccountNumber(): ?string

Returns the destination/recipient account number (IBAN or local format), or `null`.

```php
$account = $mailContent->getAccountNumber(); // "SK9812353347235"
```

#### setAccountNumber(string $accountNumber): void

Sets the destination account number.

#### getSourceAccountNumber(): ?string

Returns the source/sender account number, or `null`.

```php
$source = $mailContent->getSourceAccountNumber(); // "1100/000000-261426464"
```

#### setSourceAccountNumber(string $sourceAccountNumber): void

Sets the source account number.

### Banking Symbols

#### getVs(): ?string

Returns the Variable Symbol (VS), or `null`.

```php
$vs = $mailContent->getVs(); // "1234567890"
```

#### setVs(string $vs): void

Sets the Variable Symbol. Empty strings are converted to `null`.

#### getSs(): ?string

Returns the Specific Symbol (ŠS), or `null`.

```php
$ss = $mailContent->getSs(); // "9087654321"
```

#### setSs(string $ss): void

Sets the Specific Symbol. Empty strings are converted to `null`.

#### getKs(): ?string

Returns the Constant Symbol (KS), or `null`.

```php
$ks = $mailContent->getKs(); // "0308"
```

#### setKs(string $ks): void

Sets the Constant Symbol. Empty strings are converted to `null`.

### Transaction Details

#### getReceiverMessage(): ?string

Returns the payment message/note for recipient, or `null`.

```php
$message = $mailContent->getReceiverMessage(); // "Invoice payment"
```

#### setReceiverMessage(string $receiverMessage): void

Sets the receiver message. Empty strings are converted to `null`.

#### getDescription(): ?string

Returns the transaction description, or `null`.

```php
$description = $mailContent->getDescription(); // "CCINT 1100/000000-261426464"
```

#### setDescription(string $description): void

Sets the transaction description. Empty strings are converted to `null`.

### TatraBanka ComfortPay/CardPay Fields

These fields are specific to TatraBanka electronic payments (ComfortPay, CardPay).

#### getCid(): ?string

Returns the Client ID, or `null`.

```php
$cid = $mailContent->getCid(); // "824452"
```

#### setCid(string $cid): void

Sets the Client ID. Empty strings are converted to `null`.

#### getSign(): ?string

Returns the HMAC signature, or `null`.

```php
$sign = $mailContent->getSign(); // "C0CBF27F5D97841E"
```

#### setSign(string $sign): void

Sets the HMAC signature. Empty strings are converted to `null`.

#### getRes(): ?string

Returns the transaction result code (OK, FAIL), or `null`.

```php
$res = $mailContent->getRes(); // "OK" or "FAIL"
```

#### setRes(string $res): void

Sets the result code. Empty strings are converted to `null`.

#### getAc(): ?string

Returns the authorization code, or `null`.

```php
$ac = $mailContent->getAc(); // "558058"
```

#### setAc(string $ac): void

Sets the authorization code. Empty strings are converted to `null`.

#### getCc(): ?string

Returns masked credit card information, or `null`.

```php
$cc = $mailContent->getCc(); // "************1111"
```

#### setCc(string $cc): void

Sets the credit card information. Empty strings are converted to `null`.

#### getTid(): ?string

Returns the transaction ID, or `null`.

```php
$tid = $mailContent->getTid(); // "11224444"
```

#### setTid(string $tid): void

Sets the transaction ID. Empty strings are converted to `null`.

#### getTxn(): ?string

Returns the transaction type (PA, etc.), or `null`.

```php
$txn = $mailContent->getTxn(); // "PA"
```

#### setTxn(string $txn): void

Sets the transaction type. Empty strings are converted to `null`.

#### getRc(): ?string

Returns the return/response code, or `null`.

```php
$rc = $mailContent->getRc(); // "00"
```

#### setRc(string $rc): void

Sets the return code. Empty strings are converted to `null`.

## ParserInterface

All parser classes implement this interface.

### Namespace

```php
Tomaj\BankMailsParser\Parser\ParserInterface
```

### Methods

#### parse(string $content): ?MailContent

Parses a single transaction from email content.

**Parameters:**
- `$content` - Email body content as string

**Returns:**
- `MailContent` object if parsing succeeds
- `null` if email format not recognized or required fields missing

```php
$parser = new TatraBankaMailParser();
$mailContent = $parser->parse($emailBody);

if ($mailContent === null) {
    // Parsing failed
} else {
    // Successfully parsed
}
```

## Bank-Specific Parsers

### TatraBanka

#### TatraBankaMailParser

```php
namespace Tomaj\BankMailsParser\Parser\TatraBanka;

class TatraBankaMailParser implements ParserInterface
{
    public function parse(string $content): ?MailContent
}
```

Parses standard TatraBanka notification emails.

#### TatraBankaSimpleMailParser

```php
namespace Tomaj\BankMailsParser\Parser\TatraBanka;

class TatraBankaSimpleMailParser implements ParserInterface
{
    public function parse(string $content): ?MailContent
}
```

Parses TatraBanka ComfortPay/CardPay emails with key=value format.

#### TatraBankaStatementMailParser

```php
namespace Tomaj\BankMailsParser\Parser\TatraBanka;

class TatraBankaStatementMailParser
{
    public function __construct(TatraBankaMailDecryptor $decryptor)
    
    public function parseMulti(string $content): array
}
```

Parses PGP-encrypted TatraBanka statement emails. Returns array of `MailContent` objects.

#### TatraBankaMailDecryptor

```php
namespace Tomaj\BankMailsParser\Parser\TatraBanka;

class TatraBankaMailDecryptor
{
    public function __construct(
        string $privateKeyPath,
        string $passphrase
    )
    
    public function decrypt(string $contents): ?string
}
```

Decrypts PGP-encrypted TatraBanka emails.

**Parameters:**
- `$privateKeyPath` - Path to PGP private key file (.asc)
- `$passphrase` - Passphrase for the private key

**Returns:**
- Decrypted plain text content, or `null` on failure

**Throws:**
- `\Exception` if private key is missing or cannot be read

### ČSOB Czech Republic

#### CsobMailParser

```php
namespace Tomaj\BankMailsParser\Parser\Csob;

class CsobMailParser implements ParserInterface
{
    public function parse(string $content): ?MailContent
    
    public function parseMulti(string $content): array
}
```

Parses ČSOB CZ emails. Supports multi-transaction emails via `parseMulti()`.

### ČSOB Slovakia

#### SkCsobMailParser

```php
namespace Tomaj\BankMailsParser\Parser\Csob;

class SkCsobMailParser implements ParserInterface
{
    public function parse(string $content): ?MailContent
    
    public function parseMulti(string $content): array
}
```

Parses ČSOB SK emails. Supports multi-transaction emails via `parseMulti()`.

### VÚB

#### VubMailParser

```php
namespace Tomaj\BankMailsParser\Parser\Vub;

class VubMailParser implements ParserInterface
{
    public function parse(string $content): ?MailContent
}
```

Parses VÚB bank emails.

## Type Declarations

All classes use strict typing (`declare(strict_types=1)`):

- **Nullable types**: Properties and return values use `?type` for optional fields
- **Union types**: `int|false|null` for transaction dates (supports parse errors)
- **Strict comparisons**: All code uses `===` instead of `==`
- **Type safety**: All parameters have explicit type declarations

## Next Steps

- See [Examples](/guide/examples) for usage patterns
- Check bank-specific guides for detailed information
- Learn how to [add a new bank](/guide/adding-bank)
