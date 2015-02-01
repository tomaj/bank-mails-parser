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
	}

	public function testErrorResult()
	{
		$email = 'VS=1152201233 RES=FAIL AC=558058 SIGN=C0CBF27F5D97841E';
		$tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
		$mailContent = $tatrabankaSimpleMailParser->parse($email);
		$this->assertNull($mailContent->getVs());
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
}