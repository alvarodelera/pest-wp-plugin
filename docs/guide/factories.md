# Factories

PestWP provides factory functions to easily create WordPress objects in your tests.

## Posts

### createPost()

Create a post and return the `WP_Post` object.

```php
use function PestWP\createPost;

// Create a published post with defaults
$post = createPost();

// Create with custom attributes
$post = createPost([
    'post_title' => 'My Custom Title',
    'post_content' => 'The content of the post.',
    'post_status' => 'draft',
    'post_type' => 'post',
    'post_author' => 1,
    'post_date' => '2024-01-15 10:30:00',
]);

// Create a page
$page = createPost([
    'post_type' => 'page',
    'post_title' => 'About Us',
]);

// Create a custom post type
$product = createPost([
    'post_type' => 'product',
    'post_title' => 'Cool Widget',
    'meta_input' => [
        'price' => '99.99',
        'sku' => 'WIDGET-001',
    ],
]);
```

### Default Values

| Attribute | Default |
|-----------|---------|
| `post_title` | `'Test Post {unique_id}'` |
| `post_content` | `'Test content'` |
| `post_status` | `'publish'` |
| `post_type` | `'post'` |

### With Metadata

```php
$post = createPost([
    'post_title' => 'Product',
    'meta_input' => [
        'price' => '29.99',
        'stock' => 100,
    ],
]);

expect($post)->toHaveMeta('price', '29.99');
```

### With Taxonomy Terms

```php
$post = createPost([
    'post_title' => 'News Article',
    'tax_input' => [
        'category' => ['news', 'featured'],
        'post_tag' => ['breaking', 'important'],
    ],
]);
```

## Users

### createUser()

Create a user and return the `WP_User` object.

```php
use function PestWP\createUser;

// Create a subscriber (default)
$user = createUser();

// Create with a specific role
$admin = createUser('administrator');
$editor = createUser('editor');
$author = createUser('author');
$contributor = createUser('contributor');
$subscriber = createUser('subscriber');

// Create with custom attributes
$user = createUser('editor', [
    'user_login' => 'john_doe',
    'user_email' => 'john@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'display_name' => 'John Doe',
]);

// Create with full options array
$user = createUser([
    'user_login' => 'jane_doe',
    'user_email' => 'jane@example.com',
    'user_pass' => 'secure_password',
    'role' => 'author',
]);
```

### Default Values

| Attribute | Default |
|-----------|---------|
| `user_login` | `'testuser_{unique_id}'` |
| `user_email` | `'test_{unique_id}@example.com'` |
| `user_pass` | Random password |
| `role` | `'subscriber'` |

### With User Meta

```php
$user = createUser('subscriber', [
    'meta_input' => [
        'phone_number' => '555-1234',
        'preferred_language' => 'en',
    ],
]);
```

## Terms

### createTerm()

Create a term and return the term ID.

```php
use function PestWP\createTerm;

// Create a category
$categoryId = createTerm('News', 'category');

// Create a tag
$tagId = createTerm('Featured', 'post_tag');

// Create with additional arguments
$termId = createTerm('Technology', 'category', [
    'slug' => 'tech',
    'description' => 'Technology related posts',
    'parent' => $parentTermId,
]);

// Create custom taxonomy term
$termId = createTerm('Red', 'product_color');
```

## Attachments

### createAttachment()

Create an attachment and return the attachment ID.

```php
use function PestWP\createAttachment;

// Create a dummy image attachment
$attachmentId = createAttachment();

// Create from a specific file
$attachmentId = createAttachment('/path/to/image.jpg');

// Create and attach to a post
$post = createPost();
$attachmentId = createAttachment('', $post->ID);

// Create with custom attributes
$attachmentId = createAttachment('', 0, [
    'post_title' => 'Featured Image',
    'post_mime_type' => 'image/png',
]);

// Set as featured image
$post = createPost();
$attachmentId = createAttachment();
set_post_thumbnail($post->ID, $attachmentId);
```

## Comments

WordPress doesn't have a PestWP wrapper, but you can use the core function:

```php
$post = createPost();

$commentId = wp_insert_comment([
    'comment_post_ID' => $post->ID,
    'comment_author' => 'John Doe',
    'comment_author_email' => 'john@example.com',
    'comment_content' => 'Great post!',
    'comment_approved' => 1,
]);
```

## Options

### setOption() / deleteOption()

Work with WordPress options:

```php
use function PestWP\setOption;
use function PestWP\deleteOption;

// Set an option
setOption('my_plugin_setting', 'value');
setOption('my_plugin_array', ['key' => 'value']);

// Verify option
expect('my_plugin_setting')->toHaveOption('value');

// Delete an option
deleteOption('my_plugin_setting');
```

## Transients

### setTransient() / deleteTransient()

Work with WordPress transients:

```php
use function PestWP\setTransient;
use function PestWP\deleteTransient;

// Set a transient (expires in 1 hour)
setTransient('my_cache_key', ['data' => 'value'], HOUR_IN_SECONDS);

// Verify transient
expect('my_cache_key')->toHaveTransient();

// Delete a transient
deleteTransient('my_cache_key');
```

## Shortcodes

### registerTestShortcode()

Register a shortcode for testing:

```php
use function PestWP\registerTestShortcode;
use function PestWP\unregisterShortcode;

// Register a test shortcode
registerTestShortcode('greeting', function ($atts) {
    $atts = shortcode_atts(['name' => 'World'], $atts);
    return "Hello, {$atts['name']}!";
});

// Verify registration
expect('greeting')->toBeRegisteredShortcode();

// Test output
$output = do_shortcode('[greeting name="John"]');
expect($output)->toBe('Hello, John!');

// Cleanup
unregisterShortcode('greeting');
```

## Factory Patterns

### Creating Multiple Objects

```php
// Create multiple posts
$posts = collect(range(1, 10))->map(function ($i) {
    return createPost(['post_title' => "Post {$i}"]);
});

// Create users with different roles
$users = [
    'admin' => createUser('administrator'),
    'editor' => createUser('editor'),
    'subscriber' => createUser('subscriber'),
];
```

### Creating Related Objects

```php
// Create author with posts
$author = createUser('author');
$posts = [];

for ($i = 1; $i <= 5; $i++) {
    $posts[] = createPost([
        'post_title' => "Article {$i}",
        'post_author' => $author->ID,
    ]);
}
```

### Reusable Factory Functions

Create custom factory functions in your test helpers:

```php
<?php

// tests/Helpers.php

function createProduct(array $attributes = []): WP_Post
{
    return createPost(array_merge([
        'post_type' => 'product',
        'post_title' => 'Test Product',
        'meta_input' => [
            'price' => '9.99',
            'sku' => 'TEST-' . uniqid(),
        ],
    ], $attributes));
}

function createOrder(WP_User $customer, array $products = []): WP_Post
{
    $order = createPost([
        'post_type' => 'shop_order',
        'post_author' => $customer->ID,
        'post_status' => 'wc-pending',
    ]);
    
    foreach ($products as $product) {
        add_post_meta($order->ID, '_product_ids', $product->ID);
    }
    
    return $order;
}
```

## Best Practices

1. **Use Factories Over Raw Inserts** - Factories handle defaults and return proper objects

2. **Create Minimal Data** - Only create what you need for the test

3. **Use Descriptive Titles** - Makes debugging easier
   ```php
   createPost(['post_title' => 'Published post for visibility test']);
   ```

4. **Leverage Database Isolation** - Tests clean up automatically

5. **Group Related Creations** - Keep setup code organized
   ```php
   beforeEach(function () {
       $this->admin = createUser('administrator');
       $this->post = createPost(['post_author' => $this->admin->ID]);
   });
   ```

## Next Steps

- [Expectations](expectations.md) - Assert on created objects
- [Authentication](authentication.md) - Test with created users
- [Fixtures](fixtures.md) - Reusable test data
