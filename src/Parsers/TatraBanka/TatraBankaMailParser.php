<?php
declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

class TatraBankaMailParser implements ParserInterface
{
    /**
     * @param $content
     * @return ?MailContent
     */
    public function parse(string $content): ?MailContent
    {
        $mailContent = new MailContent();

        $pattern1 = '/(.*) bol zostatok Vasho uctu ([a-zA-Z0-9]+) (zvyseny|znizeny) o ([0-9 ]+,[0-9]+) ([a-zA-Z]+)/m';
        $res = preg_match($pattern1, $content, $result);
        if (!$res) {
            return null;
        }

        $mailContent->setTransactionDate(strtotime($result[1]));
        $mailContent->setAccountNumber($result[2]);

        $amount = floatval(str_replace(',', '.', str_replace(' ', '', $result[4])));
        $currency = $result[5];
        if ($result[3] === 'znizeny') {
            $amount = -$amount;
        }
        $mailContent->setAmount($amount);
        $mailContent->setCurrency($currency);

        $pattern = '/Informacia pre prijemcu: (.*)/m';
        $res = preg_match($pattern, $content, $result);
        if ($res) {
            $mailContent->setReceiverMessage($result[1]);
        }

        // loads VS provided in format:
        // - Referencia platitela: /VS1234056789/SS/KS
        $pattern = '/Referencia platitela: \/VS(.*)\/SS(.*)\/KS(.*)/m';
        $res = preg_match($pattern, $content, $result);
        if ($res) {
            $mailContent->setVs($result[1]);
            $mailContent->setSs($result[2]);
            $mailContent->setKs($result[3]);
        }

        // search whole email for number with `vs` prefix
        if ($mailContent->getVs() === null) {
            $pattern = '/vs([0-9]{1,10})/i';
            $res = preg_match($pattern, $content, $result);
            if ($res) {
                $mailContent->setVs($result[1]);
            }
        }

        // if still no number found, check receiver message
        // - some payers incorrectly set this field with VS number but without "VS" prefix
        // - some banks send here variable symbol in Creditor Reference Information - SEPA XML format
        // loads VS provided in formats:
        // - Informacia pre prijemcu: 1234056789
        // - Informacia pre prijemcu: (CdtrRefInf)(Tp)(CdOrPrtry)(Cd)SCOR(/Cd)(/CdOrPrtry)(/Tp)(Ref)1234056789(/Ref)(/CdtrRefInf)
        if ($mailContent->getVs() === null) {
            $pattern = '/Informacia pre prijemcu:.*\b([0-9]{1,10})\b.*/i';
            $res = preg_match($pattern, $content, $result);
            if ($res) {
                $mailContent->setVs($result[1]);
            }
        }

        // if still no number found, check unique mandate reference
        // some payers incorrectly set this field without correct prefixes /VS/SS/KS
        // loads VS provided in format:
        // - Referencia platitela: 1234056789
        if ($mailContent->getVs() === null) {
            $pattern = '/Referencia platitela:.*\b([0-9]{1,10})\b.*/i';
            $res = preg_match($pattern, $content, $result);
            if ($res) {
                $mailContent->setVs($result[1]);
            }
        }

        $pattern4 = '/Popis transakcie: (.*)/m';
        $res = preg_match($pattern4, $content, $result);
        if ($res) {
            $mailContent->setDescription($result[1]);

            $descriptionParts = explode(' ', $result[1], 2);
            $hasPrefix = count($descriptionParts) === 2;
            $mailContent->setSourceAccountNumber($descriptionParts[$hasPrefix ? 1 : 0]);
        }

        return $mailContent;
    }
}
