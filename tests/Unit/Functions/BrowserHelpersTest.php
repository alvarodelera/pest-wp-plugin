<?php

declare(strict_types=1);

use PestWP\Browser\AuthStateManager;

use function PestWP\Functions\clearAllBrowserAuthStates;
use function PestWP\Functions\clearBrowserAuthState;
use function PestWP\Functions\createBrowserState;
use function PestWP\Functions\getAuthStateManager;
use function PestWP\Functions\getBrowserAuthStateInfo;
use function PestWP\Functions\getBrowserConfig;
use function PestWP\Functions\getStorageStateFilePath;
use function PestWP\Functions\getStorageStatePath;
use function PestWP\Functions\hasBrowserAuthState;
use function PestWP\Functions\loadBrowserAuthState;
use function PestWP\Functions\saveBrowserAuthState;

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

    describe('getAuthStateManager()', function () {
        it('returns AuthStateManager instance', function () {
            $manager = getAuthStateManager();

            expect($manager)->toBeInstanceOf(AuthStateManager::class);
        });

        it('returns same instance on multiple calls (singleton)', function () {
            $manager1 = getAuthStateManager();
            $manager2 = getAuthStateManager();

            expect($manager1)->toBe($manager2);
        });
    });

    describe('Storage State Paths', function () {
        it('returns correct path to storage state directory', function () {
            $path = getStorageStatePath();

            expect($path)
                ->toBeString()
                ->toContain('.pest')
                ->toContain('state');
        });

        it('returns correct path to storage state file', function () {
            $path = getStorageStateFilePath();

            expect($path)
                ->toBeString()
                ->toContain('admin.json');
        });

        it('returns correct path for custom state name', function () {
            $path = getStorageStateFilePath('editor');

            expect($path)
                ->toBeString()
                ->toContain('editor.json');
        });
    });

    describe('Auth State Operations', function () {
        beforeEach(function () {
            // Clear any existing state before each test
            clearAllBrowserAuthStates();
        });

        afterEach(function () {
            // Clean up after tests
            clearAllBrowserAuthStates();
        });

        it('initially has no auth state', function () {
            expect(hasBrowserAuthState())->toBeFalse();
        });

        it('can save auth state', function () {
            $state = ['cookies' => [], 'origins' => []];

            $result = saveBrowserAuthState($state);

            expect($result)->toBeTrue();
            expect(hasBrowserAuthState())->toBeTrue();
        });

        it('can load saved auth state', function () {
            $state = ['test' => 'data'];
            saveBrowserAuthState($state);

            $loaded = loadBrowserAuthState();

            expect($loaded)->toBe($state);
        });

        it('can clear auth state', function () {
            saveBrowserAuthState(['test' => 'value']);
            expect(hasBrowserAuthState())->toBeTrue();

            $result = clearBrowserAuthState();

            expect($result)->toBeTrue();
            expect(hasBrowserAuthState())->toBeFalse();
        });

        it('can get auth state info', function () {
            saveBrowserAuthState(['test' => 'value']);

            $info = getBrowserAuthStateInfo();

            expect($info)->toBeArray()
                ->toHaveKey('exists')
                ->toHaveKey('created_at')
                ->toHaveKey('expires_at')
                ->toHaveKey('is_expired')
                ->toHaveKey('file_path');

            expect($info['exists'])->toBeTrue();
            expect($info['is_expired'])->toBeFalse();
        });
    });

    describe('createBrowserState()', function () {
        it('creates browser state structure from cookies', function () {
            $cookies = [
                'wordpress_logged_in' => 'session123',
            ];

            $state = createBrowserState('http://localhost:8080', $cookies);

            expect($state)->toBeArray()
                ->toHaveKey('cookies')
                ->toHaveKey('origins');

            expect($state['cookies'])->toHaveCount(1);
            expect($state['cookies'][0]['name'])->toBe('wordpress_logged_in');
            expect($state['cookies'][0]['domain'])->toBe('localhost');
        });

        it('creates browser state with localStorage', function () {
            $cookies = ['test' => 'value'];
            $localStorage = ['key' => 'data'];

            $state = createBrowserState('http://localhost:8080', $cookies, $localStorage);

            expect($state['origins'])->toHaveCount(1);
            expect($state['origins'][0]['origin'])->toBe('http://localhost:8080');
        });

        it('sets secure flag for HTTPS URLs', function () {
            $cookies = ['secure_cookie' => 'value'];

            $state = createBrowserState('https://secure.site.com', $cookies);

            expect($state['cookies'][0]['secure'])->toBeTrue();
            expect($state['cookies'][0]['domain'])->toBe('secure.site.com');
        });
    });
});
