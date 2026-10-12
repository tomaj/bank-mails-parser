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
    public function transferPayments()
    {
        $email = file_get_contents(__DIR__ . '/../../fixtures/tatrabanka-statement/zasifrovany-vypis.txt');

        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc',
                'heslo',
            ),
        );
        $mailContents = $parser->parseMulti($email);

        $this->assertCount(4, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('2621234415', $mailContent->accountNumber);
        $this->assertSame('13.37', $mailContent->money?->toDecimalString());
        $this->assertEquals('4169344603', $mailContent->vs);
        $this->assertSame(new \DateTimeImmutable('2019-03-15 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());

        $mailContent = $mailContents[1];
        $this->assertEquals('2621234415', $mailContent->accountNumber);
        $this->assertSame('15.00', $mailContent->money?->toDecimalString());
        $this->assertEquals('9051230034', $mailContent->vs);
        $this->assertSame(new \DateTimeImmutable('2019-03-15 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());

        $mailContent = $mailContents[2];
        $this->assertEquals('2946123663', $mailContent->accountNumber);
        $this->assertSame('59.90', $mailContent->money?->toDecimalString());
        $this->assertEquals('9101235209', $mailContent->vs);
        $this->assertSame(new \DateTimeImmutable('2019-03-15 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());

        $mailContent = $mailContents[3];
        $this->assertEquals('2912329663', $mailContent->accountNumber);
        $this->assertSame('34.90', $mailContent->money?->toDecimalString());
        $this->assertEquals('9101955247', $mailContent->vs);
        $this->assertSame(new \DateTimeImmutable('2019-03-15 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function parseMultiReturnsNullForContentWithoutPGPMessage()
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc',
                'heslo',
            ),
        );

        $this->expectException(\Tomaj\BankMailsParser\Exception\InvalidEmailFormatException::class);

        $parser->parseMulti('Email without PGP message block');
    }

    #[Test]
    public function parseReturnsNullForEmptyTransaction()
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc',
                'heslo',
            ),
        );

        $result = $parser->tryParse('');
        $this->assertNull($result);
    }

    #[Test]
    public function parseReturnsNullForTransactionWithAllZeros()
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc',
                'heslo',
            ),
        );

        $result = $parser->tryParse('0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0');
        $this->assertNull($result);
    }

    #[Test]
    public function parseHandlesValidTransaction()
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc',
                'heslo',
            ),
        );

        $transaction = '20190315|1|2|3|4|5|6|7|8|100.50|EUR|11|12|13|14|15|16|17|1234567890|SK1234567890|20';
        $result = $parser->parse($transaction);

        $this->assertNotNull($result);
        $this->assertSame('100.50', $result->money?->toDecimalString());
        $this->assertEquals('EUR', $result->money?->currency->value);
        $this->assertEquals('1234567890', $result->vs);
        $this->assertEquals('SK1234567890', $result->accountNumber);
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
