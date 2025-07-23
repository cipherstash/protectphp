<?php

declare(strict_types=1);

namespace CipherStash\Protect\Tests\Unit\Exceptions;

use CipherStash\Protect\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class ValidationExceptionTest extends TestCase
{
    public function test_unsupported_value(): void
    {
        $type = 'resource';
        $exception = ValidationException::unsupportedValue($type);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_unsupported_data_type_value(): void
    {
        $value = 'invalid_data_type';
        $exception = ValidationException::unsupportedDataTypeValue($value);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_unsupported_cast_as_value(): void
    {
        $castAs = 'invalid_cast_as';
        $exception = ValidationException::unsupportedCastAsValue($castAs);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_invalid_field_type(): void
    {
        $type = 'array';
        $exception = ValidationException::invalidFieldType($type);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_invalid_field_format(): void
    {
        $field = 'invalid_field';
        $exception = ValidationException::invalidFieldFormat($field);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_invalid_table_type(): void
    {
        $type = 'array';
        $exception = ValidationException::invalidTableType($type);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_invalid_column_type(): void
    {
        $type = 'array';
        $exception = ValidationException::invalidColumnType($type);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_invalid_cast_as_option(): void
    {
        $attribute = 'cast_as';
        $exception = ValidationException::invalidCastAsOption($attribute);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_invalid_indexes_option(): void
    {
        $attribute = 'indexes';
        $exception = ValidationException::invalidIndexesOption($attribute);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_invalid_context_option(): void
    {
        $attribute = 'context';
        $exception = ValidationException::invalidContextOption($attribute);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_invalid_encrypt_option(): void
    {
        $attribute = 'skip';
        $exception = ValidationException::invalidSkipOption($attribute);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_invalid_ciphertext(): void
    {
        $exception = ValidationException::invalidCiphertext();

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_missing_ciphertext(): void
    {
        $exception = ValidationException::missingCiphertext();

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_missing_data_type(): void
    {
        $exception = ValidationException::missingDataType();

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_invalid_data_type(): void
    {
        $exception = ValidationException::invalidDataType();

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_missing_table_identifier(): void
    {
        $exception = ValidationException::missingTableIdentifier();

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_missing_column_identifier(): void
    {
        $exception = ValidationException::missingColumnIdentifier();

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }
}
