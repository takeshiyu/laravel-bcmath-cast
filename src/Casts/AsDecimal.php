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
     * The plain decimal string grammar accepted by BCMath\Number.
     */
    private const string DECIMAL_PATTERN = '/^[+-]?(\d+(\.\d*)?|\.\d+)$/';

    /**
     * Cast the given value from the database to a BCMath\Number.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Number
    {
        try {
            return $value === null ? null : new Number(
                is_float($value) ? $this->fromFloat($value) : (string) $value
            );
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

        // Numeric string - validate against the grammar Number can parse back
        if (is_string($value)) {
            if (preg_match(self::DECIMAL_PATTERN, $value) !== 1) {
                throw new InvalidArgumentException(
                    "The {$key} attribute must be a numeric string in plain decimal form (e.g. '19.99')."
                );
            }

            return $value;
        }

        // Float - warn about precision loss and convert
        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new InvalidArgumentException(
                    "The {$key} attribute must be a finite number."
                );
            }

            trigger_error(
                "Float values may lose precision when converted to BCMath\\Number. Use strings for exact decimal values (e.g., '19.99' instead of 19.99).",
                E_USER_WARNING
            );

            return $this->fromFloat($value);
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

    /**
     * Convert a float to a plain decimal string that BCMath\Number can parse.
     *
     * PHP renders floats beyond ~1e15 or below ~1e-5 in scientific notation,
     * which BCMath\Number rejects — expand the exponent instead.
     */
    private function fromFloat(float $value): string
    {
        $string = (string) $value;

        $exponentPosition = stripos($string, 'E');

        if ($exponentPosition === false) {
            return $string;
        }

        $mantissa = substr($string, 0, $exponentPosition);
        $exponent = (int) substr($string, $exponentPosition + 1);

        $fraction = strpos($mantissa, '.');
        $fractionDigits = $fraction === false ? 0 : strlen($mantissa) - $fraction - 1;

        $expanded = sprintf('%.*F', max($fractionDigits - $exponent, 0), $value);

        return str_contains($expanded, '.')
            ? rtrim(rtrim($expanded, '0'), '.')
            : $expanded;
    }
}
