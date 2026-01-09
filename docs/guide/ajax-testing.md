# AJAX Testing

PestWP provides helpers for testing WordPress admin-ajax.php handlers.

## Overview

WordPress uses admin-ajax.php for AJAX requests. PestWP lets you test these handlers without making actual HTTP requests.

## Basic Usage

### The ajax() Helper

```php
use function PestWP\Functions\ajax;

// Execute an AJAX action
$response = ajax('my_action', ['param' => 'value']);

// Using the client
$response = ajax()
    ->action('my_action', ['param' => 'value']);
```

### Response Object

```php
$response = ajax('my_action');

// Check success/error
$response->isSuccess();  // true/false
$response->isError();    // true/false

// Get data
$response->data();       // Full response data
$response->get('key');   // Specific key
$response->has('key');   // Check if key exists

// Get status
$response->statusCode(); // HTTP status code
```

## Authentication

### Admin Users (Logged In)

```php
use function PestWP\createUser;
use function PestWP\Functions\ajax;

$admin = createUser('administrator');

$response = ajax()
    ->as($admin)
    ->action('admin_only_action', ['data' => 'value']);

// Shorthand
$response = ajaxAdmin('admin_only_action', ['data' => 'value']);
```

### No-Priv (Not Logged In)

```php
// Test as unauthenticated user
$response = ajax()
    ->nopriv()
    ->action('public_action', ['data' => 'value']);

// Shorthand
$response = ajaxNopriv('public_action', ['data' => 'value']);
```

### With Nonce

```php
$response = ajax()
    ->withNonce('my_nonce_action')
    ->action('my_action', ['data' => 'value']);
```

## Response Assertions

### Success/Error

```php
// Check AJAX success
expect($response)->toBeAjaxSuccess();

// Check AJAX error
expect($response)->toBeAjaxError();

// Generic assertions also work
expect($response)->toBeSuccessful();
expect($response)->toBeError();
```

### Status Code

```php
expect($response)->toHaveStatus(200);
expect($response)->toHaveStatus(403);
```

### Response Data

```php
expect($response)->toHaveResponseData('message');
expect($response)->toHaveResponseData('message', 'Success!');
```

### Error Message

```php
expect($response)->toHaveErrorMessage('Invalid request');
```

## Testing AJAX Handlers

### Register and Test

```php
// In your plugin
add_action('wp_ajax_my_action', function () {
    if (!isset($_POST['name'])) {
        wp_send_json_error(['message' => 'Name required']);
    }
    
    $name = sanitize_text_field($_POST['name']);
    wp_send_json_success(['greeting' => "Hello, {$name}!"]);
});

// In your test
it('handles my_action', function () {
    $admin = createUser('administrator');
    
    $response = ajax()
        ->as($admin)
        ->action('my_action', ['name' => 'World']);
    
    expect($response)->toBeAjaxSuccess();
    expect($response)->toHaveResponseData('greeting', 'Hello, World!');
});

it('requires name parameter', function () {
    $admin = createUser('administrator');
    
    $response = ajax()
        ->as($admin)
        ->action('my_action', []);
    
    expect($response)->toBeAjaxError();
    expect($response)->toHaveErrorMessage('Name required');
});
```

### Test Registration

```php
use function PestWP\Functions\hasAjaxAction;
use function PestWP\Functions\registeredAjaxActions;

it('registers AJAX actions', function () {
    // Check if action is registered
    expect(hasAjaxAction('my_action'))->toBeTrue();
    
    // Check specific contexts
    expect(hasAjaxAction('my_action', admin: true, nopriv: false))->toBeTrue();
    
    // Using expectation
    expect('my_action')->toBeRegisteredAjaxAction();
    expect('my_action')->toBeRegisteredAjaxAction(admin: true, nopriv: true);
});

it('lists all registered actions', function () {
    $actions = registeredAjaxActions();
    
    // Returns ['admin' => [...], 'nopriv' => [...]]
    expect($actions['admin'])->toContain('my_action');
});
```

## Testing Authentication

### Admin-Only Actions

```php
// Register admin-only action
add_action('wp_ajax_admin_only', function () {
    wp_send_json_success(['secret' => 'data']);
});

it('allows admin access', function () {
    $admin = createUser('administrator');
    
    $response = ajax()
        ->as($admin)
        ->action('admin_only');
    
    expect($response)->toBeAjaxSuccess();
});

it('denies unauthenticated access', function () {
    $response = ajax()
        ->nopriv()
        ->action('admin_only');
    
    // Action not registered for nopriv, returns 0 or error
    expect($response)->toBeAjaxError();
});
```

### Public Actions

```php
// Register public action (both logged-in and guest)
add_action('wp_ajax_public_action', 'handle_public_action');
add_action('wp_ajax_nopriv_public_action', 'handle_public_action');

function handle_public_action() {
    wp_send_json_success(['public' => 'data']);
}

it('allows authenticated access', function () {
    $user = createUser('subscriber');
    
    $response = ajax()
        ->as($user)
        ->action('public_action');
    
    expect($response)->toBeAjaxSuccess();
});

it('allows guest access', function () {
    $response = ajax()
        ->nopriv()
        ->action('public_action');
    
    expect($response)->toBeAjaxSuccess();
});
```

## Testing Nonce Verification

```php
// Handler that verifies nonce
add_action('wp_ajax_secure_action', function () {
    if (!check_ajax_referer('secure_action_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    wp_send_json_success(['secure' => 'data']);
});

it('accepts valid nonce', function () {
    $admin = createUser('administrator');
    
    $response = ajax()
        ->as($admin)
        ->withNonce('secure_action_nonce')
        ->action('secure_action');
    
    expect($response)->toBeAjaxSuccess();
});

it('rejects invalid nonce', function () {
    $admin = createUser('administrator');
    
    $response = ajax()
        ->as($admin)
        ->action('secure_action', ['nonce' => 'invalid']);
    
    expect($response)->toBeAjaxError();
    expect($response)->toHaveErrorMessage('Invalid nonce');
});
```

## Testing Capabilities

```php
add_action('wp_ajax_editor_action', function () {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    wp_send_json_success(['allowed' => true]);
});

it('allows editors', function () {
    $editor = createUser('editor');
    
    $response = ajax()
        ->as($editor)
        ->action('editor_action');
    
    expect($response)->toBeAjaxSuccess();
});

it('denies subscribers', function () {
    $subscriber = createUser('subscriber');
    
    $response = ajax()
        ->as($subscriber)
        ->action('editor_action');
    
    expect($response)->toBeAjaxError();
    expect($response)->toHaveErrorMessage('Permission denied');
});
```

## Testing File Uploads

```php
add_action('wp_ajax_upload_file', function () {
    if (empty($_FILES['file'])) {
        wp_send_json_error(['message' => 'No file uploaded']);
    }
    
    $file = $_FILES['file'];
    wp_send_json_success([
        'name' => $file['name'],
        'size' => $file['size'],
    ]);
});

it('handles file uploads', function () {
    $admin = createUser('administrator');
    
    // Create a temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tempFile, 'test content');
    
    $response = ajax()
        ->as($admin)
        ->withFile('file', [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $tempFile,
            'error' => 0,
            'size' => filesize($tempFile),
        ])
        ->action('upload_file');
    
    expect($response)->toBeAjaxSuccess();
    expect($response)->toHaveResponseData('name', 'test.txt');
    
    unlink($tempFile);
});
```

## Testing Complex Responses

```php
add_action('wp_ajax_get_posts', function () {
    $posts = get_posts(['numberposts' => 5]);
    
    $data = array_map(function ($post) {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
        ];
    }, $posts);
    
    wp_send_json_success(['posts' => $data, 'total' => count($posts)]);
});

it('returns posts data', function () {
    $admin = createUser('administrator');
    
    for ($i = 1; $i <= 3; $i++) {
        createPost(['post_title' => "Post {$i}"]);
    }
    
    $response = ajax()
        ->as($admin)
        ->action('get_posts');
    
    expect($response)->toBeAjaxSuccess();
    expect($response->get('total'))->toBe(3);
    expect($response->get('posts'))->toHaveCount(3);
});
```

## Common Patterns

### BeforeEach Setup

```php
beforeEach(function () {
    $this->admin = createUser('administrator');
    
    // Register actions for testing
    add_action('wp_ajax_test_action', function () {
        wp_send_json_success(['message' => 'OK']);
    });
});

it('test one', function () {
    $response = ajax()
        ->as($this->admin)
        ->action('test_action');
    
    expect($response)->toBeAjaxSuccess();
});

it('test two', function () {
    $response = ajax()
        ->as($this->admin)
        ->action('test_action');
    
    expect($response)->toBeAjaxSuccess();
});
```

### Error Handling

```php
it('handles exceptions gracefully', function () {
    add_action('wp_ajax_error_action', function () {
        throw new Exception('Something went wrong');
    });
    
    $admin = createUser('administrator');
    
    $response = ajax()
        ->as($admin)
        ->action('error_action');
    
    expect($response)->toBeAjaxError();
});
```

## Next Steps

- [REST API Testing](rest-api-testing.md) - Test REST endpoints
- [Authentication](authentication.md) - User authentication
- [Mocking](mocking.md) - Mock external services
