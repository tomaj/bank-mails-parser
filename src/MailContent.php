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
        $this->accountNumber = self::normalize($accountNumber);
        $this->sourceAccountNumber = self::normalize($sourceAccountNumber);
        $this->ownAccountNumber = self::normalize($ownAccountNumber);
        $this->vs = self::normalize($vs);
        $this->ss = self::normalize($ss);
        $this->ks = self::normalize($ks);
        $this->receiverMessage = self::normalize($receiverMessage);
        $this->description = self::normalize($description);
        $this->cid = self::normalize($cid);
        $this->sign = self::normalize($sign);
        $this->res = self::normalize($res);
        $this->ac = self::normalize($ac);
        $this->cc = self::normalize($cc);
        $this->tid = self::normalize($tid);
        $this->txn = self::normalize($txn);
        $this->rc = self::normalize($rc);
        $this->tres = self::normalize($tres);
    }

    private static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_trim($value, " \t\n\r\0\x0B\u{00A0}\u{2009}\u{202F}");

        return $value === '' ? null : $value;
    }
}
