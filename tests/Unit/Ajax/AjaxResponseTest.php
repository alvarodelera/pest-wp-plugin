<?php

declare(strict_types=1);

use PestWP\Ajax\AjaxResponse;

describe('AjaxResponse', function (): void {

    describe('construction and factory methods', function (): void {

        test('creates response with all parameters', function (): void {
            $response = new AjaxResponse(true, ['key' => 'value'], '{"success":true}', 200);

            expect($response->isSuccess())->toBeTrue();
            expect($response->data())->toBe(['key' => 'value']);
            expect($response->rawOutput())->toBe('{"success":true}');
            expect($response->statusCode())->toBe(200);
        });

        test('success() creates successful response', function (): void {
            $response = AjaxResponse::success(['message' => 'OK']);

            expect($response->isSuccess())->toBeTrue();
            expect($response->isError())->toBeFalse();
            expect($response->data())->toBe(['message' => 'OK']);
        });

        test('error() creates error response', function (): void {
            $response = AjaxResponse::error(['message' => 'Failed']);

            expect($response->isSuccess())->toBeFalse();
            expect($response->isError())->toBeTrue();
            expect($response->data())->toBe(['message' => 'Failed']);
        });

    });

    describe('fromOutput() parsing', function (): void {

        test('parses wp_send_json_success format', function (): void {
            $output = '{"success":true,"data":{"id":1,"name":"Test"}}';
            $response = AjaxResponse::fromOutput($output);

            expect($response->isSuccess())->toBeTrue();
            expect($response->data())->toBe(['id' => 1, 'name' => 'Test']);
        });

        test('parses wp_send_json_error format', function (): void {
            $output = '{"success":false,"data":{"message":"Error occurred"}}';
            $response = AjaxResponse::fromOutput($output);

            expect($response->isSuccess())->toBeFalse();
            expect($response->data())->toBe(['message' => 'Error occurred']);
        });

        test('parses plain JSON response', function (): void {
            $output = '{"id":1,"title":"Test"}';
            $response = AjaxResponse::fromOutput($output);

            expect($response->isSuccess())->toBeTrue();
            expect($response->data())->toBe(['id' => 1, 'title' => 'Test']);
        });

        test('handles legacy "0" error response', function (): void {
            $response = AjaxResponse::fromOutput('0');

            expect($response->isSuccess())->toBeFalse();
            expect($response->data())->toBe(['raw' => '0']);
        });

        test('handles legacy "-1" error response', function (): void {
            $response = AjaxResponse::fromOutput('-1');

            expect($response->isSuccess())->toBeFalse();
            expect($response->data())->toBe(['raw' => '-1']);
        });

        test('handles plain text success response', function (): void {
            $response = AjaxResponse::fromOutput('Success message');

            expect($response->isSuccess())->toBeTrue();
            expect($response->data())->toBe(['raw' => 'Success message']);
        });

        test('handles empty output as error', function (): void {
            $response = AjaxResponse::fromOutput('');

            expect($response->isSuccess())->toBeFalse();
            expect($response->data())->toBe([]);
        });

        test('handles whitespace-only output as error', function (): void {
            $response = AjaxResponse::fromOutput('   ');

            expect($response->isSuccess())->toBeFalse();
        });

        test('stores raw output', function (): void {
            $output = '{"success":true,"data":{}}';
            $response = AjaxResponse::fromOutput($output);

            expect($response->rawOutput())->toBe($output);
        });

        test('handles non-array data in success format', function (): void {
            $output = '{"success":true,"data":"simple string"}';
            $response = AjaxResponse::fromOutput($output);

            expect($response->isSuccess())->toBeTrue();
            expect($response->data())->toBe(['value' => 'simple string']);
        });

    });

    describe('data access with dot notation', function (): void {

        test('get() retrieves top-level values', function (): void {
            $response = new AjaxResponse(true, ['id' => 1, 'name' => 'Test']);

            expect($response->get('id'))->toBe(1);
            expect($response->get('name'))->toBe('Test');
        });

        test('get() retrieves nested values with dot notation', function (): void {
            $response = new AjaxResponse(true, [
                'user' => [
                    'profile' => [
                        'name' => 'John',
                    ],
                ],
            ]);

            expect($response->get('user.profile.name'))->toBe('John');
        });

        test('get() returns default for missing keys', function (): void {
            $response = new AjaxResponse(true, ['id' => 1]);

            expect($response->get('missing'))->toBeNull();
            expect($response->get('missing', 'default'))->toBe('default');
        });

        test('has() checks if key exists', function (): void {
            $response = new AjaxResponse(true, [
                'id' => 1,
                'nested' => ['key' => 'value'],
            ]);

            expect($response->has('id'))->toBeTrue();
            expect($response->has('nested.key'))->toBeTrue();
            expect($response->has('missing'))->toBeFalse();
        });

    });

    describe('error handling', function (): void {

        test('errorMessage() returns message from data', function (): void {
            $response = new AjaxResponse(false, ['message' => 'Something went wrong']);

            expect($response->errorMessage())->toBe('Something went wrong');
        });

        test('errorMessage() returns null when no message', function (): void {
            $response = new AjaxResponse(false, ['error' => true]);

            expect($response->errorMessage())->toBeNull();
        });

    });

    describe('json() method', function (): void {

        test('returns data as JSON string', function (): void {
            $response = new AjaxResponse(true, ['key' => 'value']);

            expect($response->json())->toBe('{"key":"value"}');
        });

    });

    describe('ArrayAccess implementation', function (): void {

        test('offsetExists checks for key', function (): void {
            $response = new AjaxResponse(true, ['id' => 1]);

            expect(isset($response['id']))->toBeTrue();
            expect(isset($response['missing']))->toBeFalse();
        });

        test('offsetGet returns value', function (): void {
            $response = new AjaxResponse(true, ['id' => 1, 'name' => 'Test']);

            expect($response['id'])->toBe(1);
            expect($response['name'])->toBe('Test');
            expect($response['missing'])->toBeNull();
        });

        test('offsetSet throws exception', function (): void {
            $response = new AjaxResponse(true, ['id' => 1]);

            expect(fn () => $response['id'] = 2)->toThrow(BadMethodCallException::class);
        });

        test('offsetUnset throws exception', function (): void {
            $response = new AjaxResponse(true, ['id' => 1]);

            expect(function () use ($response) {
                unset($response['id']);
            })->toThrow(BadMethodCallException::class);
        });

    });

    describe('JsonSerializable implementation', function (): void {

        test('jsonSerialize returns success and data', function (): void {
            $response = new AjaxResponse(true, ['id' => 1]);

            $json = json_encode($response);

            expect($json)->toBe('{"success":true,"data":{"id":1}}');
        });

    });

});
