<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\TatraBanka;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;

#[CoversClass(TatraBankaStatementMailParser::class)]
class TatraBankaStatementMailParserTest extends TestCase
{
    #[Test]
    public function transferPayments(): void
    {
        $email = (string) file_get_contents(__DIR__ . '/../../fixtures/tatrabanka-statement/zasifrovany-vypis.txt');

        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc',
                'heslo',
            ),
        );
        $mailContents = $parser->parseMulti($email);

        self::assertCount(4, $mailContents);

        $mailContent = $mailContents[0];
        self::assertSame('2621234415', $mailContent->accountNumber);
        self::assertSame('13.37', $mailContent->money?->toDecimalString());
        self::assertSame('4169344603', $mailContent->vs);
        self::assertSame(new \DateTimeImmutable('2019-03-15 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());

        $mailContent = $mailContents[1];
        self::assertSame('2621234415', $mailContent->accountNumber);
        self::assertSame('15.00', $mailContent->money?->toDecimalString());
        self::assertSame('9051230034', $mailContent->vs);
        self::assertSame(new \DateTimeImmutable('2019-03-15 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());

        $mailContent = $mailContents[2];
        self::assertSame('2946123663', $mailContent->accountNumber);
        self::assertSame('59.90', $mailContent->money?->toDecimalString());
        self::assertSame('9101235209', $mailContent->vs);
        self::assertSame(new \DateTimeImmutable('2019-03-15 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());

        $mailContent = $mailContents[3];
        self::assertSame('2912329663', $mailContent->accountNumber);
        self::assertSame('34.90', $mailContent->money?->toDecimalString());
        self::assertSame('9101955247', $mailContent->vs);
        self::assertSame(new \DateTimeImmutable('2019-03-15 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function parseMultiReturnsNullForContentWithoutPGPMessage(): void
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc',
                'heslo',
            ),
        );

        $this->expectException(InvalidEmailFormatException::class);

        $parser->parseMulti('Email without PGP message block');
    }

    #[Test]
    public function parseReturnsNullForEmptyTransaction(): void
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc',
                'heslo',
            ),
        );

        $result = $parser->tryParse('');
        self::assertNull($result);
    }

    #[Test]
    public function parseReturnsNullForTransactionWithAllZeros(): void
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc',
                'heslo',
            ),
        );

        $result = $parser->tryParse('0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0');
        self::assertNull($result);
    }

    #[Test]
    public function parseHandlesValidTransaction(): void
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc',
                'heslo',
            ),
        );

        $transaction = '20190315|1|2|3|4|5|6|7|8|100.50|EUR|11|12|13|14|15|16|17|1234567890|SK1234567890|20';
        $result = $parser->parse($transaction);

        self::assertSame('100.50', $result->money?->toDecimalString());
        self::assertSame('EUR', $result->money->currency->value);
        self::assertSame('1234567890', $result->vs);
        self::assertSame('SK1234567890', $result->accountNumber);
    }

    #[Test]
    public function encryptedMailWithoutAClosingArmorFails(): void
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc',
                'heslo',
            ),
        );

        $this->expectException(InvalidEmailFormatException::class);
        $parser->parse("-----BEGIN PGP MESSAGE-----\nnot a complete block");
    }
}
