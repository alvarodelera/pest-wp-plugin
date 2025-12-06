<?php

declare(strict_types=1);

namespace PestWP\Tests\Unit\Installer;

use PestWP\Installer\WordPressInstaller;

it('can be instantiated', function (): void {
    $installer = new WordPressInstaller(__DIR__);
    expect($installer)->toBeInstanceOf(WordPressInstaller::class);
});

it('returns the correct install path', function (): void {
    $basePath = sys_get_temp_dir() . '/pest-wp-test';
    $installer = new WordPressInstaller($basePath);

    $expected = $basePath . DIRECTORY_SEPARATOR . '.pest' . DIRECTORY_SEPARATOR . 'wordpress';
    expect($installer->getInstallPath())->toBe($expected);
});

it('returns the correct pest path', function (): void {
    $basePath = sys_get_temp_dir() . '/pest-wp-test';
    $installer = new WordPressInstaller($basePath);

    $expected = $basePath . DIRECTORY_SEPARATOR . '.pest';
    expect($installer->getPestPath())->toBe($expected);
});

it('reports not installed when wordpress directory does not exist', function (): void {
    $basePath = sys_get_temp_dir() . '/pest-wp-test-' . uniqid();
    $installer = new WordPressInstaller($basePath);

    expect($installer->isInstalled())->toBeFalse();
});

it('returns null version when not installed', function (): void {
    $basePath = sys_get_temp_dir() . '/pest-wp-test-' . uniqid();
    $installer = new WordPressInstaller($basePath);

    expect($installer->getInstalledVersion())->toBeNull();
});
