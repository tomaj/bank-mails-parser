<?php

require dirname(__FILE__). '/../vendor/autoload.php';

use Tomaj\BankMailsParser\Parser\TatraBankaMailParser;

class TatraBankaMailParserTest extends PHPUnit_Framework_TestCase
{
	public function testSimpleEmail()
	{
		$email = 'Vazeny klient,

16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.
uctovny zostatok:                            142,11 EUR
aktualny zostatok:                           142,11 EUR
disponibilny zostatok:                       142,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS/KS
Informacia pre prijemcu: test-sprava

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
		$tatrabankaMailParser = new TatraBankaMailParser();
		$mailContent = $tatrabankaMailParser->parse($email);

		$this->assertEquals('SK9812353347235', $mailContent->getAccountNumber());
		$this->assertEquals('EUR', $mailContent->getCurrency());
		$this->assertEquals(12.31, $mailContent->getAmount());
		$this->assertEquals('1234056789', $mailContent->getVs());
		$this->assertEquals('test-sprava', $mailContent->getReceiverMessage());
		$this->assertNull($mailContent->getKs());
		$this->assertNull($mailContent->getSs());
		$this->assertEquals(strtotime('16.1.2015 12:51'), $mailContent->getTransactionDate());
	}

	public function testAllInputsWithDecreaseEmail()
	{
		$email = 'Vazeny klient,

16.1.2015 12:11 bol zostatok Vasho uctu SK9812353347235 znizeny o 43,29 USD.
uctovny zostatok:                            22,11 EUR
aktualny zostatok:                           22,11 EUR
disponibilny zostatok:                       22,11 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS9087654321/KS5428175648
Informacia pre prijemcu: test-sprava-druha

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';
		$tatrabankaMailParser = new TatraBankaMailParser();
		$mailContent = $tatrabankaMailParser->parse($email);

		$this->assertEquals('SK9812353347235', $mailContent->getAccountNumber());
		$this->assertEquals('USD', $mailContent->getCurrency());
		$this->assertEquals(-43.29, $mailContent->getAmount());
		$this->assertEquals('1234056789', $mailContent->getVs());
		$this->assertEquals('9087654321', $mailContent->getSs());
		$this->assertEquals('5428175648', $mailContent->getKs());
		$this->assertEquals('test-sprava-druha', $mailContent->getReceiverMessage());
		$this->assertEquals(strtotime('16.1.2015 12:11'), $mailContent->getTransactionDate());
	}

	public function testEmailWithouReceiverMessage()
	{
		$email = 'Vazeny klient,

16.1.2015 12:11 bol zostatok Vasho uctu SK9812353347235 znizeny o 1 243,29 USD.
uctovny zostatok:                            100,00 EUR
aktualny zostatok:                           100,00 EUR
disponibilny zostatok:                       100,00 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS1234056789/SS9087654321/KS5428175648

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';

		$tatrabankaMailParser = new TatraBankaMailParser();
		$mailContent = $tatrabankaMailParser->parse($email);

		$this->assertEquals('SK9812353347235', $mailContent->getAccountNumber());
		$this->assertEquals('USD', $mailContent->getCurrency());
		$this->assertEquals(-1243.29, $mailContent->getAmount());
		$this->assertEquals('1234056789', $mailContent->getVs());
		$this->assertEquals('9087654321', $mailContent->getSs());
		$this->assertEquals('5428175648', $mailContent->getKs());
		$this->assertNull($mailContent->getReceiverMessage());
		$this->assertEquals(strtotime('16.1.2015 12:11'), $mailContent->getTransactionDate());
	}

	public function testEmailWithouRVariableSymbol()
	{
		$email = 'Vazeny klient,

12.1.2015 12:11 bol zostatok Vasho uctu SK9812369347235 znizeny o 2,20 EUR.
uctovny zostatok:                            32,52 EUR
aktualny zostatok:                           32,52 EUR
disponibilny zostatok:                       32,52 EUR

Popis transakcie: CCINT 1100/000000-261426464
Referencia platitela: /VS/SS9087654322/KS5428175649

S pozdravom

TATRA BANKA, a.s.

http://www.tatrabanka.sk

Poznamka: Vase pripomienky alebo otazky tykajuce sa tejto spravy alebo inej nasej sluzby nam poslite, prosim, pouzitim kontaktneho formulara na nasej Web stranke.

Odporucame Vam mazat si po precitani prichadzajuce bmail notifikacie. Historiu uctu najdete v ucelenom tvare v pohyboch cez internet banking a nemusite ju pracne skladat zo starych bmailov.
';

		$tatrabankaMailParser = new TatraBankaMailParser();
		$mailContent = $tatrabankaMailParser->parse($email);

		$this->assertEquals('SK9812369347235', $mailContent->getAccountNumber());
		$this->assertEquals('EUR', $mailContent->getCurrency());
		$this->assertEquals(-2.20, $mailContent->getAmount());
		$this->assertNull($mailContent->getVs());
		$this->assertEquals('9087654322', $mailContent->getSs());
		$this->assertEquals('5428175649', $mailContent->getKs());
		$this->assertNull($mailContent->getReceiverMessage());
		$this->assertEquals(strtotime('12.1.2015 12:11'), $mailContent->getTransactionDate());
	}

	public function testErrorEmail()
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
		$mailContent = $tatrabankaMailParser->parse($email);

		$this->assertFalse($mailContent);
	}
}