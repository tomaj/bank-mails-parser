<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

final readonly class TatraBankaMailDecryptor
{
    public function __construct(
        private string $privateKeyPath,
        private string $passphrase,
    ) {}

    public function decrypt(string $contents): ?string
    {
        if ($this->privateKeyPath === '' || !file_exists($this->privateKeyPath)) {
            throw new \Exception('missing path to TatraBanka PGP private key in config');
        }

        $fileContents = file_get_contents($this->privateKeyPath);
        if ($fileContents === false) {
            throw new \Exception('failed to read private key file');
        }

        $privateKey = \OpenPGP_Message::parse($fileContents);
        // @phpstan-ignore foreach.nonIterable (OpenPGP library has dynamic types)
        foreach ($privateKey as $p) {
            if (!($p instanceof \OpenPGP_SecretKeyPacket || $p instanceof \OpenPGP_SecretSubkeyPacket)) {
                continue;
            }

            $privateKey = \OpenPGP_Crypt_Symmetric::decryptSecretKey($this->passphrase, $p);
        }

        $msg = \OpenPGP_Message::parse(\OpenPGP::unarmor($contents, 'PGP MESSAGE'));

        $decryptor = new \OpenPGP_Crypt_RSA($privateKey);
        $decrypted = $decryptor->decrypt($msg);

        if ($decrypted && isset($decrypted->packets[0]->data)) {
            return $decrypted->packets[0]->data;
        }

        return null;
    }
}
