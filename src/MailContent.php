<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser;

/**
 * Immutable value object representing parsed transaction data from a bank email.
 *
 * All properties are readonly and must be provided via constructor.
 * Empty strings are automatically normalized to null.
 */
final readonly class MailContent
{
    public function __construct(
        public ?float $amount = null,
        public ?string $currency = null,
        public int|false|null $transactionDate = null,
        public ?string $accountNumber = null,
        public ?string $sourceAccountNumber = null,
        ?string $vs = null,
        ?string $ss = null,
        ?string $ks = null,
        public ?string $receiverMessage = null,
        public ?string $description = null,
        ?string $cid = null,
        ?string $sign = null,
        ?string $res = null,
        ?string $ac = null,
        ?string $cc = null,
        ?string $tid = null,
        ?string $txn = null,
        ?string $rc = null,
    ) {
        $this->vs = self::normalizeEmptyString($vs);
        $this->ss = self::normalizeEmptyString($ss);
        $this->ks = self::normalizeEmptyString($ks);
        $this->cid = self::normalizeEmptyString($cid);
        $this->sign = self::normalizeEmptyString($sign);
        $this->res = self::normalizeEmptyString($res);
        $this->ac = self::normalizeEmptyString($ac);
        $this->cc = self::normalizeEmptyString($cc);
        $this->tid = self::normalizeEmptyString($tid);
        $this->txn = self::normalizeEmptyString($txn);
        $this->rc = self::normalizeEmptyString($rc);
    }

    public ?string $vs;
    public ?string $ss;
    public ?string $ks;
    public ?string $cid;
    public ?string $sign;
    public ?string $res;
    public ?string $ac;
    public ?string $cc;
    public ?string $tid;
    public ?string $txn;
    public ?string $rc;

    private static function normalizeEmptyString(?string $value): ?string
    {
        return $value === '' ? null : $value;
    }
}
