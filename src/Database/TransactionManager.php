<?php

declare(strict_types=1);

namespace PestWP\Database;

/**
 * Manages database isolation for tests using SQLite transactions.
 *
 * This class provides test isolation using the WordPress SQLite translator's
 * nested transaction support. The translator automatically wraps each query
 * in a transaction, but also supports explicit START TRANSACTION / ROLLBACK
 * commands which create nested savepoints (LEVEL0, LEVEL1, etc.).
 *
 * Usage in tests/Pest.php:
 *
 *     uses()
 *         ->beforeEach(fn() => TransactionManager::beginTransaction())
 *         ->afterEach(fn() => TransactionManager::rollback())
 *         ->in('Integration');
 */
final class TransactionManager
{
    /**
     * Whether a transaction is currently active.
     */
    private static bool $transactionActive = false;

    /**
     * Begin a transaction for test isolation.
     *
     * This sends START TRANSACTION through wpdb which the SQLite translator
     * handles by creating a nested savepoint (SAVEPOINT LEVEL0).
     */
    public static function beginTransaction(): void
    {
        if (self::$transactionActive) {
            return;
        }

        global $wpdb;

        if (! isset($wpdb) || ! ($wpdb instanceof \wpdb)) {
            return;
        }

        // Use wpdb->query to send START TRANSACTION
        // The SQLite translator will handle this and create a nested savepoint
        $wpdb->query('START TRANSACTION');
        self::$transactionActive = true;
    }

    /**
     * Rollback the transaction to restore the database state.
     *
     * This sends ROLLBACK through wpdb which the SQLite translator
     * handles by rolling back to the nested savepoint.
     */
    public static function rollback(): void
    {
        if (! self::$transactionActive) {
            return;
        }

        global $wpdb;

        if (! isset($wpdb) || ! ($wpdb instanceof \wpdb)) {
            self::$transactionActive = false;

            return;
        }

        // Use wpdb->query to send ROLLBACK
        // The SQLite translator will handle this and rollback the nested savepoint
        $wpdb->query('ROLLBACK');
        self::$transactionActive = false;

        // Clear WordPress caches to ensure fresh data reads
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    /**
     * Check if the manager is available.
     */
    public static function isAvailable(): bool
    {
        global $wpdb;

        return isset($wpdb) && $wpdb instanceof \wpdb;
    }

    /**
     * Check if a transaction is currently active.
     */
    public static function isTransactionActive(): bool
    {
        return self::$transactionActive;
    }

    /**
     * Reset the manager state.
     */
    public static function reset(): void
    {
        self::$transactionActive = false;
    }

}
