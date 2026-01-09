# Migration Guide: wp-browser/Codeception to PestWP

This guide helps you migrate your WordPress tests from [wp-browser](https://wpbrowser.wptestkit.dev/) (Codeception) to PestWP.

## Overview

| Feature | wp-browser | PestWP |
|---------|------------|--------|
| **Test Framework** | Codeception/PHPUnit | Pest PHP |
| **Database** | MySQL required | SQLite (zero-config) |
| **Isolation** | Transactions or reinstall | SAVEPOINT/ROLLBACK (~1.7ms) |
| **Syntax** | Class-based `$I->` | Functional `expect()->` |
| **Setup Time** | Minutes (config required) | Seconds (auto-install) |
| **CI/CD** | Complex MySQL setup | Simple (no DB needed) |

## Quick Start

### 1. Install PestWP

```bash
composer require --dev pestwp/pest-wp-plugin
composer remove --dev lucatume/wp-browser codeception/codeception
```

### 2. Create Pest Configuration

```php
<?php
// tests/Pest.php

use PestWP\Config;
use PestWP\Database\TransactionManager;

// Load your plugin (same as wp-browser's modules config)
Config::plugins([
    dirname(__DIR__) . '/my-plugin.php',
]);

// Database isolation for integration tests
uses()
    ->beforeEach(fn () => TransactionManager::beginTransaction())
    ->afterEach(fn () => TransactionManager::rollback())
    ->in('Integration');
```

### 3. Run Tests

```bash
./vendor/bin/pest
```

That's it! No MySQL setup, no complex YAML configuration.

---

## Syntax Mapping

### WPUnit Tests (Integration)

**wp-browser (Codeception):**
```php
<?php
class PostsTest extends \Codeception\TestCase\WPTestCase
{
    public function test_creates_post()
    {
        $postId = $this->factory()->post->create([
            'post_title' => 'Test Post',
        ]);
        
        $post = get_post($postId);
        
        $this->assertEquals('Test Post', $post->post_title);
        $this->assertEquals('publish', $post->post_status);
    }
    
    public function test_user_can_edit()
    {
        $userId = $this->factory()->user->create(['role' => 'editor']);
        wp_set_current_user($userId);
        
        $this->assertTrue(current_user_can('edit_posts'));
        $this->assertFalse(current_user_can('manage_options'));
    }
}
```

**PestWP:**
```php
<?php
use function PestWP\createPost;
use function PestWP\createUser;
use function PestWP\loginAs;

it('creates post', function () {
    $post = createPost(['post_title' => 'Test Post']);
    
    expect($post->post_title)->toBe('Test Post')
        ->and($post)->toBePublished();
});

it('user can edit', function () {
    $editor = createUser('editor');
    loginAs($editor);
    
    expect(current_user_can('edit_posts'))->toBeTrue()
        ->and(current_user_can('manage_options'))->toBeFalse();
});
```

### Factory Methods

| wp-browser | PestWP |
|------------|--------|
| `$this->factory()->post->create($args)` | `createPost($args)` (returns `WP_Post`) |
| `$this->factory()->user->create($args)` | `createUser($args)` (returns `WP_User`) |
| `$this->factory()->term->create($args)` | `createTerm($name, $taxonomy, $args)` |
| `$this->factory()->attachment->create($args)` | `createAttachment($file, $parentId, $args)` |
| `$this->factory()->comment->create($args)` | `createComment($postId, $args)` |

### Authentication

| wp-browser | PestWP |
|------------|--------|
| `wp_set_current_user($id)` | `loginAs($user)` |
| `wp_set_current_user(0)` | `logout()` |
| `get_current_user_id()` | `currentUser()->ID` |

### Assertions

| wp-browser (PHPUnit) | PestWP |
|---------------------|--------|
| `$this->assertEquals($a, $b)` | `expect($b)->toBe($a)` |
| `$this->assertTrue($x)` | `expect($x)->toBeTrue()` |
| `$this->assertFalse($x)` | `expect($x)->toBeFalse()` |
| `$this->assertInstanceOf(WP_Post::class, $p)` | `expect($p)->toBeInstanceOf(WP_Post::class)` |
| `$this->assertWPError($error)` | `expect($error)->toBeWPError()` |
| `$this->assertCount(3, $arr)` | `expect($arr)->toHaveCount(3)` |
| `$this->assertContains($x, $arr)` | `expect($arr)->toContain($x)` |

### WordPress-Specific Expectations

PestWP adds WordPress-specific expectations not available in wp-browser:

```php
// Post status
expect($post)->toBePublished();
expect($post)->toBeDraft();
expect($post)->toBeInTrash();

// Metadata
expect($post)->toHaveMeta('key', 'value');
expect($user)->toHaveUserMeta('key', 'value');

// Hooks
expect('init')->toHaveAction($callback, 10);
expect('the_content')->toHaveFilter($callback);

// Capabilities
expect($user)->toHaveCapability('edit_posts');
expect($user)->toHaveRole('editor');

// Post types & taxonomies
expect('my_cpt')->toBeRegisteredPostType();
expect('my_taxonomy')->toBeRegisteredTaxonomy();
```

---

## Configuration Mapping

### codeception.yml to tests/Pest.php

**wp-browser codeception.yml:**
```yaml
modules:
  enabled:
    - WPLoader
  config:
    WPLoader:
      wpRootFolder: /var/www/html
      dbName: wordpress_test
      dbHost: localhost
      dbUser: root
      dbPassword: root
      plugins:
        - my-plugin/my-plugin.php
      theme: twentytwentyfour
```

**PestWP tests/Pest.php:**
```php
<?php
use PestWP\Config;
use PestWP\Database\TransactionManager;

// No wpRootFolder needed - auto-downloads to .pest/wordpress/
// No database config needed - uses SQLite automatically

Config::plugins([
    dirname(__DIR__) . '/my-plugin.php',
]);

Config::theme('twentytwentyfour');

// Database isolation
uses()
    ->beforeEach(fn () => TransactionManager::beginTransaction())
    ->afterEach(fn () => TransactionManager::rollback())
    ->in('Integration');
```

### Database Configuration

**wp-browser:**
- Requires MySQL database
- Needs database credentials in config
- Table prefix configuration
- Often needs separate test database

**PestWP:**
- Uses SQLite automatically
- No database configuration needed
- Faster test execution
- Works in any environment

---

## Module Equivalents

### WPLoader Module

The `WPLoader` module functionality is built into PestWP:

```php
// wp-browser
modules:
  config:
    WPLoader:
      plugins: ['my-plugin/my-plugin.php']
      activatePlugins: ['my-plugin/my-plugin.php']

// PestWP
Config::plugins([dirname(__DIR__) . '/my-plugin.php']);
```

### WPDb Module

Database operations are handled through WordPress functions with automatic isolation:

```php
// wp-browser
$I->havePostInDatabase(['post_title' => 'Test']);
$I->seePostInDatabase(['post_title' => 'Test']);

// PestWP
$post = createPost(['post_title' => 'Test']);
expect(get_post($post->ID))->not->toBeNull();
```

### WPWebDriver/WPBrowser Module

Browser testing in PestWP uses Pest Browser Plugin:

```php
// wp-browser (Codeception)
$I->amOnPage('/wp-login.php');
$I->fillField('user_login', 'admin');
$I->fillField('user_pass', 'password');
$I->click('Log In');
$I->seeInCurrentUrl('/wp-admin');

// PestWP (Pest Browser)
visit('/wp-login.php')
    ->type('user_login', 'admin')
    ->type('user_pass', 'password')
    ->press('Log In')
    ->assertPathBeginsWith('/wp-admin');
```

---

## Test Organization

### File Structure

**wp-browser:**
```
tests/
├── acceptance/           -> tests/Browser/
├── functional/           -> tests/Integration/
├── integration/          -> tests/Integration/
├── unit/                 -> tests/Unit/
├── wpunit/              -> tests/Integration/
├── _bootstrap.php
└── acceptance.suite.yml
```

**PestWP:**
```
tests/
├── Browser/              # E2E browser tests
├── Integration/          # WordPress loaded tests
├── Unit/                 # Pure PHP tests
├── Pest.php             # Configuration
└── bootstrap.php        # Auto-generated
```

### Naming Conventions

**wp-browser:**
```php
class UserPermissionsTest extends WPTestCase
{
    public function test_editor_can_edit_posts()
    {
        // ...
    }
}
```

**PestWP:**
```php
// UserPermissionsTest.php
describe('User Permissions', function () {
    it('editor can edit posts', function () {
        // ...
    });
});

// Or simply:
it('editor can edit posts', function () {
    // ...
});
```

---

## Data Providers

**wp-browser (PHPUnit):**
```php
/**
 * @dataProvider roleProvider
 */
public function test_role_capabilities($role, $canEdit)
{
    $user = $this->factory()->user->create(['role' => $role]);
    wp_set_current_user($user);
    
    $this->assertEquals($canEdit, current_user_can('edit_posts'));
}

public function roleProvider()
{
    return [
        ['editor', true],
        ['author', true],
        ['subscriber', false],
    ];
}
```

**PestWP (Pest datasets):**
```php
dataset('roles', [
    ['editor', true],
    ['author', true],
    ['subscriber', false],
]);

it('has correct edit capability', function ($role, $canEdit) {
    loginAs(createUser($role));
    
    expect(current_user_can('edit_posts'))->toBe($canEdit);
})->with('roles');
```

---

## setUp/tearDown

**wp-browser:**
```php
class MyTest extends WPTestCase
{
    protected $testPost;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->testPost = $this->factory()->post->create();
    }
    
    public function tearDown(): void
    {
        wp_delete_post($this->testPost, true);
        parent::tearDown();
    }
}
```

**PestWP:**
```php
beforeEach(function () {
    $this->testPost = createPost();
});

// No tearDown needed - automatic rollback handles cleanup

it('uses the test post', function () {
    expect(get_post($this->testPost->ID))->not->toBeNull();
});
```

---

## Common Patterns

### Testing Hooks

**wp-browser:**
```php
public function test_action_is_registered()
{
    $this->assertNotFalse(has_action('init', 'my_init_function'));
}
```

**PestWP:**
```php
it('registers init action', function () {
    expect('init')->toHaveAction('my_init_function');
});
```

### Testing REST API

**wp-browser:**
```php
public function test_rest_endpoint()
{
    $request = new WP_REST_Request('GET', '/my-plugin/v1/items');
    $response = rest_do_request($request);
    
    $this->assertEquals(200, $response->get_status());
}
```

**PestWP:**
```php
use function PestWP\rest;

it('returns items from REST endpoint', function () {
    $response = rest()->get('/my-plugin/v1/items');
    
    expect($response->status())->toBe(200);
});
```

### Testing with Authenticated User

**wp-browser:**
```php
public function test_admin_can_access()
{
    $admin = $this->factory()->user->create(['role' => 'administrator']);
    wp_set_current_user($admin);
    
    // test...
}
```

**PestWP:**
```php
it('admin can access', function () {
    loginAs(createUser('administrator'));
    
    // test...
});
```

---

## Migration Checklist

1. [ ] Install PestWP: `composer require --dev pestwp/pest-wp-plugin`
2. [ ] Remove wp-browser: `composer remove --dev lucatume/wp-browser codeception/codeception`
3. [ ] Create `tests/Pest.php` with plugin configuration
4. [ ] Move tests:
   - `tests/wpunit/` -> `tests/Integration/`
   - `tests/unit/` -> `tests/Unit/`
   - `tests/acceptance/` -> `tests/Browser/`
5. [ ] Convert test syntax (class-based to functional)
6. [ ] Replace factories with PestWP helpers
7. [ ] Replace assertions with Pest expectations
8. [ ] Remove `codeception.yml` and suite configs
9. [ ] Update CI/CD (remove MySQL service requirement)
10. [ ] Run tests: `./vendor/bin/pest`

---

## Troubleshooting

### "Class not found" errors

Ensure your plugin is being loaded:
```php
Config::plugins([dirname(__DIR__) . '/my-plugin.php']);
```

### Tests not isolated

Add database isolation in `tests/Pest.php`:
```php
uses()
    ->beforeEach(fn () => TransactionManager::beginTransaction())
    ->afterEach(fn () => TransactionManager::rollback())
    ->in('Integration');
```

### WordPress functions not available

Make sure your test is in the `Integration` folder, not `Unit`.

### Slow tests

PestWP with SQLite should be faster than wp-browser with MySQL. If tests are slow:
- Check for unnecessary external API calls
- Use mocking for slow operations
- Consider parallel execution: `./vendor/bin/pest --parallel`

---

## Benefits After Migration

1. **Faster Setup**: No MySQL configuration needed
2. **Faster Tests**: SQLite + SAVEPOINT isolation (~1.7ms per test)
3. **Simpler CI/CD**: No database services required
4. **Better Syntax**: Pest's expressive, readable syntax
5. **Type Safety**: PHPStan level 9 compatible helpers
6. **Modern PHP**: Built for PHP 8.3+

---

## Need Help?

- [PestWP Documentation](../README.md)
- [Pest PHP Documentation](https://pestphp.com/docs)
- [Report Issues](https://github.com/pestwp/pest-wp-plugin/issues)
