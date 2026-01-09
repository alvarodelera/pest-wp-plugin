<?php

declare(strict_types=1);

/**
 * Authentication helper functions for testing user login/logout functionality.
 *
 * These functions simulate user authentication in a test environment,
 * making it easy to test permission-dependent features.
 */

namespace PestWP;

use RuntimeException;
use WP_User;

/**
 * Log in as a specific user for testing.
 *
 * This sets the current user in WordPress, simulating authentication.
 * Useful for testing permission-dependent functionality.
 *
 * @param  int|WP_User  $user  User ID or WP_User object
 * @return WP_User The logged-in user object
 *
 * @throws RuntimeException If WordPress is not loaded or the user doesn't exist
 */
function loginAs(int|WP_User $user): WP_User
{
    if (! function_exists('wp_set_current_user')) {
        throw new RuntimeException('WordPress must be loaded to use loginAs()');
    }

    $userId = $user instanceof WP_User ? $user->ID : $user;

    $wpUser = wp_set_current_user($userId);

    if (! $wpUser instanceof WP_User || $wpUser->ID === 0) {
        throw new RuntimeException("User with ID $userId does not exist");
    }

    // Set auth cookie data for the user (simulated - doesn't set actual cookies in CLI)
    // This ensures functions like is_user_logged_in() work correctly
    wp_set_auth_cookie($userId);

    return $wpUser;
}

/**
 * Log out the current user.
 *
 * This clears the current user and simulates logging out.
 *
 * @throws RuntimeException If WordPress is not loaded
 */
function logout(): void
{
    if (! function_exists('wp_set_current_user')) {
        throw new RuntimeException('WordPress must be loaded to use logout()');
    }

    wp_set_current_user(0);
    wp_clear_auth_cookie();
}

/**
 * Get the currently logged-in user.
 *
 * This is a convenience wrapper around wp_get_current_user().
 *
 * @return WP_User The current user object (ID 0 if not logged in)
 *
 * @throws RuntimeException If WordPress is not loaded
 */
function currentUser(): WP_User
{
    if (! function_exists('wp_get_current_user')) {
        throw new RuntimeException('WordPress must be loaded to use currentUser()');
    }

    return wp_get_current_user();
}

/**
 * Check if a user is logged in.
 *
 * @return bool True if a user is logged in, false otherwise
 *
 * @throws RuntimeException If WordPress is not loaded
 */
function isUserLoggedIn(): bool
{
    if (! function_exists('is_user_logged_in')) {
        throw new RuntimeException('WordPress must be loaded to use isUserLoggedIn()');
    }

    return is_user_logged_in();
}
