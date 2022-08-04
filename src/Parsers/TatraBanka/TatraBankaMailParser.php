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

        $pattern = '/Referencia platitela: \/VS(.*)\/SS(.*)\/KS(.*)/m';
        $res = preg_match($pattern, $content, $result);
        if ($res) {
            $mailContent->setVs($result[1]);
            $mailContent->setSs($result[2]);
            $mailContent->setKs($result[3]);
        }

        if ($mailContent->getVs() === null) {
            $pattern = '/vs([0-9]{1,10})/i';
            $res = preg_match($pattern, $content, $result);
            if ($res) {
                $mailContent->setVs($result[1]);
            }
        }

        $pattern4 = '/Popis transakcie: (.*)/m';
        $res = preg_match($pattern4, $content, $result);
        if ($res) {
            $mailContent->setDescription($result[1]);
        }

        return $mailContent;
    }
}
