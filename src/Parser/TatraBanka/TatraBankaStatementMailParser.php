<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser\TatraBanka;

use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\DecryptionException;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\BankDate;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

final readonly class TatraBankaStatementMailParser implements ParserInterface
{
    use ParsesBankMail;

    public function __construct(
        private ?TatraBankaMailDecryptor $decryptor = null,
    ) {}

    /**
     * @return list<string>
     */
    #[\Override]
    public function bankDomains(): array
    {
        return ['tatrabanka.sk'];
    }

    #[\Override]
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return $this->supportsQuietly(
            fn(): bool => $this->matched('/^-----BEGIN PGP MESSAGE-----/m', $this->withoutFreeText($this->withoutByteOrderMark($content))),
        );
    }

    /**
     * @return list<MailContent>
     */
    public function parseMulti(string $content): array
    {
        return $this->parseAll($content);
    }

    public function parse(string $content): MailContent
    {
        return $this->parseAll($content)[0];
    }

    #[\Override]
    public function parseAll(string $content): array
    {
        $content = $this->scrubUtf8($this->requireUniformLineBreaks($content));
        if (str_contains($content, 'BEGIN PGP MESSAGE')) {
            return $this->decryptRows($content);
        }

        return [$this->parseRow($content)];
    }

    /**
     * @return list<MailContent>
     */
    private function decryptRows(string $content): array
    {
        if (!$this->decryptor instanceof TatraBankaMailDecryptor) {
            throw new ConfigurationException('PGP decryptor is not configured.');
        }

        if (!$this->matched('/(-{5}BEGIN[A-Za-z0-9 \-\r\n+\/=]+END PGP MESSAGE-{5})/m', $content, $results)) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Encrypted statement could not be read');
        }

        try {
            $decrypted = $this->decryptor->decrypt($this->group($results, 0));
        } catch (ConfigurationException $exception) {
            throw $exception;
        } catch (DecryptionException $exception) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Encrypted statement could not be read', $exception);
        }

        $split = preg_split("/\r\n|\n|\r/", $decrypted);
        if ($split === false) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Encrypted statement could not be read');
        }

        $parsed = [];
        $errors = [];
        $rowNumber = 0;
        foreach (array_slice($split, 1) as $transaction) {
            if (trim($transaction) === '') {
                continue;
            }
            ++$rowNumber;
            try {
                $parsed[] = $this->parseRow($transaction);
            } catch (InvalidEmailFormatException $exception) {
                $errors[] = sprintf('row %d: %s', $rowNumber, $exception->getMessage());
            }
        }

        if ($errors !== []) {
            throw InvalidEmailFormatException::malformedContent(
                self::class,
                'Could not parse every transaction: ' . implode('; ', $errors),
            );
        }

        if ($parsed === []) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Encrypted statement could not be read');
        }

        return $parsed;
    }

    private function parseRow(string $content): MailContent
    {
        $cols = array_filter(explode('|', $content), static fn(string $value): bool => $value !== '' && $value !== '0');
        if ($cols === []) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'No valid columns found');
        }

        if (!isset($cols[9], $cols[10])) {
            throw InvalidEmailFormatException::missingRequiredField(self::class, 'amount/currency');
        }

        $money = $this->money($cols[9], trim($cols[10]), '.');
        $transactionDate = isset($cols[0]) ? BankDate::parse($cols[0], '!Ymd', BankDate::bratislava()) : null;

        return new MailContent(
            money: $money,
            transactionDate: $transactionDate,
            vs: $cols[18] ?? null,
            // Column 19 is the statement header "Číslo účtu": the merchant account
            // credited for this card sale. It can differ on every row.
            accountNumber: isset($cols[19]) ? $this->normalizeAccount($cols[19]) : null,
        );
    }
}
