<?php

declare(strict_types=1);

/**
 * Options and transients-related custom expectations for WordPress.
 */

namespace PestWP\Expectations;

use function expect;
use function test;

/**
 * Register options expectations.
 */
function registerOptionsExpectations(): void
{
    expect()->extend('toHaveOption', function ($expectedValue = null) {
        $optionName = $this->value;

        if (! is_string($optionName)) {
            test()->fail('Expected value to be an option name (string).');
        }

        $optionExists = get_option($optionName, '__NOT_FOUND__') !== '__NOT_FOUND__';
        expect($optionExists)->toBeTrue("Expected option '{$optionName}' to exist");

        if ($expectedValue !== null) {
            $actualValue = get_option($optionName);
            expect($actualValue)->toEqual($expectedValue, "Expected option '{$optionName}' to be '{$expectedValue}', but got '{$actualValue}'");
        }

        return $this;
    });

    expect()->extend('toHaveTransient', function ($expectedValue = null) {
        $transientName = $this->value;

        if (! is_string($transientName)) {
            test()->fail('Expected value to be a transient name (string).');
        }

        $transientValue = get_transient($transientName);
        expect($transientValue)->not->toBeFalse("Expected transient '{$transientName}' to exist");

        if ($expectedValue !== null) {
            expect($transientValue)->toEqual($expectedValue, "Expected transient '{$transientName}' to be '{$expectedValue}', but got '{$transientValue}'");
        }

        return $this;
    });
}
