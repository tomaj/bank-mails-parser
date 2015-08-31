<?php

namespace Tomaj\BankMailsParser\Parser;

use Tomaj\BankMailsParser\MailContent;
use DateTime;

class TatraBankaSimpleMailParser implements ParserInterface
{
    public function parse($content)
    {
        $mailContent = new MailContent();

        // cardpay version
        $cardpayVersion = true;
        $pattern1 = '/VS=(.*) RES=(.*) AC=(.*) SIGN=(.*)/m';
        $res = preg_match($pattern1, $content, $result);
        if (!$res) {
            // comfortpay version
            $pattern2 = '/VS=(.*) TRES=(.*) CID=(.*) SIGN=(.*)/m';
            $res = preg_match($pattern2, $content, $result);
            $cardpayVersion = false;
        }

        if (!$res) {
            return false;
        }

        if ($result[2] == 'OK') {
            $mailContent->setVs($result[1]);
            $mailContent->setSign($result[4]);
            $mailContent->setRes($result[2]);
            if ($cardpayVersion) {
                $mailContent->setAc($result[3]);
            } else {
                $mailContent->setCid($result[3]);
            }

            $mailContent->setTransactionDate(time());
        }

        return $mailContent;
    }
}
