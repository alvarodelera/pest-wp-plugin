<?php

declare(strict_types=1);

/**
 * Post type-related custom expectations for WordPress.
 */

namespace PestWP\Expectations;

use function expect;
use function test;

/**
 * Register post type expectations.
 */
function registerPostTypeExpectations(): void
{
    expect()->extend('toBeRegisteredPostType', function () {
        $postType = $this->value;

        if (! is_string($postType)) {
            test()->fail('Expected value to be a post type (string).');
        }

        expect(post_type_exists($postType))->toBeTrue("Expected post type '{$postType}' to be registered");

        return $this;
    });

    expect()->extend('toSupportFeature', function (string $feature) {
        $postType = $this->value;

        if (! is_string($postType)) {
            test()->fail('Expected value to be a post type (string).');
        }

        expect(post_type_supports($postType, $feature))->toBeTrue("Expected post type '{$postType}' to support feature '{$feature}'");

        return $this;
    });
}
