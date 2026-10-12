<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Parses bank email dates without inheriting date.timezone and without rolling invalid dates forward.
 *
 * @internal Not part of the supported public API. Call it from a parser, and expect the formats to change.
 */
final class BankDate
{
    public const string ZONE = 'Europe/Bratislava';

    public static function bratislava(): DateTimeZone
    {
        return new DateTimeZone(self::ZONE);
    }

    public static function utc(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }

    /**
     * @param list<string> $formats
     */
    public static function parseFirst(string $value, array $formats, DateTimeZone $zone): ?DateTimeImmutable
    {
        foreach ($formats as $format) {
            $parsed = self::parse($value, $format, $zone);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * English month names, the way 4.x `strtotime()` read `16 January 2015 12:51`.
     * A numeric date stays on {@see parse()} so `31.2.2015` does not roll forward.
     */
    public static function parseEnglish(string $value, DateTimeZone $zone): ?DateTimeImmutable
    {
        if (preg_match('/^(\d{1,2})[ \t]+([A-Za-z]+)[ \t]+(\d{4})(?:[ \t]+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', trim($value), $match) !== 1) {
            return null;
        }

        $month = self::englishMonth($match[2]);
        if ($month === null) {
            return null;
        }

        $clock = sprintf(
            '%04d-%02d-%02d %02d:%02d:%02d',
            (int) $match[3],
            $month,
            (int) $match[1],
            (int) ($match[4] ?? 0),
            (int) ($match[5] ?? 0),
            (int) ($match[6] ?? 0),
        );

        return self::parse($clock, '!Y-m-d H:i:s', $zone);
    }

    public static function parse(string $value, string $format, DateTimeZone $zone): ?DateTimeImmutable
    {
        $value = trim($value);
        $nextDay = false;
        if ((str_contains($format, 'G:i') || str_contains($format, 'H:i'))
            && preg_match('/24:00(?::00)?$/', $value) === 1
        ) {
            $rewritten = preg_replace('/24:00(:00)?$/', '00:00$1', $value, 1);
            if (is_string($rewritten)) {
                $value = $rewritten;
                $nextDay = true;
            }
        }

        // Parse the wall clock in UTC first. Europe/Bratislava createFromFormat rolls a
        // spring-forward gap (29.3.2015 02:30 → 03:30) without setting getLastErrors().
        $wall = DateTimeImmutable::createFromFormat($format, $value, self::utc());
        if ($wall === false) {
            return null;
        }

        $errors = DateTimeImmutable::getLastErrors();
        if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        $year = (int) $wall->format('Y');
        if ($year < 1000 || $year > 9999) {
            return null;
        }

        $clock = $wall->format('Y-m-d H:i:s');
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $clock, $zone);
        if ($parsed === false || $parsed->format('Y-m-d H:i:s') !== $clock) {
            return null;
        }

        if ($nextDay) {
            $parsed = $parsed->modify('+1 day');
            $year = (int) $parsed->format('Y');
            if ($year < 1000 || $year > 9999) {
                return null;
            }
        }

        return $parsed;
    }

    private static function englishMonth(string $name): ?int
    {
        return match (strtolower($name)) {
            'january', 'jan' => 1,
            'february', 'feb' => 2,
            'march', 'mar' => 3,
            'april', 'apr' => 4,
            'may' => 5,
            'june', 'jun' => 6,
            'july', 'jul' => 7,
            'august', 'aug' => 8,
            'september', 'sep', 'sept' => 9,
            'october', 'oct' => 10,
            'november', 'nov' => 11,
            'december', 'dec' => 12,
            default => null,
        };
    }
}
