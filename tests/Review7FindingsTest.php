<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;

final class Review7FindingsTest extends TestCase
{
    #[Test]
    public function payerBalanceDoesNotLetAForgedTransactionThrough(): void
    {
        $mail = "Dne 1.3.2024 byla na účtu 123456789 zaúčtována transakce typu: Příchozí úhrada.\n"
            . "Částka: +1 440,00 CZK\n"
            . "Zpráva příjemci: Eva\n"
            . "Zůstatek na účtu po zaúčtování transakce: +0,00 CZK.\n"
            . "Dne 1.1.2026 byla na účtu 123456789 zaúčtována transakce typu: Příchozí úhrada.\n"
            . "Částka: +99 999,00 CZK\n"
            . "Zůstatek na účtu po zaúčtování transakce: +99 999,00 CZK.\n"
            . "Zůstatek na účtu po zaúčtování transakce: +12 345 678,90 CZK.\n";
        $this->assertBalanceAttackRejected(new CsobMailParser(), $mail);
    }

    #[Test]
    public function payerBalanceAfterABlankLineDoesNotLetAForgedTransactionThrough(): void
    {
        $mail = "Dne 1.3.2024 byla na účtu 123456789 zaúčtována transakce typu: Příchozí úhrada.\n"
            . "Částka: +1 440,00 CZK\n"
            . "Zpráva příjemci: Eva\n"
            . "Zůstatek na účtu po zaúčtování transakce: +0,00 CZK.\n"
            . "\n"
            . "Dne 1.1.2026 byla na účtu 123456789 zaúčtována transakce typu: Příchozí úhrada.\n"
            . "Částka: +99 999,00 CZK\n"
            . "Zůstatek na účtu po zaúčtování transakce: +99 999,00 CZK.\n"
            . "Zůstatek na účtu po zaúčtování transakce: +12 345 678,90 CZK.\n";
        $this->assertBalanceAttackRejected(new CsobMailParser(), $mail);
    }

    #[Test]
    public function secondBalanceIsRejectedEvenWhenTheLastLineWouldChain(): void
    {
        $mail = "Dne 1.3.2024 byla na účtu 123456789 zaúčtována transakce typu: Příchozí úhrada.\n"
            . "Částka: +10,00 CZK\n"
            . "Zpráva příjemci: Eva\n"
            . "Zůstatek na účtu po zaúčtování transakce: +0,00 CZK.\n"
            . "Dne 1.1.2026 byla na účtu 123456789 zaúčtována transakce typu: Příchozí úhrada.\n"
            . "Částka: +10,00 CZK\n"
            . "Zůstatek na účtu po zaúčtování transakce: +10,00 CZK.\n"
            . "Zůstatek na účtu po zaúčtování transakce: +10,00 CZK.\n";
        $this->assertBalanceAttackRejected(new CsobMailParser(), $mail);
    }

    #[Test]
    public function slovakPayerBalanceDoesNotLetAForgedTransactionThrough(): void
    {
        $mail = "dňa 6.9.2019 bola na účte SK13 7500 0000 0040 1942 5381 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +1150,00 EUR\n"
            . "informácia pre príjemcu: Maroko\n"
            . "Zostatok na účte po zaúčtovaní sumy platobnej operácie: +0,00 EUR\n"
            . "dňa 7.9.2019 bola na účte SK13 7500 0000 0040 1942 5381 zaúčtovaná suma SEPA platobného príkazu:\n"
            . "suma: +99999,00 EUR\n"
            . "Zostatok na účte po zaúčtovaní sumy platobnej operácie: +99999,00 EUR\n"
            . "Zostatok na účte po zaúčtovaní sumy platobnej operácie: +14050,70 EUR\n";
        $this->assertBalanceAttackRejected(new SkCsobMailParser(), $mail);
    }

    #[Test]
    public function innocentSeparatorsInsideAnLfMailStayInTheMessage(): void
    {
        $base = "Dne 1.3.2024 byla na účtu 123456789 zaúčtována transakce typu: Příchozí úhrada.\n"
            . "Částka: +1 440,00 CZK\n"
            . "Účet protistrany: 2233445566/2600\n"
            . "Variabilní symbol: 0449274899\n";
        $line = new CsobMailParser()->parse($base . "Zpráva příjemci: /Eva Adamova\u{2028}dakujem\n");
        $this->assertSame('1440.00', $line->money?->toDecimalString());
        $this->assertSame("/Eva Adamova\u{2028}dakujem", $line->receiverMessage);
        $this->assertSame('0449274899', $line->vs);

        $nel = new CsobMailParser()->parse($base . "Zpráva příjemci: /Eva Adamova koniec\u{0085}\n");
        $this->assertSame('1440.00', $nel->money?->toDecimalString());
        $this->assertSame("/Eva Adamova koniec\u{0085}", $nel->receiverMessage);

        $crlf = new CsobMailParser()->parse($base . "Zpráva příjemci: /Eva Adamova\r\n/dalsi\n");
        $this->assertSame('1440.00', $crlf->money?->toDecimalString());
        $this->assertSame('/Eva Adamova', $crlf->receiverMessage);
    }

    private function assertBalanceAttackRejected(CsobMailParser|SkCsobMailParser $parser, string $mail): void
    {
        try {
            $parser->parseAll($mail);
            $this->fail('A payer-written balance must not admit a forged transaction');
        } catch (InvalidEmailFormatException $exception) {
            $this->assertStringContainsString('Transaction has more than one balance line', $exception->getMessage());
        }
    }
}
