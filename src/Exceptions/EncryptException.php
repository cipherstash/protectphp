<?php

declare(strict_types=1);

namespace CipherStash\Protect\Exceptions;

use Exception;

/**
 * Exception thrown when encryption operations encounter failures.
 */
final class EncryptException extends Exception
{
    /**
     * Create a new exception for encryption failures.
     */
    public static function failedToEncrypt(string $reason): self
    {
        return new self("Encryption failed: [{$reason}].");
    }

    /**
     * Create a new exception for attribute encryption failures.
     */
    public static function failedToEncryptAttributes(string $reason): self
    {
        return new self("Attribute encryption failed: [{$reason}].");
    }
}
