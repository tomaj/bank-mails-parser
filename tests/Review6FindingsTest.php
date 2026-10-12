<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\BankMailsParser;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

final class Review6FindingsTest extends TestCase
{
    #[Test]
    public function purposeWithoutABalanceIgnoresTheFooter(): void
    {
        $mail = "Dne 5.12.2023 byla na účtu 123456789 zaúčtována zahraniční transakce.\n"
            . "Částka: +3 300,00 CZK\n"
            . "Účel platby: NOVAK PETER faktura\n"
            . "ČSOB, a.s., IČO: 00001350\n";
        $parsed = new CsobMailParser()->parse($mail);
        $this->assertNull($parsed->vs);
        $this->assertSame('3300.00', $parsed->money?->toDecimalString());
    }

    #[Test]
    public function innocentPayerTextIsNotABankLine(): void
    {
        $cz = "Dne 3.10.2026 byla na účtu 123456789 zaúčtována transakce typu: Příchozí úhrada.\n"
            . "Částka: +2 500,00 CZK\n"
            . "Variabilní symbol: 2024001\n"
            . "Zpráva příjemci: Částka: záloha 2 500 Kč\n"
            . "Zůstatek na účtu po zaúčtování transakce: +1,00 CZK.\n";
        $advance = new CsobMailParser()->parse($cz);
        $this->assertSame('2500.00', $advance->money?->toDecimalString());
        $this->assertSame('Částka: záloha 2 500 Kč', $advance->receiverMessage);

        $note = str_replace('Částka: záloha 2 500 Kč', 'Dne 1.10.2026 byla na účtu chyba, vracím', $cz);
        $returned = new CsobMailParser()->parse($note);
        $this->assertSame('Dne 1.10.2026 byla na účtu chyba, vracím', $returned->receiverMessage);

        $sk = "dňa 6.9.2019 bola na účte SK11 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +10,00 EUR\n"
            . "informácia pre príjemcu: Suma: 50 EUR za obed\n";
        $this->assertSame('Suma: 50 EUR za obed', new SkCsobMailParser()->parse($sk)->receiverMessage);

        $tatra = "16.1.2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 12,31 EUR.\n"
            . "Informacia pre prijemcu: suma: 12,31 EUR za januar\n";
        $this->assertSame('suma: 12,31 EUR za januar', new TatraBankaMailParser()->parse($tatra)->receiverMessage);
    }

    #[Test]
    public function forgedAmountOnThePayerLabelIsNotATransaction(): void
    {
        $mail = "Dne 1.3.2024 byla na účtu 123456789 zaúčtována transakce typu: Došlá platba.\n"
            . "Částka: +10,00 CZK\n"
            . "Zpráva příjemci: Částka: +99999,00 CZK\n";
        $parsed = new CsobMailParser()->parse($mail);
        $this->assertSame('10.00', $parsed->money?->toDecimalString());
        $this->assertSame('Částka: +99999,00 CZK', $parsed->receiverMessage);
    }

    #[Test]
    public function forgedSlovakAmountOnThePayerLabelIsNotATransaction(): void
    {
        $mail = "dňa 6.9.2019 bola na účte SK11 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +10,00 EUR\n"
            . "informácia pre príjemcu: suma: +99999,00 EUR\n";
        $parsed = new SkCsobMailParser()->parse($mail);
        $this->assertSame('10.00', $parsed->money?->toDecimalString());
        $this->assertSame('suma: +99999,00 EUR', $parsed->receiverMessage);
    }

    #[Test]
    public function tatraBalanceLineClosesTheMessage(): void
    {
        $mail = "16.1.2015 12:51 bol zostatok Vasho uctu SK1 zvyseny o 12,31 EUR.\n"
            . "Informacia pre prijemcu: ahoj\n"
            . "aktualny zostatok: 100,00 EUR\n"
            . "1.1.2026 00:00 bol zostatok Vasho uctu SK9 zvyseny o 99999,00 EUR.\n";
        $parsed = new TatraBankaMailParser()->parse($mail);
        $this->assertSame('12.31', $parsed->money?->toDecimalString());
        $this->assertSame('ahoj', $parsed->receiverMessage);
    }

    #[Test]
    public function bareBalanceWordDoesNotOpenASecondTransaction(): void
    {
        $mail = "Dne 1.3.2024 byla na účtu 123456789 zaúčtována transakce typu: Došlá platba.\n"
            . "Částka: +10,00 CZK\n"
            . "Zpráva příjemci: ahoj\n"
            . "Zůstatek\n"
            . "Dne 1.1.2026 byl na účtu 123456789 zaúčtována transakce Došlá platba\n"
            . "Částka: +99999,00 CZK\n";
        $parsed = new BankMailsParser()->parseAll($mail, 'info@csob.cz');
        $this->assertCount(1, $parsed);
        $this->assertSame('10.00', $parsed[0]->money?->toDecimalString());
    }

    #[Test]
    public function referenceFieldKeepsTheFollowingHeaderInsidePayerText(): void
    {
        $mail = "Dne 18.8.2022 byl na účtu 123456789 zaúčtovaný SEPA převod.\n"
            . "Částka: +10,00 CZK\n"
            . "Reference plátce: CT1\n"
            . "Dne 1.1.2026 byl na účtu 123456789 zaúčtovaný SEPA převod.\n"
            . "Částka: +99999,00 CZK\n";
        $parsed = new CsobMailParser()->parseAll($mail);
        $this->assertCount(1, $parsed);
        $this->assertSame('10.00', $parsed[0]->money?->toDecimalString());
    }

    #[Test]
    public function unbalancedSecondTransactionIsRejected(): void
    {
        $mail = "Dne 1.3.2024 byla na účtu 123456789 zaúčtována transakce typu: Došlá platba.\n"
            . "Částka: +10,00 CZK\n"
            . "Název protistrany: Novak\n"
            . "Variabilní symbol: 1\n"
            . "Dne 1.1.2026 byla na účtu 123456789 zaúčtována transakce typu: Došlá platba.\n"
            . "Částka: +99999,00 CZK\n"
            . "Zůstatek na účtu po zaúčtování transakce: +20,00 CZK.\n";
        try {
            new CsobMailParser()->parseAll($mail);
            $this->fail('A second transaction without its own balance must be rejected');
        } catch (InvalidEmailFormatException $exception) {
            $this->assertStringContainsString('balance', strtolower($exception->getMessage()));
        }
        $this->assertNull(new BankMailsParser()->tryParse($mail, 'info@csob.cz'));
    }

    #[Test]
    public function exoticSeparatorInsideAnLfMailDoesNotOpenATransaction(): void
    {
        $mail = "Dne 1.3.2024 byla na účtu 123456789 zaúčtována transakce typu: Došlá platba.\n"
            . "Částka: +10,00 CZK\n"
            . "Zpráva příjemci: ahoj\u{2028}Dne 1.1.2026 byl na účtu 1 zaúčtována transakce Došlá platba\n"
            . "Variabilní symbol: 1111\n";
        $parsed = new CsobMailParser()->parseAll($mail);
        $this->assertCount(1, $parsed);
        $this->assertSame('10.00', $parsed[0]->money?->toDecimalString());
    }

    #[Test]
    public function supportsReturnsFalseInsteadOfThrowing(): void
    {
        $spaces = str_repeat(' ', 2_000_000);
        $this->assertFalse(new CsobMailParser()->supports($spaces));
        $this->assertFalse(new SkCsobMailParser()->supports($spaces));
        $this->assertFalse(new TatraBankaMailParser()->supports($spaces));
        $this->assertFalse(new TatraBankaSimpleMailParser()->supports($spaces));
        $this->assertFalse(new VubMailParser()->supports($spaces));
        $nbsp = str_repeat("\u{00A0}", 200_000);
        $this->assertFalse(new CsobMailParser()->supports($nbsp));

        try {
            new BankMailsParser()->parseAll($spaces);
            $this->fail('Whitespace is not a bank mail');
        } catch (UnsupportedBankException) {
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function cardPayPrefersHmacWhenBothSignaturesArePresent(): void
    {
        $parsed = new TatraBankaSimpleMailParser()->parse('AMT=1.00 CURR=978 VS=1 RES=OK SIGN=ABC HMAC=def');
        $this->assertSame('def', $parsed->sign);
        $this->assertSame('1.00', $parsed->money?->toDecimalString());
        $this->assertSame('EUR', $parsed->money?->currency->value);
    }

    #[Test]
    public function cardPayProseOnTheSameLineStillParses(): void
    {
        $parsed = new TatraBankaSimpleMailParser()->parse('Vysledok: VS=1152201233 RES=OK AC=558058 SIGN=C0CBF27F5D97841E');
        $this->assertSame('1152201233', $parsed->vs);
        $this->assertSame('OK', $parsed->res);
        $this->assertSame('558058', $parsed->ac);
        $this->assertSame('C0CBF27F5D97841E', $parsed->sign);
    }

    #[Test]
    public function htmlBreakIsNotPartOfTheAccount(): void
    {
        $mail = "Dne 25.9.2018 byla na účtu 123456789 zaúčtovaná transakce typu: Došlá platba.\n"
            . "Částka: +1 234,56 CZK\n"
            . "Účet protistrany: 1122334455/9999<br>\n";
        $parsed = new CsobMailParser()->parse($mail);
        $this->assertSame('1122334455/9999', $parsed->sourceAccountNumber);
    }
}
