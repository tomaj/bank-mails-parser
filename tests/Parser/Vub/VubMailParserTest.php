<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\Vub;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;
use Tomaj\BankMailsParser\Tests\Fixtures;

#[CoversClass(VubMailParser::class)]
class VubMailParserTest extends TestCase
{
    #[Test]
    public function singleTransferPayment(): void
    {
        $contents = Fixtures::load('vub/platba');

        $vubMailParser = new VubMailParser();
        $mailContent = $vubMailParser->parse($contents);

        self::assertSame('SK4502000000001123100000', $mailContent->accountNumber);
        self::assertNull($mailContent->sourceAccountNumber);
        self::assertSame('SK5502000000001232860000', $mailContent->ownAccountNumber);
        self::assertSame('34.90', $mailContent->money?->toDecimalString());
        self::assertSame('EUR', $mailContent->money->currency->value);
        self::assertSame('9911929700', $mailContent->vs);
        self::assertSame('0308', $mailContent->ks);
        self::assertSame('910', $mailContent->ss);
    }

    #[Test]
    public function vubParserWithInvalidContent(): void
    {
        $parser = new VubMailParser();

        $result = $parser->tryParse('Invalid email content');
        self::assertNull($result);
    }

    #[Test]
    public function vubParserWithEmptyContent(): void
    {
        $parser = new VubMailParser();

        $result = $parser->tryParse('');
        self::assertNull($result);
    }

    #[Test]
    public function vubParserWithPartialContent(): void
    {
        $parser = new VubMailParser();

        // Test with only some fields present
        $content = 'Dtum: 11.12.2019
VS: 1234567890';

        $result = $parser->parse($content);
        self::assertSame('1234567890', $result->vs);
        self::assertNull($result->money?->toDecimalString());
        self::assertNull($result->accountNumber);
        self::assertNull($result->ks);
    }

    #[Test]
    public function vubParserWithInvalidAmountFormat(): void
    {
        $parser = new VubMailParser();

        // Test with invalid amount format
        $content = 'Suma: invalid_amount
VS: 1234567890';

        $result = $parser->parse($content);
        self::assertNull($result->money);
        self::assertSame('1234567890', $result->vs);
    }

    #[Test]
    public function vubParserWithMultilineContent(): void
    {
        // The 'm' flag allows ^ and $ to match line boundaries
        $parser = new VubMailParser();

        $content = Fixtures::load('vub/platba-123');

        $result = $parser->parse($content);
        self::assertSame(
            new \DateTimeImmutable('2015-01-12', new \DateTimeZone('Europe/Bratislava'))->getTimestamp(),
            $result->transactionDate?->getTimestamp(),
        );
        self::assertSame('SK1234567890123456', $result->accountNumber);
        self::assertNull($result->sourceAccountNumber);
        self::assertNull($result->ownAccountNumber);
        self::assertSame('123.45', $result->money?->toDecimalString());
        self::assertSame('9876543210', $result->vs);
        self::assertSame('0308', $result->ks);
    }

    #[Test]
    public function vubParserVerifyArrayIndices(): void
    {
        // Test to verify correct array indices are used ($result[1] not $result[0])
        $parser = new VubMailParser();

        $content = 'Dtum: 2015-01-12 extra
Z tu: SK1234567890123456 trailing
Suma: 100,50 EUR
VS: 1234567890 text
KS: 0308 more';

        $result = $parser->parse($content);
        self::assertNull($result->transactionDate);
        self::assertSame('SK1234567890123456', $result->accountNumber);
        self::assertNull($result->sourceAccountNumber);
        self::assertNull($result->ownAccountNumber);
        self::assertSame('100.50', $result->money?->toDecimalString());
        self::assertSame('1234567890', $result->vs);
        self::assertSame('0308', $result->ks);
    }

    #[Test]
    public function exampleEnvelopePlainPartIsNotAPayment(): void
    {
        $eml = (string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/vub/priklad.eml');
        self::assertSame(
            1,
            preg_match('/Content-Type: text\/plain;.*?Content-Transfer-Encoding: base64\s+([A-Za-z0-9+\/=\r\n]+)\r?\n----/s', $eml, $match),
        );
        $body = base64_decode(preg_replace('/\s+/', '', $match[1]) ?? '', true);
        self::assertIsString($body);
        self::assertNull(new VubMailParser()->tryParse($body));
    }
}
