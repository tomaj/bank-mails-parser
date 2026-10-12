<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests\Parser;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tomaj\BankMailsParser\Exception\InvalidEmailFormatException;
use Tomaj\BankMailsParser\MailContent;
use Tomaj\BankMailsParser\Parser\ParserInterface;
use Tomaj\BankMailsParser\Parser\ParsesBankMail;

final class ParsesBankMailTest extends TestCase
{
    #[Test]
    public function tryParseDoesNotHideTypeErrors(): void
    {
        $parser = new TraitProbeParser();

        $this->expectException(\TypeError::class);
        $parser->tryParse('type');
    }

    #[Test]
    public function missingCaptureIsAFormatError(): void
    {
        $parser = new TraitProbeParser();

        $this->expectException(InvalidEmailFormatException::class);
        $parser->missingGroup();
    }

    #[Test]
    public function tryParseSwallowsArgumentValueAndDateErrors(): void
    {
        $parser = new TraitProbeParser();

        $this->assertNull($parser->tryParse('invalid-argument'));
        $this->assertNull($parser->tryParse('value-error'));
        $this->assertNull($parser->tryParse('date-malformed'));
    }

    #[Test]
    public function emptyOptionalGroupIsNull(): void
    {
        $parser = new TraitProbeParser();

        $this->assertNull($parser->optional(''));
        $this->assertNull($parser->optional(null));
        $this->assertSame('12', $parser->optional('12'));
    }

    #[Test]
    public function accountNormalizationTrimsAndCollapsesSpaces(): void
    {
        $parser = new TraitProbeParser();

        $this->assertSame('SK12', $parser->account(" \t SK12 \t"));
        $this->assertSame('SK 12', $parser->account("SK\u{00A0}\u{00A0}12"));
    }
}

final class TraitProbeParser implements ParserInterface
{
    use ParsesBankMail;

    #[\Override]
    public function supports(string $content, ?string $sender = null, ?string $subject = null): bool
    {
        return true;
    }

    public function parse(string $content): MailContent
    {
        if ($content === 'type') {
            throw new \TypeError('bad');
        }
        if ($content === 'invalid-argument') {
            throw new \InvalidArgumentException('bad');
        }
        if ($content === 'value-error') {
            throw new \ValueError('bad');
        }
        if ($content === 'date-malformed') {
            throw new \DateMalformedStringException('bad');
        }

        return new MailContent();
    }

    public function missingGroup(): string
    {
        return $this->group([], 0);
    }

    public function optional(mixed $value): ?string
    {
        return $this->optionalGroup(is_string($value) ? [1 => $value] : [], 1);
    }

    public function account(string $value): string
    {
        return $this->normalizeAccount($value);
    }
}
