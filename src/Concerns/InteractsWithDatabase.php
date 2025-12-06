<?php

declare(strict_types=1);

namespace PestWP\Concerns;

use PestWP\Database\TransactionManager;

/**
 * Trait for database isolation in tests.
 *
 * Apply this trait to your test classes to enable automatic database
 * isolation using SQLite snapshots. Each test will start with a clean
 * database state.
 *
 * Usage in Pest.php:
 *
 *     uses(PestWP\Concerns\InteractsWithDatabase::class)->in('Integration');
 *
 * Or in a TestCase class:
 *
 *     class MyTestCase extends TestCase {
 *         use \PestWP\Concerns\InteractsWithDatabase;
 *     }
 */
trait InteractsWithDatabase
{
    /**
     * Set up database isolation before each test.
     *
     * @before
     */
    public function setUpDatabaseIsolation(): void
    {
        TransactionManager::beginTransaction();
    }

    /**
     * Clean up after each test (no-op with snapshot approach).
     *
     * @after
     */
    public function tearDownDatabaseIsolation(): void
    {
        TransactionManager::rollback();
    }

    /**
     * Manually restore the database to its clean state.
     *
     * Useful if you need to reset the database mid-test.
     */
    protected function resetDatabase(): void
    {
        TransactionManager::beginTransaction();
    }
}
