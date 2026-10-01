<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\TatraBanka;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;

#[CoversClass(TatraBankaStatementMailParser::class)]
class TatraBankaStatementMailParserTest extends TestCase
{
    #[Test]
    public function transferPayments()
    {
        $email = file_get_contents(__DIR__ . '/data/tb_encrypted_mail_body.txt');

        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/data/tb_mail_private_key.asc',
                'heslo',
            ),
        );
        $mailContents = $parser->parseMulti($email);

        $this->assertCount(4, $mailContents);

        $mailContent = $mailContents[0];
        $this->assertEquals('2621234415', $mailContent->accountNumber);
        $this->assertEquals(13.37, $mailContent->amount);
        $this->assertEquals('4169344603', $mailContent->vs);
        $this->assertEquals(strtotime('20190315'), $mailContent->transactionDate);

        $mailContent = $mailContents[1];
        $this->assertEquals('2621234415', $mailContent->accountNumber);
        $this->assertEquals(15.0, $mailContent->amount);
        $this->assertEquals('9051230034', $mailContent->vs);
        $this->assertEquals(strtotime('20190315'), $mailContent->transactionDate);

        $mailContent = $mailContents[2];
        $this->assertEquals('2946123663', $mailContent->accountNumber);
        $this->assertEquals(59.9, $mailContent->amount);
        $this->assertEquals('9101235209', $mailContent->vs);
        $this->assertEquals(strtotime('20190315'), $mailContent->transactionDate);

        $mailContent = $mailContents[3];
        $this->assertEquals('2912329663', $mailContent->accountNumber);
        $this->assertEquals(34.9, $mailContent->amount);
        $this->assertEquals('9101955247', $mailContent->vs);
        $this->assertEquals(strtotime('20190315'), $mailContent->transactionDate);
    }

    #[Test]
    public function parseMultiReturnsNullForContentWithoutPGPMessage()
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/data/tb_mail_private_key.asc',
                'heslo',
            ),
        );

        $result = $parser->parseMulti('Email without PGP message block');

        $this->assertNull($result);
    }

    #[Test]
    public function parseReturnsNullForEmptyTransaction()
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/data/tb_mail_private_key.asc',
                'heslo',
            ),
        );

        $result = $parser->parse('');

        $this->assertNull($result);
    }

    #[Test]
    public function parseReturnsNullForTransactionWithAllZeros()
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/data/tb_mail_private_key.asc',
                'heslo',
            ),
        );

        $result = $parser->parse('0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0');

        $this->assertNull($result);
    }

    #[Test]
    public function parseHandlesValidTransaction()
    {
        $parser = new TatraBankaStatementMailParser(
            new TatraBankaMailDecryptor(
                __DIR__ . '/data/tb_mail_private_key.asc',
                'heslo',
            ),
        );

        $transaction = '20190315|1|2|3|4|5|6|7|8|100.50|EUR|11|12|13|14|15|16|17|1234567890|SK1234567890|20';
        $result = $parser->parse($transaction);

        $this->assertNotNull($result);
        $this->assertEquals(100.50, $result->amount);
        $this->assertEquals('EUR', $result->currency);
        $this->assertEquals('1234567890', $result->vs);
        $this->assertEquals('SK1234567890', $result->accountNumber);
    }
}
