<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\Csob;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;

#[CoversClass(CsobMailParser::class)]
class CsobMailParserTest extends TestCase
{
    #[Test]
    public function singleTransferPayment()
    {
        $email = 'Vážený kliente,
        
toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
Částka: +1 234,56 CZK
Účet protistrany: 1122334455/9999
Název protistrany: Capi Hnizdo a.s.
Variabilní symbol: 23456789
Konstantní symbol: 3456

Zůstatek na účtu po zaúčtování transakce: +1 234 567,89 CZK.

S přáním krásného dne
Vaše ČSOB
';
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('1122334455/9999', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(1234.56, $mailContent->amount);
        $this->assertEquals('23456789', $mailContent->vs);
        $this->assertEquals('3456', $mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('25.9.2018'), $mailContent->transactionDate);
    }

    // zaúčtovaná changed to zaúčtována
    #[Test]
    public function singleTransferPaymentFixedTypoZauctovana()
    {
        $email = 'Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 25.9.2018 byla na účtu 123456789 zaúčtována transakce typu: Příchozí úhrada.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
Částka: +1 234,56 CZK
Účet protistrany: 1122334455/9999
Název protistrany: Capi Hnizdo a.s.
Variabilní symbol: 23456789
Konstantní symbol: 3456

Zůstatek na účtu po zaúčtování transakce: +1 234 567,89 CZK.

S přáním krásného dne
Vaše ČSOB
';
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('1122334455/9999', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(1234.56, $mailContent->amount);
        $this->assertEquals('23456789', $mailContent->vs);
        $this->assertEquals('3456', $mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('25.9.2018'), $mailContent->transactionDate);
    }

    #[Test]
    public function multiTransferPayment()
    {
        $email = 'Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
Částka: +1 234,56 CZK
Účet protistrany: 1122334455/9999
Název protistrany: Capi Hnizdo a.s.
Variabilní symbol: 23456789
Konstantní symbol: 3456

Zůstatek na účtu po zaúčtování transakce: +1 234 567,89 CZK.

Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
Částka: +987,65 CZK
Účet protistrany: 9988776655/1111
Název protistrany: Sorry jako a.s.
Variabilní symbol: 78787878
Konstantní symbol: 6789

Zůstatek na účtu po zaúčtování transakce: +1 235 555,54 CZK.

S přáním krásného dne
Vaše ČSOB
';
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(2, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('1122334455/9999', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(1234.56, $mailContent->amount);
        $this->assertEquals('23456789', $mailContent->vs);
        $this->assertEquals('3456', $mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('25.9.2018'), $mailContent->transactionDate);

        $mailContent = $mailContents[1];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('9988776655/1111', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(987.65, $mailContent->amount);
        $this->assertEquals('78787878', $mailContent->vs);
        $this->assertEquals('6789', $mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('25.9.2018'), $mailContent->transactionDate);
    }

    #[Test]
    public function immediateTransferPayment()
    {
        $email = 'Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 18.8.2022 byla na účtu 123456789 zaúčtována transakce typu: Příchozí úhrada okamžitá.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
Částka: +414,00 CZK
Účet protistrany: 1122334455/9999
Název protistrany: Capi Hnizdo a.s.
Variabilní symbol: 23456789
Zpráva příjemci: VS23456789

Zůstatek na účtu po zaúčtování transakce: +1 234 567,89 CZK.

S přáním krásného dne
Vaše ČSOB
';
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('1122334455/9999', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(414.00, $mailContent->amount);
        $this->assertEquals('23456789', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('18.8.2022'), $mailContent->transactionDate);
    }

    #[Test]
    public function foreignTransferPaymentAlternativeSepa()
    {
        $email = 'Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 18.8.2022 byl na účtu 123456789 zaúčtovaný SEPA převod.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
Zaslaná částka platby: 150,00 EUR
Kurz: 23,878
Částka: +3 581,70 CZK
Účet protistrany: NL56 ABNA 1234 5678 90
Název protistrany: 360GEORGE SRS
Adresa protistrany: NETHERLANDS
Číslo transakce ČSOB: 9876543210
Reference plátce: CT12345678901234
Identifikace: 23456789
Účel platby: 23456789

Zůstatek na účtu po zaúčtování transakce: +1 234 567,89 CZK.

S přáním krásného dne
Vaše ČSOB
';
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('NL56 ABNA 1234 5678 90', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(3581.70, $mailContent->amount);
        $this->assertEquals('23456789', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('18.8.2022'), $mailContent->transactionDate);
    }

    #[Test]
    public function foreignTransferPaymentAlternativeZahranicni()
    {
        $email = 'Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 5.12.2023 byla na účtu 123456789 zaúčtována zahraniční transakce.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
BIC: CEKOCZPP
Částka: +3 300,00 CZK
Účet protistrany/IBAN: SK99 1100 0000 7777 8888 9999
BIC/SWIFT: TATRSKBX
Název protistrany: NOVAK PETER
Adresa protistrany: Veterna 13
123 45  BRATISLAVA
SLOVENSKO
Kód poplatku: SHA
Číslo transakce ČSOB: 4012345678
Reference plátce: VI98765432100
Účel platby: NOVAK PETER BRATISLAVA SLOVENSKO 6869282911

Zůstatek na účtu po zaúčtování transakce: +12 345 678,90 CZK.

S přáním krásného dne
Vaše ČSOB
';
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('SK99 1100 0000 7777 8888 9999', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(3300, $mailContent->amount);
        $this->assertEquals('6869282911', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('5.12.2023'), $mailContent->transactionDate);
    }

    #[Test]
    public function foreignTransferPaymentAlternativeZahranicniWithMultilinePurpose()
    {
        $email = 'Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 5.12.2023 byla na účtu 123456789 zaúčtována zahraniční transakce.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
BIC: CEKOCZPP
Částka: +3 300,00 CZK
Účet protistrany/IBAN: SK99 1100 0000 7777 8888 9999
BIC/SWIFT: TATRSKBX
Název protistrany: NOVAK PETER
Adresa protistrany: Veterna 13
123 45  BRATISLAVA
SLOVENSKO
Kód poplatku: SHA
Číslo transakce ČSOB: 4012345678
Reference plátce: VI98765432100
Účel platby: NOVAK PETER BRATISLAVA SLOVENSKO V.S
. 6869282912

Zůstatek na účtu po zaúčtování transakce: +12 345 678,90 CZK.

S přáním krásného dne
Vaše ČSOB
';
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('SK99 1100 0000 7777 8888 9999', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(3300, $mailContent->amount);
        $this->assertEquals('6869282912', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('5.12.2023'), $mailContent->transactionDate);
    }

    #[Test]
    public function foreignTransferPaymentWithVariableSymbolSetToNull()
    {
        $email = 'Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 1.3.2024 byla na účtu 123456789 zaúčtována transakce typu: Příchozí úhrada.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
Částka: +1 440,00 CZK
Účet protistrany: 2233445566/2600
Název protistrany: WISE EUROPE SA
Variabilní symbol: 0000000000
Konstantní symbol: 0000
Specifický symbol: 0000000000
Zpráva příjemci: /Eva Adamova
/P98765432
/Random strasse 42 Berlin German
/VS0449274899

Zůstatek na účtu po zaúčtování transakce: +12 345 678,90 CZK.

S přáním krásného dne
Vaše ČSOB
';
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('2233445566/2600', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(1440.00, $mailContent->amount);
        $this->assertEquals('0449274899', $mailContent->vs);
        $this->assertEquals(strtotime('1.3.2024'), $mailContent->transactionDate);
    }

    #[Test]
    public function transferPaymentWithPrefixedVariableSymbolInReceiverMessage()
    {
        $email = 'Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 21.2.2024 byla na účtu 123456789 zaúčtována zahraniční transakce.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
Částka: +1 980,00 CZK
Účet protistrany: 6012345678/2700
Název protistrany: NETOPÍŘ KAREL
Zpráva příjemci: vs3723199116

Zůstatek na účtu po zaúčtování transakce: +12 345 678,90 CZK.

S přáním krásného dne
Vaše ČSOB
';
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('6012345678/2700', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(1980.00, $mailContent->amount);
        $this->assertEquals('3723199116', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('21.2.2024'), $mailContent->transactionDate);
    }

    #[Test]
    public function transferPaymentWithPrefixedWithDotsVariableSymbolInReceiverMessage()
    {
        $email = 'Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 21.2.2024 byla na účtu 123456789 zaúčtována zahraniční transakce.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
Částka: +1 980,00 CZK
Účet protistrany: 6012345678/2700
Název protistrany: NETOPÍŘ KAREL
Zpráva příjemci: v.s.3723199116

Zůstatek na účtu po zaúčtování transakce: +12 345 678,90 CZK.

S přáním krásného dne
Vaše ČSOB
';
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('6012345678/2700', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(1980.00, $mailContent->amount);
        $this->assertEquals('3723199116', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('21.2.2024'), $mailContent->transactionDate);
    }

    public function testTransferPaymentWithNotPrefixedVariableSymbolInReceiverMessage()
    {
        $email = 'Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 31.1.2024 byla na účtu 123456789 zaúčtována zahraniční transakce.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
Částka: +2 700,00 CZK
Účet protistrany: 6012345678/2700
Název protistrany: KRAL CZECH REPUBLIC
Zpráva příjemci: 3723199333

Zůstatek na účtu po zaúčtování transakce: +12 345 678,90 CZK.

S přáním krásného dne
Vaše ČSOB
';
        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('123456789', $mailContent->accountNumber);
        $this->assertEquals('6012345678/2700', $mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(2700.00, $mailContent->amount);
        $this->assertEquals('3723199333', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('31.1.2024'), $mailContent->transactionDate);
    }

    #[Test]
    public function singleCardpaySettlement()
    {
        $email = 'Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 25.9.2018 byla na účtu 123456789 zaúčtována transakce platební kartou.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 87654321
Majitel smlouvy: Shmelina a.s.
Účet: 123456789, CZK, CRM INTERNATION
Částka: +1 234,56 CZK
Z účtu: /
Variabilní symbol: 23456789
Konstantní symbol: 3456
Specifický symbol: 4545454545

Zůstatek na účtu po zaúčtování transakce: +1 234 567,89 CZK.

S přáním krásného dne
Vaše ČSOB
';
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
        $email = 'Vážený kliente,

toto je automaticky generovaný e-mail ze služby ČSOB CEB, neodpovídejte na něj.

Dne 18.12.2023 byla na účtu 99991111 zaúčtována hotovostní transakce.

Název smlouvy: CRM International a.s.
Číslo smlouvy: 4200000
Majitel smlouvy: CRM International a.s.
Účet: 99991111, CZK, CRM INTERNATIONAL A.S.
Částka: +3 300,00 CZK
Variabilní symbol: 1122334455
Zpráva příjemci: VS1122334455

Zůstatek na účtu po zaúčtování transakce: +11111 CZK.

S přáním krásného dne
Vaše ČSOB
';

        $csobMailParser = new CsobMailParser();
        $mailContents = $csobMailParser->parseMulti($email);

        $this->assertCount(1, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('99991111', $mailContent->accountNumber);
        $this->assertNull($mailContent->sourceAccountNumber);
        $this->assertEquals('CZK', $mailContent->currency);
        $this->assertEquals(3300.00, $mailContent->amount);
        $this->assertEquals('1122334455', $mailContent->vs);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->ss);
        $this->assertEquals(strtotime('18.12.2023'), $mailContent->transactionDate);
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

        // Should still parse other fields even with invalid date
        $this->assertCount(1, $result);
        $this->assertEquals(100.0, $result[0]->amount);
        $this->assertEquals('123456789', $result[0]->vs);
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
        // This kills the UnwrapArrayFilter mutant
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +100,00 CZK
Variabilní symbol: 111111

Dne invalid text that does not match the required pattern

Dne 26.9.2018 byla na účtu 987654321 zaúčtovaná transakce typu: Došlá platba.
Částka: +200,00 CZK
Variabilní symbol: 222222';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        // Should only return the 2 valid transactions, filtering out the 1 null
        $this->assertCount(2, $result);
        // array_filter preserves keys, so reindex the array
        $result = array_values($result);
        $this->assertEquals(100.0, $result[0]->amount);
        $this->assertEquals('111111', $result[0]->vs);
        $this->assertEquals(200.0, $result[1]->amount);
        $this->assertEquals('222222', $result[1]->vs);
    }

    #[Test]
    public function csobParserWithMultilineUnicodeContent()
    {
        // Test with multiline content that requires 'm' flag  (kills PregMatchRemoveFlags)
        // and unicode that requires 'u' flag (kills lines 37, 43, 53 mutants)
        $email = 'Nějaký předchozí text
Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: +1 234,56 CZK
Účet protistrany/IBAN: SK1122334455667788
Název protistrany: Příjemce s diakritikou ščřžýáíéúů
Zpráva příjemci: Testovací zpráva s čárkami
Variabilní symbol: 23456789
Další text pokračuje';

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
        // Tests Identical mutant on line 67 (vs === null vs vs !== null)
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
        // Tests PregMatchMatches mutants on lines 67, 74
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
        // Tests PregMatchRemoveFlags for /m flag on amount extraction (lines 43)
        $email = 'Text před
Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Nějaký další text
Částka: +1 234,56 CZK
Text za částkou';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertEquals(1234.56, $result[0]->amount);
        $this->assertEquals('CZK', $result[0]->currency);
    }

    #[Test]
    public function csobParserSourceAccountWithMultiline()
    {
        // Tests PregMatchRemoveFlags for /m and /u flags on line 37
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
        // Tests PregMatchRemoveFlags for /m and /u flags on line 53
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
        // Tests PregMatchRemoveFlags on line 61
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
        // Tests PregMatchRemoveFlags on line 70 (case insensitive flag)
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
        // Tests PregMatchRemoveFlags on lines 74 (/m flag)
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
        // This kills DecrementInteger/IncrementInteger mutants on line 47
        $email = 'Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.
Částka: -1 234,56 CZK
Variabilní symbol: 23456789';

        $parser = new CsobMailParser();
        $result = $parser->parseMulti($email);

        $this->assertCount(1, $result);
        $this->assertEquals(-1234.56, $result[0]->amount);
        $this->assertEquals('CZK', $result[0]->currency);
    }

    #[Test]
    public function csobParserWithReceiverMessageIndex()
    {
        // Test that verifies receiverMessage uses correct array index ($result[1])
        // This kills DecrementInteger mutant that changes $result[1] to $result[0]
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
        // This kills Identical mutant that changes === to !== on line 61
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
        // This kills Identical mutant on line 67
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

}
