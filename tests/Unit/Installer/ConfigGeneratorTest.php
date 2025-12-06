<?php

declare(strict_types=1);

namespace PestWP\Tests\Unit\Installer;

use PestWP\Installer\ConfigGenerator;

it('can be instantiated', function (): void {
    $wpPath = sys_get_temp_dir() . '/wordpress';
    $pestPath = sys_get_temp_dir() . '/.pest';
    $generator = new ConfigGenerator($wpPath, $pestPath);

    expect($generator)->toBeInstanceOf(ConfigGenerator::class);
});

it('returns the correct config path', function (): void {
    $wpPath = sys_get_temp_dir() . '/wordpress';
    $pestPath = sys_get_temp_dir() . '/.pest';
    $generator = new ConfigGenerator($wpPath, $pestPath);

    $expected = $pestPath . DIRECTORY_SEPARATOR . 'wp-tests-config.php';
    expect($generator->getConfigPath())->toBe($expected);
});

it('returns the correct test library path', function (): void {
    $wpPath = sys_get_temp_dir() . '/wordpress';
    $pestPath = sys_get_temp_dir() . '/.pest';
    $generator = new ConfigGenerator($wpPath, $pestPath);

    $expected = $pestPath . DIRECTORY_SEPARATOR . 'wordpress-tests-lib';
    expect($generator->getTestLibraryPath())->toBe($expected);
});

it('reports config does not exist when file is missing', function (): void {
    $wpPath = sys_get_temp_dir() . '/pest-wp-test-' . uniqid();
    $pestPath = sys_get_temp_dir() . '/pest-wp-pest-' . uniqid();
    $generator = new ConfigGenerator($wpPath, $pestPath);

    expect($generator->configExists())->toBeFalse();
});
