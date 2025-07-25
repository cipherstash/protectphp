<?php

declare(strict_types=1);

namespace CipherStash\Protect\Tests\Unit\Enums;

use CipherStash\Protect\Enums\DataType;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use stdClass;

class DataTypeTest extends TestCase
{
    public function test_enum_cases_have_correct_values(): void
    {
        $this->assertSame('text', DataType::TEXT->value);
        $this->assertSame('boolean', DataType::BOOLEAN->value);
        $this->assertSame('small_int', DataType::SMALL_INT->value);
        $this->assertSame('int', DataType::INT->value);
        $this->assertSame('big_int', DataType::BIG_INT->value);
        $this->assertSame('real', DataType::REAL->value);
        $this->assertSame('double', DataType::DOUBLE->value);
        $this->assertSame('date', DataType::DATE->value);
        $this->assertSame('jsonb', DataType::JSONB->value);
    }

    public function test_from_value_detects_string_data(): void
    {
        $testValues = [
            'john.doe@example.com',
            'John Doe',
            'Administrator',
            '',
            'user123',
        ];

        foreach ($testValues as $value) {
            $result = DataType::fromValue($value);
            $this->assertSame(DataType::TEXT, $result);
        }
    }

    public function test_from_value_detects_boolean_data(): void
    {
        $this->assertSame(DataType::BOOLEAN, DataType::fromValue(true));
        $this->assertSame(DataType::BOOLEAN, DataType::fromValue(false));
    }

    public function test_from_value_detects_integer_data(): void
    {
        $this->assertSame(DataType::SMALL_INT, DataType::fromValue(1));
        $this->assertSame(DataType::SMALL_INT, DataType::fromValue(12345));
        $this->assertSame(DataType::SMALL_INT, DataType::fromValue(0));
        $this->assertSame(DataType::SMALL_INT, DataType::fromValue(-1));

        $this->assertSame(DataType::INT, DataType::fromValue(100000));
        $this->assertSame(DataType::INT, DataType::fromValue(2000000000));
        $this->assertSame(DataType::INT, DataType::fromValue(-50000));

        $this->assertSame(DataType::BIG_INT, DataType::fromValue(3000000000));
    }

    public function test_from_value_detects_float_data(): void
    {
        $this->assertSame(DataType::REAL, DataType::fromValue(1.5));
        $this->assertSame(DataType::REAL, DataType::fromValue(99.99));
        $this->assertSame(DataType::REAL, DataType::fromValue(1234567.89));

        $this->assertSame(DataType::DOUBLE, DataType::fromValue(3.14159265359));
        $this->assertSame(DataType::DOUBLE, DataType::fromValue(1.23456789012345));
    }

    public function test_from_value_detects_date_data(): void
    {
        $this->assertSame(DataType::DATE, DataType::fromValue(new DateTime));
        $this->assertSame(DataType::DATE, DataType::fromValue(new DateTimeImmutable));
    }

    public function test_from_value_detects_jsonb_data(): void
    {
        $testValues = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['permissions' => ['read', 'write', 'admin']],
            new stdClass,
            [],
        ];

        foreach ($testValues as $value) {
            $result = DataType::fromValue($value);
            $this->assertSame(DataType::JSONB, $result);
        }
    }

    public function test_from_value_returns_null_for_unsupported_value(): void
    {
        $this->assertNull(DataType::fromValue(null));
    }

    public function test_from_integer_selects_correct_type(): void
    {
        $this->assertSame(DataType::SMALL_INT, DataType::fromInteger(-32768));
        $this->assertSame(DataType::SMALL_INT, DataType::fromInteger(32767));
        $this->assertSame(DataType::SMALL_INT, DataType::fromInteger(0));

        $this->assertSame(DataType::INT, DataType::fromInteger(-32769));
        $this->assertSame(DataType::INT, DataType::fromInteger(32768));
        $this->assertSame(DataType::INT, DataType::fromInteger(-2147483648));
        $this->assertSame(DataType::INT, DataType::fromInteger(2147483647));

        $this->assertSame(DataType::BIG_INT, DataType::fromInteger(-2147483649));
        $this->assertSame(DataType::BIG_INT, DataType::fromInteger(2147483648));
        $this->assertSame(DataType::BIG_INT, DataType::fromInteger(9223372036854775807));
    }

    public function test_from_float_selects_correct_type(): void
    {
        $this->assertSame(DataType::REAL, DataType::fromFloat(0.0));
        $this->assertSame(DataType::REAL, DataType::fromFloat(1.5));
        $this->assertSame(DataType::REAL, DataType::fromFloat(123.456));
        $this->assertSame(DataType::REAL, DataType::fromFloat(1.2345678));

        $this->assertSame(DataType::DOUBLE, DataType::fromFloat(1.23456789));
        $this->assertSame(DataType::DOUBLE, DataType::fromFloat(3.141592653589793));
        $this->assertSame(DataType::DOUBLE, DataType::fromFloat(3.5e38));
        $this->assertSame(DataType::DOUBLE, DataType::fromFloat(1e-40));
    }

    public function test_integer_boundary_values(): void
    {
        $this->assertSame(DataType::SMALL_INT, DataType::fromInteger(-32768));
        $this->assertSame(DataType::SMALL_INT, DataType::fromInteger(32767));
        $this->assertSame(DataType::INT, DataType::fromInteger(-32769));
        $this->assertSame(DataType::INT, DataType::fromInteger(32768));

        $this->assertSame(DataType::INT, DataType::fromInteger(-2147483648));
        $this->assertSame(DataType::INT, DataType::fromInteger(2147483647));
        $this->assertSame(DataType::BIG_INT, DataType::fromInteger(-2147483649));
        $this->assertSame(DataType::BIG_INT, DataType::fromInteger(2147483648));
    }

    public function test_special_float_values(): void
    {
        $this->assertSame(DataType::REAL, DataType::fromFloat(0.0));
        $this->assertSame(DataType::REAL, DataType::fromFloat(-0.0));

        $this->assertSame(DataType::DOUBLE, DataType::fromFloat(3.5e38));
        $this->assertSame(DataType::DOUBLE, DataType::fromFloat(-3.5e38));

        $this->assertSame(DataType::DOUBLE, DataType::fromFloat(1.0e-39));
        $this->assertSame(DataType::DOUBLE, DataType::fromFloat(-1.0e-39));
    }
}
