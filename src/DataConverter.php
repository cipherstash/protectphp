<?php

declare(strict_types=1);

namespace CipherStash\Protect;

use CipherStash\Protect\Enums\DataType;
use CipherStash\Protect\Exceptions\DataConversionException;
use DateTime;
use JsonException;
use Throwable;

/**
 * Handles data type conversion between PHP values and string representations.
 *
 * Provides automatic type detection and bidirectional conversion for
 * preparing data for encryption and restoring decrypted values.
 */
class DataConverter
{
    /**
     * Detect the data type of a value for encryption processing.
     *
     * @param  mixed  $value  Value to detect
     * @return DataType|null Detected data type or null when unsupported
     */
    public static function detectType(mixed $value): ?DataType
    {
        return DataType::fromValue($value);
    }

    /**
     * Convert value to string representation for encryption.
     *
     * @param  mixed  $value  Value to convert
     * @param  string  $castAs  Data type for conversion
     * @return string String representation for encryption
     *
     * @throws DataConversionException When data type is invalid or conversion fails
     */
    public static function toString(mixed $value, string $castAs): string
    {
        $dataType = DataType::tryFrom($castAs);

        if ($dataType === null) {
            throw DataConversionException::invalidCastAsDataType($castAs);
        }

        try {
            return match ($dataType) {
                DataType::TEXT => (string) $value,
                DataType::BOOLEAN => $value ? 'true' : 'false',
                DataType::SMALL_INT,
                DataType::INT,
                DataType::BIG_INT => (string) $value,
                DataType::REAL,
                DataType::DOUBLE => (string) $value,
                DataType::DATE => (is_string($value) ? new DateTime($value) : $value)->format('Y-m-d\TH:i:s.uP'),
                DataType::JSONB => self::toJson(is_string($value) ? self::fromJson($value) : $value),
            };
        } catch (Throwable $e) {
            throw DataConversionException::failedToConvertData($e->getMessage());
        }
    }

    /**
     * Convert string back to the original PHP data type after decryption.
     *
     * @param  string  $value  String value to convert
     * @param  string  $castAs  Data type for conversion
     * @return mixed Converted value in its target PHP data type
     *
     * @throws DataConversionException When data type is invalid or conversion fails
     */
    public static function fromString(string $value, string $castAs): mixed
    {
        $dataType = DataType::tryFrom($castAs);

        if ($dataType === null) {
            throw DataConversionException::invalidCastAsDataType($castAs);
        }

        try {
            return match ($dataType) {
                DataType::TEXT => $value,
                DataType::BOOLEAN => $value === 'true',
                DataType::SMALL_INT,
                DataType::INT,
                DataType::BIG_INT => (int) $value,
                DataType::REAL,
                DataType::DOUBLE => (float) $value,
                DataType::DATE => new DateTime($value),
                DataType::JSONB => self::fromJson($value),
            };
        } catch (Throwable $e) {
            throw DataConversionException::failedToConvertData($e->getMessage());
        }
    }

    /**
     * Convert arrays and objects to JSON string for JSONB storage.
     *
     * @param  mixed  $value  Value to convert to JSON
     * @return string JSON representation for storage
     *
     * @throws DataConversionException When value type is invalid or JSON encoding fails
     */
    public static function toJson(mixed $value): string
    {
        if (is_array($value)) {
            try {
                return json_encode(
                    value: $value,
                    flags: JSON_THROW_ON_ERROR
                );
            } catch (JsonException $e) {
                throw DataConversionException::failedToEncodeJson($e->getMessage());
            }
        }

        if (is_object($value)) {
            try {
                return json_encode(
                    value: get_object_vars($value),
                    flags: JSON_THROW_ON_ERROR
                );
            } catch (JsonException $e) {
                throw DataConversionException::failedToEncodeJson($e->getMessage());
            }
        }

        throw DataConversionException::invalidTypeForJsonConversion(get_debug_type($value));
    }

    /**
     * Decode JSON string back to structured data.
     *
     * @param  string  $value  JSON string to decode
     * @return array<mixed, mixed> Decoded structured data
     *
     * @throws DataConversionException When JSON decoding fails or result is not structured data
     */
    public static function fromJson(string $value): array
    {
        try {
            $result = json_decode(
                json: $value,
                associative: true,
                depth: 512,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            throw DataConversionException::failedToDecodeJsonString($e->getMessage());
        }

        if (! is_array($result)) {
            throw DataConversionException::failedToDecodeJsonArray();
        }

        return $result;
    }
}
