<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\Vub;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VubMailParser::class)]
class VubMailParserTest extends TestCase
{
    #[Test]
    public function singleTransferPayment()
    {
        $contents = 'Dtum:   11.12.2019
Na et: SK5502000000001232860000
Suma:    34,90
Z tu:  SK4502000000001123100000
VS:      9911929700
S:      910
KS:      0308
Stav:    zrealizovan
SIGN:    5CB8A45E42FEB48539E672B9F8E1B3F8E62F97FABDBCF880D0913B5A0C8431CE
        ';

        $vubMailParser = new VubMailParser();
        $mailContent = $vubMailParser->parse($contents);

        $this->assertEquals('SK4502000000001123100000', $mailContent->accountNumber);
        $this->assertEquals(34.90, $mailContent->amount);
        $this->assertEquals('9911929700', $mailContent->vs);
        $this->assertEquals('0308', $mailContent->ks);
        $this->assertNull($mailContent->ss);
    }

    #[Test]
    public function vubParserWithInvalidContent()
    {
        $parser = new VubMailParser();

        // Test with completely invalid content
        $result = $parser->parse('Invalid email content');
        $this->assertNotNull($result);
        $this->assertNull($result->amount);
        $this->assertNull($result->vs);
        $this->assertNull($result->accountNumber);
    }

    #[Test]
    public function vubParserWithEmptyContent()
    {
        $parser = new VubMailParser();

        // Test with empty content
        $result = $parser->parse('');
        $this->assertNotNull($result);
        $this->assertNull($result->amount);
        $this->assertNull($result->vs);
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
        $this->assertNull($result->amount);
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
        $this->assertNotNull($result);
        $this->assertEquals('1234567890', $result->vs);
        $this->assertEquals(0.0, $result->amount); // floatval of invalid string returns 0
    }

    #[Test]
    public function vubParserWithMultilineContent()
    {
        // Test multiline patterns to kill PregMatchRemoveFlags mutants
        // The 'm' flag allows ^ and $ to match line boundaries
        $parser = new VubMailParser();

        $content = 'Some header text
Dtum: 2015-01-12
More text here
Z tu: SK1234567890123456
Other information
Suma: 123,45
Additional data
VS: 9876543210
Even more text
KS: 0308
Footer text';

        $result = $parser->parse($content);
        $this->assertEquals(strtotime('2015-01-12'), $result->transactionDate);
        $this->assertEquals('SK1234567890123456', $result->accountNumber);
        $this->assertEquals(123.45, $result->amount);
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
        $this->assertEquals(strtotime('2015-01-12 extra'), $result->transactionDate);
        $this->assertEquals('SK1234567890123456', $result->accountNumber);
        $this->assertEquals(100.5, $result->amount);
        $this->assertEquals('1234567890', $result->vs);
        $this->assertEquals('0308', $result->ks);
    }
}
