<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\Vub;

use PHPUnit\Framework\TestCase;

class VubMailParserTest extends TestCase
{
    public function testSingleTransferPayment()
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
}