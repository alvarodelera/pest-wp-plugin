<?php

declare(strict_types=1);

/**
 * WP_Error-related custom expectations for WordPress.
 */

namespace PestWP\Expectations;

use PestWP\Rest\RestResponse;

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
        $value = $this->value;

        if ($value instanceof \WP_Error) {
            expect($value->get_error_code())->toBe($code);

            return $this;
        }

        if (class_exists(RestResponse::class) && $value instanceof RestResponse) {
            expect($value->errorCode())->toBe($code);

            return $this;
        }

        test()->fail('Expected value to be a WP_Error or RestResponse instance.');

        return $this;
    });
}
