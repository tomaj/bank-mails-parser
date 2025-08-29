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

        $this->assertEquals('SK4502000000001123100000', $mailContent->getAccountNumber());
        $this->assertEquals(34.90, $mailContent->getAmount());
        $this->assertEquals('9911929700', $mailContent->getVs());
        $this->assertEquals('0308', $mailContent->getKs());
        $this->assertNull($mailContent->getSs());
    }

    #[Test]
    public function vubParserWithInvalidContent()
    {
        $parser = new VubMailParser();
        
        // Test with completely invalid content
        $result = $parser->parse('Invalid email content');
        $this->assertNotNull($result);
        $this->assertNull($result->getAmount());
        $this->assertNull($result->getVs());
        $this->assertNull($result->getAccountNumber());
    }

    #[Test]
    public function vubParserWithEmptyContent()
    {
        $parser = new VubMailParser();
        
        // Test with empty content
        $result = $parser->parse('');
        $this->assertNotNull($result);
        $this->assertNull($result->getAmount());
        $this->assertNull($result->getVs());
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
        $this->assertEquals('1234567890', $result->getVs());
        $this->assertNull($result->getAmount());
        $this->assertNull($result->getAccountNumber());
        $this->assertNull($result->getKs());
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
        $this->assertEquals('1234567890', $result->getVs());
        $this->assertEquals(0.0, $result->getAmount()); // floatval of invalid string returns 0
    }
}
