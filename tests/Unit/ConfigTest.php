<?php

declare(strict_types=1);

use PestWP\Config;

beforeEach(function (): void {
    // Reset config before each test
    Config::reset();
});

describe('Config', function (): void {

    describe('plugins()', function (): void {

        it('registers a single plugin', function (): void {
            Config::plugins('/path/to/my-plugin.php');

            expect(Config::getPlugins())->toBe(['/path/to/my-plugin.php']);
        });

        it('registers multiple plugins from array', function (): void {
            Config::plugins([
                '/path/to/plugin-a.php',
                '/path/to/plugin-b.php',
            ]);

            expect(Config::getPlugins())->toBe([
                '/path/to/plugin-a.php',
                '/path/to/plugin-b.php',
            ]);
        });

        it('prevents duplicate plugins', function (): void {
            Config::plugins('/path/to/my-plugin.php');
            Config::plugins('/path/to/my-plugin.php');

            expect(Config::getPlugins())->toBe(['/path/to/my-plugin.php']);
        });

        it('accumulates plugins from multiple calls', function (): void {
            Config::plugins('/path/to/plugin-a.php');
            Config::plugins('/path/to/plugin-b.php');

            expect(Config::getPlugins())->toBe([
                '/path/to/plugin-a.php',
                '/path/to/plugin-b.php',
            ]);
        });

    });

    describe('muPlugins()', function (): void {

        it('registers MU-plugins', function (): void {
            Config::muPlugins('/path/to/mu-plugin.php');

            expect(Config::getMuPlugins())->toBe(['/path/to/mu-plugin.php']);
        });

        it('registers multiple MU-plugins from array', function (): void {
            Config::muPlugins([
                '/path/to/mu-a.php',
                '/path/to/mu-b.php',
            ]);

            expect(Config::getMuPlugins())->toBe([
                '/path/to/mu-a.php',
                '/path/to/mu-b.php',
            ]);
        });

    });

    describe('theme()', function (): void {

        it('sets the active theme', function (): void {
            Config::theme('twentytwentyfour');

            expect(Config::getTheme())->toBe('twentytwentyfour');
        });

        it('returns null when no theme is set', function (): void {
            expect(Config::getTheme())->toBeNull();
        });

    });

    describe('beforeWordPress()', function (): void {

        it('registers a callback', function (): void {
            $called = false;

            Config::beforeWordPress(function () use (&$called): void {
                $called = true;
            });

            Config::executeBeforeCallbacks();

            expect($called)->toBeTrue();
        });

        it('executes multiple callbacks in order', function (): void {
            $order = [];

            Config::beforeWordPress(function () use (&$order): void {
                $order[] = 'first';
            });

            Config::beforeWordPress(function () use (&$order): void {
                $order[] = 'second';
            });

            Config::executeBeforeCallbacks();

            expect($order)->toBe(['first', 'second']);
        });

    });

    describe('afterWordPress()', function (): void {

        it('registers a callback', function (): void {
            $called = false;

            Config::afterWordPress(function () use (&$called): void {
                $called = true;
            });

            Config::executeAfterCallbacks();

            expect($called)->toBeTrue();
        });

        it('executes multiple callbacks in order', function (): void {
            $order = [];

            Config::afterWordPress(function () use (&$order): void {
                $order[] = 'first';
            });

            Config::afterWordPress(function () use (&$order): void {
                $order[] = 'second';
            });

            Config::executeAfterCallbacks();

            expect($order)->toBe(['first', 'second']);
        });

    });

    describe('hasConfiguration()', function (): void {

        it('returns false when no configuration is set', function (): void {
            expect(Config::hasConfiguration())->toBeFalse();
        });

        it('returns true when plugins are registered', function (): void {
            Config::plugins('/path/to/plugin.php');

            expect(Config::hasConfiguration())->toBeTrue();
        });

        it('returns true when MU-plugins are registered', function (): void {
            Config::muPlugins('/path/to/mu-plugin.php');

            expect(Config::hasConfiguration())->toBeTrue();
        });

        it('returns true when theme is set', function (): void {
            Config::theme('twentytwentyfour');

            expect(Config::hasConfiguration())->toBeTrue();
        });

        it('returns true when before callback is registered', function (): void {
            Config::beforeWordPress(fn () => null);

            expect(Config::hasConfiguration())->toBeTrue();
        });

        it('returns true when after callback is registered', function (): void {
            Config::afterWordPress(fn () => null);

            expect(Config::hasConfiguration())->toBeTrue();
        });

    });

    describe('isApplied()', function (): void {

        it('returns false before configuration is applied', function (): void {
            expect(Config::isApplied())->toBeFalse();
        });

        it('returns true after markApplied() is called', function (): void {
            Config::markApplied();

            expect(Config::isApplied())->toBeTrue();
        });

    });

    describe('reset()', function (): void {

        it('clears all configuration', function (): void {
            Config::plugins('/path/to/plugin.php');
            Config::muPlugins('/path/to/mu-plugin.php');
            Config::theme('mytheme');
            Config::beforeWordPress(fn () => null);
            Config::afterWordPress(fn () => null);
            Config::markApplied();

            Config::reset();

            expect(Config::getPlugins())->toBe([])
                ->and(Config::getMuPlugins())->toBe([])
                ->and(Config::getTheme())->toBeNull()
                ->and(Config::hasConfiguration())->toBeFalse()
                ->and(Config::isApplied())->toBeFalse();
        });

    });

});
