<?php

declare(strict_types=1);

/**
 * Nonce and security helper functions for testing.
 *
 * Provides utilities for generating and validating nonces in tests.
 */

namespace PestWP\Functions;

use RuntimeException;

/**
 * Create a WordPress nonce for testing.
 *
 * @param string $action The nonce action name
 * @return string The generated nonce
 * @throws RuntimeException If WordPress is not available
 */
function createNonce(string $action = '-1'): string
{
    if (! function_exists('wp_create_nonce')) {
        throw new RuntimeException('WordPress must be loaded to create nonces');
    }

    return wp_create_nonce($action);
}

/**
 * Verify a WordPress nonce.
 *
 * @param string $nonce The nonce value to verify
 * @param string $action The nonce action name
 * @return bool|int False if invalid, 1 if valid and generated 0-12 hours ago, 2 if valid and generated 12-24 hours ago
 * @throws RuntimeException If WordPress is not available
 */
function verifyNonce(string $nonce, string $action = '-1'): bool|int
{
    if (! function_exists('wp_verify_nonce')) {
        throw new RuntimeException('WordPress must be loaded to verify nonces');
    }

    return wp_verify_nonce($nonce, $action);
}

/**
 * Create a REST API nonce.
 *
 * This creates a nonce for the 'wp_rest' action, which is the default
 * action used by the WordPress REST API.
 *
 * @return string The REST API nonce
 * @throws RuntimeException If WordPress is not available
 */
function createRestNonce(): string
{
    return createNonce('wp_rest');
}

/**
 * Create an AJAX referer field for testing.
 *
 * This simulates what wp_nonce_field() would output for AJAX testing.
 *
 * @param string $action The nonce action
 * @param string $name The name attribute for the nonce field (default: '_wpnonce')
 * @return array{nonce: string, referer: string} Array with nonce and referer values
 * @throws RuntimeException If WordPress is not available
 */
function createAjaxReferer(string $action, string $name = '_wpnonce'): array
{
    if (! function_exists('wp_create_nonce')) {
        throw new RuntimeException('WordPress must be loaded to create AJAX referer');
    }

    return [
        'nonce' => wp_create_nonce($action),
        'referer' => admin_url(),
    ];
}

/**
 * Create a nonce URL for testing.
 *
 * @param string $actionUrl The URL to add nonce to
 * @param string $action The nonce action
 * @param string $name The query arg name for the nonce (default: '_wpnonce')
 * @return string The URL with nonce added
 * @throws RuntimeException If WordPress is not available
 */
function createNonceUrl(string $actionUrl, string $action = '-1', string $name = '_wpnonce'): string
{
    if (! function_exists('wp_nonce_url')) {
        throw new RuntimeException('WordPress must be loaded to create nonce URLs');
    }

    return wp_nonce_url($actionUrl, $action, $name);
}

/**
 * Check if a nonce action is valid for the current user.
 *
 * This is useful for testing permission-based nonce validation.
 *
 * @param string $action The action to check
 * @param string $queryArg The query arg name to check in $_REQUEST (default: '_wpnonce')
 * @return bool|int False if invalid, 1 or 2 if valid
 * @throws RuntimeException If WordPress is not available
 */
function checkAjaxReferer(string $action, string $queryArg = '_wpnonce'): bool|int
{
    if (! function_exists('check_ajax_referer')) {
        throw new RuntimeException('WordPress must be loaded to check AJAX referer');
    }

    // Set up $_REQUEST with the nonce if testing
    if (! isset($_REQUEST[$queryArg])) {
        $_REQUEST[$queryArg] = createNonce($action);
    }

    return check_ajax_referer($action, $queryArg, false);
}

/**
 * Check admin referer for testing.
 *
 * @param string $action The action name
 * @param string $queryArg The query arg name (default: '_wpnonce')
 * @return bool|int False if invalid, 1 or 2 if valid
 * @throws RuntimeException If WordPress is not available
 */
function checkAdminReferer(string $action, string $queryArg = '_wpnonce'): bool|int
{
    if (! function_exists('check_admin_referer')) {
        throw new RuntimeException('WordPress must be loaded to check admin referer');
    }

    return check_admin_referer($action, $queryArg);
}
