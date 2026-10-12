<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use RuntimeException;
use Throwable;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\Csob\CsobMailParser;
use Tomaj\BankMailsParser\Parser\Csob\SkCsobMailParser;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaSimpleMailParser;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaStatementMailParser;
use Tomaj\BankMailsParser\Parser\Vub\VubMailParser;

final class FixtureCatalog
{
    /**
     * @return array<string, mixed>
     */
    public static function expected(string $root, string $relativeTxt): array
    {
        $body = (string) file_get_contents($root . '/' . $relativeTxt);
        $bank = explode('/', str_replace('\\', '/', $relativeTxt))[2] ?? '';

        return [
            'sha256' => hash('sha256', $body),
            'result' => self::parse($root, $bank, $body),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function parse(string $root, string $bank, string $body): array
    {
        if ($bank === 'tatrabanka-statement' && str_contains($body, 'BEGIN PGP MESSAGE')) {
            $parser = new TatraBankaStatementMailParser(new TatraBankaMailDecryptor(
                $root . '/tests/fixtures/tatrabanka-statement/private-key.asc',
                'heslo',
            ));

            return self::rows('tatrabanka-statement', $parser, $body);
        }

        if ($bank === 'tatrabanka-statement' && str_contains($body, '|')) {
            $parser = new TatraBankaStatementMailParser();
            $lines = preg_split('/\n/', $body) ?: [];
            $transactions = [];
            foreach (array_slice($lines, 1) as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $transactions[] = self::mail($parser->parse($line));
            }

            return ['parser' => 'tatrabanka-statement', 'transactions' => $transactions];
        }

        $parser = match ($bank) {
            'csob-cz' => new CsobMailParser(),
            'csob-sk' => new SkCsobMailParser(),
            'tatrabanka' => new TatraBankaMailParser(),
            'tatrabanka-cardpay' => new TatraBankaSimpleMailParser(),
            'vub' => new VubMailParser(),
            default => throw new RuntimeException('No parser for fixture bank ' . $bank),
        };

        return self::rows($bank, $parser, $body);
    }

    /**
     * @return array<string, mixed>
     */
    private static function rows(string $name, ParserInterface $parser, string $body): array
    {
        try {
            $transactions = array_map(self::mail(...), $parser->parseAll($body));
        } catch (Throwable $exception) {
            return [
                'parser' => $name,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ];
        }

        return ['parser' => $name, 'transactions' => $transactions];
    }

    /**
     * @return array<string, mixed>
     */
    private static function mail(MailContent $mail): array
    {
        return [
            'amount' => $mail->money?->amount,
            'currency' => $mail->money?->currency->value,
            'decimal' => $mail->money?->toDecimalString(),
            'date' => $mail->transactionDate?->format('Y-m-d H:i:s P'),
            'accountNumber' => $mail->accountNumber,
            'sourceAccountNumber' => $mail->sourceAccountNumber,
            'ownAccountNumber' => $mail->ownAccountNumber,
            'vs' => $mail->vs,
            'ss' => $mail->ss,
            'ks' => $mail->ks,
            'receiverMessage' => $mail->receiverMessage,
            'description' => $mail->description,
            'cid' => $mail->cid,
            'sign' => $mail->sign,
            'res' => $mail->res,
            'ac' => $mail->ac,
            'cc' => $mail->cc,
            'tid' => $mail->tid,
            'txn' => $mail->txn,
            'rc' => $mail->rc,
            'tres' => $mail->tres,
        ];
    }
}
