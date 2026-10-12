<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

#[CoversClass(MailContent::class)]
class MailContentTest extends TestCase
{
    #[Test]
    public function emptyStringHandling(): void
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
    public function nonEmptyStringHandling(): void
    {
        $mailContent = new MailContent(
            ks: '1234',
            ss: '5678',
            vs: '9876543210',
            cc: 'CC123',
            tid: 'TID456',
        );

        $this->assertSame('1234', $mailContent->ks);
        $this->assertSame('5678', $mailContent->ss);
        $this->assertSame('9876543210', $mailContent->vs);
        $this->assertSame('CC123', $mailContent->cc);
        $this->assertSame('TID456', $mailContent->tid);
    }

    #[Test]
    public function allPropertiesInitiallyNull(): void
    {
        $mailContent = new MailContent();

        $this->assertNull($mailContent->money);
        $this->assertNull($mailContent->accountNumber);
        $this->assertNull($mailContent->sourceAccountNumber);
        $this->assertNull($mailContent->ownAccountNumber);
        $this->assertNull($mailContent->vs);
        $this->assertNull($mailContent->ss);
        $this->assertNull($mailContent->ks);
        $this->assertNull($mailContent->transactionDate);
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
    public function allPropertiesCanBeSet(): void
    {
        $money = Money::fromDecimalString('123.45', Currency::fromCode('EUR'));
        $date = new DateTimeImmutable('@1234567890');
        $mailContent = new MailContent(
            money: $money,
            accountNumber: 'SK1234567890',
            sourceAccountNumber: 'SK9876543210',
            transactionDate: $date,
            receiverMessage: 'Test message',
            description: 'Test description',
            sign: 'SIGN123',
            cid: 'CID456',
            res: 'OK',
            ac: 'AC789',
            txn: 'TXN999',
            rc: 'RC111',
        );

        $this->assertSame($money, $mailContent->money);
        $this->assertSame('123.45', $mailContent->money->toDecimalString());
        $this->assertSame(Currency::fromCode('EUR'), $mailContent->money->currency);
        $this->assertSame('SK1234567890', $mailContent->accountNumber);
        $this->assertSame('SK9876543210', $mailContent->sourceAccountNumber);
        $this->assertSame(1234567890, $mailContent->transactionDate?->getTimestamp());
        $this->assertSame('Test message', $mailContent->receiverMessage);
        $this->assertSame('Test description', $mailContent->description);
        $this->assertSame('SIGN123', $mailContent->sign);
        $this->assertSame('CID456', $mailContent->cid);
        $this->assertSame('OK', $mailContent->res);
        $this->assertSame('AC789', $mailContent->ac);
        $this->assertSame('TXN999', $mailContent->txn);
        $this->assertSame('RC111', $mailContent->rc);
    }

    #[Test]
    public function immutabilityTest(): void
    {
        $mailContent = new MailContent(money: Money::fromDecimalString('100.00', Currency::fromCode('EUR')));

        $this->assertSame('100.00', $mailContent->money?->toDecimalString());

        $this->assertSame(10000, $mailContent->money?->amount);
    }

    #[Test]
    public function constructorWithAllParameters(): void
    {
        $date = new DateTimeImmutable('@1609459200');
        $mailContent = new MailContent(
            money: Money::fromDecimalString('999.99', 'USD'),
            transactionDate: $date,
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

        $this->assertSame('999.99', $mailContent->money?->toDecimalString());
        $this->assertSame('USD', $mailContent->money?->currency->value);
        $this->assertSame(1609459200, $mailContent->transactionDate?->getTimestamp());
        $this->assertSame('TEST123', $mailContent->accountNumber);
        $this->assertSame('SOURCE456', $mailContent->sourceAccountNumber);
        $this->assertSame('123', $mailContent->vs);
        $this->assertSame('456', $mailContent->ss);
        $this->assertSame('789', $mailContent->ks);
        $this->assertSame('Test msg', $mailContent->receiverMessage);
        $this->assertSame('Test desc', $mailContent->description);
        $this->assertSame('CID1', $mailContent->cid);
        $this->assertSame('SIGN1', $mailContent->sign);
        $this->assertSame('RES1', $mailContent->res);
        $this->assertSame('AC1', $mailContent->ac);
        $this->assertSame('CC1', $mailContent->cc);
        $this->assertSame('TID1', $mailContent->tid);
        $this->assertSame('TXN1', $mailContent->txn);
        $this->assertSame('RC1', $mailContent->rc);
    }

    #[Test]
    public function emptyStringNormalizationForMultipleFields(): void
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
    public function nullTransactionDate(): void
    {
        $mailContent = new MailContent(transactionDate: null);

        $this->assertNull($mailContent->transactionDate);
    }

    #[Test]
    public function negativeAmount(): void
    {
        $mailContent = new MailContent(money: Money::fromDecimalString('-123.45', Currency::fromCode('CZK')));

        $this->assertSame('-123.45', $mailContent->money?->toDecimalString());
        $this->assertSame(-12345, $mailContent->money?->amount);
        $this->assertTrue($mailContent->money?->isNegative());
    }
}
