<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\Csob;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

#[CoversClass(CsobMailParser::class)]
class CsobMailParserTest extends TestCase
{
    #[Test]
    public function singleTransferPayment()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/dosla-platba');
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('1122334455/9999', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('1234.56', $mailContent->money?->toDecimalString());
        $this->assertEquals('23456789', $mailContent->vs);
        $this->assertEquals('3456', $mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2018-09-25 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    // zaúčtovaná changed to zaúčtována
    #[Test]
    public function singleTransferPaymentFixedTypoZauctovana()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/prichadzajuca-uhrada');
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('1122334455/9999', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('1234.56', $mailContent->money?->toDecimalString());
        $this->assertEquals('23456789', $mailContent->vs);
        $this->assertEquals('3456', $mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2018-09-25 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function multiTransferPayment()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/viac-transakcii');
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(2, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('1122334455/9999', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('1234.56', $mailContent->money?->toDecimalString());
        $this->assertEquals('23456789', $mailContent->vs);
        $this->assertEquals('3456', $mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2018-09-25 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());

        $mailContent = $mailContents[1];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('9988776655/1111', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('987.65', $mailContent->money?->toDecimalString());
        $this->assertEquals('78787878', $mailContent->vs);
        $this->assertEquals('6789', $mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2018-09-25 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function immediateTransferPayment()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/okamzita-uhrada');
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('1122334455/9999', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('414.00', $mailContent->money?->toDecimalString());
        $this->assertEquals('23456789', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2022-08-18 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function foreignTransferPaymentAlternativeSepa()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/zahranicna-platba-sepa');
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('NL56 ABNA 1234 5678 90', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('3581.70', $mailContent->money?->toDecimalString());
        $this->assertEquals('23456789', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2022-08-18 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function foreignTransferPaymentAlternativeZahranicni()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/zahranicna-platba');
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('SK99 1100 0000 7777 8888 9999', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('3300.00', $mailContent->money?->toDecimalString());
        $this->assertEquals('6869282911', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2023-12-05 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function foreignTransferPaymentAlternativeZahranicniWithMultilinePurpose()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/zahranicna-platba-ucel-viac-riadkov');
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('SK99 1100 0000 7777 8888 9999', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('3300.00', $mailContent->money?->toDecimalString());
        $this->assertEquals('6869282912', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2023-12-05 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function foreignTransferPaymentWithVariableSymbolSetToNull()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/prichadzajuca-uhrada-vs-v-sprave');
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('2233445566/2600', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('1440.00', $mailContent->money?->toDecimalString());
        $this->assertEquals('0449274899', $mailContent->vs);
        $this->assertSame(new \DateTimeImmutable('2024-03-01 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function transferPaymentWithPrefixedVariableSymbolInReceiverMessage()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/zahranicna-platba-vs');
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('6012345678/2700', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('1980.00', $mailContent->money?->toDecimalString());
        $this->assertEquals('3723199116', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2024-02-21 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function transferPaymentWithPrefixedWithDotsVariableSymbolInReceiverMessage()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/zahranicna-platba-vs-bodka');
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('6012345678/2700', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('1980.00', $mailContent->money?->toDecimalString());
        $this->assertEquals('3723199116', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2024-02-21 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    public function testTransferPaymentWithNotPrefixedVariableSymbolInReceiverMessage()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/zahranicna-platba-2700');
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('6012345678/2700', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('2700.00', $mailContent->money?->toDecimalString());
        $this->assertEquals('3723199333', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2024-01-31 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function singleCardpaySettlement()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/platba-kartou');
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);
    }

    #[Test]
    public function errorEmail()
    {
        $email = 'Specifický symbol: 4545454545

Zůstatek na účtu po zaúčtování transakce: +1 234 567,89 CZK.

S přáním krásného dne
Vaše ČSOB
';
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(0, $mailContents);
    }

    #[Test]
    public function cashTransferPayment()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/hotovostna-transakcia');

        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('99991111', $mailContent->accountNumber);
        $this->assertNull($mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->money?->currency->value);
        $this->assertSame('3300.00', $mailContent->money?->toDecimalString());
        $this->assertEquals('1122334455', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertSame(new \DateTimeImmutable('2023-12-18 00:00:00', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(), $mailContent->transactionDate?->getTimestamp());
    }

    #[Test]
    public function csobParserWithNoTransactionMarker()
    {
        // Test parseMulti when there's no "Dne " to split on
        $email = 'Email without transaction date marker
Částka: +100,00 CZK
Variabilní symbol: 123456789';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);
        $this->assertEmpty($result);
    }

    #[Test]
    public function csobParserWithInvalidDateFormat()
    {
        $email = 'Dne invalid_date byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Variabilní symbol: 123456789';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertSame([], $result);
    }

    #[Test]
    public function csobParserWithMissingRequiredFields()
    {
        // Test content that doesn't match the main regex pattern
        $email = 'Some other banking email content that does not match ČSOB format.';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        // Should return empty array when pattern doesn't match
        $this->assertEmpty($result);
    }

    #[Test]
    public function csobParserWithEmptyContent()
    {
        $parser = new CsobMailParser();

        $result = $parser->parseMulti('');
        $this->assertEmpty($result);
    }

    #[Test]
    public function csobParserMultiFiltersNullResults()
    {
        // Test that parseMulti properly filters out null results
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/viac-transakcii-kratke');

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        // Should only return the 2 valid transactions, filtering out the 1 null
        $this->assertCount(2, $result);
        // array_filter preserves keys, so reindex the array
        $result = array_values($result);
        $this->assertSame('100.00', $result[0]->money?->toDecimalString());
        $this->assertEquals('111111', $result[0]->vs);
        $this->assertSame('200.00', $result[1]->money?->toDecimalString());
        $this->assertEquals('222222', $result[1]->vs);
    }

    #[Test]
    public function csobParserWithMultilineUnicodeContent()
    {
        $email = \Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/dosla-platba-kratka');

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $mailContent = $result[0];
        $this->assertEquals('SK1122334455667788', $mailContent->sourceAccountNumber);
        $this->assertEquals('Testovací zpráva s čárkami', $mailContent->receiverMessage);
        $this->assertEquals('23456789', $mailContent->vs);
    }

    #[Test]
    public function csobParserWithVsIdenticalMutant()
    {
        // When VS is already set, v.s. pattern should not overwrite it
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Variabilní symbol: 1111111111
Zpráva příjemci: text v.s.9999999999 more text';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        // Should keep the first VS found (1111111111), not overwrite with v.s. pattern
        $this->assertEquals('1111111111', $result[0]->vs);
    }

    #[Test]
    public function csobParserWithPregMatchMatches()
    {
        // Verifies regex actually needs to match, not just return empty result
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Zpráva příjemci: Some text without VS pattern
Účel platby: Text without number pattern
Zůstatek';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        // When patterns don't match, VS should be null (not garbage from failed match)
        $this->assertNull($result[0]->vs);
    }

    #[Test]
    public function csobParserAmountWithMultilineBeforeAndAfter()
    {
        $email = 'Text před

Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Nějaký další text
Částka: +1 234,56 CZK
Text za částkou';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertSame('1234.56', $result[0]->money?->toDecimalString());
        $this->assertEquals('CZK', $result[0]->money?->currency->value);
    }

    #[Test]
    public function csobParserSourceAccountWithMultiline()
    {
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Další text
Účet protistrany: CZ1234567890123456789012
Více textu';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertEquals('CZ1234567890123456789012', $result[0]->sourceAccountNumber);
    }

    #[Test]
    public function csobParserReceiverMessageWithMultiline()
    {
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Text před zprávou
Zpráva příjemci: Zpráva s diakritikou ěščřžýáíé
Text za zprávou';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertEquals('Zpráva s diakritikou ěščřžýáíé', $result[0]->receiverMessage);
    }

    #[Test]
    public function csobParserVsFromIdentifikaceWithMultiline()
    {
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Text před
Identifikace: 9876543210
Text za';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertEquals('9876543210', $result[0]->vs);
    }

    #[Test]
    public function csobParserVsFromReceiverMessageWithCaseInsensitive()
    {
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Zpráva příjemci: PAYMENT FOR INVOICE 1234567890 THANKS';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertEquals('1234567890', $result[0]->vs);
    }

    #[Test]
    public function csobParserVsFromUcelPlatbyWithMultiline()
    {
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Text před
Účel platby: 9988776655
Zůstatek na účtu';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertEquals('9988776655', $result[0]->vs);
    }

    #[Test]
    public function csobParserWithNegativeAmount()
    {
        // Test negative amount to verify correct array index in sign detection
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: -1 234,56 CZK
Variabilní symbol: 23456789';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $this->assertSame('-1234.56', $result[0]->money?->toDecimalString());
        $this->assertEquals('CZK', $result[0]->money?->currency->value);
    }

    #[Test]
    public function csobParserWithReceiverMessageIndex()
    {
        // Test that verifies receiverMessage uses correct array index ($result[1])
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Zpráva příjemci: Expected message here
Variabilní symbol: 123456';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $this->assertEquals('Expected message here', $result[0]->receiverMessage);
    }

    #[Test]
    public function csobParserWithVsFromIdentifikace()
    {
        // Test VS extraction from "Identifikace:" when VS is null
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Identifikace: 9876543210
Zůstatek';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $this->assertEquals('9876543210', $result[0]->vs);
    }

    #[Test]
    public function csobParserWithVsFromVsDotS()
    {
        // Test VS extraction from "v.s." pattern when VS is still null
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Účel platby: Platba v.s.1234567890
Zůstatek';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $this->assertEquals('1234567890', $result[0]->vs);
    }

    #[Test]
    public function csobParserWithVsFromReceiverMessage()
    {
        // Test VS extraction from receiver message when VS is null
        // This tests line 70 pattern
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Zpráva příjemci: Payment for invoice 1234567890 thank you
Zůstatek';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $this->assertEquals('1234567890', $result[0]->vs);
    }

    #[Test]
    public function csobParserWithVsFromUcelPlatby()
    {
        // Test VS extraction from "Účel platby:" when all other methods failed
        // This tests the fallback logic on lines 74-79
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Účel platby: 9988776655
Zůstatek';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $this->assertEquals('9988776655', $result[0]->vs);
    }

    #[Test]
    public function csobParserWithVsFromUcelPlatbyMultiline()
    {
        // Test VS extraction from "Účel platby:" with multiline content
        // This tests the second pattern on line 76-77 with /s flag
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Účel platby: Some text here 9988776655 more text
Zůstatek po zaúčtování: +1 234 567,89 CZK';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $this->assertEquals('9988776655', $result[0]->vs);
    }

    #[Test]
    public function parseAndParseAllRejectContentWithoutATransaction(): void
    {
        $parser = new CsobMailParser();

        try {
            $parser->parse('no transaction here');
            $this->fail('parse() must reject content without a transaction');
        } catch (InvalidEmailFormatException $exception) {
            $this->assertStringContainsString('Could not find transaction pattern', $exception->getMessage());
        }

        try {
            $parser->parseAll('no transaction here');
            $this->fail('parseAll() must reject content without a transaction');
        } catch (InvalidEmailFormatException $exception) {
            $this->assertStringContainsString('Could not find transaction pattern', $exception->getMessage());
        }
    }

}
