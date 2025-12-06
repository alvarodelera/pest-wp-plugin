<?php

declare(strict_types=1);

namespace PestWP\Tests\Unit\Installer;

use PestWP\Installer\Installer;

it('can be instantiated', function (): void {
    $installer = new Installer(sys_get_temp_dir());
    expect($installer)->toBeInstanceOf(Installer::class);
});

it('reports not installed when environment is not set up', function (): void {
    $basePath = sys_get_temp_dir() . '/pest-wp-test-' . uniqid();
    $installer = new Installer($basePath);

    expect($installer->isInstalled())->toBeFalse();
});

it('provides access to sub-installers', function (): void {
    $installer = new Installer(sys_get_temp_dir());

    expect($installer->getWordPressInstaller())->toBeInstanceOf(\PestWP\Installer\WordPressInstaller::class);
    expect($installer->getSQLiteInstaller())->toBeInstanceOf(\PestWP\Installer\SQLiteInstaller::class);
    expect($installer->getConfigGenerator())->toBeInstanceOf(\PestWP\Installer\ConfigGenerator::class);
});

it('returns correct paths', function (): void {
    $basePath = sys_get_temp_dir() . '/pest-wp-test';
    $installer = new Installer($basePath);

    expect($installer->getWordPressPath())->toContain('.pest');
    expect($installer->getWordPressPath())->toContain('wordpress');
    expect($installer->getPestPath())->toContain('.pest');
    expect($installer->getConfigPath())->toContain('wp-tests-config.php');
    expect($installer->getTestLibraryPath())->toContain('wordpress-tests-lib');
    expect($installer->getDatabasePath())->toContain('.ht.sqlite');
});
