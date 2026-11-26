<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\TatraBanka;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\DecryptionException;
use Tomaj\BankMailsParser\Exception\InvalidArgumentException;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;

#[CoversClass(TatraBankaMailDecryptor::class)]
class TatraBankaMailDecryptorTest extends TestCase
{
    private const string KEY = __DIR__ . '/../../fixtures/tatrabanka-statement/private-key.asc';

    private const string PASSPHRASE = 'Sup3r-secret passphrase';

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

    #[Test]
    public function dumpsDoNotContainThePassphrase(): void
    {
        $decryptor = new TatraBankaMailDecryptor(self::KEY, self::PASSPHRASE);

        ob_start();
        var_dump($decryptor);
        $varDump = (string) ob_get_clean();

        $dumps = [
            'print_r' => print_r($decryptor, true),
            'var_export' => var_export($decryptor, true),
            'var_dump' => $varDump,
            'array cast' => print_r((array) $decryptor, true),
            'json_encode' => (string) json_encode((array) $decryptor, JSON_PARTIAL_OUTPUT_ON_ERROR),
        ];
        foreach ($dumps as $name => $dump) {
            self::assertStringContainsString('private-key.asc', $dump, $name);
            self::assertStringNotContainsString(self::PASSPHRASE, $dump, $name);
        }
    }

    /**
     * 5.0 serialized the two promoted properties; the serialized form stays the same, so decryptors serialized by 5.0
     * (a queued job, a cache) still unserialize and vice versa.
     */
    #[Test]
    public function serializesLike50(): void
    {
        $serialized50 = sprintf(
            'O:63:"%1$s":2:{s:79:"%2$s%1$s%2$sprivateKeyPath";s:%3$d:"%4$s";s:75:"%2$s%1$s%2$spassphrase";s:5:"heslo";}',
            TatraBankaMailDecryptor::class,
            "\0",
            strlen(self::KEY),
            self::KEY,
        );
        $encrypted = (string) file_get_contents(__DIR__ . '/../../fixtures/tatrabanka-statement/zasifrovany-vypis.txt');

        self::assertSame($serialized50, serialize(new TatraBankaMailDecryptor(self::KEY, 'heslo')));
        $unserialized = unserialize($serialized50);
        self::assertInstanceOf(TatraBankaMailDecryptor::class, $unserialized);
        self::assertStringContainsString('2621234415', $unserialized->decrypt($encrypted));
        self::assertStringNotContainsString('heslo', print_r($unserialized, true));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidSerializedData(): array
    {
        $path = "\0" . TatraBankaMailDecryptor::class . "\0privateKeyPath";
        $passphrase = "\0" . TatraBankaMailDecryptor::class . "\0passphrase";

        return [
            'no properties' => [[]],
            'public names' => [['privateKeyPath' => self::KEY, 'passphrase' => 'heslo']],
            'no passphrase' => [[$path => self::KEY]],
            'no key path' => [[$passphrase => 'heslo']],
            'passphrase not a string' => [[$path => self::KEY, $passphrase => 123]],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[Test]
    #[DataProvider('invalidSerializedData')]
    public function rejectsAnInvalidSerializedForm(array $data): void
    {
        $serialized = sprintf('O:63:"%s":%s', TatraBankaMailDecryptor::class, substr(serialize($data), 2));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Invalid serialized ' . TatraBankaMailDecryptor::class);

        unserialize($serialized);
    }

    #[Test]
    public function decryptsWithTheWrappedPassphrase(): void
    {
        $encrypted = (string) file_get_contents(__DIR__ . '/../../fixtures/tatrabanka-statement/zasifrovany-vypis.txt');

        self::assertStringContainsString('2621234415', new TatraBankaMailDecryptor(self::KEY, 'heslo')->decrypt($encrypted));
        self::assertStringContainsString('2621234415', new TatraBankaMailDecryptor(privateKeyPath: self::KEY, passphrase: 'heslo')->decrypt($encrypted));
    }

    #[Test]
    public function constructorFrameRedactsThePassphrase(): void
    {
        $path = 123;

        // php.ini-production (and setup-php in CI) drops all arguments from traces; record them like a development setup
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        try {
            new TatraBankaMailDecryptor($path, self::PASSPHRASE); // @phpstan-ignore argument.type (a TypeError puts the constructor frame on the trace)
            self::fail('TypeError expected');
        } catch (\TypeError $exception) {
            $trace = $exception->getTraceAsString();
            self::assertStringContainsString('TatraBankaMailDecryptor->__construct(123, Object(SensitiveParameterValue))', $trace);
            self::assertStringNotContainsString(self::PASSPHRASE, $trace);
        } finally {
            ini_set('zend.exception_ignore_args', (string) $ignoreArgs);
        }
    }

    /**
     * The OpenPGP library throws "Unsupported cipher" from inside decryptSecretKey(), which takes the passphrase as a
     * plain argument; no exception in the chain may record it.
     */
    #[Test]
    public function libraryExceptionTraceDoesNotContainThePassphrase(): void
    {
        $key = \OpenPGP_Message::parse((string) file_get_contents(self::KEY));
        foreach ($key as $packet) {
            if ($packet instanceof \OpenPGP_SecretKeyPacket) {
                $packet->symmetric_algorithm = 200;
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'bmp-key');
        self::assertIsString($path);
        file_put_contents($path, $key->to_bytes());

        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        try {
            new TatraBankaMailDecryptor($path, self::PASSPHRASE)->decrypt('message');
            self::fail('An unsupported cipher must fail');
        } catch (DecryptionException $exception) {
            self::assertSame('Unsupported cipher', $exception->getMessage());
            self::assertStringContainsString('TatraBankaMailDecryptor->decryptSecretKey(Object(OpenPGP_SecretKeyPacket))', $exception->getTraceAsString());
            // getTraceAsString() shortens strings to 15 characters, so look at the recorded arguments themselves
            for ($e = $exception; $e instanceof \Throwable; $e = $e->getPrevious()) {
                foreach ($e->getTrace() as $frame) {
                    self::assertNotContains(self::PASSPHRASE, $frame['args'] ?? [], $e->getTraceAsString());
                }
            }
        } finally {
            ini_set('zend.exception_ignore_args', (string) $ignoreArgs);
            unlink($path);
        }
    }
}
