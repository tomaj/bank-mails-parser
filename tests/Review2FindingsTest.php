<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\BankDate;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

#[CoversClass(Money::class)]
#[CoversClass(Currency::class)]
#[CoversClass(BankDate::class)]
#[CoversClass(VubMailParser::class)]
#[CoversClass(CsobMailParser::class)]
#[CoversClass(SkCsobMailParser::class)]
#[CoversClass(TatraBankaMailParser::class)]
#[CoversClass(TatraBankaSimpleMailParser::class)]
#[CoversClass(TatraBankaStatementMailParser::class)]
#[CoversClass(TatraBankaMailDecryptor::class)]
#[CoversClass(BankMailsParser::class)]
final class Review2FindingsTest extends TestCase
{
    #[Test]
    public function groupedAmountsUseTheSameThousandsSeparators(): void
    {
        $vub = new VubMailParser();
        $cases = [
            '1 234,56' => '1234.56',
            '1 234,56 EUR' => '1234.56',
            "1\u{00A0}234,56 EUR" => '1234.56',
            "1\u{2009}234,56 EUR" => '1234.56',
            "1\u{202F}234,56 EUR" => '1234.56',
            "1\t234,56 EUR" => '1234.56',
            '12 345 678,90' => '12345678.90',
            '-13,37' => '-13.37',
            '13,37EUR' => '13.37',
        ];
        foreach ($cases as $suma => $expected) {
            $mail = $vub->parse($this->vub($suma));
            self::assertSame($expected, $mail->money?->toDecimalString(), $suma);
            self::assertSame('EUR', $mail->money->currency->value, $suma);
        }

        $csob = new CsobMailParser();
        foreach ([
            '+1 234,56 CZK' => '1234.56',
            "+1\u{202F}234,56 CZK" => '1234.56',
            "+1\u{2009}234,56 CZK" => '1234.56',
            "+1\t234,56 CZK" => '1234.56',
            '+1 234,00 JPY' => '1234',
            '+1 234 JPY' => '1234',
        ] as $amount => $expected) {
            $mail = $csob->parse($this->csob($amount));
            self::assertSame($expected, $mail->money?->toDecimalString(), $amount);
        }

        $thin = new SkCsobMailParser()->parse($this->sk("+\u{2009}10,00"));
        self::assertSame('10.00', $thin->money?->toDecimalString());
        $tab = new TatraBankaMailParser()->parse("16.1.2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 1\t234,56 EUR.");
        self::assertSame('1234.56', $tab->money?->toDecimalString());
    }

    #[Test]
    public function unreadableVubAmountsFailTheSameWay(): void
    {
        $parser = new VubMailParser();
        $letters = $parser->parse($this->vub('abc'));
        self::assertNull($letters->money);
        self::assertSame('2918721123', $letters->vs);
        $dotted = $parser->parse($this->vub('13.37'));
        self::assertNull($dotted->money);
    }

    #[Test]
    public function unknownCardPayCurrencyKeepsTheRestOfTheNotification(): void
    {
        $parser = new TatraBankaSimpleMailParser();
        $dkk = $parser->parse('AMT=10.00 CURR=208 VS=123 RES=OK AC=1 TIMESTAMP=01012026120000 HMAC=abc');
        self::assertSame('10.00', $dkk->money?->toDecimalString());
        self::assertSame('DKK', $dkk->money->currency->value);
        self::assertSame('123', $dkk->vs);
        self::assertSame('OK', $dkk->res);

        foreach ([['946', 'RON'], ['124', 'CAD'], ['036', 'AUD'], ['978', 'EUR']] as [$numeric, $alpha]) {
            $mail = $parser->parse("AMT=10.00 CURR=$numeric VS=9 RES=OK");
            self::assertSame($alpha, $mail->money?->currency->value, $numeric);
            self::assertSame('9', $mail->vs);
        }

        $historical = $parser->parse('AMT=10.00 CURR=975 VS=77 RES=OK HMAC=abc');
        self::assertSame('10.00', $historical->money?->toDecimalString());
        self::assertSame('BGN', $historical->money->currency->value);
        self::assertSame('77', $historical->vs);
        self::assertSame('OK', $historical->res);
        $kuna = $parser->parse('AMT=10.00 CURR=HRK VS=78 RES=OK');
        self::assertSame('HRK', $kuna->money?->currency->value);

        $missingCurrency = $parser->parse('AMT=10.00 VS=5 RES=OK');
        self::assertSame('10.00', $missingCurrency->money?->toDecimalString());
        self::assertSame('EUR', $missingCurrency->money->currency->value);
        self::assertSame('5', $missingCurrency->vs);
    }

    #[Test]
    public function cardPayDoesNotClaimProseThatMentionsRes(): void
    {
        $csob = "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +10,00 CZK\n"
            . "Zpráva příjemci: rezervace RES=42\n"
            . "Variabilní symbol: 23456789\n";
        $simple = new TatraBankaSimpleMailParser();
        self::assertFalse($simple->supports($csob));
        self::assertFalse($simple->supports('Ahoj, RES=nieco v texte'));

        $mail = new BankMailsParser()->parse($csob);
        self::assertSame('10.00', $mail->money?->toDecimalString());
        self::assertSame('23456789', $mail->vs);
        self::assertNull($mail->res);
        self::assertSame('123456789', $mail->accountNumber);

        $wrapped = "From: notifikacie@bank.sk\nSubject: Notifikacia\n\n\u{FEFF}" . $csob;
        $again = new BankMailsParser()->parse($wrapped);
        self::assertSame('10.00', $again->money?->toDecimalString());
        self::assertSame('23456789', $again->vs);
        self::assertNull($again->res);
    }

    #[Test]
    public function rawMailWrappersStayWithTheSameBank(): void
    {
        $headers = "From: notifikacie@bank.sk\nSubject: Notifikacia\n\n";
        $facade = new BankMailsParser();
        $bodies = [
            '16.1.2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 12,31 EUR.' . "\nReferencia platitela: /VS1234056789/SS/KS\n",
            "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\nČástka: +1 234,56 CZK\nVariabilní symbol: 23456789\n",
            "dňa 6.9.2019 bola na účte SK13 7500 0000 0040 1942 5381 zaúčtovaná suma SEPA platobného príkazu:\nsuma: +1150,00 EUR\nreferencia platiteľa: /VS212049000/SS964/KS0308\n",
            Fixtures::load('vub/platba-bez-sign'),
            'AMT=33.99 CURR=978 VS=5000001234 RES=OK AC=740017 TIMESTAMP=25112016223023 HMAC=abc',
        ];
        foreach ($bodies as $body) {
            $plain = $facade->parse($body);
            foreach ([$headers . $body, "\u{FEFF}" . $body, $headers . "\u{FEFF}" . $body] as $wrapped) {
                $parsed = $facade->parse($wrapped);
                self::assertSame($plain->money?->toDecimalString(), $parsed->money?->toDecimalString());
                self::assertSame($plain->money?->currency->value, $parsed->money?->currency->value);
                self::assertSame($plain->vs, $parsed->vs);
                self::assertSame($plain->res, $parsed->res);
                self::assertSame($plain->accountNumber, $parsed->accountNumber);
                self::assertSame($plain->transactionDate?->format('Y-m-d H:i:s'), $parsed->transactionDate?->format('Y-m-d H:i:s'));
            }
        }

        $cardpay = $bodies[4];
        $simple = new TatraBankaSimpleMailParser();
        $embedded = "Dobry den,\n\nnotifikacia:\n$cardpay\n\nS pozdravom";
        $quoted = preg_replace('/^/m', '> ', $cardpay);
        self::assertIsString($quoted);
        $split = str_replace(' ', "\n", $cardpay);
        foreach ([$embedded, $split, "Dobry den,\n\n" . $split . "\n\nS pozdravom"] as $variant) {
            self::assertTrue($simple->supports($variant));
            $parsed = $facade->parse($variant);
            self::assertSame('33.99', $parsed->money?->toDecimalString());
            self::assertSame('EUR', $parsed->money->currency->value);
            self::assertSame('5000001234', $parsed->vs);
            self::assertSame('OK', $parsed->res);
        }
        self::assertFalse($simple->supports("---------- Forwarded message ---------\n" . $quoted));
        self::assertNull($simple->tryParse("---------- Forwarded message ---------\n" . $quoted));

        self::assertFalse($simple->supports("RES=OK\nrezervace nie\nAMT=10.00 VS=1 HMAC=abc"));
        self::assertTrue($simple->supports("AMT=10.00 CURR=978 VS=1\n\nRES=OK"));

        $statement = (string) file_get_contents(__DIR__ . '/fixtures/tatrabanka-statement/zasifrovany-vypis.txt');
        $decryptor = new TatraBankaMailDecryptor(__DIR__ . '/fixtures/tatrabanka-statement/private-key.asc', 'heslo');
        $statements = new BankMailsParser(decryptor: $decryptor);
        $plainStatement = $statements->parseAll($statement)[0];
        foreach ([$headers . $statement, "\u{FEFF}" . $statement] as $wrapped) {
            $parsed = $statements->parseAll($wrapped)[0];
            self::assertSame('13.37', $parsed->money?->toDecimalString());
            self::assertSame($plainStatement->accountNumber, $parsed->accountNumber);
            self::assertSame($plainStatement->vs, $parsed->vs);
        }
    }

    #[Test]
    public function vubDoesNotClaimAGenericInvoice(): void
    {
        $invoice = "Faktúra č. 2024001\nSuma: 120,00 EUR\nVS: 2024001\nNa účet: SK3112000000198742637541\nSplatnosť: 10.10.2026";
        $parser = new VubMailParser();
        self::assertFalse($parser->supports($invoice));
        self::assertFalse($parser->supports("Výzva na úhradu\nSuma: 120,00 EUR\nZ účtu: SK3112000000198742637541\nVS: 2024001"));

        try {
            new BankMailsParser()->parse($invoice);
            self::fail('An invoice is not a VÚB notification');
        } catch (UnsupportedBankException) {
            self::assertFalse($parser->supports($invoice));
        }
    }

    #[Test]
    public function pgpConfigurationErrorsAreNotMalformedEmails(): void
    {
        $email = (string) file_get_contents(__DIR__ . '/fixtures/tatrabanka-statement/zasifrovany-vypis.txt');
        $key = __DIR__ . '/fixtures/tatrabanka-statement/private-key.asc';
        foreach ([
            new TatraBankaMailDecryptor('/nonexistent/key.asc', 'heslo'),
            new TatraBankaMailDecryptor($key, 'wrong'),
        ] as $decryptor) {
            $facade = new BankMailsParser(decryptor: $decryptor);
            try {
                $facade->tryParse($email);
                self::fail('tryParse must rethrow configuration errors');
            } catch (ConfigurationException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
            try {
                $facade->parse($email);
                self::fail('parse must throw ConfigurationException');
            } catch (ConfigurationException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function oneBadTransactionFailsTheWholeMessage(): void
    {
        $row = static fn(string $amount): string => "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\nČástka: $amount\nVariabilní symbol: 1\n\n";
        $mail = $row('+10,00 CZK') . $row('+1,505 CZK') . $row('+20,00 CZK');
        $parsed = new CsobMailParser()->parseMulti($mail);
        self::assertCount(3, $parsed);
        self::assertSame('10.00', $parsed[0]->money?->toDecimalString());
        self::assertNull($parsed[1]->money);
        self::assertSame('20.00', $parsed[2]->money?->toDecimalString());

        $sk = "dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:\nsuma: +1,505 EUR\n\n"
            . "dňa 7.9.2019 bola na účte SK92 7500 0000 0030 2072 9059 zaúčtovaná suma SEPA platobného príkazu:\nsuma: +2,00 EUR\n";
        $slovak = new SkCsobMailParser()->parseMulti($sk);
        self::assertCount(2, $slovak);
        self::assertNull($slovak[0]->money);
        self::assertSame('2.00', $slovak[1]->money?->toDecimalString());
    }

    #[Test]
    public function aMalformedStatementRowIsNotDropped(): void
    {
        // desifrovany-vypis.txt with '|15.00|EUR|' in row 2 changed to '|15.005|EUR|', encrypted once to private-key.asc
        // (.asc, so FixtureSnapshotTest does not pick it up). Encrypting at test time was flaky on PHP 8.5+ (#56).
        $armored = (string) file_get_contents(__DIR__ . '/fixtures/tatrabanka-statement/zasifrovany-vypis-chybny-riadok.asc');
        $parser = new TatraBankaStatementMailParser(new TatraBankaMailDecryptor(
            __DIR__ . '/fixtures/tatrabanka-statement/private-key.asc',
            'heslo',
        ));

        try {
            $parser->parseAll($armored);
            self::fail('A bad statement row must fail the message');
        } catch (InvalidEmailFormatException $exception) {
            self::assertStringContainsString('row 2', $exception->getMessage());
        }
    }

    #[Test]
    public function zeroMinorUnitCurrenciesAcceptTrailingZerosOnly(): void
    {
        $ok = Money::fromDecimalString('+1 234,00', 'JPY', ',');
        self::assertSame(1234, $ok->amount);
        self::assertSame('1234', $ok->toDecimalString());
        $padded = Money::fromDecimalString('12.340', 'EUR', '.');
        self::assertSame(1234, $padded->amount);
        self::assertSame('12.34', $padded->toDecimalString());

        $this->expectException(\InvalidArgumentException::class);
        Money::fromDecimalString('1234,01', 'JPY', ',');
    }

    #[Test]
    public function tatraKeepsTheDateFormatsFrom410AndRejectsShortYears(): void
    {
        $parser = new TatraBankaMailParser();
        $cases = [
            '16.1.2015' => '2015-01-16 00:00:00',
            '16. 1. 2015 12:51' => '2015-01-16 12:51:00',
            '2015-01-16 12:51' => '2015-01-16 12:51:00',
            '16.1.2015 12:51:30' => '2015-01-16 12:51:30',
            '2015-01-16' => '2015-01-16 00:00:00',
        ];
        foreach ($cases as $date => $expected) {
            $mail = $parser->parse("$date bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.");
            self::assertSame($expected, $mail->transactionDate?->format('Y-m-d H:i:s'), $date);
        }

        $short = $parser->parse('16.1.15 12:51 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');
        self::assertNull($short->transactionDate);

        $vub = new VubMailParser();
        self::assertNull($vub->parse($this->vub('1,00', '11.12.19'))->transactionDate);
        self::assertSame('2019-12-11', $vub->parse($this->vub('1,00', '11.12.2019'))->transactionDate?->format('Y-m-d'));
        self::assertNull($vub->parse($this->vub('1,00', '1.1.0'))->transactionDate);
        self::assertNull($vub->parse($this->vub('1,00', '31.12.99999'))->transactionDate);
        self::assertSame('1000-01-01', BankDate::parse('1000-01-01', '!Y-m-d', BankDate::bratislava())?->format('Y-m-d'));
        self::assertSame('9999-12-31', BankDate::parse('9999-12-31', '!Y-m-d', BankDate::bratislava())?->format('Y-m-d'));
        self::assertNull(BankDate::parse('0999-01-01', '!Y-m-d', BankDate::bratislava()));

        $gap = $parser->parse('29.3.2015 2:30 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');
        self::assertNull($gap->transactionDate);
        $overlap = $parser->parse('25.10.2015 2:30 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');
        self::assertSame('2015-10-25 02:30:00', $overlap->transactionDate?->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function ibanWithNonBreakingSpaceIsNotTruncated(): void
    {
        $email = "dňa 6.9.2019 bola na účte SK13\u{00A0}7500\u{00A0}0000\u{00A0}0040\u{00A0}1942\u{00A0}5381 zaúčtovaná suma SEPA platobného príkazu:\nsuma: +10,00 EUR\n";
        $mail = new SkCsobMailParser()->parse($email);
        self::assertSame('SK13 7500 0000 0040 1942 5381', $mail->ownAccountNumber);
        self::assertNull($mail->accountNumber);

        $vub = new VubMailParser()->parse($this->vub('1,00', '11.12.2019', "SK55\u{00A0}0200"));
        self::assertSame('SK55 0200', $vub->ownAccountNumber);
        self::assertSame('SK4502000000001984100123', $vub->accountNumber);
    }

    #[Test]
    public function anEmptyParseAllDoesNotWarn(): void
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
                throw new InvalidEmailFormatException('unused');
            }

            #[\Override]
            public function parseAll(string $content): array
            {
                return [];
            }
        });

        $this->expectException(InvalidEmailFormatException::class);
        $this->expectExceptionMessageIsOrContains('Parser returned no transactions');
        $parser->parse('anything');
    }

    #[Test]
    public function statementAccountNumberIsThePerRowMerchantAccount(): void
    {
        $lines = file(__DIR__ . '/fixtures/tatrabanka-statement/desifrovany-vypis.txt', FILE_IGNORE_NEW_LINES);
        self::assertIsArray($lines);
        $header = explode('|', $lines[0]);
        self::assertSame('Èíslo uètu', rtrim($header[19]));
        $accounts = [];
        $parser = new TatraBankaStatementMailParser();
        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $accounts[] = $parser->parse($line)->accountNumber;
        }
        self::assertGreaterThan(1, count(array_unique($accounts)));
        self::assertContains('2621234415', $accounts);
        self::assertContains('2946123663', $accounts);
    }

    private function vub(string $suma, string $date = '11.12.2019', string $account = 'SK5502000000003142860123'): string
    {
        return "Dátum:   $date\nNa účet: $account\nSuma:    $suma\nZ účtu:  SK4502000000001984100123\nVS:      2918721123\nKS:      0308\n";
    }

    private function csob(string $amount): string
    {
        return "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\nČástka: $amount\n";
    }

    private function sk(string $amount): string
    {
        return "dňa 6.9.2019 bola na účte SK91 7500 0000 0030 2072 9058 zaúčtovaná suma SEPA platobného príkazu:\nsuma: $amount EUR\n";
    }
}
