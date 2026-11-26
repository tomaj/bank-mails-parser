<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\DecryptionException;
use Tomaj\BankMailsParser\Exception\InvalidArgumentException;

final readonly class TatraBankaMailDecryptor
{
    /**
     * Wrapped so that var_dump(), print_r(), var_export() and debuggers (Tracy, Symfony VarDumper) do not show it.
     *
     * @var \SensitiveParameterValue<string>
     */
    private \SensitiveParameterValue $passphrase;

    public function __construct(
        private string $privateKeyPath,
        #[\SensitiveParameter]
        string $passphrase,
    ) {
        $this->passphrase = new \SensitiveParameterValue($passphrase);
    }

    /**
     * Same serialized form as 5.0 (a \SensitiveParameterValue cannot be serialized), so serialized decryptors keep
     * working in both directions. The passphrase is in the serialized string in clear text, as it was before.
     *
     * @return array<string, string>
     */
    public function __serialize(): array
    {
        return [
            "\0" . self::class . "\0privateKeyPath" => $this->privateKeyPath,
            "\0" . self::class . "\0passphrase" => $this->passphrase->getValue(),
        ];
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public function __unserialize(#[\SensitiveParameter] array $data): void
    {
        $privateKeyPath = $data["\0" . self::class . "\0privateKeyPath"] ?? null;
        $passphrase = $data["\0" . self::class . "\0passphrase"] ?? null;
        if (!is_string($privateKeyPath) || !is_string($passphrase)) {
            throw new InvalidArgumentException('Invalid serialized ' . self::class);
        }

        $this->privateKeyPath = $privateKeyPath;
        $this->passphrase = new \SensitiveParameterValue($passphrase);
    }

    public function decrypt(string $contents): string
    {
        if ($this->privateKeyPath === '' || !is_file($this->privateKeyPath)) {
            throw new ConfigurationException('missing path to TatraBanka PGP private key in config');
        }

        if (!is_readable($this->privateKeyPath)) {
            throw new ConfigurationException('failed to read private key file');
        }

        $fileContents = file_get_contents($this->privateKeyPath);
        if ($fileContents === false || $fileContents === '') {
            throw new ConfigurationException('failed to read private key file');
        }

        return $this->guard(function () use ($fileContents, $contents): string {
            try {
                $parsedKey = \OpenPGP_Message::parse($fileContents);
            } catch (DecryptionException $exception) {
                throw new ConfigurationException('failed to read private key file', 0, $exception);
            }

            $secret = null;
            // OpenPGP_SecretSubkeyPacket extends OpenPGP_SecretKeyPacket, so this accepts both
            foreach ($parsedKey as $packet) {
                if (!$packet instanceof \OpenPGP_SecretKeyPacket) {
                    continue;
                }

                $secret = $this->decryptSecretKey($packet);
            }

            if (!$secret instanceof \OpenPGP_SecretKeyPacket) {
                throw new ConfigurationException('Private key passphrase is incorrect or the key has no usable secret key');
            }

            $msg = \OpenPGP_Message::parse(\OpenPGP::unarmor($contents, 'PGP MESSAGE'));
            $decryptor = new \OpenPGP_Crypt_RSA($secret);
            $decrypted = $decryptor->decrypt($msg);

            if ($decrypted instanceof \OpenPGP_Message && isset($decrypted->packets[0]->data) && is_string($decrypted->packets[0]->data) && $decrypted->packets[0]->data !== '') {
                return $decrypted->packets[0]->data;
            }

            throw new DecryptionException('PGP message could not be decrypted');
        });
    }

    /**
     * The OpenPGP library takes the passphrase as a plain argument, so an exception thrown inside it (an unsupported
     * cipher, a PHP warning turned into an exception by guard()) records the passphrase in its trace. Only its message
     * is kept: the new exception is created in this frame, whose arguments do not include the passphrase.
     */
    private function decryptSecretKey(\OpenPGP_SecretKeyPacket $packet): ?\OpenPGP_SecretKeyPacket
    {
        try {
            return \OpenPGP_Crypt_Symmetric::decryptSecretKey($this->passphrase->getValue(), $packet);
        } catch (\Throwable $exception) {
            // without previous: the library exception's trace is the one that holds the passphrase
            throw new DecryptionException($exception->getMessage());
        }
    }

    /**
     * @param callable(): string $callback
     */
    private function guard(callable $callback): string
    {
        set_error_handler(static function (int $severity, string $message): never {
            throw new DecryptionException($message);
        });

        try {
            return $callback();
        } catch (ConfigurationException|DecryptionException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new DecryptionException($exception->getMessage(), 0, $exception);
        } finally {
            restore_error_handler();
        }
    }
}
