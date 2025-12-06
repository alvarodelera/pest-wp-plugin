<?php

declare(strict_types=1);

/**
 * Custom Pest Expectations for WordPress
 *
 * These expectations extend Pest's expect() API with WordPress-specific assertions.
 * They provide a fluent, readable way to test WordPress objects, metadata, and hooks.
 *
 * @see https://pestphp.com/docs/expectations
 * @see https://pestphp.com/docs/custom-expectations
 */

namespace PestWP;

use function expect;
use function test;

/**
 * Register custom WordPress expectations.
 *
 * This function is called automatically when the plugin loads and registers
 * all custom expectations using the expect()->extend() pattern.
 */
function registerExpectations(): void
{
    // WordPress Post Status Expectations
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

    // WP_Error Expectations
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

    // Metadata Expectations
    expect()->extend('toHaveMeta', function (string $key, $expectedValue = null) {
        $object = $this->value;

        if (! $object instanceof \WP_Post && ! $object instanceof \WP_User) {
            test()->fail('Expected value to be a WP_Post or WP_User instance.');
        }

        $metaType = $object instanceof \WP_Post ? 'post' : 'user';
        $objectId = $object->ID;

        $actualValue = get_metadata($metaType, $objectId, $key, true);

        expect(metadata_exists($metaType, $objectId, $key))->toBeTrue("Expected {$metaType} #{$objectId} to have meta key '{$key}'");

        if ($expectedValue !== null) {
            expect($actualValue)->toBe($expectedValue, "Expected {$metaType} #{$objectId} meta '{$key}' to be '{$expectedValue}', but got '{$actualValue}'");
        }

        return $this;
    });

    expect()->extend('toHaveMetaKey', function (string $key) {
        $object = $this->value;

        if (! $object instanceof \WP_Post && ! $object instanceof \WP_User) {
            test()->fail('Expected value to be a WP_Post or WP_User instance.');
        }

        $metaType = $object instanceof \WP_Post ? 'post' : 'user';
        $objectId = $object->ID;

        expect(metadata_exists($metaType, $objectId, $key))->toBeTrue("Expected {$metaType} #{$objectId} to have meta key '{$key}'");

        return $this;
    });

    expect()->extend('toHaveUserMeta', function (string $key, $expectedValue = null) {
        $user = $this->value;

        if (! $user instanceof \WP_User) {
            test()->fail('Expected value to be a WP_User instance.');
        }

        expect(metadata_exists('user', $user->ID, $key))->toBeTrue("Expected user #{$user->ID} to have meta key '{$key}'");

        if ($expectedValue !== null) {
            $actualValue = get_user_meta($user->ID, $key, true);
            expect($actualValue)->toBe($expectedValue, "Expected user meta '{$key}' to be '{$expectedValue}', but got '{$actualValue}'");
        }

        return $this;
    });

    // Hook Expectations
    expect()->extend('toHaveAction', function ($callback, int $priority = 10) {
        $hookName = $this->value;

        if (! is_string($hookName)) {
            test()->fail('Expected value to be a hook name (string).');
        }

        $actualPriority = has_action($hookName, $callback);

        expect($actualPriority)->toBe($priority, "Expected callback to be hooked to action '{$hookName}' with priority {$priority}, but got " . ($actualPriority === false ? 'false' : $actualPriority));

        return $this;
    });

    expect()->extend('toHaveFilter', function ($callback, int $priority = 10) {
        $hookName = $this->value;

        if (! is_string($hookName)) {
            test()->fail('Expected value to be a hook name (string).');
        }

        $actualPriority = has_filter($hookName, $callback);

        expect($actualPriority)->toBe($priority, "Expected callback to be hooked to filter '{$hookName}' with priority {$priority}, but got " . ($actualPriority === false ? 'false' : $actualPriority));

        return $this;
    });

    // Term Expectations
    expect()->extend('toHaveTerm', function ($term, string $taxonomy) {
        $post = $this->value;

        if (! $post instanceof \WP_Post) {
            test()->fail('Expected value to be a WP_Post instance.');
        }

        $hasTerm = has_term($term, $taxonomy, $post);

        expect($hasTerm)->toBeTrue("Expected post #{$post->ID} to have term '{$term}' in taxonomy '{$taxonomy}'");

        return $this;
    });
}
