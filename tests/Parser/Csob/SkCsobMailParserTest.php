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
        $this->assertEquals('SK91 7500 0000 0030 2072 9058', $mailContent->getAccountNumber());
        $this->assertEquals('EUR', $mailContent->getCurrency());
        $this->assertEquals(1150.00, $mailContent->getAmount());
        $this->assertEquals('212049000', $mailContent->getVs());
        $this->assertEquals('0308', $mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('6.9.2019'), $mailContent->getTransactionDate());
    }
}
