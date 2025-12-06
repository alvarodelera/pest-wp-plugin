<?php

declare(strict_types=1);

namespace PestWP\Functions;

/**
 * Get the browser configuration from tests/Pest.php or environment.
 *
 * @return array{base_url: string, admin_user: string, admin_password: string}
 */
function getBrowserConfig(): array
{
    if (function_exists('browser')) {
        /** @var array{base_url: string, admin_user: string, admin_password: string} $config */
        $config = browser();

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
 * Path to stored browser auth state (if used).
 */
function getStorageStatePath(): string
{
    $projectRoot = dirname(__DIR__, 2);

    return $projectRoot . '/.pest/state/admin.json';
}

/**
 * Check if a stored browser auth state exists.
 */
function hasBrowserAuthState(): bool
{
    return file_exists(getStorageStatePath());
}
