<?php

declare(strict_types=1);

use PestWP\Mock\HookMock;

beforeEach(function () {
    HookMock::clearAll();
});

afterEach(function () {
    HookMock::clearAll();
});

describe('HookMock', function () {
    describe('class structure', function () {
        test('class exists', function () {
            expect(class_exists(HookMock::class))->toBeTrue();
        });

        test('has type constants', function () {
            expect(HookMock::TYPE_ACTION)->toBe('action');
            expect(HookMock::TYPE_FILTER)->toBe('filter');
        });
    });

    describe('creation and registration', function () {
        test('create() returns a HookMock instance', function () {
            $mock = HookMock::create('init');
            expect($mock)->toBeInstanceOf(HookMock::class);
        });

        test('create() registers the mock', function () {
            HookMock::create('init');
            expect(HookMock::isMocked('init'))->toBeTrue();
        });

        test('get() returns the mock for a hook', function () {
            $mock = HookMock::create('init');
            expect(HookMock::get('init'))->toBe($mock);
        });

        test('get() returns null for non-mocked hook', function () {
            expect(HookMock::get('nonexistent'))->toBeNull();
        });

        test('clearAll() removes all mocks', function () {
            HookMock::create('init');
            HookMock::create('the_content');

            HookMock::clearAll();

            expect(HookMock::isMocked('init'))->toBeFalse();
            expect(HookMock::isMocked('the_content'))->toBeFalse();
        });

        test('all() returns all mocks', function () {
            HookMock::create('init');
            HookMock::create('the_content');

            $all = HookMock::all();

            expect($all)->toHaveKeys(['init', 'the_content']);
        });
    });

    describe('hook types', function () {
        test('default type is filter', function () {
            $mock = HookMock::create('test_hook');
            expect($mock->getType())->toBe(HookMock::TYPE_FILTER);
        });

        test('action() sets type to action', function () {
            $mock = HookMock::create('test_hook')->action();
            expect($mock->getType())->toBe(HookMock::TYPE_ACTION);
        });

        test('filter() sets type to filter', function () {
            $mock = HookMock::create('test_hook')->action()->filter();
            expect($mock->getType())->toBe(HookMock::TYPE_FILTER);
        });
    });

    describe('callback capture', function () {
        test('recordCallback() stores callbacks', function () {
            $mock = HookMock::create('init');
            $callback = fn () => null;

            $mock->recordCallback($callback, 10);

            $callbacks = $mock->getCapturedCallbacks();
            expect($callbacks)->toHaveCount(1);
            expect($callbacks[0]['callback'])->toBe($callback);
            expect($callbacks[0]['priority'])->toBe(10);
        });

        test('capture() with reference populates it', function () {
            $captured = [];
            $mock = HookMock::create('init')->capture($captured);

            $mock->recordCallback(fn () => null, 10);
            $mock->recordCallback(fn () => null, 20);

            expect($captured)->toHaveCount(2);
        });
    });

    describe('filter override', function () {
        test('andReturn() sets override value', function () {
            $mock = HookMock::create('the_title')->andReturn('Mocked Title');

            expect($mock->hasOverride())->toBeTrue();
            expect($mock->getFilterValue('Original', []))->toBe('Mocked Title');
        });

        test('andReturnUsing() sets callback override', function () {
            $mock = HookMock::create('the_title')
                ->andReturnUsing(fn ($value) => strtoupper($value));

            expect($mock->getFilterValue('hello', []))->toBe('HELLO');
        });

        test('andPassthrough() removes override', function () {
            $mock = HookMock::create('the_title')
                ->andReturn('Mocked')
                ->andPassthrough();

            expect($mock->hasOverride())->toBeFalse();
            expect($mock->getFilterValue('Original', []))->toBe('Original');
        });
    });

    describe('execution tracking', function () {
        test('recordExecution() tracks calls', function () {
            $mock = HookMock::create('init');

            $mock->recordExecution(['arg1', 'arg2']);
            $mock->recordExecution(['arg3']);

            expect($mock->getCallCount())->toBe(2);
            expect($mock->getCalls())->toHaveCount(2);
        });

        test('wasCalled() returns true after execution', function () {
            $mock = HookMock::create('init');

            expect($mock->wasCalled())->toBeFalse();

            $mock->recordExecution([]);

            expect($mock->wasCalled())->toBeTrue();
        });

        test('wasCalledWith() checks specific arguments', function () {
            $mock = HookMock::create('init');
            $mock->recordExecution(['value1']);
            $mock->recordExecution(['value2']);

            expect($mock->wasCalledWith(['value1']))->toBeTrue();
            expect($mock->wasCalledWith(['value2']))->toBeTrue();
            expect($mock->wasCalledWith(['value3']))->toBeFalse();
        });

        test('getCall() returns specific call', function () {
            $mock = HookMock::create('init');
            $mock->recordExecution(['first']);
            $mock->recordExecution(['second']);

            expect($mock->getCall(0))->toBe(['first']);
            expect($mock->getCall(1))->toBe(['second']);
        });

        test('getLastCall() returns last call', function () {
            $mock = HookMock::create('init');
            $mock->recordExecution(['first']);
            $mock->recordExecution(['last']);

            expect($mock->getLastCall())->toBe(['last']);
        });
    });

    describe('expectations', function () {
        test('times() sets expected call count', function () {
            $mock = HookMock::create('init')->times(2);
            $mock->recordExecution([]);
            $mock->recordExecution([]);

            expect($mock->verify())->toBeTrue();
        });

        test('times() throws on mismatch', function () {
            $mock = HookMock::create('init')->times(3);
            $mock->recordExecution([]);

            expect(fn () => $mock->verify())->toThrow(\RuntimeException::class);
        });

        test('once() expects exactly one call', function () {
            $mock = HookMock::create('init')->once();
            $mock->recordExecution([]);

            expect($mock->verify())->toBeTrue();
        });

        test('twice() expects exactly two calls', function () {
            $mock = HookMock::create('init')->twice();
            $mock->recordExecution([]);
            $mock->recordExecution([]);

            expect($mock->verify())->toBeTrue();
        });

        test('never() expects no calls', function () {
            $mock = HookMock::create('init')->never();
            expect($mock->verify())->toBeTrue();
        });

        test('never() throws when called', function () {
            $mock = HookMock::create('init')->never();
            $mock->recordExecution([]);

            expect(fn () => $mock->verify())->toThrow(\RuntimeException::class);
        });
    });

    describe('prevention', function () {
        test('prevent() marks hook as prevented', function () {
            $mock = HookMock::create('init')->prevent();
            expect($mock->isPrevented())->toBeTrue();
        });

        test('allow() removes prevention', function () {
            $mock = HookMock::create('init')->prevent()->allow();
            expect($mock->isPrevented())->toBeFalse();
        });
    });

    describe('mock control', function () {
        test('reset() clears state', function () {
            $mock = HookMock::create('init');
            $mock->recordCallback(fn () => null, 10);
            $mock->recordExecution([]);

            $mock->reset();

            expect($mock->getCallCount())->toBe(0);
            expect($mock->getCapturedCallbacks())->toBeEmpty();
        });

        test('disable() deactivates mock', function () {
            $mock = HookMock::create('init');
            $mock->disable();

            expect($mock->isActive())->toBeFalse();
            expect(HookMock::isMocked('init'))->toBeFalse();
        });

        test('enable() reactivates mock', function () {
            $mock = HookMock::create('init');
            $mock->disable();
            $mock->enable();

            expect($mock->isActive())->toBeTrue();
        });

        test('restore() removes mock from registry', function () {
            $mock = HookMock::create('init');
            $mock->restore();

            expect(HookMock::get('init'))->toBeNull();
        });

        test('getHook() returns hook name', function () {
            $mock = HookMock::create('init');
            expect($mock->getHook())->toBe('init');
        });
    });
});
