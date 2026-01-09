<?php

declare(strict_types=1);

namespace PestWP\Expectations;

use Pest\Expectation;
use PestWP\Mock\FunctionMock;
use PestWP\Mock\HookMock;
use PestWP\Snapshot\SnapshotManager;

/**
 * Register mocking and snapshot expectations.
 */
function registerMockingExpectations(): void
{
    // =========================================================================
    // Function Mock Expectations
    // =========================================================================

    /*
     * Assert that a function mock was called.
     *
     * @example
     * expect($mock)->toHaveBeenCalled();
     */
    expect()->extend('toHaveBeenCalled', function (): Expectation {
        /** @var Expectation<FunctionMock|HookMock> $this */
        $mock = $this->value;

        if (! ($mock instanceof FunctionMock) && ! ($mock instanceof HookMock)) {
            throw new \InvalidArgumentException('toHaveBeenCalled() expects a FunctionMock or HookMock');
        }

        expect($mock->wasCalled())->toBeTrue(
            sprintf('Expected %s to have been called, but it was not', $mock instanceof FunctionMock ? $mock->getFunction() : $mock->getHook())
        );

        return $this;
    });

    /*
     * Assert that a function mock was called N times.
     *
     * @example
     * expect($mock)->toHaveBeenCalledTimes(3);
     */
    expect()->extend('toHaveBeenCalledTimes', function (int $times): Expectation {
        /** @var Expectation<FunctionMock|HookMock> $this */
        $mock = $this->value;

        if (! ($mock instanceof FunctionMock) && ! ($mock instanceof HookMock)) {
            throw new \InvalidArgumentException('toHaveBeenCalledTimes() expects a FunctionMock or HookMock');
        }

        expect($mock->getCallCount())->toBe(
            $times,
            sprintf(
                'Expected %s to have been called %d time(s), but it was called %d time(s)',
                $mock instanceof FunctionMock ? $mock->getFunction() : $mock->getHook(),
                $times,
                $mock->getCallCount()
            )
        );

        return $this;
    });

    /*
     * Assert that a function mock was called with specific arguments.
     *
     * @example
     * expect($mock)->toHaveBeenCalledWith(['user@example.com', 'Subject']);
     */
    expect()->extend('toHaveBeenCalledWith', function (array $args): Expectation {
        /** @var Expectation<FunctionMock|HookMock> $this */
        $mock = $this->value;

        if (! ($mock instanceof FunctionMock) && ! ($mock instanceof HookMock)) {
            throw new \InvalidArgumentException('toHaveBeenCalledWith() expects a FunctionMock or HookMock');
        }

        expect($mock->wasCalledWith($args))->toBeTrue(
            sprintf(
                'Expected %s to have been called with %s',
                $mock instanceof FunctionMock ? $mock->getFunction() : $mock->getHook(),
                json_encode($args)
            )
        );

        return $this;
    });

    /*
     * Assert that a function mock was never called.
     *
     * @example
     * expect($mock)->not->toHaveBeenCalled();
     * expect($mock)->toHaveNotBeenCalled();
     */
    expect()->extend('toHaveNotBeenCalled', function (): Expectation {
        /** @var Expectation<FunctionMock|HookMock> $this */
        $mock = $this->value;

        if (! ($mock instanceof FunctionMock) && ! ($mock instanceof HookMock)) {
            throw new \InvalidArgumentException('toHaveNotBeenCalled() expects a FunctionMock or HookMock');
        }

        expect($mock->wasCalled())->toBeFalse(
            sprintf(
                'Expected %s to not have been called, but it was called %d time(s)',
                $mock instanceof FunctionMock ? $mock->getFunction() : $mock->getHook(),
                $mock->getCallCount()
            )
        );

        return $this;
    });

    // =========================================================================
    // Snapshot Expectations
    // =========================================================================

    /*
     * Assert that a value matches its stored snapshot.
     *
     * @example
     * expect($html)->toMatchSnapshot();
     * expect($output)->toMatchSnapshot('custom-name');
     */
    expect()->extend('toMatchSnapshot', function (?string $name = null): Expectation {
        /** @var Expectation<mixed> $this */
        $snapshots = SnapshotManager::getInstance();

        // Try to get test context from backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($trace as $frame) {
            if (isset($frame['file']) && str_contains($frame['file'], 'Test.php')) {
                $snapshots->setTest($frame['file'], $frame['function']);
                break;
            }
        }

        $snapshots->assertMatch($this->value, $name);

        return $this;
    });

    /*
     * Assert that JSON matches its stored snapshot.
     *
     * @example
     * expect($data)->toMatchJsonSnapshot();
     */
    expect()->extend('toMatchJsonSnapshot', function (?string $name = null): Expectation {
        /** @var Expectation<mixed> $this */
        $snapshots = SnapshotManager::getInstance();

        // Try to get test context from backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($trace as $frame) {
            if (isset($frame['file']) && str_contains($frame['file'], 'Test.php')) {
                $snapshots->setTest($frame['file'], $frame['function']);
                break;
            }
        }

        $snapshots->assertJsonMatch($this->value, $name);

        return $this;
    });

    /*
     * Assert that HTML matches its stored snapshot.
     *
     * @example
     * expect($html)->toMatchHtmlSnapshot();
     */
    expect()->extend('toMatchHtmlSnapshot', function (?string $name = null): Expectation {
        /** @var Expectation<string> $this */
        $snapshots = SnapshotManager::getInstance();

        // Try to get test context from backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($trace as $frame) {
            if (isset($frame['file']) && str_contains($frame['file'], 'Test.php')) {
                $snapshots->setTest($frame['file'], $frame['function']);
                break;
            }
        }

        $snapshots->assertHtmlMatch((string) $this->value, $name);

        return $this;
    });

    // =========================================================================
    // Time Mock Expectations
    // =========================================================================

    /*
     * Assert that a timestamp is before another timestamp.
     *
     * @example
     * expect($createdAt)->toBeBefore($now);
     */
    expect()->extend('toBeBefore', function (int|\DateTimeInterface $time): Expectation {
        /** @var Expectation<int|\DateTimeInterface> $this */
        $value = $this->value;

        $valueTs = $value instanceof \DateTimeInterface ? $value->getTimestamp() : $value;
        $timeTs = $time instanceof \DateTimeInterface ? $time->getTimestamp() : $time;

        expect($valueTs)->toBeLessThan($timeTs, 'Expected timestamp to be before the given time');

        return $this;
    });

    /*
     * Assert that a timestamp is after another timestamp.
     *
     * @example
     * expect($updatedAt)->toBeAfter($createdAt);
     */
    expect()->extend('toBeAfter', function (int|\DateTimeInterface $time): Expectation {
        /** @var Expectation<int|\DateTimeInterface> $this */
        $value = $this->value;

        $valueTs = $value instanceof \DateTimeInterface ? $value->getTimestamp() : $value;
        $timeTs = $time instanceof \DateTimeInterface ? $time->getTimestamp() : $time;

        expect($valueTs)->toBeGreaterThan($timeTs, 'Expected timestamp to be after the given time');

        return $this;
    });

    // =========================================================================
    // HTTP Mock Expectations
    // =========================================================================

    /*
     * Assert that a URL was requested via mocked HTTP.
     *
     * @example
     * expect(mockHTTP())->toHaveRequested('https://api.example.com/users');
     */
    expect()->extend('toHaveRequested', function (string $url): Expectation {
        /** @var Expectation<\PestWP\Mock\HTTPMock> $this */
        $mock = $this->value;

        if (! ($mock instanceof \PestWP\Mock\HTTPMock)) {
            throw new \InvalidArgumentException('toHaveRequested() expects an HTTPMock instance');
        }

        expect($mock->wasRequested($url))->toBeTrue(
            sprintf('Expected URL %s to have been requested', $url)
        );

        return $this;
    });

    /*
     * Assert the number of HTTP requests made.
     *
     * @example
     * expect(mockHTTP())->toHaveRequestCount(3);
     */
    expect()->extend('toHaveRequestCount', function (int $count): Expectation {
        /** @var Expectation<\PestWP\Mock\HTTPMock> $this */
        $mock = $this->value;

        if (! ($mock instanceof \PestWP\Mock\HTTPMock)) {
            throw new \InvalidArgumentException('toHaveRequestCount() expects an HTTPMock instance');
        }

        expect($mock->getRequestCount())->toBe(
            $count,
            sprintf('Expected %d HTTP requests, but %d were made', $count, $mock->getRequestCount())
        );

        return $this;
    });
}
