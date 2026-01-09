# Migration Guide

Migrate from other WordPress testing frameworks to PestWP.

## From WP_UnitTestCase

### Before (WP_UnitTestCase)

```php
<?php

class Test_My_Plugin extends WP_UnitTestCase {
    
    public function setUp(): void {
        parent::setUp();
        $this->admin = $this->factory->user->create(['role' => 'administrator']);
    }
    
    public function test_creates_post() {
        wp_set_current_user($this->admin);
        
        $post_id = wp_insert_post([
            'post_title' => 'Test Post',
            'post_status' => 'publish',
        ]);
        
        $this->assertIsInt($post_id);
        $post = get_post($post_id);
        $this->assertEquals('publish', $post->post_status);
    }
    
    public function test_user_capabilities() {
        $editor = $this->factory->user->create(['role' => 'editor']);
        
        $this->assertTrue(user_can($editor, 'edit_posts'));
        $this->assertFalse(user_can($editor, 'manage_options'));
    }
}
```

### After (PestWP)

```php
<?php

use function PestWP\createPost;
use function PestWP\createUser;
use function PestWP\loginAs;

beforeEach(function () {
    $this->admin = createUser('administrator');
});

it('creates post', function () {
    loginAs($this->admin);
    
    $post = createPost([
        'post_title' => 'Test Post',
        'post_status' => 'publish',
    ]);
    
    expect($post->ID)->toBeInt();
    expect($post)->toBePublished();
});

it('tests user capabilities', function () {
    $editor = createUser('editor');
    
    expect($editor)->toHaveCapability('edit_posts');
    expect($editor)->not->toHaveCapability('manage_options');
});
```

## From wp-browser

### Before (wp-browser with Codeception)

```php
<?php

class PostCest {
    
    public function _before(WpunitTester $I) {
        $this->admin = $I->haveUserInDatabase('admin', 'administrator');
    }
    
    public function createPostTest(WpunitTester $I) {
        $I->loginAs($this->admin);
        
        $postId = $I->havePostInDatabase([
            'post_title' => 'Test Post',
            'post_status' => 'publish',
        ]);
        
        $I->seePostInDatabase(['ID' => $postId]);
        $I->seePostWithTermInDatabase($postId, 'uncategorized', 'category');
    }
    
    public function restApiTest(WpunitTester $I) {
        $response = $I->sendGET('/wp/v2/posts');
        
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }
}
```

### After (PestWP)

```php
<?php

use function PestWP\createPost;
use function PestWP\createUser;
use function PestWP\loginAs;
use function PestWP\Functions\rest;

beforeEach(function () {
    $this->admin = createUser('administrator');
});

it('creates post', function () {
    loginAs($this->admin);
    
    $post = createPost([
        'post_title' => 'Test Post',
        'post_status' => 'publish',
    ]);
    
    expect($post)->toBePublished();
    expect($post)->toHaveTerm('Uncategorized', 'category');
});

it('tests REST API', function () {
    $response = rest()->get('/wp/v2/posts');
    
    expect($response)->toHaveStatus(200);
    expect($response)->toBeSuccessful();
});
```

## From PHPUnit (Plain)

### Before (PHPUnit)

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase {
    
    protected function setUp(): void {
        parent::setUp();
        // Manual WordPress bootstrap
        require_once '/path/to/wordpress/wp-load.php';
    }
    
    public function testOptionsSave(): void {
        update_option('my_option', 'test_value');
        
        $this->assertEquals('test_value', get_option('my_option'));
    }
    
    public function testFilterRegistration(): void {
        add_filter('the_title', 'my_filter', 10);
        
        $this->assertNotFalse(has_filter('the_title', 'my_filter'));
    }
}
```

### After (PestWP)

```php
<?php

use function PestWP\setOption;

it('saves options', function () {
    setOption('my_option', 'test_value');
    
    expect('my_option')->toHaveOption('test_value');
});

it('registers filters', function () {
    add_filter('the_title', 'my_filter', 10);
    
    expect('the_title')->toHaveFilter('my_filter', 10);
});
```

## Common Migration Patterns

### Factory Methods

| WP_UnitTestCase | PestWP |
|-----------------|--------|
| `$this->factory->post->create()` | `createPost()` |
| `$this->factory->user->create()` | `createUser()` |
| `$this->factory->term->create()` | `createTerm()` |
| `$this->factory->attachment->create()` | `createAttachment()` |
| `$this->factory->comment->create()` | `wp_insert_comment()` |

### Assertions

| PHPUnit/WP_UnitTestCase | PestWP |
|-------------------------|--------|
| `$this->assertEquals('publish', $post->post_status)` | `expect($post)->toBePublished()` |
| `$this->assertTrue(user_can($user, 'edit_posts'))` | `expect($user)->can('edit_posts')` |
| `$this->assertInstanceOf(WP_Error::class, $result)` | `expect($result)->toBeWPError()` |
| `$this->assertNotFalse(has_filter('hook', $callback))` | `expect('hook')->toHaveFilter($callback)` |

### Authentication

| WP_UnitTestCase | PestWP |
|-----------------|--------|
| `wp_set_current_user($user_id)` | `loginAs($user_id)` |
| `wp_set_current_user(0)` | `logout()` |
| `wp_get_current_user()` | `currentUser()` |
| `is_user_logged_in()` | `isUserLoggedIn()` |

## Step-by-Step Migration

### 1. Install PestWP

```bash
composer require --dev alvarodelera/pest-wp-plugin
```

### 2. Initialize Pest

```bash
./vendor/bin/pest --init
```

### 3. Configure Pest.php

```php
<?php

// tests/Pest.php

use PestWP\Concerns\InteractsWithDatabase;

uses(InteractsWithDatabase::class)->in('Integration');
```

### 4. Create Directory Structure

```
tests/
├── Pest.php
├── Unit/           # Unit tests (no WordPress)
├── Integration/    # Integration tests (with WordPress)
└── Browser/        # Browser tests
```

### 5. Convert Tests

Convert one test file at a time:

```php
// Before: tests/test-posts.php
class Test_Posts extends WP_UnitTestCase {
    public function test_post_creation() { ... }
}

// After: tests/Integration/PostTest.php
it('creates posts', function () { ... });
```

### 6. Run Tests

```bash
# Run all tests
./vendor/bin/pest

# Run specific directory
./vendor/bin/pest tests/Integration
```

## Gradual Migration

You can run both test suites during migration:

```xml
<!-- phpunit.xml -->
<testsuites>
    <!-- Old tests -->
    <testsuite name="Legacy">
        <directory>tests/legacy</directory>
    </testsuite>
    
    <!-- New PestWP tests -->
    <testsuite name="PestWP">
        <directory>tests/Unit</directory>
        <directory>tests/Integration</directory>
    </testsuite>
</testsuites>
```

## Troubleshooting

### "Function not found" Errors

Ensure WordPress is loaded:

```php
// tests/Pest.php
uses(PestWP\databaseIsolation())->in('Integration');
```

### Database State Leaking

Enable database isolation:

```php
uses(PestWP\Concerns\InteractsWithDatabase::class)->in('Integration');
```

### Slow Tests

Use SQLite for faster tests:

```xml
<php>
    <env name="PEST_WP_SQLITE" value="true"/>
</php>
```

## Benefits of Migration

1. **Cleaner Syntax**: More readable, expressive tests
2. **Less Boilerplate**: No class declarations, less setup code
3. **Better Assertions**: WordPress-specific expectations
4. **Faster Tests**: SQLite and transaction-based isolation
5. **Modern Tooling**: Parallel testing, coverage, architecture testing

## Next Steps

- [Getting Started](getting-started.md) - Write your first test
- [Factories](factories.md) - Create test data
- [Expectations](expectations.md) - Available assertions
- [Configuration](configuration.md) - Configure PestWP
