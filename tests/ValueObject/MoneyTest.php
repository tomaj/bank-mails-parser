<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

#[CoversClass(Money::class)]
final class MoneyTest extends TestCase
{
    #[Test]
    public function createsFromMinorUnits(): void
    {
        $money = new Money(1234, Currency::fromCode('EUR'));

        self::assertSame(1234, $money->amount);
        self::assertSame(Currency::fromCode('EUR'), $money->currency);
        self::assertSame('12.34', $money->toDecimalString());
    }

    #[Test]
    public function createsFromDecimalStringWithDot(): void
    {
        $money = Money::fromDecimalString('12.34', Currency::fromCode('EUR'));

        self::assertSame(1234, $money->amount);
        self::assertSame('12.34', $money->toDecimalString());
    }

    #[Test]
    public function createsFromDecimalStringWithCommaAndSpaces(): void
    {
        $money = Money::fromDecimalString('1 234,56', 'EUR');

        self::assertSame(123456, $money->amount);
        self::assertSame('1234.56', $money->toDecimalString());
    }

    #[Test]
    public function createsFromEuropeanThousandsSeparator(): void
    {
        $money = Money::fromDecimalString('1.234,56', Currency::fromCode('CZK'));

        self::assertSame(123456, $money->amount);
        self::assertSame(Currency::fromCode('CZK'), $money->currency);
    }

    #[Test]
    public function createsFromUsThousandsSeparator(): void
    {
        $money = Money::fromDecimalString('1,234.56', Currency::fromCode('USD'));

        self::assertSame(123456, $money->amount);
    }

    #[Test]
    public function createsFromNumericCurrencyCode(): void
    {
        $money = Money::fromDecimalString('12.34', '978');

        self::assertSame(1234, $money->amount);
        self::assertSame(Currency::fromCode('EUR'), $money->currency);
    }

    #[Test]
    public function createsNegativeAmount(): void
    {
        $money = Money::fromDecimalString('-12,34', Currency::fromCode('EUR'));

        self::assertSame(-1234, $money->amount);
        self::assertSame('-12.34', $money->toDecimalString());
        self::assertTrue($money->isNegative());
        self::assertFalse($money->isPositive());
        self::assertFalse($money->isZero());
    }

    #[Test]
    public function throwsExceptionForInvalidCurrencyCode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimalString('12.34', 'INVALID');
    }

    #[Test]
    public function throwsExceptionForInvalidAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimalString('abc', Currency::fromCode('EUR'));
    }

    #[Test]
    public function throwsExceptionForEmptyAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimalString('   ', Currency::fromCode('EUR'));
    }

    #[Test]
    public function formatsAsString(): void
    {
        $money = Money::fromDecimalString('12.34', Currency::fromCode('EUR'));

        self::assertSame('12.34 EUR', $money->format());
    }

    #[Test]
    public function detectsPositiveAmount(): void
    {
        $money = Money::fromDecimalString('12.34', Currency::fromCode('EUR'));

        self::assertTrue($money->isPositive());
        self::assertFalse($money->isNegative());
        self::assertFalse($money->isZero());
    }

    #[Test]
    public function getsAbsoluteAmount(): void
    {
        $money = Money::fromDecimalString('-12.34', Currency::fromCode('EUR'));
        $absolute = $money->abs();

        self::assertSame(1234, $absolute->amount);
        self::assertSame('12.34', $absolute->toDecimalString());
        self::assertSame(Currency::fromCode('EUR'), $absolute->currency);
    }

    #[Test]
    public function absoluteOfPositiveStaysPositive(): void
    {
        $money = Money::fromDecimalString('3.00', Currency::fromCode('EUR'));

        self::assertSame(300, $money->abs()->amount);
    }

    #[Test]
    public function rejectsExtraFractionDigits(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimalString('12.345', Currency::fromCode('EUR'));
    }

    #[Test]
    public function rejectsFractionThatWouldRoundIntoNextMajorUnit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimalString('12.995', Currency::fromCode('EUR'));
    }

    #[Test]
    public function padsSingleFractionDigit(): void
    {
        $money = Money::fromDecimalString('12.3', Currency::fromCode('EUR'));

        self::assertSame(1230, $money->amount);
        self::assertSame('12.30', $money->toDecimalString());
    }

    #[Test]
    public function handlesZeroAmount(): void
    {
        $money = Money::fromDecimalString('0', Currency::fromCode('EUR'));

        self::assertSame(0, $money->amount);
        self::assertTrue($money->isZero());
        self::assertFalse($money->isPositive());
        self::assertFalse($money->isNegative());
        self::assertSame('0.00', $money->toDecimalString());
        self::assertSame('0.00 EUR', $money->format());
    }

    #[Test]
    public function acceptsLeadingPlus(): void
    {
        $money = Money::fromDecimalString('+8', Currency::fromCode('CZK'));

        self::assertSame(800, $money->amount);
        self::assertSame('8.00', $money->toDecimalString());
    }

    #[Test]
    public function rejectsAmbiguousSeparatorsAndExtraPrecisionAndOverflow(): void
    {
        foreach (['1.234', '1,234', '12.345', '0,005', '12,3456789', '99999999999999999', '999999999999999999999', '1,2,3', '.5'] as $amount) {
            try {
                Money::fromDecimalString($amount, 'EUR');
                self::fail($amount . ' must be rejected');
            } catch (InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }

        $grouped = Money::fromDecimalString('1.234', 'EUR', ',');
        self::assertSame(123400, $grouped->amount);
        self::assertSame('1234.00', $grouped->toDecimalString());

        $european = Money::fromDecimalString('1.234,56', 'EUR');
        self::assertSame(123456, $european->amount);

        $us = Money::fromDecimalString('1,234.56', 'USD');
        self::assertSame(123456, $us->amount);

        $jpy = Money::fromDecimalString('1234', 'JPY');
        self::assertSame(1234, $jpy->amount);
        self::assertSame('1234', $jpy->toDecimalString());

        $bhd = Money::fromDecimalString('1.234', 'BHD', '.');
        self::assertSame(1234, $bhd->amount);
        self::assertSame('1.234', $bhd->toDecimalString());

        $this->expectException(InvalidArgumentException::class);
        Money::fromDecimalString('1', 'EUR', 'x');
    }

    #[Test]
    public function refusesToFormatTheSmallestInteger(): void
    {
        $money = new Money(PHP_INT_MIN, Currency::fromCode('EUR'));

        $this->expectException(InvalidArgumentException::class);
        $money->toDecimalString();
    }

    #[Test]
    public function refusesToNegateTheSmallestInteger(): void
    {
        $money = new Money(PHP_INT_MIN, Currency::fromCode('JPY'));

        $this->expectException(InvalidArgumentException::class);
        $money->abs();
    }

    #[Test]
    public function rejectsADecimalMarkThatDisagreesWithTheAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromDecimalString('1.234,56', 'EUR', '.');
    }

    #[Test]
    public function rejectsAnEmptyFractionAndALeadingGroupSeparator(): void
    {
        foreach (['1,234.', '.234.567'] as $amount) {
            try {
                Money::fromDecimalString($amount, 'EUR');
                self::fail($amount . ' must be rejected');
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString($amount, $exception->getMessage());
            }
        }
    }

    #[Test]
    public function rejectsTheSmallestIntegerAtParseTime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromDecimalString((string) PHP_INT_MIN, 'JPY');
    }
}
