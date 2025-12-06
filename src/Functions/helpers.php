<?php

declare(strict_types=1);

/**
 * General helper functions for the Pest WordPress plugin.
 */

namespace PestWP;

/**
 * Returns the current version of the Pest WordPress plugin.
 */
function version(): string
{
    return '1.0.0-dev';
}

/**
 * Get the InteractsWithDatabase trait class name.
 *
 * Usage in Pest.php:
 *
 *     uses(\PestWP\Concerns\InteractsWithDatabase::class)->in('Integration');
 *
 * Or using this helper:
 *
 *     uses(PestWP\databaseIsolation())->in('Integration');
 *
 * @return class-string
 */
function databaseIsolation(): string
{
    return Concerns\InteractsWithDatabase::class;
}

/**
 * Set a WordPress option.
 *
 * @param  mixed  $value
 */
function setOption(string $name, $value): bool
{
    return update_option($name, $value);
}

/**
 * Delete a WordPress option.
 */
function deleteOption(string $name): bool
{
    return delete_option($name);
}

/**
 * Set a WordPress transient.
 *
 * @param  mixed  $value
 */
function setTransient(string $name, $value, int $expiration = 0): bool
{
    return set_transient($name, $value, $expiration);
}

/**
 * Delete a WordPress transient.
 */
function deleteTransient(string $name): bool
{
    return delete_transient($name);
}

/**
 * Register a test shortcode.
 *
 * This is a convenience wrapper for add_shortcode() in tests.
 *
 * @param  callable  $callback
 */
function registerTestShortcode(string $tag, $callback): void
{
    if ($tag === '') {
        throw new \InvalidArgumentException('Shortcode tag cannot be empty');
    }

    add_shortcode($tag, $callback);
}

/**
 * Unregister a shortcode.
 */
function unregisterShortcode(string $tag): void
{
    remove_shortcode($tag);
}
