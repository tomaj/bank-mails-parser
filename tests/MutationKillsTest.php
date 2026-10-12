<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\AmbiguousBankException;
use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;
use Tomaj\BankMailsParser\Parser\BankDate;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

final class MutationKillsTest extends TestCase
{
    #[Test]
    public function senderMatchingTrimsCaseAndQuotesTheDomain(): void
    {
        $facade = new BankMailsParser();
        $body = $this->tatra();

        $this->assertSame('1.00', $facade->parse($body, " \t")->money?->toDecimalString());
        $this->assertSame('1.00', $facade->parse($body, '<Notify@TatraBanka.sk>')->money?->toDecimalString());

        try {
            $facade->parse($body, 'user@tatrabankaXsk');
            $this->fail('A dot in the bank domain must not match an arbitrary character');
        } catch (UnsupportedBankException) {
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function datesKeepSurroundingWhitespaceOutOfTheValue(): void
    {
        $parsed = BankDate::parse(' 6.9.2019', '!j.n.Y', BankDate::bratislava());

        $this->assertNotNull($parsed);
        $this->assertSame('2019-09-06', $parsed->format('Y-m-d'));
    }

    #[Test]
    public function ambiguousBankMessageNamesTheParsersAfterTheSentence(): void
    {
        $both = "16.1.2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.\n"
            . "\n"
            . "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +2,00 CZK\n";

        try {
            new BankMailsParser()->parse($both);
            $this->fail('Two banks must be reported');
        } catch (AmbiguousBankException $exception) {
            $this->assertStringContainsString(
                'Email matches more than one bank parser: Tomaj\\BankMailsParser\\Parser\\TatraBanka\\TatraBankaMailParser',
                $exception->getMessage(),
            );
        }
    }

    #[Test]
    public function cardPayDuplicateNamesTheFieldAfterTheSentence(): void
    {
        $parser = new TatraBankaSimpleMailParser();

        try {
            $parser->parse('AMT=10.00 AMT=99.00 CURR=978 VS=1 RES=OK');
            $this->fail('A conflicting duplicate key must fail');
        } catch (InvalidEmailFormatException $exception) {
            $this->assertStringContainsString('Duplicate CardPay field amount', $exception->getMessage());
        }
    }

    #[Test]
    public function cardPayTimestampIsFourteenDigitsAndNothingElse(): void
    {
        $parser = new TatraBankaSimpleMailParser();

        try {
            $parser->parse('TIMESTAMP=01012024120000999 RES=OK AMT=10.00 CURR=978 VS=1');
            $this->fail('A timestamp longer than 14 digits is not a date');
        } catch (InvalidEmailFormatException $exception) {
            $this->assertStringContainsString('TIMESTAMP is not a valid date', $exception->getMessage());
        }
    }

    #[Test]
    public function cardPayAndStatementsStillRequireTheBankDomain(): void
    {
        $facade = new BankMailsParser();
        $card = $facade->parse("VS=1111 RES=OK AMT=10.00 CURR=978\n", 'a@tatrabanka.sk');
        $this->assertSame('10.00', $card->money?->toDecimalString());
        $this->assertNull($facade->tryParse("-----BEGIN PGP MESSAGE-----\nabc\n-----END PGP MESSAGE-----\n"));

        $withDecryptor = new BankMailsParser(decryptor: new TatraBankaMailDecryptor('/nonexistent/key.asc', 'heslo'));
        try {
            $withDecryptor->parse("-----BEGIN PGP MESSAGE-----\nabc\n-----END PGP MESSAGE-----\n", 'notify@tatrabanka.sk');
            $this->fail('A PGP statement with a decryptor still has to open the key');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString('PGP', $exception->getMessage());
        }
        $this->assertNull($withDecryptor->tryParse("-----BEGIN PGP MESSAGE-----\nabc\n-----END PGP MESSAGE-----\n", 'a@example.com'));
    }

    #[Test]
    public function currencyNumericCodesAreTheWholeValue(): void
    {
        try {
            Currency::fromCode('EUR978');
            $this->fail('A numeric code glued to letters is not ISO 4217');
        } catch (\InvalidArgumentException) {
            $this->assertTrue(true);
        }

        try {
            Currency::fromCode('978EUR');
            $this->fail('A numeric code glued to letters is not ISO 4217');
        } catch (\InvalidArgumentException) {
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function vubAmountIsTheWholeField(): void
    {
        $parser = new VubMailParser();
        $kept = $parser->parse("Suma: 1,00   \nVS: 10\n");
        $this->assertSame('1.00', $kept->money?->toDecimalString());
        $payer = $parser->parse("Z účtu:   SK12  \nSuma: 1,00\n");
        $this->assertSame('SK12', $payer->accountNumber);
        $this->assertNull($payer->sourceAccountNumber);

        foreach (['Suma: xx1,00', 'Suma: 1,00 abc'] as $amountLine) {
            $unreadable = $parser->parse($amountLine . "\nVS: 10\n");
            $this->assertNull($unreadable->money, $amountLine);
            $this->assertSame('10', $unreadable->vs, $amountLine);
        }
    }

    #[Test]
    public function csobSkipsAHeaderThatIsNotATransactionAndKeepsTheFirstError(): void
    {
        $parser = new CsobMailParser();
        $second = $parser->parseAll(
            "Dne 1.1.2020 byla na účtu\n"
            . "\n"
            . "Dne 25.9.2018 14:30 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +2,00 CZK\n",
        );

        $this->assertCount(1, $second);
        $this->assertSame('2.00', $second[0]->money?->toDecimalString());
        $this->assertSame('2018-09-25T14:30:00+02:00', $second[0]->transactionDate?->format('c'));

        $unreadable = $parser->parseAll(
            "Dne 25.9.2018 byla na účtu 111 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +1.2.3 CZK\n"
            . "\n"
            . "Dne 26.9.2018 byla na účtu 222 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +9.8.7 CZK\n",
        );
        $this->assertCount(2, $unreadable);
        $this->assertNull($unreadable[0]->money);
        $this->assertSame('111', $unreadable[0]->accountNumber);
        $this->assertNull($unreadable[1]->money);
        $this->assertSame('222', $unreadable[1]->accountNumber);
    }

    #[Test]
    public function csobSkSkipsAHeaderThatIsNotATransactionAndKeepsTheFirstError(): void
    {
        $parser = new SkCsobMailParser();
        $second = $parser->parseAll(
            "dňa 1.1.2020 bola na účte\n"
            . "dňa 6.9.2019 bola na účte SK11 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +3,00 EUR\n",
        );

        $this->assertCount(1, $second);
        $this->assertSame('3.00', $second[0]->money?->toDecimalString());

        $unreadable = $parser->parseAll(
            "dňa 6.9.2019 bola na účte SK11 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +1.2.3 EUR\n"
            . "dňa 7.9.2019 bola na účte SK22 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +9.8.7 EUR\n",
        );
        $this->assertCount(2, $unreadable);
        $this->assertNull($unreadable[0]->money);
        $this->assertSame('SK11', $unreadable[0]->ownAccountNumber);
        $this->assertNull($unreadable[1]->money);
        $this->assertSame('SK22', $unreadable[1]->ownAccountNumber);
        try {
            $parser->parseAll('not a mail');
            $this->fail('An empty parse still fails');
        } catch (InvalidEmailFormatException $exception) {
            $this->assertStringContainsString('Could not find transaction pattern', $exception->getMessage());
        }
    }

    #[Test]
    public function moneyRejectsPartialTokensAndAcceptsTheIntegerLimits(): void
    {
        try {
            Money::fromDecimalString('1.2310', 'EUR');
            $this->fail('Extra fraction digits that are not zero must fail');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('more fraction digits', $exception->getMessage());
        }

        try {
            Money::fromDecimalString('1.234', 'EUR');
            $this->fail('Three fraction digits are ambiguous without a separator hint');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('Ambiguous decimal amount', $exception->getMessage());
        }

        try {
            Money::fromDecimalString('1.234.5678', 'EUR');
            $this->fail('A thousands group must be exactly three digits');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('Invalid decimal amount', $exception->getMessage());
        }

        foreach (['x1 234', '1 234x'] as $amount) {
            try {
                Money::fromDecimalString($amount, 'EUR');
                $this->fail($amount . ' is not a grouped amount');
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('Invalid decimal amount', $exception->getMessage());
            }
        }

        $this->assertSame('99.00', Money::fromDecimalString('99.00', 'EUR')->toDecimalString());
        $this->assertSame(
            '92233720368547758.07',
            Money::fromDecimalString('92233720368547758.07', 'EUR')->toDecimalString(),
        );
    }

    private function tatra(): string
    {
        return "Vazeny klient,\n\n16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 1,00 EUR.\n"
            . "Referencia platitela: /VS1/SS/KS\n";
    }
}
