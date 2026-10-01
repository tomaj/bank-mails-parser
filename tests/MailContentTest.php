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
        $mailContent = new MailContent(
            vs: '',
            ss: '',
            ks: '',
            cc: '',
            tid: '',
        );

        $this->assertNull($mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->cc);
        $this->assertNull($mailContent->tid);
    }

    #[Test]
    public function nonEmptyStringHandling()
    {
        $mailContent = new MailContent(
            ks: '1234',
            ss: '5678',
            vs: '9876543210',
            cc: 'CC123',
            tid: 'TID456',
        );

        $this->assertEquals('1234', $mailContent->ks);
        $this->assertEquals('5678', $mailContent->ss);
        $this->assertEquals('9876543210', $mailContent->vs);
        $this->assertEquals('CC123', $mailContent->cc);
        $this->assertEquals('TID456', $mailContent->tid);
    }

    #[Test]
    public function allPropertiesInitiallyNull()
    {
        $mailContent = new MailContent();

        $this->assertNull($mailContent->amount);
        $this->assertNull($mailContent->accountNumber);
        $this->assertNull($mailContent->sourceAccountNumber);
        $this->assertNull($mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->transactionDate);
        $this->assertNull($mailContent->currency);
        $this->assertNull($mailContent->receiverMessage);
        $this->assertNull($mailContent->description);
        $this->assertNull($mailContent->sign);
        $this->assertNull($mailContent->cid);
        $this->assertNull($mailContent->res);
        $this->assertNull($mailContent->ac);
        $this->assertNull($mailContent->cc);
        $this->assertNull($mailContent->tid);
        $this->assertNull($mailContent->txn);
        $this->assertNull($mailContent->rc);
    }

    #[Test]
    public function allPropertiesCanBeSet()
    {
        $mailContent = new MailContent(
            amount: 123.45,
            accountNumber: 'SK1234567890',
            sourceAccountNumber: 'SK9876543210',
            transactionDate: 1234567890,
            currency: 'EUR',
            receiverMessage: 'Test message',
            description: 'Test description',
            sign: 'SIGN123',
            cid: 'CID456',
            res: 'OK',
            ac: 'AC789',
            txn: 'TXN999',
            rc: 'RC111',
        );

        $this->assertEquals(123.45, $mailContent->amount);
        $this->assertEquals('SK1234567890', $mailContent->accountNumber);
        $this->assertEquals('SK9876543210', $mailContent->sourceAccountNumber);
        $this->assertEquals(1234567890, $mailContent->transactionDate);
        $this->assertEquals('EUR', $mailContent->currency);
        $this->assertEquals('Test message', $mailContent->receiverMessage);
        $this->assertEquals('Test description', $mailContent->description);
        $this->assertEquals('SIGN123', $mailContent->sign);
        $this->assertEquals('CID456', $mailContent->cid);
        $this->assertEquals('OK', $mailContent->res);
        $this->assertEquals('AC789', $mailContent->ac);
        $this->assertEquals('TXN999', $mailContent->txn);
        $this->assertEquals('RC111', $mailContent->rc);
    }

    #[Test]
    public function immutabilityTest()
    {
        $mailContent = new MailContent(amount: 100.0);

        $this->assertEquals(100.0, $mailContent->amount);

        // Verify it's truly readonly
        $reflection = new \ReflectionClass($mailContent);
        $this->assertTrue($reflection->isReadOnly());
    }

    #[Test]
    public function constructorWithAllParameters()
    {
        $mailContent = new MailContent(
            amount: 999.99,
            currency: 'USD',
            transactionDate: 1609459200,
            accountNumber: 'TEST123',
            sourceAccountNumber: 'SOURCE456',
            vs: '123',
            ss: '456',
            ks: '789',
            receiverMessage: 'Test msg',
            description: 'Test desc',
            cid: 'CID1',
            sign: 'SIGN1',
            res: 'RES1',
            ac: 'AC1',
            cc: 'CC1',
            tid: 'TID1',
            txn: 'TXN1',
            rc: 'RC1',
        );

        $this->assertEquals(999.99, $mailContent->amount);
        $this->assertEquals('USD', $mailContent->currency);
        $this->assertEquals(1609459200, $mailContent->transactionDate);
        $this->assertEquals('TEST123', $mailContent->accountNumber);
        $this->assertEquals('SOURCE456', $mailContent->sourceAccountNumber);
        $this->assertEquals('123', $mailContent->vs);
        $this->assertEquals('456', $mailContent->ss);
        $this->assertEquals('789', $mailContent->ks);
        $this->assertEquals('Test msg', $mailContent->receiverMessage);
        $this->assertEquals('Test desc', $mailContent->description);
        $this->assertEquals('CID1', $mailContent->cid);
        $this->assertEquals('SIGN1', $mailContent->sign);
        $this->assertEquals('RES1', $mailContent->res);
        $this->assertEquals('AC1', $mailContent->ac);
        $this->assertEquals('CC1', $mailContent->cc);
        $this->assertEquals('TID1', $mailContent->tid);
        $this->assertEquals('TXN1', $mailContent->txn);
        $this->assertEquals('RC1', $mailContent->rc);
    }

    #[Test]
    public function emptyStringNormalizationForMultipleFields()
    {
        $mailContent = new MailContent(
            vs: '',
            ss: '',
            ks: '',
            cid: '',
            sign: '',
            res: '',
            ac: '',
            cc: '',
            tid: '',
            txn: '',
            rc: '',
        );

        $this->assertNull($mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->cid);
        $this->assertNull($mailContent->sign);
        $this->assertNull($mailContent->res);
        $this->assertNull($mailContent->ac);
        $this->assertNull($mailContent->cc);
        $this->assertNull($mailContent->tid);
        $this->assertNull($mailContent->txn);
        $this->assertNull($mailContent->rc);
    }

    #[Test]
    public function falseTransactionDate()
    {
        $mailContent = new MailContent(transactionDate: false);

        $this->assertFalse($mailContent->transactionDate);
    }

    #[Test]
    public function negativeAmount()
    {
        $mailContent = new MailContent(amount: -123.45);

        $this->assertEquals(-123.45, $mailContent->amount);
    }
}
