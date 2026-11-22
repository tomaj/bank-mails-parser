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
    public function simpleEmail(): void
    {
        $email = 'VS=1152201233 RES=OK AC=558058 SIGN=C0CBF27F5D97841E';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);
        self::assertSame('1152201233', $mailContent->vs);
        self::assertSame('C0CBF27F5D97841E', $mailContent->sign);
        self::assertSame('OK', $mailContent->res);
        self::assertSame('558058', $mailContent->ac);
    }

    #[Test]
    public function errorResult(): void
    {
        $email = 'VS=1152201233 RES=FAIL AC=558058 SIGN=C0CBF27F5D97841E';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);
        self::assertSame('FAIL', $mailContent->res);
        self::assertSame('1152201233', $mailContent->vs);
        self::assertSame('C0CBF27F5D97841E', $mailContent->sign);
        self::assertSame('558058', $mailContent->ac);
    }

    #[Test]
    public function errorEmail(): void
    {
        $email = '';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->tryParse($email);
        self::assertNull($mailContent);

        $email = 'VS=1152201233 AC=558058 SIGN=C0CBF27F5D97841E';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->tryParse($email);
        self::assertNull($mailContent);
    }

    #[Test]
    public function cidAndTres(): void
    {
        $email = 'VS=1151151156 TRES=OK CID=123445 SIGN=XCCBF1235D945841C';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);
        self::assertSame('1151151156', $mailContent->vs);
        self::assertSame('XCCBF1235D945841C', $mailContent->sign);
        self::assertSame('123445', $mailContent->cid);
        self::assertSame('OK', $mailContent->res);
        self::assertSame('OK', $mailContent->tres);
        self::assertNull($mailContent->transactionDate);
    }

    #[Test]
    public function hmacCardpayEmail(): void
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-cardpay');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        self::assertSame('33.99', $mailContent->money?->toDecimalString());
        self::assertSame('5000001234', $mailContent->vs);
        self::assertSame('9dfd46cf2af977be8dd4251f4ef92307d95a8903f4738616d8456bd02e858340', $mailContent->sign);
        self::assertSame('OK', $mailContent->res);
        self::assertSame('740017', $mailContent->ac);
        self::assertSame('EUR', $mailContent->money->currency->value);
        self::assertSame('25112016223023', $mailContent->transactionDate?->format('dmYHis'));
    }

    #[Test]
    public function hmacComfortpayEmail(): void
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        self::assertSame('44.88', $mailContent->money?->toDecimalString());
        self::assertSame('4444255333', $mailContent->vs);
        self::assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        self::assertSame('OK', $mailContent->res);
        self::assertSame('824452', $mailContent->cid);
        self::assertSame('************1111', $mailContent->cc);
        self::assertSame('11224444', $mailContent->tid);
        self::assertSame('EUR', $mailContent->money->currency->value);
        self::assertSame('26112016121631', $mailContent->transactionDate?->format('dmYHis'));
        self::assertNull($mailContent->rc);
    }

    #[Test]
    public function hmacComfortpayEmailWithRc(): void
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-rc');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        self::assertSame('44.88', $mailContent->money?->toDecimalString());
        self::assertSame('4444255333', $mailContent->vs);
        self::assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        self::assertSame('OK', $mailContent->res);
        self::assertSame('824452', $mailContent->cid);
        self::assertSame('************1111', $mailContent->cc);
        self::assertSame('11224444', $mailContent->tid);
        self::assertSame('EUR', $mailContent->money->currency->value);
        self::assertSame('26112016121631', $mailContent->transactionDate?->format('dmYHis'));
        self::assertSame('00', $mailContent->rc);
    }

    public function testHmacComfortpayEmailNoCC(): void
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-bez-cc');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        self::assertSame('44.88', $mailContent->money?->toDecimalString());
        self::assertSame('4444255333', $mailContent->vs);
        self::assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        self::assertSame('OK', $mailContent->res);
        self::assertSame('824452', $mailContent->cid);
        self::assertSame('11224444', $mailContent->tid);
        self::assertSame('EUR', $mailContent->money->currency->value);
        self::assertSame('26112016121631', $mailContent->transactionDate?->format('dmYHis'));
    }

    public function testHmacComfortpayEmailWithoutTxn(): void
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-bez-cc');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        self::assertSame('44.88', $mailContent->money?->toDecimalString());
        self::assertSame('4444255333', $mailContent->vs);
        self::assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        self::assertSame('OK', $mailContent->res);
        self::assertSame('824452', $mailContent->cid);
        self::assertSame('11224444', $mailContent->tid);
        self::assertSame('EUR', $mailContent->money->currency->value);
        self::assertSame('26112016121631', $mailContent->transactionDate?->format('dmYHis'));
        self::assertNull($mailContent->txn);
    }

    public function testHmacComfortpayEmailWithTxn(): void
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-txn');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        self::assertSame('44.88', $mailContent->money?->toDecimalString());
        self::assertSame('4444255333', $mailContent->vs);
        self::assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        self::assertSame('OK', $mailContent->res);
        self::assertSame('824452', $mailContent->cid);
        self::assertSame('11224444', $mailContent->tid);
        self::assertSame('EUR', $mailContent->money->currency->value);
        self::assertSame('26112016121631', $mailContent->transactionDate?->format('dmYHis'));
        self::assertSame('PA', $mailContent->txn);
    }

    #[Test]
    public function failHmacComfortpayEmail(): void
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-fail');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        self::assertSame('140.92', $mailContent->money?->toDecimalString());
        self::assertSame('EUR', $mailContent->money->currency->value);
        self::assertSame('5555534283', $mailContent->vs);
        self::assertSame('FAIL', $mailContent->res);
        self::assertSame('************1111', $mailContent->cc);
        self::assertSame('11224444', $mailContent->tid);
        self::assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        self::assertSame('24112016170555', $mailContent->transactionDate?->format('dmYHis'));
    }

    #[Test]
    public function failHmacComfortpayEmailNoCcTidTres(): void
    {
        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-fail-bez-tid');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        self::assertSame('8.98', $mailContent->money?->toDecimalString());
        self::assertSame('EUR', $mailContent->money->currency->value);
        self::assertSame('5555534283', $mailContent->vs);
        self::assertSame('FAIL', $mailContent->res);
        self::assertNull($mailContent->cc);
        self::assertNull($mailContent->tid);
        self::assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        self::assertSame('24032020144457', $mailContent->transactionDate?->format('dmYHis'));

        $email = Fixtures::load('tatrabanka-cardpay/hmac-comfortpay-fail-bez-tres');
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        self::assertSame('4.99', $mailContent->money?->toDecimalString());
        self::assertSame('EUR', $mailContent->money->currency->value);
        self::assertSame('5555534283', $mailContent->vs);
        self::assertSame('FAIL', $mailContent->res);
        self::assertNull($mailContent->cc);
        self::assertNull($mailContent->tid);
        self::assertSame('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->sign);
        self::assertSame('27032020121740', $mailContent->transactionDate?->format('dmYHis'));
    }

    #[Test]
    public function emptyValues(): void
    {
        // Test with empty values after equals sign
        $email = 'VS= RES=OK AC= AMT=100.00';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        self::assertSame('OK', $result->res);
        self::assertSame('100.00', $result->money?->toDecimalString());
        self::assertSame('EUR', $result->money->currency->value);
    }

    #[Test]
    public function emptyEmailContent(): void
    {
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->tryParse('');
        self::assertNull($result);
    }

    #[Test]
    public function unknownParameters(): void
    {
        // Test with parameters not in the map
        $email = 'VS=123 UNKNOWN_PARAM=test RES=OK';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        self::assertSame('123', $result->vs);
        self::assertSame('OK', $result->res);
    }

    #[Test]
    public function explicitAmountFloatCasting(): void
    {
        // Test that amount is properly cast to float (RES required)
        $email = 'AMT=123.45 CURR=EUR RES=OK';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        self::assertSame('123.45', $result->money?->toDecimalString());
    }

    #[Test]
    public function explicitTransactionDateIntCasting(): void
    {
        // Test that transactionDate is properly cast to int (using TIMESTAMP field)
        $email = 'TIMESTAMP=1234567890 RES=OK';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        self::assertSame(1234567890, $result->transactionDate?->getTimestamp());
    }

    #[Test]
    public function allFieldsWithCorrectTypes(): void
    {
        // Test all mapped fields to ensure correct type casting in constructor
        $email = 'AMT=100.50 CURR=EUR TIMESTAMP=1234567890 VS=111111 '
                 . 'CID=cid123 SIGN=sign123 RES=OK AC=ac123 CC=cc123 '
                 . 'TID=tid123 TXN=txn123 RC=rc123';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        self::assertSame('100.50', $result->money?->toDecimalString());
        self::assertSame(1234567890, $result->transactionDate?->getTimestamp());
        self::assertSame('EUR', $result->money->currency->value);
        self::assertSame('111111', $result->vs);
        self::assertSame('cid123', $result->cid);
        self::assertSame('sign123', $result->sign);
        self::assertSame('OK', $result->res);
        self::assertSame('ac123', $result->ac);
        self::assertSame('cc123', $result->cc);
        self::assertSame('tid123', $result->tid);
        self::assertSame('txn123', $result->txn);
        self::assertSame('rc123', $result->rc);
    }

    #[Test]
    public function fieldMappingVerification(): void
    {
        // Test the FIELD_MAP mappings
        $email = 'AMT=50 CURR=USD TIMESTAMP=1000000000 VS=111 '
                 . 'CID=cid123 SIGN=sign123 RES=res AC=ac123 CC=cc123 '
                 . 'TID=tid123 TXN=txn123 RC=rc123';
        $parser = new TatraBankaSimpleMailParser();

        $result = $parser->parse($email);
        self::assertSame('50.00', $result->money?->toDecimalString());
        self::assertSame('USD', $result->money->currency->value);
        self::assertSame(1000000000, $result->transactionDate?->getTimestamp());
        self::assertSame('111', $result->vs);
        self::assertSame('cid123', $result->cid);
        self::assertSame('sign123', $result->sign);
        self::assertSame('res', $result->res);
        self::assertSame('ac123', $result->ac);
        self::assertSame('cc123', $result->cc);
        self::assertSame('tid123', $result->tid);
        self::assertSame('txn123', $result->txn);
        self::assertSame('rc123', $result->rc);
    }

    #[Test]
    public function returnNullForEmptyContent(): void
    {
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->tryParse('');
        self::assertNull($result);
    }

    #[Test]
    public function returnNullWhenResNotPresent(): void
    {
        // Tests that RES is required (line 56-58 logic)
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->tryParse('AMT=100 CURR=EUR TIMESTAMP=1234567890');
        self::assertNull($result);
    }

    #[Test]
    public function mbTrimAppliedToValues(): void
    {
        // The parser splits on spaces, so values with trailing spaces need careful handling
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->parse('AMT=100.50 CURR=EUR RES=OK');
        self::assertSame('100.50', $result->money?->toDecimalString());
        self::assertSame('EUR', $result->money->currency->value);
    }

    #[Test]
    public function matchArmTypeCasting(): void
    {
        $parser = new TatraBankaSimpleMailParser();

        // Test float casting for amount
        $result1 = $parser->parse('AMT=123.45 CURR=EUR RES=OK');
        self::assertIsString($result1->money?->toDecimalString());
        self::assertSame('123.45', $result1->money->toDecimalString());

        // Test int casting for transactionDate
        $result2 = $parser->parse('TIMESTAMP=1234567890 RES=OK');
        self::assertIsInt($result2->transactionDate?->getTimestamp());
        self::assertSame(1234567890, $result2->transactionDate->getTimestamp());

        // Test default branch (string)
        $result3 = $parser->parse('VS=111111 RES=OK');
        self::assertIsString($result3->vs);
        self::assertSame('111111', $result3->vs);
    }

    #[Test]
    public function constructorTypeCasting(): void
    {
        // Tests CastFloat line 65, CastString lines 66-76
        // Verifies explicit type casts in constructor call
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->parse('AMT=100.50 CURR=EUR TIMESTAMP=1234567890 '
                                 . 'VS=111 CID=cid SIGN=sign RES=res AC=ac CC=cc '
                                 . 'TID=tid TXN=txn RC=rc');

        // Verify types are correctly cast
        self::assertSame('100.50', $result->money?->toDecimalString());

        self::assertSame('EUR', $result->money->currency->value);

        self::assertSame(1234567890, $result->transactionDate?->getTimestamp());

        // Test all string casts
        self::assertIsString($result->vs);
        self::assertIsString($result->cid);
        self::assertIsString($result->sign);
        self::assertIsString($result->res);
        self::assertIsString($result->ac);
        self::assertIsString($result->cc);
        self::assertIsString($result->tid);
        self::assertIsString($result->txn);
        self::assertIsString($result->rc);
    }

    #[Test]
    public function constructorWithMissingOptionalFields(): void
    {
        // Tests that constructor handles null values correctly for optional fields
        $parser = new TatraBankaSimpleMailParser();
        $result = $parser->parse('RES=OK');

        self::assertNull($result->money?->toDecimalString());
        self::assertNull($result->money?->currency->value);
        self::assertNull($result->transactionDate);
        self::assertNull($result->vs);
        self::assertNull($result->cid);
        self::assertNull($result->sign);
        self::assertSame('OK', $result->res);
        self::assertNull($result->ac);
        self::assertNull($result->cc);
        self::assertNull($result->tid);
        self::assertNull($result->txn);
        self::assertNull($result->rc);
    }
}
