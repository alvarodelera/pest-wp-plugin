<?php

declare(strict_types=1);

/**
 * WP_Error-related custom expectations for WordPress.
 */

namespace PestWP\Expectations;

use function expect;
use function test;

/**
 * Register WP_Error expectations.
 */
function registerErrorExpectations(): void
{
    expect()->extend('toBeWPError', function () {
        expect($this->value)->toBeInstanceOf(\WP_Error::class);

        return $this;
    });

    expect()->extend('toHaveErrorCode', function (string $code) {
        $error = $this->value;

        if (! $error instanceof \WP_Error) {
            test()->fail('Expected value to be a WP_Error instance.');
        }

        expect($error->get_error_code())->toBe($code);

        return $this;
    });
}
