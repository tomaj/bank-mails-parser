<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\DecryptionException;

final readonly class TatraBankaMailDecryptor
{
    public function __construct(
        private string $privateKeyPath,
        private string $passphrase,
    ) {}

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
            // @phpstan-ignore foreach.nonIterable (OpenPGP library has dynamic types)
            foreach ($parsedKey as $packet) {
                if (!($packet instanceof \OpenPGP_SecretKeyPacket || $packet instanceof \OpenPGP_SecretSubkeyPacket)) {
                    continue;
                }

                $secret = \OpenPGP_Crypt_Symmetric::decryptSecretKey($this->passphrase, $packet);
            }

            if (!($secret instanceof \OpenPGP_SecretKeyPacket || $secret instanceof \OpenPGP_SecretSubkeyPacket)) {
                throw new ConfigurationException('Private key passphrase is incorrect or the key has no usable secret key');
            }

            $msg = \OpenPGP_Message::parse(\OpenPGP::unarmor($contents, 'PGP MESSAGE'));
            $decryptor = new \OpenPGP_Crypt_RSA($secret);
            $decrypted = $decryptor->decrypt($msg);

            if ($decrypted && isset($decrypted->packets[0]->data) && is_string($decrypted->packets[0]->data) && $decrypted->packets[0]->data !== '') {
                return $decrypted->packets[0]->data;
            }

            throw new DecryptionException('PGP message could not be decrypted');
        });
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
        } catch (ConfigurationException $exception) {
            throw $exception;
        } catch (DecryptionException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new DecryptionException($exception->getMessage(), 0, $exception);
        } finally {
            restore_error_handler();
        }
    }
}
