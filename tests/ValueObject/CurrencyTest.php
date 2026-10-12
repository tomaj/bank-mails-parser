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

        $this->assertSame('EUR', $currency->value);
    }

    #[Test]
    public function createsFromNumericCode(): void
    {
        $this->assertSame(Currency::fromCode('EUR'), Currency::fromCode('978'));
        $this->assertSame(Currency::fromCode('CZK'), Currency::fromCode('203'));
        $this->assertSame(Currency::fromCode('USD'), Currency::fromCode('840'));
        $this->assertSame(Currency::fromCode('GBP'), Currency::fromCode('826'));
        $this->assertSame(Currency::fromCode('CHF'), Currency::fromCode('756'));
        $this->assertSame(Currency::fromCode('PLN'), Currency::fromCode('985'));
        $this->assertSame(Currency::fromCode('HUF'), Currency::fromCode('348'));
    }

    #[Test]
    public function createsFromAlphabeticCodeViaFromCode(): void
    {
        $this->assertSame(Currency::fromCode('EUR'), Currency::fromCode('eur'));
        $this->assertSame(Currency::fromCode('CZK'), Currency::fromCode(' CZK '));
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
        $this->assertNull(Currency::tryFromCode('INVALID'));
        $this->assertSame(Currency::fromCode('EUR'), Currency::tryFromCode('978'));
    }

    #[Test]
    public function supportsCommonCentralEuropeanCurrencies(): void
    {
        $this->assertSame('EUR', Currency::fromCode('EUR')->value);
        $this->assertSame('CZK', Currency::fromCode('CZK')->value);
        $this->assertSame('PLN', Currency::fromCode('PLN')->value);
        $this->assertSame('HUF', Currency::fromCode('HUF')->value);
    }

    #[Test]
    public function acceptsActiveIsoCodesAndKnownMinorUnits(): void
    {
        $this->assertSame('SEK', Currency::fromCode('SEK')->value);
        $this->assertSame('XXX', Currency::fromCode('xxx')->value);
        $this->assertSame(2, Currency::fromCode('SEK')->minorUnits());
        $this->assertSame(2, Currency::fromCode('XXX')->minorUnits());
        $this->assertSame(0, Currency::fromCode('JPY')->minorUnits());
        $this->assertSame(0, Currency::fromCode('ISK')->minorUnits());
        $this->assertSame(3, Currency::fromCode('BHD')->minorUnits());
        $this->assertSame(3, Currency::fromCode('KWD')->minorUnits());
        $this->assertSame(3, Currency::fromCode('OMR')->minorUnits());
        $this->assertSame(3, Currency::fromCode('TND')->minorUnits());
        $this->assertSame(4, Currency::fromCode('CLF')->minorUnits());
        $this->assertSame('JPY', Currency::fromCode('392')->value);
        $this->assertSame('SEK', Currency::fromCode('752')->value);
        $this->assertSame('NOK', Currency::fromCode('578')->value);
        $this->assertSame('DKK', Currency::fromCode('208')->value);
        $this->assertSame('AUD', Currency::fromCode('036')->value);
        $this->assertSame('AUD', Currency::fromCode('36')->value);
        $this->assertSame(180, count(\Tomaj\BankMailsParser\ValueObject\Iso4217::SCALE));
        $this->assertSame('BGN', Currency::fromCode('975')->value);
        $this->assertSame('BGN', Currency::fromCode('BGN')->value);
        $this->assertSame('HRK', Currency::fromCode('191')->value);
        $this->assertSame(2, Currency::fromCode('HRK')->minorUnits());
        $this->assertNull(Currency::tryFromCode('1'));
        $this->assertNull(Currency::tryFromCode('ZZZ'));
    }
}
