<?php

declare(strict_types=1);

/**
 * User-related custom expectations for WordPress.
 */

namespace PestWP\Expectations;

use function expect;
use function test;

/**
 * Register user capabilities and permissions expectations.
 */
function registerUserExpectations(): void
{
    expect()->extend('toHaveCapability', function (string $capability) {
        $user = $this->value;

        if (! $user instanceof \WP_User) {
            test()->fail('Expected value to be a WP_User instance.');
        }

        expect($user->has_cap($capability))->toBeTrue("Expected user #{$user->ID} to have capability '{$capability}'");

        return $this;
    });

    expect()->extend('toHaveRole', function (string $role) {
        $user = $this->value;

        if (! $user instanceof \WP_User) {
            test()->fail('Expected value to be a WP_User instance.');
        }

        expect(in_array($role, $user->roles, true))->toBeTrue("Expected user #{$user->ID} to have role '{$role}'");

        return $this;
    });

    expect()->extend('can', function (string $capability) {
        $user = $this->value;

        if (! $user instanceof \WP_User) {
            test()->fail('Expected value to be a WP_User instance.');
        }

        expect($user->has_cap($capability))->toBeTrue("Expected user #{$user->ID} to have capability '{$capability}'");

        return $this;
    });
}
