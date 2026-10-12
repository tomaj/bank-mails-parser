<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\ValueObject;

use InvalidArgumentException;

/**
 * Immutable amount stored in minor units plus an ISO currency.
 *
 * 12.34 EUR is stored as amount 1234. The public API does not use floats.
 * Space, NBSP, thin space (U+2009), narrow NBSP (U+202F) and tab are thousands separators.
 * Extra fraction digits are rejected unless they are all zeros. Ambiguous separators
 * and values that do not fit in an int are rejected.
 */
final readonly class Money
{
    public function __construct(
        public int $amount,
        public Currency $currency,
    ) {}

    /**
     * @param string $amount Decimal amount such as "12.34", "12,34" or "-1 234,56"
     * @param Currency|string $currency Currency or ISO code ("EUR", "978", "SEK")
     * @param string|null $decimalSeparator Bank-specific decimal mark. "." or ",".
     *        When omitted, a single separator followed by exactly 3 digits is rejected as ambiguous.
     */
    public static function fromDecimalString(string $amount, Currency|string $currency, ?string $decimalSeparator = null): self
    {
        if (is_string($currency)) {
            $currency = Currency::fromCode($currency);
        }

        return new self(self::toMinorUnits($amount, $currency->minorUnits(), $decimalSeparator), $currency);
    }

    public function toDecimalString(): string
    {
        $scale = self::scale($this->currency->minorUnits());
        $negative = $this->amount < 0;
        if ($this->amount === PHP_INT_MIN) {
            throw new InvalidArgumentException('Amount is too small to format.');
        }
        $absolute = $negative ? -$this->amount : $this->amount;
        if ($scale === 1) {
            return ($negative ? '-' : '') . $absolute;
        }

        $major = intdiv($absolute, $scale);
        $minor = $absolute % $scale;

        return ($negative ? '-' : '') . $major . '.' . str_pad((string) $minor, $this->currency->minorUnits(), '0', STR_PAD_LEFT);
    }

    public function format(): string
    {
        return $this->toDecimalString() . ' ' . $this->currency->value;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency->equals($other->currency);
    }

    public function abs(): self
    {
        if ($this->amount === PHP_INT_MIN) {
            throw new InvalidArgumentException('Amount is too small to negate.');
        }

        return new self($this->amount < 0 ? -$this->amount : $this->amount, $this->currency);
    }

    private static function toMinorUnits(string $amount, int $minorUnits, ?string $decimalSeparator): int
    {
        if ($decimalSeparator !== null && $decimalSeparator !== '.' && $decimalSeparator !== ',') {
            throw new InvalidArgumentException('Decimal separator must be "." or ",".');
        }

        $spaced = trim(str_replace(["\u{00A0}", "\u{2009}", "\u{202F}", "\t"], ' ', trim($amount)));
        self::assertSpaceGroups($spaced, $amount);
        $normalized = str_replace(' ', '', $spaced);
        if ($normalized === '' || $normalized === '-' || $normalized === '+') {
            throw new InvalidArgumentException('Amount must be a decimal number.');
        }

        $negative = str_starts_with($normalized, '-');
        if ($negative || str_starts_with($normalized, '+')) {
            $normalized = substr($normalized, 1);
        }

        if ($normalized === '' || preg_match('/^[0-9.,]+$/', $normalized) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid decimal amount "%s".', $amount));
        }

        $normalized = self::normalizeSeparators($normalized, $decimalSeparator, $amount);
        if (preg_match('/^(?<major>\d+)(?:[.,](?<fraction>\d+))?$/', $normalized, $matches) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid decimal amount "%s".', $amount));
        }

        $fraction = $matches['fraction'] ?? '';
        if (strlen($fraction) > $minorUnits) {
            $extra = substr($fraction, $minorUnits);
            if (preg_match('/^0+$/', $extra) !== 1) {
                throw new InvalidArgumentException(sprintf('Amount "%s" has more fraction digits than the currency allows.', $amount));
            }
            $fraction = substr($fraction, 0, $minorUnits);
        }

        return self::combine($matches['major'], $fraction, $minorUnits, $negative, $amount);
    }

    private static function normalizeSeparators(string $body, ?string $decimalSeparator, string $original): string
    {
        $hasDot = str_contains($body, '.');
        $hasComma = str_contains($body, ',');
        if (!$hasDot && !$hasComma) {
            return $body;
        }

        if ($hasDot && $hasComma) {
            $lastDot = strrpos($body, '.');
            $lastComma = strrpos($body, ',');
            $decimal = ($lastDot !== false && $lastComma !== false && $lastDot > $lastComma) ? '.' : ',';
            if ($decimalSeparator !== null && $decimal !== $decimalSeparator) {
                throw new InvalidArgumentException(sprintf('Invalid decimal amount "%s".', $original));
            }
            $group = $decimal === '.' ? ',' : '.';
            $pos = (int) strrpos($body, $decimal);
            $major = self::stripGroups(substr($body, 0, $pos), $group, $original);
            $fraction = substr($body, $pos + 1);
            if (preg_match('/^\d+$/', $fraction) !== 1) {
                throw new InvalidArgumentException(sprintf('Invalid decimal amount "%s".', $original));
            }

            return $major . $decimal . $fraction;
        }

        $separator = $hasDot ? '.' : ',';
        if (substr_count($body, $separator) > 1) {
            return self::stripGroups($body, $separator, $original);
        }

        if ($decimalSeparator === null) {
            $fractionLength = strlen(substr($body, (int) strrpos($body, $separator) + 1));
            if ($fractionLength === 3) {
                throw new InvalidArgumentException(sprintf('Ambiguous decimal amount "%s".', $original));
            }

            return $body;
        }

        if ($decimalSeparator === $separator) {
            return $body;
        }

        return self::stripGroups($body, $separator, $original);
    }

    private static function stripGroups(string $body, string $group, string $original): string
    {
        if ($group !== '.' && $group !== ',') {
            throw new InvalidArgumentException(sprintf('Invalid decimal amount "%s".', $original));
        }

        $parts = explode($group, $body);
        $first = array_shift($parts);
        if ($first === '' || preg_match('/^\d{1,3}$/', $first) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid decimal amount "%s".', $original));
        }

        foreach ($parts as $part) {
            if (preg_match('/^\d{3}$/', $part) !== 1) {
                throw new InvalidArgumentException(sprintf('Invalid decimal amount "%s".', $original));
            }
        }

        return $first . implode('', $parts);
    }

    private static function combine(string $major, string $fraction, int $minorUnits, bool $negative, string $original): int
    {
        $digits = $minorUnits === 0 ? $major : $major . str_pad($fraction, $minorUnits, '0');
        $digits = ltrim($digits, '0');
        if ($digits === '') {
            return 0;
        }

        $limit = ltrim((string) ($negative ? PHP_INT_MIN : PHP_INT_MAX), '-');
        if (strlen($digits) > strlen($limit) || (strlen($digits) === strlen($limit) && strcmp($digits, $limit) > 0)) {
            throw new InvalidArgumentException(sprintf('Amount "%s" does not fit into an integer number of minor units.', $original));
        }

        if ($negative && $digits === ltrim((string) PHP_INT_MIN, '-')) {
            throw new InvalidArgumentException(sprintf('Amount "%s" does not fit into an integer number of minor units.', $original));
        }

        $units = (int) $digits;

        return $negative ? -$units : $units;
    }

    private static function assertSpaceGroups(string $spaced, string $original): void
    {
        $body = $spaced;
        if (str_starts_with($body, '-') || str_starts_with($body, '+')) {
            $body = substr($body, 1);
        }
        if (!str_contains($body, ' ')) {
            return;
        }

        if (preg_match('/^\d{1,3}(?: \d{3})+(?:[.,]\d+)?$/', $body) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid decimal amount "%s".', $original));
        }
    }

    private static function scale(int $minorUnits): int
    {
        $scale = 1;
        for ($i = 0; $i < $minorUnits; ++$i) {
            $scale *= 10;
        }

        return $scale;
    }
}
