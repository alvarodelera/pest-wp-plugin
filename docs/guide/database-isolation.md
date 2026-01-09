# Database Isolation

PestWP provides automatic database isolation to ensure each test runs in a clean state.

## Overview

Database isolation means each test starts with a fresh database state and any changes made during the test are rolled back afterward. This prevents tests from affecting each other and ensures consistent, reproducible results.

## Enabling Database Isolation

Add the `InteractsWithDatabase` trait to your tests in `Pest.php`:

```php
<?php

// tests/Pest.php

use PestWP\Concerns\InteractsWithDatabase;

// Apply to all integration tests
uses(InteractsWithDatabase::class)->in('Integration');

// Or use the helper function
uses(PestWP\databaseIsolation())->in('Integration');
```

## How It Works

### Transaction-Based Isolation

PestWP uses database transactions for isolation:

1. **Before Each Test**: A transaction is started
2. **During Test**: All database operations occur within the transaction
3. **After Each Test**: The transaction is rolled back

This is extremely fast because no actual data needs to be deleted.

```
┌─────────────────────────────────────┐
│ Test Execution                       │
├─────────────────────────────────────┤
│ 1. BEGIN TRANSACTION                │
│ 2. Create posts, users, etc.        │
│ 3. Run assertions                   │
│ 4. ROLLBACK                         │
│ 5. Database is clean for next test  │
└─────────────────────────────────────┘
```

### Savepoints (Nested Transactions)

For complex test scenarios, PestWP supports savepoints:

```php
it('supports savepoints for nested isolation', function () {
    $post1 = createPost(['post_title' => 'First Post']);
    
    // Create a savepoint
    $this->createSavepoint('before_second_post');
    
    $post2 = createPost(['post_title' => 'Second Post']);
    
    // Both posts exist
    expect(get_post($post1->ID))->not->toBeNull();
    expect(get_post($post2->ID))->not->toBeNull();
    
    // Rollback to savepoint
    $this->rollbackToSavepoint('before_second_post');
    
    // First post still exists, second is gone
    expect(get_post($post1->ID))->not->toBeNull();
    expect(get_post($post2->ID))->toBeNull();
});
```

## SQLite vs MySQL

### SQLite (Default)

SQLite is the default database for testing:

- **Fast**: No network overhead
- **Isolated**: Each test suite gets its own database file
- **Simple**: No server configuration needed

```php
// Automatically uses SQLite
uses(PestWP\databaseIsolation())->in('Integration');
```

### MySQL

For testing MySQL-specific features:

```xml
<!-- phpunit.xml -->
<php>
    <env name="PEST_WP_SQLITE" value="false"/>
    <env name="WP_TESTS_DB_NAME" value="wordpress_test"/>
    <env name="WP_TESTS_DB_USER" value="root"/>
    <env name="WP_TESTS_DB_PASSWORD" value=""/>
    <env name="WP_TESTS_DB_HOST" value="localhost"/>
</php>
```

## Best Practices

### 1. Use Integration Directory

Keep tests that need database access in the `Integration` directory:

```
tests/
├── Unit/           # Fast tests, no database
├── Integration/    # Tests with database isolation
└── Browser/        # E2E tests
```

### 2. Minimal Data Creation

Only create the data you need:

```php
// Good: Creates only what's needed
it('publishes a post', function () {
    $post = createPost(['post_status' => 'draft']);
    
    wp_publish_post($post->ID);
    
    expect(get_post($post->ID))->toBePublished();
});

// Bad: Creates unnecessary data
it('publishes a post', function () {
    $user = createUser('author');
    $category = createTerm('News', 'category');
    $tag = createTerm('Featured', 'post_tag');
    $post = createPost([
        'post_status' => 'draft',
        'post_author' => $user->ID, // Not needed for this test
    ]);
    
    wp_publish_post($post->ID);
    
    expect(get_post($post->ID))->toBePublished();
});
```

### 3. Use beforeEach for Shared Setup

```php
beforeEach(function () {
    $this->admin = createUser('administrator');
    $this->post = createPost();
});

it('allows admin to edit', function () {
    loginAs($this->admin);
    expect(current_user_can('edit_post', $this->post->ID))->toBeTrue();
});

it('allows admin to delete', function () {
    loginAs($this->admin);
    expect(current_user_can('delete_post', $this->post->ID))->toBeTrue();
});
```

### 4. Avoid Persistent State

Don't rely on data from previous tests:

```php
// Bad: Depends on data from another test
it('creates a post', function () {
    createPost(['post_title' => 'First']);
});

it('counts posts', function () {
    // This will fail because "First" was rolled back
    expect(wp_count_posts()->publish)->toBeGreaterThan(0);
});

// Good: Each test is self-contained
it('counts posts', function () {
    createPost(['post_title' => 'Test Post']);
    
    expect(wp_count_posts()->publish)->toBeGreaterThan(0);
});
```

## Testing Without Isolation

For unit tests that don't need WordPress:

```php
// tests/Unit/HelperTest.php - No database isolation needed

it('formats currency', function () {
    expect(format_price(1000))->toBe('$10.00');
});

it('validates email', function () {
    expect(is_valid_email('test@example.com'))->toBeTrue();
    expect(is_valid_email('invalid'))->toBeFalse();
});
```

## Debugging Database Issues

### View SQL Queries

```php
it('debugs queries', function () {
    global $wpdb;
    
    // Enable query logging
    define('SAVEQUERIES', true);
    
    $post = createPost();
    
    // View all queries
    dump($wpdb->queries);
});
```

### Check Database State

```php
it('checks database state', function () {
    global $wpdb;
    
    $post = createPost(['post_title' => 'Debug Post']);
    
    // Query directly
    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_title FROM {$wpdb->posts} WHERE ID = %d",
            $post->ID
        )
    );
    
    expect($result)->toBe('Debug Post');
});
```

## Performance Considerations

### Transaction Speed

Transactions are fast, but some operations force commits:

| Operation | Isolation Preserved |
|-----------|---------------------|
| `wp_insert_post()` | Yes |
| `wp_insert_user()` | Yes |
| `update_option()` | Yes |
| DDL (CREATE TABLE) | No* |
| External API calls | No* |

\* These operations may not be fully rolled back.

### Parallel Testing

Database isolation works with parallel testing:

```bash
# Each parallel process gets its own database
./vendor/bin/pest --parallel
```

## Common Issues

### "Transaction already in progress"

This happens when nested transactions aren't properly handled:

```php
// Bad: Manual transaction inside isolated test
it('has nested transaction issue', function () {
    global $wpdb;
    $wpdb->query('START TRANSACTION'); // Conflicts!
    
    createPost();
    
    $wpdb->query('COMMIT');
});

// Good: Use savepoints instead
it('uses savepoints', function () {
    $this->createSavepoint('my_savepoint');
    
    createPost();
    
    $this->rollbackToSavepoint('my_savepoint');
});
```

### Data Persisting Between Tests

Ensure isolation is enabled for the test directory:

```php
// tests/Pest.php
uses(PestWP\databaseIsolation())->in('Integration');
```

## Next Steps

- [Factories](factories.md) - Create test data
- [Fixtures](fixtures.md) - Reusable test data
- [Configuration](configuration.md) - Database configuration
