# Configuration

This guide covers all configuration options for PestWP.

## Pest.php Configuration

The main configuration file is `tests/Pest.php`:

```php
<?php

use PestWP\Concerns\InteractsWithDatabase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The base test case class that all tests extend.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Database Isolation
|--------------------------------------------------------------------------
|
| Apply database isolation to integration tests.
|
*/

uses(InteractsWithDatabase::class)->in('Integration');

// Or use the helper function:
// uses(PestWP\databaseIsolation())->in('Integration');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Custom expectations are automatically registered by PestWP.
| You can add your own custom expectations here.
|
*/

expect()->extend('toBeValidPost', function () {
    expect($this->value)
        ->toBeInstanceOf(WP_Post::class)
        ->and($this->value->post_status)->not->toBe('auto-draft');
    
    return $this;
});
```

## PHPUnit Configuration

Configure environment variables in `phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    
    <php>
        <!-- WordPress Path -->
        <env name="WP_PATH" value="/path/to/wordpress"/>
        
        <!-- Database Configuration -->
        <env name="WP_TESTS_DB_NAME" value="wordpress_test"/>
        <env name="WP_TESTS_DB_USER" value="root"/>
        <env name="WP_TESTS_DB_PASSWORD" value=""/>
        <env name="WP_TESTS_DB_HOST" value="localhost"/>
        
        <!-- Test Configuration -->
        <env name="WP_TESTS_DOMAIN" value="localhost"/>
        <env name="WP_TESTS_EMAIL" value="admin@example.com"/>
        <env name="WP_TESTS_TITLE" value="Test Site"/>
        
        <!-- Enable/disable features -->
        <env name="PEST_WP_SQLITE" value="true"/>
    </php>
    
    <source>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </source>
</phpunit>
```

## Environment Variables

### WordPress Path

| Variable | Description | Default |
|----------|-------------|---------|
| `WP_PATH` | Path to WordPress installation | Auto-detected |
| `WP_CONTENT_DIR` | Path to wp-content directory | `{WP_PATH}/wp-content` |
| `WP_PLUGIN_DIR` | Path to plugins directory | `{WP_CONTENT_DIR}/plugins` |

### Database

| Variable | Description | Default |
|----------|-------------|---------|
| `WP_TESTS_DB_NAME` | Test database name | `wordpress_test` |
| `WP_TESTS_DB_USER` | Database username | `root` |
| `WP_TESTS_DB_PASSWORD` | Database password | (empty) |
| `WP_TESTS_DB_HOST` | Database host | `localhost` |
| `PEST_WP_SQLITE` | Use SQLite instead of MySQL | `true` |

### Site Configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `WP_TESTS_DOMAIN` | Test site domain | `localhost` |
| `WP_TESTS_EMAIL` | Admin email | `admin@example.com` |
| `WP_TESTS_TITLE` | Site title | `Test Site` |
| `WP_DEBUG` | Enable WordPress debug mode | `true` |

### Browser Testing

| Variable | Description | Default |
|----------|-------------|---------|
| `PEST_BROWSER_HEADLESS` | Run browser in headless mode | `true` |
| `PEST_BROWSER_SLOW_MO` | Slow down browser actions (ms) | `0` |
| `PEST_BROWSER_TIMEOUT` | Default timeout (ms) | `30000` |
| `PEST_SCREENSHOT_DIR` | Screenshot output directory | `tests/__screenshots__` |

## Composer Configuration

Add PestWP configuration to `composer.json`:

```json
{
    "extra": {
        "pest-wp": {
            "wordpress-path": "/path/to/wordpress",
            "sqlite": true,
            "plugins": [
                "my-plugin/my-plugin.php"
            ],
            "theme": "my-theme"
        }
    }
}
```

### Options

| Key | Description | Default |
|-----|-------------|---------|
| `wordpress-path` | Path to WordPress | Auto-detected |
| `sqlite` | Use SQLite | `true` |
| `plugins` | Plugins to activate | `[]` |
| `theme` | Theme to activate | Default theme |
| `multisite` | Enable multisite | `false` |

## Directory Structure

Recommended project structure:

```
your-plugin/
├── src/                      # Plugin source code
├── tests/
│   ├── Pest.php              # Pest configuration
│   ├── TestCase.php          # Base test case (optional)
│   ├── Unit/                 # Unit tests (no WordPress)
│   │   └── ExampleTest.php
│   ├── Integration/          # Integration tests (with WordPress)
│   │   └── PostTest.php
│   ├── Browser/              # Browser/E2E tests
│   │   └── AdminTest.php
│   ├── __fixtures__/         # Test fixtures
│   │   ├── data.json
│   │   └── users.yaml
│   └── __snapshots__/        # Snapshot files
│       └── ExampleTest/
├── composer.json
├── phpunit.xml
└── playwright.config.js      # Browser test config (optional)
```

## Test Organization

### Unit Tests

Unit tests don't load WordPress and are fast:

```php
<?php

// tests/Unit/HelperTest.php

it('formats currency correctly', function () {
    expect(format_price(1000))->toBe('$10.00');
});
```

### Integration Tests

Integration tests load WordPress and use database isolation:

```php
<?php

// tests/Integration/PostTest.php

use function PestWP\createPost;

it('creates posts', function () {
    $post = createPost(['post_title' => 'Hello']);
    
    expect($post)->toBePublished();
});
```

### Browser Tests

Browser tests use Playwright for end-to-end testing:

```php
<?php

// tests/Browser/AdminTest.php

use function PestWP\Browser\visit;

it('loads the admin dashboard', function () {
    visit('/wp-admin/')
        ->assertSee('Dashboard');
});
```

## Custom Test Case

Create a base test case for shared setup:

```php
<?php

// tests/TestCase.php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Common setup for all tests
    }
    
    protected function tearDown(): void
    {
        // Common cleanup
        
        parent::tearDown();
    }
}
```

Use it in `Pest.php`:

```php
uses(Tests\TestCase::class)->in('Feature');
```

## Performance Tips

1. **Use SQLite** - Faster than MySQL for tests
2. **Parallel Testing** - Run tests in parallel with `--parallel`
3. **Database Isolation** - Use transactions for rollback
4. **Minimal Fixtures** - Only create necessary test data

```bash
# Run tests in parallel
./vendor/bin/pest --parallel

# Run only unit tests (faster)
./vendor/bin/pest tests/Unit
```

## Next Steps

- [Factories](factories.md) - Create test data
- [Database Isolation](database-isolation.md) - Understand isolation
- [CI/CD](ci-cd.md) - Configure continuous integration
