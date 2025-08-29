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
        $this->assertEquals('123456789', $mailContent->getAccountNumber());
        $this->assertEquals('1122334455/9999', $mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(1234.56, $mailContent->getAmount());
        $this->assertEquals('23456789', $mailContent->getVs());
        $this->assertEquals('3456', $mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('25.9.2018'), $mailContent->getTransactionDate());
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
        $this->assertEquals('123456789', $mailContent->getAccountNumber());
        $this->assertEquals('1122334455/9999', $mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(1234.56, $mailContent->getAmount());
        $this->assertEquals('23456789', $mailContent->getVs());
        $this->assertEquals('3456', $mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('25.9.2018'), $mailContent->getTransactionDate());
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
        $this->assertEquals('123456789', $mailContent->getAccountNumber());
        $this->assertEquals('1122334455/9999', $mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(1234.56, $mailContent->getAmount());
        $this->assertEquals('23456789', $mailContent->getVs());
        $this->assertEquals('3456', $mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('25.9.2018'), $mailContent->getTransactionDate());

        $mailContent = $mailContents[1];
        $this->assertEquals('123456789', $mailContent->getAccountNumber());
        $this->assertEquals('9988776655/1111', $mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(987.65, $mailContent->getAmount());
        $this->assertEquals('78787878', $mailContent->getVs());
        $this->assertEquals('6789', $mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('25.9.2018'), $mailContent->getTransactionDate());
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
        $this->assertEquals('123456789', $mailContent->getAccountNumber());
        $this->assertEquals('1122334455/9999', $mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(414.00, $mailContent->getAmount());
        $this->assertEquals('23456789', $mailContent->getVs());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('18.8.2022'), $mailContent->getTransactionDate());
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
        $this->assertEquals('123456789', $mailContent->getAccountNumber());
        $this->assertEquals('NL56 ABNA 1234 5678 90', $mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(3581.70, $mailContent->getAmount());
        $this->assertEquals('23456789', $mailContent->getVs());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('18.8.2022'), $mailContent->getTransactionDate());
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
        $this->assertEquals('123456789', $mailContent->getAccountNumber());
        $this->assertEquals('SK99 1100 0000 7777 8888 9999', $mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(3300, $mailContent->getAmount());
        $this->assertEquals('6869282911', $mailContent->getVs());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('5.12.2023'), $mailContent->getTransactionDate());
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
        $this->assertEquals('123456789', $mailContent->getAccountNumber());
        $this->assertEquals('SK99 1100 0000 7777 8888 9999', $mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(3300, $mailContent->getAmount());
        $this->assertEquals('6869282912', $mailContent->getVs());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('5.12.2023'), $mailContent->getTransactionDate());
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
        $this->assertEquals('2233445566/2600', $mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(1440.00, $mailContent->getAmount());
        $this->assertEquals('0449274899', $mailContent->getVs());
        $this->assertEquals(strtotime('1.3.2024'), $mailContent->getTransactionDate());
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
        $this->assertEquals('123456789', $mailContent->getAccountNumber());
        $this->assertEquals('6012345678/2700', $mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(1980.00, $mailContent->getAmount());
        $this->assertEquals('3723199116', $mailContent->getVs());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('21.2.2024'), $mailContent->getTransactionDate());
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
        $this->assertEquals('123456789', $mailContent->getAccountNumber());
        $this->assertEquals('6012345678/2700', $mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(1980.00, $mailContent->getAmount());
        $this->assertEquals('3723199116', $mailContent->getVs());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('21.2.2024'), $mailContent->getTransactionDate());
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
        $this->assertEquals('123456789', $mailContent->getAccountNumber());
        $this->assertEquals('6012345678/2700', $mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(2700.00, $mailContent->getAmount());
        $this->assertEquals('3723199333', $mailContent->getVs());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('31.1.2024'), $mailContent->getTransactionDate());
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
        $this->assertEquals('99991111', $mailContent->getAccountNumber());
        $this->assertNull($mailContent->getSourceAccountNumber());
        $this->assertEquals('CZK', $mailContent->getCurrency());
        $this->assertEquals(3300.00, $mailContent->getAmount());
        $this->assertEquals('1122334455', $mailContent->getVs());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getSs());
        $this->assertEquals(strtotime('18.12.2023'), $mailContent->getTransactionDate());
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
        $this->assertEquals(100.0, $result[0]->getAmount());
        $this->assertEquals('123456789', $result[0]->getVs());
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
}
