<?php

namespace Tomaj\BankMailsParser;

class MailContent
{
    private $amount;

    private $accountNumber;

    private $vs;

    private $ss;

    private $ks;

    private $transactionDate;

    private $currency;

    private $receiverMessage;

    private $sign;

    private $cid;

    private $res;

    public function getKs()
    {
        return $this->ks;
    }

    public function setKs($ks)
    {
        if ($ks == '') {
            $ks = null;
        }
        $this->ks = $ks;
    }

    public function getSs()
    {
        return $this->ss;
    }

    public function setSs($ss)
    {
        if ($ss == '') {
            $ss = null;
        }
        $this->ss = $ss;
    }

    public function getVs()
    {
        return $this->vs;
    }

    public function setVs($vs)
    {
        if ($vs == '') {
            $vs = null;
        }
        $this->vs = $vs;
    }

    public function getReceiverMessage()
    {
        return $this->receiverMessage;
    }

    public function setReceiverMessage($receiverMessage)
    {
        $this->receiverMessage = $receiverMessage;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    public function getTransactionDate()
    {
        return $this->transactionDate;
    }

    public function setTransactionDate($transactionDate)
    {
        $this->transactionDate = $transactionDate;
    }

    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    public function setAccountNumber($accountNumber)
    {
        $this->accountNumber = $accountNumber;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    public function setCid($cid)
    {
        $this->cid = $cid;
    }

    public function getCid()
    {
        return $this->cid;
    }

    public function setSign($sign)
    {
        $this->sign = $sign;
    }

    public function getSign()
    {
        return $this->sign;
    }

    public function setRes($res)
    {
        $this->res = $res;
    }

    public function getRes()
    {
        return $this->res;
    }
}
