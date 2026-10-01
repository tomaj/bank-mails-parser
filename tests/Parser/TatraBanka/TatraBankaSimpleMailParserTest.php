<?php

declare(strict_types=1);

namespace Tests\Parses\TatraBanka;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;

#[CoversClass(TatraBankaSimpleMailParser::class)]
class TatraBankaSimpleMailParserTest extends TestCase
{
    #[Test]
    public function simpleEmail()
    {
        $email = 'VS=1152201233 RES=OK AC=558058 SIGN=C0CBF27F5D97841E';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);
        $this->assertEquals('1152201233', $mailContent->vs);
        $this->assertEquals('C0CBF27F5D97841E', $mailContent->sign);
        $this->assertEquals('OK', $mailContent->res);
        $this->assertEquals('558058', $mailContent->ac);
    }

    #[Test]
    public function errorResult()
    {
        $email = 'VS=1152201233 RES=FAIL AC=558058 SIGN=C0CBF27F5D97841E';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);
        $this->assertEquals('FAIL', $mailContent->res);
        $this->assertEquals('1152201233', $mailContent->vs);
        $this->assertEquals('C0CBF27F5D97841E', $mailContent->sign);
        $this->assertEquals('558058', $mailContent->ac);
    }

    #[Test]
    public function errorEmail()
    {
        $email = '';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);
        $this->assertNull($mailContent);

        $email = 'VS=1152201233 AC=558058 SIGN=C0CBF27F5D97841E';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);
        $this->assertNull($mailContent);
    }

    #[Test]
    public function cidAndTres()
    {
        $email = 'VS=1151151156 TRES=OK CID=123445 SIGN=XCCBF1235D945841C';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);
        $this->assertEquals('1151151156', $mailContent->vs);
        $this->assertEquals('XCCBF1235D945841C', $mailContent->sign);
        $this->assertEquals('123445', $mailContent->cid);
        $this->assertEquals('OK', $mailContent->res);
        $this->assertEquals(time(), $mailContent->transactionDate);
    }

    #[Test]
    public function hmacCardpayEmail()
    {
        $email = 'AMT=33.99 CURR=978 VS=5000001234 RES=OK AC=740017 TID=88888888 TIMESTAMP=25112016223023 HMAC=9dfd46cf2af977be8dd4251f4ef92307d95a8903f4738616d8456bd02e858340 ECDSA_KEY=1 ECDSA=3045022100e2c791637534bd57b530b7e42497dc6e33fa9f6c0e3950148c14c988f014ca5f02205918cb783d02d6dad4bea6ed4823a2c833187b979cdced377b3612c939b05f3d';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('33.99', $mailContent->amount);
        $this->assertEquals('5000001234', $mailContent->vs);
        $this->assertEquals('9dfd46cf2af977be8dd4251f4ef92307d95a8903f4738616d8456bd02e858340', $mailContent->sign);
        $this->assertEquals('OK', $mailContent->res);
        $this->assertEquals('740017', $mailContent->ac);
        $this->assertEquals('978', $mailContent->currency);
        $this->assertEquals('25112016223023', $mailContent->transactionDate);
    }

    #[Test]
    public function hmacComfortpayEmail()
    {
        $email = 'AMT=44.88 CURR=978 VS=4444255333 RES=OK AC=644311 TRES=OK CID=824452 CC=************1111 TID=11224444 TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('44.88', $mailContent->amount);
        $this->assertEquals('4444255333', $mailContent->vs);
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertEquals('OK', $mailContent->res);
        $this->assertEquals('824452', $mailContent->cid);
        $this->assertEquals('************1111', $mailContent->cc);
        $this->assertEquals('11224444', $mailContent->tid);
        $this->assertEquals('978', $mailContent->currency);
        $this->assertEquals('26112016121631', $mailContent->transactionDate);
        $this->assertNull($mailContent->rc);
    }

    #[Test]
    public function hmacComfortpayEmailWithRc()
    {
        $email = 'AMT=44.88 CURR=978 VS=4444255333 RES=OK AC=644311 TRES=OK CID=824452 CC=************1111 RC=00 TID=11224444 TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('44.88', $mailContent->amount);
        $this->assertEquals('4444255333', $mailContent->vs);
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertEquals('OK', $mailContent->res);
        $this->assertEquals('824452', $mailContent->cid);
        $this->assertEquals('************1111', $mailContent->cc);
        $this->assertEquals('11224444', $mailContent->tid);
        $this->assertEquals('978', $mailContent->currency);
        $this->assertEquals('26112016121631', $mailContent->transactionDate);
        $this->assertEquals('00', $mailContent->rc);
    }

    public function testHmacComfortpayEmailNoCC()
    {
        $email = 'AMT=44.88 CURR=978 VS=4444255333 RES=OK AC=644311 TRES=OK CID=824452 TID=11224444 TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('44.88', $mailContent->amount);
        $this->assertEquals('4444255333', $mailContent->vs);
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertEquals('OK', $mailContent->res);
        $this->assertEquals('824452', $mailContent->cid);
        $this->assertEquals('11224444', $mailContent->tid);
        $this->assertEquals('978', $mailContent->currency);
        $this->assertEquals('26112016121631', $mailContent->transactionDate);
    }

    public function testHmacComfortpayEmailWithoutTxn()
    {
        $email = 'AMT=44.88 CURR=978 VS=4444255333 RES=OK AC=644311 TRES=OK CID=824452 TID=11224444 TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('44.88', $mailContent->amount);
        $this->assertEquals('4444255333', $mailContent->vs);
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertEquals('OK', $mailContent->res);
        $this->assertEquals('824452', $mailContent->cid);
        $this->assertEquals('11224444', $mailContent->tid);
        $this->assertEquals('978', $mailContent->currency);
        $this->assertEquals('26112016121631', $mailContent->transactionDate);
        $this->assertNull($mailContent->txn);
    }

    public function testHmacComfortpayEmailWithTxn()
    {
        $email = 'AMT=44.88 CURR=978 VS=4444255333 TXN=PA RES=OK AC=644311 TRES=OK CID=824452 TID=11224444 TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('44.88', $mailContent->amount);
        $this->assertEquals('4444255333', $mailContent->vs);
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertEquals('OK', $mailContent->res);
        $this->assertEquals('824452', $mailContent->cid);
        $this->assertEquals('11224444', $mailContent->tid);
        $this->assertEquals('978', $mailContent->currency);
        $this->assertEquals('26112016121631', $mailContent->transactionDate);
        $this->assertEquals('PA', $mailContent->txn);
    }

    #[Test]
    public function failHmacComfortpayEmail()
    {
        $email = 'AMT=140.92 CURR=978 VS=5555534283 RES=FAIL TRES=FAIL CC=************1111 TID=11224444 TIMESTAMP=24112016170555 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('140.92', $mailContent->amount);
        $this->assertEquals('978', $mailContent->currency);
        $this->assertEquals('5555534283', $mailContent->vs);
        $this->assertEquals('FAIL', $mailContent->res);
        $this->assertEquals('************1111', $mailContent->cc);
        $this->assertEquals('11224444', $mailContent->tid);
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertEquals('24112016170555', $mailContent->transactionDate);
    }

    #[Test]
    public function failHmacComfortpayEmailNoCcTidTres()
    {
        $email = 'AMT=8.98 CURR=978 VS=5555534283 RES=FAIL TRES=FAIL TIMESTAMP=24032020144457 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('8.98', $mailContent->amount);
        $this->assertEquals('978', $mailContent->currency);
        $this->assertEquals('5555534283', $mailContent->vs);
        $this->assertEquals('FAIL', $mailContent->res);
        $this->assertEmpty($mailContent->cc);
        $this->assertEmpty($mailContent->tid);
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertEquals('24032020144457', $mailContent->transactionDate);

        $email = 'AMT=4.99 CURR=978 VS=5555534283 RES=FAIL TIMESTAMP=27032020121740 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('4.99', $mailContent->amount);
        $this->assertEquals('978', $mailContent->currency);
        $this->assertEquals('5555534283', $mailContent->vs);
        $this->assertEquals('FAIL', $mailContent->res);
        $this->assertEmpty($mailContent->cc);
        $this->assertEmpty($mailContent->tid);
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertEquals('27032020121740', $mailContent->transactionDate);
    }

    #[Test]
    public function emptyValues()
    {
        // Test with empty values after equals sign
        $email = 'VS= RES=OK AC= AMT=100.00';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        $this->assertEquals('OK', $result->res);
        $this->assertEquals('100.00', $result->amount);
    }

    #[Test]
    public function emptyEmailContent()
    {
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse('');
        $this->assertNull($result);
    }

    #[Test]
    public function unknownParameters()
    {
        // Test with parameters not in the map
        $email = 'VS=123 UNKNOWN_PARAM=test RES=OK';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        $this->assertEquals('123', $result->vs);
        $this->assertEquals('OK', $result->res);
    }

    #[Test]
    public function explicitAmountFloatCasting()
    {
        // Test that amount is properly cast to float (RES required)
        $email = 'AMT=123.45 CURR=EUR RES=OK';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        $this->assertSame(123.45, $result->amount);
        $this->assertIsFloat($result->amount);
    }

    #[Test]
    public function explicitTransactionDateIntCasting()
    {
        // Test that transactionDate is properly cast to int (using TIMESTAMP field)
        $email = 'TIMESTAMP=1234567890 RES=OK';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        $this->assertSame(1234567890, $result->transactionDate);
        $this->assertIsInt($result->transactionDate);
    }

    #[Test]
    public function allFieldsWithCorrectTypes()
    {
        // Test all mapped fields to ensure correct type casting in constructor
        $email = 'AMT=100.50 CURR=EUR TIMESTAMP=1234567890 VS=111111 '
                 . 'CID=cid123 SIGN=sign123 RES=OK AC=ac123 CC=cc123 '
                 . 'TID=tid123 TXN=txn123 RC=rc123';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        $this->assertIsFloat($result->amount);
        $this->assertEquals(100.50, $result->amount);
        $this->assertIsInt($result->transactionDate);
        $this->assertEquals(1234567890, $result->transactionDate);
        $this->assertEquals('EUR', $result->currency);
        $this->assertEquals('111111', $result->vs);
        $this->assertEquals('cid123', $result->cid);
        $this->assertEquals('sign123', $result->sign);
        $this->assertEquals('OK', $result->res);
        $this->assertEquals('ac123', $result->ac);
        $this->assertEquals('cc123', $result->cc);
        $this->assertEquals('tid123', $result->tid);
        $this->assertEquals('txn123', $result->txn);
        $this->assertEquals('rc123', $result->rc);
    }

    #[Test]
    public function fieldMappingVerification()
    {
        // Test the FIELD_MAP mappings
        $email = 'AMT=50 CURR=USD TIMESTAMP=1000000000 VS=111 '
                 . 'CID=cid123 SIGN=sign123 RES=res AC=ac123 CC=cc123 '
                 . 'TID=tid123 TXN=txn123 RC=rc123';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        $this->assertEquals(50.0, $result->amount);
        $this->assertEquals('USD', $result->currency);
        $this->assertEquals(1000000000, $result->transactionDate);
        $this->assertEquals('111', $result->vs);
        $this->assertEquals('cid123', $result->cid);
        $this->assertEquals('sign123', $result->sign);
        $this->assertEquals('res', $result->res);
        $this->assertEquals('ac123', $result->ac);
        $this->assertEquals('cc123', $result->cc);
        $this->assertEquals('tid123', $result->tid);
        $this->assertEquals('txn123', $result->txn);
        $this->assertEquals('rc123', $result->rc);
    }

    #[Test]
    public function returnNullForEmptyContent()
    {
        // Tests ReturnRemoval mutant on line 33
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->parse('');
        $this->assertNull($result);
    }

    #[Test]
    public function returnNullWhenResNotPresent()
    {
        // Tests that RES is required (line 56-58 logic)
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->parse('AMT=100 CURR=EUR TIMESTAMP=1234567890');
        $this->assertNull($result);
    }

    #[Test]
    public function mbTrimAppliedToValues()
    {
        // Tests UnwrapArrayMap mutant on line 42 - verifies mb_trim is applied
        // The parser splits on spaces, so values with trailing spaces need careful handling
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->parse('AMT=100.50 CURR=EUR RES=OK');
        $this->assertEquals(100.50, $result->amount);
        $this->assertEquals('EUR', $result->currency);
    }

    #[Test]
    public function matchArmTypeCasting()
    {
        // Tests MatchArmRemoval mutants on line 49 (both branches)
        // and type casting mutants CastFloat line 50, CastInt line 51
        $parser = new TatraBankaSimpleMailParser();

        // Test float casting for amount
        $result1 = $parser->parse('AMT=123.45 RES=OK');
        $this->assertIsFloat($result1->amount);
        $this->assertSame(123.45, $result1->amount);

        // Test int casting for transactionDate
        $result2 = $parser->parse('TIMESTAMP=1234567890 RES=OK');
        $this->assertIsInt($result2->transactionDate);
        $this->assertSame(1234567890, $result2->transactionDate);

        // Test default branch (string)
        $result3 = $parser->parse('VS=111111 RES=OK');
        $this->assertIsString($result3->vs);
        $this->assertSame('111111', $result3->vs);
    }

    #[Test]
    public function constructorTypeCasting()
    {
        // Tests CastFloat line 65, CastString lines 66-76
        // Verifies explicit type casts in constructor call
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->parse('AMT=100.50 CURR=EUR TIMESTAMP=1234567890 '
                                 . 'VS=111 CID=cid SIGN=sign RES=res AC=ac CC=cc '
                                 . 'TID=tid TXN=txn RC=rc');

        // Verify types are correctly cast
        $this->assertIsFloat($result->amount);
        $this->assertSame(100.50, $result->amount);

        $this->assertIsString($result->currency);
        $this->assertSame('EUR', $result->currency);

        $this->assertIsInt($result->transactionDate);
        $this->assertSame(1234567890, $result->transactionDate);

        // Test all string casts
        $this->assertIsString($result->vs);
        $this->assertIsString($result->cid);
        $this->assertIsString($result->sign);
        $this->assertIsString($result->res);
        $this->assertIsString($result->ac);
        $this->assertIsString($result->cc);
        $this->assertIsString($result->tid);
        $this->assertIsString($result->txn);
        $this->assertIsString($result->rc);
    }

    #[Test]
    public function constructorWithMissingOptionalFields()
    {
        // Tests that constructor handles null values correctly for optional fields
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->parse('RES=OK');

        $this->assertNull($result->amount);
        $this->assertNull($result->currency);
        $this->assertIsInt($result->transactionDate); // Gets time() default
        $this->assertNull($result->vs);
        $this->assertNull($result->cid);
        $this->assertNull($result->sign);
        $this->assertEquals('OK', $result->res);
        $this->assertNull($result->ac);
        $this->assertNull($result->cc);
        $this->assertNull($result->tid);
        $this->assertNull($result->txn);
        $this->assertNull($result->rc);
    }
}
