<?php

declare(strict_types=1);

namespace Tests\Parses\TatraBanka;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;

#[CoversClass(TatraBankaMailParser::class)]
class TatraBankaMailParserTest extends TestCase
{
    #[Test]
    public function simpleEmail()
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

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->currency);
        $this->assertEquals(12.31, $mailContent->amount);
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertEquals('test-sprava', $mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('16.1.2015 12:51'), $mailContent->transactionDate);
    }

    #[Test]
    public function simpleEmailWithoutSourceAccountNumberPrefix()
    {
        $email = 'Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: 1100/000000-261426464
Referencia platitela: /VS1234056789/SS/KS
Informacia pre prijemcu: test-sprava

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->currency);
        $this->assertEquals(12.31, $mailContent->amount);
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertEquals('test-sprava', $mailContent->receiverMessage);
        $this->assertEquals('1100/000000-261426464', $mailContent->description);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('16.1.2015 12:51'), $mailContent->transactionDate);
    }

    #[Test]
    public function allInputsWithDecreaseEmail()
    {
        $email = 'Vazeny klient,

16.1.2015 12:11 bol zostatok Vasho uctu SK9812353347235 znizeny o 43,29 USD.
uctovny zostatok:                            22,11 EUR
aktualny zostatok:                           22,11 EUR
disponibilny zostatok:                       22,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS9087654321/KS5428175648
Informacia pre prijemcu: test-sprava-druha

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('USD', $mailContent->currency);
        $this->assertEquals(-43.29, $mailContent->amount);
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertEquals('9087654321', $mailContent->ss);
        $this->assertEquals('5428175648', $mailContent->ks);
        $this->assertEquals('test-sprava-druha', $mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertEquals(strtotime('16.1.2015 12:11'), $mailContent->transactionDate);
    }

    #[Test]
    public function emailWithoutReceiverMessage()
    {
        $email = 'Vazeny klient,

16.1.2015 12:11 bol zostatok Vasho uctu SK9812353347235 znizeny o 1 243,29 USD.
uctovny zostatok:                            100,00 EUR
aktualny zostatok:                           100,00 EUR
disponibilny zostatok:                       100,00 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS9087654321/KS5428175648

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('USD', $mailContent->currency);
        $this->assertEquals(-1243.29, $mailContent->amount);
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertEquals('9087654321', $mailContent->ss);
        $this->assertEquals('5428175648', $mailContent->ks);
        $this->assertNull($mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertEquals(strtotime('16.1.2015 12:11'), $mailContent->transactionDate);
    }

    #[Test]
    public function emailWithoutDescription()
    {
        $email = 'Vazeny klient,

16.1.2015 12:11 bol zostatok Vasho uctu SK9812353347235 znizeny o 1 243,29 USD.
uctovny zostatok:                            100,00 EUR
aktualny zostatok:                           100,00 EUR
disponibilny zostatok:                       100,00 EUR

Referencia platitela: /VS1234056789/SS9087654321/KS5428175648
Informacia pre prijemcu: test-sprava

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->accountNumber);
        $this->assertNull($mailContent->sourceAccountNumber);
        $this->assertEquals('USD', $mailContent->currency);
        $this->assertEquals(-1243.29, $mailContent->amount);
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertEquals('9087654321', $mailContent->ss);
        $this->assertEquals('5428175648', $mailContent->ks);
        $this->assertEquals('test-sprava', $mailContent->receiverMessage);
        $this->assertNull($mailContent->description);
        $this->assertEquals(strtotime('16.1.2015 12:11'), $mailContent->transactionDate);
    }

    #[Test]
    public function emailWithoutVariableSymbol()
    {
        $email = 'Vazeny klient,

12.1.2015 12:11 bol zostatok Vasho uctu SK9812369347235 znizeny o 2,20 EUR.
uctovny zostatok:                            32,52 EUR
aktualny zostatok:                           32,52 EUR
disponibilny zostatok:                       32,52 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS/SS9087654322/KS5428175649

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812369347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->currency);
        $this->assertEquals(-2.20, $mailContent->amount);
        $this->assertNull($mailContent->vs);
        $this->assertEquals('9087654322', $mailContent->ss);
        $this->assertEquals('5428175649', $mailContent->ks);
        $this->assertNull($mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertEquals(strtotime('12.1.2015 12:11'), $mailContent->transactionDate);
    }

    #[Test]
    public function emailWithVariableSymbolInReceiverMessage()
    {
        $email = 'Vazeny klient,

12.1.2015 12:11 bol zostatok Vasho uctu SK9812369347235 znizeny o 2,20 EUR.
uctovny zostatok:                            32,52 EUR
aktualny zostatok:                           32,52 EUR
disponibilny zostatok:                       32,52 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS/SS/KS
Informacia pre prijemcu: VS1234056789

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812369347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->currency);
        $this->assertEquals(-2.20, $mailContent->amount);
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertEquals('VS1234056789', $mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertEquals(strtotime('12.1.2015 12:11'), $mailContent->transactionDate);
    }

    // Referencia platitela: 1234056789
    #[Test]
    public function emailWithVariableSymbolInUniqueMandateReferenceWithoutPrefix()
    {
        $email = 'Vazeny klient,

12.1.2015 12:11 bol zostatok Vasho uctu SK9812369347235 znizeny o 2,20 EUR.
uctovny zostatok:                            32,52 EUR
aktualny zostatok:                           32,52 EUR
disponibilny zostatok:                       32,52 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: 1234056789

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812369347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->currency);
        $this->assertEquals(-2.20, $mailContent->amount);
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertEquals(strtotime('12.1.2015 12:11'), $mailContent->transactionDate);
    }

    // Informacia pre prijemcu: 1234056789
    public function testEmailWithVariableSymbolInReceiverMessageWithoutVSPrefix()
    {
        $email = 'Vazeny klient,

12.1.2015 12:11 bol zostatok Vasho uctu SK9812369347235 znizeny o 2,20 EUR.
uctovny zostatok:                            32,52 EUR
aktualny zostatok:                           32,52 EUR
disponibilny zostatok:                       32,52 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS/SS/KS
Informacia pre prijemcu: 1234056789

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812369347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->currency);
        $this->assertEquals(-2.20, $mailContent->amount);
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertEquals('1234056789', $mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertEquals(strtotime('12.1.2015 12:11'), $mailContent->transactionDate);
    }

    // Creditor Reference Information - SEPA XML format
    // Informacia pre prijemcu: (CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)
    #[Test]
    public function emailWithVariableSymbolInReceiverMessageCreditorReferenceInformation()
    {
        $email = 'Vazeny klient,

12.1.2015 12:11 bol zostatok Vasho uctu SK9812369347235 znizeny o 2,20 EUR.
uctovny zostatok:                            32,52 EUR
aktualny zostatok:                           32,52 EUR
disponibilny zostatok:                       32,52 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: Firstname Surname
Informacia pre prijemcu: (CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812369347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->currency);
        $this->assertEquals(-2.20, $mailContent->amount);
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertEquals('(CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)', $mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertEquals(strtotime('12.1.2015 12:11'), $mailContent->transactionDate);
    }

    // Transaction date with spaces (16. 1. 2015 12:51)
    #[Test]
    public function emailWithSecondaryTransactionDateFormat()
    {
        $email = 'Vazeny klient,

12. 1. 2015 12:11 bol zostatok Vasho uctu SK9812369347235 znizeny o 2,20 EUR.
uctovny zostatok:                            32,52 EUR
aktualny zostatok:                           32,52 EUR
disponibilny zostatok:                       32,52 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: Firstname Surname
Informacia pre prijemcu: (CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812369347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->currency);
        $this->assertEquals(-2.20, $mailContent->amount);
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertEquals('(CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)', $mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertEquals(strtotime('12.1.2015 12:11'), $mailContent->transactionDate);
    }

    #[Test]
    public function errorEmail()
    {
        $email = '4321/KS5428175648
Informacia pre prijemcu: test-sprava-druha

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertNull($mailContent);
    }

    #[Test]
    public function emailWithNegativeAmountFromZnizeny()
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 znizeny o 100,00 EUR.';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        $this->assertEquals(-100.0, $result->amount);
    }

    #[Test]
    public function emailWithPositiveAmountFromZvyseny()
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 50,00 EUR.';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        $this->assertEquals(50.0, $result->amount);
    }

    #[Test]
    public function emailWithAlternativeDateFormat()
    {
        $email = '19.5.2026 9:40 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        $this->assertNotFalse($result->transactionDate);
        $this->assertEquals(10.0, $result->amount);
    }

    #[Test]
    public function emailWithSpacesInAmount()
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 1 234,56 EUR.';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        $this->assertEquals(1234.56, $result->amount);
    }

    #[Test]
    public function emailWithDescriptionOnly()
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Popis transakcie: TRANSFER FROM ACCOUNT';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        $this->assertEquals('TRANSFER FROM ACCOUNT', $result->description);
        $this->assertEquals('FROM ACCOUNT', $result->sourceAccountNumber);
    }

    #[Test]
    public function emailWithSingleWordDescription()
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Popis transakcie: PAYMENT';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        $this->assertEquals('PAYMENT', $result->description);
        $this->assertEquals('PAYMENT', $result->sourceAccountNumber);
    }

    #[Test]
    public function emailWithEmptyVsSsKsInStructuredRef()
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Referencia platitela: /VS/SS/KS';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        $this->assertNull($result->vs);
        $this->assertNull($result->ss);
        $this->assertNull($result->ks);
    }

    #[Test]
    public function emailWithMultipleTimeFormats()
    {
        // Tests date parsing loop and FalseValue mutant (line 25) and Break_ mutant (line 30)
        $email1 = '12. 1. 2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.';
        $parser = new TatraBankaMailParser();
        $result1 = $parser->parse($email1);
        $this->assertNotEquals(false, $result1->transactionDate);

        $email2 = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.';
        $result2 = $parser->parse($email2);
        $this->assertNotEquals(false, $result2->transactionDate);
    }

    #[Test]
    public function emailWithReferenciaPlatelaMultiline()
    {
        // Tests regex with multiline flag (kills PregMatchRemoveFlags line 48)
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Some text before
Referencia platitela: /VS1234567890/SS9876543210/KS0308
Some text after';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        $this->assertEquals('1234567890', $result->vs);
        $this->assertEquals('9876543210', $result->ss);
        $this->assertEquals('0308', $result->ks);
    }

    #[Test]
    public function emailWithVsInStructuredRefFormat()
    {
        // Tests VS extraction from /VS format with correct array indices
        // (kills IncrementInteger lines 50, 51 and DecrementInteger line 52)
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Referencia platitela: /VS1234567890/SS9876543210/KS0308';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        // Must extract correct indices: [1] for VS, [2] for SS, [3] for KS
        $this->assertEquals('1234567890', $result->vs);
        $this->assertEquals('9876543210', $result->ss);
        $this->assertEquals('0308', $result->ks);
    }

    #[Test]
    public function emailWithVsAlreadySetFromStructuredRef()
    {
        // Tests the Identical mutant on line 55 (vs === null vs vs !== null)
        // When VS is set from structured ref, vs([0-9]) pattern should not overwrite
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Referencia platitela: /VS1111111111/SS/KS
Note: vs9999999999 in description';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        // Should keep VS from structured ref (1111111111), not overwrite with vs pattern
        $this->assertEquals('1111111111', $result->vs);
    }

    #[Test]
    public function emailWithPregMatchFailure()
    {
        // Tests PregMatchMatches mutant on line 55 - verifies regex actually matches
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Referencia platitela: no_vs_here_just_text';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        // When regex doesn't match, vs should remain null (not get garbage from failed match)
        $this->assertNull($result->vs);
    }
}
