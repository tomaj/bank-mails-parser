<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\AmbiguousBankException;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
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
use Tomaj\BankMailsParser\ValueObject\Money;

#[CoversClass(BankMailsParser::class)]
#[CoversClass(AmbiguousBankException::class)]
#[CoversClass(CsobMailParser::class)]
#[CoversClass(SkCsobMailParser::class)]
#[CoversClass(TatraBankaMailParser::class)]
#[CoversClass(TatraBankaSimpleMailParser::class)]
#[CoversClass(TatraBankaStatementMailParser::class)]
#[CoversClass(VubMailParser::class)]
#[CoversClass(Money::class)]
#[CoversClass(MailContent::class)]
final class Review3FindingsTest extends TestCase
{
    #[Test]
    public function payerMessageCannotReplaceTheAmountOrTheBank(): void
    {
        $facade = new BankMailsParser();
        foreach ([
            [$this->csobCz($this->foreignSentences()), 'info@csob.cz'],
            [$this->csobSk('NOVAK JOZEF', $this->foreignSentences()), 'notifikacie@csob.sk'],
        ] as [$mail, $sender]) {
            $parsed = $facade->parse($mail, $sender);
            self::assertSame('1.00', $parsed->money?->toDecimalString());
            self::assertNotSame('777', $parsed->vs);
        }

        $skWithCz = $this->csobSk('NOVAK JOZEF', '1.1.2026 byl na účtu 123 zaúčtována transakce Došlá platba Částka: +99999,00 EUR vs777');
        $kept = $facade->parse($skWithCz);
        self::assertSame('1.00', $kept->money?->toDecimalString());
        self::assertSame('212049000', $kept->vs);
    }

    #[Test]
    public function eachBankIgnoresForeignSentencesPlantedInTheRecipientMessage(): void
    {
        $facade = new BankMailsParser();
        $planted = $this->foreignSentences();

        foreach ([
            $this->tatra('/VS1234056789/SS/KS', $planted),
            $this->csobCz($planted),
            $this->csobSk('NOVAK JOZEF', $planted),
        ] as $mail) {
            $parsed = $facade->parse($mail);
            self::assertSame('1.00', $parsed->money?->toDecimalString());
            self::assertNotSame('777', $parsed->vs);
        }

        $vub = $facade->parse($this->vub("poznamka: $planted"), 'news@vub.sk');
        self::assertSame('1.00', $vub->money?->toDecimalString());
        self::assertSame('1234567890', $vub->vs);
        self::assertSame('SK1', $vub->accountNumber);
        self::assertSame('SK2', $vub->ownAccountNumber);
    }

    #[Test]
    public function freeTextCannotCreateAPhantomTransaction(): void
    {
        $cz = new CsobMailParser()->parseAll($this->csobCz(
            'Dne 1.1.2026 byl na účtu 123456789 zaúčtována transakce Došlá platba Částka: +99999,00 CZK vs777',
        ));
        self::assertCount(1, $cz);
        self::assertSame('1.00', $cz[0]->money?->toDecimalString());
        self::assertSame('23456789', $cz[0]->vs);

        $sk = new SkCsobMailParser()->parseAll($this->csobSk(
            'NOVAK JOZEF',
            'dňa 1.1.2026 bola na účte SK1375000000004019425381 zaúčtovaná suma SEPA platobného príkazu suma: +99999,00 EUR VS777',
        ));
        self::assertCount(1, $sk);
        self::assertSame('1.00', $sk[0]->money?->toDecimalString());
        self::assertSame('212049000', $sk[0]->vs);
    }

    #[Test]
    public function csobSkSymbolsComeOnlyFromThePayerReferenceLine(): void
    {
        $mail = new SkCsobMailParser()->parse($this->csobSk('ADVS2 BOOKS24', 'VS999 SS111 KS24'));

        self::assertSame('212049000', $mail->vs);
        self::assertSame('964', $mail->ss);
        self::assertSame('0308', $mail->ks);
        self::assertSame('VS999 SS111 KS24', $mail->receiverMessage);
    }

    #[Test]
    public function variableSymbolFallsBackToTheLastNumberInTheMessage(): void
    {
        $tatra = new TatraBankaMailParser()->parse($this->tatra('/VS/SS/KS', 'objednavka 2026001234 zo dna 5.1.2026'));
        self::assertSame('2026', $tatra->vs);
        self::assertSame('1.00', $tatra->money?->toDecimalString());
        self::assertSame('objednavka 2026001234 zo dna 5.1.2026', $tatra->receiverMessage);

        $cz = new CsobMailParser()->parse($this->csobCz('faktura 123456 ze dne 5.1.2026', '23456789'));
        self::assertSame('23456789', $cz->vs);
        self::assertSame('1.00', $cz->money?->toDecimalString());

        $withoutField = new CsobMailParser()->parse(str_replace("Variabilní symbol: 23456789\n", '', $this->csobCz('faktura 123456 ze dne 5.1.2026')));
        self::assertSame('2026', $withoutField->vs);
        self::assertSame('1.00', $withoutField->money?->toDecimalString());
        self::assertSame('123456789', $withoutField->accountNumber);
    }

    #[Test]
    public function symbolsLongerThanTenDigitsAreNotTruncated(): void
    {
        $cz = new CsobMailParser()->parse($this->csobCz('hello', '12345678901'));
        self::assertNull($cz->vs);
        self::assertSame('1.00', $cz->money?->toDecimalString());

        $vub = new VubMailParser()->parse($this->vub('VS: 123456789012'));
        self::assertNull($vub->vs);
        self::assertSame('1.00', $vub->money?->toDecimalString());

        $tatra = new TatraBankaMailParser()->parse($this->tatra('/VS123456789012/SS1/KS2', 'x'));
        self::assertSame('123456789012', $tatra->vs);
        self::assertSame('1', $tatra->ss);
        self::assertSame('2', $tatra->ks);
    }

    #[Test]
    public function cardPayReadsOnlyTheFirstBlockAndKeepsResApartFromTres(): void
    {
        $parser = new TatraBankaSimpleMailParser();
        $live = "VS=1111 RES=OK AMT=10.00 CURR=978\n> VS=2222 RES=FAIL AMT=5000.00 CURR=978\n";
        $mail = $parser->parse($live);
        self::assertSame('10.00', $mail->money?->toDecimalString());
        self::assertSame('1111', $mail->vs);
        self::assertSame('OK', $mail->res);
        self::assertNull($mail->tres);

        $both = $parser->parse('RES=FAIL TRES=OK AMT=10.00 CURR=978 VS=1');
        self::assertSame('OK', $both->res);
        self::assertSame('OK', $both->tres);

        $note = $parser->parse("AMT=10.00 CURR=978 VS=5 RES=OK\nPoznamka obchodnika: AMT=1.00 CURR=840");
        self::assertSame('10.00', $note->money?->toDecimalString());
        self::assertSame('EUR', $note->money->currency->value);

        try {
            $parser->parse('AMT=10.00 AMT=99.00 CURR=978 VS=1 RES=OK');
            self::fail('A conflicting duplicate key must fail');
        } catch (InvalidEmailFormatException $exception) {
            self::assertStringContainsString('Duplicate CardPay field', $exception->getMessage());
        }

        $same = $parser->parse('AMT=10.00 AMT=10.00 CURR=978 VS=1 RES=OK');
        self::assertSame('10.00', $same->money?->toDecimalString());

        $wrapped = $this->csobCz("platba za objednavku\nRES=OK VS=777 AMT=99999.00 CURR=203");
        $facade = new BankMailsParser();
        self::assertFalse($parser->supports($wrapped));
        $kept = $facade->parse($wrapped, 'info@csob.cz');
        self::assertSame('1.00', $kept->money?->toDecimalString());
        self::assertSame('CZK', $kept->money->currency->value);
        self::assertNotSame('777', $kept->vs);
    }

    #[Test]
    public function quotedTatraNotificationIsNotAPayment(): void
    {
        $quoted = '> 16.1.2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 99999,00 EUR.';
        $parser = new TatraBankaMailParser();

        self::assertFalse($parser->supports($quoted));
        self::assertNull($parser->tryParse($quoted));
        self::assertNull(new BankMailsParser()->tryParse($quoted));
    }

    #[Test]
    public function emptyVubAccountDoesNotReadTheNextLine(): void
    {
        $mail = new VubMailParser()->parse("Dátum: 1.1.2020\nZ účtu: SK1\nNa účet:\nSuma: 1,00\nVS: 10\nStav: ok\n");

        self::assertSame('SK1', $mail->accountNumber);
        self::assertNull($mail->sourceAccountNumber);
        self::assertNull($mail->ownAccountNumber);
        self::assertSame('1.00', $mail->money?->toDecimalString());
        self::assertSame('10', $mail->vs);
    }

    #[Test]
    public function vubAmountAnchorIgnoresSumaInsideAnotherLine(): void
    {
        $mail = new VubMailParser()->parse(Fixtures::load('vub/platba-odchadzajuca'));

        self::assertSame('1.00', $mail->money?->toDecimalString());
    }

    #[Test]
    public function thousandsGroupsMustBeThreeDigitsAndIntMinIsRejected(): void
    {
        self::assertSame('1234.56', Money::fromDecimalString('1 234,56', 'EUR', ',')->toDecimalString());
        self::assertSame('12345678.90', Money::fromDecimalString('12 345 678,90', 'EUR', ',')->toDecimalString());

        foreach (['1 2 3,45', '81 5', '904094,167'] as $amount) {
            try {
                Money::fromDecimalString($amount, 'EUR', '.');
                self::fail($amount . ' must be rejected');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString($amount, $exception->getMessage());
            }
        }

        try {
            Money::fromDecimalString('-92233720368547758,08', 'EUR', ',');
            self::fail('PHP_INT_MIN must be rejected while parsing');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('92233720368547758,08', $exception->getMessage());
        }
    }

    #[Test]
    public function twoParsersOrTheWrongSenderDoNotSilentlyPickAPayment(): void
    {
        $both = "16.1.2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.\n"
            . "\n"
            . "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +2,00 CZK\n";
        $facade = new BankMailsParser();

        try {
            $facade->parse($both);
            self::fail('Two banks must not resolve to the first parser');
        } catch (AmbiguousBankException $exception) {
            self::assertStringContainsString('TatraBankaMailParser', $exception->getMessage());
            self::assertStringContainsString('CsobMailParser', $exception->getMessage());
        }
        self::assertNull($facade->tryParse($both));

        $onlyCsob = $facade->parse($both, 'info@csob.cz');
        self::assertSame('2.00', $onlyCsob->money?->toDecimalString());
        self::assertSame('CZK', $onlyCsob->money->currency->value);

        try {
            $facade->parse($this->tatra('/VS1/SS/KS', 'x'), 'attacker@evil.test');
            self::fail('An unknown sender must not select a bank from the body');
        } catch (UnsupportedBankException $exception) {
            self::assertStringContainsString('sender', $exception->getMessage());
        }
    }

    #[Test]
    public function defaultParserOrderIsPartOfTheContract(): void
    {
        $parsers = new BankMailsParser()->getParsers();

        self::assertCount(5, $parsers);
        self::assertInstanceOf(TatraBankaMailParser::class, $parsers[0]);
        self::assertInstanceOf(TatraBankaSimpleMailParser::class, $parsers[1]);
        self::assertInstanceOf(CsobMailParser::class, $parsers[2]);
        self::assertInstanceOf(SkCsobMailParser::class, $parsers[3]);
        self::assertInstanceOf(VubMailParser::class, $parsers[4]);

        $withKey = new BankMailsParser(decryptor: new TatraBankaMailDecryptor(__DIR__ . '/fixtures/tatrabanka-statement/private-key.asc', 'heslo'));
        self::assertInstanceOf(TatraBankaStatementMailParser::class, $withKey->getParsers()[2]);
        self::assertCount(6, $withKey->getParsers());
    }

    #[Test]
    public function facadeDoesNotContinueAfterAParseException(): void
    {
        $parser = new BankMailsParser([]);
        $parser->registerParser(new class implements ParserInterface {
            use ParsesBankMail;

            #[\Override]
            public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
            {
                return str_contains($content, 'FIRST');
            }

            #[\Override]
            public function parse(string $content): MailContent
            {
                throw new ParseException('stop');
            }
        });
        $parser->registerParser(new class implements ParserInterface {
            use ParsesBankMail;

            #[\Override]
            public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
            {
                return str_contains($content, 'SECOND-ONLY');
            }

            #[\Override]
            public function parse(string $content): MailContent
            {
                return new MailContent(vs: 'planted');
            }
        });

        try {
            $parser->parse('FIRST');
            self::fail('ParseException must leave the facade');
        } catch (ParseException $exception) {
            self::assertSame('stop', $exception->getMessage());
        }
        self::assertNull($parser->tryParse('FIRST'));
    }

    private function foreignSentences(): string
    {
        return '1.1.2026 bol zostatok Vasho uctu SK1375000000004019425381 zvyseny o 99999,00 CZK vs777 '
            . 'Dne 1.1.2026 byl na účtu 123 zaúčtována transakce Došlá platba Částka: +99999,00 CZK '
            . 'dňa 1.1.2026 bola na účte SK1375000000004019425381 zaúčtovaná suma SEPA platobného príkazu suma: +99999,00 EUR '
            . 'Dátum: 1.1.2026 Na účet: SK9 Z účtu: SK8 Suma: 99999,00 VS: 777 Stav: ok '
            . 'RES=OK VS=777 AMT=99999.00 CURR=203';
    }

    private function tatra(string $reference, string $message): string
    {
        return "Vazeny klient,\n\n16.1.2015 12:51 bol zostatok Vasho uctu SK9812353347235 zvyseny o 1,00 EUR.\n"
            . "Popis transakcie: CCINT 1100/000000-261426464\n"
            . "Referencia platitela: $reference\n"
            . "Informacia pre prijemcu: $message\n";
    }

    private function csobCz(string $message, string $vs = '23456789'): string
    {
        return "Vážený kliente,\n\nDne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +1,00 CZK\n"
            . "Účet protistrany: 1122334455/9999\n"
            . "Variabilní symbol: $vs\n"
            . "Zpráva příjemci: $message\n\n"
            . "Zůstatek na účtu po zaúčtování transakce: +1 234 567,89 CZK.\n";
    }

    private function csobSk(string $name, string $message): string
    {
        return "Vážená klientka, vážený klient,\n\n"
            . "dovoľujeme si Vám oznámiť, že dňa 6.9.2019 bola na účte SK13 7500 0000 0040 1942 5381 PETIT PRESS, A.S. zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma:                    +1,00 EUR\n"
            . "z účtu:                  SK91 7500 0000 0030 2072 9058\n"
            . "názov protiúčtu:         $name\n"
            . "referencia platiteľa:    /VS212049000/SS964/KS0308\n"
            . "informácia pre príjemcu: $message\n";
    }

    private function vub(string $extra): string
    {
        return "Dátum: 1.1.2020\nZ účtu: SK1\nNa účet: SK2\nSuma: 1,00\n$extra\nVS: 1234567890\nStav: ok\n";
    }
}
