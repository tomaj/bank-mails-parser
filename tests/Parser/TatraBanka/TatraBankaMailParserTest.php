<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\TatraBanka;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
use Tomaj\BankMailsParser\Tests\Fixtures;

#[CoversClass(TatraBankaMailParser::class)]
class TatraBankaMailParserTest extends TestCase
{
    #[Test]
    public function simpleEmail(): void
    {
        $email = Fixtures::load('tatrabanka/notifikacia');
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        self::assertSame('SK9812353347235', $mailContent->accountNumber);
        self::assertSame('1100/000000-261426464', $mailContent->sourceAccountNumber);
        self::assertSame('EUR', $mailContent->money?->currency->value);
        self::assertSame('12.31', $mailContent->money->toDecimalString());
        self::assertSame('1234056789', $mailContent->vs);
        self::assertSame('test-sprava', $mailContent->receiverMessage);
        self::assertSame('CCINT 1100/000000-261426464', $mailContent->description);
        self::assertNull($mailContent->ks);
        self::assertNull($mailContent->ss);
        self::assertSame(new \DateTimeImmutable('2015-01-16 12:51:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function simpleEmailWithoutSourceAccountNumberPrefix(): void
    {
        $email = Fixtures::load('tatrabanka/notifikacia-bez-predpony-uctu');
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        self::assertSame('SK9812353347235', $mailContent->accountNumber);
        self::assertSame('1100/000000-261426464', $mailContent->sourceAccountNumber);
        self::assertSame('EUR', $mailContent->money?->currency->value);
        self::assertSame('12.31', $mailContent->money->toDecimalString());
        self::assertSame('1234056789', $mailContent->vs);
        self::assertSame('test-sprava', $mailContent->receiverMessage);
        self::assertSame('1100/000000-261426464', $mailContent->description);
        self::assertNull($mailContent->ks);
        self::assertNull($mailContent->ss);
        self::assertSame(new \DateTimeImmutable('2015-01-16 12:51:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function allInputsWithDecreaseEmail(): void
    {
        $email = Fixtures::load('tatrabanka/odchadzajuca-platba-43');
        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        self::assertSame('SK9812353347235', $mailContent->accountNumber);
        self::assertSame('1100/000000-261426464', $mailContent->sourceAccountNumber);
        self::assertSame('USD', $mailContent->money?->currency->value);
        self::assertSame('-43.29', $mailContent->money->toDecimalString());
        self::assertSame('1234056789', $mailContent->vs);
        self::assertSame('9087654321', $mailContent->ss);
        self::assertSame('5428175648', $mailContent->ks);
        self::assertSame('test-sprava-druha', $mailContent->receiverMessage);
        self::assertSame('CCINT 1100/000000-261426464', $mailContent->description);
        self::assertSame(new \DateTimeImmutable('2015-01-16 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function emailWithoutReceiverMessage(): void
    {
        $email = Fixtures::load('tatrabanka/odchadzajuca-platba');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        self::assertSame('SK9812353347235', $mailContent->accountNumber);
        self::assertSame('1100/000000-261426464', $mailContent->sourceAccountNumber);
        self::assertSame('USD', $mailContent->money?->currency->value);
        self::assertSame('-1243.29', $mailContent->money->toDecimalString());
        self::assertSame('1234056789', $mailContent->vs);
        self::assertSame('9087654321', $mailContent->ss);
        self::assertSame('5428175648', $mailContent->ks);
        self::assertNull($mailContent->receiverMessage);
        self::assertSame('CCINT 1100/000000-261426464', $mailContent->description);
        self::assertSame(new \DateTimeImmutable('2015-01-16 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function emailWithoutDescription(): void
    {
        $email = Fixtures::load('tatrabanka/odchadzajuca-platba-bez-ccint');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        self::assertSame('SK9812353347235', $mailContent->accountNumber);
        self::assertNull($mailContent->sourceAccountNumber);
        self::assertSame('USD', $mailContent->money?->currency->value);
        self::assertSame('-1243.29', $mailContent->money->toDecimalString());
        self::assertSame('1234056789', $mailContent->vs);
        self::assertSame('9087654321', $mailContent->ss);
        self::assertSame('5428175648', $mailContent->ks);
        self::assertSame('test-sprava', $mailContent->receiverMessage);
        self::assertNull($mailContent->description);
        self::assertSame(new \DateTimeImmutable('2015-01-16 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function emailWithoutVariableSymbol(): void
    {
        $email = Fixtures::load('tatrabanka/odchadzajuca-platba-mala');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        self::assertSame('SK9812369347235', $mailContent->accountNumber);
        self::assertSame('1100/000000-261426464', $mailContent->sourceAccountNumber);
        self::assertSame('EUR', $mailContent->money?->currency->value);
        self::assertSame('-2.20', $mailContent->money->toDecimalString());
        self::assertNull($mailContent->vs);
        self::assertSame('9087654322', $mailContent->ss);
        self::assertSame('5428175649', $mailContent->ks);
        self::assertNull($mailContent->receiverMessage);
        self::assertSame('CCINT 1100/000000-261426464', $mailContent->description);
        self::assertSame(new \DateTimeImmutable('2015-01-12 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function emailWithVariableSymbolInReceiverMessage(): void
    {
        $email = Fixtures::load('tatrabanka/odchadzajuca-platba-mala-2');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        self::assertSame('SK9812369347235', $mailContent->accountNumber);
        self::assertSame('1100/000000-261426464', $mailContent->sourceAccountNumber);
        self::assertSame('EUR', $mailContent->money?->currency->value);
        self::assertSame('-2.20', $mailContent->money->toDecimalString());
        self::assertSame('1234056789', $mailContent->vs);
        self::assertNull($mailContent->ss);
        self::assertNull($mailContent->ks);
        self::assertSame('VS1234056789', $mailContent->receiverMessage);
        self::assertSame('CCINT 1100/000000-261426464', $mailContent->description);
        self::assertSame(new \DateTimeImmutable('2015-01-12 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    // Referencia platitela: 1234056789
    #[Test]
    public function emailWithVariableSymbolInUniqueMandateReferenceWithoutPrefix(): void
    {
        $email = Fixtures::load('tatrabanka/odchadzajuca-platba-mala-3');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        self::assertSame('SK9812369347235', $mailContent->accountNumber);
        self::assertSame('1100/000000-261426464', $mailContent->sourceAccountNumber);
        self::assertSame('EUR', $mailContent->money?->currency->value);
        self::assertSame('-2.20', $mailContent->money->toDecimalString());
        self::assertSame('1234056789', $mailContent->vs);
        self::assertNull($mailContent->ss);
        self::assertNull($mailContent->ks);
        self::assertNull($mailContent->receiverMessage);
        self::assertSame('CCINT 1100/000000-261426464', $mailContent->description);
        self::assertSame(new \DateTimeImmutable('2015-01-12 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    // Informacia pre prijemcu: 1234056789
    public function testEmailWithVariableSymbolInReceiverMessageWithoutVSPrefix(): void
    {
        $email = Fixtures::load('tatrabanka/odchadzajuca-platba-mala-4');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        self::assertSame('SK9812369347235', $mailContent->accountNumber);
        self::assertSame('1100/000000-261426464', $mailContent->sourceAccountNumber);
        self::assertSame('EUR', $mailContent->money?->currency->value);
        self::assertSame('-2.20', $mailContent->money->toDecimalString());
        self::assertSame('1234056789', $mailContent->vs);
        self::assertNull($mailContent->ss);
        self::assertNull($mailContent->ks);
        self::assertSame('1234056789', $mailContent->receiverMessage);
        self::assertSame('CCINT 1100/000000-261426464', $mailContent->description);
        self::assertSame(new \DateTimeImmutable('2015-01-12 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    // Creditor Reference Information - SEPA XML format
    // Informacia pre prijemcu: (CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)
    #[Test]
    public function emailWithVariableSymbolInReceiverMessageCreditorReferenceInformation(): void
    {
        $email = Fixtures::load('tatrabanka/vs-v-sprave');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        self::assertSame('SK9812369347235', $mailContent->accountNumber);
        self::assertSame('1100/000000-261426464', $mailContent->sourceAccountNumber);
        self::assertSame('EUR', $mailContent->money?->currency->value);
        self::assertSame('-2.20', $mailContent->money->toDecimalString());
        self::assertSame('1234056789', $mailContent->vs);
        self::assertNull($mailContent->ss);
        self::assertNull($mailContent->ks);
        self::assertSame('(CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)', $mailContent->receiverMessage);
        self::assertSame('CCINT 1100/000000-261426464', $mailContent->description);
        self::assertSame(new \DateTimeImmutable('2015-01-12 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    // Transaction date with spaces (16. 1. 2015 12:51)
    #[Test]
    public function emailWithSecondaryTransactionDateFormat(): void
    {
        $email = Fixtures::load('tatrabanka/vs-v-sprave-cdtr');

        $tatrabankaMailParser = new TatraBankaMailParser();
        $mailContent = $tatrabankaMailParser->parse($email);

        self::assertSame('SK9812369347235', $mailContent->accountNumber);
        self::assertSame('1100/000000-261426464', $mailContent->sourceAccountNumber);
        self::assertSame('EUR', $mailContent->money?->currency->value);
        self::assertSame('-2.20', $mailContent->money->toDecimalString());
        self::assertSame('1234056789', $mailContent->vs);
        self::assertNull($mailContent->ss);
        self::assertNull($mailContent->ks);
        self::assertSame('(CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)', $mailContent->receiverMessage);
        self::assertSame('CCINT 1100/000000-261426464', $mailContent->description);
        self::assertSame(new \DateTimeImmutable('2015-01-12 12:11:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function errorEmail(): void
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
        self::assertNull($mailContent);
    }

    #[Test]
    public function emailWithNegativeAmountFromZnizeny(): void
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 znizeny o 100,00 EUR.';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        self::assertSame('-100.00', $result->money?->toDecimalString());
    }

    #[Test]
    public function emailWithPositiveAmountFromZvyseny(): void
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 50,00 EUR.';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        self::assertSame('50.00', $result->money?->toDecimalString());
    }

    #[Test]
    public function emailWithAlternativeDateFormat(): void
    {
        $email = '19.5.2026 9:40 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        self::assertNotNull($result->transactionDate);
        self::assertSame('10.00', $result->money?->toDecimalString());
    }

    #[Test]
    public function emailWithSpacesInAmount(): void
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 1 234,56 EUR.';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        self::assertSame('1234.56', $result->money?->toDecimalString());
    }

    #[Test]
    public function emailWithDescriptionOnly(): void
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Popis transakcie: TRANSFER FROM ACCOUNT';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        self::assertSame('TRANSFER FROM ACCOUNT', $result->description);
        self::assertSame('FROM ACCOUNT', $result->sourceAccountNumber);
    }

    #[Test]
    public function emailWithSingleWordDescription(): void
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Popis transakcie: PAYMENT';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        self::assertSame('PAYMENT', $result->description);
        self::assertSame('PAYMENT', $result->sourceAccountNumber);
    }

    #[Test]
    public function emailWithEmptyVsSsKsInStructuredRef(): void
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Referencia platitela: /VS/SS/KS';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        self::assertNull($result->vs);
        self::assertNull($result->ss);
        self::assertNull($result->ks);
    }

    #[Test]
    public function emailWithMultipleTimeFormats(): void
    {
        $email1 = '12. 1. 2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.';
        $parser = new TatraBankaMailParser();
        $result1 = $parser->parse($email1);
        self::assertNotEquals(false, $result1->transactionDate?->getTimestamp());

        $email2 = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.';
        $result2 = $parser->parse($email2);
        self::assertNotEquals(false, $result2->transactionDate?->getTimestamp());
    }

    #[Test]
    public function emailWithReferenciaPlatelaMultiline(): void
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Some text before
Referencia platitela: /VS1234567890/SS9876543210/KS0308
Some text after';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        self::assertSame('1234567890', $result->vs);
        self::assertSame('9876543210', $result->ss);
        self::assertSame('0308', $result->ks);
    }

    #[Test]
    public function emailWithVsInStructuredRefFormat(): void
    {
        // Tests VS extraction from /VS format with correct array indices
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Referencia platitela: /VS1234567890/SS9876543210/KS0308';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        // Must extract correct indices: [1] for VS, [2] for SS, [3] for KS
        self::assertSame('1234567890', $result->vs);
        self::assertSame('9876543210', $result->ss);
        self::assertSame('0308', $result->ks);
    }

    #[Test]
    public function emailWithVsAlreadySetFromStructuredRef(): void
    {
        // When VS is set from structured ref, vs([0-9]) pattern should not overwrite
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Referencia platitela: /VS1111111111/SS/KS
Note: vs9999999999 in description';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        // Should keep VS from structured ref (1111111111), not overwrite with vs pattern
        self::assertSame('1111111111', $result->vs);
    }

    #[Test]
    public function emailWithPregMatchFailure(): void
    {
        $email = '12.1.2015 12:11 bol zostatok Vasho uctu SK123 zvyseny o 10,00 EUR.
Referencia platitela: no_vs_here_just_text';
        $parser = new TatraBankaMailParser();
        $result = $parser->parse($email);

        // When regex doesn't match, vs should remain null (not get garbage from failed match)
        self::assertNull($result->vs);
    }
}
