<?php

declare(strict_types=1);

use BcMath\Number;
use Illuminate\Database\Eloquent\Model;
use YuWuHsien\Decimal\Casts\AsDecimal;

beforeEach(function () {
    $this->cast = new AsDecimal;
    $this->model = new class extends Model
    {
        protected $table = 'test_models';
    };
});

describe('get() method', function () {
    test('converts string to Number', function () {
        $result = $this->cast->get($this->model, 'price', '19.99', []);

        expect($result)->toBeInstanceOf(Number::class)
            ->and($result->value)->toBe('19.99');
    });

    test('handles null by returning null', function () {
        $result = $this->cast->get($this->model, 'price', null, []);

        expect($result)->toBeNull();
    });

    test('preserves decimal precision', function () {
        $result = $this->cast->get($this->model, 'price', '123.456789', []);

        expect($result->value)->toBe('123.456789');
    });

    test('handles zero', function () {
        $result = $this->cast->get($this->model, 'price', '0', []);

        expect($result)->toBeInstanceOf(Number::class)
            ->and($result->value)->toBe('0');
    });

    test('handles negative numbers', function () {
        $result = $this->cast->get($this->model, 'price', '-99.99', []);

        expect($result->value)->toBe('-99.99');
    });

    test('throws exception for invalid values', function () {
        $this->cast->get($this->model, 'price', 'invalid', []);
    })->throws(InvalidArgumentException::class);
});

describe('set() method', function () {
    test('converts Number to string', function () {
        $number = new Number('19.99');
        $result = $this->cast->set($this->model, 'price', $number, []);

        expect($result)->toBe('19.99');
    });

    test('handles null', function () {
        $result = $this->cast->set($this->model, 'price', null, []);

        expect($result)->toBeNull();
    });

    test('converts integer to string', function () {
        $result = $this->cast->set($this->model, 'price', 42, []);

        expect($result)->toBe('42');
    });

    test('accepts numeric string', function () {
        $result = $this->cast->set($this->model, 'price', '19.99', []);

        expect($result)->toBe('19.99');
    });

    test('accepts negative numeric string', function () {
        $result = $this->cast->set($this->model, 'price', '-19.99', []);

        expect($result)->toBe('-19.99');
    });

    test('accepts explicitly signed positive string', function () {
        $result = $this->cast->set($this->model, 'price', '+5', []);

        expect($result)->toBe('+5');
    });

    test('accepts string with leading decimal point', function () {
        $result = $this->cast->set($this->model, 'price', '.5', []);

        expect($result)->toBe('.5');
    });

    test('accepts string with trailing decimal point', function () {
        $result = $this->cast->set($this->model, 'price', '5.', []);

        expect($result)->toBe('5.');
    });

    test('converts float to string with warning', function () {
        $result = $this->cast->set($this->model, 'price', 19.99, []);

        expect($result)->toBe('19.99');
    })->throws(ErrorException::class, 'Float values may lose precision');

    test('converts small float to plain decimal instead of scientific notation', function () {
        $result = withSuppressedWarning(fn () => $this->cast->set($this->model, 'price', 0.000001234, []));

        expect($result)->toBe('0.000001234');
    });

    test('converts large float to plain decimal instead of scientific notation', function () {
        $result = withSuppressedWarning(fn () => $this->cast->set($this->model, 'price', 1e20, []));

        expect($result)->toBe('100000000000000000000');
    });

    test('rejects non-numeric string', function () {
        $this->cast->set($this->model, 'price', 'not-a-number', []);
    })->throws(InvalidArgumentException::class, 'must be a numeric string');

    test('rejects scientific notation string that Number cannot parse', function () {
        $this->cast->set($this->model, 'price', '1e5', []);
    })->throws(InvalidArgumentException::class, 'must be a numeric string');

    test('rejects numeric string with surrounding whitespace', function () {
        $this->cast->set($this->model, 'price', ' 19.99', []);
    })->throws(InvalidArgumentException::class, 'must be a numeric string');

    test('rejects non-finite floats', function () {
        $this->cast->set($this->model, 'price', INF, []);
    })->throws(InvalidArgumentException::class, 'must be a finite number');

    test('every stored string is readable back by get()', function (mixed $input) {
        $stored = withSuppressedWarning(fn () => $this->cast->set($this->model, 'price', $input, []));

        expect($this->cast->get($this->model, 'price', $stored, []))
            ->toBeInstanceOf(Number::class);
    })->with([
        'plain string' => '19.99',
        'signed string' => '+5',
        'leading dot' => '.5',
        'trailing dot' => '5.',
        'integer' => 42,
        'small float' => 0.000001234,
        'large float' => 1e20,
        'number object' => new Number('123.456789'),
    ]);

    test('rejects invalid types', function () {
        $this->cast->set($this->model, 'price', [], []);
    })->throws(InvalidArgumentException::class);

    test('preserves precision in string conversion', function () {
        $number = new Number('123.456789');
        $result = $this->cast->set($this->model, 'price', $number, []);

        expect($result)->toBe('123.456789');
    });
});

describe('BCMath\Number operations', function () {
    test('supports arithmetic operations on cast values', function () {
        $price = $this->cast->get($this->model, 'price', '19.99', []);
        $quantity = new Number('3');

        $result = $price * $quantity;

        expect($result)->toBeInstanceOf(Number::class)
            ->and($result->value)->toBe('59.97');
    });

    test('supports comparison operations', function () {
        $price1 = $this->cast->get($this->model, 'price', '19.99', []);
        $price2 = new Number('20.00');

        expect($price1 < $price2)->toBeTrue()
            ->and($price1 > $price2)->toBeFalse()
            ->and($price1 == new Number('19.99'))->toBeTrue();
    });

    test('supports addition with scale', function () {
        $price = $this->cast->get($this->model, 'price', '10.50', []);
        $tax = new Number('0.84');

        $result = $price + $tax;

        expect($result->value)->toBe('11.34');
    });
});
