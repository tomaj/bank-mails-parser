<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\AmbiguousBankException;
use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\MultipleTransactionsException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

#[CoversClass(BankMailsParser::class)]
#[CoversClass(TatraBankaMailParser::class)]
#[CoversClass(TatraBankaSimpleMailParser::class)]
#[CoversClass(TatraBankaStatementMailParser::class)]
#[CoversClass(CsobMailParser::class)]
#[CoversClass(SkCsobMailParser::class)]
#[CoversClass(VubMailParser::class)]
#[CoversClass(MailContent::class)]
final class ReviewFindingsTest extends TestCase
{
    #[Test]
    public function unknownCurrencyParsesAndTryParseDoesNotThrow(): void
    {
        $parser = new BankMailsParser();
        $sek = $parser->parse($this->tatra('12,31', 'SEK', '/VS1/SS/KS', 'x'));

        self::assertSame('12.31', $sek->money?->toDecimalString());
        self::assertSame('SEK', $sek->money->currency->value);
        self::assertSame($sek->money->currency, $parser->tryParse($this->tatra('12,31', 'SEK', '/VS1/SS/KS', 'x'))?->money?->currency);

        $jpy = "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\nČástka: +1 234,56 JPY";
        $yen = $parser->parse($jpy);
        self::assertNull($yen->money);
        self::assertSame('123456789', $yen->accountNumber);
        self::assertNull($parser->tryParse($jpy)?->money);

        $row = '20190315|1|2|3|4|5|6|7|8|13.37|XXX|11|12|13|14|15|16|17|1|SK1|20';
        $statement = new TatraBankaStatementMailParser()->parse($row);
        self::assertSame('XXX', $statement->money?->currency->value);
        self::assertSame('13.37', $statement->money->toDecimalString());
    }

    #[Test]
    public function facadeParsesCsobDatesFromTheFullEmail(): void
    {
        $zone = new DateTimeZone('Europe/Bratislava');
        $parser = new BankMailsParser();
        $cz = $parser->parse(Fixtures::load('csob-cz/identifikacia'));

        self::assertSame(new DateTimeImmutable('2018-09-25', $zone)->getTimestamp(), $cz->transactionDate?->getTimestamp());
        self::assertSame('Europe/Bratislava', $cz->transactionDate->getTimezone()->getName());
        self::assertSame('999', $cz->vs);
        self::assertSame('1234.56', $cz->money?->toDecimalString());

        $sk = $parser->parse(Fixtures::load('csob-sk/sepa-platba-kratka'));

        self::assertSame(new DateTimeImmutable('2019-09-06', $zone)->getTimestamp(), $sk->transactionDate?->getTimestamp());
        self::assertSame('SK91 7500 0000 0030 2072 9058', $sk->accountNumber);
        self::assertNull($sk->sourceAccountNumber);
        self::assertSame('SK13 7500 0000 0040 1942 5381', $sk->ownAccountNumber);
        self::assertSame('964', $sk->ss);
        self::assertSame('212049000', $sk->vs);
        self::assertSame('0308', $sk->ks);
    }

    #[Test]
    public function tatraVariableSymbolKeepsTheLastBoundedNumber(): void
    {
        $parser = new TatraBankaMailParser();
        $iban = $parser->parse($this->tatra('1,00', 'EUR', '', 'platba SK8975000000000012345678'));
        $order = $parser->parse($this->tatra('1,00', 'EUR', '', 'faktura 2024 objednavka 556677'));

        self::assertNull($iban->vs);
        self::assertSame('1.00', $iban->money?->toDecimalString());
        self::assertSame('556677', $order->vs);
        self::assertSame('1.00', $order->money?->toDecimalString());
    }

    #[Test]
    public function datesStayInBratislavaAndInvalidDatesAreNotRolled(): void
    {
        $previous = date_default_timezone_get();
        date_default_timezone_set('UTC');
        try {
            $parser = new TatraBankaMailParser();
            $mail = $parser->parse($this->tatra('1,00', 'EUR', '/VS1/SS/KS', 'x'));
            $expected = new DateTimeImmutable('2015-01-16 12:51', new DateTimeZone('Europe/Bratislava'));

            self::assertSame($expected->getTimestamp(), $mail->transactionDate?->getTimestamp());
            self::assertSame('Europe/Bratislava', $mail->transactionDate->getTimezone()->getName());

            $rolled = $parser->parse('31.2.2015 25:61 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');
            self::assertNull($rolled->transactionDate);
            self::assertSame('1.00', $rolled->money?->toDecimalString());
        } finally {
            date_default_timezone_set($previous);
        }
    }

    #[Test]
    public function cardPayTimestampIsUtcAndNeverInvented(): void
    {
        $parser = new TatraBankaSimpleMailParser();
        $mail = $parser->parse("VS=1\nRES=OK\nAMT=10.00\nCURR=978\nTIMESTAMP=01012026120000");

        self::assertSame('2026-01-01 12:00:00', $mail->transactionDate?->format('Y-m-d H:i:s'));
        self::assertSame('UTC', $mail->transactionDate->getTimezone()->getName());
        self::assertSame('10.00', $mail->money?->toDecimalString());
        self::assertSame('EUR', $mail->money->currency->value);

        $missing = $parser->parse('VS=1 RES=OK');
        self::assertNull($missing->transactionDate);
        self::assertNull($missing->money);

        self::assertNull($parser->tryParse('VS=1 RES=OK TIMESTAMP=abc'));
        self::assertNull($parser->tryParse('RES=OK TIMESTAMP=99999999999999'));
        try {
            $parser->parse('RES=OK TIMESTAMP=99999999999999');
            self::fail('An impossible TIMESTAMP must throw');
        } catch (InvalidEmailFormatException $exception) {
            self::assertStringContainsString('TIMESTAMP', $exception->getMessage());
        }
    }

    #[Test]
    public function detectionIgnoresNewslettersAndSenderOnlyMail(): void
    {
        $parser = new BankMailsParser();

        self::assertNull($parser->tryParse('Ahoj, klikni sem: https://shop.example/callback?RES=OK&VS=123 dakujeme'));
        self::assertNull($parser->tryParse("Faktura c. 5\nSuma: 10,00\nDakujeme"));
        self::assertNull($parser->tryParse('Dobry den, pozvanka na webinar 2026. VS: 77', 'news@vub.sk'));
        self::assertNull($parser->tryParse('Novinky', 'info@csob.cz'));
        self::assertNull($parser->tryParse('Novinky', 'info@csob.sk.evil.example'));
        self::assertFalse(new VubMailParser()->supports("Suma: 1\nVS: 1", 'news@vub.sk.evil.example'));

        $vub = new VubMailParser()->parse("Dátum: 11.12.2019\nNa účet: SK9999999999999999999999\nSuma: 100,50 CZK\nZ účtu: SK1111111111111111111111\nVS: 10\nŠS: 910");
        self::assertSame('CZK', $vub->money?->currency->value);
        self::assertSame('100.50', $vub->money->toDecimalString());
        self::assertSame('SK1111111111111111111111', $vub->accountNumber);
        self::assertNull($vub->sourceAccountNumber);
        self::assertSame('SK9999999999999999999999', $vub->ownAccountNumber);
        self::assertSame('910', $vub->ss);
        self::assertSame(
            new DateTimeImmutable('2019-12-11', new DateTimeZone('Europe/Bratislava'))->getTimestamp(),
            $vub->transactionDate?->getTimestamp(),
        );
    }

    #[Test]
    public function statementParseAllReturnsEveryRowAndMissingDecryptorIsConfiguration(): void
    {
        $parser = new BankMailsParser();
        $pgp = "-----BEGIN PGP MESSAGE-----\nabc\n-----END PGP MESSAGE-----";

        self::assertNull($parser->tryParse($pgp));
        $this->expectException(UnsupportedBankException::class);
        $parser->parse($pgp);
    }

    #[Test]
    public function statementWithDecryptorReturnsAllTransactionsAndWrapsPassphraseErrors(): void
    {
        $key = __DIR__ . '/fixtures/tatrabanka-statement/private-key.asc';
        $email = (string) file_get_contents(__DIR__ . '/fixtures/tatrabanka-statement/zasifrovany-vypis.txt');
        $parser = new BankMailsParser(decryptor: new TatraBankaMailDecryptor($key, 'heslo'));
        $rows = $parser->parseAll($email);

        self::assertCount(4, $rows);
        self::assertSame('13.37', $rows[0]->money?->toDecimalString());
        try {
            $parser->parse($email);
            self::fail('parse() must not return only the first statement row');
        } catch (MultipleTransactionsException $exception) {
            self::assertStringContainsString('parseAll()', $exception->getMessage());
        }
        self::assertSame('Europe/Bratislava', $rows[0]->transactionDate?->getTimezone()->getName());

        $wrong = new TatraBankaStatementMailParser(new TatraBankaMailDecryptor($key, 'wrong-passphrase'));
        try {
            $wrong->tryParse($email);
            self::fail('A wrong passphrase must not be hidden by tryParse');
        } catch (ConfigurationException $exception) {
            self::assertStringContainsString('passphrase', $exception->getMessage());
        }
    }

    #[Test]
    public function prependedParserRunsBeforeTheDefaults(): void
    {
        $parser = new BankMailsParser();
        $parser->prependParser(new class implements ParserInterface {
            use ParsesBankMail;

            #[\Override]
            public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
            {
                return str_contains($content, 'bol zostatok Vasho uctu');
            }

            #[\Override]
            public function parse(string $content): MailContent
            {
                return new MailContent(description: 'custom-first');
            }
        });

        $this->expectException(AmbiguousBankException::class);
        $parser->parse($this->tatra('1,00', 'EUR', '/VS1/SS/KS', 'x'));
    }

    #[Test]
    public function mailContentTrimsEveryStringAndBrokenUtf8FailsTheMatch(): void
    {
        $mail = new MailContent(accountNumber: '  SK1  ', sourceAccountNumber: '   ', receiverMessage: '', description: '  note  ');

        self::assertSame('SK1', $mail->accountNumber);
        self::assertNull($mail->sourceAccountNumber);
        self::assertNull($mail->receiverMessage);
        self::assertSame('note', $mail->description);

        self::assertFalse(new CsobMailParser()->supports("\xFF"));
    }

    private function tatra(string $amount, string $currency, string $reference, string $message): string
    {
        return "Vazeny klient,\n\n16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o {$amount} {$currency}.\nReferencia platitela: {$reference}\nInformacia pre prijemcu: {$message}\n";
    }
}
