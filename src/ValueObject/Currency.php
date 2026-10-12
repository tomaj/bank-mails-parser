<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\ValueObject;

use InvalidArgumentException;

/**
 * ISO 4217 currency.
 *
 * Alphabetic and numeric codes from {@see Iso4217} are accepted, including the
 * withdrawn lev (BGN, 975) and kuna (HRK, 191) so older mail can be parsed again.
 * `978` and `036` are stored as `EUR` and `AUD`. A code that is not in that list throws.
 * Compare values with {@see equals()}. Unserialize does not preserve object identity.
 */
final class Currency
{
    /** @var array<string, self> */
    private static array $cache = [];

    private function __construct(
        public readonly string $value,
    ) {}

    public static function fromCode(string $code): self
    {
        $normalized = strtoupper(trim($code));
        if (preg_match('/^\d{1,3}$/', $normalized) === 1) {
            $padded = str_pad($normalized, 3, '0', STR_PAD_LEFT);
            $alpha = array_search($padded, Iso4217::NUMERIC, true);
            if (!is_string($alpha)) {
                throw new InvalidArgumentException(sprintf('Unsupported currency code "%s".', $code));
            }
            $normalized = $alpha;
        }

        if (!isset(Iso4217::SCALE[$normalized])) {
            throw new InvalidArgumentException(sprintf('Unsupported currency code "%s".', $code));
        }

        return self::$cache[$normalized] ??= new self($normalized);
    }

    public static function tryFromCode(string $code): ?self
    {
        try {
            return self::fromCode($code);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function minorUnits(): int
    {
        return Iso4217::SCALE[$this->value];
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * @return array{value: string}
     */
    public function __serialize(): array
    {
        return ['value' => $this->value];
    }

    /**
     * @param array{value?: string} $data
     */
    public function __unserialize(array $data): void
    {
        $value = $data['value'] ?? '';
        if (!isset(Iso4217::SCALE[$value])) {
            throw new InvalidArgumentException('Invalid currency payload.');
        }
        $this->value = $value;
        self::$cache[$value] ??= $this;
    }
}
