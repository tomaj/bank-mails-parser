<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\BankDate;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;

final class Version41ClosenessTest extends TestCase
{
    #[Test]
    public function twentyFourHundredIsTheNextDayAndInvalidDatesStayNull(): void
    {
        $parser = new TatraBankaMailParser();
        $midnight = $parser->parse('16.1.2015 24:00 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');
        self::assertSame('2015-01-17 00:00:00', $midnight->transactionDate?->format('Y-m-d H:i:s'));
        self::assertSame('Europe/Bratislava', $midnight->transactionDate->getTimezone()->getName());

        $seconds = $parser->parse('16.1.2015 24:00:00 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');
        self::assertSame('2015-01-17 00:00:00', $seconds->transactionDate?->format('Y-m-d H:i:s'));

        $invalid = $parser->parse('31.2.2015 24:00 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');
        self::assertNull($invalid->transactionDate);
        self::assertSame('1.00', $invalid->money?->toDecimalString());
        self::assertNull(BankDate::parse('31.2.2015 24:00', '!j.n.Y G:i', BankDate::bratislava()));
    }

    #[Test]
    public function tatraAcceptsAnEnglishMonthTheWayStrtotimeDid(): void
    {
        $parser = new TatraBankaMailParser();
        $mail = $parser->parse('16 January 2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 12,31 EUR.');
        self::assertSame('2015-01-16 12:51:00', $mail->transactionDate?->format('Y-m-d H:i:s'));
        self::assertSame('12.31', $mail->money?->toDecimalString());

        $rolled = $parser->parse('31 February 2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');
        self::assertNull($rolled->transactionDate);
        self::assertSame('1.00', $rolled->money?->toDecimalString());
        self::assertNull(BankDate::parseEnglish('not a date', BankDate::bratislava()));
        self::assertNull(BankDate::parseEnglish('16 Notamonth 2015 12:51', BankDate::bratislava()));
    }

    #[Test]
    public function missingOrUnknownAmountDoesNotRejectTheMail(): void
    {
        $cz = new CsobMailParser()->parse(
            "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Variabilní symbol: 42\n",
        );
        self::assertNull($cz->money);
        self::assertSame('123456789', $cz->accountNumber);
        self::assertSame('42', $cz->vs);

        $unknown = new CsobMailParser()->parse(
            "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +10,00 XYZ\n",
        );
        self::assertNull($unknown->money);
        self::assertSame('123456789', $unknown->accountNumber);

        $garbage = new CsobMailParser()->parse(
            "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +abc,00 CZK\n",
        );
        self::assertNull($garbage->money);

        $sk = new SkCsobMailParser()->parse(
            "dňa 6.9.2019 bola na účte SK11 zaúčtovaná suma SEPA platobného príkazu:\n",
        );
        self::assertNull($sk->money);
        self::assertSame('SK11', $sk->ownAccountNumber);
    }

    #[Test]
    public function singleLineHtmlCsobBreaksBecomeLines(): void
    {
        $html = '<html><body><p>Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.</p>'
            . '<p>Částka: +1 234,56 CZK</p><div>Účet protistrany: 1122334455/9999<br>Název protistrany: Novak</div></body></html>';
        $parsed = new CsobMailParser()->parse($html);
        self::assertSame('1234.56', $parsed->money?->toDecimalString());
        self::assertSame('123456789', $parsed->accountNumber);
        self::assertSame('1122334455/9999', $parsed->sourceAccountNumber);
        self::assertTrue(new CsobMailParser()->supports($html));
    }

    #[Test]
    public function invalidUtf8TatraBodyStillParses(): void
    {
        $body = "16.1.2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 12,31 EUR.\n"
            . "Informacia pre prijemcu: \x8Aaloba\n";
        $parser = new TatraBankaMailParser();
        self::assertTrue($parser->supports($body));
        $parsed = $parser->parse($body);
        self::assertSame('12.31', $parsed->money?->toDecimalString());
        self::assertNotNull($parsed->receiverMessage);
    }

    #[Test]
    public function cardPayTresWinsAndAMissingCurrencyDefaultsToEur(): void
    {
        $parser = new TatraBankaSimpleMailParser();
        $onlyTres = $parser->parse('VS=1 TRES=OK AMT=10.00');
        self::assertSame('OK', $onlyTres->res);
        self::assertSame('OK', $onlyTres->tres);
        self::assertSame('10.00', $onlyTres->money?->toDecimalString());
        self::assertSame('EUR', $onlyTres->money->currency->value);
        self::assertNull($onlyTres->transactionDate);

        $unknown = $parser->parse('VS=1 RES=OK AMT=10.00 CURR=XYZ');
        self::assertNull($unknown->money);
        self::assertSame('OK', $unknown->res);
        self::assertSame('1', $unknown->vs);

        $bytes = "VS=1 RES=OK AMT=1.50 CURR=978\nInformacia: \x8A\n";
        self::assertTrue($parser->supports($bytes));
        self::assertSame('1.50', $parser->parse($bytes)->money?->toDecimalString());
    }

    #[Test]
    public function slovakAndTatraSymbolsKeepMoreThanTenDigits(): void
    {
        $sk = new SkCsobMailParser()->parse(
            "dňa 6.9.2019 bola na účte SK11 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +1,00 EUR\n"
            . "referencia platiteľa: /VS123456789012/KS0308\n"
            . "názov protiúčtu: BKS1 ADVS2\n",
        );
        self::assertSame('123456789012', $sk->vs);
        self::assertSame('0308', $sk->ks);

        $fromMessage = new SkCsobMailParser()->parse(
            "dňa 6.9.2019 bola na účte SK11 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +1,00 EUR\n"
            . "referencia platiteľa: /VS111\n"
            . "informácia pre príjemcu: platba KS0558 a VS222\n",
        );
        self::assertSame('111', $fromMessage->vs);
        self::assertSame('0558', $fromMessage->ks);

        $cz = new CsobMailParser()->parse(
            "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +1,00 CZK\n"
            . "Konstantní symbol: 12345678901\n",
        );
        self::assertNull($cz->ks);
    }
}
