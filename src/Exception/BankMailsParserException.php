<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Exception;

/**
 * Implemented by every exception this package throws, so a caller can catch them all with one type.
 *
 * ParseException (and its subclasses), ConfigurationException and InvalidArgumentException implement it.
 */
interface BankMailsParserException extends \Throwable {}
