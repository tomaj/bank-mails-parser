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

        self::assertSame('failed', $exception->getMessage());
        self::assertSame(7, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function unsupportedBankExceptionWithoutSender(): void
    {
        $exception = UnsupportedBankException::noParserFound();

        self::assertStringContainsString('No parser found', $exception->getMessage());
        self::assertSame(
            UnsupportedBankException::noParserFound()->getMessage(),
            UnsupportedBankException::withSender(null)->getMessage(),
        );
    }

    #[Test]
    public function unsupportedBankExceptionWithSender(): void
    {
        $exception = UnsupportedBankException::withSender('bank@example.test');

        self::assertSame('No parser found for the supplied sender.', $exception->getMessage());
        self::assertStringNotContainsString('bank@example.test', $exception->getMessage());
    }

    #[Test]
    public function invalidEmailFormatFactories(): void
    {
        $missing = InvalidEmailFormatException::missingRequiredField('Parser', 'RES');
        $malformed = InvalidEmailFormatException::malformedContent('Parser', 'broken');

        self::assertStringContainsString('RES', $missing->getMessage());
        self::assertStringContainsString('Parser', $missing->getMessage());
        self::assertSame(0, $missing->getCode());
        self::assertStringContainsString('broken', $malformed->getMessage());
        self::assertSame(0, $malformed->getCode());
    }
}
