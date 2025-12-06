# PestWP - Pest Plugin for WordPress

[![Tests](https://github.com/alvarodelera/pest-wp-plugin/actions/workflows/tests.yml/badge.svg)](https://github.com/alvarodelera/pest-wp-plugin/actions/workflows/tests.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](phpstan.neon)
[![Code Style](https://img.shields.io/badge/code%20style-PSR--12-blue.svg)](pint.json)

A modern testing plugin for WordPress using Pest PHP with SQLite database support and automatic database isolation.

## Features

- 🚀 **Modern Testing**: Built on Pest v4 and PHPUnit 12
- 🗄️ **SQLite Integration**: Automatic SQLite database setup for fast, isolated tests
- 🔄 **Database Isolation**: Automatic database state management between tests using SAVEPOINT/ROLLBACK
- 🏭 **Type-Safe Factories**: Strongly-typed helper functions for creating WordPress objects
- 🔧 **Zero Configuration**: Automatic WordPress installation and setup
- 💡 **IDE Support**: Full autocompletion and type checking with PHPStan level 9

## Requirements

- PHP 8.3 or higher
- Composer

## Installation

```bash
composer require --dev pestwp/pest-wp-plugin
```

## Quick Start (< 5 minutes)

Get your first WordPress test running in under 5 minutes:

### 1. Add PestWP to your project

```bash
composer require --dev pestwp/pest-wp-plugin
```

### 2. Create your first test

```php
<?php
// tests/Integration/MyPluginTest.php

use function PestWP\createPost;
use function PestWP\createUser;

it('can create posts', function () {
    $post = createPost(['post_title' => 'Hello World']);
    
    expect($post)->toBeInstanceOf(WP_Post::class)
        ->and($post->post_title)->toBe('Hello World');
});

it('can test user permissions', function () {
    $editor = createUser('editor');
    loginAs($editor);
    
    expect(current_user_can('edit_posts'))->toBeTrue()
        ->and(current_user_can('manage_options'))->toBeFalse();
});
```

### 3. Configure Pest

```php
<?php
// tests/Pest.php

use PestWP\Concerns\InteractsWithDatabase;

// Enable database isolation for integration tests
uses(InteractsWithDatabase::class)->in('Integration');
```

### 4. Run your tests

```bash
./vendor/bin/pest
```

That's it! PestWP automatically:
- Downloads WordPress to `.pest/wordpress/`
- Sets up SQLite database (no MySQL needed)
- Isolates each test with database snapshots
- Provides type-safe factories and helpers

### Basic Test Example

```php
<?php

use function PestWP\createPost;
use function PestWP\createUser;

it('can create and retrieve a post', function () {
    $post = createPost([
        'post_title' => 'My Test Post',
        'post_content' => 'Test content here',
    ]);
    
    expect($post)
        ->toBeInstanceOf(WP_Post::class)
        ->and($post->post_title)->toBe('My Test Post');
});

it('can create users with different roles', function () {
    $editor = createUser('editor');
    
    expect($editor->roles)->toContain('editor');
});
```

## Integration vs Browser Testing

PestWP supports two types of testing. Choose the right one for your needs:

| Feature | Integration Tests | Browser Tests |
|---------|------------------|---------------|
| **Speed** | ⚡ Very fast (~2ms/test) | 🐢 Slower (~500ms/test) |
| **Database** | SQLite (automatic) | MySQL (requires setup) |
| **UI Testing** | ❌ No | ✅ Yes (full browser) |
| **JavaScript** | ❌ No | ✅ Yes |
| **Setup** | Zero config | Requires WordPress server |
| **Best For** | Hooks, filters, CRUD, permissions | Admin UI, Gutenberg, forms |

### When to Use Integration Tests

Use integration tests for:
- Testing WordPress hooks and filters
- CRUD operations (posts, users, terms, options)
- User permissions and capabilities
- Custom post types and taxonomies
- Database queries and metadata

```php
// tests/Integration/MyPluginTest.php
it('registers custom post type', function () {
    do_action('init');
    expect(post_type_exists('my_cpt'))->toBeTrue();
});

it('filters content correctly', function () {
    $result = apply_filters('the_content', 'Hello');
    expect($result)->toContain('Modified by my plugin');
});
```

### When to Use Browser Tests

Use browser tests for:
- Admin UI workflows
- Gutenberg editor interactions
- JavaScript functionality
- Form submissions
- Visual regression testing

```php
// tests/Browser/AdminTest.php
it('can create post from admin', function () {
    $this->browse(function ($browser) {
        $browser->visit(loginUrl())
            ->type('user_login', 'admin')
            ->type('user_pass', 'password')
            ->press('Log In')
            ->visit(newPostUrl())
            ->type(postTitleSelector(), 'My Post')
            ->click(publishButtonSelector())
            ->waitForText('Post published');
    });
});
```

## Type-Safe Factory Helpers

PestWP provides strongly-typed factory functions for creating WordPress objects:

### Create Posts

```php
use function PestWP\createPost;

// With default values
$post = createPost();

// With custom values
$post = createPost([
    'post_title' => 'Custom Title',
    'post_content' => 'Custom content',
    'post_status' => 'draft',
    'post_type' => 'page',
]);

// IDE autocomplete works!
expect($post->ID)->toBeInt();
expect($post->post_title)->toBe('Custom Title');
```

### Create Users

```php
use function PestWP\createUser;

// Quick role creation
$editor = createUser('editor');

// With additional arguments
$admin = createUser('administrator', [
    'display_name' => 'Test Admin',
]);

// Full custom arguments
$user = createUser([
    'user_login' => 'testuser',
    'user_email' => 'test@example.com',
    'role' => 'author',
]);
```

### Create Terms

```php
use function PestWP\createTerm;

// Create a category
$categoryId = createTerm('Technology');

// Create in custom taxonomy
register_taxonomy('project_type', 'project');
$termId = createTerm('Web Development', 'project_type');

// With additional arguments
$termId = createTerm('Featured', 'category', [
    'description' => 'Featured content',
    'slug' => 'featured',
]);
```

### Create Attachments

```php
use function PestWP\createAttachment;

// Auto-generates a dummy image
$attachmentId = createAttachment();

// With parent post
$post = createPost();
$attachmentId = createAttachment('', $post->ID);

// With custom arguments
$attachmentId = createAttachment('', 0, [
    'post_title' => 'My Image',
    'post_excerpt' => 'Image caption',
]);
```

## Authentication Helpers

PestWP provides convenient helpers for testing authentication and user permissions:

### Login as User

```php
use function PestWP\loginAs;
use function PestWP\createUser;

it('can test editor permissions', function () {
    $editor = createUser('editor');
    loginAs($editor);
    
    expect(current_user_can('edit_posts'))->toBeTrue()
        ->and(current_user_can('manage_options'))->toBeFalse();
});

// Can also login by user ID
loginAs($userId);
```

### Logout

```php
use function PestWP\logout;

it('can logout current user', function () {
    $user = createUser();
    loginAs($user);
    
    expect(isUserLoggedIn())->toBeTrue();
    
    logout();
    
    expect(isUserLoggedIn())->toBeFalse();
});
```

### Check Current User

```php
use function PestWP\currentUser;
use function PestWP\isUserLoggedIn;

it('can get current user', function () {
    $admin = createUser('administrator');
    loginAs($admin);
    
    $current = currentUser();
    expect($current->ID)->toBe($admin->ID)
        ->and($current->roles)->toContain('administrator');
});

it('can check if user is logged in', function () {
    expect(isUserLoggedIn())->toBeFalse();
    
    loginAs(createUser());
    
    expect(isUserLoggedIn())->toBeTrue();
});
```

### Testing Different User Roles

```php
use function PestWP\loginAs;
use function PestWP\createUser;

it('can test subscriber permissions', function () {
    loginAs(createUser('subscriber'));
    
    expect(current_user_can('read'))->toBeTrue()
        ->and(current_user_can('edit_posts'))->toBeFalse();
});

it('can test administrator permissions', function () {
    loginAs(createUser('administrator'));
    
    expect(current_user_can('manage_options'))->toBeTrue()
        ->and(current_user_can('delete_users'))->toBeTrue();
});

it('can switch between users', function () {
    $editor = createUser('editor');
    $author = createUser('author');
    
    loginAs($editor);
    expect(current_user_can('edit_others_posts'))->toBeTrue();
    
    loginAs($author);
    expect(current_user_can('edit_others_posts'))->toBeFalse();
});
```

## Custom WordPress Expectations

PestWP extends Pest's expectation API with WordPress-specific assertions for more readable and expressive tests.

### Post Status Expectations

Check post statuses with intuitive methods:

```php
use function PestWP\createPost;

it('can check post statuses', function () {
    $published = createPost(['post_status' => 'publish']);
    $draft = createPost(['post_status' => 'draft']);
    
    expect($published)->toBePublished();
    expect($draft)->toBeDraft();
});
```

Available status expectations:
- `toBePublished()` - Post is published
- `toBeDraft()` - Post is draft
- `toBePending()` - Post is pending review
- `toBePrivate()` - Post is private
- `toBeInTrash()` - Post is in trash

### WP_Error Expectations

Test WordPress errors elegantly:

```php
it('can test WP_Error objects', function () {
    $error = new WP_Error('invalid_data', 'Invalid input');
    
    expect($error)
        ->toBeWPError()
        ->toHaveErrorCode('invalid_data');
});
```

### Metadata Expectations

Test post and user metadata:

```php
use function PestWP\createPost;
use function PestWP\createUser;

it('can check post metadata', function () {
    $post = createPost();
    update_post_meta($post->ID, 'price', 99);
    
    expect($post)
        ->toHaveMeta('price', 99)
        ->toHaveMetaKey('price');
});

it('can check user metadata', function () {
    $user = createUser();
    update_user_meta($user->ID, 'favorite_color', 'blue');
    
    expect($user)
        ->toHaveUserMeta('favorite_color', 'blue')
        ->toHaveMetaKey('favorite_color');
});
```

### Hook Expectations

Test action and filter registrations:

```php
it('can verify hooks are registered', function () {
    $callback = function() { echo 'test'; };
    
    add_action('init', $callback, 10);
    add_filter('the_content', $callback, 15);
    
    expect('init')->toHaveAction($callback, 10);
    expect('the_content')->toHaveFilter($callback, 15);
});
```

### Term Expectations

Test taxonomy term assignments:

```php
it('can check post terms', function () {
    $post = createPost();
    $termId = wp_insert_term('Technology', 'category');
    wp_set_post_terms($post->ID, [$termId['term_id']], 'category');
    
    expect($post)->toHaveTerm('Technology', 'category');
});
```

### Chaining Expectations

All custom expectations support chaining:

```php
it('can chain multiple expectations', function () {
    $post = createPost(['post_status' => 'publish']);
    update_post_meta($post->ID, 'featured', true);
    
    expect($post)
        ->toBePublished()
        ->toHaveMeta('featured', true)
        ->and($post->post_title)->toBeString()
        ->and($post->ID)->toBeInt();
});
```

## Database Isolation

Every test automatically runs in an isolated database transaction. Changes are rolled back after each test:

```php
it('test A creates a post', function () {
    $post = createPost(['post_title' => 'Test Post']);
    expect(get_post($post->ID))->not->toBeNull();
});

it('test B does not see the post from test A', function () {
    // The post from test A doesn't exist here
    $posts = get_posts(['post_title' => 'Test Post']);
    expect($posts)->toBeEmpty();
});
```

## Browser Testing (E2E)

PestWP integrates with [Pest Browser Plugin](https://pestphp.com/docs/browser-testing) for browser-based end-to-end testing of your WordPress site.

### Quick Setup

```bash
# 1. Install browser dependencies
./vendor/bin/pest --browser-install

# 2. Configure WordPress credentials
vendor/bin/pest-setup-browser --url http://localhost:8080 --user admin --pass password

# 3. Run browser tests
./vendor/bin/pest --browser tests/Browser/
```

### Features

- 🐘 **Native PHP**: Write browser tests in PHP, not TypeScript
- ⚡ **Pest Integration**: Uses the same Pest syntax you already know
- 🎯 **Laravel Dusk API**: Familiar API if you've used Laravel Dusk
- 📸 **Auto Screenshots**: Automatic screenshots on test failures
- 🌐 **Multi-Browser**: Support for Chromium, Firefox, and WebKit

### Example Browser Test

```php
<?php
// tests/Browser/PostsTest.php

use function PestWP\Functions\getBrowserConfig;

it('can log into WordPress dashboard', function () {
    $config = getBrowserConfig();

    $this->browse(function ($browser) use ($config) {
        $browser->visit($config['base_url'] . '/wp-login.php')
            ->type('user_login', $config['admin_user'])
            ->type('user_pass', $config['admin_password'])
            ->press('Log In')
            ->waitForLocation('/wp-admin/')
            ->assertSee('Dashboard');
    });
});

it('can create a new post', function () {
    $config = getBrowserConfig();

    $this->browse(function ($browser) use ($config) {
        $browser->visit($config['base_url'] . '/wp-admin/post-new.php')
            ->type('[aria-label="Add title"]', 'My New Post')
            ->press('Publish')
            ->press('Publish') // Confirm
            ->waitForText('Post published');
    });
});
```

### Running Browser Tests

```bash
# Run all browser tests
./vendor/bin/pest --browser tests/Browser/

# Run with visible browser (headed mode)
./vendor/bin/pest --browser --headed

# Run specific test file
./vendor/bin/pest --browser tests/Browser/DashboardTest.php
```

### Browser Configuration

The `getBrowserConfig()` helper reads configuration from your `tests/Pest.php` or environment variables:

```php
// In tests/Pest.php (created by pest-setup-browser)
function browser(): array
{
    return [
        'base_url' => 'http://localhost:8080',
        'admin_user' => 'admin',
        'admin_password' => 'password',
    ];
}
```

Or use environment variables:

```bash
export WP_BASE_URL=http://localhost:8080
export WP_ADMIN_USER=admin
export WP_ADMIN_PASSWORD=password
```

### WordPress Environment Setup

Browser tests require a **running WordPress instance** (unlike integration tests which use SQLite). Here are common setups:

#### Option 1: wp-env (Recommended for WordPress development)

[wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) is the official WordPress local environment tool.

```bash
# Install wp-env globally
npm install -g @wordpress/env

# Start WordPress (default: http://localhost:8888)
wp-env start

# Configure PestWP
vendor/bin/pest-setup-browser --url http://localhost:8888 --user admin --pass password

# Run browser tests
./vendor/bin/pest --browser tests/Browser/
```

#### Option 2: Docker Compose

Create a `docker-compose.yml` for isolated testing:

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

# Install WordPress (first time only)
docker-compose exec wordpress wp core install \
  --url=http://localhost:8080 \
  --title="Test Site" \
  --admin_user=admin \
  --admin_password=password \
  --admin_email=admin@example.com

# Configure PestWP
vendor/bin/pest-setup-browser --url http://localhost:8080 --user admin --pass password

# Run browser tests
./vendor/bin/pest --browser tests/Browser/
```

#### Option 3: Local by Flywheel / MAMP / XAMPP

If you have a local WordPress installation:

```bash
# Configure with your local URL
vendor/bin/pest-setup-browser --url http://mysite.local --user admin --pass yourpassword

# Run browser tests
./vendor/bin/pest --browser tests/Browser/
```

#### Option 4: wp-browser (Advanced)

For projects already using [wp-browser](https://wpbrowser.wptestkit.dev/), you can configure PestWP to use the same WordPress instance:

```php
// tests/Pest.php
function browser(): array
{
    return [
        'base_url' => getenv('WP_URL') ?: 'http://wordpress.test',
        'admin_user' => getenv('WP_ADMIN_USER') ?: 'admin',
        'admin_password' => getenv('WP_ADMIN_PASSWORD') ?: 'password',
    ];
}
```

### Integration Tests vs Browser Tests: Which WordPress?

| Test Type | WordPress Instance | Database |
|-----------|-------------------|----------|
| **Integration** | Auto-installed in `.pest/wordpress/` | SQLite (isolated) |
| **Browser** | Your running server (Docker, wp-env, etc.) | MySQL/MariaDB |

**Important**: Browser tests interact with a real WordPress installation. Changes made during browser tests **persist** unless you reset the database manually or use a fresh container.

### WP Admin Locators

PestWP provides helper functions for building URLs and CSS selectors for WordPress admin UI elements. These locators are designed to be resilient across WordPress versions (6.5+).

#### URL Helpers

```php
use function PestWP\Functions\adminUrl;
use function PestWP\Functions\loginUrl;
use function PestWP\Functions\newPostUrl;
use function PestWP\Functions\editPostUrl;
use function PestWP\Functions\postsListUrl;

// Build admin URLs
loginUrl();                    // http://localhost:8080/wp-login.php
adminUrl();                    // http://localhost:8080/wp-admin/
adminUrl('edit.php');          // http://localhost:8080/wp-admin/edit.php
adminUrl('my-plugin');         // http://localhost:8080/wp-admin/admin.php?page=my-plugin

// Post URLs
newPostUrl();                  // New post
newPostUrl('page');            // New page
editPostUrl(123);              // Edit post ID 123
postsListUrl('post', 'draft'); // List drafts
```

#### Gutenberg Selectors

```php
use function PestWP\Functions\postTitleSelector;
use function PestWP\Functions\publishButtonSelector;
use function PestWP\Functions\blockSelector;
use function PestWP\Functions\editorNoticeSelector;

it('can create a post using locators', function () {
    $this->browse(function ($browser) {
        $browser->visit(newPostUrl())
            ->waitFor(postTitleSelector())
            ->type(postTitleSelector(), 'My Post')
            ->click(publishButtonSelector())
            ->waitFor(editorNoticeSelector())
            ->assertSee('Post published');
    });
});

// Target specific blocks
blockSelector('core/paragraph');  // [data-type='core/paragraph']
blockSelector('core/heading');    // [data-type='core/heading']
```

#### Admin UI Selectors

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

For complete browser testing documentation, see [docs/BROWSER_TESTING.md](docs/BROWSER_TESTING.md).

## Configuration

The plugin works out of the box, but you can customize it in your `tests/Pest.php`:

```php
<?php

use PestWP\Concerns\InteractsWithDatabase;

// Enable database isolation for integration tests
uses(InteractsWithDatabase::class)->in('Integration');
```

## Project Structure

```
.pest/
├── wordpress/          # WordPress installation
├── database/           # SQLite database
└── snapshots/          # Database snapshots

tests/
├── Unit/               # Unit tests (no WordPress)
└── Integration/        # Integration tests (with WordPress)
```

## Development

```bash
# Run tests
composer test

# Run type checking
composer analyse

# Run code style check
composer lint

# Run all quality checks
composer qa
```

## How It Works

1. **Auto Installation**: On first run, PestWP downloads WordPress and SQLite integration plugin
2. **Bootstrap**: Pest automatically loads WordPress with SQLite before running tests
3. **Isolation**: Each test runs with automatic database snapshots (~1.7ms overhead)
4. **Type Safety**: Factory helpers provide full IDE autocompletion and static analysis

## Troubleshooting

### Common Issues

**Tests fail with "WordPress not found"**
```bash
# Clear and reinstall WordPress
rm -rf .pest/wordpress
./vendor/bin/pest
```

**PHPStan memory errors**
```bash
vendor/bin/phpstan analyse --memory-limit=512M
```

**Browser tests timeout**
```bash
# Ensure WordPress is accessible
curl http://localhost:8080

# Check browser installation
./vendor/bin/pest --browser-install

# Run with visible browser for debugging
./vendor/bin/pest --browser --headed
```

**Database isolation not working**
```php
// Ensure you have the trait in tests/Pest.php
uses(PestWP\Concerns\InteractsWithDatabase::class)->in('Integration');
```

### Debug Mode

Enable verbose output for troubleshooting:

```bash
# Verbose test output
./vendor/bin/pest -v

# Debug WordPress loading
PEST_WP_DEBUG=1 ./vendor/bin/pest
```

## GitHub Actions (CI/CD)

PestWP includes ready-to-use GitHub Actions workflows for continuous integration.

### Quick Setup

Copy the simple workflow to your project:

```yaml
# .github/workflows/tests.yml
name: Tests

on:
  push:
    branches: [main]
  pull_request:

jobs:
  tests:
    name: 'PHP ${{ matrix.php }}'
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.3', '8.4']

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: pdo_sqlite, dom, gd, zip
          tools: composer:v2

      - name: Cache WordPress
        uses: actions/cache@v4
        with:
          path: .pest/wordpress
          key: wordpress-latest

      - name: Install & Test
        run: |
          composer install --no-progress
          vendor/bin/pest --coverage
```

### Full Workflow Template

For a complete workflow with code quality checks, matrix testing, and browser tests, copy:

```
.github/workflows/wordpress-tests.yml
```

This includes:
- ✅ Matrix testing (PHP 8.3, 8.4)
- ✅ PHPStan static analysis
- ✅ Code style checks (Pint)
- ✅ WordPress caching for faster builds
- ✅ Coverage reporting (Codecov)
- ✅ Browser tests (on-demand)

### Running Browser Tests in CI

Browser tests require a running WordPress instance. The full workflow uses Docker services:

```yaml
services:
  wordpress:
    image: wordpress:latest
    ports:
      - 8080:80
```

Enable browser tests by including `[browser]` in your commit message, or run them on a schedule.

## Credits

- Built on [Pest PHP](https://pestphp.com/)
- Uses [WordPress SQLite Integration](https://github.com/wordpress/sqlite-database-integration)

## License

MIT License. See [LICENSE](LICENSE) for details.
