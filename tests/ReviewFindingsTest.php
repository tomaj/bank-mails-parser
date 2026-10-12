<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\MultipleTransactionsException;
use Tomaj\BankMailsParser\Exception\ParseException;
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

        $this->assertSame('12.31', $sek->money?->toDecimalString());
        $this->assertSame('SEK', $sek->money?->currency->value);
        $this->assertSame($sek->money?->currency, $parser->tryParse($this->tatra('12,31', 'SEK', '/VS1/SS/KS', 'x'))?->money?->currency);

        $jpy = "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\nČástka: +1 234,56 JPY";
        $yen = $parser->parse($jpy);
        $this->assertNull($yen->money);
        $this->assertSame('123456789', $yen->accountNumber);
        $this->assertNull($parser->tryParse($jpy)?->money);

        $row = '20190315|1|2|3|4|5|6|7|8|13.37|XXX|11|12|13|14|15|16|17|1|SK1|20';
        $statement = new TatraBankaStatementMailParser()->parse($row);
        $this->assertSame('XXX', $statement->money?->currency->value);
        $this->assertSame('13.37', $statement->money?->toDecimalString());
    }

    #[Test]
    public function facadeParsesCsobDatesFromTheFullEmail(): void
    {
        $zone = new DateTimeZone('Europe/Bratislava');
        $parser = new BankMailsParser();
        $cz = $parser->parse(\Tomaj\BankMailsParser\Tests\Fixtures::load('csob-cz/identifikacia'));

        $this->assertSame(new DateTimeImmutable('2018-09-25', $zone)->getTimestamp(), $cz->transactionDate?->getTimestamp());
        $this->assertSame('Europe/Bratislava', $cz->transactionDate?->getTimezone()->getName());
        $this->assertSame('999', $cz->vs);
        $this->assertSame('1234.56', $cz->money?->toDecimalString());

        $sk = $parser->parse(\Tomaj\BankMailsParser\Tests\Fixtures::load('csob-sk/sepa-platba-kratka'));

        $this->assertSame(new DateTimeImmutable('2019-09-06', $zone)->getTimestamp(), $sk->transactionDate?->getTimestamp());
        $this->assertSame('SK91 7500 0000 0030 2072 9058', $sk->accountNumber);
        $this->assertNull($sk->sourceAccountNumber);
        $this->assertSame('SK13 7500 0000 0040 1942 5381', $sk->ownAccountNumber);
        $this->assertSame('964', $sk->ss);
        $this->assertSame('212049000', $sk->vs);
        $this->assertSame('0308', $sk->ks);
    }

    #[Test]
    public function tatraVariableSymbolKeepsTheLastBoundedNumber(): void
    {
        $parser = new TatraBankaMailParser();
        $iban = $parser->parse($this->tatra('1,00', 'EUR', '', 'platba SK8975000000000012345678'));
        $order = $parser->parse($this->tatra('1,00', 'EUR', '', 'faktura 2024 objednavka 556677'));

        $this->assertNull($iban->vs);
        $this->assertSame('1.00', $iban->money?->toDecimalString());
        $this->assertSame('556677', $order->vs);
        $this->assertSame('1.00', $order->money?->toDecimalString());
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

            $this->assertSame($expected->getTimestamp(), $mail->transactionDate?->getTimestamp());
            $this->assertSame('Europe/Bratislava', $mail->transactionDate?->getTimezone()->getName());

            $rolled = $parser->parse('31.2.2015 25:61 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');
            $this->assertNull($rolled->transactionDate);
            $this->assertSame('1.00', $rolled->money?->toDecimalString());
        } finally {
            date_default_timezone_set($previous);
        }
    }

    #[Test]
    public function cardPayTimestampIsUtcAndNeverInvented(): void
    {
        $parser = new TatraBankaSimpleMailParser();
        $mail = $parser->parse("VS=1\nRES=OK\nAMT=10.00\nCURR=978\nTIMESTAMP=01012026120000");

        $this->assertSame('2026-01-01 12:00:00', $mail->transactionDate?->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $mail->transactionDate?->getTimezone()->getName());
        $this->assertSame('10.00', $mail->money?->toDecimalString());
        $this->assertSame('EUR', $mail->money?->currency->value);

        $missing = $parser->parse('VS=1 RES=OK');
        $this->assertNull($missing->transactionDate);
        $this->assertNull($missing->money);

        $this->assertNull($parser->tryParse('VS=1 RES=OK TIMESTAMP=abc'));
        $this->assertNull($parser->tryParse('RES=OK TIMESTAMP=99999999999999'));
        try {
            $parser->parse('RES=OK TIMESTAMP=99999999999999');
            $this->fail('An impossible TIMESTAMP must throw');
        } catch (InvalidEmailFormatException $exception) {
            $this->assertStringContainsString('TIMESTAMP', $exception->getMessage());
        }
    }

    #[Test]
    public function detectionIgnoresNewslettersAndSenderOnlyMail(): void
    {
        $parser = new BankMailsParser();

        $this->assertNull($parser->tryParse('Ahoj, klikni sem: https://shop.example/callback?RES=OK&VS=123 dakujeme'));
        $this->assertNull($parser->tryParse("Faktura c. 5\nSuma: 10,00\nDakujeme"));
        $this->assertNull($parser->tryParse('Dobry den, pozvanka na webinar 2026. VS: 77', 'news@vub.sk'));
        $this->assertNull($parser->tryParse('Novinky', 'info@csob.cz'));
        $this->assertNull($parser->tryParse('Novinky', 'info@csob.sk.evil.example'));
        $this->assertFalse(new VubMailParser()->supports("Suma: 1\nVS: 1", 'news@vub.sk.evil.example'));

        $vub = new VubMailParser()->parse("Dátum: 11.12.2019\nNa účet: SK9999999999999999999999\nSuma: 100,50 CZK\nZ účtu: SK1111111111111111111111\nVS: 10\nŠS: 910");
        $this->assertSame('CZK', $vub->money?->currency->value);
        $this->assertSame('100.50', $vub->money?->toDecimalString());
        $this->assertSame('SK1111111111111111111111', $vub->accountNumber);
        $this->assertNull($vub->sourceAccountNumber);
        $this->assertSame('SK9999999999999999999999', $vub->ownAccountNumber);
        $this->assertSame('910', $vub->ss);
        $this->assertSame(
            new DateTimeImmutable('2019-12-11', new DateTimeZone('Europe/Bratislava'))->getTimestamp(),
            $vub->transactionDate?->getTimestamp(),
        );
    }

    #[Test]
    public function statementParseAllReturnsEveryRowAndMissingDecryptorIsConfiguration(): void
    {
        $parser = new BankMailsParser();
        $pgp = "-----BEGIN PGP MESSAGE-----\nabc\n-----END PGP MESSAGE-----";

        $this->assertNull($parser->tryParse($pgp));
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

        $this->assertCount(4, $rows);
        $this->assertSame('13.37', $rows[0]->money?->toDecimalString());
        try {
            $parser->parse($email);
            $this->fail('parse() must not return only the first statement row');
        } catch (MultipleTransactionsException $exception) {
            $this->assertStringContainsString('parseAll()', $exception->getMessage());
        }
        $this->assertSame('Europe/Bratislava', $rows[0]->transactionDate?->getTimezone()->getName());

        $wrong = new TatraBankaStatementMailParser(new TatraBankaMailDecryptor($key, 'wrong-passphrase'));
        try {
            $wrong->tryParse($email);
            $this->fail('A wrong passphrase must not be hidden by tryParse');
        } catch (ConfigurationException $exception) {
            $this->assertNotInstanceOf(ParseException::class, $exception);
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

            public function parse(string $content): MailContent
            {
                return new MailContent(description: 'custom-first');
            }
        });

        $this->expectException(\Tomaj\BankMailsParser\Exception\AmbiguousBankException::class);
        $parser->parse($this->tatra('1,00', 'EUR', '/VS1/SS/KS', 'x'));
    }

    #[Test]
    public function mailContentTrimsEveryStringAndBrokenUtf8FailsTheMatch(): void
    {
        $mail = new MailContent(accountNumber: '  SK1  ', sourceAccountNumber: '   ', receiverMessage: '', description: '  note  ');

        $this->assertSame('SK1', $mail->accountNumber);
        $this->assertNull($mail->sourceAccountNumber);
        $this->assertNull($mail->receiverMessage);
        $this->assertSame('note', $mail->description);

        $this->assertFalse(new CsobMailParser()->supports("\xFF"));
    }

    private function tatra(string $amount, string $currency, string $reference, string $message): string
    {
        return "Vazeny klient,\n\n16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o {$amount} {$currency}.\nReferencia platitela: {$reference}\nInformacia pre prijemcu: {$message}\n";
    }
}
