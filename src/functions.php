<?php

declare(strict_types=1);

/**
 * Pest Plugin for WordPress
 *
 * Global helper functions for WordPress testing with Pest.
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
