<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\MultipleTransactionsException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

#[CoversClass(BankMailsParser::class)]
final class BankMailsParserTest extends TestCase
{
    #[Test]
    public function parsesTatraBankaEmail(): void
    {
        $parser = new BankMailsParser();
        $mailContent = $parser->parse($this->tatraEmail(), 'nonstopbanking@tatrabanka.sk', 'Tatra banka');

        $this->assertSame('12.31', $mailContent->money?->toDecimalString());
        $this->assertSame(Currency::fromCode('EUR'), $mailContent->money?->currency);
        $this->assertSame('SK9812353347235', $mailContent->accountNumber);
    }

    #[Test]
    public function parsesTatraBankaSimpleEmail(): void
    {
        $parser = new BankMailsParser();
        $mailContent = $parser->parse('AMT=33.99 CURR=978 VS=5000001234 RES=OK TIMESTAMP=25112016223023');

        $this->assertSame('33.99', $mailContent->money?->toDecimalString());
        $this->assertSame(Currency::fromCode('EUR'), $mailContent->money?->currency);
        $this->assertSame('5000001234', $mailContent->vs);
        $this->assertSame('2016-11-25 22:30:23', $mailContent->transactionDate?->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function parsesTatraBankaStatementFixture(): void
    {
        $email = (string) file_get_contents(__DIR__ . '/fixtures/tatrabanka-statement/zasifrovany-vypis.txt');
        $parser = new BankMailsParser([
            new TatraBankaStatementMailParser(
                new TatraBankaMailDecryptor(
                    __DIR__ . '/fixtures/tatrabanka-statement/private-key.asc',
                    'heslo',
                ),
            ),
        ]);

        $rows = $parser->parseAll($email);
        $mailContent = $rows[0];

        $this->assertSame('13.37', $mailContent->money?->toDecimalString());
        $this->expectException(MultipleTransactionsException::class);
        $parser->parse($email);
    }

    #[Test]
    public function parsesCsobCzEmail(): void
    {
        $parser = new BankMailsParser();
        $content = "Dne 25.9.2018 byla na účtu 123456789 zaúčtována transakce Došlá platba.\nČástka: +1 234,56 CZK";

        $mailContent = $parser->parse($content, 'info@csob.cz');

        $this->assertSame('1234.56', $mailContent->money?->toDecimalString());
        $this->assertSame(Currency::fromCode('CZK'), $mailContent->money?->currency);
        $this->assertSame('123456789', $mailContent->accountNumber);
    }

    #[Test]
    public function parsesCsobSkEmail(): void
    {
        $parser = new BankMailsParser();
        $content = "dňa 6.9.2019 bola na účte SK123 zaúčtovaná suma SEPA platobného príkazu\nsuma: +10,50 EUR";

        $mailContent = $parser->parse($content, 'info@csob.sk');

        $this->assertSame('10.50', $mailContent->money?->toDecimalString());
        $this->assertSame(Currency::fromCode('EUR'), $mailContent->money?->currency);
    }

    #[Test]
    public function parsesVubFixture(): void
    {
        $parser = new BankMailsParser();
        $content = (string) file_get_contents(__DIR__ . '/fixtures/vub/priklad-platby.txt');

        $mailContent = $parser->parse($content, 'notification@vub.sk');

        $this->assertSame('13.37', $mailContent->money?->toDecimalString());
        $this->assertSame(Currency::fromCode('EUR'), $mailContent->money?->currency);
        $this->assertSame('9999999999', $mailContent->vs);
    }

    #[Test]
    public function throwsExceptionForUnsupportedEmail(): void
    {
        $parser = new BankMailsParser();

        $this->expectException(UnsupportedBankException::class);
        $this->expectExceptionMessage('No parser found for the supplied sender.');

        $parser->parse('This is not a bank email', 'unknown@example.test');
    }

    #[Test]
    public function throwsInvalidFormatWhenSenderMatchesButBodyDoesNot(): void
    {
        $parser = new BankMailsParser();

        $this->expectException(UnsupportedBankException::class);

        $parser->parse('not a payment', 'notification@vub.sk');
    }

    #[Test]
    public function returnsNullForUnsupportedEmailWithTryParse(): void
    {
        $parser = new BankMailsParser();

        $this->assertNull($parser->tryParse('This is not a bank email'));
        $this->assertNull($parser->tryParse('not a payment', 'notification@vub.sk'));
    }

    #[Test]
    public function registersCustomParsers(): void
    {
        $parser = new BankMailsParser([]);

        $this->assertCount(0, $parser->getParsers());

        $parser->registerParser(new class implements ParserInterface {
            use ParsesBankMail;

            #[\Override]
            public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
            {
                return str_contains($content, 'CUSTOM-BANK');
            }

            public function parse(string $content): MailContent
            {
                return new MailContent(money: Money::fromDecimalString('1.50', Currency::fromCode('EUR')), vs: '42');
            }
        });

        $this->assertCount(1, $parser->getParsers());
        $mailContent = $parser->parse('CUSTOM-BANK payment');
        $this->assertSame('1.50', $mailContent->money?->toDecimalString());
        $this->assertSame('42', $mailContent->vs);
    }

    #[Test]
    public function registersDefaultParsersWhenNoneProvided(): void
    {
        $parser = new BankMailsParser();

        $this->assertCount(5, $parser->getParsers());
    }

    private function tatraEmail(): string
    {
        return \Tomaj\BankMailsParser\Tests\Fixtures::load('tatrabanka/notifikacia-kratka');
    }
}
