<?php

declare(strict_types=1);

namespace CipherStash\Protect\Tests\Integration;

use CipherStash\Protect\Exceptions\DecryptException;
use CipherStash\Protect\Exceptions\ValidationException;
use CipherStash\Protect\Protect;
use DateTime;
use PHPUnit\Framework\TestCase;

class ProtectTest extends TestCase
{
    public function test_encrypt_decrypt_string_roundtrip(): void
    {
        $field = 'users.email';
        $value = 'john@example.com';

        $encrypted = Protect::encrypt($field, $value);
        $decrypted = Protect::decrypt($encrypted);

        $this->assertSame($value, $decrypted);
    }

    public function test_encrypt_decrypt_bool_roundtrip(): void
    {
        $field = 'users.is_active';
        $value = true;

        $encrypted = Protect::encrypt($field, $value);
        $decrypted = Protect::decrypt($encrypted);

        $this->assertSame($value, $decrypted);
    }

    public function test_encrypt_decrypt_int_roundtrip(): void
    {
        $field = 'users.age';
        $value = 29;

        $encrypted = Protect::encrypt($field, $value);
        $decrypted = Protect::decrypt($encrypted);

        $this->assertSame($value, $decrypted);
    }

    public function test_encrypt_decrypt_float_roundtrip(): void
    {
        $field = 'users.salary';
        $value = 75000.50;

        $encrypted = Protect::encrypt($field, $value);
        $decrypted = Protect::decrypt($encrypted);

        $this->assertSame($value, $decrypted);
    }

    public function test_encrypt_decrypt_date_roundtrip(): void
    {
        $field = 'users.created_at';
        $value = new DateTime('1970-01-01T00:00:00+00:00');

        $encrypted = Protect::encrypt($field, $value);
        $decrypted = Protect::decrypt($encrypted);

        $this->assertInstanceOf(DateTime::class, $decrypted);
        $this->assertSame($value->format('Y-m-d H:i:s'), $decrypted->format('Y-m-d H:i:s'));
    }

    public function test_encrypt_decrypt_array_roundtrip(): void
    {
        $field = 'users.metadata';
        $jsonbData = [
            'payment_methods' => [
                [
                    'type' => 'credit_card',
                    'last_four' => '4532',
                    'brand' => 'visa',
                    'exp_month' => 12,
                    'exp_year' => 2027,
                ],
            ],
            'billing_address' => [
                'street' => '742 Evergreen Terrace',
                'city' => 'Springfield',
                'state' => 'OR',
                'postal_code' => '97477',
                'country' => 'US',
            ],
            'personal_info' => [
                'ssn_last_four' => '1234',
                'date_of_birth' => '1985-03-15',
                'phone_number' => '15555555555',
            ],
        ];

        $encrypted = Protect::encrypt($field, $jsonbData);
        $decrypted = Protect::decrypt($encrypted);

        $this->assertEquals($jsonbData, $decrypted);
    }

    public function test_encrypt_string_with_empty_indexes_disables_indexing(): void
    {
        $field = 'users.email';
        $value = 'john@example.com';
        $options = ['indexes' => []];

        $encrypted = Protect::encrypt($field, $value, $options);

        $this->assertTrue(array_key_exists('hm', $encrypted));
        $this->assertNull($encrypted['hm']);
        $this->assertTrue(array_key_exists('ob', $encrypted));
        $this->assertNull($encrypted['ob']);
        $this->assertTrue(array_key_exists('bf', $encrypted));
        $this->assertNull($encrypted['bf']);
    }

    public function test_encrypt_array_with_empty_indexes_disables_indexing(): void
    {
        $field = 'users.metadata';
        $value = ['city' => 'Boston', 'state' => 'MA'];
        $options = ['indexes' => []];

        $encrypted = Protect::encrypt($field, $value, $options);

        $this->assertTrue(array_key_exists('sv', $encrypted));
        $this->assertNull($encrypted['sv']);
    }

    public function test_encrypt_string_applies_default_unique_index(): void
    {
        $field = 'users.email';
        $value = 'john@example.com';

        $encrypted = Protect::encrypt($field, $value);

        $this->assertTrue(array_key_exists('hm', $encrypted));
        $this->assertNotNull($encrypted['hm']);
        $this->assertTrue(array_key_exists('ob', $encrypted));
        $this->assertNotNull($encrypted['ob']);
        $this->assertTrue(array_key_exists('bf', $encrypted));
        $this->assertNull($encrypted['bf']);
    }

    public function test_encrypt_int_applies_default_ore_index(): void
    {
        $field = 'users.age';
        $value = 29;

        $encrypted = Protect::encrypt($field, $value);

        $this->assertTrue(array_key_exists('hm', $encrypted));
        $this->assertNull($encrypted['hm']);
        $this->assertTrue(array_key_exists('ob', $encrypted));
        $this->assertNotNull($encrypted['ob']);
        $this->assertTrue(array_key_exists('bf', $encrypted));
        $this->assertNull($encrypted['bf']);
    }

    public function test_encrypt_array_applies_no_default_indexes(): void
    {
        $field = 'users.metadata';
        $value = ['city' => 'Boston', 'state' => 'MA'];

        $encrypted = Protect::encrypt($field, $value);

        $this->assertTrue(array_key_exists('sv', $encrypted));
        $this->assertNull($encrypted['sv']);
    }

    public function test_encrypt_decrypt_with_cast_as_string_override(): void
    {
        $field = 'users.age';
        $value = 29;
        $options = ['cast_as' => 'string'];

        $encrypted = Protect::encrypt($field, $value, $options);
        $decrypted = Protect::decrypt($encrypted, $options);

        $this->assertSame('29', $decrypted);
    }

    public function test_encrypt_decrypt_with_cast_as_bool_override(): void
    {
        $field = 'users.status';
        $value = 'true';
        $options = ['cast_as' => 'bool'];

        $encrypted = Protect::encrypt($field, $value, $options);
        $decrypted = Protect::decrypt($encrypted, $options);

        $this->assertSame(true, $decrypted);
    }

    public function test_encrypt_decrypt_with_cast_as_int_override(): void
    {
        $field = 'users.score';
        $value = '250';
        $options = ['cast_as' => 'int'];

        $encrypted = Protect::encrypt($field, $value, $options);
        $decrypted = Protect::decrypt($encrypted, $options);

        $this->assertSame(250, $decrypted);
    }

    public function test_encrypt_decrypt_with_cast_as_float_override(): void
    {
        $field = 'users.rating';
        $value = '4.75';
        $options = ['cast_as' => 'float'];

        $encrypted = Protect::encrypt($field, $value, $options);
        $decrypted = Protect::decrypt($encrypted, $options);

        $this->assertSame(4.75, $decrypted);
    }

    public function test_encrypt_decrypt_with_cast_as_array_override(): void
    {
        $field = 'users.tags';
        $value = '["admin","user"]';
        $options = ['cast_as' => 'array'];

        $encrypted = Protect::encrypt($field, $value, $options);
        $decrypted = Protect::decrypt($encrypted, $options);

        $this->assertEquals(['admin', 'user'], $decrypted);
    }

    public function test_encrypt_decrypt_with_cast_as_date_override(): void
    {
        $field = 'users.signup_date';
        $value = '2024-01-15T10:30:00+00:00';
        $options = ['cast_as' => 'date'];

        $encrypted = Protect::encrypt($field, $value, $options);
        $decrypted = Protect::decrypt($encrypted, $options);

        $this->assertInstanceOf(DateTime::class, $decrypted);
        $this->assertSame('2024-01-15 10:30:00', $decrypted->format('Y-m-d H:i:s'));
    }

    public function test_encrypt_decrypt_with_cast_as_int_override_preserves_big_numbers(): void
    {
        $field = 'users.big_id';
        $value = '9223372036854775807'; // Max int64
        $options = ['cast_as' => 'int'];

        $encrypted = Protect::encrypt($field, $value, $options);
        $decrypted = Protect::decrypt($encrypted, $options);

        $this->assertSame(9223372036854775807, $decrypted);
    }

    public function test_encrypt_decrypt_with_cast_as_float_override_preserves_precision(): void
    {
        $field = 'users.precise_value';
        $value = '123.456789012345';
        $options = ['cast_as' => 'float'];

        $encrypted = Protect::encrypt($field, $value, $options);
        $decrypted = Protect::decrypt($encrypted, $options);

        $this->assertSame(123.456789012345, $decrypted);
    }

    public function test_encrypt_decrypt_roundtrip_with_context(): void
    {
        $field = 'users.email';
        $value = 'john@example.com';
        $options = ['context' => ['tag' => ['pii']]];

        $encrypted = Protect::encrypt($field, $value, $options);
        $decrypted = Protect::decrypt($encrypted, $options);

        $this->assertSame($value, $decrypted);
    }

    public function test_encrypt_decrypt_attributes_roundtrip(): void
    {
        $attributes = [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'age' => 29,
            'is_active' => true,
        ];

        $encrypted = Protect::encryptAttributes('users', $attributes);
        $decrypted = Protect::decryptAttributes('users', $encrypted);

        $this->assertSame($attributes, $decrypted);
    }

    public function test_encrypt_decrypt_attributes_roundtrip_with_context(): void
    {
        $attributes = [
            'email' => 'john@example.com',
            'metadata' => [
                'payment_methods' => [
                    [
                        'type' => 'credit_card',
                        'last_four' => '4532',
                        'brand' => 'visa',
                        'exp_month' => 12,
                        'exp_year' => 2027,
                    ],
                ],
                'billing_address' => [
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'postal_code' => '97477',
                    'country' => 'US',
                ],
                'personal_info' => [
                    'ssn_last_four' => '1234',
                    'date_of_birth' => '1985-03-15',
                    'phone_number' => '15555555555',
                ],
            ],
        ];

        $options = [
            'email' => ['context' => ['tag' => ['pii']]],
        ];

        $encrypted = Protect::encryptAttributes('users', $attributes, $options);
        $decrypted = Protect::decryptAttributes('users', $encrypted, $options);

        $this->assertEquals($attributes, $decrypted);
    }

    public function test_encrypt_attributes_with_skip(): void
    {
        $attributes = [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ];

        $options = [
            'email' => ['skip' => true],
        ];

        $result = Protect::encryptAttributes('users', $attributes, $options);

        $this->assertSame($attributes['email'], $result['email']);
        $this->assertNotSame($attributes['name'], $result['name']);
    }

    public function test_decrypt_attributes_with_skip(): void
    {
        $attributes = [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ];

        $encrypted = Protect::encryptAttributes('users', $attributes);

        $options = [
            'email' => ['skip' => true],
        ];

        $result = Protect::decryptAttributes('users', $encrypted, $options);

        $this->assertSame($encrypted['email'], $result['email']);
        $this->assertNotSame($encrypted['name'], $result['name']);
    }

    public function test_create_search_terms(): void
    {
        $fields = [
            'users.email' => 'john@example.com',
            'users.name' => 'John Doe',
        ];

        $searchTerms = Protect::createSearchTerms($fields);

        $this->assertCount(2, $searchTerms);
    }

    public function test_create_search_terms_with_context(): void
    {
        $fields = [
            'users.email' => 'john@example.com',
        ];

        $options = [
            'users.email' => ['context' => ['tag' => ['pii']]],
        ];

        $searchTerms = Protect::createSearchTerms($fields, $options);

        $this->assertArrayHasKey('users.email', $searchTerms);
    }

    public function test_encrypt_throws_exception_with_invalid_context(): void
    {
        $this->expectException(ValidationException::class);
        Protect::encrypt('users.email', 'john@example.com', ['context' => 'invalid-context']);
    }

    public function test_decrypt_throws_exception_with_empty_ciphertext(): void
    {
        $this->expectException(DecryptException::class);
        Protect::decrypt([
            'k' => 'ct',
            'c' => '',
            'dt' => 'text',
            'i' => ['t' => 'users', 'c' => 'email'],
            'v' => 2,
        ]);
    }

    public function test_decrypt_throws_exception_with_invalid_envelope(): void
    {
        $invalidEnvelope = [
            'k' => 'ct',
            'c' => 'valid-ciphertext',
            'dt' => 'invalid-data-type',
            'i' => ['t' => 'users', 'c' => 'email'],
            'v' => 2,
        ];

        $this->expectException(ValidationException::class);
        Protect::decrypt($invalidEnvelope);
    }

    public function test_decrypt_throws_exception_with_context_on_ste_vec_column(): void
    {
        $field = 'users.metadata';
        $value = ['city' => 'Boston', 'state' => 'MA'];
        $options = [
            'indexes' => [
                'ste_vec' => [
                    'prefix' => $field,
                ],
            ],
            'context' => ['tag' => ['pii']],
        ];

        // Context is ignored during encryption
        $encrypted = Protect::encrypt($field, $value, $options);

        // Context causes decryption to fail
        $this->expectException(DecryptException::class);
        Protect::decrypt($encrypted, $options);
    }

    public function test_decrypt_throws_exception_with_wrong_context(): void
    {
        $field = 'users.email';
        $value = 'john@example.com';
        $originalOptions = ['context' => ['tag' => ['original-context']]];
        $wrongOptions = ['context' => ['tag' => ['wrong-context']]];

        $encrypted = Protect::encrypt($field, $value, $originalOptions);

        $this->expectException(DecryptException::class);
        Protect::decrypt($encrypted, $wrongOptions);
    }

    public function test_decrypt_throws_exception_with_wrong_value_context(): void
    {
        $field = 'users.email';
        $value = 'john@example.com';
        $originalOptions = [
            'context' => [
                'tag' => ['original-context'],
                'value' => [
                    ['key' => 'tenant_id', 'value' => 'original-tenant'],
                    ['key' => 'role', 'value' => 'original-role'],
                ],
            ],
        ];
        $wrongOptions = [
            'context' => [
                'tag' => ['original-context'],
                'value' => [
                    ['key' => 'tenant_id', 'value' => 'wrong-tenant'],
                    ['key' => 'role', 'value' => 'wrong-role'],
                ],
            ],
        ];

        $encrypted = Protect::encrypt($field, $value, $originalOptions);

        $this->expectException(DecryptException::class);
        Protect::decrypt($encrypted, $wrongOptions);
    }

    public function test_decrypt_attributes_throws_exception_with_mismatched_table(): void
    {
        $attributes = ['email' => 'john@example.com'];
        $encrypted = Protect::encryptAttributes('users', $attributes);

        $this->expectException(ValidationException::class);
        Protect::decryptAttributes('customers', $encrypted);
    }

    public function test_create_search_terms_throws_exception_with_invalid_terms(): void
    {
        $this->expectException(ValidationException::class);
        Protect::createSearchTerms(['invalid.field.format' => 'value']);
    }

    public function test_encrypt_attributes_returns_empty_with_empty_attributes(): void
    {
        $result = Protect::encryptAttributes('users', []);
        $this->assertSame([], $result);
    }

    public function test_decrypt_attributes_returns_empty_with_empty_attributes(): void
    {
        $result = Protect::decryptAttributes('users', []);
        $this->assertSame([], $result);
    }

    public function test_create_search_terms_returns_empty_with_empty_terms(): void
    {
        $result = Protect::createSearchTerms([]);
        $this->assertSame([], $result);
    }
}
