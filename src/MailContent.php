<?php
declare(strict_types=1);

namespace Tomaj\BankMailsParser;

class MailContent
{
    private $amount;

    private $accountNumber;

    private $sourceAccountNumber;

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

    private $rc;

    public function getKs(): ?string
    {
        return $this->ks;
    }

    public function setKs(string $ks)
    {
        if ($ks == '') {
            $ks = null;
        }
        $this->ks = $ks;
    }

    public function getSs(): ?string
    {
        return $this->ss;
    }

    public function setSs(string $ss)
    {
        if ($ss == '') {
            $ss = null;
        }
        $this->ss = $ss;
    }

    public function getVs(): ?string
    {
        return $this->vs;
    }

    public function setVs(string $vs)
    {
        if ($vs == '') {
            $vs = null;
        }
        $this->vs = $vs;
    }

    public function getCc(): ?string
    {
        return $this->cc;
    }

    public function setCc(string $cc)
    {
        if ($cc == '') {
            $cc = null;
        }
        $this->cc = $cc;
    }

    public function getTid(): ?string
    {
        return $this->tid;
    }

    public function setTid(string $tid)
    {
        if ($tid == '') {
            $tid = null;
        }
        $this->tid = $tid;
    }

    public function getReceiverMessage(): ?string
    {
        return $this->receiverMessage;
    }

    public function setReceiverMessage(string $receiverMessage)
    {
        $this->receiverMessage = $receiverMessage;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
    }

    public function getTransactionDate() // TODO
    {
        return $this->transactionDate;
    }

    public function setTransactionDate($transactionDate)
    {
        $this->transactionDate = $transactionDate;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string $accountNumber)
    {
        $this->accountNumber = $accountNumber;
    }

    public function getSourceAccountNumber(): ?string
    {
        return $this->sourceAccountNumber;
    }

    public function setSourceAccountNumber(string $sourceAccountNumber): void
    {
        $this->sourceAccountNumber = $sourceAccountNumber;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount)
    {
        $this->amount = $amount;
    }

    public function setCid(string $cid)
    {
        $this->cid = $cid;
    }

    public function getCid(): ?string
    {
        return $this->cid;
    }

    public function setSign(string $sign)
    {
        $this->sign = $sign;
    }

    public function getSign(): ?string
    {
        return $this->sign;
    }

    public function setRes(string $res)
    {
        $this->res = $res;
    }

    public function getRes(): ?string
    {
        return $this->res;
    }

    public function setAc(string $ac)
    {
        $this->ac = $ac;
    }

    public function getAc(): ?string
    {
        return $this->ac;
    }

    public function setTxn(string $txn)
    {
        $this->txn = $txn;
    }

    public function getTxn(): ?string
    {
        return $this->txn;
    }

    public function setRc(string $rc)
    {
        $this->rc = $rc;
    }

    public function getRc(): ?string
    {
        return $this->rc;
    }
}
