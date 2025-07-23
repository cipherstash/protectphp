<?php

declare(strict_types=1);

namespace CipherStash\Protect\Exceptions;

use Exception;

/**
 * Exception thrown when validation operations encounter failures.
 */
final class ValidationException extends Exception
{
    /**
     * Create a new exception for when a PHP data type is not supported for encryption.
     */
    public static function unsupportedValue(string $type): self
    {
        return new self("The [{$type}] PHP data type is not supported for encryption.");
    }

    /**
     * Create a new exception for when envelope has unsupported data type value.
     */
    public static function unsupportedDataTypeValue(string $value): self
    {
        return new self("The envelope data type [{$value}] is not supported.");
    }

    /**
     * Create a new exception for when cast_as PHP data type value is not supported.
     */
    public static function unsupportedCastAsValue(string $castAs): self
    {
        return new self("The [{$castAs}] 'cast_as' value is not supported.");
    }

    /**
     * Create a new exception for when a field is not a string.
     */
    public static function invalidFieldType(string $type): self
    {
        return new self("The field must be a string, [{$type}] given.");
    }

    /**
     * Create a new exception for when a field format is invalid.
     */
    public static function invalidFieldFormat(string $field): self
    {
        return new self("The field [{$field}] must use the format [table.column].");
    }

    /**
     * Create a new exception for when a table name is not a string.
     */
    public static function invalidTableType(string $type): self
    {
        return new self("The table name must be a string, [{$type}] given.");
    }

    /**
     * Create a new exception for when a column name is not a string.
     */
    public static function invalidColumnType(string $type): self
    {
        return new self("The column name must be a string, [{$type}] given.");
    }

    /**
     * Create a new exception for when cast_as option is invalid.
     */
    public static function invalidCastAsOption(string $attribute): self
    {
        return new self("The 'cast_as' option for attribute [{$attribute}] must be a string.");
    }

    /**
     * Create a new exception for when indexes option is invalid.
     */
    public static function invalidIndexesOption(string $attribute): self
    {
        return new self("The 'indexes' option for attribute [{$attribute}] must be an array.");
    }

    /**
     * Create a new exception for when context option is invalid.
     */
    public static function invalidContextOption(string $attribute): self
    {
        return new self("The 'context' option for attribute [{$attribute}] must be an array.");
    }

    /**
     * Create a new exception for when skip option is invalid.
     */
    public static function invalidSkipOption(string $attribute): self
    {
        return new self("The 'skip' option for attribute [{$attribute}] must be a boolean.");
    }

    /**
     * Create a new exception for when envelope has invalid ciphertext.
     */
    public static function invalidCiphertext(): self
    {
        return new self('The envelope ciphertext must be a string.');
    }

    /**
     * Create a new exception for when envelope has invalid data type.
     */
    public static function invalidDataType(): self
    {
        return new self('The envelope data type must be a string.');
    }

    /**
     * Create a new exception for when encryption results do not match expected metadata count.
     */
    public static function invalidEncryptResultsCount(): self
    {
        return new self('The encryption result count does not match metadata count.');
    }

    /**
     * Create a new exception for when decryption results do not match expected input count.
     */
    public static function invalidDecryptResultsCount(): self
    {
        return new self('The decryption result count does not match input data count.');
    }

    /**
     * Create a new exception for when search term results do not match expected input count.
     */
    public static function invalidSearchTermResultsCount(): self
    {
        return new self('The search term result count does not match input terms count.');
    }

    /**
     * Create a new exception for when a table name is empty.
     */
    public static function emptyTableName(): self
    {
        return new self('The table name cannot be empty.');
    }

    /**
     * Create a new exception for when a column name is empty.
     */
    public static function emptyColumnName(): self
    {
        return new self('The column name cannot be empty.');
    }

    /**
     * Create a new exception for when envelope is missing ciphertext.
     */
    public static function missingCiphertext(): self
    {
        return new self('The envelope is missing the ciphertext.');
    }

    /**
     * Create a new exception for when envelope is missing data type.
     */
    public static function missingDataType(): self
    {
        return new self('The envelope is missing the data type.');
    }

    /**
     * Create a new exception for when envelope is missing table identifier.
     */
    public static function missingTableIdentifier(): self
    {
        return new self('The envelope is missing the table identifier.');
    }

    /**
     * Create a new exception for when envelope is missing column identifier.
     */
    public static function missingColumnIdentifier(): self
    {
        return new self('The envelope is missing the column identifier.');
    }

    /**
     * Create a new exception for when table validation fails during decryption.
     */
    public static function failedToValidateTable(string $column, string $expectedTable, string $envelopeTable): self
    {
        return new self(
            "The column [{$column}] data was encrypted for table [{$envelopeTable}] but expected table [{$expectedTable}]."
        );
    }
}
