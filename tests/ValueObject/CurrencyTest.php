<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\ValueObject\Currency;

#[CoversClass(Currency::class)]
final class CurrencyTest extends TestCase
{
    #[Test]
    public function createsFromAlphabeticCode(): void
    {
        $currency = Currency::fromCode('EUR');

        self::assertSame('EUR', $currency->value);
    }

    #[Test]
    public function createsFromNumericCode(): void
    {
        self::assertSame(Currency::fromCode('EUR'), Currency::fromCode('978'));
        self::assertSame(Currency::fromCode('CZK'), Currency::fromCode('203'));
        self::assertSame(Currency::fromCode('USD'), Currency::fromCode('840'));
        self::assertSame(Currency::fromCode('GBP'), Currency::fromCode('826'));
        self::assertSame(Currency::fromCode('CHF'), Currency::fromCode('756'));
        self::assertSame(Currency::fromCode('PLN'), Currency::fromCode('985'));
        self::assertSame(Currency::fromCode('HUF'), Currency::fromCode('348'));
    }

    #[Test]
    public function createsFromAlphabeticCodeViaFromCode(): void
    {
        self::assertSame(Currency::fromCode('EUR'), Currency::fromCode('eur'));
        self::assertSame(Currency::fromCode('CZK'), Currency::fromCode(' CZK '));
    }

    #[Test]
    public function throwsExceptionForInvalidCode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Currency::fromCode('INVALID');
    }

    #[Test]
    public function returnsNullForInvalidCodeWithTryFromCode(): void
    {
        self::assertNull(Currency::tryFromCode('INVALID'));
        self::assertSame(Currency::fromCode('EUR'), Currency::tryFromCode('978'));
    }

    #[Test]
    public function supportsCommonCentralEuropeanCurrencies(): void
    {
        self::assertSame('EUR', Currency::fromCode('EUR')->value);
        self::assertSame('CZK', Currency::fromCode('CZK')->value);
        self::assertSame('PLN', Currency::fromCode('PLN')->value);
        self::assertSame('HUF', Currency::fromCode('HUF')->value);
    }

    #[Test]
    public function acceptsActiveIsoCodesAndKnownMinorUnits(): void
    {
        self::assertSame('SEK', Currency::fromCode('SEK')->value);
        self::assertSame('XXX', Currency::fromCode('xxx')->value);
        self::assertSame(2, Currency::fromCode('SEK')->minorUnits());
        self::assertSame(2, Currency::fromCode('XXX')->minorUnits());
        self::assertSame(0, Currency::fromCode('JPY')->minorUnits());
        self::assertSame(0, Currency::fromCode('ISK')->minorUnits());
        self::assertSame(3, Currency::fromCode('BHD')->minorUnits());
        self::assertSame(3, Currency::fromCode('KWD')->minorUnits());
        self::assertSame(3, Currency::fromCode('OMR')->minorUnits());
        self::assertSame(3, Currency::fromCode('TND')->minorUnits());
        self::assertSame(4, Currency::fromCode('CLF')->minorUnits());
        self::assertSame('JPY', Currency::fromCode('392')->value);
        self::assertSame('SEK', Currency::fromCode('752')->value);
        self::assertSame('NOK', Currency::fromCode('578')->value);
        self::assertSame('DKK', Currency::fromCode('208')->value);
        self::assertSame('AUD', Currency::fromCode('036')->value);
        self::assertSame('AUD', Currency::fromCode('36')->value);
        self::assertSame('BGN', Currency::fromCode('975')->value);
        self::assertSame('BGN', Currency::fromCode('BGN')->value);
        self::assertSame('HRK', Currency::fromCode('191')->value);
        self::assertSame(2, Currency::fromCode('HRK')->minorUnits());
        self::assertNull(Currency::tryFromCode('1'));
        self::assertNull(Currency::tryFromCode('ZZZ'));
    }
}
