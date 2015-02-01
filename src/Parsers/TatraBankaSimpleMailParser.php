<?php

namespace Tomaj\BankMailsParser\Parser;

use Tomaj\BankMailsParser\MailContent;

class TatraBankaSimpleMailParser implements ParserInterface
{
    public function parse($content)
    {
        $mailContent = new MailContent();

        $pattern1 = '/VS=(.*) RES=(.*) AC=(.*) SIGN=(.*)/m';
        $res = preg_match($pattern1, $content, $result);
        if (!$res) {
            return false;
        }

        if ($result[2] == 'OK') {
            $mailContent->setVs($result[1]);
        }

        return $mailContent;
    }
}
