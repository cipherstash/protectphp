<?php

declare(strict_types=1);

namespace CipherStash\Protect\Enums;

use DateTimeInterface;

/**
 * Defines supported data types for encryption and decryption operations.
 *
 * Provides type detection utilities based on the CipherStash Client SDK
 * data type specifications.
 */
enum DataType: string
{
    /**
     * Text data type.
     */
    case TEXT = 'text';

    /**
     * Boolean data type.
     */
    case BOOLEAN = 'boolean';

    /**
     * 16-bit integer.
     */
    case SMALL_INT = 'small_int';

    /**
     * 32-bit integer.
     */
    case INT = 'int';

    /**
     * 64-bit integer.
     */
    case BIG_INT = 'big_int';

    /**
     * Single precision float.
     */
    case REAL = 'real';

    /**
     * Double precision float.
     */
    case DOUBLE = 'double';

    /**
     * Date data type.
     */
    case DATE = 'date';

    /**
     * JSONB data type.
     */
    case JSONB = 'jsonb';

    /**
     * Detect the appropriate data type for a given value.
     *
     * @param  mixed  $value  Value to detect
     * @return DataType|null Detected data type or null when unsupported
     */
    public static function fromValue(mixed $value): ?self
    {
        if (is_string($value)) {
            return self::TEXT;
        }

        if (is_bool($value)) {
            return self::BOOLEAN;
        }

        if (is_int($value)) {
            return self::fromInteger($value);
        }

        if (is_float($value)) {
            return self::fromFloat($value);
        }

        if ($value instanceof DateTimeInterface) {
            return self::DATE;
        }

        if (is_array($value) || is_object($value)) {
            return self::JSONB;
        }

        return null;
    }

    /**
     * Select the appropriate integer data type based on value range.
     *
     * @param  int  $value  Integer value to detect
     * @return DataType Appropriate integer data type based on value range
     */
    public static function fromInteger(int $value): self
    {
        if ($value >= -32768 && $value <= 32767) {
            return self::SMALL_INT;
        }

        if ($value >= -2147483648 && $value <= 2147483647) {
            return self::INT;
        }

        return self::BIG_INT;
    }

    /**
     * Select the appropriate float data type based on precision requirements.
     *
     * @param  float  $value  Float value to detect
     * @return DataType Appropriate float data type based on precision requirements
     */
    public static function fromFloat(float $value): self
    {
        if (self::isFloatZero($value)) {
            return self::REAL;
        }

        if (self::exceedsRealRange($value)) {
            return self::DOUBLE;
        }

        if (self::requiresDoublePrecision($value)) {
            return self::DOUBLE;
        }

        return self::REAL;
    }

    /**
     * Check if the float value is exactly zero.
     */
    private static function isFloatZero(float $value): bool
    {
        return $value === 0.0;
    }

    /**
     * Check if the float value exceeds single precision range.
     */
    private static function exceedsRealRange(float $value): bool
    {
        $absValue = abs($value);

        return $absValue > 3.4e38 || $absValue < 1.18e-38;
    }

    /**
     * Check if the float needs double precision representation.
     */
    private static function requiresDoublePrecision(float $value): bool
    {
        $formatted = sprintf('%.8f', $value);
        $trimmed = rtrim(rtrim($formatted, '0'), '.');

        $hasDecimalPoint = str_contains($trimmed, '.');
        $precision = $hasDecimalPoint
            ? strlen(substr($trimmed, strpos($trimmed, '.') + 1))
            : 0;

        if ($hasDecimalPoint && $precision > 7) {
            return true;
        }

        return ! self::canBeRepresentedAsReal($value);
    }

    /**
     * Check if the float can be accurately represented as single precision.
     */
    private static function canBeRepresentedAsReal(float $value): bool
    {
        $singlePrecision = (float) (string) (float) $value;
        $epsilon = 1.0e-7;

        return abs($singlePrecision - $value) < $epsilon;
    }
}
