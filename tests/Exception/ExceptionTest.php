<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\ParseException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;

#[CoversClass(ParseException::class)]
#[CoversClass(UnsupportedBankException::class)]
#[CoversClass(InvalidEmailFormatException::class)]
final class ExceptionTest extends TestCase
{
    #[Test]
    public function parseExceptionIsRuntimeException(): void
    {
        $previous = new RuntimeException('earlier');
        $exception = new ParseException('failed', 7, $previous);

        $this->assertSame('failed', $exception->getMessage());
        $this->assertSame(7, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function unsupportedBankExceptionWithoutSender(): void
    {
        $exception = UnsupportedBankException::noParserFound();

        $this->assertInstanceOf(ParseException::class, $exception);
        $this->assertStringContainsString('No parser found', $exception->getMessage());
        $this->assertSame(
            UnsupportedBankException::noParserFound()->getMessage(),
            UnsupportedBankException::withSender(null)->getMessage(),
        );
    }

    #[Test]
    public function unsupportedBankExceptionWithSender(): void
    {
        $exception = UnsupportedBankException::withSender('bank@example.test');

        $this->assertSame('No parser found for the supplied sender.', $exception->getMessage());
        $this->assertStringNotContainsString('bank@example.test', $exception->getMessage());
    }

    #[Test]
    public function invalidEmailFormatFactories(): void
    {
        $missing = InvalidEmailFormatException::missingRequiredField('Parser', 'RES');
        $malformed = InvalidEmailFormatException::malformedContent('Parser', 'broken');

        $this->assertStringContainsString('RES', $missing->getMessage());
        $this->assertStringContainsString('Parser', $missing->getMessage());
        $this->assertSame(0, $missing->getCode());
        $this->assertStringContainsString('broken', $malformed->getMessage());
        $this->assertSame(0, $malformed->getCode());
        $this->assertInstanceOf(ParseException::class, $malformed);
    }
}
