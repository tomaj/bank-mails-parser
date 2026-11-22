<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

#[CoversClass(Money::class)]
#[CoversClass(CsobMailParser::class)]
#[CoversClass(SkCsobMailParser::class)]
#[CoversClass(TatraBankaMailParser::class)]
#[CoversClass(TatraBankaSimpleMailParser::class)]
#[CoversClass(TatraBankaStatementMailParser::class)]
#[CoversClass(VubMailParser::class)]
#[CoversClass(BankMailsParser::class)]
final class StrictParsingTest extends TestCase
{
    #[Test]
    public function moneyKeepsSpacingCharactersAndRejectsABareSign(): void
    {
        $nbsp = Money::fromDecimalString("1\u{00A0}234,56", 'EUR');
        $narrow = Money::fromDecimalString("1\u{202F}234,56", 'EUR');
        $padded = Money::fromDecimalString('  12,34  ', 'EUR');

        self::assertSame('1234.56', $nbsp->toDecimalString());
        self::assertSame('1234.56', $narrow->toDecimalString());
        self::assertSame('12.34', $padded->toDecimalString());
        self::assertSame(0, new Money(0, Currency::fromCode('EUR'))->abs()->amount);

        foreach (['+', '-', ''] as $amount) {
            try {
                Money::fromDecimalString($amount, 'EUR');
                self::fail($amount . ' must be rejected');
            } catch (\InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function groupedThousandsMustBeThreeDigits(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::fromDecimalString('12.34.5678', 'EUR');
    }

    #[Test]
    public function csobRequiresTheAnchoredDaySentence(): void
    {
        $parser = new CsobMailParser();

        try {
            $parser->parseAll('25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba. Částka: +10,00 CZK');
            self::fail('A sentence without the leading Dne date must not be a transaction');
        } catch (InvalidEmailFormatException $exception) {
            self::assertStringContainsString('Could not find transaction pattern', $exception->getMessage());
        }

        $rows = $parser->parseAll("Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\nČástka: +10,00 CZK");
        self::assertCount(1, $rows);
        self::assertSame('10.00', $rows[0]->money?->toDecimalString());
    }

    #[Test]
    public function csobParseAllReturnsEveryTransaction(): void
    {
        $email = "Dne 25.9.2018 byla na účtu 1 zaúčtovaná transakce typu: Došlá platba.\nČástka: +10,00 CZK\n\n"
            . "Dne 26.9.2018 byla na účtu 1 zaúčtovaná transakce typu: Došlá platba.\nČástka: +20,00 CZK";
        $rows = new CsobMailParser()->parseAll($email);

        self::assertCount(2, $rows);
        self::assertSame('10.00', $rows[0]->money?->toDecimalString());
        self::assertSame('20.00', $rows[1]->money?->toDecimalString());
    }

    #[Test]
    public function skCsobParsesWithoutTheDayWordAndReturnsEveryRow(): void
    {
        $parser = new SkCsobMailParser();
        try {
            $parser->parseAll('6.9.2019 bola na účte SK11 0000 zaúčtovaná suma SEPA platobného príkazu: suma: +10,00 EUR');
            self::fail('A sentence without dňa must not be a transaction');
        } catch (InvalidEmailFormatException $exception) {
            self::assertStringContainsString('Could not find transaction pattern', $exception->getMessage());
        }
        $one = $parser->parseAll("dňa 6.9.2019 bola na účte SK11 0000 zaúčtovaná suma SEPA platobného príkazu:\nsuma: +10,00 EUR");
        $two = $parser->parseAll(
            "dňa 6.9.2019 bola na účte SK11 0000 zaúčtovaná suma SEPA platobného príkazu:\nsuma: +10,00 EUR\n\n"
            . "dňa 7.9.2019 bola na účte SK11 0000 zaúčtovaná suma SEPA platobného príkazu:\nsuma: +20,00 EUR",
        );

        self::assertCount(1, $one);
        self::assertNull($one[0]->accountNumber);
        self::assertSame('SK11 0000', $one[0]->ownAccountNumber);
        self::assertCount(2, $two);
    }

    #[Test]
    public function tatraStructuredReferenceDoesNotBorrowNeighboringSymbols(): void
    {
        $parser = new TatraBankaMailParser();
        $emptySpecific = $parser->parse($this->tatra('/VS123/SS/KS0308', 'objednavka 556677'));
        $emptyKs = $parser->parse($this->tatra('/VS1/SS222/KS', 'x'));

        self::assertSame('123', $emptySpecific->vs);
        self::assertNull($emptySpecific->ss);
        self::assertSame('0308', $emptySpecific->ks);
        self::assertSame('222', $emptyKs->ss);
        self::assertNull($emptyKs->ks);
    }

    #[Test]
    public function emptyStructuredSymbolStillReadsTheLastNumberOnTheReferenceLine(): void
    {
        $mail = new TatraBankaMailParser()->parse($this->tatra('/VS/SS/KS objednavka 556677', 'bez cisla'));

        self::assertSame('556677', $mail->vs);
        self::assertSame('12.31', $mail->money?->toDecimalString());
    }

    #[Test]
    public function cardPayRejectsEmptyContentAndTimestampsThatAreNotExactWidths(): void
    {
        $parser = new TatraBankaSimpleMailParser();

        try {
            $parser->parse('   ');
            self::fail('Empty CardPay content must fail');
        } catch (InvalidEmailFormatException $exception) {
            self::assertStringContainsString('Empty content', $exception->getMessage());
        }

        foreach (['010120261200009', 'x01012026120000', '12345678901', '123456789'] as $timestamp) {
            try {
                $parser->parse('RES=OK TIMESTAMP=' . $timestamp);
                self::fail($timestamp . ' must be rejected');
            } catch (InvalidEmailFormatException $exception) {
                self::assertStringContainsString('TIMESTAMP', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function statementDateComesFromTheFirstColumn(): void
    {
        $row = '20190315|20190101|2|3|4|5|6|7|8|13.37|EUR|11|12|13|14|15|16|17|123|SK1|20';
        $mail = new TatraBankaStatementMailParser()->parse($row);

        self::assertSame('2019-03-15', $mail->transactionDate?->format('Y-m-d'));
        self::assertSame('13.37', $mail->money?->toDecimalString());
    }

    #[Test]
    public function vubEachSignalIsRequiredWhenItIsOneOfOnlyTwo(): void
    {
        $parser = new VubMailParser();

        $dateAndAccount = $parser->parse("Dátum: 11.12.2019\nNa účet: SK11");
        self::assertSame('2019-12-11', $dateAndAccount->transactionDate?->format('Y-m-d'));
        self::assertSame('SK11', $dateAndAccount->ownAccountNumber);
        self::assertNull($dateAndAccount->accountNumber);

        $sourceAndAmount = $parser->parse("Z účtu: SK22\nSuma: 10,50 EUR");
        self::assertSame('SK22', $sourceAndAmount->accountNumber);
        self::assertNull($sourceAndAmount->sourceAccountNumber);
        self::assertSame('10.50', $sourceAndAmount->money?->toDecimalString());

        $symbols = $parser->parse("KS: 0308\nSS: 99");
        self::assertSame('0308', $symbols->ks);
        self::assertSame('99', $symbols->ss);

        $blank = $parser->parse("Suma:\nVS: 42");
        self::assertNull($blank->money);
        self::assertSame('42', $blank->vs);
    }

    #[Test]
    public function facadeTryParseSwallowsEachInvalidInputType(): void
    {
        $parser = new BankMailsParser([]);
        $parser->registerParser(new class implements ParserInterface {
            use ParsesBankMail;

            #[\Override]
            public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
            {
                return true;
            }

            #[\Override]
            public function parse(string $content): MailContent
            {
                throw match ($content) {
                    'invalid' => new \InvalidArgumentException('bad'),
                    'value' => new \ValueError('bad'),
                    'type' => new \TypeError('bad'),
                    default => new \DateMalformedStringException('bad'),
                };
            }
        });

        self::assertNull($parser->tryParse('invalid'));
        self::assertNull($parser->tryParse('value'));
        self::assertNull($parser->tryParse('date'));
        try {
            $parser->tryParse('type');
            self::fail('TypeError must not be swallowed');
        } catch (\TypeError $error) {
            self::assertSame('bad', $error->getMessage());
        }
    }

    private function tatra(string $reference, string $message): string
    {
        return "16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 12,31 EUR.\n"
            . "Popis transakcie: CCINT 1100/000000-261426464\n"
            . "Referencia platitela: {$reference}\n"
            . "Informacia pre prijemcu: {$message}\n";
    }
}
