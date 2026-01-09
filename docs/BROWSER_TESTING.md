# Browser Testing with Pest

This guide explains how to configure and run end-to-end (E2E) browser tests in WordPress using **Pest Browser Testing** (based on Playwright).

> **Note**: This plugin integrates the [official Pest Browser Testing](https://pestphp.com/docs/browser-testing) with WordPress, providing WordPress-specific testing helpers.

## Quick Start

### 1. Install Dependencies

The plugin already includes `pestphp/pest-plugin-browser`, you just need to install the browsers:

```bash
composer install
./vendor/bin/pest --browser-install
```

### 2. Start WordPress Server

Use the built-in development server (uses .pest/wordpress installation):

```bash
vendor/bin/pest-wp-serve
```

Or configure with an existing WordPress installation:

```bash
vendor/bin/pest-setup-browser --url http://localhost:8080 --user admin --pass password
```

### 3. Run Browser Tests

```bash
./vendor/bin/pest --browser tests/Browser/     # Run browser tests
./vendor/bin/pest --browser --headed           # Run with visible browser
```

## Configuration

### Automatic Configuration with pest-wp-serve

The easiest way to run browser tests is using the built-in server:

```bash
# Start the server (uses .pest/wordpress)
vendor/bin/pest-wp-serve

# In another terminal, run tests
./vendor/bin/pest --browser tests/Browser/
```

Default credentials:
- **URL**: http://localhost:8080
- **User**: admin
- **Password**: password

### Manual Configuration

If you prefer to configure manually, add the `browser()` function in `tests/Pest.php`:

```php
function browser(): array
{
    return [
        'base_url' => 'http://localhost:8080',
        'admin_user' => 'admin',
        'admin_password' => 'password',
    ];
}
```

### Environment Variables

You can also use environment variables (used as fallback):

```bash
export WP_BASE_URL=http://localhost:8080
export WP_ADMIN_USER=admin
export WP_ADMIN_PASSWORD=password
```

Or create a `.env.testing` file:

```env
WP_BASE_URL=http://localhost:8080
WP_ADMIN_USER=admin
WP_ADMIN_PASSWORD=password
```

## Zero-Login Strategy

Browser tests use the "zero-login" strategy to optimize speed:

1. **Authentication State**: Login is performed once and the state is saved
2. **State Storage**: Auth cookies are stored in `.pest/state/admin.json`
3. **Reuse**: All tests reuse this state, avoiding repeated logins

### Advantages

- **Speed**: Tests load directly into the dashboard (< 3s vs ~10s with login)
- **Security**: Credentials are only used once
- **Isolation**: Each test maintains its own context but shares authentication

### Using Auth State

```php
use function PestWP\Functions\hasBrowserAuthState;
use function PestWP\Functions\loadBrowserAuthState;
use function PestWP\Functions\saveBrowserAuthState;

// Check if stored auth exists
if (hasBrowserAuthState()) {
    $state = loadBrowserAuthState();
    // Use state for authenticated requests
}

// Save auth state after login
$state = ['cookies' => [...], 'origins' => [...]];
saveBrowserAuthState($state);
```

## Writing Tests with Pest Browser Testing

### Basic Example

Create a file in `tests/Browser/`:

```php
<?php

declare(strict_types=1);

it('can access WordPress dashboard', function () {
    $config = browser();
    
    visit($config['base_url'] . '/wp-login.php')
        ->type('user_login', $config['admin_user'])
        ->type('user_pass', $config['admin_password'])
        ->press('Log In')
        ->assertPathBeginsWith('/wp-admin')
        ->assertSee('Dashboard');
});

it('can create a new post', function () {
    $config = browser();
    
    visit($config['base_url'] . '/wp-admin/post-new.php')
        ->type('[aria-label="Add title"]', 'My Test Post')
        ->press('Publish')
        ->wait(1)
        ->press('Publish') // Confirm
        ->assertSee('Post published');
});
```

### Pest Browser Syntax

Pest Browser uses `visit()` which returns a chainable `$page` object:

```php
// Simple visit
$page = visit('/');
$page->assertSee('Welcome');

// Chained
visit('/wp-admin/')
    ->click('Posts')
    ->assertSee('All Posts');

// With configuration
visit('/')
    ->on()->mobile()     // Mobile viewport
    ->inDarkMode();      // Dark mode
```

### Available Methods

Pest Browser Testing provides a fluent API for interacting with the browser:

```php
$page = visit('/');

// Navigation
$page->navigate('/other-page');

// Form interaction
$page->type('selector', 'text')      // Type in input
    ->press('Button Text')            // Click button by text
    ->click('selector')               // Click by selector
    ->check('checkbox')               // Check checkbox
    ->select('dropdown', 'value');    // Select option

// Assertions
$page->assertSee('text')              // Verify visible text
    ->assertDontSee('text')           // Verify text not visible
    ->assertPresent('selector')       // Verify element exists
    ->assertValue('input', 'value')   // Verify input value
    ->assertPathIs('/expected');      // Verify URL

// Utilities
$page->wait(2)                        // Wait 2 seconds
    ->screenshot('name');             // Take screenshot
```

For more methods, see the [official Pest Browser Testing documentation](https://pestphp.com/docs/browser-testing).

## WP Admin Locators

PestWP provides helper functions for building URLs and CSS selectors for WordPress admin UI elements.

### URL Helpers

```php
use function PestWP\Functions\adminUrl;
use function PestWP\Functions\loginUrl;
use function PestWP\Functions\newPostUrl;
use function PestWP\Functions\editPostUrl;
use function PestWP\Functions\postsListUrl;

// Build admin URLs
loginUrl();                    // /wp-login.php
adminUrl();                    // /wp-admin/
adminUrl('edit.php');          // /wp-admin/edit.php
adminUrl('my-plugin');         // /wp-admin/admin.php?page=my-plugin

// Post URLs
newPostUrl();                  // New post
newPostUrl('page');            // New page
editPostUrl(123);              // Edit post ID 123
postsListUrl('post', 'draft'); // List drafts
```

### Gutenberg Selectors

```php
use function PestWP\Functions\postTitleSelector;
use function PestWP\Functions\publishButtonSelector;
use function PestWP\Functions\blockSelector;
use function PestWP\Functions\editorNoticeSelector;

it('can create a post using locators', function () {
    $config = browser();
    
    visit($config['base_url'] . newPostUrl())
        ->wait(1) // Wait for Gutenberg to load
        ->type(postTitleSelector(), 'My Post')
        ->click(publishButtonSelector())
        ->wait(1)
        ->click(publishButtonSelector())
        ->assertSee('Post published');
});

// Target specific blocks
blockSelector('core/paragraph');  // [data-type='core/paragraph']
blockSelector('core/heading');    // [data-type='core/heading']
```

### Admin UI Selectors

```php
use function PestWP\Functions\menuSelector;
use function PestWP\Functions\noticeSelector;
use function PestWP\Functions\tableRowSelector;
use function PestWP\Functions\buttonSelector;

// Menu navigation
menuSelector('Posts');           // Admin menu item
submenuSelector('Settings', 'General'); // Submenu item

// Notices
noticeSelector('success');       // Success notices
noticeSelector('error');         // Error notices

// Data tables
tableRowSelector('My Post');     // Row by title
rowActionSelector('edit');       // Row action link
```

## Advanced Configuration

### Configure Browsers

By default, Pest uses Chrome. You can change this in `tests/Pest.php`:

```php
// In tests/Pest.php
pest()->browser()
    ->inFirefox();  // Use Firefox instead of Chrome

// Or Safari
pest()->browser()
    ->inSafari();
```

## Reports and Debugging

### View Screenshots

Screenshots are automatically saved on failures:

```bash
# Run tests
./vendor/bin/pest --browser

# Screenshots are saved in:
# tests/Browser/Screenshots/
```

### Debugging

```bash
# Headed mode (visible browser)
./vendor/bin/pest --browser --headed

# Debug mode (pause on errors, open browser)
./vendor/bin/pest --debug
```

To pause during a test:

```php
it('debugs a page', function () {
    $config = browser();
    
    $page = visit($config['base_url'] . '/wp-admin/')
        ->debug(); // Pause execution for inspection
});
```

### Verbose Output

```bash
./vendor/bin/pest --browser -v
```

## Selectors and Waits

### Best Practices for Selectors

```php
$page = visit('/wp-admin/');

// ✅ Good: Use visible text
$page->press('Publish');

// ✅ Good: Use ARIA attributes
$page->type('[aria-label="Add title"]', 'My Post');

// ✅ Good: Use data-testid (with @ shortcut)
$page->click('@save-button'); // Equals [data-testid="save-button"]

// ⚠️ Avoid: Fragile selectors
$page->click('.wp-block-post-title');
```

### Waits

```php
$page = visit('/wp-admin/post-new.php')
    ->wait(2)                              // Wait 2 seconds
    ->assertPresent('.editor-post-title')  // Verify element exists
    ->assertSee('Add title');              // Verify visible text
```

## WordPress Server Options

### Option 1: pest-wp-serve (Recommended)

Uses PHP's built-in server with the .pest/wordpress installation:

```bash
# Start server
vendor/bin/pest-wp-serve

# With custom port
vendor/bin/pest-wp-serve --port=8888

# Find available port automatically
vendor/bin/pest-wp-serve --find-port
```

### Option 2: wp-env

[wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) is the official WordPress local environment tool.

```bash
# Install wp-env globally
npm install -g @wordpress/env

# Start WordPress (default: http://localhost:8888)
wp-env start

# Configure PestWP
vendor/bin/pest-setup-browser --url http://localhost:8888 --user admin --pass password
```

### Option 3: Docker Compose

Create a `docker-compose.yml`:

```yaml
version: '3.8'
services:
  wordpress:
    image: wordpress:latest
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DB_NAME: wordpress
    volumes:
      - ./wp-content/plugins/my-plugin:/var/www/html/wp-content/plugins/my-plugin
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
      MYSQL_ROOT_PASSWORD: root
```

```bash
# Start containers
docker-compose up -d

# Configure PestWP
vendor/bin/pest-setup-browser --url http://localhost:8080 --user admin --pass password
```

### Option 4: Local Environments (MAMP, XAMPP, etc.)

```bash
# Configure with your local URL
vendor/bin/pest-setup-browser --url http://mysite.local --user admin --pass yourpassword
```

## Troubleshooting

### Error: "Browser plugin not found"

Make sure you have installed dependencies:

```bash
composer install
./vendor/bin/pest --browser-install
```

### Tests fail with "Cannot connect to browser"

Verify browsers are installed:

```bash
./vendor/bin/pest --browser-install
```

### WordPress not responding

Verify that:
- WordPress is running at the configured URL
- The `browser()` function has the correct URL
- No firewalls are blocking access

### Debug Configuration

```php
// In your test
it('shows browser config', function () {
    $config = browser();
    dump($config); // View current configuration
});
```

## Environment Variables for CI/CD

```bash
# Enable browser tests
PEST_BROWSER_TESTS=true

# Skip browser tests
PEST_SKIP_BROWSER=true

# WordPress configuration
WP_BASE_URL=http://localhost:8080
WP_ADMIN_USER=admin
WP_ADMIN_PASSWORD=password
```

## Resources

- [Pest Browser Testing Documentation](https://pestphp.com/docs/browser-testing)
- [WordPress Testing Handbook](https://make.wordpress.org/core/handbook/testing/)
- [PestWP Documentation](../README.md)

## Differences from Pure Playwright

This plugin uses **Pest Browser Testing** which:

**Advantages:**
- Native PHP syntax (no TypeScript needed)
- Direct Pest integration
- Same API as Laravel Dusk (familiar)
- Automatic screenshots on failures
- Simplified configuration

**Considerations:**
- Based on Playwright under the hood
- Fewer advanced options than pure Playwright
- Documentation evolving (Pest Browser is new)
