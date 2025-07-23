<?php

declare(strict_types=1);

namespace CipherStash\Protect\Tests\Unit;

use CipherStash\Protect\DataConverter;
use CipherStash\Protect\Enums\DataType;
use CipherStash\Protect\Exceptions\DataConversionException;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use stdClass;

class DataConverterTest extends TestCase
{
    public function test_detect_type_for_text_values(): void
    {
        $textValues = [
            'john@example.com',
            'Software Engineer',
            'Springfield',
            '',
            '742 Evergreen Terrace',
        ];

        foreach ($textValues as $value) {
            $result = DataConverter::detectType($value);
            $this->assertSame(DataType::TEXT, $result);
        }
    }

    public function test_detect_type_for_boolean_values(): void
    {
        $this->assertSame(DataType::BOOLEAN, DataConverter::detectType(true));
        $this->assertSame(DataType::BOOLEAN, DataConverter::detectType(false));
    }

    public function test_detect_type_for_integer_values(): void
    {
        $this->assertSame(DataType::SMALL_INT, DataConverter::detectType(29));
        $this->assertSame(DataType::SMALL_INT, DataConverter::detectType(12345));
        $this->assertSame(DataType::INT, DataConverter::detectType(100000));
        $this->assertSame(DataType::BIG_INT, DataConverter::detectType(3000000000));
    }

    public function test_detect_type_for_float_values(): void
    {
        $this->assertSame(DataType::REAL, DataConverter::detectType(99.99));
        $this->assertSame(DataType::DOUBLE, DataConverter::detectType(3.14159265359));
    }

    public function test_detect_type_for_date_values(): void
    {
        $this->assertSame(DataType::DATE, DataConverter::detectType(new DateTime));
        $this->assertSame(DataType::DATE, DataConverter::detectType(new DateTimeImmutable));
    }

    public function test_detect_type_for_jsonb_values(): void
    {
        $jsonbValues = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['city' => 'Boston', 'state' => 'MA'],
            new stdClass,
            [],
        ];

        foreach ($jsonbValues as $value) {
            $result = DataConverter::detectType($value);
            $this->assertSame(DataType::JSONB, $result);
        }
    }

    public function test_detect_type_returns_null_for_unsupported_type(): void
    {
        $this->assertNull(DataConverter::detectType(null));
    }

    public function test_to_string_converts_text(): void
    {
        $values = [
            'john@example.com',
            'Software Engineer',
            'Springfield',
            '',
        ];

        foreach ($values as $value) {
            $result = DataConverter::toString($value, 'text');
            $this->assertSame($value, $result);
        }
    }

    public function test_to_string_converts_boolean(): void
    {
        $this->assertSame('true', DataConverter::toString(true, 'boolean'));
        $this->assertSame('false', DataConverter::toString(false, 'boolean'));
    }

    public function test_to_string_converts_integers(): void
    {
        $testCases = [
            [-1, 'small_int', '-1'],
            [0, 'small_int', '0'],
            [29, 'small_int', '29'],
            [12345, 'int', '12345'],
            [3000000000, 'big_int', '3000000000'],
        ];

        foreach ($testCases as [$value, $type, $expected]) {
            $result = DataConverter::toString($value, $type);
            $this->assertSame($expected, $result);
        }
    }

    public function test_to_string_converts_floats(): void
    {
        $testCases = [
            [99.99, 'real', '99.99'],
            [1.5, 'real', '1.5'],
            [3.14159265359, 'double', '3.14159265359'],
        ];

        foreach ($testCases as [$value, $type, $expected]) {
            $result = DataConverter::toString($value, $type);
            $this->assertSame($expected, $result);
        }
    }

    public function test_to_string_converts_date(): void
    {
        $date = new DateTime('2023-01-01 12:30:45.123456');
        $result = DataConverter::toString($date, 'date');

        $this->assertStringContainsString('2023-01-01', $result);
        $this->assertStringContainsString('12:30:45', $result);
    }

    public function test_to_string_converts_jsonb(): void
    {
        $testCases = [
            [['name' => 'John Doe'], '{"name":"John Doe"}'],
            [['city' => 'Boston', 'state' => 'MA'], '{"city":"Boston","state":"MA"}'],
            [[], '[]'],
        ];

        foreach ($testCases as [$value, $expected]) {
            $result = DataConverter::toString($value, 'jsonb');
            $this->assertSame($expected, $result);
        }
    }

    public function test_to_string_throws_for_invalid_cast_as(): void
    {
        $this->expectException(DataConversionException::class);
        DataConverter::toString('test', 'invalid_type');
    }

    public function test_from_string_converts_text(): void
    {
        $values = [
            'john@example.com',
            'Software Engineer',
            'Springfield',
            '',
        ];

        foreach ($values as $value) {
            $result = DataConverter::fromString($value, 'text');
            $this->assertSame($value, $result);
        }
    }

    public function test_from_string_converts_boolean(): void
    {
        $this->assertTrue(DataConverter::fromString('true', 'boolean'));
        $this->assertFalse(DataConverter::fromString('false', 'boolean'));
        $this->assertFalse(DataConverter::fromString('anything_else', 'boolean'));
    }

    public function test_from_string_converts_integers(): void
    {
        $testCases = [
            ['-1', 'small_int', -1],
            ['0', 'small_int', 0],
            ['29', 'small_int', 29],
            ['12345', 'int', 12345],
            ['3000000000', 'big_int', 3000000000],
        ];

        foreach ($testCases as [$value, $type, $expected]) {
            $result = DataConverter::fromString($value, $type);
            $this->assertSame($expected, $result);
        }
    }

    public function test_from_string_converts_floats(): void
    {
        $testCases = [
            ['99.99', 'real', 99.99],
            ['1.5', 'real', 1.5],
            ['3.14159265359', 'double', 3.14159265359],
        ];

        foreach ($testCases as [$value, $type, $expected]) {
            $result = DataConverter::fromString($value, $type);
            $this->assertEqualsWithDelta($expected, $result, 0.00000000001);
        }
    }

    public function test_from_string_converts_date(): void
    {
        $dateString = '2023-01-01T12:30:45+00:00';
        $result = DataConverter::fromString($dateString, 'date');

        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertSame('2023-01-01', $result->format('Y-m-d'));
        $this->assertSame('12:30:45', $result->format('H:i:s'));
    }

    public function test_from_string_converts_jsonb(): void
    {
        $testCases = [
            ['{"name":"John Doe"}', ['name' => 'John Doe']],
            ['{"city":"Boston","state":"MA"}', ['city' => 'Boston', 'state' => 'MA']],
            ['[]', []],
        ];

        foreach ($testCases as [$value, $expected]) {
            $result = DataConverter::fromString($value, 'jsonb');
            $this->assertSame($expected, $result);
        }
    }

    public function test_from_string_throws_for_invalid_cast_as(): void
    {
        $this->expectException(DataConversionException::class);
        DataConverter::fromString('test', 'invalid_type');
    }

    public function test_to_json_converts_arrays(): void
    {
        $testCases = [
            [['name' => 'John Doe'], '{"name":"John Doe"}'],
            [['city' => 'Boston', 'state' => 'MA'], '{"city":"Boston","state":"MA"}'],
            [[], '[]'],
            [[29, 30, 31], '[29,30,31]'],
        ];

        foreach ($testCases as [$value, $expected]) {
            $result = DataConverter::toJson($value);
            $this->assertSame($expected, $result);
        }
    }

    public function test_to_json_converts_objects(): void
    {
        $obj = new stdClass;
        $obj->name = 'John Doe';
        $obj->age = 29;

        $result = DataConverter::toJson($obj);
        $this->assertSame('{"name":"John Doe","age":29}', $result);
    }

    public function test_to_json_throws_for_invalid_types(): void
    {
        $this->expectException(DataConversionException::class);
        DataConverter::toJson('string');
    }

    public function test_from_json_decodes_valid_json(): void
    {
        $testCases = [
            ['{"name":"John Doe"}', ['name' => 'John Doe']],
            ['{"city":"Boston","state":"MA"}', ['city' => 'Boston', 'state' => 'MA']],
            ['[]', []],
            ['[29,30,31]', [29, 30, 31]],
        ];

        foreach ($testCases as [$value, $expected]) {
            $result = DataConverter::fromJson($value);
            $this->assertSame($expected, $result);
        }
    }

    public function test_from_json_throws_for_invalid_json(): void
    {
        $this->expectException(DataConversionException::class);
        DataConverter::fromJson('invalid json');
    }

    public function test_from_json_throws_for_non_array_result(): void
    {
        $this->expectException(DataConversionException::class);
        DataConverter::fromJson('"primitive value"');
    }

    public function test_bidirectional_conversion_for_text(): void
    {
        $originalValue = 'john.doe@example.com';
        $stringValue = DataConverter::toString($originalValue, 'text');
        $convertedBack = DataConverter::fromString($stringValue, 'text');

        $this->assertSame($originalValue, $convertedBack);
    }

    public function test_bidirectional_conversion_for_boolean(): void
    {
        foreach ([true, false] as $originalValue) {
            $stringValue = DataConverter::toString($originalValue, 'boolean');
            $convertedBack = DataConverter::fromString($stringValue, 'boolean');

            $this->assertSame($originalValue, $convertedBack);
        }
    }

    public function test_bidirectional_conversion_for_integers(): void
    {
        $testValues = [-1, 0, 1, 12345, 3000000000];

        foreach ($testValues as $originalValue) {
            $type = DataConverter::detectType($originalValue);
            $this->assertNotNull($type);
            $stringValue = DataConverter::toString($originalValue, $type->value);
            $convertedBack = DataConverter::fromString($stringValue, $type->value);

            $this->assertSame($originalValue, $convertedBack);
        }
    }

    public function test_bidirectional_conversion_for_floats(): void
    {
        $testValues = [99.99, 1.5, 3.14159265359];

        foreach ($testValues as $originalValue) {
            $type = DataConverter::detectType($originalValue);
            $this->assertNotNull($type);
            $stringValue = DataConverter::toString($originalValue, $type->value);
            $convertedBack = DataConverter::fromString($stringValue, $type->value);

            $this->assertEqualsWithDelta($originalValue, $convertedBack, 0.00000000001);
        }
    }

    public function test_bidirectional_conversion_for_jsonb(): void
    {
        $testValues = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['permissions' => ['read', 'write', 'admin']],
            [],
            ['id' => 1, 'active' => true, 'meta' => ['created' => '2023-01-01']],
        ];

        foreach ($testValues as $originalValue) {
            $stringValue = DataConverter::toString($originalValue, 'jsonb');
            $convertedBack = DataConverter::fromString($stringValue, 'jsonb');

            $this->assertSame($originalValue, $convertedBack);
        }
    }
}
