<?php

declare(strict_types=1);

use PestWP\Ajax\AjaxClient;

describe('AjaxClient', function (): void {

    describe('class structure', function (): void {

        test('class exists', function (): void {
            expect(class_exists(AjaxClient::class))->toBeTrue();
        });

    });

    describe('fluent interface methods exist', function (): void {

        test('as() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'as'))->toBeTrue();
        });

        test('actingAs() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'actingAs'))->toBeTrue();
        });

        test('withNonce() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'withNonce'))->toBeTrue();
        });

        test('withNonceValue() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'withNonceValue'))->toBeTrue();
        });

        test('admin() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'admin'))->toBeTrue();
        });

        test('nopriv() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'nopriv'))->toBeTrue();
        });

        test('withServerVar() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'withServerVar'))->toBeTrue();
        });

    });

    describe('action execution methods exist', function (): void {

        test('action() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'action'))->toBeTrue();
        });

        test('dispatch() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'dispatch'))->toBeTrue();
        });

    });

    describe('action checking methods exist', function (): void {

        test('hasAction() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'hasAction'))->toBeTrue();
        });

        test('hasAdminAction() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'hasAdminAction'))->toBeTrue();
        });

        test('hasNoprivAction() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'hasNoprivAction'))->toBeTrue();
        });

        test('registeredActions() method exists', function (): void {
            expect(method_exists(AjaxClient::class, 'registeredActions'))->toBeTrue();
        });

    });

});
