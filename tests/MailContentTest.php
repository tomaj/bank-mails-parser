<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MailContent::class)]
class MailContentTest extends TestCase
{
    #[Test]
    public function emptyStringHandling()
    {
        $mailContent = new MailContent();
        
        $mailContent->setKs('');
        $this->assertNull($mailContent->getKs());
        
        $mailContent->setSs('');
        $this->assertNull($mailContent->getSs());
        
        $mailContent->setVs('');
        $this->assertNull($mailContent->getVs());
        
        $mailContent->setCc('');
        $this->assertNull($mailContent->getCc());
        
        $mailContent->setTid('');
        $this->assertNull($mailContent->getTid());
    }

    #[Test]
    public function nonEmptyStringHandling()
    {
        $mailContent = new MailContent();
        
        $mailContent->setKs('1234');
        $this->assertEquals('1234', $mailContent->getKs());
        
        $mailContent->setSs('5678');
        $this->assertEquals('5678', $mailContent->getSs());
        
        $mailContent->setVs('9876543210');
        $this->assertEquals('9876543210', $mailContent->getVs());
        
        $mailContent->setCc('CC123');
        $this->assertEquals('CC123', $mailContent->getCc());
        
        $mailContent->setTid('TID456');
        $this->assertEquals('TID456', $mailContent->getTid());
    }

    #[Test]
    public function allGettersInitiallyReturnNull()
    {
        $mailContent = new MailContent();
        
        $this->assertNull($mailContent->getAmount());
        $this->assertNull($mailContent->getAccountNumber());
        $this->assertNull($mailContent->getSourceAccountNumber());
        $this->assertNull($mailContent->getVs());
        $this->assertNull($mailContent->getSs());
        $this->assertNull($mailContent->getKs());
        $this->assertNull($mailContent->getTransactionDate());
        $this->assertNull($mailContent->getCurrency());
        $this->assertNull($mailContent->getReceiverMessage());
        $this->assertNull($mailContent->getDescription());
        $this->assertNull($mailContent->getSign());
        $this->assertNull($mailContent->getCid());
        $this->assertNull($mailContent->getRes());
        $this->assertNull($mailContent->getAc());
        $this->assertNull($mailContent->getCc());
        $this->assertNull($mailContent->getTid());
        $this->assertNull($mailContent->getTxn());
        $this->assertNull($mailContent->getRc());
    }

    #[Test]
    public function allSettersAndGetters()
    {
        $mailContent = new MailContent();
        
        $mailContent->setAmount(123.45);
        $this->assertEquals(123.45, $mailContent->getAmount());
        
        $mailContent->setAccountNumber('SK1234567890');
        $this->assertEquals('SK1234567890', $mailContent->getAccountNumber());
        
        $mailContent->setSourceAccountNumber('SK9876543210');
        $this->assertEquals('SK9876543210', $mailContent->getSourceAccountNumber());
        
        $mailContent->setTransactionDate(1234567890);
        $this->assertEquals(1234567890, $mailContent->getTransactionDate());
        
        $mailContent->setCurrency('EUR');
        $this->assertEquals('EUR', $mailContent->getCurrency());
        
        $mailContent->setReceiverMessage('Test message');
        $this->assertEquals('Test message', $mailContent->getReceiverMessage());
        
        $mailContent->setDescription('Test description');
        $this->assertEquals('Test description', $mailContent->getDescription());
        
        $mailContent->setSign('SIGN123');
        $this->assertEquals('SIGN123', $mailContent->getSign());
        
        $mailContent->setCid('CID456');
        $this->assertEquals('CID456', $mailContent->getCid());
        
        $mailContent->setRes('OK');
        $this->assertEquals('OK', $mailContent->getRes());
        
        $mailContent->setAc('AC789');
        $this->assertEquals('AC789', $mailContent->getAc());
        
        $mailContent->setTxn('TXN999');
        $this->assertEquals('TXN999', $mailContent->getTxn());
        
        $mailContent->setRc('RC111');
        $this->assertEquals('RC111', $mailContent->getRc());
    }
}
