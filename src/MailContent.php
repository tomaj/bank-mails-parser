<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser;

use DateTimeImmutable;
use Tomaj\BankMailsParser\ValueObject\Money;

/**
 * Immutable value object representing parsed transaction data from a bank email.
 *
 * Every string is trimmed. An empty string becomes null.
 */
final readonly class MailContent
{
    public ?Money $money;

    public ?DateTimeImmutable $transactionDate;

    public ?string $accountNumber;

    public ?string $sourceAccountNumber;

    /**
     * The account at the bank that sent the mail (ČSOB SK `na účte`, VÚB `Na účet`).
     * `$accountNumber` stays the payer account, as in 4.x.
     */
    public ?string $ownAccountNumber;

    public ?string $vs;

    public ?string $ss;

    public ?string $ks;

    public ?string $receiverMessage;

    public ?string $description;

    public ?string $cid;

    public ?string $sign;

    public ?string $res;

    public ?string $ac;

    public ?string $cc;

    public ?string $tid;

    public ?string $txn;

    public ?string $rc;

    public ?string $tres;

    public function __construct(
        ?Money $money = null,
        ?DateTimeImmutable $transactionDate = null,
        ?string $accountNumber = null,
        ?string $sourceAccountNumber = null,
        ?string $ownAccountNumber = null,
        ?string $vs = null,
        ?string $ss = null,
        ?string $ks = null,
        ?string $receiverMessage = null,
        ?string $description = null,
        ?string $cid = null,
        ?string $sign = null,
        ?string $res = null,
        ?string $ac = null,
        ?string $cc = null,
        ?string $tid = null,
        ?string $txn = null,
        ?string $rc = null,
        ?string $tres = null,
    ) {
        $this->money = $money;
        $this->transactionDate = $transactionDate;
        $this->accountNumber = $this->normalize($accountNumber);
        $this->sourceAccountNumber = $this->normalize($sourceAccountNumber);
        $this->ownAccountNumber = $this->normalize($ownAccountNumber);
        $this->vs = $this->normalize($vs);
        $this->ss = $this->normalize($ss);
        $this->ks = $this->normalize($ks);
        $this->receiverMessage = $this->normalize($receiverMessage);
        $this->description = $this->normalize($description);
        $this->cid = $this->normalize($cid);
        $this->sign = $this->normalize($sign);
        $this->res = $this->normalize($res);
        $this->ac = $this->normalize($ac);
        $this->cc = $this->normalize($cc);
        $this->tid = $this->normalize($tid);
        $this->txn = $this->normalize($txn);
        $this->rc = $this->normalize($rc);
        $this->tres = $this->normalize($tres);
    }

    private function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_trim($value, " \t\n\r\0\x0B\u{00A0}\u{2009}\u{202F}");

        return $value === '' ? null : $value;
    }
}
