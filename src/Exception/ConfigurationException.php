<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Exception;

use RuntimeException;

/**
 * The parser is not configured. tryParse() does not swallow this.
 */
final class ConfigurationException extends RuntimeException {}
