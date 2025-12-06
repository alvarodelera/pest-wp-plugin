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
    // Define constants
    if (! defined('WP_TESTS_CONFIG_FILE_PATH')) {
        define('WP_TESTS_CONFIG_FILE_PATH', $installer->getConfigPath());
    }

    // Load the test config
    if (file_exists($installer->getConfigPath())) {
        require_once $installer->getConfigPath();
    }

    // Try to load WordPress test bootstrap
    $wpTestsDir = $installer->getTestLibraryPath();
    $wpTestsBootstrap = $wpTestsDir . '/includes/bootstrap.php';

    if (file_exists($wpTestsBootstrap)) {
        // Set up WordPress test environment
        $_SERVER['SERVER_NAME'] = 'example.org';
        $_SERVER['HTTP_HOST'] = 'example.org';

        // Load WordPress test suite
        require_once $wpTestsBootstrap;
    } else {
        // Fallback: load WordPress directly
        $wpLoadPath = $installer->getWordPressPath() . '/wp-load.php';

        if (file_exists($wpLoadPath)) {
            // Suppress any output during WordPress load
            ob_start();
            require_once $wpLoadPath;
            ob_end_clean();
        }
    }

    // Mark TestCase as having WordPress loaded
    TestCase::markWordPressLoaded();
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
