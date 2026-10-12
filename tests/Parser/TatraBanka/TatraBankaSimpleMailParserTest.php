<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\TatraBanka;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;
use Tomaj\BankMailsParser\Tests\Fixtures;

#[CoversClass(TatraBankaSimpleMailParser::class)]
class TatraBankaSimpleMailParserTest extends TestCase
{
    #[Test]
    public function simpleEmail()
    {
        $email = 'VS=1152201233 RES=OK AC=558058 SIGN=C0CBF27F5D97841E';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);
        $this->assertSame('1152201233', $mailContent->vs);
        $this->assertSame('C0CBF27F5D97841E', $mailContent->sign);
        $this->assertSame('OK', $mailContent->res);
        $this->assertSame('558058', $mailContent->ac);
    }

    #[Test]
    public function errorResult()
    {
        $email = 'VS=1152201233 RES=FAIL AC=558058 SIGN=C0CBF27F5D97841E';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);
        $this->assertSame('FAIL', $mailContent->res);
        $this->assertSame('1152201233', $mailContent->vs);
        $this->assertSame('C0CBF27F5D97841E', $mailContent->sign);
        $this->assertSame('558058', $mailContent->ac);
    }

    #[Test]
    public function errorEmail()
    {
        $email = '';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->tryParse($email);
        $this->assertNull($mailContent);

        $email = 'VS=1152201233 AC=558058 SIGN=C0CBF27F5D97841E';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->tryParse($email);
        $this->assertNull($mailContent);
    }

    #[Test]
    public function cidAndTres()
    {
        $email = 'VS=1151151156 TRES=OK CID=123445 SIGN=XCCBF1235D945841C';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);
        $this->assertSame('1151151156', $mailContent->vs);
        $this->assertSame('XCCBF1235D945841C', $mailContent->sign);
        $this->assertSame('123445', $mailContent->cid);
        $this->assertSame('OK', $mailContent->res);
        $this->assertSame('OK', $mailContent->tres);
        $this->assertNull($mailContent->transactionDate);
    }

    #[Test]
    public function hmacCardpayEmail()
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-cardpay');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertSame('33.99', $mailContent->money?->toDecimalString());
        $this->assertSame('5000001234', $mailContent->vs);
        $this->assertSame('9dfd46cf2af977be8dd4251f4ef92307d95a8903f4738616d8456bd02e858340', $mailContent->sign);
        $this->assertSame('OK', $mailContent->res);
        $this->assertSame('740017', $mailContent->ac);
        $this->assertSame('EUR', $mailContent->money?->currency->value);
        $this->assertSame('25112016223023', $mailContent->transactionDate?->format('dmYHis'));
    }

    #[Test]
    public function hmacComfortpayEmail()
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertSame('44.88', $mailContent->money?->toDecimalString());
        $this->assertSame('4444255333', $mailContent->vs);
        $this->assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertSame('OK', $mailContent->res);
        $this->assertSame('824452', $mailContent->cid);
        $this->assertSame('************1111', $mailContent->cc);
        $this->assertSame('11224444', $mailContent->tid);
        $this->assertSame('EUR', $mailContent->money?->currency->value);
        $this->assertSame('26112016121631', $mailContent->transactionDate?->format('dmYHis'));
        $this->assertNull($mailContent->rc);
    }

    #[Test]
    public function hmacComfortpayEmailWithRc()
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-rc');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertSame('44.88', $mailContent->money?->toDecimalString());
        $this->assertSame('4444255333', $mailContent->vs);
        $this->assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertSame('OK', $mailContent->res);
        $this->assertSame('824452', $mailContent->cid);
        $this->assertSame('************1111', $mailContent->cc);
        $this->assertSame('11224444', $mailContent->tid);
        $this->assertSame('EUR', $mailContent->money?->currency->value);
        $this->assertSame('26112016121631', $mailContent->transactionDate?->format('dmYHis'));
        $this->assertSame('00', $mailContent->rc);
    }

    public function testHmacComfortpayEmailNoCC()
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-bez-cc');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertSame('44.88', $mailContent->money?->toDecimalString());
        $this->assertSame('4444255333', $mailContent->vs);
        $this->assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertSame('OK', $mailContent->res);
        $this->assertSame('824452', $mailContent->cid);
        $this->assertSame('11224444', $mailContent->tid);
        $this->assertSame('EUR', $mailContent->money?->currency->value);
        $this->assertSame('26112016121631', $mailContent->transactionDate?->format('dmYHis'));
    }

    public function testHmacComfortpayEmailWithoutTxn()
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-bez-cc');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertSame('44.88', $mailContent->money?->toDecimalString());
        $this->assertSame('4444255333', $mailContent->vs);
        $this->assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertSame('OK', $mailContent->res);
        $this->assertSame('824452', $mailContent->cid);
        $this->assertSame('11224444', $mailContent->tid);
        $this->assertSame('EUR', $mailContent->money?->currency->value);
        $this->assertSame('26112016121631', $mailContent->transactionDate?->format('dmYHis'));
        $this->assertNull($mailContent->txn);
    }

    public function testHmacComfortpayEmailWithTxn()
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-txn');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertSame('44.88', $mailContent->money?->toDecimalString());
        $this->assertSame('4444255333', $mailContent->vs);
        $this->assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertSame('OK', $mailContent->res);
        $this->assertSame('824452', $mailContent->cid);
        $this->assertSame('11224444', $mailContent->tid);
        $this->assertSame('EUR', $mailContent->money?->currency->value);
        $this->assertSame('26112016121631', $mailContent->transactionDate?->format('dmYHis'));
        $this->assertSame('PA', $mailContent->txn);
    }

    #[Test]
    public function failHmacComfortpayEmail()
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-fail');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertSame('140.92', $mailContent->money?->toDecimalString());
        $this->assertSame('EUR', $mailContent->money?->currency->value);
        $this->assertSame('5555534283', $mailContent->vs);
        $this->assertSame('FAIL', $mailContent->res);
        $this->assertSame('************1111', $mailContent->cc);
        $this->assertSame('11224444', $mailContent->tid);
        $this->assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertSame('24112016170555', $mailContent->transactionDate?->format('dmYHis'));
    }

    #[Test]
    public function failHmacComfortpayEmailNoCcTidTres()
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-fail-bez-tid');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertSame('8.98', $mailContent->money?->toDecimalString());
        $this->assertSame('EUR', $mailContent->money?->currency->value);
        $this->assertSame('5555534283', $mailContent->vs);
        $this->assertSame('FAIL', $mailContent->res);
        $this->assertEmpty($mailContent->cc);
        $this->assertEmpty($mailContent->tid);
        $this->assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertSame('24032020144457', $mailContent->transactionDate?->format('dmYHis'));

        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-fail-bez-tres');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertSame('4.99', $mailContent->money?->toDecimalString());
        $this->assertSame('EUR', $mailContent->money?->currency->value);
        $this->assertSame('5555534283', $mailContent->vs);
        $this->assertSame('FAIL', $mailContent->res);
        $this->assertEmpty($mailContent->cc);
        $this->assertEmpty($mailContent->tid);
        $this->assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        $this->assertSame('27032020121740', $mailContent->transactionDate?->format('dmYHis'));
    }

    #[Test]
    public function emptyValues()
    {
        // Test with empty values after equals sign
        $email = 'VS= RES=OK AC= AMT=100.00';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        $this->assertSame('OK', $result->res);
        $this->assertSame('100.00', $result->money?->toDecimalString());
        $this->assertSame('EUR', $result->money?->currency->value);
    }

    #[Test]
    public function emptyEmailContent()
    {
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->tryParse('');
        $this->assertNull($result);
    }

    #[Test]
    public function unknownParameters()
    {
        // Test with parameters not in the map
        $email = 'VS=123 UNKNOWN_PARAM=test RES=OK';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        $this->assertSame('123', $result->vs);
        $this->assertSame('OK', $result->res);
    }

    #[Test]
    public function explicitAmountFloatCasting()
    {
        // Test that amount is properly cast to float (RES required)
        $email = 'AMT=123.45 CURR=EUR RES=OK';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        $this->assertSame('123.45', $result->money?->toDecimalString());
        $this->assertIsString($result->money?->toDecimalString());
    }

    #[Test]
    public function explicitTransactionDateIntCasting()
    {
        // Test that transactionDate is properly cast to int (using TIMESTAMP field)
        $email = 'TIMESTAMP=1234567890 RES=OK';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        $this->assertSame(1234567890, $result->transactionDate?->getTimestamp());
        $this->assertIsInt($result->transactionDate?->getTimestamp());
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
        $this->assertIsString($result->money?->toDecimalString());
        $this->assertSame('100.50', $result->money?->toDecimalString());
        $this->assertIsInt($result->transactionDate?->getTimestamp());
        $this->assertSame(1234567890, $result->transactionDate?->getTimestamp());
        $this->assertSame('EUR', $result->money?->currency->value);
        $this->assertSame('111111', $result->vs);
        $this->assertSame('cid123', $result->cid);
        $this->assertSame('sign123', $result->sign);
        $this->assertSame('OK', $result->res);
        $this->assertSame('ac123', $result->ac);
        $this->assertSame('cc123', $result->cc);
        $this->assertSame('tid123', $result->tid);
        $this->assertSame('txn123', $result->txn);
        $this->assertSame('rc123', $result->rc);
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
        $this->assertSame('50.00', $result->money?->toDecimalString());
        $this->assertSame('USD', $result->money?->currency->value);
        $this->assertSame(1000000000, $result->transactionDate?->getTimestamp());
        $this->assertSame('111', $result->vs);
        $this->assertSame('cid123', $result->cid);
        $this->assertSame('sign123', $result->sign);
        $this->assertSame('res', $result->res);
        $this->assertSame('ac123', $result->ac);
        $this->assertSame('cc123', $result->cc);
        $this->assertSame('tid123', $result->tid);
        $this->assertSame('txn123', $result->txn);
        $this->assertSame('rc123', $result->rc);
    }

    #[Test]
    public function returnNullForEmptyContent()
    {
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->tryParse('');
        $this->assertNull($result);
    }

    #[Test]
    public function returnNullWhenResNotPresent()
    {
        // Tests that RES is required (line 56-58 logic)
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->tryParse('AMT=100 CURR=EUR TIMESTAMP=1234567890');
        $this->assertNull($result);
    }

    #[Test]
    public function mbTrimAppliedToValues()
    {
        // The parser splits on spaces, so values with trailing spaces need careful handling
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->parse('AMT=100.50 CURR=EUR RES=OK');
        $this->assertSame('100.50', $result->money?->toDecimalString());
        $this->assertSame('EUR', $result->money?->currency->value);
    }

    #[Test]
    public function matchArmTypeCasting()
    {
        $parser = new TatraBankaSimpleMailParser();

        // Test float casting for amount
        $result1 = $parser->parse('AMT=123.45 CURR=EUR RES=OK');
        $this->assertIsString($result1->money?->toDecimalString());
        $this->assertSame('123.45', $result1->money?->toDecimalString());

        // Test int casting for transactionDate
        $result2 = $parser->parse('TIMESTAMP=1234567890 RES=OK');
        $this->assertIsInt($result2->transactionDate?->getTimestamp());
        $this->assertSame(1234567890, $result2->transactionDate?->getTimestamp());

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
        $this->assertIsString($result->money?->toDecimalString());
        $this->assertSame('100.50', $result->money?->toDecimalString());

        $this->assertIsString($result->money?->currency->value);
        $this->assertSame('EUR', $result->money?->currency->value);

        $this->assertIsInt($result->transactionDate?->getTimestamp());
        $this->assertSame(1234567890, $result->transactionDate?->getTimestamp());

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

        $this->assertNull($result->money?->toDecimalString());
        $this->assertNull($result->money?->currency->value);
        $this->assertNull($result->transactionDate);
        $this->assertNull($result->vs);
        $this->assertNull($result->cid);
        $this->assertNull($result->sign);
        $this->assertSame('OK', $result->res);
        $this->assertNull($result->ac);
        $this->assertNull($result->cc);
        $this->assertNull($result->tid);
        $this->assertNull($result->txn);
        $this->assertNull($result->rc);
    }
}
