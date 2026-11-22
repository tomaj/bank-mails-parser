<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\Csob;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;
use Tomaj\BankMailsParser\Tests\Fixtures;

#[CoversClass(SkCsobMailParser::class)]
class SkCsobMailParserTest extends TestCase
{
    #[Test]
    public function singleTransferPayment(): void
    {
        $email = Fixtures::load('csob-sk/sepa-platba');

        $skCsobMailParser = new SkCsobMailParser();
        $mailContents = $skCsobMailParser->parseMulti($email);

        self::assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        self::assertSame('SK91 7500 0000 0030 2072 9058', $mailContent->accountNumber);
        self::assertNull($mailContent->sourceAccountNumber);
        self::assertSame('SK13 7500 0000 0040 1942 5381', $mailContent->ownAccountNumber);
        self::assertSame('EUR', $mailContent->money?->currency->value);
        self::assertSame('1150.00', $mailContent->money->toDecimalString());
        self::assertSame('212049000', $mailContent->vs);
        self::assertSame('0308', $mailContent->ks);
        self::assertSame('964', $mailContent->ss);
        self::assertSame(
            new \DateTimeImmutable('2019-09-06', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(),
            $mailContent->transactionDate?->getTimestamp(),
        );
    }

    #[Test]
    public function skCsobParserMultiFiltersNullResults(): void
    {
        $email = Fixtures::load('csob-sk/viac-transakcii');

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        // Should return 2 valid transactions, filtering out the null
        self::assertCount(2, $result);
        self::assertSame('100.00', $result[0]->money?->toDecimalString());
        self::assertSame('200.00', $result[1]->money?->toDecimalString());
    }

    #[Test]
    public function skCsobParserWithMultilineAndUnicode(): void
    {
        $email = Fixtures::load('csob-sk/sepa-platba-diakritika');

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        self::assertCount(1, $result);
        $mailContent = $result[0];
        self::assertSame('1150.50', $mailContent->money?->toDecimalString());
        self::assertSame('EUR', $mailContent->money->currency->value);
        self::assertSame('212049000', $mailContent->vs);
        self::assertSame('0308', $mailContent->ks);
        self::assertSame('Platba za služby č. ščřžýáíéúô', $mailContent->receiverMessage);
    }

    #[Test]
    public function skCsobParserAmountSignDetection(): void
    {
        $email = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: -500,25 EUR
referencia platiteľa: /VS111111';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        self::assertCount(1, $result);
        self::assertSame('-500.25', $result[0]->money?->toDecimalString());
        self::assertSame('EUR', $result[0]->money->currency->value);
    }

    #[Test]
    public function skCsobParserAccountNumberExtraction(): void
    {
        $email = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: +100,00 EUR
Text před
z účtu: SK12 3456 7890 1234 5678 9012
referencia platiteľa: VS111111/KS0308';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        self::assertCount(1, $result);
        self::assertSame('SK12 3456 7890 1234 5678 9012', $result[0]->accountNumber);
        self::assertNull($result[0]->sourceAccountNumber);
        self::assertSame('SK91 7500 0000 0030 2072 9058', $result[0]->ownAccountNumber);
    }

    #[Test]
    public function skCsobParserVsExtraction(): void
    {
        $email = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: +100,00 EUR
referencia platiteľa: VS9876543210/KS0308';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);
        self::assertSame('9876543210', $result[0]->vs);
        self::assertSame('0308', $result[0]->ks);
    }

    #[Test]
    public function skCsobParserReceiverMessageIndex(): void
    {
        $email = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: +100,00 EUR
informácia pre príjemcu: Expected message content here
referencia platiteľa: /VS111111';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        self::assertSame('Expected message content here', $result[0]->receiverMessage);
    }

    #[Test]
    public function parseAndParseAllRejectContentWithoutATransaction(): void
    {
        $parser = new SkCsobMailParser();

        try {
            $parser->parse('no transaction here');
            self::fail('parse() must reject content without a transaction');
        } catch (InvalidEmailFormatException $exception) {
            self::assertStringContainsString('Could not find transaction pattern', $exception->getMessage());
        }

        try {
            $parser->parseAll('no transaction here');
            self::fail('parseAll() must reject content without a transaction');
        } catch (InvalidEmailFormatException $exception) {
            self::assertStringContainsString('Could not find transaction pattern', $exception->getMessage());
        }
    }
}
