<?php

declare(strict_types=1);

/**
 * Shortcode-related custom expectations for WordPress.
 */

namespace PestWP\Expectations;

use function expect;
use function test;

/**
 * Register shortcode expectations.
 */
function registerShortcodeExpectations(): void
{
    expect()->extend('toBeRegisteredShortcode', function () {
        $shortcodeTag = $this->value;

        if (! is_string($shortcodeTag)) {
            test()->fail('Expected value to be a shortcode tag (string).');
        }

        expect(shortcode_exists($shortcodeTag))->toBeTrue("Expected shortcode '{$shortcodeTag}' to be registered");

        return $this;
    });
}
