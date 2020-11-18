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

    private $description;

    private $sign;

    private $cid;

    private $res;

    private $ac;

    private $cc;

    private $tid;

    private $txn;

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

    public function getCc()
    {
        return $this->cc;
    }

    public function setCc($cc)
    {
        if ($cc == '') {
            $cc = null;
        }
        $this->cc = $cc;
    }

    public function getTid()
    {
        return $this->tid;
    }

    public function setTid($tid)
    {
        if ($tid == '') {
            $tid = null;
        }
        $this->tid = $tid;
    }

    public function getReceiverMessage()
    {
        return $this->receiverMessage;
    }

    public function setReceiverMessage($receiverMessage)
    {
        $this->receiverMessage = $receiverMessage;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
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

    public function setAc($ac)
    {
        $this->ac = $ac;
    }

    public function getAc()
    {
        return $this->ac;
    }

    public function setTxn($txn)
    {
        $this->txn = $txn;
    }

    public function getTxn()
    {
        return $this->txn;
    }
}
