<?php

namespace Tomaj\BankMailsParser\Parser;

use Tomaj\BankMailsParser\MailContent;

class TatraBankaMailParser implements ParserInterface
{
    /**
     * @param $content
     * @return bool|MailContent
     */
    public function parse($content)
    {
        $mailContent = new MailContent();

        $pattern1 = '/(.*) bol zostatok Vasho uctu ([a-zA-Z0-9]+) (zvyseny|znizeny) o ([0-9 ]+,[0-9]+) ([a-zA-Z]+)/m';
        $res = preg_match($pattern1, $content, $result);
        if (!$res) {
            return false;
        }

        $mailContent->setTransactionDate(strtotime($result[1]));
        $mailContent->setAccountNumber($result[2]);

        $amount = floatval(str_replace(',', '.', str_replace(' ', '', $result[4])));
        $currency = $result[5];
        if ($result[3] == 'znizeny') {
            $amount = -$amount;
        }
        $mailContent->setAmount($amount);
        $mailContent->setCurrency($currency);

        $pattern2 = '/Informacia pre prijemcu: (.*)/m';
        $res = preg_match($pattern2, $content, $result);
        if ($res) {
            $mailContent->setReceiverMessage($result[1]);
        }

        $pattern3 = '/Referencia platitela: \/VS(.*)\/SS(.*)\/KS(.*)/m';
        $res = preg_match($pattern3, $content, $result);
        if ($res) {
            $mailContent->setVs($result[1]);
            $mailContent->setSs($result[2]);
            $mailContent->setKs($result[3]);
        }

        return $mailContent;
    }
}
