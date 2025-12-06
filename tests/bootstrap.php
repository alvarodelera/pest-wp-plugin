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

// WordPress path configuration - use forward slashes
$wpDir = str_replace('\\', '/', $pestDir . '/wordpress');
$wpContent = $wpDir . '/wp-content';

// Define test constants BEFORE loading WordPress
// These MUST be defined before ABSPATH for them to take effect
define('WP_USE_THEMES', false);
define('WP_INSTALLING', true);
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
define('DOING_CRON', false);
define('DISALLOW_FILE_MODS', true);
define('AUTOMATIC_UPDATER_DISABLED', true);

// Define content directories BEFORE loading WordPress
// Note: WP_CONTENT_DIR is normally relative to ABSPATH, but we need to set it first
define('WP_CONTENT_DIR', $wpContent);
define('WP_PLUGIN_DIR', $wpContent . '/plugins');
define('WPMU_PLUGIN_DIR', $wpContent . '/mu-plugins');

// Set the table prefix GLOBALLY before loading WordPress
// This is CRITICAL - wp-config.php will read it from $GLOBALS
$GLOBALS['table_prefix'] = 'wptests_';

// Load WordPress through wp-load.php
// This will:
// 1. Define ABSPATH if not defined
// 2. Load wp-config.php which sets $table_prefix
// 3. wp-config.php then loads wp-settings.php
ob_start();
require_once $wpDir . '/wp-load.php';
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

// Note: Database isolation is handled per-test via SAVEPOINT/ROLLBACK
// See tests/Pest.php for the beforeEach/afterEach hooks that call
// TransactionManager::createSavepoint() and ::rollbackToSavepoint()
