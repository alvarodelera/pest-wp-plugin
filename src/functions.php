<?php

declare(strict_types=1);

/**
 * Pest Plugin for WordPress
 *
 * Global helper functions for WordPress testing with Pest.
 */

namespace PestWP;

/**
 * Returns the current version of the Pest WordPress plugin.
 */
function version(): string
{
    return '1.0.0-dev';
}

/**
 * Get the InteractsWithDatabase trait class name.
 *
 * Usage in Pest.php:
 *
 *     uses(\PestWP\Concerns\InteractsWithDatabase::class)->in('Integration');
 *
 * Or using this helper:
 *
 *     uses(PestWP\databaseIsolation())->in('Integration');
 *
 * @return class-string
 */
function databaseIsolation(): string
{
    return Concerns\InteractsWithDatabase::class;
}

/**
 * Create a post and return the WP_Post object.
 *
 * This is a type-safe wrapper around WordPress factory()->post->create_and_get().
 *
 * @param  array<string, mixed>  $args  Post arguments (title, content, status, etc.)
 * @return \WP_Post The created post object
 *
 * @throws \RuntimeException If WordPress is not loaded or post creation fails
 */
function createPost(array $args = []): \WP_Post
{
    if (! function_exists('wp_insert_post')) {
        throw new \RuntimeException('WordPress must be loaded to use createPost()');
    }

    $defaults = [
        'post_title' => 'Test Post ' . uniqid(),
        'post_content' => 'Test content',
        'post_status' => 'publish',
        'post_type' => 'post',
    ];

    $args = array_merge($defaults, $args);
    /** @phpstan-ignore-next-line WordPress function accepts broader array */
    $postId = wp_insert_post($args, true);

    if (is_wp_error($postId)) {
        throw new \RuntimeException('Failed to create post: ' . $postId->get_error_message());
    }

    $post = get_post($postId);

    if (! $post instanceof \WP_Post) {
        throw new \RuntimeException('Failed to retrieve created post');
    }

    return $post;
}

/**
 * Create a user and return the WP_User object.
 *
 * This is a type-safe wrapper around WordPress user creation.
 *
 * @param  string|array<string, mixed>  $roleOrArgs  User role (string) or full args array
 * @param  array<string, mixed>  $extraArgs  Additional user args if first param is role
 * @return \WP_User The created user object
 *
 * @throws \RuntimeException If WordPress is not loaded or user creation fails
 */
function createUser(string|array $roleOrArgs = 'subscriber', array $extraArgs = []): \WP_User
{
    if (! function_exists('wp_insert_user')) {
        throw new \RuntimeException('WordPress must be loaded to use createUser()');
    }

    // If first argument is a string, treat it as role
    if (is_string($roleOrArgs)) {
        $args = array_merge([
            'role' => $roleOrArgs,
        ], $extraArgs);
    } else {
        $args = $roleOrArgs;
    }

    $defaults = [
        'user_login' => 'testuser_' . uniqid(),
        'user_pass' => wp_generate_password(),
        'user_email' => 'test_' . uniqid() . '@example.com',
        'role' => 'subscriber',
    ];

    $args = array_merge($defaults, $args);
    /** @phpstan-ignore-next-line WordPress function accepts broader array */
    $userId = wp_insert_user($args);

    if (is_wp_error($userId)) {
        throw new \RuntimeException('Failed to create user: ' . $userId->get_error_message());
    }

    $user = get_user_by('id', $userId);

    if (! $user instanceof \WP_User) {
        throw new \RuntimeException('Failed to retrieve created user');
    }

    return $user;
}

/**
 * Create a term and return the term ID.
 *
 * This is a type-safe wrapper around WordPress term creation.
 *
 * @param  string  $name  Term name
 * @param  string  $taxonomy  Taxonomy name (default: 'category')
 * @param  array<string, mixed>  $args  Additional term arguments
 * @return int The created term ID
 *
 * @throws \RuntimeException If WordPress is not loaded or term creation fails
 */
function createTerm(string $name, string $taxonomy = 'category', array $args = []): int
{
    if (! function_exists('wp_insert_term')) {
        throw new \RuntimeException('WordPress must be loaded to use createTerm()');
    }

    /** @phpstan-ignore-next-line WordPress function accepts broader array */
    $result = wp_insert_term($name, $taxonomy, $args);

    if (is_wp_error($result)) {
        throw new \RuntimeException('Failed to create term: ' . $result->get_error_message());
    }

    return (int) $result['term_id'];
}

/**
 * Create an attachment and return the attachment ID.
 *
 * This is a type-safe wrapper around WordPress attachment creation.
 *
 * @param  string  $file  Path to the file to attach
 * @param  int  $parentPostId  Parent post ID (0 for no parent)
 * @param  array<string, mixed>  $args  Additional attachment arguments
 * @return int The created attachment ID
 *
 * @throws \RuntimeException If WordPress is not loaded or attachment creation fails
 */
function createAttachment(string $file = '', int $parentPostId = 0, array $args = []): int
{
    if (! function_exists('wp_insert_attachment')) {
        throw new \RuntimeException('WordPress must be loaded to use createAttachment()');
    }

    // If no file provided, create a dummy image
    if (empty($file)) {
        $uploadDir = wp_upload_dir();
        $file = $uploadDir['path'] . '/test-image-' . uniqid() . '.jpg';

        // Create a simple 1x1 pixel image
        $image = imagecreate(1, 1);
        if ($image === false) {
            throw new \RuntimeException('Failed to create test image');
        }
        imagejpeg($image, $file);
        imagedestroy($image);
    }

    $defaults = [
        'post_mime_type' => 'image/jpeg',
        'post_title' => 'Test Attachment ' . uniqid(),
        'post_status' => 'inherit',
    ];

    $args = array_merge($defaults, $args);
    $attachmentId = wp_insert_attachment($args, $file, $parentPostId, true);

    if (is_wp_error($attachmentId)) {
        throw new \RuntimeException('Failed to create attachment: ' . $attachmentId->get_error_message());
    }

    // Generate attachment metadata
    if (function_exists('wp_generate_attachment_metadata')) {
        /** @phpstan-ignore-next-line ABSPATH is defined when WordPress is loaded */
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachData = wp_generate_attachment_metadata($attachmentId, $file);
        wp_update_attachment_metadata($attachmentId, $attachData);
    }

    return $attachmentId;
}

/**
 * Log in as a specific user for testing.
 *
 * This sets the current user in WordPress, simulating authentication.
 * Useful for testing permission-dependent functionality.
 *
 * @param  int|\WP_User  $user  User ID or WP_User object
 * @return \WP_User The logged-in user object
 *
 * @throws \RuntimeException If WordPress is not loaded or user doesn't exist
 */
function loginAs(int|\WP_User $user): \WP_User
{
    if (! function_exists('wp_set_current_user')) {
        throw new \RuntimeException('WordPress must be loaded to use loginAs()');
    }

    $userId = $user instanceof \WP_User ? $user->ID : $user;

    $wpUser = wp_set_current_user($userId);

    if (! $wpUser instanceof \WP_User || $wpUser->ID === 0) {
        throw new \RuntimeException("User with ID {$userId} does not exist");
    }

    // Set auth cookie data for the user (simulated - doesn't set actual cookies in CLI)
    // This ensures functions like is_user_logged_in() work correctly
    wp_set_auth_cookie($userId);

    return $wpUser;
}

/**
 * Log out the current user.
 *
 * This clears the current user and simulates logging out.
 *
 * @throws \RuntimeException If WordPress is not loaded
 */
function logout(): void
{
    if (! function_exists('wp_set_current_user')) {
        throw new \RuntimeException('WordPress must be loaded to use logout()');
    }

    wp_set_current_user(0);
    wp_clear_auth_cookie();
}

/**
 * Get the currently logged-in user.
 *
 * This is a convenience wrapper around wp_get_current_user().
 *
 * @return \WP_User The current user object (ID 0 if not logged in)
 *
 * @throws \RuntimeException If WordPress is not loaded
 */
function currentUser(): \WP_User
{
    if (! function_exists('wp_get_current_user')) {
        throw new \RuntimeException('WordPress must be loaded to use currentUser()');
    }

    return wp_get_current_user();
}

/**
 * Check if a user is logged in.
 *
 * @return bool True if a user is logged in, false otherwise
 *
 * @throws \RuntimeException If WordPress is not loaded
 */
function isUserLoggedIn(): bool
{
    if (! function_exists('is_user_logged_in')) {
        throw new \RuntimeException('WordPress must be loaded to use isUserLoggedIn()');
    }

    return is_user_logged_in();
}
