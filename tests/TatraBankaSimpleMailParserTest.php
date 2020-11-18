<?php

require dirname(__FILE__). '/../vendor/autoload.php';

use Tomaj\BankMailsParser\Parser\TatraBankaSimpleMailParser;

class TatraBankaSimpleMailParserTest extends PHPUnit_Framework_TestCase
{
	public function testSimpleEmail()
	{
		$email = 'VS=1152201233 RES=OK AC=558058 SIGN=C0CBF27F5D97841E';
		$tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
		$mailContent = $tatrabankaSimpleMailParser->parse($email);
		$this->assertEquals('1152201233', $mailContent->getVs());
		$this->assertEquals('C0CBF27F5D97841E', $mailContent->getSign());
		$this->assertEquals('OK', $mailContent->getRes());
		$this->assertEquals('558058', $mailContent->getAc());
	}

	public function testErrorResult()
	{
		$email = 'VS=1152201233 RES=FAIL AC=558058 SIGN=C0CBF27F5D97841E';
		$tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
		$mailContent = $tatrabankaSimpleMailParser->parse($email);
		$this->assertEquals('FAIL', $mailContent->getRes());
		$this->assertEquals('1152201233', $mailContent->getVs());
		$this->assertEquals('C0CBF27F5D97841E', $mailContent->getSign());
		$this->assertEquals('558058', $mailContent->getAc());
	}

	public function testErrorEmail()
	{
		$email = '';
		$tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
		$mailContent = $tatrabankaSimpleMailParser->parse($email);
		$this->assertFalse($mailContent);

		$email = 'VS=1152201233 AC=558058 SIGN=C0CBF27F5D97841E';
		$tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
		$mailContent = $tatrabankaSimpleMailParser->parse($email);
		$this->assertFalse($mailContent);
	}

	public function testCidAndTres()
	{
		$email = 'VS=1151151156 TRES=OK CID=123445 SIGN=XCCBF1235D945841C';;
		$tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
		$mailContent = $tatrabankaSimpleMailParser->parse($email);
		$this->assertEquals('1151151156', $mailContent->getVs());
		$this->assertEquals('XCCBF1235D945841C', $mailContent->getSign());
		$this->assertEquals('123445', $mailContent->getCid());
		$this->assertEquals('OK', $mailContent->getRes());
		$this->assertEquals(time(), $mailContent->getTransactionDate());
	}
	
	public function testHmacCardpayEmail()
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

	public function testHmacComfortpayEmail()
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

	public function testFailHmacComfortpayEmail()
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

	public function testFailHmacComfortpayEmailNoCcTidTres()
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
}