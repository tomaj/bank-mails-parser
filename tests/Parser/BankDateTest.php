<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\BankDate;

#[CoversClass(BankDate::class)]
final class BankDateTest extends TestCase
{
    #[Test]
    public function parsesInBratislavaAndRejectsInvalidDates(): void
    {
        $zone = BankDate::bratislava();
        $this->assertSame('Europe/Bratislava', $zone->getName());
        $this->assertSame('UTC', BankDate::utc()->getName());

        $parsed = BankDate::parse('6.9.2019', '!j.n.Y', $zone);
        $this->assertInstanceOf(DateTimeImmutable::class, $parsed);
        $this->assertSame('2019-09-06T00:00:00+02:00', $parsed->format('c'));

        $this->assertNull(BankDate::parse('not-a-date', '!j.n.Y', $zone));
        $this->assertNull(BankDate::parse('31.2.2015', '!j.n.Y', $zone));
        $this->assertNull(BankDate::parse('6.9.2019', '!!!', $zone));
    }

    #[Test]
    public function parseFirstUsesTheFirstFormatThatMatches(): void
    {
        $zone = BankDate::bratislava();
        $parsed = BankDate::parseFirst('6.9.2019', ['!Y-m-d', '!j.n.Y'], $zone);

        $this->assertInstanceOf(DateTimeImmutable::class, $parsed);
        $this->assertSame('2019-09-06', $parsed->format('Y-m-d'));
        $this->assertNull(BankDate::parseFirst('nope', ['!Y-m-d', '!j.n.Y'], $zone));
    }

    #[Test]
    public function englishMonthsAndMidnight24BecomeTheNextDay(): void
    {
        $zone = BankDate::bratislava();
        $months = [
            'January' => 1, 'Jan' => 1,
            'February' => 2, 'Feb' => 2,
            'March' => 3, 'Mar' => 3,
            'April' => 4, 'Apr' => 4,
            'May' => 5,
            'June' => 6, 'Jun' => 6,
            'July' => 7, 'Jul' => 7,
            'August' => 8, 'Aug' => 8,
            'September' => 9, 'Sep' => 9, 'Sept' => 9,
            'October' => 10, 'Oct' => 10,
            'November' => 11, 'Nov' => 11,
            'December' => 12, 'Dec' => 12,
        ];
        foreach ($months as $name => $month) {
            $parsed = BankDate::parseEnglish('16 ' . $name . ' 2015 12:51', $zone);
            $this->assertInstanceOf(DateTimeImmutable::class, $parsed);
            $this->assertSame(sprintf('2015-%02d-16 12:51:00', $month), $parsed->format('Y-m-d H:i:s'));
        }

        $midnight = BankDate::parse('31.12.2015 24:00:00', '!j.n.Y G:i:s', $zone);
        $this->assertInstanceOf(DateTimeImmutable::class, $midnight);
        $this->assertSame('2016-01-01 00:00:00', $midnight->format('Y-m-d H:i:s'));
        $this->assertNull(BankDate::parse('31.12.9999 24:00', '!j.n.Y G:i', $zone));
    }
}
