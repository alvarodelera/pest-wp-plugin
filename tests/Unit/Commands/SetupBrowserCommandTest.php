<?php

declare(strict_types=1);

use PestWP\Commands\SetupBrowserCommand;

describe('Setup Browser Command', function () {
    beforeEach(function () {
        $this->tempDir = sys_get_temp_dir() . '/pest-wp-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->configPath = $this->tempDir . '/Pest.php';
        $this->command = new SetupBrowserCommand($this->configPath);
    });

    afterEach(function () {
        if (is_dir($this->tempDir)) {
            $files = array_diff(scandir($this->tempDir), ['.', '..']);
            foreach ($files as $file) {
                unlink($this->tempDir . '/' . $file);
            }
            rmdir($this->tempDir);
        }
    });

    it('creates config file if it does not exist', function () {
        expect(file_exists($this->configPath))->toBeFalse();

        $this->command->execute('http://localhost:8080', 'admin', 'password');

        expect(file_exists($this->configPath))->toBeTrue();
    });

    it('generates valid browser configuration', function () {
        $this->command->execute('http://localhost:8080', 'admin', 'password123');

        $content = file_get_contents($this->configPath);

        expect($content)->toContain('function browser()')
            ->toContain('http://localhost:8080')
            ->toContain('admin')
            ->toContain('password123');
    });

    it('validates base URL format', function () {
        $this->command->execute('not-a-valid-url', 'admin', 'password');
    })->throws(RuntimeException::class, 'Base URL must be a valid URL');

    it('validates empty base URL', function () {
        $this->command->execute('', 'admin', 'password');
    })->throws(RuntimeException::class, 'Base URL cannot be empty');

    it('validates empty admin user', function () {
        $this->command->execute('http://localhost:8080', '', 'password');
    })->throws(RuntimeException::class, 'Admin username cannot be empty');

    it('validates empty admin password', function () {
        $this->command->execute('http://localhost:8080', 'admin', '');
    })->throws(RuntimeException::class, 'Admin password cannot be empty');

    it('updates existing configuration', function () {
        // Create initial config
        $this->command->execute('http://localhost:8080', 'admin', 'oldpass');

        $initialContent = file_get_contents($this->configPath);
        expect($initialContent)->toContain('http://localhost:8080')
            ->toContain('oldpass');

        // Update config
        $this->command->execute('http://newsite.test', 'newadmin', 'newpass');

        $updatedContent = file_get_contents($this->configPath);
        expect($updatedContent)->toContain('http://newsite.test')
            ->toContain('newadmin')
            ->toContain('newpass')
            ->not->toContain('oldpass');
    });

    it('retrieves current configuration', function () {
        $this->command->execute('http://localhost:8080', 'testuser', 'testpass');

        $config = $this->command->getCurrentConfig();

        expect($config)->toBeArray()
            ->toHaveKey('base_url', 'http://localhost:8080')
            ->toHaveKey('admin_user', 'testuser')
            ->toHaveKey('admin_password', 'testpass');
    });

    it('returns null when no configuration exists', function () {
        $config = $this->command->getCurrentConfig();

        expect($config)->toBeNull();
    });

    it('preserves existing Pest.php content', function () {
        // Create initial Pest.php with content
        $initialContent = <<<'PHP'
<?php

declare(strict_types=1);

use PestWP\TestCase;

uses(TestCase::class)->in('Integration');

// Custom test setup
function customHelper(): string
{
    return 'test';
}

PHP;

        file_put_contents($this->configPath, $initialContent);

        // Add browser config
        $this->command->execute('http://localhost:8080', 'admin', 'password');

        $updatedContent = file_get_contents($this->configPath);

        expect($updatedContent)->toContain('use PestWP\TestCase')
            ->toContain("uses(TestCase::class)->in('Integration')")
            ->toContain('function customHelper()')
            ->toContain('function browser()');
    });

    it('does not create multiple browser configurations', function () {
        // Add config twice
        $this->command->execute('http://localhost:8080', 'admin', 'pass1');
        $this->command->execute('http://localhost:8080', 'admin', 'pass2');

        $content = file_get_contents($this->configPath);

        // Should only have one browser function
        $count = substr_count($content, 'function browser()');
        expect($count)->toBe(1);
    });

    it('handles HTTPS URLs', function () {
        $this->command->execute('https://secure.site.com', 'admin', 'password');

        $config = $this->command->getCurrentConfig();

        expect($config['base_url'])->toBe('https://secure.site.com');
    });

    it('handles URLs with ports', function () {
        $this->command->execute('http://localhost:3000', 'admin', 'password');

        $config = $this->command->getCurrentConfig();

        expect($config['base_url'])->toBe('http://localhost:3000');
    });

    it('handles special characters in password', function () {
        $complexPassword = 'P@ssw0rd!#$%';

        $this->command->execute('http://localhost:8080', 'admin', $complexPassword);

        $config = $this->command->getCurrentConfig();

        expect($config['admin_password'])->toBe($complexPassword);
    });
});
