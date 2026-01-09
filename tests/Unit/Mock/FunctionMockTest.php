<?php

declare(strict_types=1);

use PestWP\Mock\FunctionMock;

beforeEach(function () {
    FunctionMock::clearAll();
});

afterEach(function () {
    FunctionMock::clearAll();
});

describe('FunctionMock', function () {
    describe('class structure', function () {
        test('class exists', function () {
            expect(class_exists(FunctionMock::class))->toBeTrue();
        });
    });

    describe('creation and registration', function () {
        test('create() returns a FunctionMock instance', function () {
            $mock = FunctionMock::create('test_function');
            expect($mock)->toBeInstanceOf(FunctionMock::class);
        });

        test('create() registers the mock', function () {
            FunctionMock::create('test_function');
            expect(FunctionMock::isMocked('test_function'))->toBeTrue();
        });

        test('get() returns the mock for a function', function () {
            $mock = FunctionMock::create('test_function');
            expect(FunctionMock::get('test_function'))->toBe($mock);
        });

        test('get() returns null for non-mocked function', function () {
            expect(FunctionMock::get('nonexistent'))->toBeNull();
        });

        test('isMocked() returns false for non-mocked function', function () {
            expect(FunctionMock::isMocked('nonexistent'))->toBeFalse();
        });

        test('clearAll() removes all mocks', function () {
            FunctionMock::create('func1');
            FunctionMock::create('func2');

            FunctionMock::clearAll();

            expect(FunctionMock::isMocked('func1'))->toBeFalse();
            expect(FunctionMock::isMocked('func2'))->toBeFalse();
        });

        test('all() returns all mocks', function () {
            FunctionMock::create('func1');
            FunctionMock::create('func2');

            $all = FunctionMock::all();

            expect($all)->toHaveKeys(['func1', 'func2']);
        });
    });

    describe('return values', function () {
        test('andReturn() sets fixed return value', function () {
            $mock = FunctionMock::create('test_function')->andReturn('test_value');

            $result = $mock->invoke([]);

            expect($result)->toBe('test_value');
        });

        test('andReturnUsing() sets callback return', function () {
            $mock = FunctionMock::create('test_function')
                ->andReturnUsing(fn ($x) => $x * 2);

            $result = $mock->invoke([5]);

            expect($result)->toBe(10);
        });

        test('andReturnConsecutive() returns different values on each call', function () {
            $mock = FunctionMock::create('test_function')
                ->andReturnConsecutive(['first', 'second', 'third']);

            expect($mock->invoke([]))->toBe('first');
            expect($mock->invoke([]))->toBe('second');
            expect($mock->invoke([]))->toBe('third');
            // After exhausted, returns last value
            expect($mock->invoke([]))->toBe('third');
        });

        test('andReturnTrue() returns true', function () {
            $mock = FunctionMock::create('test_function')->andReturnTrue();
            expect($mock->invoke([]))->toBeTrue();
        });

        test('andReturnFalse() returns false', function () {
            $mock = FunctionMock::create('test_function')->andReturnFalse();
            expect($mock->invoke([]))->toBeFalse();
        });

        test('andReturnVoid() returns null', function () {
            $mock = FunctionMock::create('test_function')->andReturnVoid();
            expect($mock->invoke([]))->toBeNull();
        });

        test('andReturnFirstArg() returns first argument', function () {
            $mock = FunctionMock::create('test_function')->andReturnFirstArg();
            expect($mock->invoke(['hello', 'world']))->toBe('hello');
        });

        test('andReturnArg() returns argument at index', function () {
            $mock = FunctionMock::create('test_function')->andReturnArg(1);
            expect($mock->invoke(['hello', 'world']))->toBe('world');
        });

        test('andThrow() throws exception', function () {
            $mock = FunctionMock::create('test_function')
                ->andThrow(new \RuntimeException('Test error'));

            expect(fn () => $mock->invoke([]))->toThrow(\RuntimeException::class);
        });
    });

    describe('call tracking', function () {
        test('wasCalled() returns false when not called', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true);
            expect($mock->wasCalled())->toBeFalse();
        });

        test('wasCalled() returns true when called', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true);
            $mock->invoke([]);
            expect($mock->wasCalled())->toBeTrue();
        });

        test('getCallCount() tracks number of calls', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true);
            $mock->invoke([]);
            $mock->invoke([]);
            $mock->invoke([]);
            expect($mock->getCallCount())->toBe(3);
        });

        test('getCalls() returns all call arguments', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true);
            $mock->invoke(['a', 'b']);
            $mock->invoke(['c', 'd']);

            $calls = $mock->getCalls();

            expect($calls)->toHaveCount(2);
            expect($calls[0])->toBe(['a', 'b']);
            expect($calls[1])->toBe(['c', 'd']);
        });

        test('getCall() returns specific call arguments', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true);
            $mock->invoke(['first']);
            $mock->invoke(['second']);

            expect($mock->getCall(0))->toBe(['first']);
            expect($mock->getCall(1))->toBe(['second']);
        });

        test('getLastCall() returns last call arguments', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true);
            $mock->invoke(['first']);
            $mock->invoke(['last']);

            expect($mock->getLastCall())->toBe(['last']);
        });

        test('wasCalledWith() checks for specific arguments', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true);
            $mock->invoke(['hello', 'world']);

            expect($mock->wasCalledWith(['hello', 'world']))->toBeTrue();
            expect($mock->wasCalledWith(['other']))->toBeFalse();
        });

        test('wasCalledWithMatching() checks with callback', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true);
            $mock->invoke(['test@example.com']);

            $matches = $mock->wasCalledWithMatching(fn ($args) => str_contains($args[0], '@'));

            expect($matches)->toBeTrue();
        });
    });

    describe('expectations', function () {
        test('times() sets expected call count', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true)->times(2);
            $mock->invoke([]);
            $mock->invoke([]);

            expect($mock->verify())->toBeTrue();
        });

        test('times() throws on mismatch', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true)->times(2);
            $mock->invoke([]);

            expect(fn () => $mock->verify())->toThrow(\RuntimeException::class);
        });

        test('once() expects exactly one call', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true)->once();
            $mock->invoke([]);

            expect($mock->verify())->toBeTrue();
        });

        test('twice() expects exactly two calls', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true)->twice();
            $mock->invoke([]);
            $mock->invoke([]);

            expect($mock->verify())->toBeTrue();
        });

        test('never() expects no calls', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true)->never();
            expect($mock->verify())->toBeTrue();
        });

        test('never() throws when called', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true)->never();
            $mock->invoke([]);

            expect(fn () => $mock->verify())->toThrow(\RuntimeException::class);
        });

        test('atLeast() sets minimum calls', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true)->atLeast(2);
            $mock->invoke([]);
            $mock->invoke([]);
            $mock->invoke([]);

            expect($mock->verify())->toBeTrue();
        });

        test('atLeast() throws on insufficient calls', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true)->atLeast(3);
            $mock->invoke([]);

            expect(fn () => $mock->verify())->toThrow(\RuntimeException::class);
        });

        test('atMost() sets maximum calls', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true)->atMost(3);
            $mock->invoke([]);
            $mock->invoke([]);

            expect($mock->verify())->toBeTrue();
        });

        test('atMost() throws on too many calls', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true)->atMost(1);
            $mock->invoke([]);
            $mock->invoke([]);

            expect(fn () => $mock->verify())->toThrow(\RuntimeException::class);
        });
    });

    describe('mock control', function () {
        test('reset() clears call history', function () {
            $mock = FunctionMock::create('test_function')->andReturn(true);
            $mock->invoke([]);
            $mock->reset();

            expect($mock->getCallCount())->toBe(0);
            expect($mock->getCalls())->toBeEmpty();
        });

        test('disable() stops the mock from intercepting', function () {
            $mock = FunctionMock::create('test_function')->andReturn('mocked');
            $mock->disable();

            expect($mock->isActive())->toBeFalse();
            expect(FunctionMock::isMocked('test_function'))->toBeFalse();
        });

        test('enable() re-enables the mock', function () {
            $mock = FunctionMock::create('test_function')->andReturn('mocked');
            $mock->disable();
            $mock->enable();

            expect($mock->isActive())->toBeTrue();
        });

        test('restore() removes the mock from registry', function () {
            $mock = FunctionMock::create('test_function');
            $mock->restore();

            expect(FunctionMock::get('test_function'))->toBeNull();
        });

        test('getFunction() returns the function name', function () {
            $mock = FunctionMock::create('test_function');
            expect($mock->getFunction())->toBe('test_function');
        });
    });
});
