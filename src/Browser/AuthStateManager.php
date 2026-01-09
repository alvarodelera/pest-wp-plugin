<?php

declare(strict_types=1);

namespace PestWP\Browser;

use RuntimeException;

use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;
use function time;

/**
 * Manages browser authentication state for zero-login testing.
 *
 * This class handles saving and loading browser storage state (cookies, localStorage)
 * to enable tests to skip the login process and directly access authenticated pages.
 */
class AuthStateManager
{
    private string $statePath;

    private int $maxAge;

    /**
     * @param string|null $statePath Path to store auth state (defaults to .pest/state/)
     * @param int $maxAge Maximum age of stored state in seconds (default: 1 hour)
     */
    public function __construct(?string $statePath = null, int $maxAge = 3600)
    {
        $basePath = getcwd() ?: dirname(__DIR__, 2);
        $this->statePath = $statePath ?? $basePath . '/.pest/state';
        $this->maxAge = $maxAge;
    }

    /**
     * Get the path to the state directory.
     */
    public function getStatePath(): string
    {
        return $this->statePath;
    }

    /**
     * Get the path to a specific state file.
     */
    public function getStateFilePath(string $name = 'admin'): string
    {
        return $this->statePath . '/' . $name . '.json';
    }

    /**
     * Ensure the state directory exists.
     */
    public function ensureStateDirectory(): void
    {
        if (! is_dir($this->statePath)) {
            if (! mkdir($this->statePath, 0755, true)) {
                throw new RuntimeException("Failed to create state directory: {$this->statePath}");
            }
        }
    }

    /**
     * Save authentication state.
     *
     * @param array<string, mixed> $state The state data to save (cookies, localStorage, etc.)
     * @param string $name The name of the state file (default: 'admin')
     */
    public function saveState(array $state, string $name = 'admin'): bool
    {
        $this->ensureStateDirectory();

        $stateWithMeta = [
            'created_at' => time(),
            'expires_at' => time() + $this->maxAge,
            'state' => $state,
        ];

        $json = json_encode($stateWithMeta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException('Failed to encode state to JSON');
        }

        $result = file_put_contents($this->getStateFilePath($name), $json);

        return $result !== false;
    }

    /**
     * Load authentication state.
     *
     * @param string $name The name of the state file (default: 'admin')
     * @return array<string, mixed>|null The state data, or null if not found/expired
     */
    public function loadState(string $name = 'admin'): ?array
    {
        $filePath = $this->getStateFilePath($name);

        if (! file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            return null;
        }

        /** @var array{created_at: int, expires_at: int, state: array<string, mixed>}|null $data */
        $data = json_decode($content, true);

        if ($data === null || ! isset($data['state'])) {
            return null;
        }

        // Check if state has expired
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            $this->deleteState($name);

            return null;
        }

        return $data['state'];
    }

    /**
     * Check if a valid (non-expired) state exists.
     */
    public function hasValidState(string $name = 'admin'): bool
    {
        return $this->loadState($name) !== null;
    }

    /**
     * Delete a stored state.
     */
    public function deleteState(string $name = 'admin'): bool
    {
        $filePath = $this->getStateFilePath($name);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    /**
     * Delete all stored states.
     */
    public function clearAllStates(): void
    {
        if (! is_dir($this->statePath)) {
            return;
        }

        $files = glob($this->statePath . '/*.json');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Generate cookies array for WordPress authentication.
     *
     * This creates the cookie format expected by Playwright/browser testing tools.
     *
     * @param string $baseUrl The WordPress site URL
     * @param array<string, string> $cookies WordPress cookies (wordpress_logged_in_*, etc.)
     * @return array<int, array{name: string, value: string, domain: string, path: string, expires: int, httpOnly: bool, secure: bool, sameSite: string}>
     */
    public function formatCookiesForBrowser(string $baseUrl, array $cookies): array
    {
        $parsed = parse_url($baseUrl);
        $domain = $parsed['host'] ?? 'localhost';
        $secure = ($parsed['scheme'] ?? 'http') === 'https';
        $expires = time() + $this->maxAge;

        $formattedCookies = [];

        foreach ($cookies as $name => $value) {
            $formattedCookies[] = [
                'name' => $name,
                'value' => $value,
                'domain' => $domain,
                'path' => '/',
                'expires' => $expires,
                'httpOnly' => true,
                'secure' => $secure,
                'sameSite' => 'Lax',
            ];
        }

        return $formattedCookies;
    }

    /**
     * Create a minimal state structure for Playwright.
     *
     * @param array<int, array{name: string, value: string, domain: string, path: string, expires: int, httpOnly: bool, secure: bool, sameSite: string}> $cookies
     * @param array<string, mixed> $localStorage
     * @return array{cookies: array<int, array{name: string, value: string, domain: string, path: string, expires: int, httpOnly: bool, secure: bool, sameSite: string}>, origins: array<int, array{origin: string, localStorage: array<int, array{name: string, value: string}>}>}
     */
    public function createBrowserState(array $cookies, array $localStorage = [], string $origin = ''): array
    {
        $localStorageItems = [];

        foreach ($localStorage as $key => $value) {
            $encodedValue = is_string($value) ? $value : json_encode($value);
            $localStorageItems[] = [
                'name' => (string) $key,
                'value' => $encodedValue !== false ? $encodedValue : '',
            ];
        }

        /** @var array<int, array{origin: string, localStorage: array<int, array{name: string, value: string}>}> $origins */
        $origins = [];

        if ($origin !== '' && ! empty($localStorageItems)) {
            $origins[] = [
                'origin' => $origin,
                'localStorage' => $localStorageItems,
            ];
        }

        return [
            'cookies' => $cookies,
            'origins' => $origins,
        ];
    }

    /**
     * Get metadata about stored state.
     *
     * @return array{exists: bool, created_at: int|null, expires_at: int|null, is_expired: bool, file_path: string}
     */
    public function getStateInfo(string $name = 'admin'): array
    {
        $filePath = $this->getStateFilePath($name);
        $exists = file_exists($filePath);
        $createdAt = null;
        $expiresAt = null;
        $isExpired = true;

        if ($exists) {
            $content = file_get_contents($filePath);

            if ($content !== false) {
                $data = json_decode($content, true);

                if (is_array($data)) {
                    $createdAt = isset($data['created_at']) && is_int($data['created_at']) ? $data['created_at'] : null;
                    $expiresAt = isset($data['expires_at']) && is_int($data['expires_at']) ? $data['expires_at'] : null;
                    $isExpired = $expiresAt !== null && $expiresAt < time();
                }
            }
        }

        return [
            'exists' => $exists,
            'created_at' => $createdAt,
            'expires_at' => $expiresAt,
            'is_expired' => $isExpired,
            'file_path' => $filePath,
        ];
    }
}
