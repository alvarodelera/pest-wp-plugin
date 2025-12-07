# Testing WordPress Plugins with PestWP

This guide shows you how to set up PestWP to test your WordPress plugin with full database isolation, type-safe factories, and modern Pest syntax.

## Table of Contents

- [Quick Start](#quick-start)
- [Project Structure](#project-structure)
- [Configuration](#configuration)
- [Writing Tests](#writing-tests)
- [Factory Helpers](#factory-helpers)
- [Custom Expectations](#custom-expectations)
- [Testing Hooks and Filters](#testing-hooks-and-filters)
- [Testing Custom Post Types](#testing-custom-post-types)
- [Testing User Permissions](#testing-user-permissions)
- [Testing Settings Pages](#testing-settings-pages)
- [Testing AJAX Handlers](#testing-ajax-handlers)
- [Testing Shortcodes](#testing-shortcodes)
- [Testing REST API Endpoints](#testing-rest-api-endpoints)
- [Browser Testing](#browser-testing)
- [Advanced Configuration](#advanced-configuration)
- [Best Practices](#best-practices)

---

## Quick Start

### 1. Install PestWP

```bash
cd my-wordpress-plugin
composer require --dev alvarodelera/pest-wp-plugin
```

### 2. Initialize Pest

```bash
./vendor/bin/pest --init
```

### 3. Configure `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

### 4. Configure `tests/Pest.php`

```php
<?php

declare(strict_types=1);

use PestWP\Config;
use PestWP\Database\TransactionManager;

/*
|--------------------------------------------------------------------------
| Load Your Plugin
|--------------------------------------------------------------------------
*/

Config::plugins(dirname(__DIR__) . '/my-plugin.php');

/*
|--------------------------------------------------------------------------
| Database Isolation
|--------------------------------------------------------------------------
*/

uses()
    ->beforeEach(fn () => TransactionManager::beginTransaction())
    ->afterEach(fn () => TransactionManager::rollback())
    ->in('Integration');
```

### 5. Run Your First Test

```bash
./vendor/bin/pest
```

---

## Project Structure

Recommended directory structure for your plugin:

```
my-plugin/
├── my-plugin.php              # Main plugin file
├── src/                       # Plugin source code
│   ├── Admin/
│   ├── Frontend/
│   └── Core/
├── composer.json
├── phpunit.xml
├── tests/
│   ├── Pest.php               # PestWP configuration
│   ├── Unit/                  # Unit tests (no WordPress)
│   │   ├── HelpersTest.php
│   │   └── ValidatorsTest.php
│   └── Integration/           # Integration tests (WordPress loaded)
│       ├── ActivationTest.php
│       ├── PostTypesTest.php
│       ├── HooksTest.php
│       └── PermissionsTest.php
├── .gitignore
└── .pest/                     # Auto-created by PestWP
    ├── wordpress/             # WordPress installation
    └── database/              # SQLite database
```

Add to `.gitignore`:

```gitignore
/.pest/
.phpunit.cache/
vendor/
```

---

## Configuration

### Basic Plugin Loading

```php
<?php
// tests/Pest.php

use PestWP\Config;

// Single plugin file
Config::plugins(dirname(__DIR__) . '/my-plugin.php');
```

### Loading Multiple Plugins

```php
<?php
// tests/Pest.php

use PestWP\Config;

// Your plugin depends on WooCommerce
Config::plugins([
    dirname(__DIR__) . '/vendor/woocommerce/woocommerce/woocommerce.php',
    dirname(__DIR__) . '/my-woo-extension.php',
]);
```

### Loading MU-Plugins

MU-plugins are loaded before regular plugins:

```php
<?php
// tests/Pest.php

use PestWP\Config;

// Load test helpers as MU-plugin
Config::muPlugins([
    dirname(__DIR__) . '/tests/mu-plugins/test-helpers.php',
]);

Config::plugins(dirname(__DIR__) . '/my-plugin.php');
```

### Setting Active Theme

```php
<?php
// tests/Pest.php

use PestWP\Config;

Config::plugins(dirname(__DIR__) . '/my-plugin.php');
Config::theme('twentytwentyfour');
```

### Custom Constants

Define constants before WordPress loads:

```php
<?php
// tests/Pest.php

use PestWP\Config;

Config::beforeWordPress(function () {
    define('MY_PLUGIN_DEBUG', true);
    define('MY_PLUGIN_TEST_MODE', true);
    define('DISABLE_EXTERNAL_API', true);
});

Config::plugins(dirname(__DIR__) . '/my-plugin.php');
```

### Setup After WordPress Loads

Configure options, add filters, mock external services:

```php
<?php
// tests/Pest.php

use PestWP\Config;

Config::plugins(dirname(__DIR__) . '/my-plugin.php');

Config::afterWordPress(function () {
    // Set plugin options
    update_option('my_plugin_settings', [
        'api_key' => 'test-api-key',
        'feature_enabled' => true,
    ]);
    
    // Mock external API calls
    add_filter('pre_http_request', function ($preempt, $args, $url) {
        if (str_contains($url, 'api.external-service.com')) {
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['success' => true]),
            ];
        }
        return $preempt;
    }, 10, 3);
});
```

### Complete Configuration Example

```php
<?php
// tests/Pest.php

declare(strict_types=1);

use PestWP\Config;
use PestWP\Database\TransactionManager;

/*
|--------------------------------------------------------------------------
| Pre-WordPress Configuration
|--------------------------------------------------------------------------
*/

Config::beforeWordPress(function () {
    // Debug constants
    define('MY_PLUGIN_DEBUG', true);
    define('MY_PLUGIN_LOG_LEVEL', 'debug');
    
    // Disable external services
    define('MY_PLUGIN_DISABLE_ANALYTICS', true);
    define('MY_PLUGIN_DISABLE_UPDATES', true);
});

/*
|--------------------------------------------------------------------------
| Plugin Loading
|--------------------------------------------------------------------------
*/

// MU-plugins first
Config::muPlugins([
    __DIR__ . '/mu-plugins/disable-emails.php',
]);

// Main plugin
Config::plugins(dirname(__DIR__) . '/my-awesome-plugin.php');

// Optional: Set theme
Config::theme('twentytwentyfour');

/*
|--------------------------------------------------------------------------
| Post-WordPress Configuration
|--------------------------------------------------------------------------
*/

Config::afterWordPress(function () {
    // Initialize plugin settings
    update_option('my_plugin_api_key', 'test-key-12345');
    update_option('my_plugin_settings', [
        'notifications' => false,
        'cache_enabled' => false,
        'debug_mode' => true,
    ]);
    
    // Mock external HTTP requests
    add_filter('pre_http_request', function ($preempt, $args, $url) {
        // Mock all external API calls
        if (!str_contains($url, home_url())) {
            return [
                'response' => ['code' => 200],
                'body' => '{"mocked": true}',
            ];
        }
        return $preempt;
    }, 10, 3);
});

/*
|--------------------------------------------------------------------------
| Database Isolation
|--------------------------------------------------------------------------
*/

uses()
    ->beforeEach(function () {
        TransactionManager::beginTransaction();
        
        // Additional per-test setup
        wp_cache_flush();
    })
    ->afterEach(function () {
        TransactionManager::rollback();
    })
    ->in('Integration');
```

---

## Writing Tests

### Basic Test Structure

```php
<?php
// tests/Integration/MyPluginTest.php

describe('My Plugin', function () {
    
    it('is activated', function () {
        expect(is_plugin_active('my-plugin/my-plugin.php'))->toBeTrue();
    });
    
    it('defines main constant', function () {
        expect(defined('MY_PLUGIN_VERSION'))->toBeTrue();
    });
    
    it('loads text domain', function () {
        expect(is_textdomain_loaded('my-plugin'))->toBeTrue();
    });
    
});
```

### Using describe() for Organization

```php
<?php
// tests/Integration/SettingsTest.php

describe('Plugin Settings', function () {
    
    describe('Default Values', function () {
        
        it('has default API endpoint', function () {
            $settings = get_option('my_plugin_settings');
            
            expect($settings['api_endpoint'])
                ->toBe('https://api.example.com/v1');
        });
        
        it('has notifications enabled by default', function () {
            $settings = get_option('my_plugin_settings');
            
            expect($settings['notifications'])->toBeTrue();
        });
        
    });
    
    describe('Saving Settings', function () {
        
        it('saves custom API endpoint', function () {
            update_option('my_plugin_settings', [
                'api_endpoint' => 'https://custom.api.com',
            ]);
            
            $settings = get_option('my_plugin_settings');
            
            expect($settings['api_endpoint'])
                ->toBe('https://custom.api.com');
        });
        
    });
    
});
```

### Using beforeEach() for Setup

```php
<?php
// tests/Integration/PostHandlerTest.php

use function PestWP\createPost;
use function PestWP\createUser;

describe('Post Handler', function () {
    
    beforeEach(function () {
        // Create test data available to all tests in this describe block
        $this->post = createPost([
            'post_title' => 'Test Post',
            'post_status' => 'publish',
        ]);
        
        $this->editor = createUser('editor');
    });
    
    it('can process published posts', function () {
        $result = my_plugin_process_post($this->post->ID);
        
        expect($result)->toBeTrue();
    });
    
    it('adds meta after processing', function () {
        my_plugin_process_post($this->post->ID);
        
        expect($this->post)
            ->toHaveMeta('_my_plugin_processed', true);
    });
    
    it('allows editors to process posts', function () {
        loginAs($this->editor);
        
        $result = my_plugin_process_post($this->post->ID);
        
        expect($result)->toBeTrue();
    });
    
});
```

---

## Factory Helpers

### Creating Posts

```php
<?php

use function PestWP\createPost;

// Default post (published, type 'post')
$post = createPost();

// Custom title and content
$post = createPost([
    'post_title' => 'My Test Post',
    'post_content' => 'This is the content.',
]);

// Draft post
$post = createPost([
    'post_title' => 'Draft Post',
    'post_status' => 'draft',
]);

// Custom post type
$post = createPost([
    'post_title' => 'My Product',
    'post_type' => 'product',
    'post_status' => 'publish',
]);

// Post with meta
$post = createPost([
    'post_title' => 'Post with Meta',
    'meta_input' => [
        'custom_field' => 'custom value',
        '_private_field' => 'private value',
    ],
]);

// Post with specific author
$author = createUser('author');
$post = createPost([
    'post_title' => 'Authored Post',
    'post_author' => $author->ID,
]);
```

### Creating Users

```php
<?php

use function PestWP\createUser;
use function PestWP\loginAs;
use function PestWP\logout;

// Default subscriber
$user = createUser();

// Specific role (shorthand)
$editor = createUser('editor');
$admin = createUser('administrator');
$author = createUser('author');

// Custom user data
$user = createUser([
    'user_login' => 'johndoe',
    'user_email' => 'john@example.com',
    'display_name' => 'John Doe',
    'role' => 'editor',
]);

// Role + additional args
$user = createUser('author', [
    'display_name' => 'Jane Author',
    'user_email' => 'jane@example.com',
]);

// Login/logout
loginAs($editor);
expect(current_user_can('edit_posts'))->toBeTrue();

logout();
expect(is_user_logged_in())->toBeFalse();
```

### Creating Terms

```php
<?php

use function PestWP\createTerm;

// Category (default taxonomy)
$categoryId = createTerm('Technology');

// Tag
$tagId = createTerm('featured', 'post_tag');

// Custom taxonomy
register_taxonomy('project_type', 'project');
$termId = createTerm('Web Development', 'project_type');

// With additional args
$termId = createTerm('Premium', 'category', [
    'description' => 'Premium content category',
    'slug' => 'premium-content',
]);

// Hierarchical term (child category)
$parentId = createTerm('Parent Category');
$childId = createTerm('Child Category', 'category', [
    'parent' => $parentId,
]);
```

### Creating Attachments

```php
<?php

use function PestWP\createAttachment;
use function PestWP\createPost;

// Auto-generates a dummy image
$attachmentId = createAttachment();

// With parent post
$post = createPost();
$attachmentId = createAttachment('', $post->ID);

// Custom attachment data
$attachmentId = createAttachment('', 0, [
    'post_title' => 'Featured Image',
    'post_mime_type' => 'image/png',
]);
```

---

## Custom Expectations

### Post Expectations

```php
<?php

use function PestWP\createPost;

it('checks post status', function () {
    $published = createPost(['post_status' => 'publish']);
    $draft = createPost(['post_status' => 'draft']);
    $pending = createPost(['post_status' => 'pending']);
    $private = createPost(['post_status' => 'private']);
    $trashed = createPost(['post_status' => 'trash']);
    
    expect($published)->toBePublished();
    expect($draft)->toBeDraft();
    expect($pending)->toBePending();
    expect($private)->toBePrivate();
    expect($trashed)->toBeInTrash();
});

it('checks post meta', function () {
    $post = createPost();
    update_post_meta($post->ID, 'price', '29.99');
    update_post_meta($post->ID, '_internal', 'value');
    
    // Refresh post object
    $post = get_post($post->ID);
    
    expect($post)
        ->toHaveMeta('price', '29.99')
        ->toHaveMetaKey('_internal');
});

it('checks post terms', function () {
    $post = createPost();
    $categoryId = createTerm('News');
    wp_set_post_terms($post->ID, [$categoryId], 'category');
    
    $post = get_post($post->ID);
    
    expect($post)->toHaveTerm('News', 'category');
    expect($post)->toHaveTerm($categoryId, 'category');
});
```

### User Expectations

```php
<?php

use function PestWP\createUser;

it('checks user capabilities', function () {
    $editor = createUser('editor');
    $subscriber = createUser('subscriber');
    
    expect($editor)
        ->toHaveCapability('edit_posts')
        ->toHaveCapability('edit_others_posts')
        ->toHaveRole('editor');
    
    expect($subscriber)
        ->toHaveCapability('read')
        ->not->toHaveCapability('edit_posts')
        ->toHaveRole('subscriber');
});

it('uses can() alias', function () {
    $admin = createUser('administrator');
    
    expect($admin)
        ->can('manage_options')
        ->can('edit_users')
        ->can('install_plugins');
});

it('checks user meta', function () {
    $user = createUser();
    update_user_meta($user->ID, 'custom_field', 'custom_value');
    
    $user = get_user_by('id', $user->ID);
    
    expect($user)->toHaveUserMeta('custom_field', 'custom_value');
});
```

### Hook Expectations

```php
<?php

it('checks registered actions', function () {
    add_action('my_custom_action', 'my_callback', 15);
    
    expect('my_custom_action')
        ->toHaveAction('my_callback')
        ->toHaveAction('my_callback', 15); // With priority
});

it('checks registered filters', function () {
    add_filter('my_custom_filter', 'my_filter_callback', 20);
    
    expect('my_custom_filter')
        ->toHaveFilter('my_filter_callback')
        ->toHaveFilter('my_filter_callback', 20); // With priority
});

it('checks WordPress core hooks', function () {
    expect('init')->toHaveAction();
    expect('the_content')->toHaveFilter();
});
```

### Options and Transients

```php
<?php

it('checks options', function () {
    update_option('my_plugin_enabled', true);
    update_option('my_plugin_version', '1.2.3');
    
    expect('my_plugin_enabled')->toHaveOption();
    expect('my_plugin_enabled')->toHaveOption(true);
    expect('my_plugin_version')->toHaveOption('1.2.3');
});

it('checks transients', function () {
    set_transient('my_cache', ['data' => 'value'], 3600);
    
    expect('my_cache')->toHaveTransient();
    expect('my_cache')->toHaveTransient(['data' => 'value']);
});
```

### Post Types and Taxonomies

```php
<?php

it('checks registered post types', function () {
    register_post_type('product', [
        'public' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
    ]);
    
    expect('product')
        ->toBeRegisteredPostType()
        ->toSupportFeature('title')
        ->toSupportFeature('editor')
        ->toSupportFeature('thumbnail');
    
    expect('product')->not->toSupportFeature('comments');
});

it('checks registered taxonomies', function () {
    register_taxonomy('product_category', 'product');
    
    expect('product_category')->toBeRegisteredTaxonomy();
    expect('non_existent')->not->toBeRegisteredTaxonomy();
});
```

### Shortcodes

```php
<?php

it('checks registered shortcodes', function () {
    add_shortcode('my_shortcode', fn() => 'output');
    
    expect('my_shortcode')->toBeRegisteredShortcode();
    expect('unregistered')->not->toBeRegisteredShortcode();
});
```

### WP_Error

```php
<?php

it('checks WP_Error', function () {
    $error = new WP_Error('invalid_data', 'The data is invalid');
    
    expect($error)
        ->toBeWPError()
        ->toHaveErrorCode('invalid_data');
});

it('handles functions returning WP_Error', function () {
    $result = wp_insert_post([]); // Missing required fields
    
    expect($result)->toBeWPError();
});
```

---

## Testing Hooks and Filters

### Testing Actions

```php
<?php
// tests/Integration/HooksTest.php

describe('Plugin Hooks', function () {
    
    it('registers init action', function () {
        expect('init')->toHaveAction('my_plugin_init');
    });
    
    it('executes callback on init', function () {
        $executed = false;
        
        add_action('my_plugin_initialized', function () use (&$executed) {
            $executed = true;
        });
        
        do_action('init');
        
        expect($executed)->toBeTrue();
    });
    
    it('passes correct arguments to action', function () {
        $receivedArgs = [];
        
        add_action('my_plugin_post_saved', function ($postId, $data) use (&$receivedArgs) {
            $receivedArgs = ['post_id' => $postId, 'data' => $data];
        }, 10, 2);
        
        do_action('my_plugin_post_saved', 123, ['title' => 'Test']);
        
        expect($receivedArgs)->toBe([
            'post_id' => 123,
            'data' => ['title' => 'Test'],
        ]);
    });
    
});
```

### Testing Filters

```php
<?php
// tests/Integration/FiltersTest.php

describe('Plugin Filters', function () {
    
    it('modifies the_content', function () {
        $content = 'Original content';
        
        $filtered = apply_filters('the_content', $content);
        
        expect($filtered)->toContain('Original content');
        expect($filtered)->toContain('<!-- my-plugin-signature -->');
    });
    
    it('allows filtering plugin output', function () {
        // Add custom filter
        add_filter('my_plugin_output', fn($output) => strtoupper($output));
        
        $result = apply_filters('my_plugin_output', 'hello world');
        
        expect($result)->toBe('HELLO WORLD');
    });
    
    it('respects filter priority', function () {
        add_filter('my_plugin_priority_test', fn($v) => $v . '-first', 5);
        add_filter('my_plugin_priority_test', fn($v) => $v . '-second', 10);
        add_filter('my_plugin_priority_test', fn($v) => $v . '-third', 15);
        
        $result = apply_filters('my_plugin_priority_test', 'start');
        
        expect($result)->toBe('start-first-second-third');
    });
    
});
```

---

## Testing Custom Post Types

```php
<?php
// tests/Integration/CustomPostTypeTest.php

use function PestWP\createPost;
use function PestWP\createUser;
use function PestWP\loginAs;

describe('Product Post Type', function () {
    
    it('is registered on init', function () {
        do_action('init');
        
        expect('product')->toBeRegisteredPostType();
    });
    
    it('has correct supports', function () {
        expect('product')
            ->toSupportFeature('title')
            ->toSupportFeature('editor')
            ->toSupportFeature('thumbnail')
            ->toSupportFeature('custom-fields');
    });
    
    it('can create product posts', function () {
        $product = createPost([
            'post_type' => 'product',
            'post_title' => 'Test Product',
            'post_status' => 'publish',
        ]);
        
        expect($product->post_type)->toBe('product');
        expect($product)->toBePublished();
    });
    
    it('saves product meta correctly', function () {
        $product = createPost(['post_type' => 'product']);
        
        update_post_meta($product->ID, '_price', '29.99');
        update_post_meta($product->ID, '_sku', 'PROD-001');
        
        expect(get_post_meta($product->ID, '_price', true))->toBe('29.99');
        expect(get_post_meta($product->ID, '_sku', true))->toBe('PROD-001');
    });
    
    it('has product_category taxonomy', function () {
        expect('product_category')->toBeRegisteredTaxonomy();
    });
    
    it('can assign categories to products', function () {
        $product = createPost(['post_type' => 'product']);
        $categoryId = createTerm('Electronics', 'product_category');
        
        wp_set_post_terms($product->ID, [$categoryId], 'product_category');
        
        $terms = wp_get_post_terms($product->ID, 'product_category');
        
        expect($terms)->toHaveCount(1);
        expect($terms[0]->name)->toBe('Electronics');
    });
    
    it('enforces editor capability for products', function () {
        $subscriber = createUser('subscriber');
        $editor = createUser('editor');
        
        loginAs($subscriber);
        expect(current_user_can('edit_products'))->toBeFalse();
        
        loginAs($editor);
        expect(current_user_can('edit_products'))->toBeTrue();
    });
    
});
```

---

## Testing User Permissions

```php
<?php
// tests/Integration/PermissionsTest.php

use function PestWP\createUser;
use function PestWP\loginAs;
use function PestWP\logout;

describe('Plugin Permissions', function () {
    
    describe('Custom Capabilities', function () {
        
        it('adds custom capabilities to administrator', function () {
            $admin = createUser('administrator');
            
            expect($admin)
                ->can('manage_my_plugin')
                ->can('my_plugin_settings')
                ->can('my_plugin_reports');
        });
        
        it('adds limited capabilities to editor', function () {
            $editor = createUser('editor');
            
            expect($editor)
                ->can('use_my_plugin')
                ->not->can('manage_my_plugin')
                ->not->can('my_plugin_settings');
        });
        
        it('denies capabilities to subscriber', function () {
            $subscriber = createUser('subscriber');
            
            expect($subscriber)
                ->not->can('use_my_plugin')
                ->not->can('manage_my_plugin');
        });
        
    });
    
    describe('Settings Access', function () {
        
        it('allows admin to access settings page', function () {
            $admin = createUser('administrator');
            loginAs($admin);
            
            expect(my_plugin_can_access_settings())->toBeTrue();
        });
        
        it('denies editor access to settings page', function () {
            $editor = createUser('editor');
            loginAs($editor);
            
            expect(my_plugin_can_access_settings())->toBeFalse();
        });
        
    });
    
    describe('Content Restrictions', function () {
        
        it('restricts premium content to subscribers', function () {
            $post = createPost([
                'post_type' => 'post',
                'meta_input' => ['_is_premium' => true],
            ]);
            
            // Guest user
            logout();
            expect(my_plugin_can_view_post($post->ID))->toBeFalse();
            
            // Regular subscriber
            $user = createUser('subscriber');
            loginAs($user);
            expect(my_plugin_can_view_post($post->ID))->toBeFalse();
            
            // Premium subscriber
            update_user_meta($user->ID, '_has_premium', true);
            wp_cache_flush();
            expect(my_plugin_can_view_post($post->ID))->toBeTrue();
        });
        
    });
    
});
```

---

## Testing Settings Pages

```php
<?php
// tests/Integration/SettingsTest.php

use function PestWP\createUser;
use function PestWP\loginAs;

describe('Settings Page', function () {
    
    beforeEach(function () {
        // Ensure settings are registered
        do_action('admin_init');
    });
    
    it('registers settings', function () {
        global $wp_registered_settings;
        
        expect($wp_registered_settings)->toHaveKey('my_plugin_settings');
    });
    
    it('registers settings sections', function () {
        global $wp_settings_sections;
        
        expect($wp_settings_sections['my-plugin-settings'] ?? [])
            ->toHaveKey('my_plugin_general');
    });
    
    it('sanitizes settings on save', function () {
        $input = [
            'api_key' => '  my-key-123  ',
            'max_items' => '50abc', // Invalid number
            'email' => 'invalid-email',
        ];
        
        $sanitized = my_plugin_sanitize_settings($input);
        
        expect($sanitized['api_key'])->toBe('my-key-123'); // Trimmed
        expect($sanitized['max_items'])->toBe(50); // Numeric only
        expect($sanitized['email'])->toBe(''); // Invalid removed
    });
    
    it('validates required fields', function () {
        $input = [
            'api_key' => '', // Required field empty
        ];
        
        $result = my_plugin_sanitize_settings($input);
        
        // Check for error
        $errors = get_settings_errors('my_plugin_settings');
        
        expect($errors)->not->toBeEmpty();
        expect($errors[0]['code'])->toBe('api_key_required');
    });
    
    it('saves valid settings', function () {
        $admin = createUser('administrator');
        loginAs($admin);
        
        $settings = [
            'api_key' => 'valid-key',
            'max_items' => 100,
            'notifications' => true,
        ];
        
        update_option('my_plugin_settings', $settings);
        
        expect('my_plugin_settings')->toHaveOption($settings);
    });
    
});
```

---

## Testing AJAX Handlers

```php
<?php
// tests/Integration/AjaxTest.php

use function PestWP\createPost;
use function PestWP\createUser;
use function PestWP\loginAs;

describe('AJAX Handlers', function () {
    
    beforeEach(function () {
        // Set up AJAX environment
        if (!defined('DOING_AJAX')) {
            define('DOING_AJAX', true);
        }
        
        // Clear any previous output
        ob_start();
    });
    
    afterEach(function () {
        ob_end_clean();
    });
    
    it('registers AJAX actions', function () {
        global $wp_filter;
        
        expect($wp_filter)->toHaveKey('wp_ajax_my_plugin_action');
        expect($wp_filter)->toHaveKey('wp_ajax_nopriv_my_plugin_public_action');
    });
    
    it('requires authentication for private actions', function () {
        logout();
        
        $_POST['action'] = 'my_plugin_action';
        $_POST['nonce'] = wp_create_nonce('my_plugin_nonce');
        
        // Capture the die response
        try {
            do_action('wp_ajax_nopriv_my_plugin_action');
        } catch (WPDieException $e) {
            expect($e->getMessage())->toContain('Unauthorized');
        }
    });
    
    it('validates nonce', function () {
        $user = createUser('editor');
        loginAs($user);
        
        $_POST['action'] = 'my_plugin_action';
        $_POST['nonce'] = 'invalid-nonce';
        
        try {
            do_action('wp_ajax_my_plugin_action');
        } catch (WPDieException $e) {
            expect($e->getMessage())->toContain('Invalid nonce');
        }
    });
    
    it('processes valid request', function () {
        $user = createUser('editor');
        $post = createPost();
        loginAs($user);
        
        $_POST['action'] = 'my_plugin_action';
        $_POST['nonce'] = wp_create_nonce('my_plugin_nonce');
        $_POST['post_id'] = $post->ID;
        $_POST['data'] = 'test data';
        
        ob_start();
        do_action('wp_ajax_my_plugin_action');
        $response = json_decode(ob_get_clean(), true);
        
        expect($response['success'])->toBeTrue();
        expect($response['data']['message'])->toBe('Action completed');
    });
    
});
```

---

## Testing Shortcodes

```php
<?php
// tests/Integration/ShortcodeTest.php

use function PestWP\createPost;
use function PestWP\createUser;
use function PestWP\loginAs;

describe('Plugin Shortcodes', function () {
    
    describe('[my_plugin_button]', function () {
        
        it('is registered', function () {
            expect('my_plugin_button')->toBeRegisteredShortcode();
        });
        
        it('renders with default attributes', function () {
            $output = do_shortcode('[my_plugin_button]');
            
            expect($output)->toContain('<button');
            expect($output)->toContain('class="my-plugin-btn"');
            expect($output)->toContain('Click Here'); // Default text
        });
        
        it('accepts custom text', function () {
            $output = do_shortcode('[my_plugin_button text="Subscribe Now"]');
            
            expect($output)->toContain('Subscribe Now');
        });
        
        it('accepts custom CSS class', function () {
            $output = do_shortcode('[my_plugin_button class="custom-class"]');
            
            expect($output)->toContain('class="my-plugin-btn custom-class"');
        });
        
        it('accepts URL attribute', function () {
            $output = do_shortcode('[my_plugin_button url="https://example.com"]');
            
            expect($output)->toContain('href="https://example.com"');
        });
        
        it('escapes attributes properly', function () {
            $output = do_shortcode('[my_plugin_button text="<script>alert(1)</script>"]');
            
            expect($output)->not->toContain('<script>');
            expect($output)->toContain('&lt;script&gt;');
        });
        
    });
    
    describe('[my_plugin_user_content]', function () {
        
        it('shows content only to logged-in users', function () {
            logout();
            
            $output = do_shortcode('[my_plugin_user_content]Private content[/my_plugin_user_content]');
            
            expect($output)->not->toContain('Private content');
            expect($output)->toContain('Please log in');
        });
        
        it('shows content to logged-in users', function () {
            $user = createUser();
            loginAs($user);
            
            $output = do_shortcode('[my_plugin_user_content]Private content[/my_plugin_user_content]');
            
            expect($output)->toContain('Private content');
        });
        
        it('respects role restriction', function () {
            $subscriber = createUser('subscriber');
            $editor = createUser('editor');
            
            loginAs($subscriber);
            $output = do_shortcode('[my_plugin_user_content role="editor"]Editor only[/my_plugin_user_content]');
            expect($output)->not->toContain('Editor only');
            
            loginAs($editor);
            $output = do_shortcode('[my_plugin_user_content role="editor"]Editor only[/my_plugin_user_content]');
            expect($output)->toContain('Editor only');
        });
        
    });
    
    describe('[my_plugin_posts]', function () {
        
        it('displays recent posts', function () {
            createPost(['post_title' => 'Post One']);
            createPost(['post_title' => 'Post Two']);
            createPost(['post_title' => 'Post Three']);
            
            $output = do_shortcode('[my_plugin_posts count="3"]');
            
            expect($output)->toContain('Post One');
            expect($output)->toContain('Post Two');
            expect($output)->toContain('Post Three');
        });
        
        it('limits number of posts', function () {
            for ($i = 1; $i <= 10; $i++) {
                createPost(['post_title' => "Post $i"]);
            }
            
            $output = do_shortcode('[my_plugin_posts count="5"]');
            
            // Count list items
            preg_match_all('/<li/', $output, $matches);
            expect(count($matches[0]))->toBe(5);
        });
        
    });
    
});
```

---

## Testing REST API Endpoints

```php
<?php
// tests/Integration/RestApiTest.php

use function PestWP\createPost;
use function PestWP\createUser;
use function PestWP\loginAs;

describe('REST API Endpoints', function () {
    
    beforeEach(function () {
        // Ensure REST API is initialized
        rest_api_init();
        do_action('rest_api_init');
        
        // Create REST server
        global $wp_rest_server;
        $wp_rest_server = new WP_REST_Server();
        do_action('rest_api_init', $wp_rest_server);
    });
    
    describe('GET /my-plugin/v1/items', function () {
        
        it('registers the endpoint', function () {
            $routes = rest_get_server()->get_routes();
            
            expect($routes)->toHaveKey('/my-plugin/v1/items');
        });
        
        it('returns items list', function () {
            // Create test data
            createPost(['post_type' => 'my_item', 'post_title' => 'Item 1']);
            createPost(['post_type' => 'my_item', 'post_title' => 'Item 2']);
            
            $request = new WP_REST_Request('GET', '/my-plugin/v1/items');
            $response = rest_get_server()->dispatch($request);
            
            expect($response->get_status())->toBe(200);
            
            $data = $response->get_data();
            expect($data)->toHaveCount(2);
        });
        
        it('supports pagination', function () {
            for ($i = 1; $i <= 15; $i++) {
                createPost(['post_type' => 'my_item', 'post_title' => "Item $i"]);
            }
            
            $request = new WP_REST_Request('GET', '/my-plugin/v1/items');
            $request->set_param('per_page', 5);
            $request->set_param('page', 2);
            
            $response = rest_get_server()->dispatch($request);
            $data = $response->get_data();
            
            expect($data)->toHaveCount(5);
            expect($response->get_headers()['X-WP-Total'])->toBe(15);
            expect($response->get_headers()['X-WP-TotalPages'])->toBe(3);
        });
        
    });
    
    describe('POST /my-plugin/v1/items', function () {
        
        it('requires authentication', function () {
            logout();
            
            $request = new WP_REST_Request('POST', '/my-plugin/v1/items');
            $request->set_body_params(['title' => 'New Item']);
            
            $response = rest_get_server()->dispatch($request);
            
            expect($response->get_status())->toBe(401);
        });
        
        it('requires proper capability', function () {
            $subscriber = createUser('subscriber');
            loginAs($subscriber);
            wp_set_current_user($subscriber->ID);
            
            $request = new WP_REST_Request('POST', '/my-plugin/v1/items');
            $request->set_body_params(['title' => 'New Item']);
            
            $response = rest_get_server()->dispatch($request);
            
            expect($response->get_status())->toBe(403);
        });
        
        it('creates item with valid data', function () {
            $editor = createUser('editor');
            loginAs($editor);
            wp_set_current_user($editor->ID);
            
            $request = new WP_REST_Request('POST', '/my-plugin/v1/items');
            $request->set_body_params([
                'title' => 'New Item',
                'description' => 'Item description',
            ]);
            
            $response = rest_get_server()->dispatch($request);
            
            expect($response->get_status())->toBe(201);
            
            $data = $response->get_data();
            expect($data['title'])->toBe('New Item');
            expect($data['id'])->toBeInt();
        });
        
        it('validates required fields', function () {
            $editor = createUser('editor');
            loginAs($editor);
            wp_set_current_user($editor->ID);
            
            $request = new WP_REST_Request('POST', '/my-plugin/v1/items');
            $request->set_body_params([]); // Missing title
            
            $response = rest_get_server()->dispatch($request);
            
            expect($response->get_status())->toBe(400);
            expect($response->get_data()['code'])->toBe('rest_missing_callback_param');
        });
        
    });
    
    describe('DELETE /my-plugin/v1/items/{id}', function () {
        
        it('deletes item', function () {
            $admin = createUser('administrator');
            loginAs($admin);
            wp_set_current_user($admin->ID);
            
            $post = createPost(['post_type' => 'my_item']);
            
            $request = new WP_REST_Request('DELETE', "/my-plugin/v1/items/{$post->ID}");
            $response = rest_get_server()->dispatch($request);
            
            expect($response->get_status())->toBe(200);
            expect(get_post($post->ID))->toBeNull();
        });
        
        it('returns 404 for non-existent item', function () {
            $admin = createUser('administrator');
            loginAs($admin);
            wp_set_current_user($admin->ID);
            
            $request = new WP_REST_Request('DELETE', '/my-plugin/v1/items/99999');
            $response = rest_get_server()->dispatch($request);
            
            expect($response->get_status())->toBe(404);
        });
        
    });
    
});
```

---

## Advanced Configuration

### Testing with External Dependencies

Create a test helper MU-plugin to mock external services:

```php
<?php
// tests/mu-plugins/mock-external-services.php

/**
 * Mock external HTTP requests during tests.
 */
add_filter('pre_http_request', function ($preempt, $args, $url) {
    $mocks = [
        'api.stripe.com' => [
            'response' => ['code' => 200],
            'body' => json_encode(['id' => 'ch_test_123', 'status' => 'succeeded']),
        ],
        'api.mailchimp.com' => [
            'response' => ['code' => 200],
            'body' => json_encode(['id' => 'list_123', 'member_count' => 100]),
        ],
        'maps.googleapis.com' => [
            'response' => ['code' => 200],
            'body' => json_encode(['results' => [], 'status' => 'OK']),
        ],
    ];
    
    foreach ($mocks as $domain => $response) {
        if (str_contains($url, $domain)) {
            return $response;
        }
    }
    
    return $preempt;
}, 10, 3);

/**
 * Disable email sending during tests.
 */
add_filter('pre_wp_mail', '__return_true');
```

### Testing Database Migrations

```php
<?php
// tests/Integration/MigrationTest.php

describe('Database Migrations', function () {
    
    it('creates custom tables on activation', function () {
        global $wpdb;
        
        // Run activation
        do_action('activate_my-plugin/my-plugin.php');
        
        // Check table exists
        $table = $wpdb->prefix . 'my_plugin_data';
        $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        
        expect($result)->toBe($table);
    });
    
    it('has correct table structure', function () {
        global $wpdb;
        
        $table = $wpdb->prefix . 'my_plugin_data';
        $columns = $wpdb->get_results("DESCRIBE $table");
        
        $columnNames = array_column($columns, 'Field');
        
        expect($columnNames)->toContain('id');
        expect($columnNames)->toContain('user_id');
        expect($columnNames)->toContain('data');
        expect($columnNames)->toContain('created_at');
    });
    
    it('runs migrations on update', function () {
        // Simulate old version
        update_option('my_plugin_db_version', '1.0.0');
        
        // Trigger update check
        my_plugin_check_db_updates();
        
        // Check new version
        expect(get_option('my_plugin_db_version'))->toBe('1.1.0');
    });
    
});
```

### Testing Cron Jobs

```php
<?php
// tests/Integration/CronTest.php

describe('Scheduled Tasks', function () {
    
    it('schedules daily cleanup', function () {
        // Activation should schedule the event
        do_action('activate_my-plugin/my-plugin.php');
        
        $scheduled = wp_next_scheduled('my_plugin_daily_cleanup');
        
        expect($scheduled)->not->toBeFalse();
    });
    
    it('unschedules on deactivation', function () {
        // First activate
        do_action('activate_my-plugin/my-plugin.php');
        
        // Then deactivate
        do_action('deactivate_my-plugin/my-plugin.php');
        
        $scheduled = wp_next_scheduled('my_plugin_daily_cleanup');
        
        expect($scheduled)->toBeFalse();
    });
    
    it('cleanup removes old data', function () {
        // Create old transients
        set_transient('my_plugin_cache_old', 'data', -3600); // Expired
        set_transient('my_plugin_cache_new', 'data', 3600); // Valid
        
        // Run cleanup
        do_action('my_plugin_daily_cleanup');
        
        expect(get_transient('my_plugin_cache_old'))->toBeFalse();
        expect(get_transient('my_plugin_cache_new'))->toBe('data');
    });
    
});
```

---

## Best Practices

### 1. Keep Tests Focused

```php
// ❌ Bad: Testing too many things
it('creates user, logs in, creates post, and publishes', function () {
    $user = createUser('editor');
    loginAs($user);
    $post = createPost(['post_status' => 'draft']);
    wp_update_post(['ID' => $post->ID, 'post_status' => 'publish']);
    expect($post)->toBePublished();
});

// ✅ Good: One assertion per test
it('creates draft post', function () {
    $post = createPost(['post_status' => 'draft']);
    expect($post)->toBeDraft();
});

it('can publish draft post', function () {
    $post = createPost(['post_status' => 'draft']);
    wp_update_post(['ID' => $post->ID, 'post_status' => 'publish']);
    
    $post = get_post($post->ID);
    expect($post)->toBePublished();
});
```

### 2. Use Descriptive Test Names

```php
// ❌ Bad
it('works', function () { /* ... */ });
it('test 1', function () { /* ... */ });

// ✅ Good
it('creates user with subscriber role by default', function () { /* ... */ });
it('denies access when user lacks capability', function () { /* ... */ });
it('sends notification email on post publish', function () { /* ... */ });
```

### 3. Group Related Tests

```php
describe('User Registration', function () {
    
    describe('Validation', function () {
        it('requires email', function () { /* ... */ });
        it('requires unique username', function () { /* ... */ });
        it('validates password strength', function () { /* ... */ });
    });
    
    describe('Success Flow', function () {
        it('creates user account', function () { /* ... */ });
        it('assigns default role', function () { /* ... */ });
        it('sends welcome email', function () { /* ... */ });
    });
    
});
```

### 4. Use Data Providers for Similar Tests

```php
dataset('user_roles', [
    'subscriber' => ['subscriber', false],
    'contributor' => ['contributor', false],
    'author' => ['author', false],
    'editor' => ['editor', true],
    'administrator' => ['administrator', true],
]);

it('checks edit_others_posts capability', function (string $role, bool $expected) {
    $user = createUser($role);
    loginAs($user);
    
    expect(current_user_can('edit_others_posts'))->toBe($expected);
})->with('user_roles');
```

### 5. Don't Test WordPress Core

```php
// ❌ Bad: Testing WordPress itself
it('wp_insert_post creates a post', function () {
    $postId = wp_insert_post(['post_title' => 'Test']);
    expect($postId)->toBeGreaterThan(0);
});

// ✅ Good: Test YOUR plugin's behavior
it('my_plugin_create_special_post adds custom meta', function () {
    $postId = my_plugin_create_special_post('Test');
    
    expect(get_post_meta($postId, '_is_special', true))->toBeTrue();
});
```

### 6. Clean Up After Tests (When Needed)

Database isolation handles most cleanup, but for other resources:

```php
describe('File Uploads', function () {
    
    afterEach(function () {
        // Clean up uploaded files
        $uploads = wp_upload_dir();
        array_map('unlink', glob($uploads['path'] . '/test-*'));
    });
    
    it('processes uploaded file', function () {
        // Test file upload...
    });
    
});
```

### 7. Use Helper Functions for Complex Setup

```php
// tests/Helpers.php
function createProductWithVariations(array $variations = []): WP_Post
{
    $product = createPost([
        'post_type' => 'product',
        'post_status' => 'publish',
    ]);
    
    foreach ($variations as $variation) {
        $varPost = createPost([
            'post_type' => 'product_variation',
            'post_parent' => $product->ID,
        ]);
        update_post_meta($varPost->ID, '_price', $variation['price']);
        update_post_meta($varPost->ID, '_sku', $variation['sku']);
    }
    
    return $product;
}

// In tests
it('calculates total with variations', function () {
    $product = createProductWithVariations([
        ['price' => '10.00', 'sku' => 'VAR-1'],
        ['price' => '20.00', 'sku' => 'VAR-2'],
    ]);
    
    $total = my_plugin_get_variations_total($product->ID);
    
    expect($total)->toBe(30.00);
});
```

---

## Browser Testing

Browser testing allows you to test your plugin's UI, JavaScript functionality, and complete user workflows using a real browser. PestWP integrates with **Pest Browser Plugin** (based on Playwright) to provide end-to-end testing capabilities.

### When to Use Browser Tests

| Test Type | Integration Tests | Browser Tests |
|-----------|------------------|---------------|
| **Speed** | ⚡ ~2ms per test | 🐢 ~500ms per test |
| **Database** | SQLite (automatic) | MySQL (requires setup) |
| **JavaScript** | ❌ No | ✅ Yes |
| **Admin UI** | ❌ No | ✅ Yes |
| **Best For** | Hooks, filters, CRUD | UI, Gutenberg, forms |

### Setting Up Browser Testing

#### 1. Install Browser Dependencies

```bash
# Install Playwright browsers
./vendor/bin/pest --browser-install
```

#### 2. Configure Browser Credentials

Use the interactive wizard:

```bash
vendor/bin/pest-setup-browser \
    --url http://localhost:8080 \
    --user admin \
    --pass your-password
```

Or manually add to `tests/Pest.php`:

```php
<?php

declare(strict_types=1);

use PestWP\Config;
use PestWP\Database\TransactionManager;

/*
|--------------------------------------------------------------------------
| Plugin Configuration (for Integration Tests)
|--------------------------------------------------------------------------
*/

Config::plugins(dirname(__DIR__) . '/my-plugin.php');

/*
|--------------------------------------------------------------------------
| Browser Configuration (for Browser Tests)
|--------------------------------------------------------------------------
*/

function browser(): array
{
    return [
        'base_url' => 'http://localhost:8080',
        'admin_user' => 'admin',
        'admin_password' => 'your-secure-password',
    ];
}

/*
|--------------------------------------------------------------------------
| Database Isolation (Integration Tests Only)
|--------------------------------------------------------------------------
*/

uses()
    ->beforeEach(fn () => TransactionManager::beginTransaction())
    ->afterEach(fn () => TransactionManager::rollback())
    ->in('Integration');
```

### Environment-Specific Configuration

#### Using Environment Variables

```php
// tests/Pest.php

function browser(): array
{
    return [
        'base_url' => getenv('WP_BASE_URL') ?: 'http://localhost:8080',
        'admin_user' => getenv('WP_ADMIN_USER') ?: 'admin',
        'admin_password' => getenv('WP_ADMIN_PASSWORD') ?: 'password',
    ];
}
```

Create environment files for different setups:

```bash
# .env.local (Local Docker)
WP_BASE_URL=http://localhost:8080
WP_ADMIN_USER=admin
WP_ADMIN_PASSWORD=local_password

# .env.staging (Staging Server)
WP_BASE_URL=https://staging.mysite.com
WP_ADMIN_USER=test_admin
WP_ADMIN_PASSWORD=staging_password

# .env.ci (CI/CD Pipeline)
WP_BASE_URL=http://wordpress:80
WP_ADMIN_USER=admin
WP_ADMIN_PASSWORD=ci_password
```

Load the appropriate environment:

```bash
# Local development
source .env.local && ./vendor/bin/pest --browser

# CI/CD
source .env.ci && ./vendor/bin/pest --browser
```

#### Multi-Environment Configuration

```php
// tests/Pest.php

function browser(): array
{
    $env = getenv('TEST_ENV') ?: 'local';
    
    $configs = [
        'local' => [
            'base_url' => 'http://localhost:8080',
            'admin_user' => 'admin',
            'admin_password' => 'password',
        ],
        'docker' => [
            'base_url' => 'http://wordpress:80',
            'admin_user' => 'admin',
            'admin_password' => 'docker_password',
        ],
        'staging' => [
            'base_url' => 'https://staging.example.com',
            'admin_user' => 'test_admin',
            'admin_password' => getenv('STAGING_PASSWORD'),
        ],
        'production' => [
            'base_url' => 'https://example.com',
            'admin_user' => 'test_admin',
            'admin_password' => getenv('PROD_TEST_PASSWORD'),
        ],
    ];
    
    return $configs[$env] ?? $configs['local'];
}
```

Run with different environments:

```bash
TEST_ENV=local ./vendor/bin/pest --browser
TEST_ENV=docker ./vendor/bin/pest --browser
TEST_ENV=staging ./vendor/bin/pest --browser
```

### Writing Browser Tests

Pest Browser Plugin usa la función `visit()` que retorna un objeto `$page` para interactuar con el navegador. **No usa closures ni `$browser`** como Laravel Dusk.

Create test files in `tests/Browser/`:

```php
<?php
// tests/Browser/AdminDashboardTest.php

declare(strict_types=1);

describe('Admin Dashboard', function () {

    it('can access the login page', function () {
        $config = browser();
        
        $page = visit($config['base_url'] . '/wp-login.php');
        
        $page->assertSee('Log In');
    });

    it('can log into WordPress', function () {
        $config = browser();
        
        $page = visit($config['base_url'] . '/wp-login.php')
            ->type('user_login', $config['admin_user'])
            ->type('user_pass', $config['admin_password'])
            ->press('Log In');
        
        $page->assertPathBeginsWith('/wp-admin')
            ->assertSee('Dashboard');
    });

    it('shows plugin menu item when logged in', function () {
        $config = browser();
        
        // Login and navigate
        visit($config['base_url'] . '/wp-login.php')
            ->type('user_login', $config['admin_user'])
            ->type('user_pass', $config['admin_password'])
            ->press('Log In')
            ->assertSee('My Plugin'); // Your plugin menu
    });

});
```

### Sintaxis de Pest Browser Plugin

```php
// ✅ Sintaxis correcta - visit() retorna $page
$page = visit('/');
$page->assertSee('Welcome');

// ✅ Encadenado
visit('/wp-admin/')
    ->click('Posts')
    ->assertSee('All Posts');

// ✅ Con configuración
$page = visit('/')
    ->on()->mobile()  // Viewport móvil
    ->inDarkMode();   // Modo oscuro

// ❌ INCORRECTO - No usar browse() ni closures
// browse(function ($browser) { ... }); // Esto es Laravel Dusk, NO Pest
```

### Testing Plugin Settings Page

```php
<?php
// tests/Browser/PluginSettingsTest.php

declare(strict_types=1);

describe('Plugin Settings', function () {

    it('can save settings', function () {
        $config = browser();
        
        // Login first
        visit($config['base_url'] . '/wp-login.php')
            ->type('user_login', $config['admin_user'])
            ->type('user_pass', $config['admin_password'])
            ->press('Log In');
        
        // Navigate to settings and save
        visit($config['base_url'] . '/wp-admin/admin.php?page=my-plugin-settings')
            ->type('#my_plugin_api_key', 'test-api-key-12345')
            ->check('#my_plugin_enable_feature')
            ->press('Save Changes')
            ->assertSee('Settings saved');
    });

    it('validates required fields', function () {
        $config = browser();
        
        visit($config['base_url'] . '/wp-admin/admin.php?page=my-plugin-settings')
            ->clear('#my_plugin_api_key')
            ->press('Save Changes')
            ->assertSee('API key is required');
    });

    it('preserves saved values after refresh', function () {
        $config = browser();
        
        // Save a value
        $page = visit($config['base_url'] . '/wp-admin/admin.php?page=my-plugin-settings')
            ->type('#my_plugin_api_key', 'saved-key-value')
            ->press('Save Changes')
            ->assertSee('Settings saved');
        
        // Navigate again and verify
        visit($config['base_url'] . '/wp-admin/admin.php?page=my-plugin-settings')
            ->assertValue('#my_plugin_api_key', 'saved-key-value');
    });

});
```

### Testing Gutenberg Blocks

```php
<?php
// tests/Browser/GutenbergBlocksTest.php

declare(strict_types=1);

describe('Gutenberg Blocks', function () {

    it('can insert my custom block', function () {
        $config = browser();
        
        // Login first
        visit($config['base_url'] . '/wp-login.php')
            ->type('user_login', $config['admin_user'])
            ->type('user_pass', $config['admin_password'])
            ->press('Log In');
        
        // Create new post and insert block
        visit($config['base_url'] . '/wp-admin/post-new.php')
            // Open block inserter
            ->click('[aria-label="Toggle block inserter"]')
            ->wait(1) // Wait for inserter to open
            // Search for custom block
            ->type('.block-editor-inserter__search-input', 'My Custom Block')
            ->click('[aria-label="My Custom Block"]')
            // Verify block is inserted
            ->assertPresent('.wp-block-my-plugin-custom-block');
    });

    it('can configure block settings', function () {
        $config = browser();
        
        visit($config['base_url'] . '/wp-admin/post-new.php')
            // Click on block
            ->click('.wp-block-my-plugin-custom-block')
            // Open block settings
            ->click('[aria-label="Settings"]')
            // Change a setting
            ->type('[aria-label="Block Title"]', 'Custom Title')
            // Verify setting applied
            ->assertSeeIn('.wp-block-my-plugin-custom-block', 'Custom Title');
    });

    it('can publish post with custom block', function () {
        $config = browser();
        
        visit($config['base_url'] . '/wp-admin/post-new.php')
            ->type('[aria-label="Add title"]', 'Post with Custom Block')
            // Publish
            ->click('.editor-post-publish-button__button')
            ->wait(1) // Wait for panel
            ->click('.editor-post-publish-panel .editor-post-publish-button__button')
            ->assertSee('Post published');
    });

});
```

### Testing with WP Admin Locators

PestWP provides helper functions for common WordPress admin selectors:

```php
<?php
// tests/Browser/AdminNavigationTest.php

declare(strict_types=1);

use function PestWP\Functions\adminUrl;
use function PestWP\Functions\loginUrl;
use function PestWP\Functions\newPostUrl;
use function PestWP\Functions\menuSelector;
use function PestWP\Functions\submenuSelector;
use function PestWP\Functions\successNotice;
use function PestWP\Functions\publishButtonSelector;
use function PestWP\Functions\postTitleSelector;
use function PestWP\Functions\getBrowserConfig;

describe('Admin Navigation', function () {

    it('can navigate to posts', function () {
        $config = getBrowserConfig();
        
        visit($config['base_url'] . adminUrl())
            ->click(menuSelector('Posts'))
            ->assertPathContains('edit.php')
            ->assertSee('Posts');
    });

    it('can access plugin submenu', function () {
        $config = getBrowserConfig();
        
        visit($config['base_url'] . adminUrl())
            ->click(menuSelector('My Plugin'))
            ->click(submenuSelector('Settings'))
            ->assertSee('Plugin Settings');
    });

    it('can create and publish a post', function () {
        $config = getBrowserConfig();
        
        visit($config['base_url'] . newPostUrl())
            ->type(postTitleSelector(), 'Test Post')
            ->click(publishButtonSelector())
            ->wait(1)
            ->click(publishButtonSelector()) // Confirm
            ->assertSee('Post published');
    });

});
```

### Running Browser Tests

```bash
# Run all browser tests
./vendor/bin/pest --browser

# Run with visible browser (for debugging)
./vendor/bin/pest --browser --headed

# Run specific browser test file
./vendor/bin/pest tests/Browser/AdminDashboardTest.php --browser

# Run with verbose output
./vendor/bin/pest --browser -v
```

### CI/CD Configuration

#### GitHub Actions

```yaml
# .github/workflows/browser-tests.yml
name: Browser Tests

on: [push, pull_request]

jobs:
  browser-tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: wordpress
        ports:
          - 3306:3306
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, xml, mysql
      
      - name: Install Composer dependencies
        run: composer install
      
      - name: Setup WordPress
        run: |
          # Download WordPress
          curl -O https://wordpress.org/latest.tar.gz
          tar -xzf latest.tar.gz
          
          # Configure
          cp wordpress/wp-config-sample.php wordpress/wp-config.php
          sed -i "s/database_name_here/wordpress/" wordpress/wp-config.php
          sed -i "s/username_here/root/" wordpress/wp-config.php
          sed -i "s/password_here/password/" wordpress/wp-config.php
          sed -i "s/localhost/127.0.0.1/" wordpress/wp-config.php
          
          # Start PHP server
          php -S localhost:8080 -t wordpress &
          sleep 5
          
          # Install WordPress
          curl "http://localhost:8080/wp-admin/install.php?step=2" \
            --data "weblog_title=Test&user_name=admin&admin_password=password&admin_password2=password&admin_email=admin@example.com"
      
      - name: Install browsers
        run: ./vendor/bin/pest --browser-install
      
      - name: Run browser tests
        env:
          WP_BASE_URL: http://localhost:8080
          WP_ADMIN_USER: admin
          WP_ADMIN_PASSWORD: password
        run: ./vendor/bin/pest --browser
      
      - name: Upload screenshots on failure
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: browser-screenshots
          path: tests/.pest/screenshots/
```

#### Docker Compose Setup

```yaml
# docker-compose.test.yml
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
      - ./:/var/www/html/wp-content/plugins/my-plugin
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - db_data:/var/lib/mysql

  test-runner:
    build:
      context: .
      dockerfile: Dockerfile.test
    environment:
      WP_BASE_URL: http://wordpress:80
      WP_ADMIN_USER: admin
      WP_ADMIN_PASSWORD: admin
      TEST_ENV: docker
    depends_on:
      - wordpress
    volumes:
      - ./:/app
    command: ./vendor/bin/pest --browser

volumes:
  db_data:
```

### Browser Testing Best Practices

#### 1. Encadenar Métodos

```php
// ✅ Fluent API - encadenar todo
visit('/wp-admin/')
    ->click('Posts')
    ->click('Add New Post')
    ->type('[aria-label="Add title"]', 'My Post')
    ->press('Publish')
    ->assertSee('Post published');
```

#### 2. Usar `wait()` para Contenido Dinámico

```php
// ❌ Bad: Sin espera para contenido AJAX
visit('/wp-admin/post-new.php')
    ->type('[aria-label="Add title"]', 'My Post');

// ✅ Good: Esperar antes de interactuar
visit('/wp-admin/post-new.php')
    ->wait(1) // Esperar 1 segundo
    ->type('[aria-label="Add title"]', 'My Post');

// ✅ También: Usar selectores que verifican presencia
visit('/wp-admin/post-new.php')
    ->assertPresent('[aria-label="Add title"]')
    ->type('[aria-label="Add title"]', 'My Post');
```

#### 3. Usar Selectores Estables

```php
// ❌ Bad: Selectores frágiles
$page->click('.css-1234xyz');
$page->click('div > div > button');

// ✅ Good: Selectores estables
$page->press('Publish');                      // Por texto del botón
$page->click('[aria-label="Settings"]');      // Por aria-label
$page->click('[data-testid="save-button"]');  // Por data-testid
$page->click('@save-button');                 // Atajo para data-testid
```

#### 4. Aislar Datos de Test

```php
beforeEach(function () {
    // Crear datos únicos para cada test
    $this->testPostTitle = 'Test Post ' . uniqid();
});

it('creates a post', function () {
    $config = browser();
    
    visit($config['base_url'] . '/wp-admin/post-new.php')
        ->type('[aria-label="Add title"]', $this->testPostTitle)
        ->press('Publish')
        ->wait(1)
        ->press('Publish')
        ->assertSee('Post published');
});
```

#### 5. Debugging con `--debug` y `screenshot()`

```php
it('can complete a complex workflow', function () {
    $config = browser();
    
    $page = visit($config['base_url'] . '/wp-admin/')
        ->click('My Plugin');
    
    // Tomar screenshot para debugging
    $page->screenshot('after-clicking-menu');
    
    $page->click('Settings')
        ->assertSee('Plugin Settings');
    
    // O usar debug() para pausar
    // $page->debug();
});
```

Ejecutar con navegador visible:

```bash
./vendor/bin/pest --debug    # Pausa en fallos
./vendor/bin/pest --headed   # Navegador visible siempre
```

---

## Troubleshooting

### Tests are slow

1. Make sure database isolation is enabled
2. Don't load unnecessary plugins
3. Use `--parallel` for parallel execution (with separate databases)

### WordPress functions not available

Ensure your test file is in the `Integration` directory and `Pest.php` is configured correctly.

### Plugin not loading

Check the path in `Config::plugins()` is absolute and correct:

```php
// Debug: Print the path
echo dirname(__DIR__) . '/my-plugin.php';

// Make sure file exists
Config::plugins(dirname(__DIR__) . '/my-plugin.php');
```

### Database changes persisting

Make sure `TransactionManager` is configured in `Pest.php`:

```php
uses()
    ->beforeEach(fn () => TransactionManager::beginTransaction())
    ->afterEach(fn () => TransactionManager::rollback())
    ->in('Integration');
```

---

## Resources

- [PestWP GitHub Repository](https://github.com/alvarodelera/pest-wp-plugin)
- [Pest PHP Documentation](https://pestphp.com)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
