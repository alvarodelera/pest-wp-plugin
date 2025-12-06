<?php

declare(strict_types=1);

namespace PestWP\Presets;

/**
 * WordPress Code Quality Preset.
 *
 * This preset defines rules for detecting bad practices in WordPress code.
 * It can be used with PHPStan or as standalone analysis.
 */
final class WordPressPreset
{
    /**
     * Forbidden functions that should never be used in production code.
     *
     * @var array<string, string>
     */
    public const FORBIDDEN_FUNCTIONS = [
        'dd' => 'Use proper logging instead of dd(). Remove before production.',
        'dump' => 'Use proper logging instead of dump(). Remove before production.',
        'var_dump' => 'Use proper logging instead of var_dump(). Remove before production.',
        'print_r' => 'Use proper logging instead of print_r(). Remove before production.',
        'var_export' => 'Use proper logging instead of var_export() for debugging. Remove before production.',
        'error_log' => 'Consider using a proper logging library instead of error_log().',
    ];

    /**
     * Deprecated MySQL functions that should not be used.
     *
     * @var array<string, string>
     */
    public const DEPRECATED_MYSQL_FUNCTIONS = [
        'mysql_connect' => 'mysql_* functions are removed in PHP 7+. Use $wpdb or PDO instead.',
        'mysql_query' => 'mysql_* functions are removed in PHP 7+. Use $wpdb or PDO instead.',
        'mysql_fetch_array' => 'mysql_* functions are removed in PHP 7+. Use $wpdb or PDO instead.',
        'mysql_fetch_assoc' => 'mysql_* functions are removed in PHP 7+. Use $wpdb or PDO instead.',
        'mysql_fetch_row' => 'mysql_* functions are removed in PHP 7+. Use $wpdb or PDO instead.',
        'mysql_num_rows' => 'mysql_* functions are removed in PHP 7+. Use $wpdb or PDO instead.',
        'mysql_real_escape_string' => 'mysql_* functions are removed in PHP 7+. Use $wpdb->prepare() instead.',
        'mysql_escape_string' => 'mysql_* functions are removed in PHP 7+. Use $wpdb->prepare() instead.',
        'mysql_close' => 'mysql_* functions are removed in PHP 7+. Use $wpdb or PDO instead.',
        'mysql_select_db' => 'mysql_* functions are removed in PHP 7+. Use $wpdb or PDO instead.',
    ];

    /**
     * Discouraged patterns in WordPress code.
     *
     * @var array<string, string>
     */
    public const DISCOURAGED_PATTERNS = [
        'global $wpdb' => 'Avoid using global $wpdb directly. Consider dependency injection or helper functions.',
        'global $wp_query' => 'Avoid using global $wp_query. Use get_queried_object() or pass as parameter.',
        'global $post' => 'Avoid using global $post. Use get_post() or pass as parameter.',
        'global $current_user' => 'Avoid using global $current_user. Use wp_get_current_user() instead.',
        '$GLOBALS[' => 'Avoid using $GLOBALS. Use proper dependency injection instead.',
    ];

    /**
     * Security-sensitive functions that need careful review.
     *
     * @var array<string, string>
     */
    public const SECURITY_SENSITIVE = [
        'eval' => 'eval() is dangerous and should be avoided. Consider alternatives.',
        'exec' => 'exec() can be dangerous. Ensure proper sanitization and validation.',
        'shell_exec' => 'shell_exec() can be dangerous. Ensure proper sanitization and validation.',
        'system' => 'system() can be dangerous. Ensure proper sanitization and validation.',
        'passthru' => 'passthru() can be dangerous. Ensure proper sanitization and validation.',
        'unserialize' => 'unserialize() can be dangerous with untrusted data. Use maybe_unserialize() in WP.',
        'extract' => 'extract() makes code hard to read and can cause variable injection. Avoid it.',
    ];

    /**
     * WordPress functions that should use their sanitized counterparts.
     *
     * @var array<string, string>
     */
    public const USE_SANITIZED_FUNCTIONS = [
        '$_GET' => 'Use sanitize_text_field($_GET[...]) or wp_unslash() for GET data.',
        '$_POST' => 'Use sanitize_text_field($_POST[...]) or wp_unslash() for POST data.',
        '$_REQUEST' => 'Use sanitize_text_field($_REQUEST[...]) for REQUEST data.',
        '$_SERVER' => 'Sanitize $_SERVER data before use with sanitize_text_field() or esc_url_raw().',
    ];

    /**
     * Get all forbidden functions.
     *
     * @return array<string, string>
     */
    public static function getForbiddenFunctions(): array
    {
        return array_merge(
            self::FORBIDDEN_FUNCTIONS,
            self::DEPRECATED_MYSQL_FUNCTIONS,
        );
    }

    /**
     * Get all patterns to check.
     *
     * @return array<string, string>
     */
    public static function getAllPatterns(): array
    {
        return array_merge(
            self::FORBIDDEN_FUNCTIONS,
            self::DEPRECATED_MYSQL_FUNCTIONS,
            self::DISCOURAGED_PATTERNS,
            self::SECURITY_SENSITIVE,
            self::USE_SANITIZED_FUNCTIONS,
        );
    }

    /**
     * Check if a function call is forbidden.
     */
    public static function isForbidden(string $functionName): bool
    {
        return isset(self::FORBIDDEN_FUNCTIONS[$functionName])
            || isset(self::DEPRECATED_MYSQL_FUNCTIONS[$functionName]);
    }

    /**
     * Get the message for a forbidden function.
     */
    public static function getMessage(string $pattern): ?string
    {
        return self::getAllPatterns()[$pattern] ?? null;
    }
}
