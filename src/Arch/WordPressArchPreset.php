<?php

declare(strict_types=1);

namespace PestWP\Arch;

/**
 * WordPress Architecture Preset.
 *
 * Provides WordPress-specific architecture testing presets that can be used
 * with Pest's arch() function to enforce coding standards and best practices.
 *
 * @example
 * ```php
 * arch('wordpress')
 *     ->expect('App')
 *     ->toUseWordPressPreset();
 *
 * arch('no debug functions')
 *     ->expect('App')
 *     ->not->toUseDebugFunctions();
 * ```
 */
final class WordPressArchPreset
{
    /**
     * Debug functions that should not be used in production code.
     *
     * @var array<int, string>
     */
    public const DEBUG_FUNCTIONS = [
        'dd',
        'dump',
        'var_dump',
        'print_r',
        'var_export',
        'debug_print_backtrace',
        'debug_backtrace',
        'error_log',
    ];

    /**
     * Deprecated MySQL functions removed in PHP 7+.
     *
     * @var array<int, string>
     */
    public const DEPRECATED_MYSQL_FUNCTIONS = [
        'mysql_connect',
        'mysql_query',
        'mysql_fetch_array',
        'mysql_fetch_assoc',
        'mysql_fetch_row',
        'mysql_num_rows',
        'mysql_real_escape_string',
        'mysql_escape_string',
        'mysql_close',
        'mysql_select_db',
        'mysql_affected_rows',
        'mysql_error',
        'mysql_insert_id',
        'mysql_result',
    ];

    /**
     * Security-sensitive functions that should be reviewed carefully.
     *
     * @var array<int, string>
     */
    public const SECURITY_SENSITIVE_FUNCTIONS = [
        'eval',
        'exec',
        'shell_exec',
        'system',
        'passthru',
        'popen',
        'proc_open',
        'pcntl_exec',
        'extract',
        'parse_str', // without second argument
        'unserialize',
        'assert',
    ];

    /**
     * Functions that bypass WordPress escaping.
     *
     * @var array<int, string>
     */
    public const UNESCAPED_OUTPUT_FUNCTIONS = [
        'echo',
        'print',
        'printf',
        'vprintf',
    ];

    /**
     * WordPress escaping functions that should be used.
     *
     * @var array<int, string>
     */
    public const ESCAPING_FUNCTIONS = [
        'esc_html',
        'esc_attr',
        'esc_url',
        'esc_js',
        'esc_textarea',
        'esc_html__',
        'esc_html_e',
        'esc_attr__',
        'esc_attr_e',
        'wp_kses',
        'wp_kses_post',
        'wp_kses_data',
    ];

    /**
     * WordPress sanitization functions.
     *
     * @var array<int, string>
     */
    public const SANITIZATION_FUNCTIONS = [
        'sanitize_text_field',
        'sanitize_textarea_field',
        'sanitize_email',
        'sanitize_file_name',
        'sanitize_html_class',
        'sanitize_key',
        'sanitize_meta',
        'sanitize_mime_type',
        'sanitize_option',
        'sanitize_sql_orderby',
        'sanitize_title',
        'sanitize_title_for_query',
        'sanitize_title_with_dashes',
        'sanitize_user',
        'sanitize_url',
        'wp_kses',
        'wp_kses_post',
        'absint',
        'intval',
        'floatval',
        'wp_unslash',
    ];

    /**
     * Direct file operations that should use WordPress Filesystem API.
     *
     * @var array<int, string>
     */
    public const DIRECT_FILE_FUNCTIONS = [
        'file_put_contents',
        'file_get_contents',
        'fopen',
        'fwrite',
        'fread',
        'fclose',
        'readfile',
        'file',
        'mkdir',
        'rmdir',
        'unlink',
        'rename',
        'copy',
        'move_uploaded_file',
        'chmod',
        'chown',
        'chgrp',
    ];

    /**
     * Direct database functions that should use $wpdb.
     *
     * @var array<int, string>
     */
    public const DIRECT_DATABASE_FUNCTIONS = [
        'mysqli_connect',
        'mysqli_query',
        'mysqli_fetch_array',
        'mysqli_fetch_assoc',
        'mysqli_real_escape_string',
        'PDO::query',
    ];

    /**
     * WordPress functions that are deprecated.
     *
     * @var array<string, string>
     */
    public const DEPRECATED_WP_FUNCTIONS = [
        'get_bloginfo' => "get_bloginfo('wpurl') is deprecated, use home_url() or site_url()",
        'bloginfo' => "bloginfo('wpurl') is deprecated, use home_url() or site_url()",
        'get_currentuserinfo' => 'get_currentuserinfo() is deprecated since 4.5, use wp_get_current_user()',
        'get_userdatabylogin' => 'get_userdatabylogin() is deprecated since 3.3, use get_user_by()',
        'get_user_by_email' => "get_user_by_email() is deprecated since 3.3, use get_user_by('email')",
        'is_comments_popup' => 'is_comments_popup() is deprecated since 4.5',
        'link_pages' => 'link_pages() is deprecated since 2.1, use wp_link_pages()',
        'user_pass_ok' => 'user_pass_ok() is deprecated since 3.0, use wp_authenticate()',
        'wp_get_single_post' => 'wp_get_single_post() is deprecated since 3.5, use get_post()',
        'wp_setcookie' => 'wp_setcookie() is deprecated since 2.5, use wp_set_auth_cookie()',
        'wp_get_cookie_login' => 'wp_get_cookie_login() is deprecated since 2.5',
        'the_editor' => 'the_editor() is deprecated since 3.3, use wp_editor()',
        'get_the_author_email' => 'get_the_author_email() is deprecated since 2.8, use get_the_author_meta()',
        'get_the_author_login' => 'get_the_author_login() is deprecated since 2.8, use get_the_author_meta()',
    ];

    /**
     * Patterns indicating direct superglobal access without sanitization.
     *
     * @var array<int, string>
     */
    public const SUPERGLOBAL_PATTERNS = [
        '$_GET',
        '$_POST',
        '$_REQUEST',
        '$_SERVER',
        '$_FILES',
        '$_COOKIE',
    ];

    /**
     * Get all debug functions.
     *
     * @return array<int, string>
     */
    public static function getDebugFunctions(): array
    {
        return self::DEBUG_FUNCTIONS;
    }

    /**
     * Get all security-sensitive functions.
     *
     * @return array<int, string>
     */
    public static function getSecuritySensitiveFunctions(): array
    {
        return self::SECURITY_SENSITIVE_FUNCTIONS;
    }

    /**
     * Get all deprecated MySQL functions.
     *
     * @return array<int, string>
     */
    public static function getDeprecatedMySQLFunctions(): array
    {
        return self::DEPRECATED_MYSQL_FUNCTIONS;
    }

    /**
     * Get all direct file operation functions.
     *
     * @return array<int, string>
     */
    public static function getDirectFileFunctions(): array
    {
        return self::DIRECT_FILE_FUNCTIONS;
    }

    /**
     * Get all WordPress sanitization functions.
     *
     * @return array<int, string>
     */
    public static function getSanitizationFunctions(): array
    {
        return self::SANITIZATION_FUNCTIONS;
    }

    /**
     * Get all WordPress escaping functions.
     *
     * @return array<int, string>
     */
    public static function getEscapingFunctions(): array
    {
        return self::ESCAPING_FUNCTIONS;
    }

    /**
     * Get all forbidden functions (debug + deprecated MySQL + security-sensitive).
     *
     * @return array<int, string>
     */
    public static function getAllForbiddenFunctions(): array
    {
        return array_merge(
            self::DEBUG_FUNCTIONS,
            self::DEPRECATED_MYSQL_FUNCTIONS,
            self::SECURITY_SENSITIVE_FUNCTIONS,
        );
    }

    /**
     * Check if a function is a debug function.
     */
    public static function isDebugFunction(string $function): bool
    {
        return in_array($function, self::DEBUG_FUNCTIONS, true);
    }

    /**
     * Check if a function is security-sensitive.
     */
    public static function isSecuritySensitive(string $function): bool
    {
        return in_array($function, self::SECURITY_SENSITIVE_FUNCTIONS, true);
    }

    /**
     * Check if a function is a direct file operation.
     */
    public static function isDirectFileFunction(string $function): bool
    {
        return in_array($function, self::DIRECT_FILE_FUNCTIONS, true);
    }

    /**
     * Check if a function is a deprecated WordPress function.
     */
    public static function isDeprecatedWordPressFunction(string $function): bool
    {
        return isset(self::DEPRECATED_WP_FUNCTIONS[$function]);
    }

    /**
     * Get the deprecation message for a WordPress function.
     */
    public static function getDeprecationMessage(string $function): ?string
    {
        return self::DEPRECATED_WP_FUNCTIONS[$function] ?? null;
    }
}
