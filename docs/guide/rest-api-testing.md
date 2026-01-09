# REST API Testing

PestWP provides a fluent API for testing WordPress REST API endpoints.

## Overview

The REST testing helpers let you make requests to REST endpoints and assert on responses without making actual HTTP requests.

## Basic Usage

### The rest() Helper

```php
use function PestWP\Functions\rest;

// Make a GET request
$response = rest()->get('/wp/v2/posts');

// Make a POST request
$response = rest()->post('/wp/v2/posts', [
    'title' => 'New Post',
    'status' => 'publish',
]);

// Make a PUT request
$response = rest()->put('/wp/v2/posts/1', [
    'title' => 'Updated Title',
]);

// Make a PATCH request
$response = rest()->patch('/wp/v2/posts/1', [
    'title' => 'Patched Title',
]);

// Make a DELETE request
$response = rest()->delete('/wp/v2/posts/1');
```

### Shorthand Functions

```php
use function PestWP\Functions\restGet;
use function PestWP\Functions\restPost;
use function PestWP\Functions\restPut;
use function PestWP\Functions\restPatch;
use function PestWP\Functions\restDelete;

$response = restGet('/wp/v2/posts');
$response = restPost('/wp/v2/posts', ['title' => 'New']);
$response = restPut('/wp/v2/posts/1', ['title' => 'Updated']);
$response = restPatch('/wp/v2/posts/1', ['title' => 'Patched']);
$response = restDelete('/wp/v2/posts/1');
```

## Authentication

### As a User

```php
use function PestWP\createUser;
use function PestWP\Functions\rest;

$admin = createUser('administrator');

// Authenticate as user
$response = rest()
    ->as($admin)
    ->post('/wp/v2/posts', [
        'title' => 'Admin Post',
        'status' => 'publish',
    ]);

expect($response)->toBeSuccessful();
```

### With Nonce

```php
$response = rest()
    ->withNonce()
    ->post('/wp/v2/posts', [
        'title' => 'Nonced Request',
    ]);
```

### With Custom Headers

```php
$response = rest()
    ->withHeader('X-Custom-Header', 'value')
    ->withHeader('X-Another-Header', 'another')
    ->get('/my-plugin/v1/endpoint');
```

## Response Assertions

### Status Assertions

```php
// Check specific status
expect($response)->toHaveStatus(200);
expect($response)->toHaveStatus(201);
expect($response)->toHaveStatus(404);

// Check success (2xx)
expect($response)->toBeSuccessful();

// Check error (4xx or 5xx)
expect($response)->toBeError();
```

### Data Assertions

```php
// Check response has key
expect($response)->toHaveResponseData('id');
expect($response)->toHaveResponseData('title');

// Check specific value
expect($response)->toHaveResponseData('status', 'publish');
expect($response)->toHaveResponseData('title', ['rendered' => 'My Post']);
```

### Error Assertions

```php
$response = rest()->get('/wp/v2/posts/99999');

expect($response)->toBeError();
expect($response)->toHaveStatus(404);
expect($response)->toHaveErrorCode('rest_post_invalid_id');
expect($response)->toHaveErrorMessage('Invalid post ID.');
```

### Header Assertions

```php
expect($response)->toHaveHeader('Content-Type');
expect($response)->toHaveHeader('Content-Type', 'application/json');
expect($response)->toHaveHeader('X-WP-Total');
```

### Collection Assertions

```php
$response = rest()->get('/wp/v2/posts', ['per_page' => 10]);

expect($response)->toHaveCount(10);
```

## Working with Response Data

### Get Data

```php
$response = rest()->get('/wp/v2/posts/1');

// Get all data
$data = $response->data();

// Get specific field
$title = $response->get('title');
$renderedTitle = $response->get('title.rendered');

// Check if key exists
if ($response->has('meta')) {
    $meta = $response->get('meta');
}
```

### Get Status

```php
$status = $response->status();

if ($response->isSuccessful()) {
    // 2xx status
}

if ($response->isError()) {
    // 4xx or 5xx status
}
```

### Get Headers

```php
$contentType = $response->header('Content-Type');
$total = $response->header('X-WP-Total');
$allHeaders = $response->headers();
```

## Testing Custom Endpoints

### Register and Test

```php
// In your plugin
add_action('rest_api_init', function () {
    register_rest_route('my-plugin/v1', '/items', [
        'methods' => 'GET',
        'callback' => function () {
            return ['items' => ['one', 'two', 'three']];
        },
        'permission_callback' => '__return_true',
    ]);
});

// In your test
it('returns items from custom endpoint', function () {
    $response = rest()->get('/my-plugin/v1/items');
    
    expect($response)->toBeSuccessful();
    expect($response)->toHaveResponseData('items');
    expect($response->get('items'))->toHaveCount(3);
});
```

### Test Route Registration

```php
use function PestWP\Functions\restRouteExists;
use function PestWP\Functions\restRoutes;
use function PestWP\Functions\restRoutesForNamespace;

it('registers custom routes', function () {
    // Check route exists
    expect(restRouteExists('/my-plugin/v1/items'))->toBeTrue();
    expect(restRouteExists('/my-plugin/v1/items', 'GET'))->toBeTrue();
    
    // Using expectation
    expect('/my-plugin/v1/items')->toBeRegisteredRestRoute();
    expect('/my-plugin/v1/items')->toBeRegisteredRestRoute('GET');
});

it('lists all routes', function () {
    $routes = restRoutes();
    
    expect($routes)->toHaveKey('/wp/v2/posts');
});

it('lists routes for namespace', function () {
    $routes = restRoutesForNamespace('wp/v2');
    
    expect($routes)->toHaveKey('/wp/v2/posts');
    expect($routes)->toHaveKey('/wp/v2/pages');
});
```

## Testing CRUD Operations

### Create (POST)

```php
it('creates a post via REST', function () {
    $admin = createUser('administrator');
    
    $response = rest()
        ->as($admin)
        ->post('/wp/v2/posts', [
            'title' => 'REST Created Post',
            'content' => 'Post content here',
            'status' => 'publish',
        ]);
    
    expect($response)->toHaveStatus(201);
    expect($response)->toHaveResponseData('id');
    expect($response->get('title.rendered'))->toBe('REST Created Post');
    
    // Verify in database
    $post = get_post($response->get('id'));
    expect($post)->toBePublished();
});
```

### Read (GET)

```php
it('retrieves posts via REST', function () {
    $post = createPost(['post_title' => 'Test Post']);
    
    $response = rest()->get("/wp/v2/posts/{$post->ID}");
    
    expect($response)->toBeSuccessful();
    expect($response->get('id'))->toBe($post->ID);
    expect($response->get('title.rendered'))->toBe('Test Post');
});

it('lists posts with pagination', function () {
    for ($i = 1; $i <= 15; $i++) {
        createPost(['post_title' => "Post {$i}"]);
    }
    
    $response = rest()->get('/wp/v2/posts', [
        'per_page' => 5,
        'page' => 2,
    ]);
    
    expect($response)->toHaveCount(5);
    expect($response)->toHaveHeader('X-WP-Total', '15');
    expect($response)->toHaveHeader('X-WP-TotalPages', '3');
});
```

### Update (PUT/PATCH)

```php
it('updates a post via REST', function () {
    $admin = createUser('administrator');
    $post = createPost(['post_title' => 'Original Title']);
    
    $response = rest()
        ->as($admin)
        ->put("/wp/v2/posts/{$post->ID}", [
            'title' => 'Updated Title',
        ]);
    
    expect($response)->toBeSuccessful();
    expect($response->get('title.rendered'))->toBe('Updated Title');
    
    // Verify in database
    $post = get_post($post->ID);
    expect($post->post_title)->toBe('Updated Title');
});
```

### Delete (DELETE)

```php
it('deletes a post via REST', function () {
    $admin = createUser('administrator');
    $post = createPost();
    
    $response = rest()
        ->as($admin)
        ->delete("/wp/v2/posts/{$post->ID}");
    
    expect($response)->toBeSuccessful();
    
    // Verify trashed
    $post = get_post($post->ID);
    expect($post)->toBeInTrash();
});

it('permanently deletes a post', function () {
    $admin = createUser('administrator');
    $post = createPost();
    
    $response = rest()
        ->as($admin)
        ->delete("/wp/v2/posts/{$post->ID}", ['force' => true]);
    
    expect($response)->toBeSuccessful();
    expect(get_post($post->ID))->toBeNull();
});
```

## Testing Permissions

```php
it('requires authentication to create posts', function () {
    $response = rest()->post('/wp/v2/posts', [
        'title' => 'Unauthenticated Post',
    ]);
    
    expect($response)->toBeError();
    expect($response)->toHaveStatus(401);
});

it('denies subscribers from creating posts', function () {
    $subscriber = createUser('subscriber');
    
    $response = rest()
        ->as($subscriber)
        ->post('/wp/v2/posts', [
            'title' => 'Subscriber Post',
        ]);
    
    expect($response)->toBeError();
    expect($response)->toHaveStatus(403);
});

it('allows authors to create posts', function () {
    $author = createUser('author');
    
    $response = rest()
        ->as($author)
        ->post('/wp/v2/posts', [
            'title' => 'Author Post',
            'status' => 'draft',
        ]);
    
    expect($response)->toBeSuccessful();
});
```

## Testing Query Parameters

```php
it('filters posts by status', function () {
    $admin = createUser('administrator');
    
    createPost(['post_status' => 'publish']);
    createPost(['post_status' => 'draft']);
    createPost(['post_status' => 'draft']);
    
    $response = rest()
        ->as($admin)
        ->get('/wp/v2/posts', ['status' => 'draft']);
    
    expect($response)->toHaveCount(2);
});

it('searches posts', function () {
    createPost(['post_title' => 'WordPress Guide']);
    createPost(['post_title' => 'PHP Tutorial']);
    createPost(['post_title' => 'WordPress Tips']);
    
    $response = rest()->get('/wp/v2/posts', [
        'search' => 'WordPress',
    ]);
    
    expect($response)->toHaveCount(2);
});

it('orders posts', function () {
    createPost(['post_title' => 'A Post', 'post_date' => '2024-01-01']);
    createPost(['post_title' => 'B Post', 'post_date' => '2024-01-02']);
    createPost(['post_title' => 'C Post', 'post_date' => '2024-01-03']);
    
    $response = rest()->get('/wp/v2/posts', [
        'orderby' => 'title',
        'order' => 'asc',
    ]);
    
    $titles = array_map(fn($p) => $p['title']['rendered'], $response->data());
    expect($titles)->toBe(['A Post', 'B Post', 'C Post']);
});
```

## Next Steps

- [AJAX Testing](ajax-testing.md) - Test admin-ajax handlers
- [Authentication](authentication.md) - User authentication
- [Mocking](mocking.md) - Mock external APIs
