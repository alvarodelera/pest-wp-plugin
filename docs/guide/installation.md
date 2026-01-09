# Installation

This guide will walk you through installing PestWP in your WordPress plugin or theme.

## Requirements

- **PHP 8.3** or higher
- **Composer** for dependency management
- **Pest PHP 4.0** or higher

## Install via Composer

```bash
composer require --dev alvarodelera/pest-wp-plugin
```

This will install PestWP along with its dependencies, including Pest PHP.

## Initialize Pest

If you haven't already initialized Pest in your project:

```bash
./vendor/bin/pest --init
```

This creates the `tests/` directory structure and a `Pest.php` configuration file.

## Project Structure

After installation, your project should look like this:

```
your-plugin/
├── src/
├── tests/
│   ├── Pest.php           # Pest configuration
│   ├── TestCase.php       # Base test case (optional)
│   ├── Unit/              # Unit tests
│   └── Integration/       # Integration tests
├── composer.json
└── phpunit.xml
```

## Configure Pest.php

Edit your `tests/Pest.php` file to include PestWP:

```php
<?php

use PestWP\Concerns\InteractsWithDatabase;

// Apply database isolation to integration tests
uses(InteractsWithDatabase::class)->in('Integration');

// Or use the helper function
uses(PestWP\databaseIsolation())->in('Integration');
```

## WordPress Path Configuration

PestWP needs to know where WordPress is located. You can configure this in several ways:

### Option 1: Environment Variable

Set the `WP_PATH` environment variable:

```bash
export WP_PATH=/path/to/wordpress
```

Or in your `phpunit.xml`:

```xml
<php>
    <env name="WP_PATH" value="/path/to/wordpress"/>
</php>
```

### Option 2: Composer Extra

Add to your `composer.json`:

```json
{
    "extra": {
        "pest-wp": {
            "wordpress-path": "/path/to/wordpress"
        }
    }
}
```

### Option 3: Auto-Detection

If WordPress is in a parent directory or standard location, PestWP will attempt to auto-detect it.

## Database Configuration

PestWP supports SQLite for testing (no MySQL required) or a dedicated MySQL test database.

### SQLite (Default)

SQLite is used by default for fast, isolated testing:

```php
// tests/Pest.php
uses(PestWP\databaseIsolation())->in('Integration');
```

### MySQL

To use MySQL, set the database credentials:

```xml
<!-- phpunit.xml -->
<php>
    <env name="WP_TESTS_DB_NAME" value="wordpress_test"/>
    <env name="WP_TESTS_DB_USER" value="root"/>
    <env name="WP_TESTS_DB_PASSWORD" value=""/>
    <env name="WP_TESTS_DB_HOST" value="localhost"/>
</php>
```

## Browser Testing Setup

For browser testing with Playwright:

```bash
# Install browser binaries
./vendor/bin/pest-setup-browser

# Or manually install Playwright
npx playwright install chromium
```

## Verify Installation

Create a simple test to verify everything works:

```php
<?php

// tests/Unit/ExampleTest.php

it('can use PestWP', function () {
    expect(true)->toBeTrue();
});
```

Run the tests:

```bash
./vendor/bin/pest
```

## Next Steps

- [Quick Start Guide](getting-started.md) - Write your first WordPress test
- [Configuration](configuration.md) - Customize PestWP settings
- [Factories](factories.md) - Learn to create test data
