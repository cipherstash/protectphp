<?php

declare(strict_types=1);

namespace CipherStash\Protect\Tests\Unit\Exceptions;

use CipherStash\Protect\Exceptions\DataConversionException;
use PHPUnit\Framework\TestCase;

class DataConversionExceptionTest extends TestCase
{
    public function test_failed_to_convert_data(): void
    {
        $reason = 'Invalid data format';
        $exception = DataConversionException::failedToConvertData($reason);

        $this->assertInstanceOf(DataConversionException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_failed_to_encode_json(): void
    {
        $reason = 'Circular reference detected';
        $exception = DataConversionException::failedToEncodeJson($reason);

        $this->assertInstanceOf(DataConversionException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_failed_to_decode_json_string(): void
    {
        $reason = 'Syntax error in JSON';
        $exception = DataConversionException::failedToDecodeJsonString($reason);

        $this->assertInstanceOf(DataConversionException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_failed_to_decode_json_array(): void
    {
        $exception = DataConversionException::failedToDecodeJsonArray();

        $this->assertInstanceOf(DataConversionException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_invalid_type_for_json_conversion(): void
    {
        $type = 'resource';
        $exception = DataConversionException::invalidTypeForJsonConversion($type);

        $this->assertInstanceOf(DataConversionException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_invalid_cast_as_data_type(): void
    {
        $castAs = 'invalid_type';
        $exception = DataConversionException::invalidCastAsDataType($castAs);

        $this->assertInstanceOf(DataConversionException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }
}
