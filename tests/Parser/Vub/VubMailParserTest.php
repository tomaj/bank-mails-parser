<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\Vub;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

#[CoversClass(VubMailParser::class)]
class VubMailParserTest extends TestCase
{
    #[Test]
    public function singleTransferPayment()
    {
        $contents = \Tomaj\BankMailsParser\Tests\Fixtures::load('vub/platba');

        $vubMailParser = new VubMailParser();
        $mailContent = $vubMailParser->parse($contents);

        $this->assertSame('SK4502000000001123100000', $mailContent->accountNumber);
        $this->assertNull($mailContent->sourceAccountNumber);
        $this->assertSame('SK5502000000001232860000', $mailContent->ownAccountNumber);
        $this->assertSame('34.90', $mailContent->money?->toDecimalString());
        $this->assertSame('EUR', $mailContent->money?->currency->value);
        $this->assertEquals('9911929700', $mailContent->vs);
        $this->assertEquals('0308', $mailContent->ks);
        $this->assertSame('910', $mailContent->ss);
    }

    #[Test]
    public function vubParserWithInvalidContent()
    {
        $parser = new VubMailParser();

        $result = $parser->tryParse('Invalid email content');
        $this->assertNull($result);
    }

    #[Test]
    public function vubParserWithEmptyContent()
    {
        $parser = new VubMailParser();

        $result = $parser->tryParse('');
        $this->assertNull($result);
    }

    #[Test]
    public function vubParserWithPartialContent()
    {
        $parser = new VubMailParser();

        // Test with only some fields present
        $content = 'Dtum: 11.12.2019
VS: 1234567890';

        $result = $parser->parse($content);
        $this->assertNotNull($result);
        $this->assertEquals('1234567890', $result->vs);
        $this->assertNull($result->money?->toDecimalString());
        $this->assertNull($result->accountNumber);
        $this->assertNull($result->ks);
    }

    #[Test]
    public function vubParserWithInvalidAmountFormat()
    {
        $parser = new VubMailParser();

        // Test with invalid amount format
        $content = 'Suma: invalid_amount
VS: 1234567890';

        $result = $parser->parse($content);
        $this->assertNull($result->money);
        $this->assertSame('1234567890', $result->vs);
    }

    #[Test]
    public function vubParserWithMultilineContent()
    {
        // The 'm' flag allows ^ and $ to match line boundaries
        $parser = new VubMailParser();

        $content = \Tomaj\BankMailsParser\Tests\Fixtures::load('vub/platba-123');

        $result = $parser->parse($content);
        $this->assertSame(
            new \DateTimeImmutable('2015-01-12', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(),
            $result->transactionDate?->getTimestamp(),
        );
        $this->assertSame('SK1234567890123456', $result->accountNumber);
        $this->assertNull($result->sourceAccountNumber);
        $this->assertNull($result->ownAccountNumber);
        $this->assertSame('123.45', $result->money?->toDecimalString());
        $this->assertEquals('9876543210', $result->vs);
        $this->assertEquals('0308', $result->ks);
    }

    #[Test]
    public function vubParserVerifyArrayIndices()
    {
        // Test to verify correct array indices are used ($result[1] not $result[0])
        $parser = new VubMailParser();

        $content = 'Dtum: 2015-01-12 extra
Z tu: SK1234567890123456 trailing
Suma: 100,50 EUR
VS: 1234567890 text
KS: 0308 more';

        $result = $parser->parse($content);
        $this->assertNull($result->transactionDate);
        $this->assertSame('SK1234567890123456', $result->accountNumber);
        $this->assertNull($result->sourceAccountNumber);
        $this->assertNull($result->ownAccountNumber);
        $this->assertSame('100.50', $result->money?->toDecimalString());
        $this->assertEquals('1234567890', $result->vs);
        $this->assertEquals('0308', $result->ks);
    }

    #[Test]
    public function exampleEnvelopePlainPartIsNotAPayment(): void
    {
        $eml = (string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/vub/priklad.eml');
        $this->assertSame(
            1,
            preg_match('/Content-Type: text\/plain;.*?Content-Transfer-Encoding: base64\s+([A-Za-z0-9+\/=\r\n]+)\r?\n----/s', $eml, $match),
        );
        $body = base64_decode(preg_replace('/\s+/', '', $match[1]) ?? '', true);
        $this->assertIsString($body);
        $this->assertNull(new VubMailParser()->tryParse($body));
    }
}
