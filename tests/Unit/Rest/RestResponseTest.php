<?php

declare(strict_types=1);

use PestWP\Rest\RestResponse;

describe('RestResponse', function (): void {

    describe('construction and basic getters', function (): void {

        test('creates response with status, data, and headers', function (): void {
            $response = new RestResponse(200, ['id' => 1, 'title' => 'Test'], ['Content-Type' => 'application/json']);

            expect($response->status())->toBe(200);
            expect($response->data())->toBe(['id' => 1, 'title' => 'Test']);
            expect($response->headers())->toBe(['Content-Type' => 'application/json']);
        });

        test('creates response with default empty headers', function (): void {
            $response = new RestResponse(201, ['created' => true]);

            expect($response->headers())->toBe([]);
        });

        test('json() returns data as JSON string', function (): void {
            $response = new RestResponse(200, ['key' => 'value']);

            expect($response->json())->toBe('{"key":"value"}');
        });

    });

    describe('status checks', function (): void {

        test('isSuccessful returns true for 2xx status codes', function (int $status): void {
            $response = new RestResponse($status, []);

            expect($response->isSuccessful())->toBeTrue();
        })->with([200, 201, 204, 299]);

        test('isSuccessful returns false for non-2xx status codes', function (int $status): void {
            $response = new RestResponse($status, []);

            expect($response->isSuccessful())->toBeFalse();
        })->with([100, 301, 400, 404, 500]);

        test('isError returns true for 4xx and 5xx status codes', function (int $status): void {
            $response = new RestResponse($status, []);

            expect($response->isError())->toBeTrue();
        })->with([400, 401, 403, 404, 500, 502, 503]);

        test('isError returns false for non-error status codes', function (int $status): void {
            $response = new RestResponse($status, []);

            expect($response->isError())->toBeFalse();
        })->with([100, 200, 201, 301, 302]);

        test('isClientError returns true for 4xx status codes', function (int $status): void {
            $response = new RestResponse($status, []);

            expect($response->isClientError())->toBeTrue();
        })->with([400, 401, 403, 404, 422, 429]);

        test('isClientError returns false for non-4xx status codes', function (int $status): void {
            $response = new RestResponse($status, []);

            expect($response->isClientError())->toBeFalse();
        })->with([200, 201, 301, 500, 502]);

        test('isServerError returns true for 5xx status codes', function (int $status): void {
            $response = new RestResponse($status, []);

            expect($response->isServerError())->toBeTrue();
        })->with([500, 501, 502, 503, 504]);

        test('isServerError returns false for non-5xx status codes', function (int $status): void {
            $response = new RestResponse($status, []);

            expect($response->isServerError())->toBeFalse();
        })->with([200, 201, 400, 404]);

        test('hasStatus checks for specific status code', function (): void {
            $response = new RestResponse(201, []);

            expect($response->hasStatus(201))->toBeTrue();
            expect($response->hasStatus(200))->toBeFalse();
        });

    });

    describe('header access', function (): void {

        test('header() returns specific header value', function (): void {
            $response = new RestResponse(200, [], [
                'Content-Type' => 'application/json',
                'X-Custom' => 'value',
            ]);

            expect($response->header('Content-Type'))->toBe('application/json');
            expect($response->header('X-Custom'))->toBe('value');
        });

        test('header() is case-insensitive', function (): void {
            $response = new RestResponse(200, [], ['Content-Type' => 'application/json']);

            expect($response->header('content-type'))->toBe('application/json');
            expect($response->header('CONTENT-TYPE'))->toBe('application/json');
        });

        test('header() returns null for missing headers', function (): void {
            $response = new RestResponse(200, [], []);

            expect($response->header('X-Missing'))->toBeNull();
        });

        test('header() handles array header values', function (): void {
            $response = new RestResponse(200, [], ['Set-Cookie' => ['cookie1', 'cookie2']]);

            expect($response->header('Set-Cookie'))->toBe('cookie1');
        });

    });

    describe('data access with dot notation', function (): void {

        test('get() retrieves top-level values', function (): void {
            $response = new RestResponse(200, ['id' => 1, 'title' => 'Test']);

            expect($response->get('id'))->toBe(1);
            expect($response->get('title'))->toBe('Test');
        });

        test('get() retrieves nested values with dot notation', function (): void {
            $response = new RestResponse(200, [
                'user' => [
                    'name' => 'John',
                    'address' => [
                        'city' => 'NYC',
                    ],
                ],
            ]);

            expect($response->get('user.name'))->toBe('John');
            expect($response->get('user.address.city'))->toBe('NYC');
        });

        test('get() returns default for missing keys', function (): void {
            $response = new RestResponse(200, ['id' => 1]);

            expect($response->get('missing'))->toBeNull();
            expect($response->get('missing', 'default'))->toBe('default');
            expect($response->get('user.name', 'Unknown'))->toBe('Unknown');
        });

        test('has() checks if key exists', function (): void {
            $response = new RestResponse(200, [
                'id' => 1,
                'user' => ['name' => 'John'],
            ]);

            expect($response->has('id'))->toBeTrue();
            expect($response->has('user'))->toBeTrue();
            expect($response->has('user.name'))->toBeTrue();
            expect($response->has('missing'))->toBeFalse();
            expect($response->has('user.email'))->toBeFalse();
        });

    });

    describe('error handling', function (): void {

        test('errorCode() returns code from WP_Error-like response', function (): void {
            $response = new RestResponse(400, [
                'code' => 'rest_invalid_param',
                'message' => 'Invalid parameter',
            ]);

            expect($response->errorCode())->toBe('rest_invalid_param');
        });

        test('errorCode() returns null when no code present', function (): void {
            $response = new RestResponse(200, ['data' => 'value']);

            expect($response->errorCode())->toBeNull();
        });

        test('errorMessage() returns message from WP_Error-like response', function (): void {
            $response = new RestResponse(400, [
                'code' => 'rest_invalid_param',
                'message' => 'Invalid parameter',
            ]);

            expect($response->errorMessage())->toBe('Invalid parameter');
        });

        test('errorMessage() returns null when no message present', function (): void {
            $response = new RestResponse(200, ['data' => 'value']);

            expect($response->errorMessage())->toBeNull();
        });

    });

    describe('collection handling', function (): void {

        test('count() returns number of items in collection', function (): void {
            $response = new RestResponse(200, [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ]);

            expect($response->count())->toBe(3);
        });

        test('count() returns 1 for non-collection response', function (): void {
            $response = new RestResponse(200, ['id' => 1, 'title' => 'Test']);

            expect($response->count())->toBe(1);
        });

        test('first() returns first item in collection', function (): void {
            $response = new RestResponse(200, [
                ['id' => 1, 'title' => 'First'],
                ['id' => 2, 'title' => 'Second'],
            ]);

            expect($response->first())->toBe(['id' => 1, 'title' => 'First']);
        });

        test('first() returns null for empty collection', function (): void {
            $response = new RestResponse(200, []);

            expect($response->first())->toBeNull();
        });

        test('items() returns all items as array', function (): void {
            $response = new RestResponse(200, [
                ['id' => 1],
                ['id' => 2],
            ]);

            expect($response->items())->toBe([['id' => 1], ['id' => 2]]);
        });

        test('items() wraps non-collection in array', function (): void {
            $response = new RestResponse(200, ['id' => 1, 'title' => 'Test']);

            expect($response->items())->toBe([['id' => 1, 'title' => 'Test']]);
        });

    });

    describe('ArrayAccess implementation', function (): void {

        test('offsetExists checks for key', function (): void {
            $response = new RestResponse(200, ['id' => 1]);

            expect(isset($response['id']))->toBeTrue();
            expect(isset($response['missing']))->toBeFalse();
        });

        test('offsetGet returns value', function (): void {
            $response = new RestResponse(200, ['id' => 1, 'title' => 'Test']);

            expect($response['id'])->toBe(1);
            expect($response['title'])->toBe('Test');
            expect($response['missing'])->toBeNull();
        });

        test('offsetSet throws exception', function (): void {
            $response = new RestResponse(200, ['id' => 1]);

            expect(fn () => $response['id'] = 2)->toThrow(BadMethodCallException::class);
        });

        test('offsetUnset throws exception', function (): void {
            $response = new RestResponse(200, ['id' => 1]);

            expect(function () use ($response) {
                unset($response['id']);
            })->toThrow(BadMethodCallException::class);
        });

    });

    describe('JsonSerializable implementation', function (): void {

        test('jsonSerialize returns data', function (): void {
            $response = new RestResponse(200, ['id' => 1, 'title' => 'Test']);

            expect(json_encode($response))->toBe('{"id":1,"title":"Test"}');
        });

    });

});
