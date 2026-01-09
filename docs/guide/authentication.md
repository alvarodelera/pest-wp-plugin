# Authentication

PestWP provides helper functions to simulate user authentication in tests.

## Overview

Testing user-dependent functionality requires simulating different user contexts. PestWP makes this easy with intuitive authentication helpers.

## Functions

### loginAs()

Log in as a specific user:

```php
use function PestWP\loginAs;
use function PestWP\createUser;

$user = createUser('editor');

// Login by user object
loginAs($user);

// Login by user ID
loginAs($user->ID);

// Returns the WP_User object
$loggedInUser = loginAs($user);
expect($loggedInUser->ID)->toBe($user->ID);
```

### logout()

Log out the current user:

```php
use function PestWP\logout;
use function PestWP\loginAs;
use function PestWP\createUser;

$user = createUser('administrator');
loginAs($user);

// Verify logged in
expect(is_user_logged_in())->toBeTrue();

// Log out
logout();

// Verify logged out
expect(is_user_logged_in())->toBeFalse();
```

### currentUser()

Get the currently authenticated user:

```php
use function PestWP\currentUser;
use function PestWP\loginAs;
use function PestWP\createUser;

$admin = createUser('administrator');
loginAs($admin);

$current = currentUser();
expect($current->ID)->toBe($admin->ID);
expect($current->user_login)->toBe($admin->user_login);
```

### isUserLoggedIn()

Check if a user is logged in:

```php
use function PestWP\isUserLoggedIn;
use function PestWP\loginAs;
use function PestWP\logout;
use function PestWP\createUser;

expect(isUserLoggedIn())->toBeFalse();

$user = createUser();
loginAs($user);

expect(isUserLoggedIn())->toBeTrue();

logout();

expect(isUserLoggedIn())->toBeFalse();
```

## Testing Capabilities

### Role-Based Access

```php
use function PestWP\createUser;
use function PestWP\loginAs;

it('allows administrators to manage options', function () {
    $admin = createUser('administrator');
    loginAs($admin);
    
    expect(current_user_can('manage_options'))->toBeTrue();
});

it('prevents subscribers from managing options', function () {
    $subscriber = createUser('subscriber');
    loginAs($subscriber);
    
    expect(current_user_can('manage_options'))->toBeFalse();
});
```

### Testing User Capabilities

```php
it('tests editor capabilities', function () {
    $editor = createUser('editor');
    
    expect($editor)->toHaveRole('editor');
    expect($editor)->toHaveCapability('edit_posts');
    expect($editor)->toHaveCapability('edit_others_posts');
    expect($editor)->toHaveCapability('publish_posts');
    expect($editor)->not->toHaveCapability('manage_options');
    expect($editor)->not->toHaveCapability('install_plugins');
});

it('tests author capabilities', function () {
    $author = createUser('author');
    
    expect($author)->can('edit_posts');
    expect($author)->can('upload_files');
    expect($author)->not->can('edit_others_posts');
});
```

### Post-Specific Capabilities

```php
use function PestWP\createPost;
use function PestWP\createUser;
use function PestWP\loginAs;

it('allows authors to edit their own posts', function () {
    $author = createUser('author');
    $post = createPost(['post_author' => $author->ID]);
    
    loginAs($author);
    
    expect(current_user_can('edit_post', $post->ID))->toBeTrue();
    expect(current_user_can('delete_post', $post->ID))->toBeTrue();
});

it('prevents authors from editing others posts', function () {
    $author = createUser('author');
    $otherAuthor = createUser('author');
    $post = createPost(['post_author' => $otherAuthor->ID]);
    
    loginAs($author);
    
    expect(current_user_can('edit_post', $post->ID))->toBeFalse();
});
```

## Common Patterns

### Testing Admin Functions

```php
it('updates plugin settings as admin', function () {
    $admin = createUser('administrator');
    loginAs($admin);
    
    // Simulate updating settings
    update_option('my_plugin_setting', 'new_value');
    
    expect('my_plugin_setting')->toHaveOption('new_value');
});

it('denies settings update for non-admins', function () {
    $editor = createUser('editor');
    loginAs($editor);
    
    // Check capability before update
    expect(current_user_can('manage_options'))->toBeFalse();
});
```

### Testing User Registration

```php
it('creates users with correct defaults', function () {
    $user = createUser('subscriber', [
        'user_login' => 'newuser',
        'user_email' => 'new@example.com',
    ]);
    
    expect($user->user_login)->toBe('newuser');
    expect($user->user_email)->toBe('new@example.com');
    expect($user)->toHaveRole('subscriber');
});
```

### Testing Content Ownership

```php
it('assigns posts to correct author', function () {
    $author = createUser('author');
    $post = createPost([
        'post_author' => $author->ID,
        'post_title' => 'My Article',
    ]);
    
    expect((int) $post->post_author)->toBe($author->ID);
    
    // Query by author
    $authorPosts = get_posts([
        'author' => $author->ID,
        'post_type' => 'post',
    ]);
    
    expect($authorPosts)->toHaveCount(1);
    expect($authorPosts[0]->ID)->toBe($post->ID);
});
```

## BeforeEach Pattern

Set up authentication for multiple tests:

```php
beforeEach(function () {
    $this->admin = createUser('administrator');
    $this->editor = createUser('editor');
    $this->subscriber = createUser('subscriber');
});

it('admin can do everything', function () {
    loginAs($this->admin);
    
    expect(current_user_can('manage_options'))->toBeTrue();
    expect(current_user_can('edit_users'))->toBeTrue();
});

it('editor has limited access', function () {
    loginAs($this->editor);
    
    expect(current_user_can('edit_posts'))->toBeTrue();
    expect(current_user_can('manage_options'))->toBeFalse();
});

afterEach(function () {
    logout();
});
```

## Testing with REST API

```php
use function PestWP\Functions\rest;
use function PestWP\createUser;

it('allows authenticated users to create posts', function () {
    $author = createUser('author');
    
    $response = rest()
        ->as($author)
        ->post('/wp/v2/posts', [
            'title' => 'New Post',
            'status' => 'draft',
        ]);
    
    expect($response)->toBeSuccessful();
    expect($response)->toHaveStatus(201);
});

it('denies anonymous post creation', function () {
    $response = rest()->post('/wp/v2/posts', [
        'title' => 'New Post',
    ]);
    
    expect($response)->toBeError();
    expect($response)->toHaveStatus(401);
});
```

## Testing with AJAX

```php
use function PestWP\Functions\ajax;
use function PestWP\createUser;

it('handles admin AJAX actions', function () {
    $admin = createUser('administrator');
    
    $response = ajax()
        ->as($admin)
        ->action('my_admin_action', ['data' => 'value']);
    
    expect($response)->toBeAjaxSuccess();
});
```

## Testing with Browser

```php
use function PestWP\createUser;

it('logs into wp-admin', function () {
    $admin = createUser('administrator', [
        'user_login' => 'testadmin',
        'user_pass' => 'password123',
    ]);
    
    visit('/wp-login.php')
        ->fill('#user_login', 'testadmin')
        ->fill('#user_pass', 'password123')
        ->click('#wp-submit')
        ->assertUrlContains('/wp-admin/')
        ->assertSee('Dashboard');
});
```

## Error Handling

```php
use function PestWP\loginAs;

it('throws exception for non-existent user', function () {
    loginAs(99999);
})->throws(RuntimeException::class, 'User with ID 99999 does not exist');

it('throws exception when WordPress not loaded', function () {
    // This would only happen in unit tests without WordPress
    loginAs(1);
})->throws(RuntimeException::class);
```

## Next Steps

- [Factories](factories.md) - Create users and other test data
- [Expectations](expectations.md) - Assert on user capabilities
- [REST API Testing](rest-api-testing.md) - Test authenticated endpoints
- [Browser Testing](browser-testing.md) - Test login flows
