<?php

declare(strict_types=1);

use PestWP\Mock\HTTPMock;

beforeEach(function () {
    HTTPMock::resetInstance();
});

afterEach(function () {
    HTTPMock::resetInstance();
});

describe('HTTPMock', function () {
    describe('singleton', function () {
        test('getInstance() returns singleton', function () {
            $instance1 = HTTPMock::getInstance();
            $instance2 = HTTPMock::getInstance();

            expect($instance1)->toBe($instance2);
        });

        test('resetInstance() clears singleton', function () {
            $instance1 = HTTPMock::getInstance();
            $instance1->whenUrl('https://example.com')->andReturn('test');

            HTTPMock::resetInstance();
            $instance2 = HTTPMock::getInstance();

            expect($instance2->willMock('https://example.com'))->toBeFalse();
        });
    });

    describe('URL mocking', function () {
        test('whenUrl() registers a mock', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com/users')->andReturn(['users' => []]);

            expect($mock->willMock('https://api.example.com/users'))->toBeTrue();
        });

        test('andReturn() sets the response', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com')->andReturn(['status' => 'ok']);

            $response = $mock->handle('https://api.example.com');

            expect($response)->toBeArray();
            expect($response['body'])->toBe('{"status":"ok"}');
        });

        test('andReturn() with status code', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com')->andReturn(['error' => true], 400);

            $response = $mock->handle('https://api.example.com');

            expect($response['response']['code'])->toBe(400);
        });

        test('andReturnJson() sets JSON response', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com')->andReturnJson(['data' => 'test']);

            $response = $mock->handle('https://api.example.com');

            expect($response['headers']['Content-Type'])->toBe('application/json');
        });

        test('andReturnError() creates error response', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com')->andReturnError('http_request_failed', 'Timeout');

            $response = $mock->handle('https://api.example.com');

            expect($response)->toHaveKey('errors');
            expect($response['errors'])->toHaveKey('http_request_failed');
        });

        test('andReturnUsing() uses callback for dynamic response', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com/*')
                ->andReturnUsing(fn ($url) => ['url' => $url]);

            $response = $mock->handle('https://api.example.com/users');

            expect(json_decode($response['body'], true))->toBe(['url' => 'https://api.example.com/users']);
        });
    });

    describe('pattern matching', function () {
        test('exact URL match', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com/users')->andReturn([]);

            expect($mock->willMock('https://api.example.com/users'))->toBeTrue();
            expect($mock->willMock('https://api.example.com/posts'))->toBeFalse();
        });

        test('wildcard pattern match', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com/*')->andReturn([]);

            expect($mock->willMock('https://api.example.com/users'))->toBeTrue();
            expect($mock->willMock('https://api.example.com/posts'))->toBeTrue();
            expect($mock->willMock('https://other.example.com/users'))->toBeFalse();
        });
    });

    describe('request limits', function () {
        test('times() limits how many times mock responds', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com')
                ->times(2)
                ->andReturn(['ok' => true]);

            $mock->handle('https://api.example.com');
            $mock->handle('https://api.example.com');

            expect($mock->willMock('https://api.example.com'))->toBeFalse();
        });

        test('once() limits to one response', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com')->once()->andReturn([]);

            $mock->handle('https://api.example.com');

            expect($mock->willMock('https://api.example.com'))->toBeFalse();
        });
    });

    describe('default responses', function () {
        test('default() sets fallback response', function () {
            $mock = HTTPMock::getInstance();
            $mock->default(['fallback' => true]);

            $response = $mock->handle('https://unknown.example.com');

            expect($response['body'])->toBe('{"fallback":true}');
        });

        test('blockUnmatched() blocks all unregistered requests', function () {
            $mock = HTTPMock::getInstance();
            $mock->blockUnmatched();

            $response = $mock->handle('https://unknown.example.com');

            expect($response)->toHaveKey('errors');
        });

        test('allowUnmatched() removes blocking', function () {
            $mock = HTTPMock::getInstance();
            $mock->blockUnmatched();
            $mock->allowUnmatched();

            $response = $mock->handle('https://unknown.example.com');

            expect($response)->toBeNull();
        });
    });

    describe('request history', function () {
        test('getRequests() returns all requests', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com/*')->andReturn([]);

            $mock->handle('https://api.example.com/users');
            $mock->handle('https://api.example.com/posts');

            expect($mock->getRequests())->toHaveCount(2);
        });

        test('getRequestCount() returns count', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com/*')->andReturn([]);

            $mock->handle('https://api.example.com/a');
            $mock->handle('https://api.example.com/b');
            $mock->handle('https://api.example.com/c');

            expect($mock->getRequestCount())->toBe(3);
        });

        test('wasRequested() checks if URL was requested', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com/*')->andReturn([]);

            $mock->handle('https://api.example.com/users');

            expect($mock->wasRequested('https://api.example.com/users'))->toBeTrue();
            expect($mock->wasRequested('https://api.example.com/posts'))->toBeFalse();
        });

        test('wasRequestedMatching() checks pattern', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com/*')->andReturn([]);

            $mock->handle('https://api.example.com/users/123');

            expect($mock->wasRequestedMatching('https://api.example.com/users/*'))->toBeTrue();
        });
    });

    describe('mock control', function () {
        test('disable() stops mocking', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com')->andReturn([]);
            $mock->disable();

            expect($mock->isActive())->toBeFalse();
            expect($mock->handle('https://api.example.com'))->toBeNull();
        });

        test('enable() re-enables mocking', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com')->andReturn([]);
            $mock->disable();
            $mock->enable();

            expect($mock->isActive())->toBeTrue();
        });

        test('reset() clears all state', function () {
            $mock = HTTPMock::getInstance();
            $mock->whenUrl('https://api.example.com')->andReturn([]);
            $mock->handle('https://api.example.com');

            $mock->reset();

            expect($mock->getRequests())->toBeEmpty();
            expect($mock->willMock('https://api.example.com'))->toBeFalse();
        });
    });
});
