<?php

declare(strict_types=1);

namespace CipherStash\Protect\Exceptions;

use Exception;

/**
 * Exception thrown when search term operations encounter failures.
 */
final class SearchTermException extends Exception
{
    /**
     * Create a new exception for when search term creation fails.
     */
    public static function failedToCreateSearchTerms(string $reason): self
    {
        return new self("Search term creation failed: [{$reason}].");
    }
}
