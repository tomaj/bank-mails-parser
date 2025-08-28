<?php
declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

class TatraBankaStatementMailParser implements ParserInterface
{
    private TatraBankaMailDecryptor $decryptor;

    public function __construct(
        TatraBankaMailDecryptor $decryptor
    ) {
        $this->decryptor = $decryptor;
    }

    /**
     * @return MailContent[]|null
     */
    public function parseMulti(string $content): ?array
    {
        $mailContents = [];

        $results = [];
        $res = preg_match('/(-{5}BEGIN[A-Za-z0-9 \-\r?\n+\/=]+END PGP MESSAGE-{5})/m', $content, $results);
        if (!$res) {
            return null;
        }

        $decrypted = $this->decryptor->decrypt($results[0]);
        if ($decrypted === null) {
            return null;
        }
        $transactions = preg_split("/\r\n|\n|\r/", $decrypted);

        foreach ($transactions as $line => $transaction) {
            if (!$line) {
                continue;
            }

            $mailContent = $this->parse($transaction);
            if ($mailContent !== null) {
                $mailContents[] = $mailContent;
            }
        }

        return $mailContents;
    }

    public function parse(string $content): ?MailContent
    {
        $cols = array_filter(explode('|', $content));
        if (empty($cols)) {
            return null;
        }

        $mailContent = new MailContent();
        $mailContent->setAmount((float) $cols[9]);
        $mailContent->setCurrency($cols[10]);
        $mailContent->setVs($cols[18]);
        $mailContent->setAccountNumber(trim($cols[19]));
        $mailContent->setTransactionDate(strtotime($cols[0]));

        return $mailContent;
    }
}
