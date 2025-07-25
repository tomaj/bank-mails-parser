<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser;

use DateTimeInterface;

class MailContent
{
    public function __construct(
        private ?float $amount = null,
        private ?string $accountNumber = null,
        private ?string $sourceAccountNumber = null,
        private ?string $vs = null,
        private ?string $ss = null,
        private ?string $ks = null,
        private ?DateTimeInterface $transactionDate = null,
        private ?string $currency = null,
        private ?string $receiverMessage = null,
        private ?string $description = null,
        private ?string $sign = null,
        private ?string $cid = null,
        private ?string $res = null,
        private ?string $ac = null,
        private ?string $cc = null,
        private ?string $tid = null,
        private ?string $txn = null,
        private ?string $rc = null,
        private ?string $tres = null,
    ) {
    }

    public function getKs(): ?string
    {
        return $this->ks;
    }

    public function setKs(?string $ks): self
    {
        $this->ks = $ks === '' ? null : $ks;
        return $this;
    }

    public function getSs(): ?string
    {
        return $this->ss;
    }

    public function setSs(?string $ss): self
    {
        $this->ss = $ss === '' ? null : $ss;
        return $this;
    }

    public function getVs(): ?string
    {
        return $this->vs;
    }

    public function setVs(?string $vs): self
    {
        $this->vs = $vs === '' ? null : $vs;
        return $this;
    }

    public function getCc(): ?string
    {
        return $this->cc;
    }

    public function setCc(?string $cc): self
    {
        $this->cc = $cc === '' ? null : $cc;
        return $this;
    }

    public function getTid(): ?string
    {
        return $this->tid;
    }

    public function setTid(?string $tid): self
    {
        $this->tid = $tid === '' ? null : $tid;
        return $this;
    }

    public function getReceiverMessage(): ?string
    {
        return $this->receiverMessage;
    }

    public function setReceiverMessage(?string $receiverMessage): self
    {
        $this->receiverMessage = $receiverMessage;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getTransactionDate(): ?DateTimeInterface
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(?DateTimeInterface $transactionDate): self
    {
        $this->transactionDate = $transactionDate;
        return $this;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(?string $accountNumber): self
    {
        $this->accountNumber = $accountNumber;
        return $this;
    }

    public function getSourceAccountNumber(): ?string
    {
        return $this->sourceAccountNumber;
    }

    public function setSourceAccountNumber(?string $sourceAccountNumber): self
    {
        $this->sourceAccountNumber = $sourceAccountNumber;
        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function setCid(?string $cid): self
    {
        $this->cid = $cid;
        return $this;
    }

    public function getCid(): ?string
    {
        return $this->cid;
    }

    public function setSign(?string $sign): self
    {
        $this->sign = $sign;
        return $this;
    }

    public function getSign(): ?string
    {
        return $this->sign;
    }

    public function setRes(?string $res): self
    {
        $this->res = $res;
        return $this;
    }

    public function getRes(): ?string
    {
        return $this->res;
    }

    public function setAc(?string $ac): self
    {
        $this->ac = $ac;
        return $this;
    }

    public function getAc(): ?string
    {
        return $this->ac;
    }

    public function setTxn(?string $txn): self
    {
        $this->txn = $txn;
        return $this;
    }

    public function getTxn(): ?string
    {
        return $this->txn;
    }

    public function setRc(?string $rc): self
    {
        $this->rc = $rc;
        return $this;
    }

    public function getRc(): ?string
    {
        return $this->rc;
    }

    public function setTres(?string $tres): self
    {
        $this->tres = $tres;
        return $this;
    }

    public function getTres(): ?string
    {
        return $this->tres;
    }
}
