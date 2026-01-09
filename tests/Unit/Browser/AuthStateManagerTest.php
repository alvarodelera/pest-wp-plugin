<?php

declare(strict_types=1);

use PestWP\Browser\AuthStateManager;

describe('Auth State Manager', function () {
    beforeEach(function () {
        $this->tempDir = sys_get_temp_dir() . '/pest-wp-auth-test-' . uniqid();
        if (PHP_OS_FAMILY === 'Windows' && str_starts_with($this->tempDir, 'C:\\Windows\\TEMP')) {
            $this->tempDir = dirname(__DIR__, 3) . '/.pest/tests/auth-' . uniqid();
        }
        $this->manager = new AuthStateManager($this->tempDir, 3600);
    });

    afterEach(function () {
        // Clean up temp directory
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*.json');
            if ($files) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    });

    describe('State Path Management', function () {
        it('returns correct state path', function () {
            expect($this->manager->getStatePath())->toBe($this->tempDir);
        });

        it('returns correct state file path for default name', function () {
            expect($this->manager->getStateFilePath())
                ->toBe($this->tempDir . '/admin.json');
        });

        it('returns correct state file path for custom name', function () {
            expect($this->manager->getStateFilePath('editor'))
                ->toBe($this->tempDir . '/editor.json');
        });

        it('creates state directory when needed', function () {
            expect(is_dir($this->tempDir))->toBeFalse();

            $this->manager->ensureStateDirectory();

            expect(is_dir($this->tempDir))->toBeTrue();
        });
    });

    describe('State Saving', function () {
        it('saves state to file', function () {
            $state = ['cookies' => [], 'origins' => []];

            $result = $this->manager->saveState($state);

            expect($result)->toBeTrue();
            expect(file_exists($this->manager->getStateFilePath()))->toBeTrue();
        });

        it('saves state with metadata', function () {
            $state = ['test' => 'value'];

            $this->manager->saveState($state);

            $content = file_get_contents($this->manager->getStateFilePath());
            $data = json_decode($content, true);

            expect($data)->toHaveKey('created_at')
                ->toHaveKey('expires_at')
                ->toHaveKey('state');

            expect($data['state'])->toBe(['test' => 'value']);
        });

        it('saves state with custom name', function () {
            $state = ['user' => 'editor'];

            $this->manager->saveState($state, 'editor');

            expect(file_exists($this->manager->getStateFilePath('editor')))->toBeTrue();
        });
    });

    describe('State Loading', function () {
        it('loads saved state', function () {
            $state = ['cookies' => ['test' => 'cookie']];
            $this->manager->saveState($state);

            $loaded = $this->manager->loadState();

            expect($loaded)->toBe($state);
        });

        it('returns null when state does not exist', function () {
            $loaded = $this->manager->loadState('nonexistent');

            expect($loaded)->toBeNull();
        });

        it('returns null for expired state', function () {
            // Create manager with very short expiry
            $manager = new AuthStateManager($this->tempDir, 1);
            $manager->saveState(['test' => 'value']);

            // Wait for expiry
            sleep(2);

            $loaded = $manager->loadState();

            expect($loaded)->toBeNull();
        });
    });

    describe('State Validation', function () {
        it('validates existing state', function () {
            $this->manager->saveState(['test' => 'value']);

            expect($this->manager->hasValidState())->toBeTrue();
        });

        it('validates non-existing state', function () {
            expect($this->manager->hasValidState('nonexistent'))->toBeFalse();
        });
    });

    describe('State Deletion', function () {
        it('deletes specific state', function () {
            $this->manager->saveState(['test' => 'value']);
            expect(file_exists($this->manager->getStateFilePath()))->toBeTrue();

            $result = $this->manager->deleteState();

            expect($result)->toBeTrue();
            expect(file_exists($this->manager->getStateFilePath()))->toBeFalse();
        });

        it('returns true when deleting non-existent state', function () {
            expect($this->manager->deleteState('nonexistent'))->toBeTrue();
        });

        it('clears all states', function () {
            $this->manager->saveState(['admin' => 'data'], 'admin');
            $this->manager->saveState(['editor' => 'data'], 'editor');

            expect(file_exists($this->manager->getStateFilePath('admin')))->toBeTrue();
            expect(file_exists($this->manager->getStateFilePath('editor')))->toBeTrue();

            $this->manager->clearAllStates();

            expect(file_exists($this->manager->getStateFilePath('admin')))->toBeFalse();
            expect(file_exists($this->manager->getStateFilePath('editor')))->toBeFalse();
        });
    });

    describe('Cookie Formatting', function () {
        it('formats cookies for browser', function () {
            $cookies = [
                'wordpress_logged_in_abc123' => 'session_value',
                'wp_sec_abc123' => 'security_value',
            ];

            $formatted = $this->manager->formatCookiesForBrowser('http://localhost:8080', $cookies);

            expect($formatted)->toBeArray()
                ->toHaveCount(2);

            expect($formatted[0])
                ->toHaveKey('name')
                ->toHaveKey('value')
                ->toHaveKey('domain')
                ->toHaveKey('path')
                ->toHaveKey('expires')
                ->toHaveKey('httpOnly')
                ->toHaveKey('secure')
                ->toHaveKey('sameSite');

            expect($formatted[0]['domain'])->toBe('localhost');
            expect($formatted[0]['secure'])->toBeFalse();
        });

        it('sets secure flag for HTTPS URLs', function () {
            $cookies = ['test' => 'value'];

            $formatted = $this->manager->formatCookiesForBrowser('https://secure.site.com', $cookies);

            expect($formatted[0]['secure'])->toBeTrue();
            expect($formatted[0]['domain'])->toBe('secure.site.com');
        });
    });

    describe('Browser State Creation', function () {
        it('creates minimal browser state', function () {
            $cookies = [
                [
                    'name' => 'test',
                    'value' => 'value',
                    'domain' => 'localhost',
                    'path' => '/',
                    'expires' => time() + 3600,
                    'httpOnly' => true,
                    'secure' => false,
                    'sameSite' => 'Lax',
                ],
            ];

            $state = $this->manager->createBrowserState($cookies);

            expect($state)->toHaveKey('cookies')
                ->toHaveKey('origins');

            expect($state['cookies'])->toBe($cookies);
            expect($state['origins'])->toBeEmpty();
        });

        it('creates browser state with localStorage', function () {
            $cookies = [];
            $localStorage = ['key1' => 'value1', 'key2' => 'value2'];

            $state = $this->manager->createBrowserState($cookies, $localStorage, 'http://localhost:8080');

            expect($state['origins'])->toHaveCount(1);
            expect($state['origins'][0]['origin'])->toBe('http://localhost:8080');
            expect($state['origins'][0]['localStorage'])->toHaveCount(2);
        });
    });

    describe('State Info', function () {
        it('returns info for existing state', function () {
            $this->manager->saveState(['test' => 'value']);

            $info = $this->manager->getStateInfo();

            expect($info['exists'])->toBeTrue();
            expect($info['created_at'])->toBeInt();
            expect($info['expires_at'])->toBeInt();
            expect($info['is_expired'])->toBeFalse();
            expect($info['file_path'])->toBe($this->manager->getStateFilePath());
        });

        it('returns info for non-existing state', function () {
            $info = $this->manager->getStateInfo('nonexistent');

            expect($info['exists'])->toBeFalse();
            expect($info['created_at'])->toBeNull();
            expect($info['expires_at'])->toBeNull();
        });
    });
});
