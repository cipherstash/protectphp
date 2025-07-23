<?php

declare(strict_types=1);

namespace CipherStash\Protect\Exceptions;

use Exception;

/**
 * Exception thrown when data conversion operations encounter failures.
 */
final class DataConversionException extends Exception
{
    /**
     * Create a new exception for PHP data type conversion failures.
     */
    public static function failedToConvertData(string $reason): self
    {
        return new self("Data conversion failed: [{$reason}].");
    }

    /**
     * Create a new exception for JSON encoding failures.
     */
    public static function failedToEncodeJson(string $reason): self
    {
        return new self("JSON encoding failed: [{$reason}].");
    }

    /**
     * Create a new exception for JSON decoding failures.
     */
    public static function failedToDecodeJsonString(string $reason): self
    {
        return new self("JSON decoding failed: [{$reason}].");
    }

    /**
     * Create a new exception for when JSON decode doesn't return expected array.
     */
    public static function failedToDecodeJsonArray(): self
    {
        return new self('The JSON must decode to an array, not a primitive value.');
    }

    /**
     * Create a new exception for invalid types in JSON conversion.
     */
    public static function invalidTypeForJsonConversion(string $type): self
    {
        return new self(
            "The [{$type}] type cannot be converted to JSON. Only arrays and objects are supported."
        );
    }

    /**
     * Create a new exception for when cast_as PHP data type is invalid.
     */
    public static function invalidCastAsDataType(string $castAs): self
    {
        return new self("The 'cast_as' PHP data type [{$castAs}] is not supported.");
    }
}
