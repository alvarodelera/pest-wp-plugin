<?php

declare(strict_types=1);

namespace PestWP\Functions;

use PestWP\Browser\AuthStateManager;

/**
 * Get the browser configuration from tests/Pest.php or environment.
 *
 * @return array{base_url: string, admin_user: string, admin_password: string}
 */
function getBrowserConfig(): array
{
    // The browser() function is defined by users in tests/Pest.php
    if (function_exists('browser')) {
        /** @var callable $browserFunc */ // @phpstan-ignore varTag.nativeType
        $browserFunc = 'browser';
        /** @var array{base_url: string, admin_user: string, admin_password: string} $config */
        $config = $browserFunc();

        return $config;
    }

    $baseUrl = $_ENV['WP_BASE_URL'] ?? getenv('WP_BASE_URL');
    $adminUser = $_ENV['WP_ADMIN_USER'] ?? getenv('WP_ADMIN_USER');
    $adminPassword = $_ENV['WP_ADMIN_PASSWORD'] ?? getenv('WP_ADMIN_PASSWORD');

    return [
        'base_url' => is_string($baseUrl) && $baseUrl !== '' ? $baseUrl : 'http://localhost:8080',
        'admin_user' => is_string($adminUser) && $adminUser !== '' ? $adminUser : 'admin',
        'admin_password' => is_string($adminPassword) && $adminPassword !== '' ? $adminPassword : 'password',
    ];
}

/**
 * Get the AuthStateManager instance.
 */
function getAuthStateManager(): AuthStateManager
{
    static $manager = null;

    if ($manager === null) {
        $manager = new AuthStateManager();
    }

    return $manager;
}

/**
 * Path to the stored browser auth state directory.
 */
function getStorageStatePath(): string
{
    return getAuthStateManager()->getStatePath();
}

/**
 * Path to a specific stored browser auth state file.
 *
 * @param string $name The name of the state (default: 'admin')
 */
function getStorageStateFilePath(string $name = 'admin'): string
{
    return getAuthStateManager()->getStateFilePath($name);
}

/**
 * Check if a stored browser auth state exists and is valid (not expired).
 *
 * @param string $name The name of the state (default: 'admin')
 */
function hasBrowserAuthState(string $name = 'admin'): bool
{
    return getAuthStateManager()->hasValidState($name);
}

/**
 * Save browser authentication state for later reuse.
 *
 * @param array<string, mixed> $state The browser state (cookies, localStorage, etc.)
 * @param string $name The name of the state (default: 'admin')
 */
function saveBrowserAuthState(array $state, string $name = 'admin'): bool
{
    return getAuthStateManager()->saveState($state, $name);
}

/**
 * Load previously saved browser authentication state.
 *
 * @param string $name The name of the state (default: 'admin')
 * @return array<string, mixed>|null The state data, or null if not found/expired
 */
function loadBrowserAuthState(string $name = 'admin'): ?array
{
    return getAuthStateManager()->loadState($name);
}

/**
 * Clear stored browser authentication state.
 *
 * @param string $name The name of the state to clear (default: 'admin')
 */
function clearBrowserAuthState(string $name = 'admin'): bool
{
    return getAuthStateManager()->deleteState($name);
}

/**
 * Clear all stored browser authentication states.
 */
function clearAllBrowserAuthStates(): void
{
    getAuthStateManager()->clearAllStates();
}

/**
 * Get information about stored auth state.
 *
 * @param string $name The name of the state (default: 'admin')
 * @return array{exists: bool, created_at: int|null, expires_at: int|null, is_expired: bool, file_path: string}
 */
function getBrowserAuthStateInfo(string $name = 'admin'): array
{
    return getAuthStateManager()->getStateInfo($name);
}

/**
 * Create a browser state structure from cookies.
 *
 * This is useful for manually creating state from WordPress cookies.
 *
 * @param string $baseUrl The WordPress site URL
 * @param array<string, string> $cookies WordPress cookies
 * @param array<string, mixed> $localStorage Optional localStorage data
 * @return array{cookies: array<int, array{name: string, value: string, domain: string, path: string, expires: int, httpOnly: bool, secure: bool, sameSite: string}>, origins: array<int, array{origin: string, localStorage: array<int, array{name: string, value: string}>}>}
 */
function createBrowserState(string $baseUrl, array $cookies, array $localStorage = []): array
{
    $manager = getAuthStateManager();
    $formattedCookies = $manager->formatCookiesForBrowser($baseUrl, $cookies);

    return $manager->createBrowserState($formattedCookies, $localStorage, $baseUrl);
}
