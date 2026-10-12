<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Exception;

/**
 * PGP decryption failed because of the key, the passphrase, or the message.
 */
final class DecryptionException extends ParseException {}
