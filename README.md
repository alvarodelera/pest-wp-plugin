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

## Quick Start

The plugin automatically bootstraps WordPress with SQLite when you run Pest tests. No manual configuration needed!

### Basic Test

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
3. **Isolation**: Each test runs in a database transaction (SAVEPOINT/ROLLBACK)
4. **Type Safety**: Factory helpers provide full IDE autocompletion and static analysis

## Credits

- Built on [Pest PHP](https://pestphp.com/)
- Uses [WordPress SQLite Integration](https://github.com/wordpress/sqlite-database-integration)

## License

MIT License. See [LICENSE](LICENSE) for details.
