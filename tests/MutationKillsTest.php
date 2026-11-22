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

        self::assertSame('1.00', $facade->parse($body, " \t")->money?->toDecimalString());
        self::assertSame('1.00', $facade->parse($body, '<Notify@TatraBanka.sk>')->money?->toDecimalString());

        try {
            $facade->parse($body, 'user@tatrabankaXsk');
            self::fail('A dot in the bank domain must not match an arbitrary character');
        } catch (UnsupportedBankException $exception) {
            self::assertStringContainsString('No parser found', $exception->getMessage());
        }
    }

    #[Test]
    public function datesKeepSurroundingWhitespaceOutOfTheValue(): void
    {
        $parsed = BankDate::parse(' 6.9.2019', '!j.n.Y', BankDate::bratislava());

        self::assertNotNull($parsed);
        self::assertSame('2019-09-06', $parsed->format('Y-m-d'));
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
            self::fail('Two banks must be reported');
        } catch (AmbiguousBankException $exception) {
            self::assertStringContainsString(
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
            self::fail('A conflicting duplicate key must fail');
        } catch (InvalidEmailFormatException $exception) {
            self::assertStringContainsString('Duplicate CardPay field amount', $exception->getMessage());
        }
    }

    #[Test]
    public function cardPayTimestampIsFourteenDigitsAndNothingElse(): void
    {
        $parser = new TatraBankaSimpleMailParser();

        try {
            $parser->parse('TIMESTAMP=01012024120000999 RES=OK AMT=10.00 CURR=978 VS=1');
            self::fail('A timestamp longer than 14 digits is not a date');
        } catch (InvalidEmailFormatException $exception) {
            self::assertStringContainsString('TIMESTAMP is not a valid date', $exception->getMessage());
        }
    }

    #[Test]
    public function cardPayAndStatementsStillRequireTheBankDomain(): void
    {
        $facade = new BankMailsParser();
        $card = $facade->parse("VS=1111 RES=OK AMT=10.00 CURR=978\n", 'a@tatrabanka.sk');
        self::assertSame('10.00', $card->money?->toDecimalString());
        self::assertNull($facade->tryParse("-----BEGIN PGP MESSAGE-----\nabc\n-----END PGP MESSAGE-----\n"));

        $withDecryptor = new BankMailsParser(decryptor: new TatraBankaMailDecryptor('/nonexistent/key.asc', 'heslo'));
        try {
            $withDecryptor->parse("-----BEGIN PGP MESSAGE-----\nabc\n-----END PGP MESSAGE-----\n", 'notify@tatrabanka.sk');
            self::fail('A PGP statement with a decryptor still has to open the key');
        } catch (ConfigurationException $exception) {
            self::assertStringContainsString('PGP', $exception->getMessage());
        }
        self::assertNull($withDecryptor->tryParse("-----BEGIN PGP MESSAGE-----\nabc\n-----END PGP MESSAGE-----\n", 'a@example.com'));
    }

    #[Test]
    public function currencyNumericCodesAreTheWholeValue(): void
    {
        try {
            Currency::fromCode('EUR978');
            self::fail('A numeric code glued to letters is not ISO 4217');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Unsupported currency code "EUR978".', $exception->getMessage());
        }

        try {
            Currency::fromCode('978EUR');
            self::fail('A numeric code glued to letters is not ISO 4217');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Unsupported currency code "978EUR".', $exception->getMessage());
        }
    }

    #[Test]
    public function vubAmountIsTheWholeField(): void
    {
        $parser = new VubMailParser();
        $kept = $parser->parse("Suma: 1,00   \nVS: 10\n");
        self::assertSame('1.00', $kept->money?->toDecimalString());
        $payer = $parser->parse("Z účtu:   SK12  \nSuma: 1,00\n");
        self::assertSame('SK12', $payer->accountNumber);
        self::assertNull($payer->sourceAccountNumber);

        foreach (['Suma: xx1,00', 'Suma: 1,00 abc'] as $amountLine) {
            $unreadable = $parser->parse($amountLine . "\nVS: 10\n");
            self::assertNull($unreadable->money, $amountLine);
            self::assertSame('10', $unreadable->vs, $amountLine);
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

        self::assertCount(1, $second);
        self::assertSame('2.00', $second[0]->money?->toDecimalString());
        self::assertSame('2018-09-25T14:30:00+02:00', $second[0]->transactionDate?->format('c'));

        $unreadable = $parser->parseAll(
            "Dne 25.9.2018 byla na účtu 111 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +1.2.3 CZK\n"
            . "\n"
            . "Dne 26.9.2018 byla na účtu 222 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +9.8.7 CZK\n",
        );
        self::assertCount(2, $unreadable);
        self::assertNull($unreadable[0]->money);
        self::assertSame('111', $unreadable[0]->accountNumber);
        self::assertNull($unreadable[1]->money);
        self::assertSame('222', $unreadable[1]->accountNumber);
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

        self::assertCount(1, $second);
        self::assertSame('3.00', $second[0]->money?->toDecimalString());

        $unreadable = $parser->parseAll(
            "dňa 6.9.2019 bola na účte SK11 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +1.2.3 EUR\n"
            . "dňa 7.9.2019 bola na účte SK22 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +9.8.7 EUR\n",
        );
        self::assertCount(2, $unreadable);
        self::assertNull($unreadable[0]->money);
        self::assertSame('SK11', $unreadable[0]->ownAccountNumber);
        self::assertNull($unreadable[1]->money);
        self::assertSame('SK22', $unreadable[1]->ownAccountNumber);
        try {
            $parser->parseAll('not a mail');
            self::fail('An empty parse still fails');
        } catch (InvalidEmailFormatException $exception) {
            self::assertStringContainsString('Could not find transaction pattern', $exception->getMessage());
        }
    }

    #[Test]
    public function moneyRejectsPartialTokensAndAcceptsTheIntegerLimits(): void
    {
        try {
            Money::fromDecimalString('1.2310', 'EUR');
            self::fail('Extra fraction digits that are not zero must fail');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('more fraction digits', $exception->getMessage());
        }

        try {
            Money::fromDecimalString('1.234', 'EUR');
            self::fail('Three fraction digits are ambiguous without a separator hint');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('Ambiguous decimal amount', $exception->getMessage());
        }

        try {
            Money::fromDecimalString('1.234.5678', 'EUR');
            self::fail('A thousands group must be exactly three digits');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('Invalid decimal amount', $exception->getMessage());
        }

        foreach (['x1 234', '1 234x'] as $amount) {
            try {
                Money::fromDecimalString($amount, 'EUR');
                self::fail($amount . ' is not a grouped amount');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString('Invalid decimal amount', $exception->getMessage());
            }
        }

        self::assertSame('99.00', Money::fromDecimalString('99.00', 'EUR')->toDecimalString());
        self::assertSame(
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
