<?php

declare(strict_types=1);
/**
 * PestWP Test Bootstrap
 *
 * This file should be used as the bootstrap for phpunit.xml
 * It loads WordPress with SQLite in a way that's compatible with PHPUnit/Pest.
 */

// Load Composer autoloader
$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/vendor/autoload.php';

// Check if WordPress needs to be installed
$pestDir = $projectRoot . '/.pest';
$installer = new \PestWP\Installer\Installer($projectRoot);

if (! $installer->isInstalled()) {
    echo "PestWP: Installing WordPress test environment...\n";
    $installer->install(false, function (string $message): void {
        echo "  → {$message}\n";
    });
    echo "PestWP: Installation complete!\n\n";
}

// Suppress errors during bootstrap
error_reporting(E_ERROR | E_PARSE);

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

// WordPress path configuration
$wpDir = str_replace('\\', '/', $pestDir . '/wordpress');
$wpContent = $wpDir . '/wp-content';

// Define test constants BEFORE loading WordPress
define('WP_USE_THEMES', false);
define('WP_INSTALLING', true);
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
define('DOING_CRON', false);
define('DISALLOW_FILE_MODS', true);
define('AUTOMATIC_UPDATER_DISABLED', true);

// Path constants
define('ABSPATH', $wpDir . '/');
define('WP_CONTENT_DIR', $wpContent);
define('WP_PLUGIN_DIR', $wpContent . '/plugins');
define('WPMU_PLUGIN_DIR', $wpContent . '/mu-plugins');

// Database constants (SQLite drop-in handles actual connection)
define('DB_NAME', 'wordpress_test');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_HOST', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// Table prefix - WordPress expects this as a variable
$table_prefix = 'wptests_';

// Authentication keys (for test environment)
define('AUTH_KEY', 'test-auth-key');
define('SECURE_AUTH_KEY', 'test-secure-auth-key');
define('LOGGED_IN_KEY', 'test-logged-in-key');
define('NONCE_KEY', 'test-nonce-key');
define('AUTH_SALT', 'test-auth-salt');
define('SECURE_AUTH_SALT', 'test-secure-auth-salt');
define('LOGGED_IN_SALT', 'test-logged-in-salt');
define('NONCE_SALT', 'test-nonce-salt');

// Load WordPress
ob_start();
require_once ABSPATH . 'wp-settings.php';
ob_end_clean();

// Restore error reporting
error_reporting(E_ALL);

// Ensure WordPress is installed
global $wpdb;

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

// Mark TestCase as having WordPress loaded
\PestWP\TestCase::markWordPressLoaded();
