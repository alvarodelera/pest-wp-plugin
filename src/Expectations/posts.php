<?php

declare(strict_types=1);

/**
 * Post-related custom expectations for WordPress.
 */

namespace PestWP\Expectations;

use function expect;
use function test;

/**
 * Register post status expectations.
 */
function registerPostExpectations(): void
{
    expect()->extend('toBePublished', function () {
        $post = $this->value;

        if (! $post instanceof \WP_Post) {
            test()->fail('Expected value to be a WP_Post instance.');
        }

        expect($post->post_status)->toBe('publish', "Expected post '{$post->post_title}' to be published, but status is '{$post->post_status}'");

        return $this;
    });

    expect()->extend('toBeDraft', function () {
        $post = $this->value;

        if (! $post instanceof \WP_Post) {
            test()->fail('Expected value to be a WP_Post instance.');
        }

        expect($post->post_status)->toBe('draft', "Expected post '{$post->post_title}' to be draft, but status is '{$post->post_status}'");

        return $this;
    });

    expect()->extend('toBePending', function () {
        $post = $this->value;

        if (! $post instanceof \WP_Post) {
            test()->fail('Expected value to be a WP_Post instance.');
        }

        expect($post->post_status)->toBe('pending', "Expected post '{$post->post_title}' to be pending, but status is '{$post->post_status}'");

        return $this;
    });

    expect()->extend('toBePrivate', function () {
        $post = $this->value;

        if (! $post instanceof \WP_Post) {
            test()->fail('Expected value to be a WP_Post instance.');
        }

        expect($post->post_status)->toBe('private', "Expected post '{$post->post_title}' to be private, but status is '{$post->post_status}'");

        return $this;
    });

    expect()->extend('toBeInTrash', function () {
        $post = $this->value;

        if (! $post instanceof \WP_Post) {
            test()->fail('Expected value to be a WP_Post instance.');
        }

        expect($post->post_status)->toBe('trash', "Expected post '{$post->post_title}' to be in trash, but status is '{$post->post_status}'");

        return $this;
    });
}
