# Migration Guide: WP_UnitTestCase to PestWP

This guide helps you migrate your WordPress tests from the official WordPress core test framework (`WP_UnitTestCase`) to PestWP.

## Overview

| Feature | WP_UnitTestCase | PestWP |
|---------|-----------------|--------|
| **Framework** | PHPUnit | Pest PHP |
| **Database** | MySQL required | SQLite (zero-config) |
| **WordPress Source** | Checkout wp-tests-lib | Auto-downloads |
| **Setup** | Complex (wp-tests-config.php) | Zero configuration |
| **Syntax** | Class-based | Functional |
| **Speed** | ~5-10ms/test | ~1.7ms/test |

## Quick Start

### 1. Install PestWP

```bash
composer require --dev pestwp/pest-wp-plugin
```

### 2. Remove Old Test Framework

```bash
# Remove old bootstrap references
rm tests/bootstrap.php  # Will be auto-generated
rm wp-tests-config.php  # No longer needed

# Remove PHPUnit if not needed elsewhere
composer remove --dev phpunit/phpunit
```

### 3. Create Pest Configuration

```php
<?php
// tests/Pest.php

use PestWP\Config;
use PestWP\Database\TransactionManager;

// Load your plugin
Config::plugins([
    dirname(__DIR__) . '/my-plugin.php',
]);

// Database isolation
uses()
    ->beforeEach(fn () => TransactionManager::beginTransaction())
    ->afterEach(fn () => TransactionManager::rollback())
    ->in('Integration');
```

### 4. Run Tests

```bash
./vendor/bin/pest
```

---

## Syntax Conversion

### Basic Test Structure

**WP_UnitTestCase:**
```php
<?php
class Test_My_Plugin extends WP_UnitTestCase
{
    public function test_example()
    {
        $this->assertTrue(true);
    }
    
    public function test_creates_post()
    {
        $post_id = $this->factory->post->create();
        $post = get_post($post_id);
        
        $this->assertInstanceOf('WP_Post', $post);
    }
}
```

**PestWP:**
```php
<?php
use function PestWP\createPost;

it('is an example', function () {
    expect(true)->toBeTrue();
});

it('creates post', function () {
    $post = createPost();
    
    expect($post)->toBeInstanceOf(WP_Post::class);
});
```

### Factory Methods

| WP_UnitTestCase | PestWP |
|-----------------|--------|
| `$this->factory->post->create($args)` | `createPost($args)` |
| `$this->factory->post->create_and_get($args)` | `createPost($args)` (always returns object) |
| `$this->factory->user->create($args)` | `createUser($args)` |
| `$this->factory->term->create($args)` | `createTerm($name, $taxonomy, $args)` |
| `$this->factory->comment->create($args)` | `createComment($postId, $args)` |
| `$this->factory->attachment->create($args)` | `createAttachment($file, $parentId, $args)` |

### Key Difference: Return Types

**WP_UnitTestCase** factories return IDs by default:
```php
$post_id = $this->factory->post->create();  // Returns int
$post = $this->factory->post->create_and_get();  // Returns WP_Post
```

**PestWP** factories always return objects:
```php
$post = createPost();  // Returns WP_Post
$post->ID;  // Access the ID
```

---

## Assertion Mapping

### PHPUnit to Pest Expectations

| WP_UnitTestCase (PHPUnit) | PestWP (Pest) |
|---------------------------|---------------|
| `$this->assertTrue($x)` | `expect($x)->toBeTrue()` |
| `$this->assertFalse($x)` | `expect($x)->toBeFalse()` |
| `$this->assertEquals($a, $b)` | `expect($b)->toBe($a)` |
| `$this->assertSame($a, $b)` | `expect($b)->toBe($a)` |
| `$this->assertNotEquals($a, $b)` | `expect($b)->not->toBe($a)` |
| `$this->assertNull($x)` | `expect($x)->toBeNull()` |
| `$this->assertNotNull($x)` | `expect($x)->not->toBeNull()` |
| `$this->assertEmpty($x)` | `expect($x)->toBeEmpty()` |
| `$this->assertNotEmpty($x)` | `expect($x)->not->toBeEmpty()` |
| `$this->assertCount($n, $arr)` | `expect($arr)->toHaveCount($n)` |
| `$this->assertContains($x, $arr)` | `expect($arr)->toContain($x)` |
| `$this->assertArrayHasKey($k, $arr)` | `expect($arr)->toHaveKey($k)` |
| `$this->assertInstanceOf($c, $o)` | `expect($o)->toBeInstanceOf($c)` |
| `$this->assertIsArray($x)` | `expect($x)->toBeArray()` |
| `$this->assertIsString($x)` | `expect($x)->toBeString()` |
| `$this->assertIsInt($x)` | `expect($x)->toBeInt()` |
| `$this->assertStringContainsString($n, $h)` | `expect($h)->toContain($n)` |
| `$this->assertMatchesRegularExpression($p, $s)` | `expect($s)->toMatch($p)` |

### WordPress-Specific Assertions

**WP_UnitTestCase:**
```php
$this->assertWPError($result);
$this->assertNotWPError($result);
```

**PestWP:**
```php
expect($result)->toBeWPError();
expect($result)->not->toBeWPError();

// Additional WP expectations
expect($result)->toHaveErrorCode('invalid_data');
```

### Chaining Assertions

**WP_UnitTestCase:**
```php
$post = get_post($id);
$this->assertInstanceOf('WP_Post', $post);
$this->assertEquals('Test Title', $post->post_title);
$this->assertEquals('publish', $post->post_status);
```

**PestWP:**
```php
$post = get_post($id);

expect($post)
    ->toBeInstanceOf(WP_Post::class)
    ->and($post->post_title)->toBe('Test Title')
    ->and($post)->toBePublished();
```

---

## setUp and tearDown

### Basic setUp/tearDown

**WP_UnitTestCase:**
```php
class Test_My_Plugin extends WP_UnitTestCase
{
    protected $test_post_id;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->test_post_id = $this->factory->post->create();
    }
    
    public function tearDown(): void
    {
        wp_delete_post($this->test_post_id, true);
        parent::tearDown();
    }
    
    public function test_uses_post()
    {
        $post = get_post($this->test_post_id);
        $this->assertNotNull($post);
    }
}
```

**PestWP:**
```php
beforeEach(function () {
    $this->testPost = createPost();
});

// tearDown not needed - automatic rollback handles cleanup

it('uses post', function () {
    expect(get_post($this->testPost->ID))->not->toBeNull();
});
```

### setUpBeforeClass / tearDownAfterClass

**WP_UnitTestCase:**
```php
public static function setUpBeforeClass(): void
{
    parent::setUpBeforeClass();
    // Expensive one-time setup
}

public static function tearDownAfterClass(): void
{
    // Cleanup
    parent::tearDownAfterClass();
}
```

**PestWP:**
```php
beforeAll(function () {
    // One-time setup
});

afterAll(function () {
    // One-time cleanup
});
```

---

## Data Providers

**WP_UnitTestCase:**
```php
/**
 * @dataProvider user_role_provider
 */
public function test_user_capabilities($role, $expected_can_edit)
{
    $user_id = $this->factory->user->create(['role' => $role]);
    wp_set_current_user($user_id);
    
    $this->assertEquals($expected_can_edit, current_user_can('edit_posts'));
}

public function user_role_provider()
{
    return [
        'editor can edit' => ['editor', true],
        'author can edit' => ['author', true],
        'subscriber cannot edit' => ['subscriber', false],
    ];
}
```

**PestWP:**
```php
dataset('user roles', [
    'editor can edit' => ['editor', true],
    'author can edit' => ['author', true],
    'subscriber cannot edit' => ['subscriber', false],
]);

it('checks user capabilities', function ($role, $expectedCanEdit) {
    loginAs(createUser($role));
    
    expect(current_user_can('edit_posts'))->toBe($expectedCanEdit);
})->with('user roles');
```

---

## Test Groups

**WP_UnitTestCase:**
```php
/**
 * @group slow
 * @group external-api
 */
public function test_slow_operation()
{
    // ...
}
```

**PestWP:**
```php
it('performs slow operation', function () {
    // ...
})->group('slow', 'external-api');

// Run specific groups
// vendor/bin/pest --group=slow
```

---

## Skip Tests

**WP_UnitTestCase:**
```php
public function test_requires_extension()
{
    if (!extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick not available');
    }
    // ...
}
```

**PestWP:**
```php
it('requires imagick extension', function () {
    // ...
})->skipUnless(extension_loaded('imagick'), 'Imagick not available');

// Or skip always
it('is not ready')->skip();

// Skip on condition
it('requires windows')->skipOnWindows();
it('requires linux')->skipOnLinux();
```

---

## Common Patterns

### Testing Hooks

**WP_UnitTestCase:**
```php
public function test_action_is_added()
{
    $this->assertNotFalse(
        has_action('init', 'my_plugin_init')
    );
}

public function test_filter_modifies_content()
{
    $result = apply_filters('the_content', 'Hello');
    $this->assertStringContainsString('Modified', $result);
}
```

**PestWP:**
```php
it('adds init action', function () {
    expect('init')->toHaveAction('my_plugin_init');
});

it('modifies content via filter', function () {
    $result = apply_filters('the_content', 'Hello');
    
    expect($result)->toContain('Modified');
});
```

### Testing Post Types

**WP_UnitTestCase:**
```php
public function test_registers_custom_post_type()
{
    do_action('init');
    
    $this->assertTrue(post_type_exists('my_custom_type'));
}
```

**PestWP:**
```php
it('registers custom post type', function () {
    do_action('init');
    
    expect('my_custom_type')->toBeRegisteredPostType();
});
```

### Testing User Capabilities

**WP_UnitTestCase:**
```php
public function test_editor_permissions()
{
    $user_id = $this->factory->user->create(['role' => 'editor']);
    wp_set_current_user($user_id);
    
    $this->assertTrue(current_user_can('edit_posts'));
    $this->assertFalse(current_user_can('manage_options'));
}
```

**PestWP:**
```php
it('checks editor permissions', function () {
    loginAs(createUser('editor'));
    
    expect(current_user_can('edit_posts'))->toBeTrue()
        ->and(current_user_can('manage_options'))->toBeFalse();
});

// Or using expectations directly on user
it('editor has correct capabilities', function () {
    $editor = createUser('editor');
    
    expect($editor)
        ->toHaveCapability('edit_posts')
        ->toHaveRole('editor');
});
```

### Testing Meta Data

**WP_UnitTestCase:**
```php
public function test_saves_post_meta()
{
    $post_id = $this->factory->post->create();
    update_post_meta($post_id, 'my_meta_key', 'my_value');
    
    $this->assertEquals('my_value', get_post_meta($post_id, 'my_meta_key', true));
}
```

**PestWP:**
```php
it('saves post meta', function () {
    $post = createPost();
    update_post_meta($post->ID, 'my_meta_key', 'my_value');
    
    expect($post)->toHaveMeta('my_meta_key', 'my_value');
});
```

### Testing AJAX Handlers

**WP_UnitTestCase:**
```php
public function test_ajax_handler()
{
    $this->_setRole('administrator');
    
    try {
        $this->_handleAjax('my_ajax_action');
    } catch (WPAjaxDieContinueException $e) {
        // Expected
    }
    
    $response = json_decode($this->_last_response);
    $this->assertTrue($response->success);
}
```

**PestWP:**
```php
use function PestWP\ajax;

it('handles ajax request', function () {
    loginAs(createUser('administrator'));
    
    $response = ajax('my_ajax_action', ['data' => 'value']);
    
    expect($response->success)->toBeTrue();
});
```

---

## Configuration Comparison

### wp-tests-config.php vs tests/Pest.php

**WP_UnitTestCase (wp-tests-config.php):**
```php
define('DB_NAME', 'wordpress_tests');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('ABSPATH', '/path/to/wordpress/');
define('WP_DEBUG', true);

$table_prefix = 'wptests_';
```

**PestWP (tests/Pest.php):**
```php
<?php
use PestWP\Config;
use PestWP\Database\TransactionManager;

// No database config needed - uses SQLite
// No ABSPATH needed - auto-downloads WordPress

Config::plugins([dirname(__DIR__) . '/my-plugin.php']);

// Optional: Set constants before WordPress loads
Config::beforeWordPress(function () {
    define('WP_DEBUG', true);
});

uses()
    ->beforeEach(fn () => TransactionManager::beginTransaction())
    ->afterEach(fn () => TransactionManager::rollback())
    ->in('Integration');
```

### Bootstrap Files

**WP_UnitTestCase (tests/bootstrap.php):**
```php
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin()
{
    require dirname(__DIR__) . '/my-plugin.php';
}
add_action('muplugins_loaded', '_manually_load_plugin');

require $_tests_dir . '/includes/bootstrap.php';
```

**PestWP:**
```php
// tests/bootstrap.php - Auto-generated, usually don't need to edit

// Plugin loading is done in tests/Pest.php:
Config::plugins([dirname(__DIR__) . '/my-plugin.php']);
```

---

## File Structure Migration

### Before (WP_UnitTestCase)

```
my-plugin/
├── my-plugin.php
├── phpunit.xml
├── tests/
│   ├── bootstrap.php
│   ├── test-sample.php
│   ├── test-posts.php
│   └── test-users.php
└── bin/
    └── install-wp-tests.sh
```

### After (PestWP)

```
my-plugin/
├── my-plugin.php
├── composer.json
├── tests/
│   ├── Pest.php            # Configuration
│   ├── Integration/        # WordPress tests
│   │   ├── PostsTest.php
│   │   └── UsersTest.php
│   └── Unit/               # Pure PHP tests
│       └── HelpersTest.php
└── .pest/                  # Auto-created
    ├── wordpress/          # WordPress installation
    └── database/           # SQLite database
```

### File Naming

| WP_UnitTestCase | PestWP |
|-----------------|--------|
| `test-*.php` | `*Test.php` |
| `class Test_Sample` | `describe('Sample', ...)` or just functions |

---

## Migration Script

Here's a helper script to automate basic conversions:

```php
#!/usr/bin/env php
<?php
/**
 * Basic migration helper - run on test files
 * Usage: php migrate.php tests/test-sample.php
 */

$file = $argv[1] ?? null;
if (!$file || !file_exists($file)) {
    echo "Usage: php migrate.php <test-file.php>\n";
    exit(1);
}

$content = file_get_contents($file);

// Replace common patterns
$replacements = [
    // Assertions
    '/\$this->assertTrue\((.+?)\);/' => 'expect($1)->toBeTrue();',
    '/\$this->assertFalse\((.+?)\);/' => 'expect($1)->toBeFalse();',
    '/\$this->assertEquals\((.+?), (.+?)\);/' => 'expect($2)->toBe($1);',
    '/\$this->assertNull\((.+?)\);/' => 'expect($1)->toBeNull();',
    '/\$this->assertNotNull\((.+?)\);/' => 'expect($1)->not->toBeNull();',
    '/\$this->assertInstanceOf\([\'"](.+?)[\'"]\s*,\s*(.+?)\);/' => 'expect($2)->toBeInstanceOf($1::class);',
    
    // Factories
    '/\$this->factory->post->create\(/' => 'createPost(',
    '/\$this->factory->user->create\(/' => 'createUser(',
    '/\$this->factory->term->create\(/' => 'createTerm(',
    
    // User switching
    '/wp_set_current_user\((.+?)\);/' => 'loginAs($1);',
];

foreach ($replacements as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

echo $content;
```

---

## Migration Checklist

1. [ ] Install PestWP
2. [ ] Create `tests/Pest.php` configuration
3. [ ] Move test files to `tests/Integration/`
4. [ ] Rename files from `test-*.php` to `*Test.php`
5. [ ] Convert class-based tests to functional syntax
6. [ ] Replace `$this->factory->` with PestWP helpers
7. [ ] Replace PHPUnit assertions with Pest expectations
8. [ ] Remove `extends WP_UnitTestCase`
9. [ ] Remove old bootstrap.php and wp-tests-config.php
10. [ ] Remove install-wp-tests.sh script
11. [ ] Update phpunit.xml or remove if not needed
12. [ ] Update CI/CD configuration (remove MySQL)
13. [ ] Run tests: `./vendor/bin/pest`

---

## Benefits After Migration

1. **Zero Configuration**: No need to download WordPress test suite
2. **No MySQL Required**: SQLite works automatically
3. **Faster Tests**: ~3x faster with SQLite + SAVEPOINT isolation
4. **Better Syntax**: More readable, expressive tests
5. **Type Safety**: PHPStan level 9 compatible
6. **Modern PHP**: Built for PHP 8.3+
7. **Simpler CI/CD**: No database service configuration

---

## Need Help?

- [PestWP Documentation](../README.md)
- [Pest PHP Documentation](https://pestphp.com/docs)
- [Report Issues](https://github.com/pestwp/pest-wp-plugin/issues)
