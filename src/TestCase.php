<?php

declare(strict_types=1);

namespace PestWP;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for WordPress tests.
 *
 * This class provides the bridge between Pest/PHPUnit 12 and the WordPress
 * test suite. It handles the compatibility layer and provides factory methods
 * for creating WordPress objects.
 *
 * Note: The actual WP_UnitTestCase integration will be loaded dynamically
 * when WordPress is bootstrapped. This class serves as the primary interface.
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * The factory instance for creating WordPress objects.
     *
     * @var object|null
     */
    protected static ?object $wpFactory = null;

    /**
     * Whether WordPress has been bootstrapped.
     */
    protected static bool $wpLoaded = false;

    /**
     * Set up before the test class runs.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::ensureWordPressLoaded();
    }

    /**
     * Set up before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (static::$wpLoaded) {
            $this->startTransaction();
        }
    }

    /**
     * Tear down after each test.
     */
    protected function tearDown(): void
    {
        if (static::$wpLoaded) {
            $this->rollbackTransaction();
        }

        parent::tearDown();
    }

    /**
     * Ensure WordPress is loaded.
     */
    protected static function ensureWordPressLoaded(): void
    {
        if (static::$wpLoaded) {
            return;
        }

        // WordPress loading is handled by the bootstrap file
        // This method is here for future dynamic loading support
        static::$wpLoaded = defined('ABSPATH') && function_exists('wp');
    }

    /**
     * Start a database transaction for test isolation.
     */
    protected function startTransaction(): void
    {
        global $wpdb;

        if ($wpdb instanceof \wpdb) {
            // SQLite doesn't support nested transactions, but we can use savepoints
            $wpdb->query('SAVEPOINT pest_test_start');
        }
    }

    /**
     * Rollback the database transaction.
     */
    protected function rollbackTransaction(): void
    {
        global $wpdb;

        if ($wpdb instanceof \wpdb) {
            $wpdb->query('ROLLBACK TO SAVEPOINT pest_test_start');
        }
    }

    /**
     * Get the WordPress factory for creating test data.
     *
     * @return object The factory instance
     */
    protected static function factory(): object
    {
        if (static::$wpFactory === null) {
            // Will be set by the bootstrap process
            static::$wpFactory = new class () {
                /**
                 * @param  array<mixed>  $arguments
                 */
                public function __call(string $name, array $arguments): mixed
                {
                    throw new \RuntimeException(
                        'WordPress factory not initialized. Make sure WordPress is properly bootstrapped.',
                    );
                }
            };
        }

        return static::$wpFactory;
    }

    /**
     * Set the WordPress factory instance.
     *
     * @internal Used by the bootstrap process
     */
    public static function setFactory(object $factory): void
    {
        static::$wpFactory = $factory;
    }

    /**
     * Mark WordPress as loaded.
     *
     * @internal Used by the bootstrap process
     */
    public static function markWordPressLoaded(): void
    {
        static::$wpLoaded = true;
    }

    /**
     * Set the current user.
     *
     * @param  int  $userId  The user ID to set as current
     */
    protected function setCurrentUser(int $userId): void
    {
        if (function_exists('wp_set_current_user')) {
            wp_set_current_user($userId);
        }
    }

    /**
     * Create and log in as a user with the specified role.
     *
     * @param  string  $role  The user role (e.g., 'administrator', 'editor')
     * @return int The created user ID
     */
    protected function loginAs(string $role): int
    {
        $userId = $this->createUser($role);
        $this->setCurrentUser($userId);

        return $userId;
    }

    /**
     * Create a user with the specified role.
     *
     * @param  string  $role  The user role
     * @return int The created user ID
     */
    protected function createUser(string $role = 'subscriber'): int
    {
        if (! function_exists('wp_insert_user')) {
            throw new \RuntimeException('WordPress is not loaded');
        }

        $userData = [
            'user_login' => 'testuser_' . uniqid(),
            'user_pass' => wp_generate_password(),
            'user_email' => 'test_' . uniqid() . '@example.com',
            'role' => $role,
        ];

        $userId = wp_insert_user($userData);

        if (is_wp_error($userId)) {
            throw new \RuntimeException('Failed to create user: ' . $userId->get_error_message());
        }

        return $userId;
    }

    /**
     * Log out the current user.
     */
    protected function logout(): void
    {
        if (function_exists('wp_set_current_user')) {
            wp_set_current_user(0);
        }
    }
}
