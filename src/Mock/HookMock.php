<?php

declare(strict_types=1);

namespace PestWP\Mock;

use Closure;
use RuntimeException;

/**
 * Hook mocking utility for WordPress actions and filters.
 *
 * Allows intercepting WordPress hooks to capture registered callbacks,
 * spy on executions, and control filter values.
 *
 * @example
 * ```php
 * // Capture callbacks registered to a hook
 * $mock = mockHook('init')->capture($callbacks);
 * do_action('init');
 * expect($callbacks)->toHaveCount(3);
 *
 * // Spy on filter execution
 * $mock = mockHook('the_content')->spy();
 * apply_filters('the_content', 'Hello');
 * expect($mock)->toHaveBeenCalled();
 *
 * // Override filter result
 * $mock = mockHook('the_title')->andReturn('Mocked Title');
 * $title = apply_filters('the_title', 'Original');
 * expect($title)->toBe('Mocked Title');
 * ```
 */
final class HookMock
{
    /**
     * Type constant for actions
     */
    public const TYPE_ACTION = 'action';

    /**
     * Type constant for filters
     */
    public const TYPE_FILTER = 'filter';

    /**
     * Active hook mocks
     *
     * @var array<string, HookMock>
     */
    private static array $mocks = [];

    /**
     * Hook name
     */
    private string $hook;

    /**
     * Hook type (action or filter)
     */
    private string $type = self::TYPE_FILTER;

    /**
     * Whether the mock is active
     */
    private bool $active = true;

    /**
     * Number of times the hook was executed
     */
    private int $callCount = 0;

    /**
     * Execution history with arguments
     *
     * @var array<int, array<int, mixed>>
     */
    private array $calls = [];

    /**
     * Captured callbacks registered to the hook
     *
     * @var array<int, array{callback: callable, priority: int}>
     */
    private array $capturedCallbacks = [];

    /**
     * Reference for capturing callbacks externally
     *
     * @var array<int, array{callback: callable, priority: int}>|null
     */
    private ?array $captureRef = null;

    /**
     * Override return value for filters
     */
    private mixed $returnValue = null;

    /**
     * Override callback for filters
     */
    private ?Closure $returnCallback = null;

    /**
     * Whether to override the filter value
     */
    private bool $hasOverride = false;

    /**
     * Whether to prevent the hook from executing
     */
    private bool $prevented = false;

    /**
     * Expected call count
     */
    private ?int $expectedCalls = null;

    /**
     * @param string $hook The hook name
     */
    public function __construct(string $hook)
    {
        $this->hook = $hook;
        self::$mocks[$hook] = $this;
    }

    /**
     * Create a new hook mock
     */
    public static function create(string $hook): self
    {
        if (isset(self::$mocks[$hook])) {
            self::$mocks[$hook]->reset();
        }

        return new self($hook);
    }

    /**
     * Get the mock for a hook
     */
    public static function get(string $hook): ?self
    {
        return self::$mocks[$hook] ?? null;
    }

    /**
     * Check if a hook is mocked
     */
    public static function isMocked(string $hook): bool
    {
        return isset(self::$mocks[$hook]) && self::$mocks[$hook]->active;
    }

    /**
     * Clear all hook mocks
     */
    public static function clearAll(): void
    {
        foreach (self::$mocks as $mock) {
            $mock->reset();
        }
        self::$mocks = [];
    }

    /**
     * Get all active mocks
     *
     * @return array<string, HookMock>
     */
    public static function all(): array
    {
        return self::$mocks;
    }

    /**
     * Mark this as an action hook
     *
     * @return $this
     */
    public function action(): self
    {
        $this->type = self::TYPE_ACTION;

        return $this;
    }

    /**
     * Mark this as a filter hook
     *
     * @return $this
     */
    public function filter(): self
    {
        $this->type = self::TYPE_FILTER;

        return $this;
    }

    /**
     * Capture callbacks registered to this hook
     *
     * @param array<int, array{callback: callable, priority: int}>|null $ref Reference to store callbacks
     * @return $this
     */
    public function capture(?array &$ref = null): self
    {
        if ($ref !== null) {
            $this->captureRef = &$ref;
        }

        return $this;
    }

    /**
     * Record a callback registration
     *
     * @param callable $callback The registered callback
     * @param int $priority The priority
     */
    public function recordCallback(callable $callback, int $priority = 10): void
    {
        $entry = ['callback' => $callback, 'priority' => $priority];
        $this->capturedCallbacks[] = $entry;

        if ($this->captureRef !== null) {
            $this->captureRef[] = $entry;
        }
    }

    /**
     * Get captured callbacks
     *
     * @return array<int, array{callback: callable, priority: int}>
     */
    public function getCapturedCallbacks(): array
    {
        return $this->capturedCallbacks;
    }

    /**
     * Enable spy mode - track executions without affecting behavior
     *
     * @return $this
     */
    public function spy(): self
    {
        // Already tracking by default, this is just semantic
        return $this;
    }

    /**
     * Override the filter return value
     *
     * @return $this
     */
    public function andReturn(mixed $value): self
    {
        $this->returnValue = $value;
        $this->hasOverride = true;
        $this->returnCallback = null;

        return $this;
    }

    /**
     * Override the filter return value using a callback
     *
     * @return $this
     */
    public function andReturnUsing(Closure $callback): self
    {
        $this->returnCallback = $callback;
        $this->hasOverride = true;

        return $this;
    }

    /**
     * Pass the value through unchanged
     *
     * @return $this
     */
    public function andPassthrough(): self
    {
        $this->hasOverride = false;
        $this->returnCallback = null;

        return $this;
    }

    /**
     * Prevent the hook from executing (skip all callbacks)
     *
     * @return $this
     */
    public function prevent(): self
    {
        $this->prevented = true;

        return $this;
    }

    /**
     * Allow the hook to execute normally
     *
     * @return $this
     */
    public function allow(): self
    {
        $this->prevented = false;

        return $this;
    }

    /**
     * Record a hook execution
     *
     * @param array<int, mixed> $args
     */
    public function recordExecution(array $args): void
    {
        if ($this->active) {
            $this->callCount++;
            $this->calls[] = $args;
        }
    }

    /**
     * Get the override value for a filter
     *
     * @param mixed $originalValue The original value passed to the filter
     * @param array<int, mixed> $args All arguments passed to the filter
     */
    public function getFilterValue(mixed $originalValue, array $args): mixed
    {
        if (! $this->hasOverride) {
            return $originalValue;
        }

        if ($this->returnCallback !== null) {
            return ($this->returnCallback)($originalValue, ...$args);
        }

        return $this->returnValue;
    }

    /**
     * Check if the hook should be prevented from executing
     */
    public function isPrevented(): bool
    {
        return $this->prevented;
    }

    /**
     * Check if there's an override value
     */
    public function hasOverride(): bool
    {
        return $this->hasOverride;
    }

    /**
     * Expect the hook to be called exactly n times
     *
     * @return $this
     */
    public function times(int $count): self
    {
        $this->expectedCalls = $count;

        return $this;
    }

    /**
     * Expect the hook to be called once
     *
     * @return $this
     */
    public function once(): self
    {
        return $this->times(1);
    }

    /**
     * Expect the hook to be called twice
     *
     * @return $this
     */
    public function twice(): self
    {
        return $this->times(2);
    }

    /**
     * Expect the hook to never be called
     *
     * @return $this
     */
    public function never(): self
    {
        return $this->times(0);
    }

    /**
     * Get the hook name
     */
    public function getHook(): string
    {
        return $this->hook;
    }

    /**
     * Get the hook type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the call count
     */
    public function getCallCount(): int
    {
        return $this->callCount;
    }

    /**
     * Get all calls
     *
     * @return array<int, array<int, mixed>>
     */
    public function getCalls(): array
    {
        return $this->calls;
    }

    /**
     * Get a specific call's arguments
     *
     * @return array<int, mixed>
     */
    public function getCall(int $index): array
    {
        return $this->calls[$index] ?? [];
    }

    /**
     * Get the last call's arguments
     *
     * @return array<int, mixed>
     */
    public function getLastCall(): array
    {
        return $this->calls[count($this->calls) - 1] ?? [];
    }

    /**
     * Check if the hook was called
     */
    public function wasCalled(): bool
    {
        return $this->callCount > 0;
    }

    /**
     * Check if the hook was called with specific arguments
     *
     * @param array<int, mixed> $args
     */
    public function wasCalledWith(array $args): bool
    {
        foreach ($this->calls as $call) {
            if ($call === $args) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify expectations were met
     *
     * @throws RuntimeException if expectations failed
     */
    public function verify(): bool
    {
        if ($this->expectedCalls !== null && $this->callCount !== $this->expectedCalls) {
            throw new RuntimeException(sprintf(
                'Expected hook "%s" to be called %d time(s), but it was called %d time(s)',
                $this->hook,
                $this->expectedCalls,
                $this->callCount,
            ));
        }

        return true;
    }

    /**
     * Reset the mock state
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->callCount = 0;
        $this->calls = [];
        $this->capturedCallbacks = [];

        return $this;
    }

    /**
     * Disable the mock
     *
     * @return $this
     */
    public function disable(): self
    {
        $this->active = false;

        return $this;
    }

    /**
     * Enable the mock
     *
     * @return $this
     */
    public function enable(): self
    {
        $this->active = true;

        return $this;
    }

    /**
     * Check if active
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Remove this mock
     */
    public function restore(): void
    {
        $this->active = false;
        unset(self::$mocks[$this->hook]);
    }
}
