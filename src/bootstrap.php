<?php

declare(strict_types=1);

/**
 * PestWP Bootstrap Loader
 *
 * This file bootstraps the WordPress test environment.
 * It should be included in phpunit.xml bootstrap attribute or manually in tests.
 */

namespace PestWP;

use PestWP\Installer\Installer;

/**
 * Bootstrap the WordPress test environment.
 *
 * @param  string|null  $basePath  The base path of the project (defaults to current working directory)
 * @param  bool  $autoInstall  Whether to automatically install WordPress if not present
 */
function bootstrap(?string $basePath = null, bool $autoInstall = true): void
{
    static $bootstrapped = false;

    if ($bootstrapped) {
        return;
    }

    $basePath = $basePath ?? getcwd() ?: dirname(__DIR__, 2);

    // Load Composer autoloader
    $autoloadPaths = [
        $basePath . '/vendor/autoload.php',
        dirname(__DIR__) . '/vendor/autoload.php',
        dirname(__DIR__, 4) . '/autoload.php', // When installed as a dependency
    ];

    foreach ($autoloadPaths as $autoloadPath) {
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;

            break;
        }
    }

    // Initialize the installer
    $installer = new Installer($basePath);

    // Auto-install if needed
    if ($autoInstall && ! $installer->isInstalled()) {
        echo "PestWP: Installing WordPress test environment...\n";
        $installer->install(false, function (string $message): void {
            echo "  → {$message}\n";
        });
        echo "PestWP: Installation complete!\n\n";
    }

    // Load WordPress if installed
    if ($installer->isInstalled()) {
        loadWordPress($installer);
    }

    $bootstrapped = true;
}

/**
 * Load WordPress and the test suite.
 */
function loadWordPress(Installer $installer): void
{
    $wpDir = str_replace('\\', '/', $installer->getWordPressPath());
    $wpContent = $wpDir . '/wp-content';

    // Set up server context
    $_SERVER['HTTP_HOST'] = 'example.org';
    $_SERVER['SERVER_NAME'] = 'example.org';
    $_SERVER['SERVER_PORT'] = '80';
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTPS'] = '';

    // Initialize required globals
    $GLOBALS['wp_plugin_paths'] = [];

    // Define test constants BEFORE loading WordPress
    if (! defined('WP_USE_THEMES')) {
        define('WP_USE_THEMES', false);
    }
    if (! defined('WP_INSTALLING')) {
        define('WP_INSTALLING', true);
    }
    if (! defined('WP_DEBUG')) {
        define('WP_DEBUG', false);
    }
    if (! defined('WP_DEBUG_DISPLAY')) {
        define('WP_DEBUG_DISPLAY', false);
    }
    if (! defined('DOING_CRON')) {
        define('DOING_CRON', false);
    }
    if (! defined('DISALLOW_FILE_MODS')) {
        define('DISALLOW_FILE_MODS', true);
    }
    if (! defined('AUTOMATIC_UPDATER_DISABLED')) {
        define('AUTOMATIC_UPDATER_DISABLED', true);
    }

    // Path constants
    if (! defined('ABSPATH')) {
        define('ABSPATH', $wpDir . '/');
    }
    if (! defined('WP_CONTENT_DIR')) {
        define('WP_CONTENT_DIR', $wpContent);
    }
    if (! defined('WP_PLUGIN_DIR')) {
        define('WP_PLUGIN_DIR', $wpContent . '/plugins');
    }
    if (! defined('WPMU_PLUGIN_DIR')) {
        define('WPMU_PLUGIN_DIR', $wpContent . '/mu-plugins');
    }

    // Database constants (SQLite drop-in handles actual connection)
    if (! defined('DB_NAME')) {
        define('DB_NAME', 'wordpress_test');
    }
    if (! defined('DB_USER')) {
        define('DB_USER', '');
    }
    if (! defined('DB_PASSWORD')) {
        define('DB_PASSWORD', '');
    }
    if (! defined('DB_HOST')) {
        define('DB_HOST', '');
    }
    if (! defined('DB_CHARSET')) {
        define('DB_CHARSET', 'utf8mb4');
    }
    if (! defined('DB_COLLATE')) {
        define('DB_COLLATE', '');
    }

    // Table prefix - use a global variable that wp-settings.php expects
    $GLOBALS['table_prefix'] = 'wptests_';

    // Authentication keys (for test environment)
    if (! defined('AUTH_KEY')) {
        define('AUTH_KEY', 'test-auth-key');
    }
    if (! defined('SECURE_AUTH_KEY')) {
        define('SECURE_AUTH_KEY', 'test-secure-auth-key');
    }
    if (! defined('LOGGED_IN_KEY')) {
        define('LOGGED_IN_KEY', 'test-logged-in-key');
    }
    if (! defined('NONCE_KEY')) {
        define('NONCE_KEY', 'test-nonce-key');
    }
    if (! defined('AUTH_SALT')) {
        define('AUTH_SALT', 'test-auth-salt');
    }
    if (! defined('SECURE_AUTH_SALT')) {
        define('SECURE_AUTH_SALT', 'test-secure-auth-salt');
    }
    if (! defined('LOGGED_IN_SALT')) {
        define('LOGGED_IN_SALT', 'test-logged-in-salt');
    }
    if (! defined('NONCE_SALT')) {
        define('NONCE_SALT', 'test-nonce-salt');
    }

    // Suppress errors during bootstrap
    $originalErrorReporting = error_reporting();
    error_reporting(E_ERROR | E_PARSE);

    // Load WordPress
    ob_start();
    require_once ABSPATH . 'wp-settings.php';
    ob_end_clean();

    // Restore error reporting
    error_reporting($originalErrorReporting);

    // Ensure WordPress is installed
    ensureWordPressInstalled();

    // Mark TestCase as having WordPress loaded
    TestCase::markWordPressLoaded();
}

/**
 * Ensure WordPress database tables are installed.
 */
function ensureWordPressInstalled(): void
{
    global $wpdb;

    // Check if WordPress tables exist
    $tables = $wpdb->get_results(
        "SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '{$wpdb->prefix}%'",
        ARRAY_A,
    );

    if (empty($tables)) {
        // Include WordPress installation functions
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Suppress output during installation
        ob_start();
        wp_install(
            'Test Blog',
            'admin',
            'admin@example.org',
            true,
            '',
            'password123',
        );
        ob_end_clean();
    }
}

/**
 * Get the PestWP installer instance.
 *
 * @param  string|null  $basePath  The base path of the project
 */
function installer(?string $basePath = null): Installer
{
    static $installerInstance = null;

    if ($installerInstance === null) {
        $installerInstance = new Installer($basePath);
    }

    return $installerInstance;
}
