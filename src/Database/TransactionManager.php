<?php

declare(strict_types=1);

namespace PestWP\Database;

/**
 * Manages database isolation for tests.
 *
 * This class provides a simple API for test isolation using database snapshots.
 * It wraps DatabaseManager to provide the interface expected by Pest hooks.
 *
 * Usage in tests/Pest.php:
 *
 *     uses()
 *         ->beforeEach(fn() => TransactionManager::beginTransaction())
 *         ->afterEach(fn() => TransactionManager::rollback())
 *         ->in('Integration');
 *
 * Despite the name "Transaction", this class uses snapshot-based isolation
 * which is faster and more reliable than actual database transactions with
 * the SQLite WordPress adapter.
 */
final class TransactionManager
{
    /**
     * Whether the manager has been initialized for the test suite.
     */
    private static bool $initialized = false;

    /**
     * Begin a "transaction" (initialize snapshot if needed).
     *
     * This is called before each test. On first call, it creates a snapshot.
     * On subsequent calls, it restores the database to the snapshot state.
     */
    public static function beginTransaction(): void
    {
        if (! self::$initialized) {
            // First call: initialize and create snapshot
            DatabaseManager::initialize();
            self::$initialized = true;

            return;
        }

        // Subsequent calls: restore to snapshot
        DatabaseManager::restoreSnapshot();
    }

    /**
     * Rollback the "transaction" (no-op with snapshot approach).
     *
     * With the snapshot approach, the rollback is effectively done
     * at the START of the next test (via restoreSnapshot).
     * This method exists for API compatibility.
     */
    public static function rollback(): void
    {
        // No-op: restoration happens in beginTransaction
        // This keeps the API compatible but avoids double restoration
    }

    /**
     * Check if the manager is available.
     */
    public static function isAvailable(): bool
    {
        return DatabaseManager::isInitialized() || DatabaseManager::getDatabasePath() !== null;
    }

    /**
     * Check if isolation is active.
     */
    public static function isTransactionActive(): bool
    {
        return self::$initialized && DatabaseManager::isInitialized();
    }

    /**
     * Reset the manager state.
     */
    public static function reset(): void
    {
        self::$initialized = false;
        DatabaseManager::reset();
    }

    // ================================================================
    // Legacy API aliases (for backward compatibility)
    // ================================================================

    /**
     * @deprecated Use beginTransaction() instead
     */
    public static function createSavepoint(): void
    {
        self::beginTransaction();
    }

    /**
     * @deprecated Use rollback() instead
     */
    public static function rollbackToSavepoint(): void
    {
        self::rollback();
    }

    /**
     * Commit - no-op with snapshot approach.
     */
    public static function commit(): void
    {
        // No-op: snapshots don't need commits
    }
}
