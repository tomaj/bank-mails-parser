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
        $this->assertEquals('1152201233', $mailContent->getVs());
        $this->assertEquals('C0CBF27F5D97841E', $mailContent->getSign());
        $this->assertEquals('OK', $mailContent->getRes());
        $this->assertEquals('558058', $mailContent->getAc());
    }

    #[Test]
    public function errorResult()
    {
        $email = 'VS=1152201233 RES=FAIL AC=558058 SIGN=C0CBF27F5D97841E';
        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);
        $this->assertEquals('FAIL', $mailContent->getRes());
        $this->assertEquals('1152201233', $mailContent->getVs());
        $this->assertEquals('C0CBF27F5D97841E', $mailContent->getSign());
        $this->assertEquals('558058', $mailContent->getAc());
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
        $this->assertEquals('1151151156', $mailContent->getVs());
        $this->assertEquals('XCCBF1235D945841C', $mailContent->getSign());
        $this->assertEquals('123445', $mailContent->getCid());
        $this->assertEquals('OK', $mailContent->getRes());
        $this->assertEquals(time(), $mailContent->getTransactionDate());
    }
    
    #[Test]
    public function hmacCardpayEmail()
    {
        $email = 'AMT=33.99 CURR=978 VS=5000001234 RES=OK AC=740017 TID=88888888 TIMESTAMP=25112016223023 HMAC=9dfd46cf2af977be8dd4251f4ef92307d95a8903f4738616d8456bd02e858340 ECDSA_KEY=1 ECDSA=3045022100e2c791637534bd57b530b7e42497dc6e33fa9f6c0e3950148c14c988f014ca5f02205918cb783d02d6dad4bea6ed4823a2c833187b979cdced377b3612c939b05f3d';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('33.99', $mailContent->getAmount());
        $this->assertEquals('5000001234', $mailContent->getVs());
        $this->assertEquals('9dfd46cf2af977be8dd4251f4ef92307d95a8903f4738616d8456bd02e858340', $mailContent->getSign());
        $this->assertEquals('OK', $mailContent->getRes());
        $this->assertEquals('740017', $mailContent->getAc());
        $this->assertEquals('978', $mailContent->getCurrency());
        $this->assertEquals('25112016223023', $mailContent->getTransactionDate());
    }

    #[Test]
    public function hmacComfortpayEmail()
    {
        $email = 'AMT=44.88 CURR=978 VS=4444255333 RES=OK AC=644311 TRES=OK CID=824452 CC=************1111 TID=11224444 TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('44.88', $mailContent->getAmount());
        $this->assertEquals('4444255333', $mailContent->getVs());
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->getSign());
        $this->assertEquals('OK', $mailContent->getRes());
        $this->assertEquals('824452', $mailContent->getCid());
        $this->assertEquals('************1111', $mailContent->getCC());
        $this->assertEquals('11224444', $mailContent->getTid());
        $this->assertEquals('978', $mailContent->getCurrency());
        $this->assertEquals('26112016121631', $mailContent->getTransactionDate());
        $this->assertNull($mailContent->getRc());
    }

    #[Test]
    public function hmacComfortpayEmailWithRc()
    {
        $email = 'AMT=44.88 CURR=978 VS=4444255333 RES=OK AC=644311 TRES=OK CID=824452 CC=************1111 RC=00 TID=11224444 TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('44.88', $mailContent->getAmount());
        $this->assertEquals('4444255333', $mailContent->getVs());
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->getSign());
        $this->assertEquals('OK', $mailContent->getRes());
        $this->assertEquals('824452', $mailContent->getCid());
        $this->assertEquals('************1111', $mailContent->getCC());
        $this->assertEquals('11224444', $mailContent->getTid());
        $this->assertEquals('978', $mailContent->getCurrency());
        $this->assertEquals('26112016121631', $mailContent->getTransactionDate());
        $this->assertEquals('00', $mailContent->getRc());
    }

    public function testHmacComfortpayEmailNoCC()
    {
        $email = 'AMT=44.88 CURR=978 VS=4444255333 RES=OK AC=644311 TRES=OK CID=824452 TID=11224444 TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('44.88', $mailContent->getAmount());
        $this->assertEquals('4444255333', $mailContent->getVs());
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->getSign());
        $this->assertEquals('OK', $mailContent->getRes());
        $this->assertEquals('824452', $mailContent->getCid());
        $this->assertEquals('11224444', $mailContent->getTid());
        $this->assertEquals('978', $mailContent->getCurrency());
        $this->assertEquals('26112016121631', $mailContent->getTransactionDate());
    }

    public function testHmacComfortpayEmailWithoutTxn()
    {
        $email = 'AMT=44.88 CURR=978 VS=4444255333 RES=OK AC=644311 TRES=OK CID=824452 TID=11224444 TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('44.88', $mailContent->getAmount());
        $this->assertEquals('4444255333', $mailContent->getVs());
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->getSign());
        $this->assertEquals('OK', $mailContent->getRes());
        $this->assertEquals('824452', $mailContent->getCid());
        $this->assertEquals('11224444', $mailContent->getTid());
        $this->assertEquals('978', $mailContent->getCurrency());
        $this->assertEquals('26112016121631', $mailContent->getTransactionDate());
        $this->assertNull($mailContent->getTxn());
    }

    public function testHmacComfortpayEmailWithTxn()
    {
        $email = 'AMT=44.88 CURR=978 VS=4444255333 TXN=PA RES=OK AC=644311 TRES=OK CID=824452 TID=11224444 TIMESTAMP=26112016121631 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('44.88', $mailContent->getAmount());
        $this->assertEquals('4444255333', $mailContent->getVs());
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->getSign());
        $this->assertEquals('OK', $mailContent->getRes());
        $this->assertEquals('824452', $mailContent->getCid());
        $this->assertEquals('11224444', $mailContent->getTid());
        $this->assertEquals('978', $mailContent->getCurrency());
        $this->assertEquals('26112016121631', $mailContent->getTransactionDate());
        $this->assertEquals('PA', $mailContent->getTxn());
    }

    #[Test]
    public function failHmacComfortpayEmail()
    {
        $email = 'AMT=140.92 CURR=978 VS=5555534283 RES=FAIL TRES=FAIL CC=************1111 TID=11224444 TIMESTAMP=24112016170555 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('140.92', $mailContent->getAmount());
        $this->assertEquals('978', $mailContent->getCurrency());
        $this->assertEquals('5555534283', $mailContent->getVs());
        $this->assertEquals('FAIL', $mailContent->getRes());
        $this->assertEquals('************1111', $mailContent->getCC());
        $this->assertEquals('11224444', $mailContent->getTid());
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->getSign());
        $this->assertEquals('24112016170555', $mailContent->getTransactionDate());
    }

    #[Test]
    public function failHmacComfortpayEmailNoCcTidTres()
    {
        $email = 'AMT=8.98 CURR=978 VS=5555534283 RES=FAIL TRES=FAIL TIMESTAMP=24032020144457 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('8.98', $mailContent->getAmount());
        $this->assertEquals('978', $mailContent->getCurrency());
        $this->assertEquals('5555534283', $mailContent->getVs());
        $this->assertEquals('FAIL', $mailContent->getRes());
        $this->assertEmpty($mailContent->getCC());
        $this->assertEmpty($mailContent->getTid());
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->getSign());
        $this->assertEquals('24032020144457', $mailContent->getTransactionDate());

        $email = 'AMT=4.99 CURR=978 VS=5555534283 RES=FAIL TIMESTAMP=27032020121740 HMAC=b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1 ECDSA_KEY=1 ECDSA=a5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21081c076caa4a8732e43aa5e75a2e2c21';
        $parser = new TatraBankaSimpleMailParser();
        $mailContent = $parser->parse($email);
        $this->assertEquals('4.99', $mailContent->getAmount());
        $this->assertEquals('978', $mailContent->getCurrency());
        $this->assertEquals('5555534283', $mailContent->getVs());
        $this->assertEquals('FAIL', $mailContent->getRes());
        $this->assertEmpty($mailContent->getCC());
        $this->assertEmpty($mailContent->getTid());
        $this->assertEquals('b76cb9ddeed7ed0bcf991f19bbbabfb1b76cb9ddeed7ed0bcf991f19bbbabfb1', $mailContent->getSign());
        $this->assertEquals('27032020121740', $mailContent->getTransactionDate());
    }

    #[Test]
    public function emptyValues()
    {
        // Test with empty values after equals sign
        $email = 'VS= RES=OK AC= AMT=100.00';
        $parser = new TatraBankaSimpleMailParser();
        
        $result = $parser->parse($email);
        $this->assertEquals('OK', $result->getRes());
        $this->assertEquals('100.00', $result->getAmount());
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
        $this->assertEquals('123', $result->getVs());
        $this->assertEquals('OK', $result->getRes());
    }
}
