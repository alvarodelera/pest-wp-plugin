<?php

declare(strict_types=1);

/**
 * Hook-related custom expectations for WordPress.
 */

namespace PestWP\Expectations;

use function expect;
use function test;

/**
 * Register hook expectations.
 */
function registerHookExpectations(): void
{
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
}
