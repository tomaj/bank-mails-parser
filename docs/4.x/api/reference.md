# API Reference (4.x)

::: warning Older Version Documentation
You are viewing documentation for version **4.x**. For the latest version with immutable architecture, see [5.x documentation](/) or read the [5.0 Upgrade Guide](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md).
:::

## MailContent

The `MailContent` class represents a parsed transaction with all extracted fields.

### Namespace

```php
Tomaj\BankMailsParser\MailContent
```

### Architecture (4.x)

Version 4.x uses a mutable object with private properties and public getter/setter methods:

```php
class MailContent
{
    private $amount;
    private $currency;
    private $vs;
    // ... other private properties
    
    public function getAmount(): ?float { ... }
    public function setAmount(float $amount): void { ... }
    
    public function getVs(): ?string { ... }
    public function setVs(string $vs): void { ... }
    // ... other getters and setters
}
```

### Creating MailContent

Parsers create `MailContent` and populate it using setters:

```php
$mailContent = new MailContent();
$mailContent->setAmount(123.45);
$mailContent->setCurrency('EUR');
$mailContent->setVs('1234567890');
```

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

Returns the Variable Symbol (VS), or `null`. Empty strings are converted to `null`.

```php
$vs = $mailContent->getVs(); // "1234567890"
```

#### setVs(string $vs): void

Sets the Variable Symbol. Empty strings are automatically converted to `null`.

#### getSs(): ?string

Returns the Specific Symbol (ŠS/SS), or `null`.

```php
$ss = $mailContent->getSs(); // "9876543210"
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

Sets the receiver message.

#### getDescription(): ?string

Returns the transaction description from the bank, or `null`.

```php
$description = $mailContent->getDescription(); // "SEPA Credit Transfer"
```

#### setDescription(string $description): void

Sets the transaction description.

### ComfortPay/CardPay Fields

#### getCid(): ?string

Client ID (ComfortPay), or `null`.

```php
$cid = $mailContent->getCid(); // "824452"
```

#### getSign(): ?string

HMAC signature, or `null`.

```php
$sign = $mailContent->getSign(); // "C0CBF27F5D97841E"
```

#### getRes(): ?string

Result code (OK, FAIL, etc.), or `null`.

```php
$res = $mailContent->getRes(); // "OK"
```

#### getAc(): ?string

Authorization code, or `null`.

```php
$ac = $mailContent->getAc(); // "558058"
```

#### getCc(): ?string

Masked credit card number, or `null`.

```php
$cc = $mailContent->getCc(); // "************1111"
```

#### getTid(): ?string

Transaction ID, or `null`.

```php
$tid = $mailContent->getTid(); // "11224444"
```

#### getTxn(): ?string

Transaction type (PA, etc.), or `null`.

```php
$txn = $mailContent->getTxn(); // "PA"
```

#### getRc(): ?string

Return code, or `null`.

```php
$rc = $mailContent->getRc(); // "0"
```

## ParserInterface

All parsers implement the `ParserInterface`:

```php
namespace Tomaj\BankMailsParser\Parser;

interface ParserInterface
{
    public function parse(string $content): ?MailContent;
}
```

### Return Values

- Returns `MailContent` object when email format is recognized
- Returns `null` when email format is not recognized

## Available Parsers

### TatraBanka

- **TatraBankaMailParser** - Standard transaction notifications
- **TatraBankaSimpleMailParser** - ComfortPay/CardPay confirmations
- **TatraBankaStatementMailParser** - PGP-encrypted statements

### ČSOB

- **CsobMailParser** - ČSOB Czech Republic emails
- **SkCsobMailParser** - ČSOB Slovakia emails

### VÚB

- **VubMailParser** - VÚB Slovakia emails

## Upgrading to 5.x

Version 5.x introduces immutable value objects with direct property access:

```php
// 4.x (old)
$amount = $mailContent->getAmount();
$mailContent->setAmount(100);

// 5.x (new)
$amount = $mailContent->amount;
$mailContent = new MailContent(amount: 100);
```

See the [5.0 Upgrade Guide](https://github.com/tomaj/bank-mails-parser/blob/master/UPGRADE-5.0.md) for complete migration instructions.
