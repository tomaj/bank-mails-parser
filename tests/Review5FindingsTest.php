<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
use Tomaj\BankMailsParser\ValueObject\Currency;
use Tomaj\BankMailsParser\ValueObject\Money;

final class Review5FindingsTest extends TestCase
{
    #[Test]
    #[DataProvider('payerPhantoms')]
    public function payerFieldThatLooksLikeABankLineIsNotATransaction(string $mail, string $sender): void
    {
        $parsed = new BankMailsParser()->parseAll($mail, $sender);
        $this->assertCount(1, $parsed);
        $this->assertNotNull($parsed[0]->money);
        $this->assertStringNotContainsString('99999', $parsed[0]->money->toDecimalString());
        $kept = new BankMailsParser()->tryParse($mail, $sender);
        $this->assertSame($parsed[0]->money->toDecimalString(), $kept?->money?->toDecimalString());
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function payerPhantoms(): array
    {
        $header = "Dne 18.8.2022 byla na účtu 123456789 zaúčtována transakce Došlá platba.\nČástka: +10,00 CZK\n";
        $phantom = "Dne 1.1.2026 byl na účtu 123456789 zaúčtována transakce Došlá platba\nČástka: +99999,00 CZK\n";
        $sk = "dňa 6.9.2019 bola na účte SK11 zaúčtovaná suma SEPA platobného príkazu:\nsuma: +10,00 EUR\n";
        $skPhantom = "dňa 1.1.2026 bola na účte SK1 zaúčtovaná suma SEPA platobného príkazu\nsuma: +99999,00 EUR\n";

        return [
            'address is a payer field' => [
                $header . "Adresa protistrany: Veterna 13\n" . $phantom,
                'info@csob.cz',
            ],
            'arbitrary label does not end the message' => [
                $header . "Zpráva příjemci: ahoj\nPoznamka: x\n" . $phantom,
                'info@csob.cz',
            ],
            'sk message' => [
                $sk . "informácia pre príjemcu: ahoj\nS:1\n" . $skPhantom,
                'notifikacia@csob.sk',
            ],
            'sk name' => [
                $sk . "názov protiúčtu: NOVAK\nsuma: x\n" . $skPhantom,
                'notifikacia@csob.sk',
            ],
            'tatra sentence inside the message' => [
                "16.1.2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.\n"
                . "Informacia pre prijemcu: ahoj\n"
                . "1.1.2026 00:00 bol zostatok Vasho uctu SK9 zvyseny o 99999,00 EUR.\n",
                'a@tatrabanka.sk',
            ],
        ];
    }

    #[Test]
    public function headerWithoutABlankLineStillParses(): void
    {
        $mail = "Vážený kliente,\n"
            . "toto je automaticky generovaný e-mail.\n"
            . "Dne 1.3.2024 byla na účtu 123456789 zaúčtována transakce typu: Došlá platba.\n"
            . "Částka: +1 440,00 CZK\n"
            . "Variabilní symbol: 0449274899\n";
        $parsed = new CsobMailParser()->parse($mail);
        $this->assertSame('1440.00', $parsed->money?->toDecimalString());
        $this->assertSame('0449274899', $parsed->vs);

        $collapsed = str_replace("\n\n", "\n", "Vážený kliente,\n\n" . substr($mail, strlen("Vážený kliente,\n")));
        $again = new CsobMailParser()->parseAll($collapsed);
        $this->assertCount(1, $again);
        $this->assertSame('1440.00', $again[0]->money?->toDecimalString());
    }

    #[Test]
    public function purposeSymbolUsesAWordBoundaryLike4x(): void
    {
        $parser = new CsobMailParser();
        $glued = $parser->parse($this->purpose('NOVAK PETER 6869282911 REF AB12'));
        $this->assertSame('6869282911', $glued->vs);

        $wrapped = $parser->parse($this->purpose("NOVAK PETER 6869282911\nINV2026"));
        $this->assertSame('6869282911', $wrapped->vs);

        $invoice = $parser->parse($this->purpose('PLATBA ZA FAKTURU FV2024001'));
        $this->assertNull($invoice->vs);

        $acrossBlank = $parser->parse($this->purpose("NOVAK PETER\n\n6869282913"));
        $this->assertSame('6869282913', $acrossBlank->vs);
    }

    #[Test]
    public function skMessageUsesTheFirstVariableSymbol(): void
    {
        $mail = "dňa 6.9.2019 bola na účte SK11 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +10,00 EUR\n"
            . "referencia platiteľa: NOTPROVIDED\n"
            . "informácia pre príjemcu: VS111 nahrada za VS222\n";
        $parsed = new SkCsobMailParser()->parse($mail);
        $this->assertSame('111', $parsed->vs);
        $this->assertSame('10.00', $parsed->money?->toDecimalString());
    }

    #[Test]
    public function constantSymbolAfterTheMessageIsRead(): void
    {
        $mail = "Dne 1.3.2024 byla na účtu 123456789 zaúčtována transakce typu: Došlá platba.\n"
            . "Částka: +500,00 CZK\n"
            . "Variabilní symbol: 1234\n"
            . "Zpráva příjemci: platba faktury\n"
            . "Konstantní symbol: 0308\n"
            . "Zůstatek na účtu po zaúčtování transakce: +1,00 CZK.\n";
        $parsed = new CsobMailParser()->parse($mail);
        $this->assertSame('0308', $parsed->ks);
        $this->assertNull($parsed->sourceAccountNumber);
        $this->assertSame('1234', $parsed->vs);
        $this->assertSame('500.00', $parsed->money?->toDecimalString());
    }

    #[Test]
    public function blankLineEndsTheMessageSoTheNextHeaderIsAnotherTransaction(): void
    {
        $header = "Dne 18.8.2022 byla na účtu 123456789 zaúčtována transakce Došlá platba.\nČástka: +10,00 CZK\n";
        $second = "Dne 1.1.2026 byl na účtu 123456789 zaúčtována transakce Došlá platba\nČástka: +2,00 CZK\n";
        $rows = new CsobMailParser()->parseAll($header . "Zpráva příjemci: ahoj\n\n" . $second);
        $this->assertCount(2, $rows);
        $this->assertSame('10.00', $rows[0]->money?->toDecimalString());
        $this->assertSame('2.00', $rows[1]->money?->toDecimalString());

        $spaces = new CsobMailParser()->parseAll($header . "Zpráva příjemci: ahoj\n   \n" . $second);
        $this->assertCount(2, $spaces);
        $nbsp = new CsobMailParser()->parseAll($header . "Zpráva příjemci: ahoj\n\u{00A0}\n" . $second);
        $this->assertCount(2, $nbsp);
    }

    #[Test]
    public function accountLineInsideTheMessageIsTheCounterparty(): void
    {
        $mail = "Dne 18.8.2022 byla na účtu 123456789 zaúčtována transakce Došlá platba.\n"
            . "Částka: +10,00 CZK\n"
            . "Zpráva příjemci: platba\n"
            . "Účet protistrany: 1111111111/0800\n";
        $parsed = new CsobMailParser()->parse($mail);
        $this->assertSame('10.00', $parsed->money?->toDecimalString());
        $this->assertSame('1111111111/0800', $parsed->sourceAccountNumber);
    }

    #[Test]
    public function lineSeparatorsInsideARealMailStillParse(): void
    {
        $mail = "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\u{2028}"
            . "Částka: +10,00 CZK\u{2028}"
            . 'Variabilní symbol: 1111';
        $parsed = new CsobMailParser()->parse($mail);
        $this->assertSame('10.00', $parsed->money?->toDecimalString());
        $this->assertSame('1111', $parsed->vs);
        $vertical = new CsobMailParser()->parse(str_replace("\u{2028}", "\u{000B}", $mail));
        $this->assertSame('10.00', $vertical->money?->toDecimalString());

        $cr = str_replace("\n", "\r", "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\nČástka: +8,00 CZK\n");
        $this->assertSame('8.00', new CsobMailParser()->parse($cr)->money?->toDecimalString());
    }

    #[Test]
    public function twoRealTransactionsAfterTheBalanceStillBothParse(): void
    {
        $mail = "Dne 25.9.2018 byla na účtu 111 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +1,00 CZK\n"
            . "Zpráva příjemci: prva\n"
            . "Zůstatek na účtu po zaúčtování transakce: +1,00 CZK.\n"
            . "\n"
            . "Dne 26.9.2018 byla na účtu 222 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +2,00 CZK\n"
            . "Zůstatek na účtu po zaúčtování transakce: +3,00 CZK.\n";
        $rows = new CsobMailParser()->parseAll($mail);
        $this->assertCount(2, $rows);
        $this->assertSame('1.00', $rows[0]->money?->toDecimalString());
        $this->assertSame('2.00', $rows[1]->money?->toDecimalString());
    }

    #[Test]
    public function moneyAndCurrencyEqualsCheckTheCurrency(): void
    {
        $eur = Currency::fromCode('EUR');
        $this->assertTrue($eur->equals(Currency::fromCode('978')));
        $this->assertFalse($eur->equals(Currency::fromCode('CZK')));

        $oneEur = Money::fromDecimalString('1.00', 'EUR');
        $this->assertTrue($oneEur->equals(Money::fromDecimalString('1.00', 'EUR')));
        $this->assertFalse($oneEur->equals(Money::fromDecimalString('1.00', 'CZK')));
        $this->assertFalse($oneEur->equals(Money::fromDecimalString('2.00', 'EUR')));
    }

    #[Test]
    public function freeTextLabelMatchesPayerFieldsOnly(): void
    {
        $probe = new PayerLabelProbe();
        $this->assertTrue($probe->label('Adresa protistrany: Veterna'));
        $this->assertTrue($probe->label('informácia pre príjemcu: ahoj'));
        $this->assertTrue($probe->label('Reference plátce: CT1'));
        $this->assertTrue($probe->label('referencia platiteľa: /VS1'));
        $this->assertTrue($probe->label('Referencia platitela: /VS1/SS/KS'));
        $this->assertFalse($probe->label('Poznamka: x'));
        $this->assertFalse($probe->label('Částka: +1,00 CZK'));
    }

    #[Test]
    public function tatraPaymentWithoutAPlantedSentenceStillParses(): void
    {
        $mail = "16.1.2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 12,31 EUR.\n"
            . "Popis transakcie: CCINT 1100/1\n"
            . "Referencia platitela: /VS10/SS/KS\n"
            . "Informacia pre prijemcu: test\n";
        $parsed = new TatraBankaMailParser()->parse($mail);
        $this->assertSame('12.31', $parsed->money?->toDecimalString());
        $this->assertSame('10', $parsed->vs);
        $this->assertSame('test', $parsed->receiverMessage);
    }

    private function purpose(string $text): string
    {
        return "Dne 5.12.2023 byla na účtu 123456789 zaúčtována zahraniční transakce.\n"
            . "Částka: +3 300,00 CZK\n"
            . "Účel platby: $text\n"
            . "Zůstatek na účtu po zaúčtování transakce: +1,00 CZK.\n";
    }
}

final class PayerLabelProbe implements ParserInterface
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

    public function label(string $line): bool
    {
        return $this->isFreeTextLabel($line);
    }
}
