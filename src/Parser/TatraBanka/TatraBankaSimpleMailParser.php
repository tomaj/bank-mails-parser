<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use DateTimeImmutable;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\BankDate;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;
use Tomaj\BankMailsParser\ValueObject\Currency;

final readonly class TatraBankaSimpleMailParser implements ParserInterface
{
    use ParsesBankMail;

    private const array FIELD_MAP = [
        'VS' => 'vs',
        'RES' => 'res',
        'AC' => 'ac',
        'SIGN' => 'sign',
        'TRES' => 'tres',
        'CID' => 'cid',
        'AMT' => 'amount',
        'CURR' => 'currency',
        'CC' => 'cc',
        'TID' => 'tid',
        'TIMESTAMP' => 'transactionDate',
        'TXN' => 'txn',
        'RC' => 'rc',
        'HMAC' => 'sign',
    ];

    /**
     * @return list<string>
     */
    #[\Override]
    public function bankDomains(): array
    {
        return ['tatrabanka.sk'];
    }

    #[\Override]
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return $this->supportsQuietly(function () use ($content): bool {
            $block = $this->firstNotificationBlock($this->withoutFreeText($this->scrubUtf8($this->withoutByteOrderMark($content))));
            if ($block === null) {
                return false;
            }

            $keys = [];
            foreach ($block as [$key]) {
                $keys[$key] = true;
            }

            return self::cardPayBlock($keys);
        });
    }

    public function parse(string $content): MailContent
    {
        $block = $this->firstNotificationBlock($this->withoutFreeText($this->scrubUtf8($this->requireUniformLineBreaks($content))));
        if ($block === null) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Empty content');
        }

        $data = [];
        $seen = [];
        $sign = null;
        $hmac = null;
        foreach ($block as [$key, $value]) {
            $value = mb_trim($value);
            if ($key === 'SIGN') {
                if ($sign !== null && $sign !== $value) {
                    throw InvalidEmailFormatException::malformedContent(self::class, 'Duplicate CardPay field sign');
                }
                $sign = $value;

                continue;
            }
            if ($key === 'HMAC') {
                if ($hmac !== null && $hmac !== $value) {
                    throw InvalidEmailFormatException::malformedContent(self::class, 'Duplicate CardPay field sign');
                }
                $hmac = $value;

                continue;
            }
            if (!isset(self::FIELD_MAP[$key])) {
                continue;
            }

            $property = self::FIELD_MAP[$key];
            $data[$property] = $value;
            $seen[$property][] = $data[$property];
        }
        if ($hmac !== null || $sign !== null) {
            $data['sign'] = $hmac ?? $sign;
        }

        foreach ($seen as $property => $values) {
            if (count(array_unique($values)) > 1) {
                throw InvalidEmailFormatException::malformedContent(self::class, 'Duplicate CardPay field ' . $property);
            }
        }

        if (!isset($data['res']) && !isset($data['tres'])) {
            throw InvalidEmailFormatException::missingRequiredField(self::class, 'RES');
        }

        $money = null;
        if (isset($data['amount']) && $data['amount'] !== '') {
            $currency = isset($data['currency']) && $data['currency'] !== '' ? $data['currency'] : 'EUR';
            if (Currency::tryFromCode($currency) !== null) {
                $money = $this->money($data['amount'], $currency, '.');
            }
        }

        $tres = $data['tres'] ?? null;
        $res = $tres ?? ($data['res'] ?? null);

        $transactionDate = null;
        if (isset($data['transactionDate']) && $data['transactionDate'] !== '') {
            $transactionDate = self::parseTimestamp($data['transactionDate']);
        }

        return new MailContent(
            money: $money,
            transactionDate: $transactionDate,
            vs: $data['vs'] ?? null,
            cid: $data['cid'] ?? null,
            sign: $data['sign'] ?? null,
            res: $res,
            ac: $data['ac'] ?? null,
            cc: $data['cc'] ?? null,
            tid: $data['tid'] ?? null,
            txn: $data['txn'] ?? null,
            rc: $data['rc'] ?? null,
            tres: $tres,
        );
    }

    private static function parseTimestamp(string $value): DateTimeImmutable
    {
        if (preg_match('/^\d{14}$/', $value) === 1) {
            $parsed = BankDate::parse($value, '!dmYHis', BankDate::utc());
            if ($parsed === null) {
                throw InvalidEmailFormatException::malformedContent(self::class, 'TIMESTAMP is not a valid date');
            }

            return $parsed;
        }

        if (preg_match('/^\d{10}$/', $value) === 1) {
            return new DateTimeImmutable('@' . $value);
        }

        throw InvalidEmailFormatException::malformedContent(self::class, 'TIMESTAMP is not a valid date');
    }

    /**
     * First contiguous KEY=value region that carries RES or TRES. Quoted lines are ignored.
     *
     * @return list<array{0: string, 1: string}>|null
     */
    private function firstNotificationBlock(string $content): ?array
    {
        $lines = preg_split('/\R/u', $content);
        if ($lines === false) {
            return null;
        }

        /** @var list<array{0: string, 1: string}> $pairs */
        $pairs = [];
        $hasResult = false;
        foreach ($lines as $line) {
            $bare = preg_replace('/^\x{FEFF}/u', '', $line) ?? $line;
            if (preg_match('/^[ \t]*>/', $bare) === 1 || trim($bare) === '') {
                continue;
            }

            if ($pairs !== [] && self::mixesProse($bare)) {
                return $pairs;
            }

            $linePairs = self::cardPayPairs($bare);
            if ($linePairs === null) {
                if ($hasResult && $pairs !== []) {
                    return $pairs;
                }
                $pairs = [];
                $hasResult = false;

                continue;
            }

            foreach ($linePairs as $pair) {
                if ($pair[0] === 'RES' || $pair[0] === 'TRES') {
                    $hasResult = true;
                }
                $pairs[] = $pair;
            }
        }

        if ($hasResult && $pairs !== []) {
            return $pairs;
        }

        return null;
    }

    /**
     * @return list<array{0: string, 1: string}>|null
     */
    private static function cardPayPairs(string $line): ?array
    {
        $parts = preg_split('/\s+/u', trim($line));
        if ($parts === false || $parts === [] || $parts === ['']) {
            return null;
        }

        $pairs = [];
        foreach ($parts as $part) {
            if (preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $part, $match) !== 1) {
                continue;
            }
            $pairs[] = [$match[1], $match[2]];
        }

        return $pairs === [] ? null : $pairs;
    }

    private static function mixesProse(string $line): bool
    {
        $parts = preg_split('/\s+/u', trim($line));
        if ($parts === false) {
            return false;
        }
        foreach ($parts as $part) {
            if ($part !== '' && preg_match('/^[A-Z][A-Z0-9_]*=/', $part) !== 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, true> $keys
     */
    private static function cardPayBlock(array $keys): bool
    {
        $hasResult = isset($keys['RES']) || isset($keys['TRES']);
        $hasAnchor = isset($keys['AMT']) || isset($keys['VS']) || isset($keys['HMAC']) || isset($keys['SIGN']);

        return $hasResult && $hasAnchor;
    }
}
