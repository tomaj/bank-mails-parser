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
        $this->assertSame('2015-01-17 00:00:00', $midnight->transactionDate?->format('Y-m-d H:i:s'));
        $this->assertSame('Europe/Bratislava', $midnight->transactionDate?->getTimezone()->getName());

        $seconds = $parser->parse('16.1.2015 24:00:00 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');
        $this->assertSame('2015-01-17 00:00:00', $seconds->transactionDate?->format('Y-m-d H:i:s'));

        $invalid = $parser->parse('31.2.2015 24:00 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');
        $this->assertNull($invalid->transactionDate);
        $this->assertSame('1.00', $invalid->money?->toDecimalString());
        $this->assertNull(BankDate::parse('31.2.2015 24:00', '!j.n.Y G:i', BankDate::bratislava()));
    }

    #[Test]
    public function tatraAcceptsAnEnglishMonthTheWayStrtotimeDid(): void
    {
        $parser = new TatraBankaMailParser();
        $mail = $parser->parse('16 January 2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 12,31 EUR.');
        $this->assertSame('2015-01-16 12:51:00', $mail->transactionDate?->format('Y-m-d H:i:s'));
        $this->assertSame('12.31', $mail->money?->toDecimalString());

        $rolled = $parser->parse('31 February 2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');
        $this->assertNull($rolled->transactionDate);
        $this->assertSame('1.00', $rolled->money?->toDecimalString());
        $this->assertNull(BankDate::parseEnglish('not a date', BankDate::bratislava()));
        $this->assertNull(BankDate::parseEnglish('16 Notamonth 2015 12:51', BankDate::bratislava()));
    }

    #[Test]
    public function missingOrUnknownAmountDoesNotRejectTheMail(): void
    {
        $cz = new CsobMailParser()->parse(
            "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Variabilní symbol: 42\n",
        );
        $this->assertNull($cz->money);
        $this->assertSame('123456789', $cz->accountNumber);
        $this->assertSame('42', $cz->vs);

        $unknown = new CsobMailParser()->parse(
            "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +10,00 XYZ\n",
        );
        $this->assertNull($unknown->money);
        $this->assertSame('123456789', $unknown->accountNumber);

        $garbage = new CsobMailParser()->parse(
            "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +abc,00 CZK\n",
        );
        $this->assertNull($garbage->money);

        $sk = new SkCsobMailParser()->parse(
            "dňa 6.9.2019 bola na účte SK11 zaúčtovaná suma SEPA platobného príkazu:\n",
        );
        $this->assertNull($sk->money);
        $this->assertSame('SK11', $sk->ownAccountNumber);
    }

    #[Test]
    public function singleLineHtmlCsobBreaksBecomeLines(): void
    {
        $html = '<html><body><p>Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.</p>'
            . '<p>Částka: +1 234,56 CZK</p><div>Účet protistrany: 1122334455/9999<br>Název protistrany: Novak</div></body></html>';
        $parsed = new CsobMailParser()->parse($html);
        $this->assertSame('1234.56', $parsed->money?->toDecimalString());
        $this->assertSame('123456789', $parsed->accountNumber);
        $this->assertSame('1122334455/9999', $parsed->sourceAccountNumber);
        $this->assertTrue(new CsobMailParser()->supports($html));
    }

    #[Test]
    public function invalidUtf8TatraBodyStillParses(): void
    {
        $body = "16.1.2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 12,31 EUR.\n"
            . "Informacia pre prijemcu: \x8Aaloba\n";
        $parser = new TatraBankaMailParser();
        $this->assertTrue($parser->supports($body));
        $parsed = $parser->parse($body);
        $this->assertSame('12.31', $parsed->money?->toDecimalString());
        $this->assertNotNull($parsed->receiverMessage);
    }

    #[Test]
    public function cardPayTresWinsAndAMissingCurrencyDefaultsToEur(): void
    {
        $parser = new TatraBankaSimpleMailParser();
        $onlyTres = $parser->parse('VS=1 TRES=OK AMT=10.00');
        $this->assertSame('OK', $onlyTres->res);
        $this->assertSame('OK', $onlyTres->tres);
        $this->assertSame('10.00', $onlyTres->money?->toDecimalString());
        $this->assertSame('EUR', $onlyTres->money?->currency->value);
        $this->assertNull($onlyTres->transactionDate);

        $unknown = $parser->parse('VS=1 RES=OK AMT=10.00 CURR=XYZ');
        $this->assertNull($unknown->money);
        $this->assertSame('OK', $unknown->res);
        $this->assertSame('1', $unknown->vs);

        $bytes = "VS=1 RES=OK AMT=1.50 CURR=978\nInformacia: \x8A\n";
        $this->assertTrue($parser->supports($bytes));
        $this->assertSame('1.50', $parser->parse($bytes)->money?->toDecimalString());
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
        $this->assertSame('123456789012', $sk->vs);
        $this->assertSame('0308', $sk->ks);

        $fromMessage = new SkCsobMailParser()->parse(
            "dňa 6.9.2019 bola na účte SK11 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +1,00 EUR\n"
            . "referencia platiteľa: /VS111\n"
            . "informácia pre príjemcu: platba KS0558 a VS222\n",
        );
        $this->assertSame('111', $fromMessage->vs);
        $this->assertSame('0558', $fromMessage->ks);

        $cz = new CsobMailParser()->parse(
            "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +1,00 CZK\n"
            . "Konstantní symbol: 12345678901\n",
        );
        $this->assertNull($cz->ks);
    }
}
