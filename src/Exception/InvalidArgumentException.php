<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Exception;

/**
 * An invalid amount, decimal separator or currency code passed to Money or Currency.
 *
 * It extends the SPL \InvalidArgumentException, so existing catch blocks for that type still work.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements BankMailsParserException {}
