<?php

declare(strict_types=1);

/**
 * Metadata-related custom expectations for WordPress.
 */

namespace PestWP\Expectations;

use function expect;
use function test;

/**
 * Register metadata expectations.
 */
function registerMetadataExpectations(): void
{
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
}
