<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser;

use Tomaj\BankMailsParser\Exception\AmbiguousBankException;
use Tomaj\BankMailsParser\Exception\ConfigurationException;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\Exception\MultipleTransactionsException;
use Tomaj\BankMailsParser\Exception\ParseException;
use Tomaj\BankMailsParser\Exception\UnsupportedBankException;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\SenderHost;
use Tomaj\BankMailsParser\Parser\TatraBanka\TatraBankaMailDecryptor;

/**
 * Facade that selects a parser from the email body.
 *
 * When a sender is present, only parsers of that bank are considered. If more than one
 * of those parsers accepts the body, parsing stops with AmbiguousBankException.
 * A parser that throws InvalidEmailFormatException is recorded and not replaced by a later parser.
 * Any other parse error, including ConfigurationException, stops the walk.
 */
final class BankMailsParser
{
    /**
     * @var list<ParserInterface>
     */
    private array $parsers = [];

    /**
     * @param list<ParserInterface>|null $parsers Parsers to use. Null registers the built-in parsers.
     *        The statement parser is included only when $decryptor is set.
     */
    public function __construct(
        ?array $parsers = null,
        private readonly ?TatraBankaMailDecryptor $decryptor = null,
    ) {
        if ($parsers === null) {
            $this->registerDefaultParsers();

            return;
        }

        foreach ($parsers as $parser) {
            $this->registerParser($parser);
        }
    }

    public function registerParser(ParserInterface $parser): void
    {
        $this->parsers[] = $parser;
    }

    public function prependParser(ParserInterface $parser): void
    {
        array_unshift($this->parsers, $parser);
    }

    /**
     * @return list<MailContent>
     */
    public function parseAll(string $content, ?string $sender = null, ?string $subject = null): array
    {
        $matched = [];
        foreach ($this->parsers as $parser) {
            if (!$this->parserAcceptsSender($parser, $sender)) {
                continue;
            }
            if ($parser->supports($content, $sender, $subject)) {
                $matched[] = $parser;
            }
        }

        if (count($matched) > 1) {
            throw AmbiguousBankException::forParsers(...array_map(static fn(ParserInterface $parser): string => $parser::class, $matched));
        }

        if ($matched === []) {
            throw UnsupportedBankException::withSender($sender);
        }

        return $matched[0]->parseAll($content);
    }

    private function parserAcceptsSender(ParserInterface $parser, ?string $sender): bool
    {
        if ($sender === null || trim($sender) === '') {
            return true;
        }

        $domains = $parser->bankDomains();
        if ($domains === []) {
            return false;
        }

        foreach ($domains as $domain) {
            if (SenderHost::matches($sender, $domain)) {
                return true;
            }
        }

        return false;
    }

    public function parse(string $content, ?string $sender = null, ?string $subject = null): MailContent
    {
        $parsed = $this->parseAll($content, $sender, $subject);
        if ($parsed === []) {
            throw InvalidEmailFormatException::malformedContent(self::class, 'Parser returned no transactions');
        }
        if (count($parsed) > 1) {
            throw MultipleTransactionsException::useParseAll(count($parsed));
        }

        return $parsed[0];
    }

    public function tryParse(string $content, ?string $sender = null, ?string $subject = null): ?MailContent
    {
        try {
            return $this->parse($content, $sender, $subject);
        } catch (ConfigurationException $exception) {
            throw $exception;
        } catch (ParseException|\InvalidArgumentException|\ValueError|\DateMalformedStringException) {
            return null;
        }
    }

    /**
     * @return list<ParserInterface>
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

    private function registerDefaultParsers(): void
    {
        $this->registerParser(new Parser\TatraBanka\TatraBankaMailParser());
        $this->registerParser(new Parser\TatraBanka\TatraBankaSimpleMailParser());
        if ($this->decryptor instanceof TatraBankaMailDecryptor) {
            $this->registerParser(new Parser\TatraBanka\TatraBankaStatementMailParser($this->decryptor));
        }
        $this->registerParser(new Parser\Csob\CsobMailParser());
        $this->registerParser(new Parser\Csob\SkCsobMailParser());
        $this->registerParser(new Parser\Vub\VubMailParser());
    }
}
