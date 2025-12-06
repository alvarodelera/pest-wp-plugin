# Functions Organization

This directory contains all global helper functions for PestWP, organized by category for better maintainability.

## Structure

- **`helpers.php`** - General utility functions
  - `version()` - Get plugin version
  - `databaseIsolation()` - Get database isolation trait

- **`factories.php`** - WordPress object creation helpers
  - `createPost()` - Create test posts
  - `createUser()` - Create test users
  - `createTerm()` - Create test terms/categories
  - `createAttachment()` - Create test attachments/media

- **`auth.php`** - Authentication and user session helpers
  - `loginAs()` - Log in as a specific user
  - `logout()` - Log out current user
  - `currentUser()` - Get current logged-in user
  - `isUserLoggedIn()` - Check if user is logged in

## Adding New Functions

When adding new helper functions:

1. **Choose the right file** based on the function's purpose:
   - General utilities → `helpers.php`
   - Object creation → `factories.php`
   - Authentication/sessions → `auth.php`
   - New category → Create a new file

2. **Follow the existing patterns**:
   - Use proper PHPDoc annotations
   - Include `@throws` declarations
   - Add runtime checks for WordPress availability
   - Use PHPStan suppression comments where needed

3. **Update `functions.php`** to require your new file:
   ```php
   require_once __DIR__ . '/Functions/your-new-file.php';
   ```

## Example

```php
// In src/Functions/factories.php

/**
 * Create a comment and return the comment ID.
 *
 * @param  int  $postId  Post ID to attach comment to
 * @param  array<string, mixed>  $args  Comment arguments
 * @return int The created comment ID
 *
 * @throws \RuntimeException If WordPress is not loaded or comment creation fails
 */
function createComment(int $postId, array $args = []): int
{
    if (! function_exists('wp_insert_comment')) {
        throw new \RuntimeException('WordPress must be loaded to use createComment()');
    }

    // ... implementation
}
```
