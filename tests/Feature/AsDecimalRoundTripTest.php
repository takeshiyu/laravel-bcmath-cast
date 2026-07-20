<?php

declare(strict_types=1);

use BcMath\Number;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use YuWuHsien\Decimal\Casts\AsDecimal;

beforeEach(function () {
    Schema::create('products', function ($table) {
        $table->increments('id');
        $table->decimal('price', 30, 12)->nullable();
        $table->timestamps();
    });

    $this->model = new class extends Model
    {
        protected $table = 'products';

        protected $guarded = [];

        protected function casts(): array
        {
            return ['price' => AsDecimal::class];
        }
    };
});

afterEach(function () {
    Schema::drop('products');
});

describe('model round-trip', function () {
    test('persists and rehydrates a plain decimal string', function () {
        $product = $this->model::query()->create(['price' => '19.99']);

        $fresh = $this->model::query()->findOrFail($product->id);

        expect($fresh->price)->toBeInstanceOf(Number::class)
            ->and($fresh->price == new Number('19.99'))->toBeTrue();
    });

    test('persists and rehydrates a small float that stringifies to scientific notation', function () {
        $product = withSuppressedWarning(fn () => $this->model::query()->create(['price' => 0.000001234]));

        $fresh = $this->model::query()->findOrFail($product->id);

        expect($fresh->price)->toBeInstanceOf(Number::class)
            ->and($fresh->price == new Number('0.000001234'))->toBeTrue();
    });

    test('persists and rehydrates a null value', function () {
        $product = $this->model::query()->create(['price' => null]);

        $fresh = $this->model::query()->findOrFail($product->id);

        expect($fresh->price)->toBeNull();
    });

    test('rehydrates a driver-returned float without crashing', function () {
        // sqlite's NUMERIC affinity hands decimals back to PHP as native
        // floats — get() must not depend on PHP's exponent-form (string) cast.
        $product = $this->model::query()->create(['price' => '0.000001234']);

        $fresh = $this->model::query()->findOrFail($product->id);

        expect($fresh->price)->toBeInstanceOf(Number::class)
            ->and($fresh->price == new Number('0.000001234'))->toBeTrue();
    });
});
