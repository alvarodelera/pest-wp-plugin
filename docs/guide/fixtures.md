# Fixtures

PestWP provides a fixture system for managing reusable test data.

## Overview

Fixtures allow you to define test data in files and load them consistently across tests. This is useful for complex test scenarios that require specific data setups.

## Basic Usage

### The fixtures() Helper

```php
use function PestWP\Functions\fixtures;

// Get fixture manager
$manager = fixtures();

// Load from a path
$manager = fixtures('/path/to/fixtures');
```

### Loading Fixtures

```php
// Load from JSON file
$data = fixtures()->load('users.json');

// Load from YAML file
$data = fixtures()->load('posts.yaml');

// Load from PHP file
$data = fixtures()->load('settings.php');
```

## Defining Fixtures

### JSON Fixtures

```json
// tests/__fixtures__/users.json
{
    "users": [
        {
            "login": "admin",
            "email": "admin@example.com",
            "role": "administrator"
        },
        {
            "login": "editor",
            "email": "editor@example.com",
            "role": "editor"
        }
    ]
}
```

### YAML Fixtures

```yaml
# tests/__fixtures__/posts.yaml
posts:
  - title: "Welcome Post"
    content: "Welcome to our site!"
    status: publish
    
  - title: "Draft Article"
    content: "Work in progress..."
    status: draft
    meta:
      featured: true
      priority: 1
```

### PHP Fixtures

```php
<?php

// tests/__fixtures__/complex.php

return [
    'settings' => [
        'site_name' => 'Test Site',
        'options' => [
            'posts_per_page' => 10,
            'date_format' => 'Y-m-d',
        ],
    ],
    'users' => function () {
        // Dynamic fixture generation
        return [
            'admin' => [
                'login' => 'admin_' . uniqid(),
                'email' => 'admin_' . uniqid() . '@example.com',
            ],
        ];
    },
];
```

## Inline Fixtures

Define fixtures directly in tests:

```php
$fixtures = fixtures()->define([
    'users' => [
        'admin' => [
            'login' => 'testadmin',
            'role' => 'administrator',
        ],
        'subscriber' => [
            'login' => 'testuser',
            'role' => 'subscriber',
        ],
    ],
    'posts' => [
        'welcome' => [
            'title' => 'Welcome',
            'status' => 'publish',
        ],
    ],
]);
```

## Seeding Fixtures

Create WordPress objects from fixtures:

```php
$fixtures = fixtures()->load('test-data.yaml');

// Seed all fixtures
$fixtures->seed();

// Access created objects
$admin = $fixtures->get('users.admin');      // WP_User
$post = $fixtures->get('posts.welcome');     // WP_Post
$category = $fixtures->get('terms.news');    // Term ID
```

### Custom Seeders

```php
$fixtures = fixtures()->define([
    'products' => [
        'widget' => [
            'title' => 'Cool Widget',
            'price' => '29.99',
        ],
    ],
]);

// Register custom seeder
$fixtures->seeder('products', function ($data, $key) {
    $post = createPost([
        'post_type' => 'product',
        'post_title' => $data['title'],
        'meta_input' => [
            'price' => $data['price'],
        ],
    ]);
    
    return $post;
});

// Seed with custom seeder
$fixtures->seed();

$product = $fixtures->get('products.widget');
expect($product)->toBeInstanceOf(WP_Post::class);
expect($product)->toHaveMeta('price', '29.99');
```

## Accessing Fixture Data

### Get Specific Values

```php
$fixtures = fixtures()->load('data.yaml');

// Get by key
$adminData = $fixtures->get('users.admin');

// Get nested value
$email = $fixtures->get('users.admin.email');

// Get with default
$value = $fixtures->get('missing.key', 'default');
```

### Check Existence

```php
if ($fixtures->has('users.admin')) {
    $admin = $fixtures->get('users.admin');
}
```

### Get All Data

```php
$allData = $fixtures->all();
```

## BeforeEach Pattern

Load fixtures before each test:

```php
beforeEach(function () {
    $this->fixtures = fixtures()->load('test-data.yaml')->seed();
    
    $this->admin = $this->fixtures->get('users.admin');
    $this->post = $this->fixtures->get('posts.main');
});

it('admin can edit post', function () {
    loginAs($this->admin);
    
    expect(current_user_can('edit_post', $this->post->ID))->toBeTrue();
});

it('post has correct status', function () {
    expect($this->post)->toBePublished();
});
```

## Shared Fixtures

Use shared fixtures across test files:

```php
// tests/Pest.php

uses()->beforeEach(function () {
    $this->fixtures = fixtures()
        ->setPath(dirname(__DIR__) . '/tests/__fixtures__')
        ->load('shared.yaml')
        ->seed();
})->in('Integration');
```

## Fixture Cleanup

Fixtures created within database isolation are automatically cleaned up:

```php
beforeEach(function () {
    // These will be rolled back after each test
    $this->fixtures = fixtures()->define([
        'users' => [
            'test' => ['login' => 'test', 'role' => 'subscriber'],
        ],
    ])->seed();
});

it('test one', function () {
    $user = $this->fixtures->get('users.test');
    expect($user)->toHaveRole('subscriber');
});

it('test two', function () {
    // Fresh user created, previous was rolled back
    $user = $this->fixtures->get('users.test');
    expect($user)->toHaveRole('subscriber');
});
```

## Complex Scenarios

### Related Objects

```yaml
# tests/__fixtures__/blog.yaml
authors:
  john:
    login: john_doe
    email: john@example.com
    role: author

posts:
  johns_post:
    title: "John's Article"
    status: publish
    author: "@authors.john"  # Reference to author
    
categories:
  tech:
    name: Technology
    
post_terms:
  - post: "@posts.johns_post"
    term: "@categories.tech"
    taxonomy: category
```

### Dynamic Data

```php
$fixtures = fixtures()->define([
    'users' => function () {
        return [
            'random' => [
                'login' => 'user_' . uniqid(),
                'email' => 'user_' . uniqid() . '@example.com',
                'role' => 'subscriber',
            ],
        ];
    },
]);

// Each call generates new data
$fixtures->seed();
$user1 = $fixtures->get('users.random');

$fixtures->reset()->seed();
$user2 = $fixtures->get('users.random');

// Different users
expect($user1->ID)->not->toBe($user2->ID);
```

## Best Practices

1. **Keep Fixtures Minimal**: Only include data needed for tests

2. **Use Descriptive Names**: Name fixtures clearly
   ```yaml
   posts:
     published_post_by_admin: { ... }
     draft_post_pending_review: { ... }
   ```

3. **Organize by Feature**: Group fixtures by feature/module
   ```
   __fixtures__/
   ├── auth/
   │   └── users.yaml
   ├── blog/
   │   ├── posts.yaml
   │   └── categories.yaml
   └── shop/
       ├── products.yaml
       └── orders.yaml
   ```

4. **Avoid Duplication**: Use shared fixtures for common data

5. **Document Dependencies**: Note when fixtures depend on each other

## Comparison with Factories

| Fixtures | Factories |
|----------|-----------|
| Pre-defined data | Dynamic creation |
| File-based | Code-based |
| Good for complex scenarios | Good for simple objects |
| Reusable across tests | Created per-test |
| Explicit relationships | Implicit defaults |

```php
// Fixtures: Load predefined data
$fixtures = fixtures()->load('users.yaml')->seed();
$admin = $fixtures->get('users.admin');

// Factories: Create on demand
$admin = createUser('administrator');
```

## Next Steps

- [Factories](factories.md) - Dynamic object creation
- [Database Isolation](database-isolation.md) - Automatic cleanup
- [Snapshots](snapshots.md) - Snapshot testing
