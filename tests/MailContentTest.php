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

        self::assertNull($mailContent->vs);
        self::assertNull($mailContent->ss);
        self::assertNull($mailContent->ks);
        self::assertNull($mailContent->cc);
        self::assertNull($mailContent->tid);
    }

    #[Test]
    public function nonEmptyStringHandling(): void
    {
        $mailContent = new MailContent(
            vs: '9876543210',
            ss: '5678',
            ks: '1234',
            cc: 'CC123',
            tid: 'TID456',
        );

        self::assertSame('1234', $mailContent->ks);
        self::assertSame('5678', $mailContent->ss);
        self::assertSame('9876543210', $mailContent->vs);
        self::assertSame('CC123', $mailContent->cc);
        self::assertSame('TID456', $mailContent->tid);
    }

    #[Test]
    public function allPropertiesInitiallyNull(): void
    {
        $mailContent = new MailContent();

        self::assertNull($mailContent->money);
        self::assertNull($mailContent->accountNumber);
        self::assertNull($mailContent->sourceAccountNumber);
        self::assertNull($mailContent->ownAccountNumber);
        self::assertNull($mailContent->vs);
        self::assertNull($mailContent->ss);
        self::assertNull($mailContent->ks);
        self::assertNull($mailContent->transactionDate);
        self::assertNull($mailContent->receiverMessage);
        self::assertNull($mailContent->description);
        self::assertNull($mailContent->sign);
        self::assertNull($mailContent->cid);
        self::assertNull($mailContent->res);
        self::assertNull($mailContent->ac);
        self::assertNull($mailContent->cc);
        self::assertNull($mailContent->tid);
        self::assertNull($mailContent->txn);
        self::assertNull($mailContent->rc);
    }

    #[Test]
    public function allPropertiesCanBeSet(): void
    {
        $money = Money::fromDecimalString('123.45', Currency::fromCode('EUR'));
        $date = new DateTimeImmutable('@1234567890');
        $mailContent = new MailContent(
            money: $money,
            transactionDate: $date,
            accountNumber: 'SK1234567890',
            sourceAccountNumber: 'SK9876543210',
            receiverMessage: 'Test message',
            description: 'Test description',
            cid: 'CID456',
            sign: 'SIGN123',
            res: 'OK',
            ac: 'AC789',
            txn: 'TXN999',
            rc: 'RC111',
        );

        self::assertSame($money, $mailContent->money);
        self::assertSame('123.45', $mailContent->money->toDecimalString());
        self::assertSame(Currency::fromCode('EUR'), $mailContent->money->currency);
        self::assertSame('SK1234567890', $mailContent->accountNumber);
        self::assertSame('SK9876543210', $mailContent->sourceAccountNumber);
        self::assertSame(1234567890, $mailContent->transactionDate?->getTimestamp());
        self::assertSame('Test message', $mailContent->receiverMessage);
        self::assertSame('Test description', $mailContent->description);
        self::assertSame('SIGN123', $mailContent->sign);
        self::assertSame('CID456', $mailContent->cid);
        self::assertSame('OK', $mailContent->res);
        self::assertSame('AC789', $mailContent->ac);
        self::assertSame('TXN999', $mailContent->txn);
        self::assertSame('RC111', $mailContent->rc);
    }

    #[Test]
    public function immutabilityTest(): void
    {
        $mailContent = new MailContent(money: Money::fromDecimalString('100.00', Currency::fromCode('EUR')));

        self::assertSame('100.00', $mailContent->money?->toDecimalString());

        self::assertSame(10000, $mailContent->money->amount);
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

        self::assertSame('999.99', $mailContent->money?->toDecimalString());
        self::assertSame('USD', $mailContent->money->currency->value);
        self::assertSame(1609459200, $mailContent->transactionDate?->getTimestamp());
        self::assertSame('TEST123', $mailContent->accountNumber);
        self::assertSame('SOURCE456', $mailContent->sourceAccountNumber);
        self::assertSame('123', $mailContent->vs);
        self::assertSame('456', $mailContent->ss);
        self::assertSame('789', $mailContent->ks);
        self::assertSame('Test msg', $mailContent->receiverMessage);
        self::assertSame('Test desc', $mailContent->description);
        self::assertSame('CID1', $mailContent->cid);
        self::assertSame('SIGN1', $mailContent->sign);
        self::assertSame('RES1', $mailContent->res);
        self::assertSame('AC1', $mailContent->ac);
        self::assertSame('CC1', $mailContent->cc);
        self::assertSame('TID1', $mailContent->tid);
        self::assertSame('TXN1', $mailContent->txn);
        self::assertSame('RC1', $mailContent->rc);
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

        self::assertNull($mailContent->vs);
        self::assertNull($mailContent->ss);
        self::assertNull($mailContent->ks);
        self::assertNull($mailContent->cid);
        self::assertNull($mailContent->sign);
        self::assertNull($mailContent->res);
        self::assertNull($mailContent->ac);
        self::assertNull($mailContent->cc);
        self::assertNull($mailContent->tid);
        self::assertNull($mailContent->txn);
        self::assertNull($mailContent->rc);
    }

    #[Test]
    public function nullTransactionDate(): void
    {
        $mailContent = new MailContent();

        self::assertNull($mailContent->transactionDate);
    }

    #[Test]
    public function negativeAmount(): void
    {
        $mailContent = new MailContent(money: Money::fromDecimalString('-123.45', Currency::fromCode('CZK')));

        self::assertSame('-123.45', $mailContent->money?->toDecimalString());
        self::assertSame(-12345, $mailContent->money->amount);
        self::assertTrue($mailContent->money->isNegative());
    }
}
