<?php

declare(strict_types=1);

use YuWuHsien\Decimal\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * Run the callback with E_USER_WARNING suppressed, restoring the handler afterwards.
 */
function withSuppressedWarning(Closure $callback): mixed
{
    set_error_handler(fn (): bool => true, E_USER_WARNING);

    try {
        return $callback();
    } finally {
        restore_error_handler();
    }
}
