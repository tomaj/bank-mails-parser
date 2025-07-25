<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parsers\TatraBanka;

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
use PHPUnit\Framework\TestCase;

class TatraBankaMailParserTest extends TestCase
{
    public function testSimpleEmail()
    {
        $email = 'Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS/KS
Informacia pre prijemcu: test-sprava

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, ' .
            'prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom ' .
            'tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        $this->assertEquals('1100/000000-261426464', $mailContent->getSourceAccountNumber());
        $this->assertEquals('EUR', $mailContent->getCurrency());
        $this->assertEquals(12.31, $mailContent->getAmount());
        $this->assertEquals('1234056789', $mailContent->getVs());
        $this->assertEquals('test-sprava', $mailContent->getReceiverMessage());
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->getDescription());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testSimpleEmailWithoutSourceAccountNumberPrefix()
    {
        $email = 'Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 000000-261426464
Referencia platitela: /VS1234056789/SS/KS
Informacia pre prijemcu: test-sprava

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, ' .
            'prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom ' .
            'tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        $this->assertEquals('000000-261426464', $mailContent->getSourceAccountNumber());
        $this->assertEquals('EUR', $mailContent->getCurrency());
        $this->assertEquals(12.31, $mailContent->getAmount());
        $this->assertEquals('1234056789', $mailContent->getVs());
        $this->assertEquals('test-sprava', $mailContent->getReceiverMessage());
        $this->assertEquals('CCINT 000000-261426464', $mailContent->getDescription());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testEmailWithVsInReceiverMessage()
    {
        $email = 'Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS/SS/KS
Informacia pre prijemcu: 1234056789

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, ' .
            'prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom ' .
            'tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        $this->assertEquals('1100/000000-261426464', $mailContent->getSourceAccountNumber());
        $this->assertEquals('EUR', $mailContent->getCurrency());
        $this->assertEquals(12.31, $mailContent->getAmount());
        $this->assertEquals('1234056789', $mailContent->getVs());
        $this->assertEquals('1234056789', $mailContent->getReceiverMessage());
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->getDescription());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testEmailWithVsInReceiversMessage()
    {
        $email = 'Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS/SS/KS
Informacia pre prijemcu: VS:1234056789

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, ' .
            'prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom ' .
            'tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        $this->assertEquals('1100/000000-261426464', $mailContent->getSourceAccountNumber());
        $this->assertEquals('EUR', $mailContent->getCurrency());
        $this->assertEquals(12.31, $mailContent->getAmount());
        $this->assertEquals('1234056789', $mailContent->getVs());
        $this->assertEquals('VS:1234056789', $mailContent->getReceiverMessage());
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->getDescription());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testEmailWithVsInReceiversMessageSepaFormat()
    {
        $email = 'Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS/SS/KS
Informacia pre prijemcu: (CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, ' .
            'prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom ' .
            'tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        $this->assertEquals('1100/000000-261426464', $mailContent->getSourceAccountNumber());
        $this->assertEquals('EUR', $mailContent->getCurrency());
        $this->assertEquals(12.31, $mailContent->getAmount());
        $this->assertEquals('1234056789', $mailContent->getVs());
        $this->assertEquals(
            '(CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)',
            $mailContent->getReceiverMessage()
        );
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->getDescription());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testSimpleEmailWithSs()
    {
        $email = 'Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS1234/KS308
Informacia pre prijemcu: test-sprava

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, ' .
            'prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom ' .
            'tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        $this->assertEquals('1100/000000-261426464', $mailContent->getSourceAccountNumber());
        $this->assertEquals('EUR', $mailContent->getCurrency());
        $this->assertEquals(12.31, $mailContent->getAmount());
        $this->assertEquals('1234056789', $mailContent->getVs());
        $this->assertEquals('1234', $mailContent->getSs());
        $this->assertEquals('308', $mailContent->getKs());
        $this->assertEquals('test-sprava', $mailContent->getReceiverMessage());
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->getDescription());
        $this->assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testNotRecognised()
    {
        $email = 'Not TB email';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertNull($mailContent);
    }

    public function testSimpleEmailDecrease()
    {
        $email = 'Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 znizeny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS/KS
Informacia pre prijemcu: test-sprava

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, ' .
            'prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom ' .
            'tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        $this->assertEquals('1100/000000-261426464', $mailContent->getSourceAccountNumber());
        $this->assertEquals('EUR', $mailContent->getCurrency());
        $this->assertEquals(-12.31, $mailContent->getAmount());
        $this->assertEquals('1234056789', $mailContent->getVs());
        $this->assertEquals('test-sprava', $mailContent->getReceiverMessage());
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->getDescription());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testSimpleEmailDecreaseFromDifferentSourceAccount()
    {
        $email = 'Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 znizeny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: UCUBER
Uctovny kod uctu: Informacia pre prijemcu

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, ' .
            'prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom ' .
            'tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        $this->assertNull($mailContent->getSourceAccountNumber());
        $this->assertEquals('EUR', $mailContent->getCurrency());
        $this->assertEquals(-12.31, $mailContent->getAmount());
        $this->assertNull($mailContent->getVs());
        $this->assertEquals('Informacia pre prijemcu', $mailContent->getReceiverMessage());
        $this->assertEquals('UCUBER', $mailContent->getDescription());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }
}
