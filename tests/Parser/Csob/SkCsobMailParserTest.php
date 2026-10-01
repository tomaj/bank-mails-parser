<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\Csob;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;

#[CoversClass(SkCsobMailParser::class)]
class SkCsobMailParserTest extends TestCase
{
    #[Test]
    public function singleTransferPayment()
    {
        $email = 'Vážená klientka, vážený klient,

dovoľujeme si Vám oznámiť, že dňa 6.9.2019 bola na účte SK13 7500 0000 0040 1942 5381 PETIT PRESS, A.S. zaúčtovaná suma SEPA platobného príkazu:
suma:                    +1150,00 EUR
z účtu:                  SK91 7500 0000 0030 2072 9058
banka:                   CEKOSKBX
detaily platby:
názov protiúčtu:         NOVAK JOZEF
referencia platiteľa:    /VS212049000/SS964/KS0308
informácia pre príjemcu: Maroko 2019 rodicia



Zostatok na účte po zaúčtovaní sumy platobnej operácie: +14050,70 EUR

Tento e-mail je generovaný automaticky, prosíme, neodpovedajte naň.
Ak máte otázky alebo problémy súvisiace so službami Elektronického bankovníctva kontaktujte nás prosím na e-mail adrese helpdeskeb@csob.sk

Ďakujeme za využitie služieb ČSOB Info 24,

ČSOB.';

        $skCsobMailParser = new SkCsobMailParser();
        $mailContents = $skCsobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('SK91 7500 0000 0030 2072 9058', $mailContent->accountNumber);
        $this->assertEquals('EUR', $mailContent->currency);
        $this->assertEquals(1150.00, $mailContent->amount);
        $this->assertEquals('212049000', $mailContent->vs);
        $this->assertEquals('0308', $mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('6.9.2019'), $mailContent->transactionDate);
    }

    #[Test]
    public function skCsobParserMultiFiltersNullResults()
    {
        // Test parseMulti filters out null results (kills UnwrapArrayFilter mutant line 19)
        $email = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: +100,00 EUR

dňa invalid text that does not match pattern

dňa 7.9.2019 bola na účte SK92 7500 0000 0030 2072 9059 zaúčtovaná suma SEPA platobného príkazu:
suma: +200,00 EUR';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        // Should return 2 valid transactions, filtering out the null
        $this->assertCount(2, $result);
        $result = array_values($result);
        $this->assertEquals(100.0, $result[0]->amount);
        $this->assertEquals(200.0, $result[1]->amount);
    }

    #[Test]
    public function skCsobParserWithMultilineAndUnicode()
    {
        // Tests multiline patterns (kills PregMatchRemoveFlags for /m flag on line 28)
        // and UTF-8 handling (kills /u flag removal)
        $email = 'Prvý riadok textu
dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma:                    +1150,50 EUR
názov protiúčtu:         NOVÁČEK Ján č. účtu
referencia platiteľa:    /VS212049000/KS0308
informácia pre príjemcu: Platba za služby č. ščřžýáíéúô
Ďalší riadok s diakritikou';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $mailContent = $result[0];
        $this->assertEquals(1150.50, $mailContent->amount);
        $this->assertEquals('EUR', $mailContent->currency);
        $this->assertEquals('212049000', $mailContent->vs);
        $this->assertEquals('0308', $mailContent->ks);
        $this->assertEquals('Platba za služby č. ščřžýáíéúô', $mailContent->receiverMessage);
    }

    #[Test]
    public function skCsobParserAmountSignDetection()
    {
        // Tests array index $result[1] for sign detection (kills Increment/DecrementInteger line 40)
        $email = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: -500,25 EUR
referencia platiteľa: /VS111111';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $this->assertEquals(-500.25, $result[0]->amount);
        $this->assertEquals('EUR', $result[0]->currency);
    }

    #[Test]
    public function skCsobParserAccountNumberExtraction()
    {
        // Tests line 61 PregMatchRemoveFlags for /m flag
        // Note: SkCsobMailParser extracts to accountNumber, not sourceAccountNumber
        $email = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: +100,00 EUR
Text před
z účtu: SK12 3456 7890 1234 5678 9012
referencia platiteľa: VS111111/KS0308';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        // accountNumber is extracted with spaces from "z účtu:"
        $this->assertEquals('SK12 3456 7890 1234 5678 9012', $result[0]->accountNumber);
    }

    #[Test]
    public function skCsobParserVsExtraction()
    {
        // Tests VS extraction pattern (line 51) with PregMatchRemoveFlags
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
        // Tests correct array index for receiver message (kills DecrementInteger line 47)
        $email = 'dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:
suma: +100,00 EUR
informácia pre príjemcu: Expected message content here
referencia platiteľa: /VS111111';

        $parser = new SkCsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertEquals('Expected message content here', $result[0]->receiverMessage);
    }
}
