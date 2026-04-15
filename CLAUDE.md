# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

`yuwuhsien/laravel-bcmath-cast` — a small Laravel package providing an `AsDecimal` Eloquent cast that converts database decimal values to PHP 8.4's `BCMath\Number` object (and back), so model attributes support natural operator arithmetic with arbitrary precision.

Requires PHP 8.4+ and `ext-bcmath`. Supports Laravel 11/12/13 via `illuminate/contracts` and `illuminate/database`.

## Karpathy Guidelines (mandatory for all code changes)

- **`karpathy-guidelines`** — Must be activated before writing any code or modifying any file. Covers: explicitly stating assumptions, minimal implementation, only changing what the task requires, and converting tasks into verifiable success criteria. This skill applies to all code changes, no exceptions.

## Commands

Use Herd for local PHP:

```bash
herd composer test              # Pest test suite with profiling
herd composer test-coverage     # Pest with HTML/clover coverage → build/
herd composer analyse           # PHPStan level 8 on src/ and tests/
herd composer lint              # Pint (from global CLAUDE.md workflow)

# Single test / filter
herd php vendor/bin/pest --filter='converts string to Number'
herd php vendor/bin/pest tests/Unit/AsDecimalTest.php
```

Tests boot via `orchestra/testbench` through `tests/TestCase.php`; `phpunit.xml.dist` sets `failOnRisky=true` and `failOnWarning=true`, so any PHP warning (notably the `E_USER_WARNING` emitted on float input) fails the suite.

## Architecture

Single-class package. All production logic lives in `src/Casts/AsDecimal.php`, a `CastsAttributes` implementation with two sides:

- **`get()`** — wraps any non-null DB value in `new Number((string) $value)`. Failures are rethrown as `InvalidArgumentException` keyed by attribute name so callers see *which* column failed.
- **`set()`** — type-dispatches on the incoming value:
  - `Number` → `$value->value` (the canonical string form)
  - `int` → cast to string
  - `string` → must pass `is_numeric()` or throw
  - `float` → emits `E_USER_WARNING` then casts (tests rely on `failOnWarning` to surface this as a hard error)
  - anything else → `InvalidArgumentException` with `get_debug_type()`

The generic is declared as `CastsAttributes<Number|null, Number|string|int|float|null>` — keep input/output shapes aligned with this if you extend the cast.

Intentional constraints worth preserving:

- Floats are accepted but loud. Do not silence the warning; the README and test `converts float to string with warning` both depend on it.
- All precision-sensitive paths go through string serialization — never `(float)` or `number_format` a `Number`.
- Database columns must be `DECIMAL`, not `FLOAT` (documented in README); the cast assumes the driver returns a numeric string.

## Testing Notes

- Package tests only — no application, routes, migrations, or factories. The global rule about vfsStream/feature dirs does not apply here.
- Tests instantiate an anonymous `Model` subclass directly; there's no migration layer. Mirror that pattern for new tests rather than introducing Testbench schema setup unless actually needed.
- When adding input-type branches to `set()`, add matching cases to both the `set() method` and `BCMath\Number operations` describe blocks.
