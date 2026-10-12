<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\DecryptionException;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

#[CoversClass(CsobMailParser::class)]
#[CoversClass(SkCsobMailParser::class)]
#[CoversClass(TatraBankaMailParser::class)]
#[CoversClass(TatraBankaSimpleMailParser::class)]
#[CoversClass(TatraBankaStatementMailParser::class)]
#[CoversClass(VubMailParser::class)]
#[CoversClass(TatraBankaMailDecryptor::class)]
final class EdgeCasesTest extends TestCase
{
    #[Test]
    public function supportsMatchesContentSenderAndSubject(): void
    {
        $tatra = new TatraBankaMailParser();
        $this->assertTrue($tatra->supports('1.1.2020 1:00 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR'));
        $this->assertTrue($tatra->supports('zostatok Vasho uctu bez sumy', 'a@tatrabanka.sk'));
        $this->assertTrue($tatra->supports('zostatok Vasho uctu bez sumy', null, 'Tatra notice'));
        $this->assertFalse($tatra->supports('zostatok Vasho uctu bez sumy'));
        $this->assertFalse($tatra->supports('nothing'));

        $simple = new TatraBankaSimpleMailParser();
        $this->assertFalse($simple->supports('RES=OK'));
        $this->assertFalse($simple->supports('TRES=FAIL'));
        $this->assertFalse($simple->supports('Ahoj, RES=nieco v texte'));
        $this->assertTrue($simple->supports('AMT=10.00 CURR=EUR RES=OK'));
        $this->assertTrue($simple->supports("VS=1\nTRES=OK\nSIGN=abc"));
        $this->assertFalse($simple->supports('VS=1'));

        $statement = new TatraBankaStatementMailParser();
        $this->assertTrue($statement->supports("-----BEGIN PGP MESSAGE-----\n-----END PGP MESSAGE-----"));
        $this->assertFalse($statement->supports('plain'));

        $csob = new CsobMailParser();
        $this->assertTrue($csob->supports('Dne 1.1.2020 byla na účtu 1 zaúčtována transakce'));
        $this->assertFalse($csob->supports('nope', 'info@csob.cz'));
        $this->assertFalse($csob->supports('nope', 'info@csob.cz.evil.example'));
        $this->assertFalse($csob->supports('nope'));

        $sk = new SkCsobMailParser();
        $this->assertTrue($sk->supports('dňa 1.1.2020 bola na účte SK1 zaúčtovaná suma'));
        $this->assertFalse($sk->supports('nope', 'info@csob.sk'));
        $this->assertFalse($sk->supports('nope', 'news@csob.sk.evil.example'));
        $this->assertFalse($sk->supports('nope'));

        $vub = new VubMailParser();
        $this->assertTrue($vub->supports("Dátum: 11.12.2019\nNa účet: SK1\nZ účtu: SK2\nSuma: 1,00\nVS: 10\nStav: ok"));
        $this->assertFalse($vub->supports("Suma: 1,00\nNa účet: SK1\nVS: 10"));
        $this->assertFalse($vub->supports('Suma: 1,00'));
        $this->assertFalse($vub->supports('nope', 'info@vub.sk'));
        $this->assertFalse($vub->supports('nope'));
    }

    #[Test]
    public function tatraRejectsDatesOutsideTheBankFormats(): void
    {
        $parser = new TatraBankaMailParser();
        $mailContent = $parser->parse('2015-01-16 12:51 bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.');

        $this->assertSame('2015-01-16 12:51', $mailContent->transactionDate?->format('Y-m-d H:i'));
        $this->assertSame('1.00', $mailContent->money?->toDecimalString());
        $this->assertNull($parser->tryParse('včera bol zostatok Vasho uctu SK1 zvyseny o 1,00 EUR.'));
        $this->assertNull($parser->tryParse('not a tatra mail'));
    }

    #[Test]
    public function simpleParserSkipsTokensAndRejectsInvalidAmount(): void
    {
        $parser = new TatraBankaSimpleMailParser();

        $mailContent = $parser->parse("RES=OK\nAMT=10.00 CURR=EUR");
        $this->assertSame('10.00', $mailContent->money?->toDecimalString());
        $orphan = $parser->parse('RES=OK ORPHAN AMT=10.00 CURR=EUR');
        $this->assertSame('10.00', $orphan->money?->toDecimalString());
        $this->assertSame('OK', $orphan->res);
        $this->assertNull($parser->tryParse('AMT=nope CURR=EUR RES=OK'));
        $unknown = $parser->parse('AMT=10 CURR=ZZZ RES=OK VS=42');
        $this->assertNull($unknown->money);
        $this->assertSame('OK', $unknown->res);
        $this->assertSame('42', $unknown->vs);
        $short = $parser->parse('AMT=10 CURR=ZZ RES=OK');
        $this->assertNull($short->money);
        $this->assertSame('OK', $short->res);
    }

    #[Test]
    public function statementParserReportsUnreadableAndIncompleteRows(): void
    {
        $parser = new TatraBankaStatementMailParser();

        try {
            $parser->tryParse('BEGIN PGP MESSAGE without armor');
            $this->fail('A PGP statement without a decryptor must not be swallowed.');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString('not configured', $exception->getMessage());
        }
        $this->assertNull($parser->tryParse('20190315|only|few|columns'));
        try {
            $parser->parseMulti('not encrypted');
            $this->fail('A row without amount columns must fail.');
        } catch (InvalidEmailFormatException $exception) {
            $this->assertStringContainsString('amount/currency', $exception->getMessage());
        }

        $broken = new TatraBankaStatementMailParser(new TatraBankaMailDecryptor('/tmp', 'x'));
        try {
            $broken->tryParse("-----BEGIN PGP MESSAGE-----\n-----END PGP MESSAGE-----");
            $this->fail('A missing key path is a configuration error.');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString('missing path', $exception->getMessage());
        }

        $decryptor = new TatraBankaMailDecryptor(
            __DIR__ . '/../fixtures/tatrabanka-statement/private-key.asc',
            'heslo',
        );
        try {
            $decryptor->decrypt("-----BEGIN PGP MESSAGE-----\nnot-valid\n-----END PGP MESSAGE-----");
            $this->fail('A broken PGP message must throw.');
        } catch (DecryptionException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }

        $unreadable = new TatraBankaStatementMailParser($decryptor);
        $this->assertNull($unreadable->tryParse("-----BEGIN PGP MESSAGE-----\nnot-valid\n-----END PGP MESSAGE-----"));
    }

    #[Test]
    public function decryptorRejectsUnreadableKeyAndInvalidMessage(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bmp-key');
        $this->assertNotFalse($path);
        file_put_contents($path, '');
        chmod($path, 0o000);

        try {
            $decryptor = new TatraBankaMailDecryptor($path, 'heslo');
            $decryptor->decrypt('not a message');
            $this->fail('Unreadable key must throw');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('failed to read private key file', $exception->getMessage());
        } finally {
            chmod($path, 0o600);
            unlink($path);
        }

        $empty = tempnam(sys_get_temp_dir(), 'bmp-empty');
        $this->assertNotFalse($empty);
        file_put_contents($empty, '');
        try {
            $decryptor = new TatraBankaMailDecryptor($empty, 'heslo');
            $decryptor->decrypt('not a message');
            $this->fail('Empty key must throw');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('failed to read private key file', $exception->getMessage());
        } finally {
            unlink($empty);
        }
    }
}
