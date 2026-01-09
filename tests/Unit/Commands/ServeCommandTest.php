<?php

declare(strict_types=1);

use PestWP\Commands\ServeCommand;

describe('Serve Command', function () {
    beforeEach(function () {
        $this->tempDir = sys_get_temp_dir() . '/pest-wp-serve-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->command = new ServeCommand($this->tempDir, 'localhost', 9999);
    });

    afterEach(function () {
        if (is_dir($this->tempDir . '/.pest')) {
            // Clean up .pest directory
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->tempDir . '/.pest', RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            rmdir($this->tempDir . '/.pest');
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    });

    describe('Configuration', function () {
        it('returns correct base URL', function () {
            expect($this->command->getBaseUrl())->toBe('http://localhost:9999');
        });

        it('returns correct host', function () {
            expect($this->command->getHost())->toBe('localhost');
        });

        it('returns correct port', function () {
            expect($this->command->getPort())->toBe(9999);
        });

        it('returns status information', function () {
            $status = $this->command->getStatus();

            expect($status)
                ->toBeArray()
                ->toHaveKey('ready')
                ->toHaveKey('url')
                ->toHaveKey('wordpress_path')
                ->toHaveKey('host')
                ->toHaveKey('port');

            expect($status['url'])->toBe('http://localhost:9999');
            expect($status['host'])->toBe('localhost');
            expect($status['port'])->toBe(9999);
        });
    });

    describe('Default Credentials', function () {
        it('has default admin user constant', function () {
            expect(ServeCommand::DEFAULT_ADMIN_USER)->toBe('admin');
        });

        it('has default admin password constant', function () {
            expect(ServeCommand::DEFAULT_ADMIN_PASSWORD)->toBe('password');
        });

        it('has default admin email constant', function () {
            expect(ServeCommand::DEFAULT_ADMIN_EMAIL)->toBe('admin@example.org');
        });
    });

    describe('Port Detection', function () {
        it('can check if port is available', function () {
            // Use a very high port that's unlikely to be in use
            $command = new ServeCommand($this->tempDir, 'localhost', 59999);

            // This should return true for an unused port
            // Note: We can't guarantee a port is free, but 59999 is unlikely to be used
            $result = $command->isPortAvailable();

            expect($result)->toBeBool();
        });

        it('can find an available port', function () {
            $command = new ServeCommand($this->tempDir, 'localhost', 59990);

            $port = $command->findAvailablePort(5);

            expect($port)->toBeInt()
                ->toBeGreaterThanOrEqual(59990);
        });
    });

    describe('Server Command Generation', function () {
        it('generates server command with correct format', function () {
            // First create .pest directory
            mkdir($this->tempDir . '/.pest', 0755, true);
            mkdir($this->tempDir . '/.pest/wordpress', 0755, true);

            $serverInfo = $this->command->getServerCommand();

            expect($serverInfo)->toBeArray()
                ->toHaveKey('command')
                ->toHaveKey('workdir')
                ->toHaveKey('router');

            expect($serverInfo['command'])->toContain('php -S')
                ->toContain('localhost:9999');
        });

        it('creates router script', function () {
            // Create .pest directory first
            mkdir($this->tempDir . '/.pest', 0755, true);

            $routerPath = $this->command->createRouterScript();

            expect(file_exists($routerPath))->toBeTrue();
            expect($routerPath)->toContain('router.php');

            $content = file_get_contents($routerPath);
            expect($content)->toContain('PestWP Router Script')
                ->toContain('WordPress');
        });
    });

    describe('Environment File Generation', function () {
        it('generates .env.testing file', function () {
            $envPath = $this->command->generateEnvFile();

            expect(file_exists($envPath))->toBeTrue();
            expect($envPath)->toContain('.env.testing');

            $content = file_get_contents($envPath);
            expect($content)
                ->toContain('WP_BASE_URL=http://localhost:9999')
                ->toContain('WP_ADMIN_USER=admin')
                ->toContain('WP_ADMIN_PASSWORD=password');

            // Clean up
            unlink($envPath);
        });
    });

    describe('Installation Status', function () {
        it('reports not ready when WordPress is not installed', function () {
            expect($this->command->isReady())->toBeFalse();
        });
    });
});
