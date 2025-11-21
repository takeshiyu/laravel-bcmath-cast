# Laravel BCMath Cast

Laravel Eloquent casts for PHP 8.4's `BCMath\Number` object API, enabling natural operator-based arithmetic on model attributes while maintaining arbitrary precision decimal accuracy.

## Why This Package?

PHP 8.4 introduced the `BCMath\Number` class with operator overloading, transforming cumbersome decimal calculations:

```php
// Old way with BCMath functions
$total = bcadd(bcmul($price, $quantity), $tax, 2);

// New way with BCMath\Number
$total = ($price * $quantity) + $tax;
```

This package bridges Laravel's Eloquent ORM with PHP 8.4's BCMath\Number, letting you write clean, precise arithmetic directly on model attributes.

Read more: [BCMath Object API in PHP 8.4](https://ywh.sh/writing/bcmath-object-api-in-php-84)

## Requirements

- **PHP 8.4+** (for `BCMath\Number` class)
- **Laravel 11.x**

## Installation

```bash
composer require yuwuhsien/laravel-bcmath-cast
```

## Usage

### Basic Usage

Add the `AsDecimal` cast to your Eloquent model:

```php
use YuWuHsien\Decimal\Casts\AsDecimal;

class Product extends Model
{
    protected function casts(): array
    {
        return [
            'price' => AsDecimal::class,
            'cost' => AsDecimal::class,
            'tax_rate' => AsDecimal::class,
        ];
    }
}
```

Now your model attributes are automatically converted to `BCMath\Number` objects:

```php
$product = Product::create([
    'price' => '19.99',
    'cost' => '12.50',
    'tax_rate' => '0.0825',
]);

// Attributes are BCMath\Number instances
$product->price instanceof Number; // true

// Perform precise calculations with natural operators
$margin = $product->price - $product->cost; // Number('7.49')
$marginPercent = ($margin / $product->cost) * new Number('100'); // Number('59.92')

// Calculate total with tax
$total = $product->price * (new Number('1') + $product->tax_rate); // Number('21.639175')
```

### Setting Values

The cast accepts multiple input types:

```php
// BCMath\Number objects
$product->price = new Number('29.99');

// Numeric strings (recommended for precision)
$product->price = '29.99';

// Integers
$product->price = 30;

// Floats (works but may lose precision - triggers warning)
$product->price = 29.99; // ⚠️ Warning: precision loss possible
```

### Database Column Types

**Use DECIMAL columns, NOT FLOAT:**

```php
Schema::create('products', function (Blueprint $table) {
    $table->decimal('price', 10, 2);       // ✅ Correct
    $table->decimal('cost', 10, 4);        // ✅ Correct
    $table->float('amount');               // ❌ Wrong - loses precision
});
```

The DECIMAL type preserves exact decimal values. Floats introduce rounding errors that defeat the purpose of BCMath.

## Testing

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis
composer analyse
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
