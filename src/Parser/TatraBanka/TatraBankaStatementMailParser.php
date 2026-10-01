<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

final readonly class TatraBankaStatementMailParser implements ParserInterface
{
    public function __construct(
        private TatraBankaMailDecryptor $decryptor,
    ) {}

    /**
     * @return MailContent[]|null
     */
    public function parseMulti(string $content): ?array
    {
        if (preg_match('/(-{5}BEGIN[A-Za-z0-9 \-\r?\n+\/=]+END PGP MESSAGE-{5})/m', $content, $results) !== 1) {
            return null;
        }

        $decrypted = $this->decryptor->decrypt($results[0]);
        if ($decrypted === null) {
            return null;
        }

        $transactions = preg_split("/\r\n|\n|\r/", $decrypted);
        if ($transactions === false) {
            return null;
        }

        return array_filter(
            array_map($this->parse(...), array_slice($transactions, 1)),
            static fn(?MailContent $mc): bool => $mc !== null,
        );
    }

    #[\Override]
    public function parse(string $content): ?MailContent
    {
        $cols = array_filter(explode('|', $content), static fn(string $value): bool => $value !== '' && $value !== '0');
        if (count($cols) === 0) {
            return null;
        }

        return new MailContent(
            amount: (float) $cols[9],
            currency: $cols[10],
            vs: $cols[18],
            accountNumber: mb_trim($cols[19]),
            transactionDate: strtotime($cols[0]),
        );
    }
}
