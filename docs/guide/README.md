# PestWP

> A modern WordPress testing framework powered by Pest PHP

PestWP brings the elegant syntax of [Pest PHP](https://pestphp.com) to WordPress development, making it delightful to write tests for your plugins and themes.

## Features

- **Zero-Config Setup** - Works out of the box with SQLite, no MySQL required
- **Elegant Syntax** - Write expressive tests with Pest's beautiful API
- **WordPress Factories** - Create posts, users, terms, and more with simple helpers
- **Custom Expectations** - WordPress-specific assertions for posts, users, hooks, and more
- **Database Isolation** - Each test runs in a clean database state
- **Browser Testing** - Full Playwright integration for end-to-end testing
- **REST API Testing** - Fluent API for testing REST endpoints
- **AJAX Testing** - Easy testing of admin-ajax handlers
- **Mocking System** - Mock functions, hooks, HTTP requests, and time
- **Architecture Testing** - Enforce coding standards with Pest's arch() API
- **Visual Regression** - Screenshot comparison for UI testing
- **Accessibility Testing** - Built-in WCAG compliance checks
- **WooCommerce Support** - Dedicated helpers and selectors
- **Gutenberg Support** - Block editor testing utilities

## Quick Example

```php
<?php

use function PestWP\createPost;
use function PestWP\createUser;
use function PestWP\loginAs;

it('allows editors to publish posts', function () {
    $editor = createUser('editor');
    $post = createPost(['post_status' => 'draft']);
    
    loginAs($editor);
    
    wp_update_post([
        'ID' => $post->ID,
        'post_status' => 'publish',
    ]);
    
    $post = get_post($post->ID);
    
    expect($post)->toBePublished();
});

it('registers custom post types correctly', function () {
    expect('product')->toBeRegisteredPostType();
    expect('product')->toSupportFeature('title');
    expect('product')->toSupportFeature('thumbnail');
});

it('filters content correctly', function () {
    expect('the_content')
        ->toHaveFilter('wpautop', 10);
});
```

## Requirements

- PHP 8.3+
- Pest PHP 4.0+
- WordPress 6.0+ (for testing)

## Getting Started

1. [Installation](installation.md) - Install PestWP via Composer
2. [Quick Start](getting-started.md) - Write your first test
3. [Configuration](configuration.md) - Customize your setup

## Documentation

### Core Features
- [Factories](factories.md) - Create test data easily
- [Expectations](expectations.md) - WordPress-specific assertions
- [Authentication](authentication.md) - Test with different users
- [Database Isolation](database-isolation.md) - Clean state for each test

### Testing Types
- [Browser Testing](browser-testing.md) - End-to-end testing with Playwright
- [REST API Testing](rest-api-testing.md) - Test REST endpoints
- [AJAX Testing](ajax-testing.md) - Test admin-ajax handlers
- [Architecture Testing](architecture-testing.md) - Enforce code standards

### Advanced Features
- [Mocking](mocking.md) - Mock functions, hooks, HTTP, and time
- [Fixtures](fixtures.md) - Reusable test data
- [Snapshots](snapshots.md) - Snapshot testing
- [Visual Regression](visual-regression.md) - Screenshot comparison
- [Accessibility](accessibility-testing.md) - WCAG compliance testing

### Integrations
- [WooCommerce](woocommerce.md) - E-commerce testing
- [Gutenberg](gutenberg.md) - Block editor testing

### Deployment
- [CI/CD](ci-cd.md) - GitHub Actions, GitLab CI
- [Migration](migration.md) - Migrate from other frameworks

## License

PestWP is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
