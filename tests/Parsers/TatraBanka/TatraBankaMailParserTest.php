<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parsers\TatraBanka;

use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
use PHPUnit\Framework\TestCase;

class TatraBankaMailParserTest extends TestCase
{
    public function testSimpleEmail(): void
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

        self::assertNotNull($mailContent);
        self::assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        self::assertEquals('1100/000000-261426464', $mailContent->getSourceAccountNumber());
        self::assertEquals('EUR', $mailContent->getCurrency());
        self::assertEquals(12.31, $mailContent->getAmount());
        self::assertEquals('1234056789', $mailContent->getVs());
        self::assertEquals('test-sprava', $mailContent->getReceiverMessage());
        self::assertEquals('CCINT 1100/000000-261426464', $mailContent->getDescription());
        self::assertNull($mailContent->getKs());
        self::assertNull($mailContent->getSs());
        self::assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testSimpleEmailWithoutSourceAccountNumberPrefix(): void
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

        self::assertNotNull($mailContent);
        self::assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        self::assertEquals('000000-261426464', $mailContent->getSourceAccountNumber());
        self::assertEquals('EUR', $mailContent->getCurrency());
        self::assertEquals(12.31, $mailContent->getAmount());
        self::assertEquals('1234056789', $mailContent->getVs());
        self::assertEquals('test-sprava', $mailContent->getReceiverMessage());
        self::assertEquals('CCINT 000000-261426464', $mailContent->getDescription());
        self::assertNull($mailContent->getKs());
        self::assertNull($mailContent->getSs());
        self::assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testEmailWithVsInReceiverMessage(): void
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

        self::assertNotNull($mailContent);
        self::assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        self::assertEquals('1100/000000-261426464', $mailContent->getSourceAccountNumber());
        self::assertEquals('EUR', $mailContent->getCurrency());
        self::assertEquals(12.31, $mailContent->getAmount());
        self::assertEquals('1234056789', $mailContent->getVs());
        self::assertEquals('1234056789', $mailContent->getReceiverMessage());
        self::assertEquals('CCINT 1100/000000-261426464', $mailContent->getDescription());
        self::assertNull($mailContent->getKs());
        self::assertNull($mailContent->getSs());
        self::assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testEmailWithVsInReceiversMessage(): void
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

        self::assertNotNull($mailContent);
        self::assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        self::assertEquals('1100/000000-261426464', $mailContent->getSourceAccountNumber());
        self::assertEquals('EUR', $mailContent->getCurrency());
        self::assertEquals(12.31, $mailContent->getAmount());
        self::assertEquals('1234056789', $mailContent->getVs());
        self::assertEquals('VS:1234056789', $mailContent->getReceiverMessage());
        self::assertEquals('CCINT 1100/000000-261426464', $mailContent->getDescription());
        self::assertNull($mailContent->getKs());
        self::assertNull($mailContent->getSs());
        self::assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testEmailWithVsInReceiversMessageSepaFormat(): void
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

        self::assertNotNull($mailContent);
        self::assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        self::assertEquals('1100/000000-261426464', $mailContent->getSourceAccountNumber());
        self::assertEquals('EUR', $mailContent->getCurrency());
        self::assertEquals(12.31, $mailContent->getAmount());
        self::assertEquals('1234056789', $mailContent->getVs());
        self::assertEquals(
            '(CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)',
            $mailContent->getReceiverMessage()
        );
        self::assertEquals('CCINT 1100/000000-261426464', $mailContent->getDescription());
        self::assertNull($mailContent->getKs());
        self::assertNull($mailContent->getSs());
        self::assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testSimpleEmailWithSs(): void
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

        self::assertNotNull($mailContent);
        self::assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        self::assertEquals('1100/000000-261426464', $mailContent->getSourceAccountNumber());
        self::assertEquals('EUR', $mailContent->getCurrency());
        self::assertEquals(12.31, $mailContent->getAmount());
        self::assertEquals('1234056789', $mailContent->getVs());
        self::assertEquals('1234', $mailContent->getSs());
        self::assertEquals('308', $mailContent->getKs());
        self::assertEquals('test-sprava', $mailContent->getReceiverMessage());
        self::assertEquals('CCINT 1100/000000-261426464', $mailContent->getDescription());
        self::assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testNotRecognised(): void
    {
        $email = 'Not TB email';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        self::assertNull($mailContent);
    }

    public function testSimpleEmailDecrease(): void
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

        self::assertNotNull($mailContent);
        self::assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        self::assertEquals('1100/000000-261426464', $mailContent->getSourceAccountNumber());
        self::assertEquals('EUR', $mailContent->getCurrency());
        self::assertEquals(-12.31, $mailContent->getAmount());
        self::assertEquals('1234056789', $mailContent->getVs());
        self::assertEquals('test-sprava', $mailContent->getReceiverMessage());
        self::assertEquals('CCINT 1100/000000-261426464', $mailContent->getDescription());
        self::assertNull($mailContent->getKs());
        self::assertNull($mailContent->getSs());
        self::assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testSimpleEmailDecreaseFromDifferentSourceAccount(): void
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

        self::assertNotNull($mailContent);
        self::assertEquals('SK9812353347235', $mailContent->getAccountNumber());
        self::assertNull($mailContent->getSourceAccountNumber());
        self::assertEquals('EUR', $mailContent->getCurrency());
        self::assertEquals(-12.31, $mailContent->getAmount());
        self::assertNull($mailContent->getVs());
        self::assertEquals('Informacia pre prijemcu', $mailContent->getReceiverMessage());
        self::assertEquals('UCUBER', $mailContent->getDescription());
        self::assertNull($mailContent->getKs());
        self::assertNull($mailContent->getSs());
        self::assertEquals(
            strtotime('16.1.2015 12:51'),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }
}
