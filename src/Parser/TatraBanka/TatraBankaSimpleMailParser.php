<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;

final readonly class TatraBankaSimpleMailParser implements ParserInterface
{
    private const array FIELD_MAP = [
        'VS' => 'vs',
        'RES' => 'res',
        'AC' => 'ac',
        'SIGN' => 'sign',
        'TRES' => 'res',
        'CID' => 'cid',
        'AMT' => 'amount',
        'CURR' => 'currency',
        'CC' => 'cc',
        'TID' => 'tid',
        'TIMESTAMP' => 'transactionDate',
        'TXN' => 'txn',
        'RC' => 'rc',
        'HMAC' => 'sign',
    ];

    #[\Override]
    public function parse(string $content): ?MailContent
    {
        if ($content === '') {
            return null;
        }

        $data = [];
        foreach (explode(' ', $content) as $part) {
            $exploded = explode('=', $part);
            if (count($exploded) !== 2) {
                continue;
            }
            [$key, $value] = array_map(mb_trim(...), $exploded);

            if (!isset(self::FIELD_MAP[$key])) {
                continue;
            }

            $property = self::FIELD_MAP[$key];
            $data[$property] = match ($property) {
                'amount' => (float) $value,
                'transactionDate' => (int) $value,
                default => $value,
            };
        }

        if (!isset($data['res'])) {
            return null;
        }

        if (!isset($data['transactionDate'])) {
            $data['transactionDate'] = time();
        }

        return new MailContent(
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            currency: isset($data['currency']) ? (string) $data['currency'] : null,
            transactionDate: (int) $data['transactionDate'],
            vs: isset($data['vs']) ? (string) $data['vs'] : null,
            cid: isset($data['cid']) ? (string) $data['cid'] : null,
            sign: isset($data['sign']) ? (string) $data['sign'] : null,
            res: (string) $data['res'],
            ac: isset($data['ac']) ? (string) $data['ac'] : null,
            cc: isset($data['cc']) ? (string) $data['cc'] : null,
            tid: isset($data['tid']) ? (string) $data['tid'] : null,
            txn: isset($data['txn']) ? (string) $data['txn'] : null,
            rc: isset($data['rc']) ? (string) $data['rc'] : null,
        );
    }
}
