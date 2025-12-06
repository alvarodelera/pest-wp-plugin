<?php

declare(strict_types=1);

namespace PestWP;

/**
 * Configuration class for PestWP.
 *
 * This class allows users to configure the WordPress test environment
 * to load their plugins, themes, and custom setup.
 *
 * Usage in tests/Pest.php:
 *
 * ```php
 * use PestWP\Config;
 *
 * Config::plugins([
 *     dirname(__DIR__) . '/my-plugin.php',        // Single-file plugin
 *     dirname(__DIR__) . '/my-plugin/plugin.php', // Plugin in folder
 * ]);
 *
 * Config::theme('my-theme');
 *
 * Config::beforeWordPress(function () {
 *     // Code to run BEFORE WordPress loads
 *     define('MY_PLUGIN_DEBUG', true);
 * });
 *
 * Config::afterWordPress(function () {
 *     // Code to run AFTER WordPress loads
 *     update_option('my_plugin_setting', 'test_value');
 * });
 * ```
 */
final class Config
{
    /**
     * Plugins to load during tests.
     *
     * @var array<string>
     */
    private static array $plugins = [];

    /**
     * MU-Plugins to load during tests.
     *
     * @var array<string>
     */
    private static array $muPlugins = [];

    /**
     * Active theme slug.
     */
    private static ?string $theme = null;

    /**
     * Callbacks to run before WordPress loads.
     *
     * @var array<callable>
     */
    private static array $beforeCallbacks = [];

    /**
     * Callbacks to run after WordPress loads.
     *
     * @var array<callable>
     */
    private static array $afterCallbacks = [];

    /**
     * Whether configuration has been applied.
     */
    private static bool $applied = false;

    /**
     * Register plugins to be loaded during tests.
     *
     * @param  array<string>|string  $plugins  Plugin file paths (absolute paths)
     *
     * Example:
     * ```php
     * Config::plugins([
     *     dirname(__DIR__) . '/my-plugin.php',
     *     dirname(__DIR__) . '/my-plugin/my-plugin.php',
     * ]);
     * ```
     */
    public static function plugins(array|string $plugins): void
    {
        $plugins = is_array($plugins) ? $plugins : [$plugins];

        foreach ($plugins as $plugin) {
            if (! in_array($plugin, self::$plugins, true)) {
                self::$plugins[] = $plugin;
            }
        }
    }

    /**
     * Register MU-plugins to be loaded during tests.
     *
     * @param  array<string>|string  $plugins  MU-Plugin file paths (absolute paths)
     */
    public static function muPlugins(array|string $plugins): void
    {
        $plugins = is_array($plugins) ? $plugins : [$plugins];

        foreach ($plugins as $plugin) {
            if (! in_array($plugin, self::$muPlugins, true)) {
                self::$muPlugins[] = $plugin;
            }
        }
    }

    /**
     * Set the active theme for tests.
     *
     * @param  string  $theme  Theme directory name
     *
     * Example:
     * ```php
     * Config::theme('twentytwentyfour');
     * ```
     */
    public static function theme(string $theme): void
    {
        self::$theme = $theme;
    }

    /**
     * Register a callback to run BEFORE WordPress loads.
     *
     * Useful for defining constants that affect WordPress behavior.
     *
     * Example:
     * ```php
     * Config::beforeWordPress(function () {
     *     define('WP_DEBUG', true);
     *     define('MY_PLUGIN_DEBUG', true);
     * });
     * ```
     */
    public static function beforeWordPress(callable $callback): void
    {
        self::$beforeCallbacks[] = $callback;
    }

    /**
     * Register a callback to run AFTER WordPress loads.
     *
     * Useful for setting up test data, options, etc.
     *
     * Example:
     * ```php
     * Config::afterWordPress(function () {
     *     update_option('my_plugin_option', 'test_value');
     *     add_filter('my_plugin_filter', fn() => true);
     * });
     * ```
     */
    public static function afterWordPress(callable $callback): void
    {
        self::$afterCallbacks[] = $callback;
    }

    /**
     * Get registered plugins.
     *
     * @return array<string>
     */
    public static function getPlugins(): array
    {
        return self::$plugins;
    }

    /**
     * Get registered MU-plugins.
     *
     * @return array<string>
     */
    public static function getMuPlugins(): array
    {
        return self::$muPlugins;
    }

    /**
     * Get the active theme.
     */
    public static function getTheme(): ?string
    {
        return self::$theme;
    }

    /**
     * Execute before-WordPress callbacks.
     *
     * @internal
     */
    public static function executeBeforeCallbacks(): void
    {
        foreach (self::$beforeCallbacks as $callback) {
            $callback();
        }
    }

    /**
     * Execute after-WordPress callbacks.
     *
     * @internal
     */
    public static function executeAfterCallbacks(): void
    {
        foreach (self::$afterCallbacks as $callback) {
            $callback();
        }
    }

    /**
     * Load configured plugins into WordPress.
     *
     * @internal
     */
    public static function loadPlugins(): void
    {
        // Load MU-plugins first
        foreach (self::$muPlugins as $plugin) {
            if (file_exists($plugin)) {
                require_once $plugin;
            }
        }

        // Load regular plugins
        foreach (self::$plugins as $plugin) {
            if (file_exists($plugin)) {
                require_once $plugin;

                // Fire activation hook if plugin defines one
                $pluginBasename = basename(dirname($plugin)) . '/' . basename($plugin);
                do_action('activate_' . $pluginBasename);
            }
        }
    }

    /**
     * Apply theme configuration.
     *
     * @internal
     */
    public static function applyTheme(): void
    {
        if (self::$theme !== null) {
            switch_theme(self::$theme);
        }
    }

    /**
     * Check if configuration has been applied.
     */
    public static function isApplied(): bool
    {
        return self::$applied;
    }

    /**
     * Mark configuration as applied.
     *
     * @internal
     */
    public static function markApplied(): void
    {
        self::$applied = true;
    }

    /**
     * Reset configuration (useful for testing).
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$plugins = [];
        self::$muPlugins = [];
        self::$theme = null;
        self::$beforeCallbacks = [];
        self::$afterCallbacks = [];
        self::$applied = false;
    }

    /**
     * Check if any configuration has been set.
     */
    public static function hasConfiguration(): bool
    {
        return ! empty(self::$plugins)
            || ! empty(self::$muPlugins)
            || self::$theme !== null
            || ! empty(self::$beforeCallbacks)
            || ! empty(self::$afterCallbacks);
    }
}
