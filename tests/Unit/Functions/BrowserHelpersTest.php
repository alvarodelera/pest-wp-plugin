<?php

declare(strict_types=1);

use function PestWP\Functions\getBrowserConfig;
use function PestWP\Functions\getStorageStatePath;

describe('Browser Helpers', function () {
    describe('getBrowserConfig()', function () {
        it('returns config from environment variables when browser() function does not exist', function () {
            $_ENV['WP_BASE_URL'] = 'http://example.com';
            $_ENV['WP_ADMIN_USER'] = 'testuser';
            $_ENV['WP_ADMIN_PASSWORD'] = 'testpass';

            $config = getBrowserConfig();

            expect($config)
                ->toBeArray()
                ->toHaveKey('base_url')
                ->toHaveKey('admin_user')
                ->toHaveKey('admin_password');

            expect($config['base_url'])->toBe('http://example.com');
            expect($config['admin_user'])->toBe('testuser');
            expect($config['admin_password'])->toBe('testpass');

            unset($_ENV['WP_BASE_URL'], $_ENV['WP_ADMIN_USER'], $_ENV['WP_ADMIN_PASSWORD']);
        });

        it('returns default values when no environment variables are set', function () {
            // Clear any existing env vars
            unset($_ENV['WP_BASE_URL'], $_ENV['WP_ADMIN_USER'], $_ENV['WP_ADMIN_PASSWORD']);

            $config = getBrowserConfig();

            expect($config['base_url'])->toBe('http://localhost:8080');
            expect($config['admin_user'])->toBe('admin');
            expect($config['admin_password'])->toBe('password');
        });
    });

    describe('getStorageStatePath()', function () {
        it('returns correct path to storage state file', function () {
            $path = getStorageStatePath();

            expect($path)
                ->toBeString()
                ->toContain('.pest/state/admin.json');
        });

        // Playwright-specific helpers removed in favor of Pest Browser integration

        // Documentation helper removed in favor of Pest Browser docs
    });
});
