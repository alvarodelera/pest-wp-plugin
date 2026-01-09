# Quick Start Guide

This guide will help you write your first WordPress tests with PestWP.

## Your First Test

Create a new test file in `tests/Unit/`:

```php
<?php

// tests/Unit/ExampleTest.php

it('has a working WordPress environment', function () {
    expect(function_exists('wp_insert_post'))->toBeTrue();
});
```

Run the test:

```bash
./vendor/bin/pest
```

## Testing Posts

Use the `createPost()` factory to create test posts:

```php
<?php

use function PestWP\createPost;

it('creates a published post', function () {
    $post = createPost([
        'post_title' => 'Hello World',
        'post_content' => 'This is my first post.',
    ]);
    
    expect($post)->toBeInstanceOf(WP_Post::class);
    expect($post)->toBePublished();
    expect($post->post_title)->toBe('Hello World');
});

it('creates a draft post', function () {
    $post = createPost([
        'post_status' => 'draft',
    ]);
    
    expect($post)->toBeDraft();
});
```

## Testing Users

Create and authenticate as different users:

```php
<?php

use function PestWP\createUser;
use function PestWP\loginAs;
use function PestWP\logout;
use function PestWP\currentUser;

it('authenticates users correctly', function () {
    $admin = createUser('administrator');
    
    loginAs($admin);
    
    expect(currentUser()->ID)->toBe($admin->ID);
    expect($admin)->toHaveRole('administrator');
    expect($admin)->toHaveCapability('manage_options');
    
    logout();
    
    expect(currentUser()->ID)->toBe(0);
});

it('tests user capabilities', function () {
    $editor = createUser('editor');
    $subscriber = createUser('subscriber');
    
    expect($editor)->can('edit_posts');
    expect($editor)->can('publish_posts');
    expect($subscriber)->not->can('publish_posts');
});
```

## Testing Custom Post Types

Verify that your custom post types are registered correctly:

```php
<?php

it('registers the product post type', function () {
    expect('product')->toBeRegisteredPostType();
    expect('product')->toSupportFeature('title');
    expect('product')->toSupportFeature('editor');
    expect('product')->toSupportFeature('thumbnail');
});

it('registers the product taxonomy', function () {
    expect('product_category')->toBeRegisteredTaxonomy();
});
```

## Testing Hooks

Verify that hooks are registered:

```php
<?php

it('adds filters correctly', function () {
    add_filter('the_title', 'my_title_filter', 20);
    
    expect('the_title')->toHaveFilter('my_title_filter', 20);
});

it('adds actions correctly', function () {
    add_action('init', 'my_init_callback', 15);
    
    expect('init')->toHaveAction('my_init_callback', 15);
});
```

## Testing Options

Work with WordPress options:

```php
<?php

use function PestWP\setOption;
use function PestWP\deleteOption;

it('stores options correctly', function () {
    setOption('my_plugin_setting', 'value');
    
    expect('my_plugin_setting')->toHaveOption('value');
    
    deleteOption('my_plugin_setting');
    
    expect(get_option('my_plugin_setting', false))->toBeFalse();
});
```

## Testing with Metadata

Add and verify post/user metadata:

```php
<?php

use function PestWP\createPost;
use function PestWP\createUser;

it('stores post meta correctly', function () {
    $post = createPost();
    
    update_post_meta($post->ID, 'price', '99.99');
    
    // Refresh the post object
    $post = get_post($post->ID);
    
    expect($post)->toHaveMeta('price', '99.99');
    expect($post)->toHaveMetaKey('price');
});

it('stores user meta correctly', function () {
    $user = createUser();
    
    update_user_meta($user->ID, 'favorite_color', 'blue');
    
    expect($user)->toHaveUserMeta('favorite_color', 'blue');
});
```

## Testing Terms

Create and assign terms to posts:

```php
<?php

use function PestWP\createPost;
use function PestWP\createTerm;

it('assigns terms to posts', function () {
    $post = createPost();
    $termId = createTerm('News', 'category');
    
    wp_set_post_terms($post->ID, [$termId], 'category');
    
    expect($post)->toHaveTerm('News', 'category');
});
```

## Testing Shortcodes

Register and test shortcodes:

```php
<?php

use function PestWP\registerTestShortcode;

it('registers shortcodes', function () {
    registerTestShortcode('my_shortcode', function ($atts) {
        return 'Hello World';
    });
    
    expect('my_shortcode')->toBeRegisteredShortcode();
    
    $output = do_shortcode('[my_shortcode]');
    expect($output)->toBe('Hello World');
});
```

## Testing WP_Error

Handle and verify WordPress errors:

```php
<?php

it('handles WP_Error correctly', function () {
    $error = new WP_Error('invalid_email', 'The email address is invalid.');
    
    expect($error)->toBeWPError();
    expect($error)->toHaveErrorCode('invalid_email');
});
```

## Running Tests

```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Unit/PostTest.php

# Run tests matching a pattern
./vendor/bin/pest --filter="creates a published post"

# Run with coverage
./vendor/bin/pest --coverage
```

## Next Steps

- [Factories](factories.md) - Deep dive into test data creation
- [Expectations](expectations.md) - All available assertions
- [Database Isolation](database-isolation.md) - Understand test isolation
- [Browser Testing](browser-testing.md) - End-to-end testing
