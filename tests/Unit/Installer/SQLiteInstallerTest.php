<?php

declare(strict_types=1);

namespace PestWP\Tests\Unit\Installer;

use PestWP\Installer\SQLiteInstaller;

it('can be instantiated', function (): void {
    $wpPath = sys_get_temp_dir() . '/wordpress';
    $pestPath = sys_get_temp_dir() . '/.pest';
    $installer = new SQLiteInstaller($wpPath, $pestPath);

    expect($installer)->toBeInstanceOf(SQLiteInstaller::class);
});

it('returns the correct db drop-in path', function (): void {
    $wpPath = sys_get_temp_dir() . '/wordpress';
    $pestPath = sys_get_temp_dir() . '/.pest';
    $installer = new SQLiteInstaller($wpPath, $pestPath);

    $expected = $wpPath . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . 'db.php';
    expect($installer->getDbDropInPath())->toBe($expected);
});

it('returns the correct plugin path', function (): void {
    $wpPath = sys_get_temp_dir() . '/wordpress';
    $pestPath = sys_get_temp_dir() . '/.pest';
    $installer = new SQLiteInstaller($wpPath, $pestPath);

    $expected = $wpPath . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'sqlite-database-integration';
    expect($installer->getPluginPath())->toBe($expected);
});

it('returns the correct database path', function (): void {
    $wpPath = sys_get_temp_dir() . '/wordpress';
    $pestPath = sys_get_temp_dir() . '/.pest';
    $installer = new SQLiteInstaller($wpPath, $pestPath);

    $expected = $pestPath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . '.ht.sqlite';
    expect($installer->getDatabasePath())->toBe($expected);
});

it('reports not installed when db.php does not exist', function (): void {
    $wpPath = sys_get_temp_dir() . '/pest-wp-test-' . uniqid();
    $pestPath = sys_get_temp_dir() . '/pest-wp-pest-' . uniqid();
    $installer = new SQLiteInstaller($wpPath, $pestPath);

    expect($installer->isInstalled())->toBeFalse();
});
