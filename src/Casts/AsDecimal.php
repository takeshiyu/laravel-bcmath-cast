<?php

declare(strict_types=1);

namespace YuWuHsien\Decimal\Casts;

use BcMath\Number;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Throwable;

/**
 * Cast database decimal values to BCMath\Number objects.
 *
 * @implements CastsAttributes<Number|null, Number|string|int|float|null>
 */
class AsDecimal implements CastsAttributes
{
    /**
     * Cast the given value from the database to a BCMath\Number.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Number
    {
        try {
            return $value === null ? null : new Number((string) $value);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                "Failed to cast {$key} to BCMath\\Number: {$exception->getMessage()}",
                previous: $exception
            );
        }
    }

    /**
     * Prepare the given value for storage in the database.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        // Already a Number object - extract its string value
        if ($value instanceof Number) {
            return $value->value;
        }

        // Integer - convert to string
        if (is_int($value)) {
            return (string) $value;
        }

        // Numeric string - validate and return
        if (is_string($value)) {
            if (! is_numeric($value)) {
                throw new InvalidArgumentException(
                    "The {$key} attribute must be a numeric string. Non-numeric string given."
                );
            }

            return $value;
        }

        // Float - warn about precision loss and convert
        if (is_float($value)) {
            trigger_error(
                "Float values may lose precision when converted to BCMath\\Number. Use strings for exact decimal values (e.g., '19.99' instead of 19.99).",
                E_USER_WARNING
            );

            return (string) $value;
        }

        // Unsupported type
        throw new InvalidArgumentException(
            sprintf(
                'The %s attribute must be a BCMath\\Number, numeric value, or numeric string. %s given.',
                $key,
                get_debug_type($value)
            )
        );
    }
}
