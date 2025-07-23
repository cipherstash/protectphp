<?php

declare(strict_types=1);

namespace CipherStash\Protect\Exceptions;

use Exception;

/**
 * Exception thrown when decryption operations encounter failures.
 */
final class DecryptException extends Exception
{
    /**
     * Create a new exception for decryption failures.
     */
    public static function failedToDecrypt(string $reason): self
    {
        return new self("Decryption failed: [{$reason}].");
    }

    /**
     * Create a new exception for attribute decryption failures.
     */
    public static function failedToDecryptAttributes(string $reason): self
    {
        return new self("Attribute decryption failed: [{$reason}].");
    }
}
