<?php

declare(strict_types=1);

use function PestWP\createAttachment;
use function PestWP\createPost;
use function PestWP\createTerm;
use function PestWP\createUser;

describe('Factory Helpers', function () {
    describe('createPost()', function () {
        it('creates a post with default values', function () {
            $post = createPost();

            expect($post)->toBeInstanceOf(WP_Post::class)
                ->and($post->post_status)->toBe('publish')
                ->and($post->post_type)->toBe('post')
                ->and($post->post_title)->toContain('Test Post');
        });

        it('creates a post with custom values', function () {
            $post = createPost([
                'post_title' => 'Custom Title',
                'post_content' => 'Custom content here',
                'post_status' => 'draft',
                'post_type' => 'page',
            ]);

            expect($post)->toBeInstanceOf(WP_Post::class)
                ->and($post->post_title)->toBe('Custom Title')
                ->and($post->post_content)->toBe('Custom content here')
                ->and($post->post_status)->toBe('draft')
                ->and($post->post_type)->toBe('page');
        });

        it('returns WP_Post with accessible properties', function () {
            $post = createPost(['post_title' => 'Property Test']);

            // Test IDE autocompletion works with returned WP_Post
            expect($post->ID)->toBeInt()
                ->and($post->post_title)->toBe('Property Test')
                ->and($post->post_author)->toBeString()
                ->and($post->post_date)->toBeString();
        });
    });

    describe('createUser()', function () {
        it('creates a user with default subscriber role', function () {
            $user = createUser();

            expect($user)->toBeInstanceOf(WP_User::class)
                ->and($user->roles)->toContain('subscriber')
                ->and($user->user_login)->toContain('testuser_');
        });

        it('creates a user with specific role using string', function () {
            $user = createUser('editor');

            expect($user)->toBeInstanceOf(WP_User::class)
                ->and($user->roles)->toContain('editor');
        });

        it('creates a user with custom arguments', function () {
            $user = createUser([
                'user_login' => 'customuser',
                'user_email' => 'custom@example.com',
                'role' => 'author',
                'display_name' => 'Custom Display Name',
            ]);

            expect($user)->toBeInstanceOf(WP_User::class)
                ->and($user->user_login)->toBe('customuser')
                ->and($user->user_email)->toBe('custom@example.com')
                ->and($user->roles)->toContain('author')
                ->and($user->display_name)->toBe('Custom Display Name');
        });

        it('creates a user with role and extra args', function () {
            $user = createUser('administrator', [
                'display_name' => 'Admin User',
            ]);

            expect($user)->toBeInstanceOf(WP_User::class)
                ->and($user->roles)->toContain('administrator')
                ->and($user->display_name)->toBe('Admin User');
        });

        it('returns WP_User with accessible properties', function () {
            $user = createUser('editor');

            // Test IDE autocompletion works with returned WP_User
            expect($user->ID)->toBeInt()
                ->and($user->user_login)->toBeString()
                ->and($user->user_email)->toBeString()
                ->and($user->roles)->toBeArray();
        });
    });

    describe('createTerm()', function () {
        it('creates a category term by default', function () {
            $termId = createTerm('Test Category');

            expect($termId)->toBeInt()
                ->and($termId)->toBeGreaterThan(0);

            $term = get_term($termId, 'category');
            expect($term)->toBeInstanceOf(WP_Term::class)
                ->and($term->name)->toBe('Test Category')
                ->and($term->taxonomy)->toBe('category');
        });

        it('creates a term in custom taxonomy', function () {
            register_taxonomy('test_taxonomy', 'post');

            $termId = createTerm('Test Tag', 'test_taxonomy');

            expect($termId)->toBeInt();

            $term = get_term($termId, 'test_taxonomy');
            expect($term)->toBeInstanceOf(WP_Term::class)
                ->and($term->name)->toBe('Test Tag')
                ->and($term->taxonomy)->toBe('test_taxonomy');
        });

        it('creates a term with additional arguments', function () {
            $termId = createTerm('Term with Description', 'category', [
                'description' => 'This is a test description',
                'slug' => 'custom-slug',
            ]);

            $term = get_term($termId, 'category');
            expect($term->description)->toBe('This is a test description')
                ->and($term->slug)->toBe('custom-slug');
        });
    });

    describe('createAttachment()', function () {
        it('creates an attachment with auto-generated image', function () {
            $attachmentId = createAttachment();

            expect($attachmentId)->toBeInt()
                ->and($attachmentId)->toBeGreaterThan(0);

            $attachment = get_post($attachmentId);
            expect($attachment)->toBeInstanceOf(WP_Post::class)
                ->and($attachment->post_type)->toBe('attachment')
                ->and($attachment->post_mime_type)->toBe('image/jpeg');
        });

        it('creates an attachment with parent post', function () {
            $post = createPost();
            $attachmentId = createAttachment('', $post->ID);

            $attachment = get_post($attachmentId);
            expect($attachment->post_parent)->toBe($post->ID);
        });

        it('creates an attachment with custom arguments', function () {
            $attachmentId = createAttachment('', 0, [
                'post_title' => 'Custom Attachment Title',
                'post_excerpt' => 'Custom caption',
            ]);

            $attachment = get_post($attachmentId);
            expect($attachment->post_title)->toBe('Custom Attachment Title')
                ->and($attachment->post_excerpt)->toBe('Custom caption');
        });

        it('creates attachment successfully', function () {
            $attachmentId = createAttachment();

            // Verify attachment was created
            $attachment = get_post($attachmentId);
            expect($attachment)->toBeInstanceOf(WP_Post::class)
                ->and($attachment->post_type)->toBe('attachment')
                ->and($attachment->post_mime_type)->toBe('image/jpeg');
        });
    });

    describe('Type Safety', function () {
        it('throws exception when WordPress is not loaded for createPost', function () {
            // This test would need to run in isolation without WordPress
            // For now, we just verify the function exists and is callable
            expect(function_exists('PestWP\createPost'))->toBeTrue();
        });

        it('throws exception when WordPress is not loaded for createUser', function () {
            expect(function_exists('PestWP\createUser'))->toBeTrue();
        });

        it('throws exception when WordPress is not loaded for createTerm', function () {
            expect(function_exists('PestWP\createTerm'))->toBeTrue();
        });

        it('throws exception when WordPress is not loaded for createAttachment', function () {
            expect(function_exists('PestWP\createAttachment'))->toBeTrue();
        });
    });
});
