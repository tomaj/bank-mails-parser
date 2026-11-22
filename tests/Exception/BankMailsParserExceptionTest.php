<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\AmbiguousBankException;
use Tomaj\BankMailsParser\Exception\BankMailsParserException;
use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\DecryptionException;
use Tomaj\BankMailsParser\Exception\InvalidArgumentException;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\MultipleTransactionsException;
use Tomaj\BankMailsParser\Exception\ParseException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

#[CoversClass(InvalidArgumentException::class)]
#[CoversClass(Money::class)]
#[CoversClass(Currency::class)]
final class BankMailsParserExceptionTest extends TestCase
{
    /**
     * @return array<string, array{class-string<\Throwable>, class-string<\Throwable>}>
     */
    public static function exceptionClasses(): array
    {
        return [
            'ParseException' => [ParseException::class, \RuntimeException::class],
            'InvalidEmailFormatException' => [InvalidEmailFormatException::class, ParseException::class],
            'UnsupportedBankException' => [UnsupportedBankException::class, ParseException::class],
            'AmbiguousBankException' => [AmbiguousBankException::class, ParseException::class],
            'MultipleTransactionsException' => [MultipleTransactionsException::class, ParseException::class],
            'DecryptionException' => [DecryptionException::class, ParseException::class],
            'ConfigurationException' => [ConfigurationException::class, \RuntimeException::class],
            'InvalidArgumentException' => [InvalidArgumentException::class, \InvalidArgumentException::class],
        ];
    }

    /**
     * @param class-string<\Throwable> $class
     * @param class-string<\Throwable> $parent
     */
    #[Test]
    #[DataProvider('exceptionClasses')]
    public function everyExceptionImplementsThePackageInterfaceAndKeepsItsParent(string $class, string $parent): void
    {
        self::assertTrue(is_a($class, BankMailsParserException::class, true));
        self::assertTrue(is_a($class, $parent, true));
    }

    #[Test]
    public function everyExceptionClassInTheNamespaceIsListed(): void
    {
        $files = glob(dirname(__DIR__, 2) . '/src/Exception/*.php');
        self::assertIsArray($files);

        $classes = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if ($name !== 'BankMailsParserException') {
                $classes[] = 'Tomaj\\BankMailsParser\\Exception\\' . $name;
            }
        }
        sort($classes);

        $listed = array_column(self::exceptionClasses(), 0);
        sort($listed);

        self::assertSame($listed, $classes);
    }

    #[Test]
    public function configurationExceptionIsNotAParseException(): void
    {
        // tryParse() catches ParseException; a configuration error must not be swallowed
        self::assertFalse(is_a(self::exceptionClasses()['ConfigurationException'][0], ParseException::class, true));
    }

    #[Test]
    public function moneyErrorsArePackageExceptions(): void
    {
        try {
            Money::fromDecimalString('12,3,4', 'EUR');
            self::fail('An ambiguous amount must be rejected');
        } catch (BankMailsParserException $exception) {
            self::assertInstanceOf(\InvalidArgumentException::class, $exception);
            self::assertSame('Invalid decimal amount "12,3,4".', $exception->getMessage());
        }

        try {
            Money::fromDecimalString('1.00', 'EUR', ';');
            self::fail('An unknown decimal separator must be rejected');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Decimal separator must be "." or ",".', $exception->getMessage());
        }
    }

    #[Test]
    public function currencyErrorsArePackageExceptions(): void
    {
        try {
            Currency::fromCode('XYZ');
            self::fail('An unknown currency must be rejected');
        } catch (BankMailsParserException $exception) {
            self::assertInstanceOf(\InvalidArgumentException::class, $exception);
            self::assertSame('Unsupported currency code "XYZ".', $exception->getMessage());
        }

        $payload = str_replace('s:3:"EUR"', 's:3:"XYZ"', serialize(Currency::fromCode('EUR')));
        try {
            unserialize($payload);
            self::fail('An unknown serialized currency must be rejected');
        } catch (\Throwable $exception) {
            self::assertInstanceOf(InvalidArgumentException::class, $exception);
            self::assertSame('Invalid currency payload.', $exception->getMessage());
        }
    }

    #[Test]
    public function oneCatchCoversParseAndConfigurationErrors(): void
    {
        $caught = [];
        foreach ([
            static fn(): mixed => new BankMailsParser()->parse('not a bank mail'),
            static fn(): mixed => new TatraBankaMailDecryptor('', 'x')->decrypt('x'),
        ] as $call) {
            try {
                $call();
            } catch (BankMailsParserException $exception) {
                $caught[] = $exception::class;
            }
        }

        self::assertSame([UnsupportedBankException::class, ConfigurationException::class], $caught);
    }
}
