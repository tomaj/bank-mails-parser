<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\TatraBanka;

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
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('tatrabanka/notifikacia');
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->money?->currency->value);
        $this->assertSame('12.31', $mailContent->money?->toDecimalString());
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertEquals('test-sprava', $mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2015-01-16 12:51:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function simpleEmailWithoutSourceAccountNumberPrefix()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('tatrabanka/notifikacia-bez-predpony-uctu');
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->money?->currency->value);
        $this->assertSame('12.31', $mailContent->money?->toDecimalString());
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertEquals('test-sprava', $mailContent->receiverMessage);
        $this->assertEquals('1100/000000-261426464', $mailContent->description);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2015-01-16 12:51:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function allInputsWithDecreaseEmail()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('tatrabanka/odchadzajuca-platba-43');
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('USD', $mailContent->money?->currency->value);
        $this->assertSame('-43.29', $mailContent->money?->toDecimalString());
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertEquals('9087654321', $mailContent->ss);
        $this->assertEquals('5428175648', $mailContent->ks);
        $this->assertEquals('test-sprava-druha', $mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertSame(new \DateTimeImmutable('2015-01-16 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function emailWithoutReceiverMessage()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('tatrabanka/odchadzajuca-platba');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('USD', $mailContent->money?->currency->value);
        $this->assertSame('-1243.29', $mailContent->money?->toDecimalString());
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertEquals('9087654321', $mailContent->ss);
        $this->assertEquals('5428175648', $mailContent->ks);
        $this->assertNull($mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertSame(new \DateTimeImmutable('2015-01-16 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function emailWithoutDescription()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('tatrabanka/odchadzajuca-platba-bez-ccint');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812353347235', $mailContent->accountNumber);
        $this->assertNull($mailContent->sourceAccountNumber);
        $this->assertEquals('USD', $mailContent->money?->currency->value);
        $this->assertSame('-1243.29', $mailContent->money?->toDecimalString());
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertEquals('9087654321', $mailContent->ss);
        $this->assertEquals('5428175648', $mailContent->ks);
        $this->assertEquals('test-sprava', $mailContent->receiverMessage);
        $this->assertNull($mailContent->description);
        $this->assertSame(new \DateTimeImmutable('2015-01-16 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function emailWithoutVariableSymbol()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('tatrabanka/odchadzajuca-platba-mala');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812369347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->money?->currency->value);
        $this->assertSame('-2.20', $mailContent->money?->toDecimalString());
        $this->assertNull($mailContent->vs);
        $this->assertEquals('9087654322', $mailContent->ss);
        $this->assertEquals('5428175649', $mailContent->ks);
        $this->assertNull($mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertSame(new \DateTimeImmutable('2015-01-12 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function emailWithVariableSymbolInReceiverMessage()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('tatrabanka/odchadzajuca-platba-mala-2');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812369347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->money?->currency->value);
        $this->assertSame('-2.20', $mailContent->money?->toDecimalString());
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertEquals('VS1234056789', $mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertSame(new \DateTimeImmutable('2015-01-12 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    // Referencia platitela: 1234056789
    #[Test]
    public function emailWithVariableSymbolInUniqueMandateReferenceWithoutPrefix()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('tatrabanka/odchadzajuca-platba-mala-3');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812369347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->money?->currency->value);
        $this->assertSame('-2.20', $mailContent->money?->toDecimalString());
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertSame(new \DateTimeImmutable('2015-01-12 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    // Informacia pre prijemcu: 1234056789
    public function testEmailWithVariableSymbolInReceiverMessageWithoutVSPrefix()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('tatrabanka/odchadzajuca-platba-mala-4');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812369347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->money?->currency->value);
        $this->assertSame('-2.20', $mailContent->money?->toDecimalString());
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertEquals('1234056789', $mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertSame(new \DateTimeImmutable('2015-01-12 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    // Creditor Reference Information - SEPA XML format
    // Informacia pre prijemcu: (CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)
    #[Test]
    public function emailWithVariableSymbolInReceiverMessageCreditorReferenceInformation()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('tatrabanka/vs-v-sprave');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812369347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->money?->currency->value);
        $this->assertSame('-2.20', $mailContent->money?->toDecimalString());
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertEquals('(CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)', $mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertSame(new \DateTimeImmutable('2015-01-12 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    // Transaction date with spaces (16. 1. 2015 12:51)
    #[Test]
    public function emailWithSecondaryTransactionDateFormat()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('tatrabanka/vs-v-sprave-cdtr');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        $this->assertEquals('SK9812369347235', $mailContent->accountNumber);
        $this->assertEquals('1100/000000-261426464', $mailContent->sourceAccountNumber);
        $this->assertEquals('EUR', $mailContent->money?->currency->value);
        $this->assertSame('-2.20', $mailContent->money?->toDecimalString());
        $this->assertEquals('1234056789', $mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertEquals('(CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)', $mailContent->receiverMessage);
        $this->assertEquals('CCINT 1100/000000-261426464', $mailContent->description);
        $this->assertSame(new \DateTimeImmutable('2015-01-12 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
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
        $mailContent = $tatrabankaMailParser->tryParse($email);
        $this->assertNull($mailContent);
    }

    #[Test]
    public function emailWithNegativeAmountFromZnizeny()
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 znizeny o 100,00 EUR.';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        $this->assertSame('-100.00', $result->money?->toDecimalString());
    }

    #[Test]
    public function emailWithPositiveAmountFromZvyseny()
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 50,00 EUR.';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        $this->assertSame('50.00', $result->money?->toDecimalString());
    }

    #[Test]
    public function emailWithAlternativeDateFormat()
    {
        $email = '19.5.2026 9:40 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        $this->assertNotFalse($result->transactionDate?->getTimestamp());
        $this->assertSame('10.00', $result->money?->toDecimalString());
    }

    #[Test]
    public function emailWithSpacesInAmount()
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 1 234,56 EUR.';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        $this->assertSame('1234.56', $result->money?->toDecimalString());
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
        $email1 = '12. 1. 2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.';
        $parser = new TatraBankaMailParser();
        $result1 = $parser->parse($email1);
        $this->assertNotEquals(false, $result1->transactionDate?->getTimestamp());

        $email2 = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.';
        $result2 = $parser->parse($email2);
        $this->assertNotEquals(false, $result2->transactionDate?->getTimestamp());
    }

    #[Test]
    public function emailWithReferenciaPlatelaMultiline()
    {
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
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Referencia platitela: no_vs_here_just_text';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        // When regex doesn't match, vs should remain null (not get garbage from failed match)
        $this->assertNull($result->vs);
    }
}
