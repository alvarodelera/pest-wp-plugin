<?php

declare(strict_types=1);

namespace PestWP\Functions;

use PestWP\Fixtures\FixtureManager;
use PestWP\Mock\FunctionMock;
use PestWP\Mock\HookMock;
use PestWP\Mock\HTTPMock;
use PestWP\Mock\TimeMock;
use PestWP\Snapshot\SnapshotManager;

/**
 * Create a function mock
 *
 * @example
 * ```php
 * // Mock wp_mail to always return true
 * $mock = mockFunction('wp_mail')->andReturn(true);
 *
 * // Mock with callback
 * $mock = mockFunction('wp_mail')->andReturnUsing(function($to) {
 *     return str_ends_with($to, '@example.com');
 * });
 *
 * // Assert function was called
 * expect($mock)->toHaveBeenCalled();
 * expect($mock)->toHaveBeenCalledWith(['user@example.com']);
 * ```
 */
function mockFunction(string $function): FunctionMock
{
    return FunctionMock::create($function);
}

/**
 * Create a hook mock
 *
 * @example
 * ```php
 * // Capture callbacks registered to a hook
 * $mock = mockHook('init')->capture($callbacks);
 * do_action('init');
 *
 * // Spy on filter execution
 * $mock = mockHook('the_content')->spy();
 * apply_filters('the_content', 'Hello');
 * expect($mock)->toHaveBeenCalled();
 *
 * // Override filter result
 * $mock = mockHook('the_title')->andReturn('Mocked Title');
 * ```
 */
function mockHook(string $hook): HookMock
{
    return HookMock::create($hook);
}

/**
 * Create an HTTP mock
 *
 * @example
 * ```php
 * // Mock a specific URL
 * mockHTTP()
 *     ->whenUrl('https://api.example.com/users')
 *     ->andReturn(['users' => []]);
 *
 * // Mock with pattern matching
 * mockHTTP()
 *     ->whenUrl('https://api.example.com/*')
 *     ->andReturn(['status' => 'ok']);
 *
 * // Block all unmatched requests
 * mockHTTP()->blockUnmatched();
 * ```
 */
function mockHTTP(): HTTPMock
{
    return HTTPMock::getInstance();
}

/**
 * Create a time mock
 *
 * @example
 * ```php
 * // Freeze time to a specific date
 * $mock = mockTime()->freeze('2024-01-15 10:30:00');
 *
 * // Get mocked timestamp
 * $timestamp = $mock->getTimestamp();
 *
 * // Advance time
 * $mock->advance('+1 hour');
 * $mock->advanceDays(1);
 *
 * // Unfreeze
 * $mock->unfreeze();
 * ```
 */
function mockTime(): TimeMock
{
    return TimeMock::getInstance();
}

/**
 * Freeze time at the given moment
 *
 * @param string|int|\DateTimeInterface|null $time The time to freeze at
 *
 * @example
 * ```php
 * freezeTime('2024-01-15 10:30:00');
 * freezeTime(strtotime('2024-01-15'));
 * freezeTime(); // freeze at current time
 * ```
 */
function freezeTime(string|int|\DateTimeInterface|null $time = null): TimeMock
{
    return mockTime()->freeze($time);
}

/**
 * Get the fixture manager
 *
 * @example
 * ```php
 * // Load fixtures from a file
 * $fixtures = fixtures()->load('users.yaml');
 *
 * // Define fixtures inline
 * $fixtures = fixtures()->define([
 *     'users' => [
 *         ['login' => 'admin', 'role' => 'administrator'],
 *     ],
 * ]);
 *
 * // Seed and access
 * $fixtures->seed();
 * $admin = $fixtures->get('users.admin');
 * ```
 */
function fixtures(?string $path = null): FixtureManager
{
    $manager = FixtureManager::getInstance();

    if ($path !== null) {
        $manager->setPath($path);
    }

    return $manager;
}

/**
 * Get the snapshot manager
 *
 * @example
 * ```php
 * $snapshots = snapshots();
 * $snapshots->setTest(__FILE__, 'my test name');
 * $snapshots->assertMatch($output);
 * ```
 */
function snapshots(?string $path = null): SnapshotManager
{
    $manager = SnapshotManager::getInstance();

    if ($path !== null) {
        $manager->setPath($path);
    }

    return $manager;
}

/**
 * Clear all mocks
 *
 * Call this in afterEach() to ensure mocks don't leak between tests.
 *
 * @example
 * ```php
 * afterEach(function () {
 *     clearMocks();
 * });
 * ```
 */
function clearMocks(): void
{
    FunctionMock::clearAll();
    HookMock::clearAll();
    HTTPMock::resetInstance();
    TimeMock::resetInstance();
    FixtureManager::resetInstance();
    SnapshotManager::resetInstance();
}

/**
 * Clear function mocks
 */
function clearFunctionMocks(): void
{
    FunctionMock::clearAll();
}

/**
 * Clear hook mocks
 */
function clearHookMocks(): void
{
    HookMock::clearAll();
}

/**
 * Clear HTTP mocks
 */
function clearHTTPMocks(): void
{
    HTTPMock::resetInstance();
}

/**
 * Unfreeze time
 */
function unfreezeTime(): void
{
    TimeMock::resetInstance();
}

/**
 * Get a mock for a function
 */
function getFunctionMock(string $function): ?FunctionMock
{
    return FunctionMock::get($function);
}

/**
 * Get a mock for a hook
 */
function getHookMock(string $hook): ?HookMock
{
    return HookMock::get($hook);
}

/**
 * Check if a function is currently mocked
 */
function isFunctionMocked(string $function): bool
{
    return FunctionMock::isMocked($function);
}

/**
 * Check if a hook is currently mocked
 */
function isHookMocked(string $hook): bool
{
    return HookMock::isMocked($hook);
}

/**
 * Check if time is currently frozen
 */
function isTimeFrozen(): bool
{
    return TimeMock::getInstance()->isFrozen();
}

/**
 * Get the current mocked timestamp (or real time if not mocked)
 */
function mockedTime(): int
{
    return TimeMock::getInstance()->getTimestamp();
}

/**
 * Get the current mocked datetime (or real time if not mocked)
 */
function mockedDateTime(): \DateTimeImmutable
{
    return TimeMock::getInstance()->getDateTime();
}
