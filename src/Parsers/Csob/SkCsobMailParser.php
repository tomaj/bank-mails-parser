<?php
declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\Csob;

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

class SkCsobMailParser implements ParserInterface
{
    /**
     * @return MailContent[]
     */
    public function parseMulti(string $content): array
    {
        $transactions = array_slice(explode("dňa ", $content), 1);

        $mailContents = [];
        foreach ($transactions as $transaction) {
            $mailContent = $this->parse($transaction);
            if ($mailContent !== null) {
                $mailContents[] = $mailContent;
            }
        }

        return $mailContents;
    }

    public function parse(string $content): ?MailContent
    {
        $mailContent = new MailContent();

        $pattern1 = '/(.*) bola na účte (.*) zaúčtovaná suma SEPA platobného príkazu/m';
        $res = preg_match($pattern1, $content, $result);
        if (!$res) {
            return null;
        }

        $mailContent->setTransactionDate(strtotime($result[1]));

        $pattern2 = '/suma:.*?([+-])(.*?) ([A-Z]+)/m';
        $res = preg_match($pattern2, $content, $result);
        if ($res) {
            // there's unicode non-breaking space (u00A0) in mime encoded version of email, unicode regex switched is necessary
            $amount = floatval(str_replace(',', '.', preg_replace('/\s+/u', '', $result[2])));
            $currency = $result[3];
            if ($result[1] === '-') {
                $amount = -$amount;
            }
            $mailContent->setAmount($amount);
            $mailContent->setCurrency($currency);
        }

        $pattern3 = '/informácia pre príjemcu: (.*)/m';
        $res = preg_match($pattern3, $content, $result);
        if ($res) {
            $mailContent->setReceiverMessage(trim($result[1]));
        }

        $pattern4 = '/VS([0-9]+)/m';
        $res = preg_match($pattern4, $content, $result);
        if ($res) {
            $mailContent->setVs($result[1]);
        }

        $pattern5 = '/KS([0-9]+)/m';
        $res = preg_match($pattern5, $content, $result);
        if ($res) {
            $mailContent->setKs($result[1]);
        }

        $pattern6 = '/z účtu:.*?([A-Z0-9 ]+)/m';
        $res = preg_match($pattern6, $content, $result);
        if ($res) {
            $iban = trim($result[1]);
            $mailContent->setAccountNumber($iban);
        }

        return $mailContent;
    }
}
