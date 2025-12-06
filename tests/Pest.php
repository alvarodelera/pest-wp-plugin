<?php

declare(strict_types=1);

use PestWP\Database\TransactionManager;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Database Isolation
|--------------------------------------------------------------------------
|
| These hooks ensure that each test runs in isolation. The database is
| restored to a clean snapshot state before each test, guaranteeing that
| changes made during one test do not affect subsequent tests.
|
| This uses SQLite database snapshots for fast and reliable isolation.
|
*/

// Apply transaction isolation to all Integration tests
// EXCEPT AuthHelpersTest which needs to persist users across assertions
uses()
    ->beforeEach(function (): void {
        // Always logout before starting a test to ensure clean state
        if (function_exists('PestWP\\logout')) {
            \PestWP\logout();
        }

        // Skip transaction for auth tests - they manage their own cleanup
        if (! str_contains($this->name(), 'Auth Helpers')) {
            TransactionManager::beginTransaction();
        }
    })
    ->afterEach(function (): void {
        // Skip rollback for auth tests
        if (! str_contains($this->name(), 'Auth Helpers')) {
            TransactionManager::rollback();
        }

        // Always logout after each test
        if (function_exists('PestWP\\logout')) {
            \PestWP\logout();
        }
    })
    ->in('Integration');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

// expect()->extend('toBeOne', function () {
//     return $this->toBe(1);
// });

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

// function something()
// {
//     // ..
// }
