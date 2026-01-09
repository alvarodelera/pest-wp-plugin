<?php

declare(strict_types=1);

namespace PestWP\Functions;

use PestWP\Arch\WordPressArchHelper;

/**
 * Create a WordPress architecture helper for testing.
 *
 * This function provides a fluent API for WordPress-specific architecture testing.
 *
 * @param array<int, string>|string $targets Optional target namespaces or paths
 *
 * @example
 * ```php
 * // Test that App namespace doesn't use debug functions
 * arch('no debug functions')
 *     ->expect('App')
 *     ->not->toUseDebugFunctions();
 *
 * // Or use the wordpress() helper for preset methods
 * test('wordpress preset', function () {
 *     wordpress('App')->noDebugFunctions();
 *     wordpress('App')->noSecuritySensitiveFunctions();
 * });
 * ```
 */
function wordpress(array|string $targets = []): WordPressArchHelper
{
    return new WordPressArchHelper($targets);
}

/**
 * Get the WordPress architecture preset instance.
 *
 * This is an alias for wordpress() for better readability in tests.
 *
 * @param array<int, string>|string $targets Optional target namespaces or paths
 */
function wpArch(array|string $targets = []): WordPressArchHelper
{
    return wordpress($targets);
}
