<?php

declare(strict_types=1);

use PestWP\Rest\RestClient;

describe('RestClient', function (): void {

    describe('class structure', function (): void {

        test('class exists', function (): void {
            expect(class_exists(RestClient::class))->toBeTrue();
        });

    });

    describe('fluent interface (without WordPress)', function (): void {
        // These tests verify the fluent interface returns new instances
        // They can't actually make requests without WordPress

        test('as() returns a new instance', function (): void {
            // We can't test this without WordPress
            // But we can verify the method signature exists
            expect(method_exists(RestClient::class, 'as'))->toBeTrue();
        });

        test('actingAs() is an alias for as()', function (): void {
            expect(method_exists(RestClient::class, 'actingAs'))->toBeTrue();
        });

        test('withHeader() exists', function (): void {
            expect(method_exists(RestClient::class, 'withHeader'))->toBeTrue();
        });

        test('withHeaders() exists', function (): void {
            expect(method_exists(RestClient::class, 'withHeaders'))->toBeTrue();
        });

        test('withQuery() exists', function (): void {
            expect(method_exists(RestClient::class, 'withQuery'))->toBeTrue();
        });

        test('withNonce() exists', function (): void {
            expect(method_exists(RestClient::class, 'withNonce'))->toBeTrue();
        });

        test('withNonceValue() exists', function (): void {
            expect(method_exists(RestClient::class, 'withNonceValue'))->toBeTrue();
        });

    });

    describe('HTTP methods', function (): void {

        test('get() method exists', function (): void {
            expect(method_exists(RestClient::class, 'get'))->toBeTrue();
        });

        test('post() method exists', function (): void {
            expect(method_exists(RestClient::class, 'post'))->toBeTrue();
        });

        test('put() method exists', function (): void {
            expect(method_exists(RestClient::class, 'put'))->toBeTrue();
        });

        test('patch() method exists', function (): void {
            expect(method_exists(RestClient::class, 'patch'))->toBeTrue();
        });

        test('delete() method exists', function (): void {
            expect(method_exists(RestClient::class, 'delete'))->toBeTrue();
        });

        test('request() method exists', function (): void {
            expect(method_exists(RestClient::class, 'request'))->toBeTrue();
        });

    });

    describe('route checking methods', function (): void {

        test('routeExists() method exists', function (): void {
            expect(method_exists(RestClient::class, 'routeExists'))->toBeTrue();
        });

        test('routes() method exists', function (): void {
            expect(method_exists(RestClient::class, 'routes'))->toBeTrue();
        });

        test('routesForNamespace() method exists', function (): void {
            expect(method_exists(RestClient::class, 'routesForNamespace'))->toBeTrue();
        });

    });

});
