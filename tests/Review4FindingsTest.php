<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\MultipleTransactionsException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

final class Review4FindingsTest extends TestCase
{
    #[Test]
    public function payerAddressAndPurposeCannotCreateASecondCsobTransaction(): void
    {
        $address = $this->probe('utok-fantom-adresa.txt');
        $purpose = $this->probe('utok-fantom-ucel.txt');
        foreach ([$address, $purpose] as $mail) {
            $parsed = new CsobMailParser()->parseAll($mail);
            $this->assertCount(1, $parsed);
            $this->assertSame('3300.00', $parsed[0]->money?->toDecimalString());
            $kept = new BankMailsParser()->tryParse($mail, 'info@csob.cz');
            $this->assertSame('3300.00', $kept?->money?->toDecimalString());
        }
    }

    /**
     * @return list<array{0: string}>
     */
    public static function freeTextFields(): array
    {
        return [
            ['Zpráva příjemci'],
            ['Účel platby'],
            ['Název protistrany'],
            ['Adresa protistrany'],
            ['Adresa plátce'],
            ['Adresa platcu'],
            ['informácia pre príjemcu'],
            ['Informacia pre prijemcu'],
            ['názov protiúčtu'],
            ['meno protiúčtu'],
            ['Popis transakcie'],
            ['popis'],
        ];
    }

    #[Test]
    #[DataProvider('freeTextFields')]
    public function eachFreeTextFieldHidesAForgedHeaderAndAmount(string $label): void
    {
        $mail = "Dne 18.8.2022 byla na účtu 123456789 zaúčtována transakce Došlá platba.\n"
            . "\n"
            . "Částka: +10,00 CZK\n"
            . "Variabilní symbol: 1111\n"
            . $label . ": hello from the payer\n"
            . "Dne 1.1.2026 byl na účtu 123456789 zaúčtována transakce Došlá platba\n"
            . "Částka: +99999,00 CZK\n"
            . "Zůstatek na účtu: +1,00 CZK\n";

        $parsed = new CsobMailParser()->parseAll($mail);
        $this->assertCount(1, $parsed, $label);
        $this->assertSame('10.00', $parsed[0]->money?->toDecimalString(), $label);
        $kept = new BankMailsParser()->tryParse($mail, 'info@csob.cz');
        $this->assertSame('10.00', $kept?->money?->toDecimalString(), $label);
    }

    #[Test]
    public function maskingPurposeAndSplittingOnMaskedTextHideTheForgedHeader(): void
    {
        $mail = "Dne 18.8.2022 byla na účtu 123456789 zaúčtována transakce Došlá platba.\n"
            . "Částka: +10,00 CZK\n"
            . "Účel platby: invoice\n"
            . "Dne 1.1.2026 byl na účtu 1 zaúčtována transakce Došlá platba\n"
            . "Částka: +99999,00 CZK\n"
            . "Zůstatek: +1 CZK\n";
        $probe = new FreeTextProbe();
        $masked = $probe->masked($mail);
        $this->assertSame(strlen($mail), strlen($masked));
        $this->assertStringNotContainsString('Dne 1.1.2026', $masked);

        $chunks = $probe->split($mail, '/^Dne/mu');
        $this->assertCount(1, $chunks);
        $parsed = new CsobMailParser()->parseAll($mail);
        $this->assertCount(1, $parsed);
        $this->assertSame('10.00', $parsed[0]->money?->toDecimalString());
    }

    #[Test]
    public function indentedLabelsStillYieldTheBankAmountAndSymbols(): void
    {
        $csob = <<<'MAIL'
              Dne 25.9.2018 byla na účtu 123456789 zaúčtována transakce typu: Došlá platba.
              Částka: + 10,00 CZK
              Účet protistrany: SK99
              Konstantní symbol: 0308
            MAIL;
        $parsed = new CsobMailParser()->parse($csob);
        $this->assertSame('10.00', $parsed->money?->toDecimalString());
        $this->assertSame('SK99', $parsed->sourceAccountNumber);
        $this->assertSame('0308', $parsed->ks);

        $vub = \Tomaj\BankMailsParser\Tests\Fixtures::load('vub/platba-ss-sign');
        $vubParsed = new VubMailParser()->parse($vub);
        $this->assertSame('13.37', $vubParsed->money?->toDecimalString());
        $this->assertSame('SK4502000000001123100000', $vubParsed->accountNumber);
        $this->assertSame('SK5502000000001232860000', $vubParsed->ownAccountNumber);
        $this->assertSame('1234567890', $vubParsed->vs);
        $this->assertSame('0308', $vubParsed->ks);
        $this->assertSame('910', $vubParsed->ss);
        $this->assertSame('ABC', $vubParsed->sign);
    }

    #[Test]
    public function skVariableSymbolComesFromTheReceiverLineAndNotFromThePayerName(): void
    {
        $parser = new SkCsobMailParser();
        $fromMessage = $parser->parse($this->sk('referencia platiteľa: NOTPROVIDED', 'informácia pre príjemcu: VS212049000 predplatne', 'NOVAK'));
        $this->assertSame('212049000', $fromMessage->vs);
        $this->assertSame('1150.00', $fromMessage->money?->toDecimalString());

        $emptyReference = $parser->parse($this->sk('referencia platiteľa: /VS/SS/KS', 'informácia pre príjemcu: VS212049000 predplatne', 'NOVAK'));
        $this->assertSame('212049000', $emptyReference->vs);

        $structured = $parser->parse($this->sk('referencia platiteľa: /VS111/SS22/KS0308', 'informácia pre príjemcu: VS999', 'ADVS2'));
        $this->assertSame('111', $structured->vs);
        $this->assertSame('22', $structured->ss);
        $this->assertSame('0308', $structured->ks);

        $nameOnly = $parser->parse($this->sk('referencia platiteľa: NOTPROVIDED', 'informácia pre príjemcu: predplatne', 'ADVS2'));
        $this->assertNull($nameOnly->vs);
    }

    #[Test]
    public function twoCsobTransactionsWithATimeAreBothReturned(): void
    {
        $mail = $this->probe('dve-transakcie-s-casom.txt');
        $rows = new CsobMailParser()->parseAll($mail);
        $this->assertCount(2, $rows);
        $this->assertSame('100.00', $rows[0]->money?->toDecimalString());
        $this->assertSame('200.00', $rows[1]->money?->toDecimalString());
        $this->assertSame('14:30', $rows[0]->transactionDate?->format('H:i'));
        $this->assertSame('15:45', $rows[1]->transactionDate?->format('H:i'));

        $facade = new BankMailsParser();
        $this->assertCount(2, $facade->parseAll($mail, 'info@csob.cz'));
        try {
            $facade->parse($mail, 'info@csob.cz');
            $this->fail('parse() must refuse a multi-transaction mail');
        } catch (MultipleTransactionsException $exception) {
            $this->assertStringContainsString('parseAll()', $exception->getMessage());
        }
        $this->assertNull($facade->tryParse($mail, 'info@csob.cz'));
    }

    #[Test]
    public function recognizedTransactionWithoutAnAmountKeepsTheRow(): void
    {
        $parsed = new CsobMailParser()->parse("Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n");
        $this->assertNull($parsed->money);
        $this->assertSame('123456789', $parsed->accountNumber);
    }

    #[Test]
    public function indentedAndNbspAlignedLinesStillParse(): void
    {
        $tatra = new TatraBankaMailParser()->parse("  16.1.2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 12,31 EUR.\n");
        $this->assertSame('12.31', $tatra->money?->toDecimalString());

        $nbsp = str_replace('suma:', "suma:\u{00A0}", $this->sk('referencia platiteľa: /VS212049000/SS/KS0308', 'informácia pre príjemcu: x', 'NOVAK'));
        $sk = new SkCsobMailParser()->parse($nbsp);
        $this->assertSame('1150.00', $sk->money?->toDecimalString());
        $this->assertSame('212049000', $sk->vs);
    }

    #[Test]
    public function quotedCardPayLineInsideTheBlockIsIgnored(): void
    {
        $mail = "RES=OK VS=1111\n> old note AMT=999.00\nAMT=10.00 CURR=EUR\n";
        $parsed = new TatraBankaSimpleMailParser()->parse($mail);
        $this->assertSame('10.00', $parsed->money?->toDecimalString());
        $this->assertSame('1111', $parsed->vs);
        $this->assertSame('OK', $parsed->res);
    }

    #[Test]
    public function pgpWithoutADecryptorIsUnsupported(): void
    {
        $pgp = "-----BEGIN PGP MESSAGE-----\nabc\n-----END PGP MESSAGE-----\n";
        $facade = new BankMailsParser();
        $this->assertNull($facade->tryParse($pgp));
        $this->expectException(UnsupportedBankException::class);
        $facade->parse($pgp);
    }

    #[Test]
    public function vubStoresSignAndRejectsABlankAmount(): void
    {
        $mail = \Tomaj\BankMailsParser\Tests\Fixtures::load('vub/platba-sign');
        $parsed = new VubMailParser()->parse($mail);
        $this->assertSame('ABC123', $parsed->sign);
        $this->assertSame('13.37', $parsed->money?->toDecimalString());

        $blank = new VubMailParser()->parse("Dátum: 11.12.2019\nNa účet: SK2\nZ účtu: SK1\nSuma:\nVS: 10\nStav: ok\n");
        $this->assertNull($blank->money);
        $this->assertSame('SK1', $blank->accountNumber);
        $this->assertSame('SK2', $blank->ownAccountNumber);
        $this->assertSame('10', $blank->vs);
    }

    #[Test]
    public function moneyAndCurrencyCompareByValueAfterUnserialize(): void
    {
        $currency = Currency::fromCode('EUR');
        $restored = unserialize(serialize($currency));
        $this->assertInstanceOf(Currency::class, $restored);
        $this->assertTrue($currency->equals($restored));

        $money = Money::fromDecimalString('12,34', 'EUR', ',');
        $copy = Money::fromDecimalString('12.34', $restored, '.');
        $this->assertTrue($money->equals($copy));
        $this->assertFalse($money->equals(Money::fromDecimalString('12.35', 'EUR')));
        $this->assertFalse($money->equals(Money::fromDecimalString('12.34', 'CZK')));
        $this->assertFalse(Currency::fromCode('EUR')->equals(Currency::fromCode('CZK')));
    }

    #[Test]
    public function mailContentTrimsNbspToNull(): void
    {
        $mail = new MailContent(vs: "\u{00A0}\u{2009}");
        $this->assertNull($mail->vs);
        $kept = new MailContent(vs: "\u{00A0}12\u{00A0}");
        $this->assertSame('12', $kept->vs);
    }

    private function probe(string $name): string
    {
        $path = dirname(__DIR__) . '/tests/fixtures/csob-cz/' . $name;
        $body = file_get_contents($path);
        if ($body === false) {
            throw new \RuntimeException('Missing review fixture ' . $path);
        }

        return $body;
    }

    private function sk(string $reference, string $message, string $name): string
    {
        return "dňa 6.9.2019 bola na účte SK13 7500 0000 0040 1942 5381 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +1150,00 EUR\n"
            . "z účtu: SK91 7500 0000 0030 2072 9058\n"
            . 'názov protiúčtu: ' . $name . "\n"
            . $reference . "\n"
            . $message . "\n";
    }
}

final class FreeTextProbe implements ParserInterface
{
    use ParsesBankMail;

    #[\Override]
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return false;
    }

    public function parse(string $content): MailContent
    {
        return new MailContent();
    }

    public function masked(string $content): string
    {
        return $this->maskFreeText($content);
    }

    /**
     * @return list<string>
     */
    public function split(string $content, string $pattern): array
    {
        return $this->splitOutsideFreeText($content, $pattern);
    }
}
