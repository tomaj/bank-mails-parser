<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\TatraBanka;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\DecryptionException;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;

#[CoversClass(TatraBankaMailDecryptor::class)]
class TatraBankaMailDecryptorTest extends TestCase
{
    #[Test]
    public function throwsExceptionForMissingKeyFile(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageIsOrContains('missing path to TatraBanka PGP private key in config');

        $decryptor = new TatraBankaMailDecryptor('', 'passphrase');
        $decryptor->decrypt('some content');
    }

    #[Test]
    public function throwsExceptionForNonExistentKeyFile(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageIsOrContains('missing path to TatraBanka PGP private key in config');

        $decryptor = new TatraBankaMailDecryptor('/non/existent/path.asc', 'passphrase');
        $decryptor->decrypt('some content');
    }

    #[Test]
    public function decryptsValidPGPMessage(): void
    {
        $encryptedContent = (string) file_get_contents(__DIR__ . '/../../fixtures/tatrabanka-statement/zasifrovany-vypis.txt');

        $decryptor = new TatraBankaMailDecryptor(
            __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc',
            'heslo',
        );

        $decrypted = $decryptor->decrypt($encryptedContent);

        self::assertStringContainsString('2621234415', $decrypted);
    }

    #[Test]
    public function rejectsAKeyThatContainsNoSecretPacket(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bmp-key');
        self::assertIsString($path);
        file_put_contents($path, "\x80\x01\x00");

        try {
            new TatraBankaMailDecryptor($path, 'heslo')->decrypt('message');
            self::fail('A key without a secret packet must fail');
        } catch (ConfigurationException $exception) {
            self::assertSame('Private key passphrase is incorrect or the key has no usable secret key', $exception->getMessage());
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function turnsLibraryWarningsIntoDecryptionException(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bmp-key');
        self::assertIsString($path);
        file_put_contents($path, 'not-a-key');

        try {
            new TatraBankaMailDecryptor($path, 'heslo')->decrypt('message');
            self::fail('Library warnings must become an exception');
        } catch (ConfigurationException $exception) {
            self::assertInstanceOf(DecryptionException::class, $exception->getPrevious());
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function wrapsLibraryExceptionsFromAMessageThatIsNotEncrypted(): void
    {
        $literal = new \OpenPGP_LiteralDataPacket('hello');
        $armored = \OpenPGP::enarmor(new \OpenPGP_Message([$literal])->to_bytes(), 'PGP MESSAGE');
        $decryptor = new TatraBankaMailDecryptor(__DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc', 'heslo');

        try {
            $decryptor->decrypt($armored);
            self::fail('A literal packet is not an encrypted message');
        } catch (DecryptionException $exception) {
            self::assertInstanceOf(\Exception::class, $exception->getPrevious());
            self::assertSame(0, $exception->getCode());
            self::assertStringContainsString('Not an asymmetrically encrypted message', $exception->getMessage());
        }
    }
}
