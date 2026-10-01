<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser\TatraBanka;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;

#[CoversClass(TatraBankaMailDecryptor::class)]
class TatraBankaMailDecryptorTest extends TestCase
{
    #[Test]
    public function throwsExceptionForMissingKeyFile()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('missing path to TatraBanka PGP private key in config');

        $decryptor = new TatraBankaMailDecryptor('', 'passphrase');
        $decryptor->decrypt('some content');
    }

    #[Test]
    public function throwsExceptionForNonExistentKeyFile()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('missing path to TatraBanka PGP private key in config');

        $decryptor = new TatraBankaMailDecryptor('/non/existent/path.asc', 'passphrase');
        $decryptor->decrypt('some content');
    }

    #[Test]
    public function decryptsValidPGPMessage()
    {
        $encryptedContent = file_get_contents(__DIR__ . '/data/tb_encrypted_mail_body.txt');

        $decryptor = new TatraBankaMailDecryptor(
            __DIR__ . '/data/tb_mail_private_key.asc',
            'heslo',
        );

        $decrypted = $decryptor->decrypt($encryptedContent);

        $this->assertNotNull($decrypted);
        $this->assertStringContainsString('2621234415', $decrypted);
    }
}
