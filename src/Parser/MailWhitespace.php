<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Parser;

/**
 * Space, tab, NBSP, thin space and narrow NBSP, as used by HTML-to-text conversion.
 *
 * @internal
 */
final class MailWhitespace
{
    public const string WS = '[ \t\x{00A0}\x{2009}\x{202F}]*';

    public const string WS1 = '[ \t\x{00A0}\x{2009}\x{202F}]+';
}
