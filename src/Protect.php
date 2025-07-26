<?php

declare(strict_types=1);

namespace CipherStash\Protect;

use CipherStash\Protect\Enums\DataType;
use CipherStash\Protect\Exceptions\DecryptException;
use CipherStash\Protect\Exceptions\EncryptException;
use CipherStash\Protect\Exceptions\SearchTermException;
use CipherStash\Protect\Exceptions\ValidationException;
use CipherStash\Protect\FFI\Client;
use CipherStash\Protect\FFI\Exceptions\FFIException;
use Throwable;

/**
 * Handles encryption and decryption operations for database columns.
 *
 * Provides individual and bulk operations for encrypting values, decrypting
 * encrypted envelopes, and creating search terms for querying.
 */
class Protect
{
    /**
     * Encrypt a value for a specific table column.
     *
     * @param  string  $field  Table and column name in dot notation format
     * @param  mixed  $value  Value to encrypt
     * @param  array<string, mixed>  $options  Encrypt options
     * @return array{
     *     k: string,
     *     c: string,
     *     dt: string,
     *     hm?: string|null,
     *     ob?: array<int, string>|null,
     *     bf?: array<int, int>|null,
     *     sv?: array<int, array{s: string, t: string, r: string, pa: bool}>|null,
     *     i: array{t: string, c: string},
     *     v: int
     * } Encrypted envelope
     *
     * @throws ValidationException When field, value, or options validation fails
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     * @throws EncryptException When encryption fails
     */
    public static function encrypt(string $field, mixed $value, array $options = []): array
    {
        $validatedField = self::validateField($field);
        $validatedValue = self::validateValue($value);
        $validatedOptions = self::validateOptions($options, ['cast_as', 'indexes', 'context']);

        $resolvedOptions = self::resolveOptions($validatedOptions, $validatedValue['data_type']);

        $fieldConfig = self::buildFieldConfig($validatedField['table'], $validatedField['column'], $resolvedOptions);
        $encryptConfig = self::buildEncryptConfig([$fieldConfig]);
        $plaintext = DataConverter::toString($validatedValue['value'], $resolvedOptions['cast_as']);

        return self::withClient($encryptConfig, function (Client $client, \FFI\CData $clientPtr) use ($plaintext, $validatedField, $resolvedOptions) {
            return self::performEncryption(
                $client,
                $clientPtr,
                $plaintext,
                $validatedField['column'],
                $validatedField['table'],
                $resolvedOptions['context']
            );
        }, EncryptException::failedToEncrypt(...));
    }

    /**
     * Decrypt an encrypted envelope to its original value.
     *
     * @param  array{
     *     k: string,
     *     c: string,
     *     dt: string,
     *     hm?: string|null,
     *     ob?: array<int, string>|null,
     *     bf?: array<int, int>|null,
     *     sv?: array<int, array{s: string, t: string, r: string, pa: bool}>|null,
     *     i: array{t: string, c: string},
     *     v: int
     * }  $envelope  Encrypted envelope
     * @param  array<string, mixed>  $options  Decrypt options
     * @return mixed Decrypted value in its original PHP data type
     *
     * @throws ValidationException When envelope or options validation fails
     * @throws DecryptException When decryption fails
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     */
    public static function decrypt(array $envelope, array $options = []): mixed
    {
        $validatedEnvelope = self::validateEnvelope($envelope);
        $validatedOptions = self::validateOptions($options, ['cast_as', 'context']);

        $resolvedOptions = self::resolveOptions($validatedOptions, $validatedEnvelope['data_type']);

        $encryptConfig = self::buildEncryptConfig([]);

        $decryptResult = self::withClient($encryptConfig, function (Client $client, \FFI\CData $clientPtr) use ($validatedEnvelope, $resolvedOptions) {
            return self::performDecryption(
                $client,
                $clientPtr,
                $validatedEnvelope['ciphertext'],
                $resolvedOptions['context']
            );
        }, DecryptException::failedToDecrypt(...));

        return DataConverter::fromString($decryptResult, $resolvedOptions['cast_as']);
    }

    /**
     * Encrypt multiple attributes in a single batch operation.
     *
     * @param  string  $table  Database table name
     * @param  array<string, mixed>  $attributes  Attributes to encrypt with column names as keys
     * @param  array<string, array<string, mixed>>  $options  Encrypt options with column names as keys
     * @return array<string, array{
     *     k: string,
     *     c: string,
     *     dt: string,
     *     hm?: string|null,
     *     ob?: array<int, string>|null,
     *     bf?: array<int, int>|null,
     *     sv?: array<int, array{s: string, t: string, r: string, pa: bool}>|null,
     *     i: array{t: string, c: string},
     *     v: int
     * }|mixed> Attributes with encrypted envelopes or original values
     *
     * @throws ValidationException When table, attributes, or options validation fails
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     * @throws EncryptException When encryption fails
     */
    public static function encryptAttributes(string $table, array $attributes, array $options = []): array
    {
        $validatedTable = self::validateTableName($table);
        $validatedAttributes = self::validateEncryptAttributes($attributes);

        if (empty($validatedAttributes)) {
            return $validatedAttributes;
        }

        $validatedOptions = self::validateBulkOptions($options, ['cast_as', 'indexes', 'context', 'skip']);

        $resolvedOptions = self::resolveColumnOptions($validatedOptions, $validatedAttributes, $validatedTable);

        $fieldConfigs = self::buildColumnConfigs($resolvedOptions, $validatedTable);
        $encryptConfig = self::buildEncryptConfig($fieldConfigs);
        $plaintextItems = self::buildPlaintextItems($validatedAttributes, $validatedTable, $resolvedOptions);

        $encryptResults = self::withClient($encryptConfig, function (Client $client, \FFI\CData $clientPtr) use ($plaintextItems) {
            return self::performBulkEncryption($client, $clientPtr, $plaintextItems);
        }, EncryptException::failedToEncryptAttributes(...));

        return self::mergeEncryptResultsIntoAttributes($attributes, $encryptResults, $plaintextItems);
    }

    /**
     * Decrypt multiple attributes in a single batch operation.
     *
     * @param  string  $table  Database table name
     * @param  array<string, array{
     *     k: string,
     *     c: string,
     *     dt: string,
     *     hm?: string|null,
     *     ob?: array<int, string>|null,
     *     bf?: array<int, int>|null,
     *     sv?: array<int, array{s: string, t: string, r: string, pa: bool}>|null,
     *     i: array{t: string, c: string},
     *     v: int
     * }>  $attributes  Attributes to decrypt with column names as keys
     * @param  array<string, array<string, mixed>>  $options  Decrypt options with column names as keys
     * @return array<string, mixed> Attributes with decrypted values
     *
     * @throws ValidationException When table, attributes, or options validation fails
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     * @throws DecryptException When decryption fails
     */
    public static function decryptAttributes(string $table, array $attributes, array $options = []): array
    {
        $validatedTable = self::validateTableName($table);
        $validatedAttributes = self::validateDecryptAttributes($attributes);

        if (empty($validatedAttributes)) {
            return $validatedAttributes;
        }

        $validatedTableMatchedAttributes = self::validateTableMatch($validatedAttributes, $validatedTable);
        $validatedOptions = self::validateBulkOptions($options, ['cast_as', 'context', 'skip']);

        $resolvedOptions = self::resolveColumnOptions($validatedOptions, $validatedTableMatchedAttributes, $validatedTable);

        $encryptConfig = self::buildEncryptConfig([]);
        $ciphertextItems = self::buildCiphertextItems($validatedTableMatchedAttributes, $resolvedOptions);

        $decryptResults = self::withClient($encryptConfig, function (Client $client, \FFI\CData $clientPtr) use ($ciphertextItems) {
            return self::performBulkDecryption($client, $clientPtr, $ciphertextItems);
        }, DecryptException::failedToDecryptAttributes(...));

        return self::mergeDecryptResultsIntoAttributes($attributes, $decryptResults, $ciphertextItems);
    }

    /**
     * Create search terms for querying encrypted data without decryption.
     *
     * @param  array<string, mixed>  $fields  Field names in dot notation format with values to search for
     * @param  array<string, array<string, mixed>>  $options  Search term options with field names as keys in dot notation format
     * @return array<string, array{
     *     hm?: string|null,
     *     ob?: array<int, int>|null,
     *     bf?: array<int, int>|null,
     *     sv?: array<int, array{s: string, t: string, r: string, pa: bool}>|null,
     *     i: array{t: string, c: string}
     * }> Search terms with field names as keys
     *
     * @throws ValidationException When fields or options validation fails
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     * @throws SearchTermException When search term creation fails
     */
    public static function createSearchTerms(array $fields, array $options = []): array
    {
        $validatedFields = self::validateFields($fields);

        if (empty($validatedFields)) {
            return $validatedFields;
        }

        $validatedOptions = self::validateBulkOptions($options, ['cast_as', 'indexes', 'context']);

        $resolvedOptions = self::resolveFieldOptions($validatedOptions, $validatedFields);

        $fieldConfigs = self::buildFieldConfigs($resolvedOptions);
        $encryptConfig = self::buildEncryptConfig($fieldConfigs);
        $searchTermItems = self::buildSearchTermItems($validatedFields, $resolvedOptions);

        $searchTermResults = self::withClient($encryptConfig, function (Client $client, \FFI\CData $clientPtr) use ($searchTermItems) {
            return self::performSearchTermCreation($client, $clientPtr, $searchTermItems);
        }, SearchTermException::failedToCreateSearchTerms(...));

        return self::mergeSearchTermResultsIntoFields($validatedFields, $searchTermResults);
    }

    /**
     * Validate value type compatibility for encryption operations.
     *
     * @param  mixed  $value  Value to validate
     * @param  bool  $throw  Whether to throw exception for unsupported values
     * @return array{value: mixed, data_type: DataType|null} Validated value and its detected PHP data type
     *
     * @throws ValidationException When value type is unsupported and throw is true
     */
    private static function validateValue(mixed $value, bool $throw = false): array
    {
        $dataType = DataConverter::detectType($value);

        if ($dataType instanceof DataType) {
            return ['value' => $value, 'data_type' => $dataType];
        }

        if ($throw) {
            throw ValidationException::unsupportedValue(get_debug_type($value));
        }

        return ['value' => $value, 'data_type' => null];
    }

    /**
     * Validate and parse field format into table and column components.
     *
     * @return array{table: string, column: string, field: string} Validated field components
     *
     * @throws ValidationException When field validation fails
     */
    private static function validateField(mixed $field): array
    {
        if (! is_string($field)) {
            throw ValidationException::invalidFieldType(get_debug_type($field));
        }

        $parts = explode('.', $field);

        if (count($parts) !== 2) {
            throw ValidationException::invalidFieldFormat($field);
        }

        $validatedTable = self::validateTableName($parts[0]);
        $validatedColumn = self::validateColumnName($parts[1]);

        return [
            'table' => $validatedTable,
            'column' => $validatedColumn,
            'field' => "{$validatedTable}.{$validatedColumn}",
        ];
    }

    /**
     * Validate table name for encryption operations.
     *
     * @throws ValidationException When table name validation fails
     */
    private static function validateTableName(mixed $table): string
    {
        if (! is_string($table)) {
            throw ValidationException::invalidTableType(get_debug_type($table));
        }

        $validatedTable = trim($table);

        if ($validatedTable === '') {
            throw ValidationException::emptyTableName();
        }

        return $validatedTable;
    }

    /**
     * Validate column name for encryption operations.
     *
     * @throws ValidationException When column name validation fails
     */
    private static function validateColumnName(mixed $column): string
    {
        if (! is_string($column)) {
            throw ValidationException::invalidColumnType(get_debug_type($column));
        }

        $validatedColumn = trim($column);

        if ($validatedColumn === '') {
            throw ValidationException::emptyColumnName();
        }

        return $validatedColumn;
    }

    /**
     * Validate cast as type for encrypt and decrypt operations.
     *
     * @throws ValidationException When cast as type validation fails
     */
    private static function validateCastAsType(mixed $castAs, string $attribute): string
    {
        if (! is_string($castAs)) {
            throw ValidationException::invalidCastAsOption($attribute);
        }

        $validCastAsTypes = ['string', 'bool', 'int', 'float', 'date', 'array'];

        if (! in_array($castAs, $validCastAsTypes, true)) {
            throw ValidationException::unsupportedCastAsValue($castAs);
        }

        return $castAs;
    }

    /**
     * Validate and extract supported options from configuration.
     *
     * @param  array<string, mixed>  $options  Options to validate
     * @param  array<int, string>  $allowedOptionKeys  Allowed option keys
     * @return array{
     *     cast_as?: string,
     *     indexes?: array<string, mixed>,
     *     context?: array<string, mixed>,
     *     skip?: bool
     * } Validated options with allowed keys only
     *
     * @throws ValidationException When option types are invalid
     */
    private static function validateOptions(array $options, array $allowedOptionKeys): array
    {
        $validatedOptions = [];

        foreach ($allowedOptionKeys as $key) {
            if (! isset($options[$key])) {
                continue;
            }

            $option = $options[$key];

            match ($key) {
                'cast_as' => self::validateCastAsType($option, $key),
                'indexes' => is_array($option) ?: throw ValidationException::invalidIndexesOption($key),
                'context' => is_array($option) ?: throw ValidationException::invalidContextOption($key),
                'skip' => is_bool($option) ?: throw ValidationException::invalidSkipOption($key),
                default => null,
            };

            $validatedOptions[$key] = $option;
        }

        return $validatedOptions;
    }

    /**
     * Validate bulk options for operations with multiple fields or columns.
     *
     * @param  array<string, array<string, mixed>>  $options  Bulk options to validate with field or column names as keys
     * @param  array<int, string>  $allowedOptionKeys  Allowed option keys
     * @return array<string, array<string, mixed>> Validated options per field or column
     *
     * @throws ValidationException When bulk option types are invalid
     */
    private static function validateBulkOptions(array $options, array $allowedOptionKeys): array
    {
        $validatedOptions = [];

        foreach ($options as $fieldOrColumn => $fieldOrColumnOptions) {
            $validatedOptions[$fieldOrColumn] = self::validateOptions($fieldOrColumnOptions, $allowedOptionKeys);
        }

        return $validatedOptions;
    }

    /**
     * Validate attributes for encryption operations.
     *
     * @param  array<string, mixed>  $attributes  Attributes with column names as keys
     * @return array<string, array{value: mixed, data_type: DataType|null}> Validated attributes with column names as keys
     *
     * @throws ValidationException When column names are invalid or values are unsupported
     */
    private static function validateEncryptAttributes(array $attributes): array
    {
        $validatedAttributes = [];

        foreach ($attributes as $column => $attribute) {
            $validatedColumn = self::validateColumnName($column);
            $validatedValue = self::validateValue($attribute);

            $validatedAttributes[$validatedColumn] = $validatedValue;
        }

        return $validatedAttributes;
    }

    /**
     * Validate attributes for decryption operations.
     *
     * @param  array<string, mixed>  $attributes  Attributes with column names as keys
     * @return array<string, array{
     *     ciphertext: string,
     *     data_type: DataType,
     *     identifier: array{table: string, column: string}
     * }> Validated attributes with column names as keys
     *
     * @throws ValidationException When column names are invalid or encrypted envelope structure is invalid
     */
    private static function validateDecryptAttributes(array $attributes): array
    {
        $validatedAttributes = [];

        foreach ($attributes as $column => $attribute) {
            $validatedColumn = self::validateColumnName($column);
            $validatedEnvelope = self::validateEnvelope($attribute);

            $validatedAttributes[$validatedColumn] = $validatedEnvelope;
        }

        return $validatedAttributes;
    }

    /**
     * Validate encrypted envelope structure and required fields.
     *
     * @param  array<string, mixed>  $envelope  Encrypted envelope to validate
     * @return array{
     *     ciphertext: string,
     *     data_type: DataType,
     *     identifier: array{table: string, column: string}
     * } Validated encrypted envelope components
     *
     * @throws ValidationException When encrypted envelope validation fails
     */
    private static function validateEnvelope(array $envelope): array
    {
        if (! isset($envelope['c'])) {
            throw ValidationException::missingCiphertext();
        }

        if (! is_string($envelope['c'])) {
            throw ValidationException::invalidCiphertext();
        }

        if (! isset($envelope['dt'])) {
            throw ValidationException::missingDataType();
        }

        if (! is_string($envelope['dt'])) {
            throw ValidationException::invalidDataType();
        }

        $dataType = DataType::tryFrom($envelope['dt']);

        if ($dataType === null) {
            throw ValidationException::unsupportedDataTypeValue($envelope['dt']);
        }

        if (! isset($envelope['i']['t'])) {
            throw ValidationException::missingTableIdentifier();
        }

        $validatedTable = self::validateTableName($envelope['i']['t']);

        if (! isset($envelope['i']['c'])) {
            throw ValidationException::missingColumnIdentifier();
        }

        $validatedColumn = self::validateColumnName($envelope['i']['c']);

        return [
            'ciphertext' => $envelope['c'],
            'data_type' => $dataType,
            'identifier' => [
                'table' => $validatedTable,
                'column' => $validatedColumn,
            ],
        ];
    }

    /**
     * Validate encrypted attributes match the expected table name.
     *
     * @param  array<string, mixed>  $validatedAttributes  Validated attributes to check
     * @param  string  $expectedTable  Expected table name
     * @return array<string, mixed> Validated attributes after table name verification
     *
     * @throws ValidationException When encrypted envelope table name does not match expected table
     */
    private static function validateTableMatch(array $validatedAttributes, string $expectedTable): array
    {
        foreach ($validatedAttributes as $column => $validatedEnvelope) {
            $envelopeTable = $validatedEnvelope['identifier']['table'];

            if ($envelopeTable !== $expectedTable) {
                throw ValidationException::failedToValidateTable($column, $expectedTable, $envelopeTable);
            }
        }

        return $validatedAttributes;
    }

    /**
     * Validate fields structure and field format.
     *
     * @param  array<string, mixed>  $fields  Fields to validate
     * @return array<string, array{value: mixed, data_type: DataType|null}> Validated fields with field names as keys
     *
     * @throws ValidationException When field format is invalid or values are unsupported
     */
    private static function validateFields(array $fields): array
    {
        $validatedFields = [];

        foreach ($fields as $field => $value) {
            $validatedField = self::validateField($field);
            $validatedValue = self::validateValue(value: $value, throw: true);

            $validatedFields[$validatedField['field']] = $validatedValue;
        }

        return $validatedFields;
    }

    /**
     * Get default indexes based on data type.
     *
     * @return array<string, mixed> Index configuration based on data type
     */
    private static function getDefaultIndexes(DataType $dataType): array
    {
        return match ($dataType) {
            DataType::TEXT => [
                'unique' => [],
                'ore' => [],
            ],
            DataType::BOOLEAN => [
                'unique' => [],
            ],
            DataType::SMALL_INT,
            DataType::INT,
            DataType::BIG_INT => [
                'ore' => [],
            ],
            DataType::REAL,
            DataType::DOUBLE => [
                'ore' => [],
            ],
            DataType::DATE => [
                'ore' => [],
            ],
            DataType::JSONB => [],
        };
    }

    /**
     * Get appropriate integer type for cast as type conversion.
     */
    private static function getCastAsIntegerType(DataType $detectedType): string
    {
        return match ($detectedType) {
            DataType::SMALL_INT,
            DataType::INT,
            DataType::BIG_INT => $detectedType->value,
            default => DataType::INT->value,
        };
    }

    /**
     * Get appropriate float type for cast as type conversion.
     */
    private static function getCastAsFloatType(DataType $detectedType): string
    {
        return match ($detectedType) {
            DataType::REAL,
            DataType::DOUBLE => $detectedType->value,
            default => DataType::REAL->value,
        };
    }

    /**
     * Map cast as type names to appropriate data types.
     */
    private static function mapCastAsToDataType(string $castAs, DataType $detectedType): ?string
    {
        return match ($castAs) {
            'string' => DataType::TEXT->value,
            'bool' => DataType::BOOLEAN->value,
            'int' => self::getCastAsIntegerType($detectedType),
            'float' => self::getCastAsFloatType($detectedType),
            'date' => DataType::DATE->value,
            'array' => DataType::JSONB->value,
            default => null,
        };
    }

    /**
     * Resolve options by applying defaults after validation.
     *
     * @param  array<string, mixed>  $options  Options to resolve
     * @param  DataType|null  $dataType  Data type or null for unsupported values
     * @return array{
     *     cast_as: string,
     *     indexes: array<string, mixed>,
     *     context: array<string, mixed>|null,
     *     skip: bool
     * } Resolved options with defaults applied
     */
    private static function resolveOptions(array $options, ?DataType $dataType): array
    {
        if ($dataType === null) {
            return [
                'cast_as' => 'string',
                'indexes' => [],
                'context' => null,
                'skip' => true,
            ];
        }

        return [
            'cast_as' => isset($options['cast_as'])
                ? (self::mapCastAsToDataType($options['cast_as'], $dataType) ?? 'string')
                : $dataType->value,
            'indexes' => $options['indexes'] ?? self::getDefaultIndexes($dataType),
            'context' => $options['context'] ?? null,
            'skip' => $options['skip'] ?? false,
        ];
    }

    /**
     * Resolve column-based options for bulk operations.
     *
     * @param  array<string, array<string, mixed>>  $validatedOptions  Validated options per column
     * @param  array<string, mixed>  $attributes  Attributes to process
     * @return array<string, array<string, mixed>> Resolved options per column
     */
    private static function resolveColumnOptions(array $validatedOptions, array $attributes, string $table): array
    {
        $resolvedOptions = [];

        foreach ($attributes as $column => $attribute) {
            $columnOptions = $validatedOptions[$column] ?? [];
            $dataType = $attribute['data_type'];

            $resolvedOptions[$column] = self::resolveOptions($columnOptions, $dataType);
        }

        return $resolvedOptions;
    }

    /**
     * Resolve field-based options for bulk operations.
     *
     * @param  array<string, array<string, mixed>>  $validatedOptions  Validated options per field
     * @param  array<string, array{value: mixed, data_type: DataType|null}>  $validatedFields  Validated fields
     * @return array<string, array<string, mixed>> Resolved options per field
     *
     * @throws ValidationException When field format is invalid or table/column names are invalid
     */
    private static function resolveFieldOptions(array $validatedOptions, array $validatedFields): array
    {
        $resolvedOptions = [];

        foreach ($validatedFields as $field => $validatedValue) {
            $validatedField = self::validateField($field);
            $validatedFieldOptions = $validatedOptions[$field] ?? [];

            $resolvedFieldOptions = self::resolveOptions($validatedFieldOptions, $validatedValue['data_type']);

            $resolvedOptions[$field] = $resolvedFieldOptions;
        }

        return $resolvedOptions;
    }

    /**
     * Build field configuration for encryption and decryption operations.
     *
     * @param  string  $table  Database table name
     * @param  string  $column  Database column name
     * @param  array<string, mixed>  $resolvedOptions  Resolved options for the field
     * @return array{
     *     table: string,
     *     column: string,
     *     cast_as: string,
     *     indexes: array<string, mixed>
     * } Field configuration structure
     */
    private static function buildFieldConfig(string $table, string $column, array $resolvedOptions): array
    {
        return [
            'table' => $table,
            'column' => $column,
            'cast_as' => $resolvedOptions['cast_as'],
            'indexes' => $resolvedOptions['indexes'],
        ];
    }

    /**
     * Build field configurations for column-based bulk operations.
     *
     * @param  array<string, array<string, mixed>>  $resolvedOptions  Resolved options per column
     * @param  string  $table  Database table name
     * @return array<int, array<string, mixed>> Field configurations
     */
    private static function buildColumnConfigs(array $resolvedOptions, string $table): array
    {
        $fieldConfigs = [];

        foreach ($resolvedOptions as $column => $resolvedColumnOptions) {
            if (! $resolvedColumnOptions['skip']) {
                $fieldConfigs[] = self::buildFieldConfig($table, $column, $resolvedColumnOptions);
            }
        }

        return $fieldConfigs;
    }

    /**
     * Build field configurations for field-based bulk operations.
     *
     * @param  array<string, array<string, mixed>>  $resolvedOptions  Resolved options per field
     * @return array<int, array<string, mixed>> Field configurations
     *
     * @throws ValidationException When field format is invalid or table/column names are invalid
     */
    private static function buildFieldConfigs(array $resolvedOptions): array
    {
        $fieldConfigs = [];

        foreach ($resolvedOptions as $field => $resolvedFieldOptions) {
            $validatedField = self::validateField($field);
            $fieldConfigs[] = self::buildFieldConfig($validatedField['table'], $validatedField['column'], $resolvedFieldOptions);
        }

        return $fieldConfigs;
    }

    /**
     * Build encrypt configuration from field configurations.
     *
     * @param  array<int, array<string, mixed>>  $fieldConfigs  Field configurations
     * @return array{v: int, tables: object} Encrypt configuration structure
     */
    private static function buildEncryptConfig(array $fieldConfigs): array
    {
        $tables = [];

        foreach ($fieldConfigs as $fieldConfig) {
            $indexes = $fieldConfig['indexes'];

            foreach ($indexes as $indexType => $indexConfig) {
                if (is_array($indexConfig) && empty($indexConfig)) {
                    $indexes[$indexType] = (object) [];
                }
            }

            $tables[$fieldConfig['table']][$fieldConfig['column']] = [
                'cast_as' => $fieldConfig['cast_as'],
                'indexes' => (object) $indexes,
            ];
        }

        return ['v' => 2, 'tables' => (object) $tables];
    }

    /**
     * Build plaintext items for bulk encryption.
     *
     * @param  array<string, mixed>  $validatedAttributes  Validated attributes to process
     * @param  string  $table  Database table name
     * @param  array<string, array<string, mixed>>  $resolvedOptions  Resolved options per column
     * @return array<int, array<string, mixed>> Plaintext items for bulk encryption
     *
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     */
    private static function buildPlaintextItems(array $validatedAttributes, string $table, array $resolvedOptions = []): array
    {
        $items = [];

        foreach ($validatedAttributes as $column => $validatedValue) {
            $columnOptions = $resolvedOptions[$column];

            if (! $columnOptions['skip']) {
                $plaintext = DataConverter::toString($validatedValue['value'], $columnOptions['cast_as']);

                $items[] = [
                    'plaintext' => $plaintext,
                    'column' => $column,
                    'table' => $table,
                    'context' => $columnOptions['context'],
                ];
            }
        }

        return $items;
    }

    /**
     * Build ciphertext items for bulk decryption.
     *
     * @param  array<string, mixed>  $validatedAttributes  Validated attributes to process
     * @param  array<string, array<string, mixed>>  $resolvedOptions  Resolved options per column
     * @return array<int, array<string, mixed>> Ciphertext items for bulk decryption
     */
    private static function buildCiphertextItems(array $validatedAttributes, array $resolvedOptions = []): array
    {
        $items = [];

        foreach ($validatedAttributes as $column => $validatedEnvelope) {
            $columnOptions = $resolvedOptions[$column];

            if (! $columnOptions['skip']) {
                $items[] = [
                    'ciphertext' => $validatedEnvelope['ciphertext'],
                    'column' => $column,
                    'cast_as' => $columnOptions['cast_as'],
                    'context' => $columnOptions['context'],
                ];
            }
        }

        return $items;
    }

    /**
     * Build search term items for encryption.
     *
     * @param  array<string, array{value: mixed, data_type: DataType|null}>  $validatedFields  Validated fields
     * @param  array<string, array<string, mixed>>  $resolvedOptions  Resolved options per field
     * @return array<int, array<string, mixed>> Items for search term creation
     *
     * @throws ValidationException When field format is invalid or table/column names are invalid
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     */
    private static function buildSearchTermItems(array $validatedFields, array $resolvedOptions = []): array
    {
        $items = [];

        foreach ($validatedFields as $field => $validatedValue) {
            $validatedField = self::validateField($field);
            $resolvedFieldOptions = $resolvedOptions[$validatedField['field']];

            $items[] = [
                'plaintext' => DataConverter::toString($validatedValue['value'], $resolvedFieldOptions['cast_as']),
                'table' => $validatedField['table'],
                'column' => $validatedField['column'],
                'context' => $resolvedFieldOptions['context'],
            ];
        }

        return $items;
    }

    /**
     * Execute an operation with automatic client lifecycle management.
     *
     * Creates a new client instance, executes the provided operation, and ensures
     * proper cleanup of resources regardless of operation outcome.
     *
     * @param  array<string, mixed>  $config  Configuration for client instance
     * @param  callable(Client, \FFI\CData): mixed  $operation  Operation to perform with client
     * @param  callable(string): Throwable  $createException  Exception factory function
     * @return mixed Operation result
     *
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     * @throws EncryptException When encryption fails
     * @throws DecryptException When decryption fails
     */
    private static function withClient(array $config, callable $operation, callable $createException): mixed
    {
        $client = new Client;
        $clientPtr = null;

        try {
            $configJson = DataConverter::toJson($config);
            $clientPtr = $client->newClient($configJson);

            return $operation($client, $clientPtr);
        } catch (FFIException $e) {
            throw $createException($e->getMessage());
        } catch (Throwable $e) {
            throw $createException($e->getMessage());
        } finally {
            if ($clientPtr !== null) {
                $client->freeClient($clientPtr);
            }
        }
    }

    /**
     * Perform a single encryption operation.
     *
     * @param  Client  $client  FFI client instance
     * @param  \FFI\CData  $clientPtr  Client pointer
     * @param  string  $plaintext  Plaintext value to encrypt
     * @param  string  $column  Database column name
     * @param  string  $table  Database table name
     * @param  array<string, mixed>|null  $context  Encryption context
     * @return array<string, mixed> Encrypted envelope
     *
     * @throws FFIException When FFI operation fails
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     */
    private static function performEncryption(Client $client, \FFI\CData $clientPtr, string $plaintext, string $column, string $table, ?array $context): array
    {
        $contextJson = $context ? DataConverter::toJson($context) : null;

        $resultJson = $client->encrypt($clientPtr, $plaintext, $column, $table, $contextJson);

        return DataConverter::fromJson($resultJson);
    }

    /**
     * Perform a single decryption operation.
     *
     * @param  Client  $client  FFI client instance
     * @param  \FFI\CData  $clientPtr  Client pointer
     * @param  string  $ciphertext  Ciphertext value to decrypt
     * @param  array<string, mixed>|null  $context  Decryption context
     * @return string Decrypted plaintext value
     *
     * @throws FFIException When FFI operation fails
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     */
    private static function performDecryption(Client $client, \FFI\CData $clientPtr, string $ciphertext, ?array $context): string
    {
        $contextJson = $context ? DataConverter::toJson($context) : null;

        return $client->decrypt($clientPtr, $ciphertext, $contextJson);
    }

    /**
     * Perform a bulk encryption operation.
     *
     * @param  Client  $client  FFI client instance
     * @param  \FFI\CData  $clientPtr  Client pointer
     * @param  array<int, array<string, mixed>>  $items  Items to encrypt
     * @return array<int, array<string, mixed>> Encrypted envelopes
     *
     * @throws FFIException When FFI operation fails
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     */
    private static function performBulkEncryption(Client $client, \FFI\CData $clientPtr, array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $itemsJson = DataConverter::toJson($items);

        $resultJson = $client->encryptBulk($clientPtr, $itemsJson);

        return DataConverter::fromJson($resultJson);
    }

    /**
     * Perform a bulk decryption operation.
     *
     * @param  Client  $client  FFI client instance
     * @param  \FFI\CData  $clientPtr  Client pointer
     * @param  array<int, array<string, mixed>>  $items  Items to decrypt
     * @return array<int, string> Decrypted plaintext values
     *
     * @throws FFIException When FFI operation fails
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     */
    private static function performBulkDecryption(Client $client, \FFI\CData $clientPtr, array $items): array
    {
        $itemsJson = DataConverter::toJson($items);

        $resultJson = $client->decryptBulk($clientPtr, $itemsJson);

        return DataConverter::fromJson($resultJson);
    }

    /**
     * Perform search term creation operation.
     *
     * @param  Client  $client  FFI client instance
     * @param  \FFI\CData  $clientPtr  Client pointer
     * @param  array<int, array<string, mixed>>  $items  Items to create search terms for
     * @return array<int, array<string, mixed>> Search term results
     *
     * @throws FFIException When FFI operation fails
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     */
    private static function performSearchTermCreation(Client $client, \FFI\CData $clientPtr, array $items): array
    {
        $itemsJson = DataConverter::toJson($items);

        $resultJson = $client->createSearchTerms($clientPtr, $itemsJson);

        return DataConverter::fromJson($resultJson);
    }

    /**
     * Merge encrypt results into original attributes.
     *
     * @param  array<string, mixed>  $original  Original attributes
     * @param  array<int, array<string, mixed>>  $results  Encryption results
     * @param  array<int, array<string, mixed>>  $metadata  Metadata for merging
     * @return array<string, mixed> Merged attributes with encrypted values or original values
     *
     * @throws ValidationException When result count does not match metadata count
     */
    private static function mergeEncryptResultsIntoAttributes(array $original, array $results, array $metadata): array
    {
        if (empty($results) || empty($metadata)) {
            return $original;
        }

        if (count($results) !== count($metadata)) {
            throw ValidationException::invalidEncryptResultsCount();
        }

        $merged = $original;

        foreach ($results as $index => $result) {
            $merged[$metadata[$index]['column']] = $result;
        }

        return $merged;
    }

    /**
     * Merge decrypt results into original attributes.
     *
     * @param  array<string, mixed>  $original  Original attributes
     * @param  array<int, string>  $results  Decryption results
     * @param  array<int, array<string, mixed>>  $metadata  Metadata for merging
     * @return array<string, mixed> Merged attributes with decrypted values
     *
     * @throws ValidationException When result count does not match input data count
     * @throws \CipherStash\Protect\Exceptions\DataConversionException When data conversion fails
     */
    private static function mergeDecryptResultsIntoAttributes(array $original, array $results, array $metadata): array
    {
        if (empty($results) || empty($metadata)) {
            return $original;
        }

        if (count($results) !== count($metadata)) {
            throw ValidationException::invalidDecryptResultsCount();
        }

        $merged = $original;

        foreach ($results as $index => $result) {
            $converted = DataConverter::fromString($result, $metadata[$index]['cast_as']);

            $merged[$metadata[$index]['column']] = $converted;
        }

        return $merged;
    }

    /**
     * Merge search term results into original fields structure.
     *
     * @param  array<string, mixed>  $original  Original fields
     * @param  array<int, array<string, mixed>>  $results  Search term results
     * @return array<string, mixed> Merged search terms with fields as keys
     *
     * @throws ValidationException When result count does not match input field count
     */
    private static function mergeSearchTermResultsIntoFields(array $original, array $results): array
    {
        if (empty($original) || empty($results)) {
            return $original;
        }

        $originalKeys = array_keys($original);

        if (count($results) !== count($originalKeys)) {
            throw ValidationException::invalidSearchTermResultsCount();
        }

        return array_combine($originalKeys, $results);
    }
}
