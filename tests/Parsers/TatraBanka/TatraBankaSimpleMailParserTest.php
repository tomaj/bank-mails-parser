<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parsers\TatraBanka;

use DateTime;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;

class TatraBankaSimpleMailParserTest extends TestCase
{
    public function testTatra()
    {
        $email = 'bla VS=1234056789 bla SIGN=1234567 bla RES=OK bla CID=123445 bla';

        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);

        $this->assertEquals('1234056789', $mailContent->getVs());
        $this->assertEquals('1234567', $mailContent->getSign());
        $this->assertEquals('123445', $mailContent->getCid());
        $this->assertEquals('OK', $mailContent->getRes());
    }

    public function testTatra2()
    {
        $email = 'This is Tatra Bank confirmation email. Please find the details of the transaction below.' . "\n\n" .
            'VS=1234056789 TRES=0 RES=OK AC=123456 SIGN=XCCBF1235D945841C CID=123445';

        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);

        $this->assertEquals('1234056789', $mailContent->getVs());
        $this->assertEquals('0', $mailContent->getTres());
        $this->assertEquals('OK', $mailContent->getRes());
        $this->assertEquals('123456', $mailContent->getAc());
        $this->assertEquals('XCCBF1235D945841C', $mailContent->getSign());
        $this->assertEquals('123445', $mailContent->getCid());
        $this->assertEquals('OK', $mailContent->getRes());
        // Check that transaction date is set to current time (within 1 second tolerance)
        $this->assertLessThanOrEqual(
            1,
            abs(time() - $mailContent->getTransactionDate()?->getTimestamp())
        );
    }

    public function testHmacCardpayEmail()
    {
        $email = 'This is TatraBanka notification.' . "\n\n" .
                'Please do not reply to this message, it has been sent from automated notification mailbox.' . "\n\n" .
                'HMAC VS=1234567890 AMT=12.50 CURR=978 TIMESTAMP=25112016223023 AC=740017 TXN=111 ' .
                'SIGN=A4CC0E2EB5A6A6B006FA31E4BF50D7E5436BDD15 CC=1111';

        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);

        $this->assertEquals('1234567890', $mailContent->getVs());
        $this->assertEquals(12.50, $mailContent->getAmount());
        $this->assertEquals('740017', $mailContent->getAc());
        $this->assertEquals('978', $mailContent->getCurrency());

        $expectedDate = DateTime::createFromFormat('dmYHis', '25112016223023');
        $this->assertEquals(
            $expectedDate->getTimestamp(),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testHmacComfortpayEmail()
    {
        $email = 'This is TatraBanka ComfortPay notification.' . "\n\n" .
                'Please do not reply to this message, it has been sent from automated notification mailbox.' . "\n\n" .
                'VS=1234567890 AMT=10.00 CURR=978 AC=740017 TXN=111 SIGN=A4CC0E2EB5A6A6B006FA31E4BF50D7E5436BDD15 ' .
                'CC=1111 CID=555222111333 RES=1 TIMESTAMP=25112016223023';

        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);

        $this->assertEquals('1234567890', $mailContent->getVs());
        $this->assertEquals(10.00, $mailContent->getAmount());
        $this->assertEquals('740017', $mailContent->getAc());
        $this->assertEquals('978', $mailContent->getCurrency());

        $expectedDate = DateTime::createFromFormat('dmYHis', '25112016223023');
        $this->assertEquals(
            $expectedDate->getTimestamp(),
            $mailContent->getTransactionDate()?->getTimestamp()
        );
    }

    public function testResultNull()
    {
        $email = 'This is TatraBanka notification.' . "\n\n" .
                'Please do not reply to this message, it has been sent from automated notification mailbox.' . "\n\n" .
                'VS=1234567890 AMT=10.00 CURR=978 AC=740017 TXN=111 SIGN=A4CC0E2EB5A6A6B006FA31E4BF50D7E5436BDD15 ' .
                'CC=1111 CID=555222111333 TIMESTAMP=25112016223023';

        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);

        $this->assertNull($mailContent);
    }

    public function testNotRecognised()
    {
        $email = 'This is TatraBanka notification. Please do not reply to this message, ' .
                'it has been sent from automated notification mailbox. HMAC bla bla bla';

        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);

        $this->assertNull($mailContent);
    }

    public function testNotRecognised2()
    {
        $email = 'This is TatraBanka notification.' . "\n\n" .
                'Please do not reply to this message, it has been sent from automated notification mailbox.' . "\n\n" .
                'RES=OK SIGN=A4CC0E2EB5A6A6B006FA31E4BF50D7E5436BDD15';

        $tatrabankaSimpleMailParser = new TatraBankaSimpleMailParser();
        $mailContent = $tatrabankaSimpleMailParser->parse($email);

        $this->assertEquals('OK', $mailContent->getRes());
        $this->assertEquals('A4CC0E2EB5A6A6B006FA31E4BF50D7E5436BDD15', $mailContent->getSign());
        // Check that transaction date is set to current time (within 1 second tolerance)
        $this->assertLessThanOrEqual(
            1,
            abs(time() - $mailContent->getTransactionDate()?->getTimestamp())
        );
    }
}
