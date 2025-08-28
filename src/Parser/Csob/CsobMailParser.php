<?php
declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\Csob;

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

class CsobMailParser implements ParserInterface
{
    /**
     * @return MailContent[]
     */
    public function parseMulti(string $content): array
    {
        $transactions = array_slice(explode("Dne ", $content), 1);

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

        $pattern1 = '/(.*) byl(?:a)? na účtu ([a-zA-Z0-9]+) (?:zaúčtovaná|zaúčtována|zaúčtovaný) (?:zahraniční transakce\.|hotovostní transakce\.|(?:transakce typu: |transakce )?(Došlá platba|Příchozí úhrada|Došlá úhrada|SEPA převod|platební kartou))/mu';
        $res = preg_match($pattern1, $content, $result);
        if (!$res) {
            return null;
        }

        $mailContent->setTransactionDate(strtotime($result[1]));
        $mailContent->setAccountNumber($result[2]);

        $pattern3 = '/Účet protistrany(?:\/IBAN)*: (.*)/mu';
        $res = preg_match($pattern3, $content, $result);
        if ($res) {
            $mailContent->setSourceAccountNumber(trim($result[1]));
        }

        $pattern2 = '/Částka: ([+-])(.*?) ([A-Z]+)/mu';
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

        $pattern3 = '/Zpráva příjemci: (.*)/mu';
        $res = preg_match($pattern3, $content, $result);
        if ($res) {
            $mailContent->setReceiverMessage(trim($result[1]));
        }

        $pattern4 = '/Variabilní symbol: ([0-9]{1,10})/m';
        $res = preg_match($pattern4, $content, $result);
        if ($res) {
            // check if variable symbol field is not filled with zeros (0000000000)
            // (this can happen for foreign transfers which use receiver message for VS)
            if ((int) $result[1] !== 0) {
                $mailContent->setVs($result[1]);
            }
        }
        $pattern4 = '/Identifikace: ([0-9]{1,10})/m';
        $res = preg_match($pattern4, $content, $result);
        if ($res) {
            $mailContent->setVs($result[1]);
        }

        // search whole email for number with `vs` / `VS` prefix
        if ($mailContent->getVs() === null) {
            $pattern = '/vs([0-9]{1,10})/i';
            $res = preg_match($pattern, $content, $result);
            if ($res) {
                $mailContent->setVs($result[1]);
            }
        }

        // search whole email for number with `v.s.` / `V.S.` prefix
        if ($mailContent->getVs() === null) {
            $pattern = '/v\.s\.([0-9]{1,10})/i';
            $res = preg_match($pattern, $content, $result);
            if ($res) {
                $mailContent->setVs($result[1]);
            }
        }

        // if still no number (VS) found, check receiver message
        // - some payers incorrectly set this field with VS number but without "VS" prefix
        // - some banks send here variable symbol in Creditor Reference Information - SEPA XML format
        // loads VS provided in formats:
        // - Informacia pre prijemcu: 1234056789
        // - Informacia pre prijemcu: (CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)
        if ($mailContent->getVs() === null) {
            $pattern = '/Zpráva příjemci:.*\b([0-9]{1,10})\b.*/i';
            $res = preg_match($pattern, $content, $result);
            if ($res) {
                $mailContent->setVs($result[1]);
            }
        }

        // if still no number (VS) found, check payment purpose (some foreign payments use this field for variable symbol)
        if ($mailContent->getVs() === null) {
            $pattern4 = '/Účel platby: ([0-9]{1,10})/m';
            $res = preg_match($pattern4, $content, $result);
            if ($res) {
                $mailContent->setVs($result[1]);
            } else {
                // Transaction description/purpose (Účel platby) is sometimes multiline element.
                // For now it is always last entry before balance element. So check all lines until "Zůstatek".
                // Use modifier `s` - single-line (dot matches newline).
                $pattern4 = '/Účel platby:.*\b([0-9]{1,10})\b.*Zůstatek/s';
                $res = preg_match($pattern4, $content, $result);
                if ($res) {
                    $mailContent->setVs($result[1]);
                }
            }
        }

        $pattern5 = '/Konstantní symbol: ([0-9]{1,10})/mu';
        $res = preg_match($pattern5, $content, $result);
        if ($res) {
            $mailContent->setKs($result[1]);
        }

        return $mailContent;
    }
}
