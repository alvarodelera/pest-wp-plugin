# Expectations

PestWP extends Pest's `expect()` API with WordPress-specific assertions.

## Post Expectations

### Status Assertions

```php
use function PestWP\createPost;

$post = createPost(['post_status' => 'publish']);

// Assert post status
expect($post)->toBePublished();
expect($post)->toBeDraft();       // Fails
expect($post)->toBePending();     // Fails
expect($post)->toBePrivate();     // Fails
expect($post)->toBeInTrash();     // Fails

// Negation
expect($post)->not->toBeDraft();
```

### All Post Status Expectations

| Expectation | Post Status |
|-------------|-------------|
| `toBePublished()` | `publish` |
| `toBeDraft()` | `draft` |
| `toBePending()` | `pending` |
| `toBePrivate()` | `private` |
| `toBeInTrash()` | `trash` |

### Metadata Assertions

```php
$post = createPost();
update_post_meta($post->ID, 'price', '29.99');
update_post_meta($post->ID, 'featured', true);

// Assert meta exists with specific value
expect($post)->toHaveMeta('price', '29.99');

// Assert meta key exists (any value)
expect($post)->toHaveMetaKey('price');
expect($post)->toHaveMetaKey('featured');

// Negation
expect($post)->not->toHaveMetaKey('nonexistent');
```

### Term Assertions

```php
use function PestWP\createPost;
use function PestWP\createTerm;

$post = createPost();
$termId = createTerm('News', 'category');
wp_set_post_terms($post->ID, [$termId], 'category');

// Assert post has term
expect($post)->toHaveTerm('News', 'category');
expect($post)->toHaveTerm($termId, 'category');

// Negation
expect($post)->not->toHaveTerm('Sports', 'category');
```

## User Expectations

### Role Assertions

```php
use function PestWP\createUser;

$admin = createUser('administrator');

expect($admin)->toHaveRole('administrator');
expect($admin)->not->toHaveRole('subscriber');
```

### Capability Assertions

```php
$editor = createUser('editor');

// Assert user has capability
expect($editor)->toHaveCapability('edit_posts');
expect($editor)->toHaveCapability('publish_posts');
expect($editor)->not->toHaveCapability('manage_options');

// Alias: can()
expect($editor)->can('edit_posts');
expect($editor)->can('delete_posts');
```

### User Meta Assertions

```php
$user = createUser();
update_user_meta($user->ID, 'phone', '555-1234');

// Assert user meta
expect($user)->toHaveUserMeta('phone', '555-1234');
expect($user)->toHaveMeta('phone', '555-1234'); // Also works
expect($user)->toHaveMetaKey('phone');
```

## Error Expectations

### WP_Error Assertions

```php
$error = new WP_Error('invalid_email', 'The email address is invalid.');

// Assert is WP_Error
expect($error)->toBeWPError();

// Assert error code
expect($error)->toHaveErrorCode('invalid_email');

// Negation
expect('not an error')->not->toBeWPError();
```

## Hook Expectations

### Action Assertions

```php
add_action('init', 'my_init_function', 15);

expect('init')->toHaveAction('my_init_function', 15);
expect('init')->not->toHaveAction('nonexistent_function');

// Default priority is 10
add_action('wp_head', 'my_head_function');
expect('wp_head')->toHaveAction('my_head_function', 10);
```

### Filter Assertions

```php
add_filter('the_content', 'my_content_filter', 20);

expect('the_content')->toHaveFilter('my_content_filter', 20);
expect('the_title')->not->toHaveFilter('my_content_filter');
```

## Post Type & Taxonomy Expectations

### Post Type Assertions

```php
// Assert post type is registered
expect('post')->toBeRegisteredPostType();
expect('page')->toBeRegisteredPostType();
expect('product')->toBeRegisteredPostType(); // Custom post type

// Assert post type supports features
expect('post')->toSupportFeature('title');
expect('post')->toSupportFeature('editor');
expect('post')->toSupportFeature('thumbnail');
expect('post')->not->toSupportFeature('comments'); // If disabled
```

### Taxonomy Assertions

```php
// Assert taxonomy is registered
expect('category')->toBeRegisteredTaxonomy();
expect('post_tag')->toBeRegisteredTaxonomy();
expect('product_cat')->toBeRegisteredTaxonomy(); // WooCommerce
```

## Option & Transient Expectations

### Option Assertions

```php
update_option('my_setting', 'enabled');

// Assert option exists
expect('my_setting')->toHaveOption();

// Assert option has specific value
expect('my_setting')->toHaveOption('enabled');

// Negation
expect('nonexistent_option')->not->toHaveOption();
```

### Transient Assertions

```php
set_transient('my_cache', ['data' => 'value'], HOUR_IN_SECONDS);

// Assert transient exists
expect('my_cache')->toHaveTransient();

// Assert transient has specific value
expect('my_cache')->toHaveTransient(['data' => 'value']);
```

## Shortcode Expectations

```php
add_shortcode('my_shortcode', function () {
    return 'Hello';
});

expect('my_shortcode')->toBeRegisteredShortcode();
expect('nonexistent')->not->toBeRegisteredShortcode();
```

## REST API Expectations

### Response Assertions

```php
use function PestWP\Functions\rest;

$response = rest()->get('/wp/v2/posts');

// Status assertions
expect($response)->toHaveStatus(200);
expect($response)->toBeSuccessful();
expect($response)->not->toBeError();

// Data assertions
expect($response)->toHaveResponseData('id');
expect($response)->toHaveResponseData('title', ['rendered' => 'Hello']);

// Header assertions
expect($response)->toHaveHeader('Content-Type');
expect($response)->toHaveHeader('Content-Type', 'application/json');

// Collection assertions
expect($response)->toHaveCount(10);
```

### Route Assertions

```php
// Assert REST route exists
expect('/wp/v2/posts')->toBeRegisteredRestRoute();
expect('/wp/v2/posts')->toBeRegisteredRestRoute('GET');
expect('/my-plugin/v1/items')->toBeRegisteredRestRoute('POST');
```

### Error Response Assertions

```php
$response = rest()->get('/wp/v2/posts/99999');

expect($response)->toBeError();
expect($response)->toHaveStatus(404);
expect($response)->toHaveErrorCode('rest_post_invalid_id');
expect($response)->toHaveErrorMessage('Invalid post ID.');
```

## AJAX Expectations

### Response Assertions

```php
use function PestWP\Functions\ajax;

$response = ajax('my_action', ['data' => 'value']);

expect($response)->toBeAjaxSuccess();
expect($response)->toHaveStatus(200);
expect($response)->toHaveResponseData('message');
```

### Action Assertions

```php
// Assert AJAX action is registered
expect('my_action')->toBeRegisteredAjaxAction();
expect('my_action')->toBeRegisteredAjaxAction(admin: true, nopriv: false);
```

## Mocking Expectations

### Function Mock Assertions

```php
use function PestWP\Functions\mockFunction;

$mock = mockFunction('wp_mail')->andReturn(true);

wp_mail('test@example.com', 'Subject', 'Message');

expect($mock)->toHaveBeenCalled();
expect($mock)->toHaveBeenCalledTimes(1);
expect($mock)->toHaveBeenCalledWith(['test@example.com', 'Subject', 'Message']);
```

### HTTP Mock Assertions

```php
use function PestWP\Functions\mockHTTP;

$mock = mockHTTP()
    ->whenUrl('https://api.example.com/users')
    ->andReturn(['users' => []]);

wp_remote_get('https://api.example.com/users');

expect($mock)->toHaveRequested('https://api.example.com/users');
expect($mock)->toHaveRequestCount(1);
```

## Snapshot Expectations

```php
// Match against stored snapshot
expect($html)->toMatchSnapshot();
expect($html)->toMatchSnapshot('custom-name');

// JSON snapshot
expect($data)->toMatchJsonSnapshot();

// HTML snapshot (normalized)
expect($renderedHtml)->toMatchHtmlSnapshot();
```

## Time Expectations

```php
use function PestWP\Functions\freezeTime;

freezeTime('2024-01-15 10:00:00');

$createdAt = time();
sleep(0); // Time is frozen
$later = time();

expect($createdAt)->toBe($later); // Same timestamp

// Relative assertions
expect($createdAt)->toBeBefore(strtotime('2024-01-16'));
expect($createdAt)->toBeAfter(strtotime('2024-01-14'));
```

## Chaining Expectations

All expectations return `$this`, allowing chaining:

```php
$post = createPost([
    'post_status' => 'publish',
    'meta_input' => ['featured' => true],
]);

expect($post)
    ->toBeInstanceOf(WP_Post::class)
    ->toBePublished()
    ->toHaveMetaKey('featured')
    ->toHaveMeta('featured', true);
```

## Custom Expectations

Add your own expectations in `tests/Pest.php`:

```php
expect()->extend('toBeValidProduct', function () {
    expect($this->value)
        ->toBeInstanceOf(WP_Post::class)
        ->and($this->value->post_type)->toBe('product')
        ->and(get_post_meta($this->value->ID, 'price', true))->not->toBeEmpty();
    
    return $this;
});

// Usage
expect($product)->toBeValidProduct();
```

## Next Steps

- [Factories](factories.md) - Create test data
- [Mocking](mocking.md) - Mock functions and hooks
- [REST API Testing](rest-api-testing.md) - Test REST endpoints
