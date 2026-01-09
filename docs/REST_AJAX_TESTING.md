# REST API & AJAX Testing Guide

> **Version**: 0.4.0 (Phase 3)  
> **Requires**: PestWP with WordPress loaded

This guide covers testing WordPress REST API endpoints and AJAX handlers using PestWP's fluent testing utilities.

---

## Table of Contents

1. [REST API Testing](#rest-api-testing)
   - [Basic Usage](#basic-rest-usage)
   - [Authenticated Requests](#authenticated-rest-requests)
   - [Request Options](#rest-request-options)
   - [Response Handling](#rest-response-handling)
   - [Route Checking](#checking-rest-routes)
2. [AJAX Testing](#ajax-testing)
   - [Basic Usage](#basic-ajax-usage)
   - [Authenticated Requests](#authenticated-ajax-requests)
   - [Nonce Handling](#ajax-nonce-handling)
   - [Response Handling](#ajax-response-handling)
3. [Custom Expectations](#custom-expectations)
4. [Nonce Utilities](#nonce-utilities)
5. [Examples](#complete-examples)

---

## REST API Testing

PestWP provides a fluent `rest()` helper for testing WordPress REST API endpoints without making actual HTTP requests.

### Basic REST Usage

```php
use function PestWP\Functions\rest;

test('can fetch posts via REST API', function () {
    // Create some test posts
    createPost(['post_title' => 'Test Post 1']);
    createPost(['post_title' => 'Test Post 2']);

    // Make a GET request
    $response = rest()->get('/wp/v2/posts');

    expect($response)->toBeSuccessful();
    expect($response->count())->toBe(2);
});

test('can create a post via REST API', function () {
    $admin = createUser(['role' => 'administrator']);

    $response = rest()
        ->as($admin)
        ->post('/wp/v2/posts', [
            'title' => 'New Post',
            'content' => 'Post content here',
            'status' => 'publish',
        ]);

    expect($response)->toHaveStatus(201);
    expect($response->get('title.rendered'))->toBe('New Post');
});
```

### Authenticated REST Requests

Use `as()` or `actingAs()` to make authenticated requests:

```php
test('admin can delete posts', function () {
    $admin = createUser(['role' => 'administrator']);
    $post = createPost();

    $response = rest()
        ->as($admin)
        ->delete("/wp/v2/posts/{$post->ID}", ['force' => true]);

    expect($response)->toBeSuccessful();
});

test('subscriber cannot delete posts', function () {
    $subscriber = createUser(['role' => 'subscriber']);
    $post = createPost();

    $response = rest()
        ->as($subscriber)
        ->delete("/wp/v2/posts/{$post->ID}");

    expect($response)->toHaveStatus(403);
    expect($response)->toHaveErrorCode('rest_cannot_delete');
});
```

### REST Request Options

#### Custom Headers

```php
$response = rest()
    ->withHeader('X-Custom-Header', 'value')
    ->withHeaders([
        'Accept-Language' => 'en-US',
        'X-API-Key' => 'secret',
    ])
    ->get('/my-plugin/v1/endpoint');
```

#### Query Parameters

```php
$response = rest()
    ->withQuery(['per_page' => 5, 'orderby' => 'date'])
    ->get('/wp/v2/posts');
```

#### Nonce Authentication

```php
// Auto-generate nonce for wp_rest action
$response = rest()
    ->as($user)
    ->withNonce()
    ->post('/my-plugin/v1/secure-endpoint', $data);

// Use specific nonce value
$response = rest()
    ->withNonceValue($myNonce)
    ->post('/my-plugin/v1/endpoint', $data);
```

### REST Response Handling

The `RestResponse` class provides a rich API for working with responses:

```php
$response = rest()->get('/wp/v2/posts/1');

// Status checks
$response->status();           // 200
$response->isSuccessful();     // true (2xx)
$response->isError();          // true (4xx or 5xx)
$response->isClientError();    // true (4xx)
$response->isServerError();    // true (5xx)
$response->hasStatus(200);     // true

// Data access
$response->data();             // Full response data array
$response->get('title');       // Top-level key
$response->get('author.name'); // Nested with dot notation
$response->has('content');     // Check if key exists

// Headers
$response->headers();          // All headers
$response->header('X-Total');  // Specific header

// Errors (WP_Error format)
$response->errorCode();        // 'rest_invalid_param'
$response->errorMessage();     // 'Invalid parameter.'

// Collections
$response->count();            // Number of items
$response->first();            // First item
$response->items();            // All items as array

// JSON
$response->json();             // JSON string

// Array access
$response['id'];               // Access like array
isset($response['title']);     // Check existence
```

### Checking REST Routes

```php
use function PestWP\Functions\rest;
use function PestWP\Functions\restRouteExists;
use function PestWP\Functions\restRoutes;
use function PestWP\Functions\restRoutesForNamespace;

test('plugin registers REST routes', function () {
    // Check if route exists
    expect(restRouteExists('/my-plugin/v1/items'))->toBeTrue();

    // Check with specific method
    expect(restRouteExists('/my-plugin/v1/items', 'POST'))->toBeTrue();

    // Using the client
    expect(rest()->routeExists('/wp/v2/posts'))->toBeTrue();

    // Get all routes
    $routes = restRoutes();

    // Get routes for a namespace
    $myRoutes = restRoutesForNamespace('my-plugin/v1');
});
```

---

## AJAX Testing

PestWP provides the `ajax()` helper for testing WordPress admin-ajax.php handlers.

### Basic AJAX Usage

```php
use function PestWP\Functions\ajax;

// Register an AJAX handler in your plugin
add_action('wp_ajax_my_action', function () {
    wp_send_json_success(['message' => 'Hello!']);
});

test('AJAX handler returns success', function () {
    $response = ajax('my_action');

    expect($response)->toBeAjaxSuccess();
    expect($response->get('message'))->toBe('Hello!');
});

// Or use the fluent interface
test('AJAX handler with data', function () {
    $response = ajax()->action('my_action', ['param' => 'value']);

    expect($response->isSuccess())->toBeTrue();
});
```

### Authenticated AJAX Requests

```php
test('admin can access protected action', function () {
    $admin = createUser(['role' => 'administrator']);

    $response = ajax()
        ->as($admin)
        ->action('admin_only_action');

    expect($response)->toBeAjaxSuccess();
});

test('nopriv action works for logged-out users', function () {
    // Test nopriv handler (wp_ajax_nopriv_*)
    $response = ajax()
        ->nopriv()
        ->action('public_action');

    expect($response)->toBeAjaxSuccess();
});
```

### AJAX Nonce Handling

```php
test('action requires valid nonce', function () {
    $admin = createUser(['role' => 'administrator']);

    // With auto-generated nonce
    $response = ajax()
        ->as($admin)
        ->withNonce('my_action_nonce')
        ->action('my_secure_action', ['data' => 'value']);

    expect($response)->toBeAjaxSuccess();
});

test('action fails with invalid nonce', function () {
    $admin = createUser(['role' => 'administrator']);

    $response = ajax()
        ->as($admin)
        ->withNonceValue('invalid_nonce')
        ->action('my_secure_action');

    expect($response)->toBeAjaxError();
});
```

### AJAX Response Handling

The `AjaxResponse` class handles various WordPress AJAX response formats:

```php
$response = ajax('my_action', ['key' => 'value']);

// Success checks
$response->isSuccess();        // true for wp_send_json_success
$response->isError();          // true for wp_send_json_error

// Data access
$response->data();             // Response data
$response->get('key');         // Get value
$response->get('user.name');   // Dot notation
$response->has('key');         // Check existence

// Error info
$response->errorMessage();     // Error message if present

// Raw output
$response->rawOutput();        // Original output string
$response->json();             // Data as JSON

// Array access
$response['key'];              // Access like array
```

### Checking AJAX Actions

```php
use function PestWP\Functions\hasAjaxAction;
use function PestWP\Functions\registeredAjaxActions;

test('plugin registers AJAX handlers', function () {
    // Check if action exists
    expect(hasAjaxAction('my_action'))->toBeTrue();

    // Check specific contexts
    expect(hasAjaxAction('my_action', admin: true, nopriv: false))->toBeTrue();

    // Get all registered actions
    $actions = registeredAjaxActions();
    expect($actions['admin'])->toContain('my_action');
});

// Using the client
test('check action registration via client', function () {
    expect(ajax()->hasAdminAction('my_action'))->toBeTrue();
    expect(ajax()->hasNoprivAction('my_action'))->toBeFalse();
});
```

---

## Custom Expectations

PestWP adds several expectations for REST and AJAX testing:

### Response Expectations

```php
// Status and success
expect($response)->toHaveStatus(200);
expect($response)->toBeSuccessful();
expect($response)->toBeError();

// Response data
expect($response)->toHaveResponseData('id');
expect($response)->toHaveResponseData('title', 'Expected Title');

// Error handling
expect($response)->toHaveErrorCode('rest_forbidden');
expect($response)->toHaveErrorMessage('Sorry, you cannot do that.');

// Headers (REST only)
expect($response)->toHaveHeader('X-WP-Total');
expect($response)->toHaveHeader('Content-Type', 'application/json');

// Collection count
expect($response)->toHaveCount(10);
```

### Route and Action Expectations

```php
// REST routes
expect('/wp/v2/posts')->toBeRegisteredRestRoute();
expect('/wp/v2/posts')->toBeRegisteredRestRoute('POST');

// AJAX actions
expect('my_action')->toBeRegisteredAjaxAction();
expect('my_action')->toBeRegisteredAjaxAction(admin: true, nopriv: false);
```

### AJAX-Specific Expectations

```php
expect($response)->toBeAjaxSuccess();
expect($response)->toBeAjaxError();
```

---

## Nonce Utilities

PestWP provides helper functions for working with WordPress nonces:

```php
use function PestWP\Functions\createNonce;
use function PestWP\Functions\verifyNonce;
use function PestWP\Functions\createRestNonce;
use function PestWP\Functions\createAjaxReferer;
use function PestWP\Functions\createNonceUrl;

// Create a nonce
$nonce = createNonce('my_action');

// Verify a nonce
$valid = verifyNonce($nonce, 'my_action'); // Returns 1, 2, or false

// Create REST API nonce
$restNonce = createRestNonce(); // wp_rest action

// Create AJAX referer data
$referer = createAjaxReferer('my_action');
// Returns: ['nonce' => '...', 'referer' => 'http://...']

// Create nonce URL
$url = createNonceUrl('http://example.com/action', 'my_action');
// Returns: 'http://example.com/action?_wpnonce=...'
```

---

## Complete Examples

### Testing a Custom REST Endpoint

```php
// In your plugin: register-routes.php
add_action('rest_api_init', function () {
    register_rest_route('my-plugin/v1', '/items', [
        'methods' => 'GET',
        'callback' => 'get_items',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('my-plugin/v1', '/items', [
        'methods' => 'POST',
        'callback' => 'create_item',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
});

// In your test file
describe('Items REST API', function () {

    test('anyone can list items', function () {
        $response = rest()->get('/my-plugin/v1/items');

        expect($response)->toBeSuccessful();
    });

    test('unauthenticated users cannot create items', function () {
        $response = rest()->post('/my-plugin/v1/items', [
            'name' => 'New Item',
        ]);

        expect($response)->toHaveStatus(401);
    });

    test('editors can create items', function () {
        $editor = createUser(['role' => 'editor']);

        $response = rest()
            ->as($editor)
            ->post('/my-plugin/v1/items', [
                'name' => 'New Item',
            ]);

        expect($response)->toHaveStatus(201);
        expect($response->get('name'))->toBe('New Item');
    });

});
```

### Testing AJAX Form Submission

```php
// In your plugin
add_action('wp_ajax_submit_contact_form', function () {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'contact_form')) {
        wp_send_json_error(['message' => 'Invalid security token']);
    }

    // Process form
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');

    if (empty($name) || empty($email)) {
        wp_send_json_error(['message' => 'All fields are required']);
    }

    // Save submission...
    wp_send_json_success(['message' => 'Form submitted successfully']);
});

// In your test file
describe('Contact Form AJAX', function () {

    test('rejects requests without nonce', function () {
        $response = ajax('submit_contact_form', [
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        expect($response)->toBeAjaxError();
        expect($response)->toHaveErrorMessage('Invalid security token');
    });

    test('validates required fields', function () {
        $response = ajax()
            ->withNonce('contact_form')
            ->action('submit_contact_form', [
                'name' => '',
                'email' => '',
            ]);

        expect($response)->toBeAjaxError();
        expect($response)->toHaveErrorMessage('All fields are required');
    });

    test('accepts valid submission with nonce', function () {
        $response = ajax()
            ->withNonce('contact_form')
            ->action('submit_contact_form', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        expect($response)->toBeAjaxSuccess();
        expect($response->get('message'))->toBe('Form submitted successfully');
    });

});
```

---

## Quick Reference

### REST API Helpers

| Function | Description |
|----------|-------------|
| `rest()` | Create REST client |
| `rest()->get($route, $params)` | GET request |
| `rest()->post($route, $data)` | POST request |
| `rest()->put($route, $data)` | PUT request |
| `rest()->patch($route, $data)` | PATCH request |
| `rest()->delete($route, $params)` | DELETE request |
| `rest()->as($user)` | Authenticate as user |
| `rest()->withNonce()` | Add wp_rest nonce |
| `rest()->withHeader($name, $value)` | Add header |
| `rest()->withQuery($params)` | Add query params |
| `restRouteExists($route, $method)` | Check route exists |
| `restRoutes()` | Get all routes |
| `restRoutesForNamespace($ns)` | Get namespace routes |

### AJAX Helpers

| Function | Description |
|----------|-------------|
| `ajax($action, $data)` | Execute AJAX action |
| `ajax()->action($action, $data)` | Execute action |
| `ajax()->as($user)` | Authenticate as user |
| `ajax()->nopriv()` | Test nopriv handler |
| `ajax()->withNonce($action)` | Add auto-generated nonce |
| `ajax()->withNonceValue($value)` | Add specific nonce |
| `hasAjaxAction($action)` | Check action registered |
| `registeredAjaxActions()` | Get all actions |

### Nonce Helpers

| Function | Description |
|----------|-------------|
| `createNonce($action)` | Create nonce |
| `verifyNonce($nonce, $action)` | Verify nonce |
| `createRestNonce()` | Create REST nonce |
| `createAjaxReferer($action)` | Create AJAX referer |
| `createNonceUrl($url, $action)` | Add nonce to URL |

---

*This documentation is part of PestWP v0.4.0 - Phase 3: REST API & AJAX Testing*
