<?php

declare(strict_types=1);

namespace CipherStash\Protect\Tests\Unit;

use CipherStash\Protect\Enums\DataType;
use CipherStash\Protect\Exceptions\ValidationException;
use CipherStash\Protect\Protect;
use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class ProtectTest extends TestCase
{
    public function test_validate_value_accepts_supported_types(): void
    {
        $testCases = [
            ['john@example.com', DataType::TEXT],
            [true, DataType::BOOLEAN],
            [false, DataType::BOOLEAN],
            [29, DataType::SMALL_INT],
            [100000, DataType::INT],
            [3000000000, DataType::BIG_INT],
            [99.99, DataType::REAL],
            [1.7976931348623157e+308, DataType::DOUBLE],
            [new DateTime, DataType::DATE],
            [['name' => 'John'], DataType::JSONB],
        ];

        foreach ($testCases as [$value, $expectedType]) {
            $reflection = new ReflectionClass(Protect::class);
            $method = $reflection->getMethod('validateValue');
            $method->setAccessible(true);

            $result = $method->invokeArgs(null, [$value]);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('value', $result);
            $this->assertArrayHasKey('data_type', $result);
            $this->assertSame($value, $result['value']);
            $this->assertSame($expectedType, $result['data_type']);
        }
    }

    public function test_validate_value_throws_exception_for_unsupported_value_when_throw_is_true(): void
    {
        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateValue');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $method->invokeArgs(null, [null, true]);
    }

    public function test_validate_field_accepts_valid_format(): void
    {
        $testCases = [
            'users.email',
            'users.job_title',
            'users.age',
        ];

        foreach ($testCases as $field) {
            $reflection = new ReflectionClass(Protect::class);
            $method = $reflection->getMethod('validateField');
            $method->setAccessible(true);

            $result = $method->invokeArgs(null, [$field]);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('table', $result);
            $this->assertArrayHasKey('column', $result);

            [$expectedTable, $expectedColumn] = explode('.', $field);
            $this->assertSame($expectedTable, $result['table']);
            $this->assertSame($expectedColumn, $result['column']);
        }
    }

    public function test_validate_field_throws_exception_for_invalid_types(): void
    {
        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateField');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $method->invokeArgs(null, [null]);
    }

    public function test_validate_field_throws_exception_for_invalid_format(): void
    {
        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateField');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $method->invokeArgs(null, ['invalid.table.column']);
    }

    public function test_validate_table_name_accepts_valid_names(): void
    {
        $validNames = [
            'users',
            'user_profiles',
            'customers',
            'orders',
        ];

        foreach ($validNames as $table) {
            $reflection = new ReflectionClass(Protect::class);
            $method = $reflection->getMethod('validateTableName');
            $method->setAccessible(true);

            $result = $method->invokeArgs(null, [$table]);
            $this->assertSame($table, $result);
        }
    }

    public function test_validate_table_name_throws_exception_for_invalid_types(): void
    {
        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateTableName');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $method->invokeArgs(null, [null]);
    }

    public function test_validate_column_name_accepts_valid_names(): void
    {
        $validNames = [
            'email',
            'job_title',
            'age',
            'metadata',
        ];

        foreach ($validNames as $column) {
            $reflection = new ReflectionClass(Protect::class);
            $method = $reflection->getMethod('validateColumnName');
            $method->setAccessible(true);

            $result = $method->invokeArgs(null, [$column]);
            $this->assertSame($column, $result);
        }
    }

    public function test_validate_column_name_throws_exception_for_invalid_types(): void
    {
        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateColumnName');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $method->invokeArgs(null, [null]);
    }

    public function test_validate_cast_as_type_accepts_valid_types(): void
    {
        $validTypes = ['string', 'bool', 'int', 'float', 'date', 'array'];

        foreach ($validTypes as $type) {
            $reflection = new ReflectionClass(Protect::class);
            $method = $reflection->getMethod('validateCastAsType');
            $method->setAccessible(true);

            $result = $method->invokeArgs(null, [$type, 'test_attribute']);
            $this->assertSame($type, $result);
        }
    }

    public function test_validate_cast_as_type_throws_exception_for_invalid_type(): void
    {
        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateCastAsType');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $method->invokeArgs(null, [123, 'test_attribute']);
    }

    public function test_validate_cast_as_type_throws_exception_for_unsupported_type(): void
    {
        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateCastAsType');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $method->invokeArgs(null, ['unsupported_type', 'test_attribute']);
    }

    public function test_validate_options_accepts_valid_options(): void
    {
        $allowedKeys = ['cast_as', 'indexes', 'context', 'skip'];

        $validOptions = [
            [],
            ['cast_as' => 'string'],
            ['indexes' => ['unique' => [], 'match' => []]],
            ['context' => ['tag' => ['pii']]],
            ['skip' => true],
        ];

        foreach ($validOptions as $options) {
            $reflection = new ReflectionClass(Protect::class);
            $method = $reflection->getMethod('validateOptions');
            $method->setAccessible(true);

            $result = $method->invokeArgs(null, [$options, $allowedKeys]);
            $this->assertIsArray($result);
            $this->assertEquals($options, $result);
        }
    }

    public function test_validate_options_throws_exception_for_invalid_cast_as_type(): void
    {
        $allowedKeys = ['cast_as'];

        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateOptions');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $method->invokeArgs(null, [['cast_as' => 123], $allowedKeys]);
    }

    public function test_validate_options_throws_exception_for_invalid_indexes_type(): void
    {
        $allowedKeys = ['indexes'];

        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateOptions');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $method->invokeArgs(null, [['indexes' => 'not_array'], $allowedKeys]);
    }

    public function test_validate_options_throws_exception_for_invalid_context_type(): void
    {
        $allowedKeys = ['context'];

        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateOptions');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $method->invokeArgs(null, [['context' => 'not_array'], $allowedKeys]);
    }

    public function test_validate_options_accepts_skip(): void
    {
        $allowedKeys = ['skip'];

        $validOptions = [
            ['skip' => true],
            ['skip' => false],
        ];

        foreach ($validOptions as $options) {
            $reflection = new ReflectionClass(Protect::class);
            $method = $reflection->getMethod('validateOptions');
            $method->setAccessible(true);

            $result = $method->invokeArgs(null, [$options, $allowedKeys]);
            $this->assertIsArray($result);
            $this->assertEquals($options, $result);
        }
    }

    public function test_validate_options_throws_exception_for_invalid_skip_type(): void
    {
        $allowedKeys = ['skip'];

        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateOptions');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $method->invokeArgs(null, [['skip' => 'not_boolean'], $allowedKeys]);
    }

    public function test_validate_envelope_accepts_valid_structure(): void
    {
        $validEnvelopes = [
            [
                'k' => 'ct',
                'c' => 'mBbKlk}G7QdaGiNj$dL7#+AOrA^}*VJx...',
                'dt' => 'text',
                'hm' => 'f3ca71fd39ae9d3d1d1fc25141bcb6da...',
                'ob' => null,
                'bf' => null,
                'i' => ['t' => 'users', 'c' => 'email'],
                'v' => 2,
            ],
            [
                'k' => 'sv',
                'c' => 'mBbLQ2^Io|1eh_K2*n^LSCVVQuGhkL>w...',
                'dt' => 'jsonb',
                'sv' => [
                    [
                        's' => 'dd4659b9c279af040dd05ce21b2a22f7...',
                        't' => '22303061363334333330316661653633...',
                        'r' => 'mBbLQ2^Io|1eh_K2*n^LSCVVQuGhkL>w...',
                        'pa' => false,
                    ],
                ],
                'i' => ['t' => 'users', 'c' => 'contact'],
                'v' => 2,
            ],
        ];

        foreach ($validEnvelopes as $envelope) {
            $reflection = new ReflectionClass(Protect::class);
            $method = $reflection->getMethod('validateEnvelope');
            $method->setAccessible(true);

            $result = $method->invokeArgs(null, [$envelope]);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('ciphertext', $result);
            $this->assertArrayHasKey('data_type', $result);
            $this->assertArrayHasKey('identifier', $result);

            $this->assertIsString($result['ciphertext']);
            $this->assertSame($envelope['c'], $result['ciphertext']);

            $this->assertInstanceOf(DataType::class, $result['data_type']);
            $this->assertSame($envelope['dt'], $result['data_type']->value);

            $this->assertIsArray($result['identifier']);
            $this->assertArrayHasKey('table', $result['identifier']);
            $this->assertArrayHasKey('column', $result['identifier']);
            $this->assertIsString($result['identifier']['table']);
            $this->assertIsString($result['identifier']['column']);
            $this->assertSame($envelope['i']['t'], $result['identifier']['table']);
            $this->assertSame($envelope['i']['c'], $result['identifier']['column']);
        }
    }

    public function test_validate_envelope_throws_exception_for_missing_required_fields(): void
    {
        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateEnvelope');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $method->invokeArgs(null, [[]]);
    }

    public function test_build_field_config_creates_correct_structure(): void
    {
        $table = 'users';
        $column = 'email';
        $options = [
            'cast_as' => 'string',
            'indexes' => ['unique' => [], 'match' => []],
        ];

        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('buildFieldConfig');
        $method->setAccessible(true);

        $result = $method->invokeArgs(null, [$table, $column, $options]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('table', $result);
        $this->assertArrayHasKey('column', $result);
        $this->assertArrayHasKey('cast_as', $result);
        $this->assertArrayHasKey('indexes', $result);

        $this->assertSame($table, $result['table']);
        $this->assertSame($column, $result['column']);
        $this->assertSame($options['cast_as'], $result['cast_as']);
        $this->assertSame($options['indexes'], $result['indexes']);
    }

    public function test_build_encrypt_config_creates_correct_structure(): void
    {
        $fieldConfigs = [
            [
                'table' => 'users',
                'column' => 'email',
                'cast_as' => 'string',
                'indexes' => ['unique' => [], 'match' => []],
                'context' => [],
            ],
        ];

        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('buildEncryptConfig');
        $method->setAccessible(true);

        $result = $method->invokeArgs(null, [$fieldConfigs]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('v', $result);
        $this->assertArrayHasKey('tables', $result);
        $this->assertSame(2, $result['v']);
        $this->assertInstanceOf(stdClass::class, $result['tables']);
    }

    public function test_validate_encrypt_attributes_accepts_valid_attributes(): void
    {
        $attributes = [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ];

        $reflection = new ReflectionClass(Protect::class);
        $method = $reflection->getMethod('validateEncryptAttributes');
        $method->setAccessible(true);

        $result = $method->invokeArgs(null, [$attributes]);
        $this->assertIsArray($result);

        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertIsArray($result['email']);
        $this->assertIsArray($result['name']);
        $this->assertArrayHasKey('value', $result['email']);
        $this->assertArrayHasKey('data_type', $result['email']);
    }
}
