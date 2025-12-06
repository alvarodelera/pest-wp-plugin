<?php

declare(strict_types=1);

namespace PestWP;

use Pest\Contracts\Plugins\Bootable;
use PestWP\Database\DatabaseManager;

/**
 * Pest Plugin for WordPress Testing.
 *
 * This plugin automatically bootstraps WordPress with SQLite
 * when Pest tests are executed. It handles:
 *
 * - WordPress installation and loading
 * - Database snapshot management for test isolation
 * - Auto-configuration without user intervention
 *
 * @internal
 */
final class Plugin implements Bootable
{
    /**
     * Whether the plugin has been booted.
     */
    private static bool $booted = false;

    /**
     * Boot the WordPress test environment.
     *
     * This method is called by Pest before running any tests.
     * It ensures WordPress is loaded and the database manager is initialized.
     */
    public function boot(): void
    {
        if (self::$booted) {
            return;
        }

        // Bootstrap WordPress if not already loaded
        if (! defined('ABSPATH')) {
            bootstrap();
        }

        // Initialize the database manager for snapshot support
        if (defined('ABSPATH') && ! DatabaseManager::isInitialized()) {
            DatabaseManager::initialize();
        }

        self::$booted = true;
    }

    /**
     * Check if the plugin has been booted.
     */
    public static function isBooted(): bool
    {
        return self::$booted;
    }

    /**
     * Reset the booted state (useful for testing).
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$booted = false;
    }
}
