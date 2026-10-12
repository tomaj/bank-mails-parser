<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\Csob;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;

#[CoversClass(SkCsobMailParser::class)]
class SkCsobMailParserTest extends TestCase
{
    #[Test]
    public function singleTransferPayment()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-sk/sepa-platba');

        $skCsobMailParser = new SkCsobMailParser();
        $mailContents = $skCsobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertSame('SK91 7500 0000 0030 2072 9058', $mailContent->accountNumber);
        $this->assertNull($mailContent->sourceAccountNumber);
        $this->assertSame('SK13 7500 0000 0040 1942 5381', $mailContent->ownAccountNumber);
        $this->assertEquals('EUR', $mailContent->money?->currency->value);
        $this->assertSame('1150.00', $mailContent->money?->toDecimalString());
        $this->assertEquals('212049000', $mailContent->vs);
        $this->assertEquals('0308', $mailContent->ks);
        $this->assertSame('964', $mailContent->ss);
        $this->assertEquals(
            new \DateTimeImmutable('2019-09-06', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(),
            $mailContent->transactionDate?->getTimestamp(),
        );
    }

    #[Test]
    public function skCsobParserMultiFiltersNullResults()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-sk/viac-transakcii');

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        // Should return 2 valid transactions, filtering out the null
        $this->assertCount(2, $result);
        $result = array_values($result);
        $this->assertSame('100.00', $result[0]->money?->toDecimalString());
        $this->assertSame('200.00', $result[1]->money?->toDecimalString());
    }

    #[Test]
    public function skCsobParserWithMultilineAndUnicode()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-sk/sepa-platba-diakritika');

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $mailContent = $result[0];
        $this->assertSame('1150.50', $mailContent->money?->toDecimalString());
        $this->assertEquals('EUR', $mailContent->money?->currency->value);
        $this->assertEquals('212049000', $mailContent->vs);
        $this->assertEquals('0308', $mailContent->ks);
        $this->assertEquals('Platba za služby č. ščřžýáíéúô', $mailContent->receiverMessage);
    }

    #[Test]
    public function skCsobParserAmountSignDetection()
    {
        $email = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: -500,25 EUR
referencia platiteľa: /VS111111';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $this->assertSame('-500.25', $result[0]->money?->toDecimalString());
        $this->assertEquals('EUR', $result[0]->money?->currency->value);
    }

    #[Test]
    public function skCsobParserAccountNumberExtraction()
    {
        $email = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: +100,00 EUR
Text před
z účtu: SK12 3456 7890 1234 5678 9012
referencia platiteľa: VS111111/KS0308';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $this->assertSame('SK12 3456 7890 1234 5678 9012', $result[0]->accountNumber);
        $this->assertNull($result[0]->sourceAccountNumber);
        $this->assertSame('SK91 7500 0000 0030 2072 9058', $result[0]->ownAccountNumber);
    }

    #[Test]
    public function skCsobParserVsExtraction()
    {
        $email = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: +100,00 EUR
referencia platiteľa: VS9876543210/KS0308';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);
        $this->assertEquals('9876543210', $result[0]->vs);
        $this->assertEquals('0308', $result[0]->ks);
    }

    #[Test]
    public function skCsobParserReceiverMessageIndex()
    {
        $email = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: +100,00 EUR
informácia pre príjemcu: Expected message content here
referencia platiteľa: /VS111111';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertEquals('Expected message content here', $result[0]->receiverMessage);
    }

    #[Test]
    public function parseAndParseAllRejectContentWithoutATransaction(): void
    {
        $parser = new SkCsobMailParser();

        try {
            $parser->parse('no transaction here');
            $this->fail('parse() must reject content without a transaction');
        } catch (InvalidEmailFormatException $exception) {
            $this->assertStringContainsString('Could not find transaction pattern', $exception->getMessage());
        }

        try {
            $parser->parseAll('no transaction here');
            $this->fail('parseAll() must reject content without a transaction');
        } catch (InvalidEmailFormatException $exception) {
            $this->assertStringContainsString('Could not find transaction pattern', $exception->getMessage());
        }
    }
}
