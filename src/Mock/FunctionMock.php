<?php

declare(strict_types=1);

namespace PestWP\Mock;

use Closure;
use RuntimeException;

/**
 * Function mocking utility for WordPress functions.
 *
 * Allows mocking of global functions during tests without external dependencies.
 * Uses PHP's namespace fallback mechanism to intercept function calls.
 *
 * Note: This mocking approach works best for functions called from namespaced code.
 * For functions called from global scope, the actual function will be called.
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
final class FunctionMock
{
    /**
     * Active mocks registry
     *
     * @var array<string, FunctionMock>
     */
    private static array $mocks = [];

    /**
     * The function name being mocked
     */
    private string $function;

    /**
     * The return value or callback
     */
    private mixed $returnValue = null;

    /**
     * Callback for dynamic return values
     */
    private ?Closure $returnCallback = null;

    /**
     * Exception to throw
     */
    private ?\Throwable $exception = null;

    /**
     * Sequence of return values
     *
     * @var array<int, mixed>
     */
    private array $returnSequence = [];

    /**
     * Current index in return sequence
     */
    private int $sequenceIndex = 0;

    /**
     * Number of times the function was called
     */
    private int $callCount = 0;

    /**
     * Call history with arguments
     *
     * @var array<int, array<int, mixed>>
     */
    private array $calls = [];

    /**
     * Whether the mock is active
     */
    private bool $active = true;

    /**
     * Expected call count
     */
    private ?int $expectedCalls = null;

    /**
     * Minimum expected calls
     */
    private int $minCalls = 0;

    /**
     * Maximum expected calls
     */
    private ?int $maxCalls = null;

    /**
     * Whether to call the original function after mock
     */
    private bool $passthrough = false;

    /**
     * @param string $function The function name to mock
     */
    public function __construct(string $function)
    {
        $this->function = $function;
        self::$mocks[$function] = $this;
    }

    /**
     * Create a new function mock
     */
    public static function create(string $function): self
    {
        // Clear any existing mock for this function
        if (isset(self::$mocks[$function])) {
            self::$mocks[$function]->reset();
        }

        return new self($function);
    }

    /**
     * Get the mock for a function
     */
    public static function get(string $function): ?self
    {
        return self::$mocks[$function] ?? null;
    }

    /**
     * Check if a function is mocked
     */
    public static function isMocked(string $function): bool
    {
        return isset(self::$mocks[$function]) && self::$mocks[$function]->active;
    }

    /**
     * Clear all mocks
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
     * @return array<string, FunctionMock>
     */
    public static function all(): array
    {
        return self::$mocks;
    }

    /**
     * Set a fixed return value
     *
     * @return $this
     */
    public function andReturn(mixed $value): self
    {
        $this->returnValue = $value;
        $this->returnCallback = null;
        $this->returnSequence = [];

        return $this;
    }

    /**
     * Set a callback for dynamic return values
     *
     * @return $this
     */
    public function andReturnUsing(Closure $callback): self
    {
        $this->returnCallback = $callback;
        $this->returnValue = null;
        $this->returnSequence = [];

        return $this;
    }

    /**
     * Return different values on consecutive calls
     *
     * @param array<int, mixed> $values
     * @return $this
     */
    public function andReturnConsecutive(array $values): self
    {
        $this->returnSequence = $values;
        $this->returnValue = null;
        $this->returnCallback = null;
        $this->sequenceIndex = 0;

        return $this;
    }

    /**
     * Throw an exception when called
     *
     * @return $this
     */
    public function andThrow(\Throwable $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    /**
     * Return the first argument passed to the function
     *
     * @return $this
     */
    public function andReturnFirstArg(): self
    {
        return $this->andReturnUsing(fn (mixed ...$args): mixed => $args[0] ?? null);
    }

    /**
     * Return the argument at the given index
     *
     * @return $this
     */
    public function andReturnArg(int $index): self
    {
        return $this->andReturnUsing(fn (mixed ...$args): mixed => $args[$index] ?? null);
    }

    /**
     * Return void (null)
     *
     * @return $this
     */
    public function andReturnVoid(): self
    {
        return $this->andReturn(null);
    }

    /**
     * Return true
     *
     * @return $this
     */
    public function andReturnTrue(): self
    {
        return $this->andReturn(true);
    }

    /**
     * Return false
     *
     * @return $this
     */
    public function andReturnFalse(): self
    {
        return $this->andReturn(false);
    }

    /**
     * Return self (the original argument for filter-like functions)
     *
     * @return $this
     */
    public function andReturnSelf(): self
    {
        return $this->andReturnFirstArg();
    }

    /**
     * Pass through to the original function after recording the call
     *
     * @return $this
     */
    public function andPassthrough(): self
    {
        $this->passthrough = true;

        return $this;
    }

    /**
     * Expect the function to be called exactly n times
     *
     * @return $this
     */
    public function times(int $count): self
    {
        $this->expectedCalls = $count;

        return $this;
    }

    /**
     * Expect the function to be called once
     *
     * @return $this
     */
    public function once(): self
    {
        return $this->times(1);
    }

    /**
     * Expect the function to be called twice
     *
     * @return $this
     */
    public function twice(): self
    {
        return $this->times(2);
    }

    /**
     * Expect the function to never be called
     *
     * @return $this
     */
    public function never(): self
    {
        return $this->times(0);
    }

    /**
     * Expect the function to be called at least n times
     *
     * @return $this
     */
    public function atLeast(int $count): self
    {
        $this->minCalls = $count;

        return $this;
    }

    /**
     * Expect the function to be called at most n times
     *
     * @return $this
     */
    public function atMost(int $count): self
    {
        $this->maxCalls = $count;

        return $this;
    }

    /**
     * Invoke the mock (called when the mocked function is executed)
     *
     * @param array<int, mixed> $args
     */
    public function invoke(array $args): mixed
    {
        if (! $this->active) {
            return $this->callOriginal($args);
        }

        $this->callCount++;
        $this->calls[] = $args;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        if ($this->passthrough) {
            return $this->callOriginal($args);
        }

        if ($this->returnCallback !== null) {
            return ($this->returnCallback)(...$args);
        }

        if ($this->returnSequence !== []) {
            $value = $this->returnSequence[$this->sequenceIndex] ?? end($this->returnSequence);
            if ($this->sequenceIndex < count($this->returnSequence) - 1) {
                $this->sequenceIndex++;
            }

            return $value;
        }

        return $this->returnValue;
    }

    /**
     * Call the original function
     *
     * @param array<int, mixed> $args
     */
    private function callOriginal(array $args): mixed
    {
        if (function_exists($this->function)) {
            return ($this->function)(...$args);
        }

        // Try with backslash prefix for global functions
        $globalFunction = '\\' . ltrim($this->function, '\\');
        if (function_exists($globalFunction)) {
            return $globalFunction(...$args);
        }

        return null;
    }

    /**
     * Get the function name
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Get the number of times the function was called
     */
    public function getCallCount(): int
    {
        return $this->callCount;
    }

    /**
     * Get all calls with their arguments
     *
     * @return array<int, array<int, mixed>>
     */
    public function getCalls(): array
    {
        return $this->calls;
    }

    /**
     * Get the arguments from a specific call
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
     * Check if the function was called
     */
    public function wasCalled(): bool
    {
        return $this->callCount > 0;
    }

    /**
     * Check if the function was called with specific arguments
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
     * Check if the function was called with arguments matching a callback
     *
     * @param Closure(array<int, mixed>): bool $callback
     */
    public function wasCalledWithMatching(Closure $callback): bool
    {
        foreach ($this->calls as $call) {
            if ($callback($call)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify that all expectations were met
     *
     * @throws RuntimeException if expectations were not met
     */
    public function verify(): bool
    {
        if ($this->expectedCalls !== null && $this->callCount !== $this->expectedCalls) {
            throw new RuntimeException(sprintf(
                'Expected %s to be called %d time(s), but it was called %d time(s)',
                $this->function,
                $this->expectedCalls,
                $this->callCount
            ));
        }

        if ($this->callCount < $this->minCalls) {
            throw new RuntimeException(sprintf(
                'Expected %s to be called at least %d time(s), but it was called %d time(s)',
                $this->function,
                $this->minCalls,
                $this->callCount
            ));
        }

        if ($this->maxCalls !== null && $this->callCount > $this->maxCalls) {
            throw new RuntimeException(sprintf(
                'Expected %s to be called at most %d time(s), but it was called %d time(s)',
                $this->function,
                $this->maxCalls,
                $this->callCount
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
        $this->sequenceIndex = 0;

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
     * Check if the mock is active
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Remove this mock from the registry
     */
    public function restore(): void
    {
        $this->active = false;
        unset(self::$mocks[$this->function]);
    }
}
