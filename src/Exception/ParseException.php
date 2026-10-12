<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Exception;

use RuntimeException;

/**
 * Base exception for parsing errors. Invalid input becomes this type, never a bare TypeError.
 */
class ParseException extends RuntimeException {}
