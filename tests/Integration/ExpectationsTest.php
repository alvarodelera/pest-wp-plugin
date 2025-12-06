<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;

use function PestWP\createPost;
use function PestWP\createUser;

describe('Custom Expectations', function () {
    describe('Post Status Expectations', function () {
        it('can check if post is published', function () {
            $post = createPost(['post_status' => 'publish']);

            expect($post)->toBePublished();
        });

        it('fails when post is not published', function () {
            $post = createPost(['post_status' => 'draft']);

            expect($post)->toBePublished();
        })->throws(ExpectationFailedException::class);

        it('can check if post is draft', function () {
            $post = createPost(['post_status' => 'draft']);

            expect($post)->toBeDraft();
        });

        it('fails when post is not draft', function () {
            $post = createPost(['post_status' => 'publish']);

            expect($post)->toBeDraft();
        })->throws(ExpectationFailedException::class);

        it('can check if post is pending', function () {
            $post = createPost(['post_status' => 'pending']);

            expect($post)->toBePending();
        });

        it('can check if post is private', function () {
            $post = createPost(['post_status' => 'private']);

            expect($post)->toBePrivate();
        });

        it('can check if post is in trash', function () {
            $post = createPost(['post_status' => 'trash']);

            expect($post)->toBeInTrash();
        });

        it('throws exception when checking status on non-WP_Post', function () {
            $notAPost = (object) ['post_status' => 'publish'];

            expect($notAPost)->toBePublished();
        })->throws(AssertionFailedError::class, 'Expected value to be a WP_Post instance');
    });

    describe('WP_Error Expectations', function () {
        it('can check if value is WP_Error', function () {
            $error = new WP_Error('test_error', 'Test error message');

            expect($error)->toBeWPError();
        });

        it('fails when value is not WP_Error', function () {
            $notAnError = new stdClass();

            expect($notAnError)->toBeWPError();
        })->throws(ExpectationFailedException::class);

        it('can check WP_Error code', function () {
            $error = new WP_Error('invalid_input', 'Invalid input provided');

            expect($error)->toHaveErrorCode('invalid_input');
        });

        it('fails when error code does not match', function () {
            $error = new WP_Error('invalid_input', 'Invalid input provided');

            expect($error)->toHaveErrorCode('different_code');
        })->throws(ExpectationFailedException::class);

        it('throws exception when checking error code on non-WP_Error', function () {
            $notAnError = new stdClass();

            expect($notAnError)->toHaveErrorCode('test');
        })->throws(AssertionFailedError::class, 'Expected value to be a WP_Error instance');
    });

    describe('Post Meta Expectations', function () {
        it('can check if post has meta with specific value', function () {
            $post = createPost();
            update_post_meta($post->ID, 'price', 100);

            expect($post)->toHaveMeta('price', '100');
        });

        it('can check if post has meta key regardless of value', function () {
            $post = createPost();
            update_post_meta($post->ID, 'featured', true);

            expect($post)->toHaveMeta('featured');
        });

        it('can check if post has meta key using toHaveMetaKey', function () {
            $post = createPost();
            update_post_meta($post->ID, 'custom_field', 'value');

            expect($post)->toHaveMetaKey('custom_field');
        });

        it('fails when post does not have expected meta value', function () {
            $post = createPost();
            update_post_meta($post->ID, 'price', 100);

            expect($post)->toHaveMeta('price', '200');
        })->throws(ExpectationFailedException::class);

        it('fails when post does not have meta key', function () {
            $post = createPost();

            expect($post)->toHaveMetaKey('nonexistent_key');
        })->throws(ExpectationFailedException::class);

        it('throws exception when checking meta on non-WP_Post', function () {
            $notAPost = new stdClass();

            expect($notAPost)->toHaveMeta('key', 'value');
        })->throws(AssertionFailedError::class, 'Expected value to be a WP_Post or WP_User instance');
    });

    describe('User Meta Expectations', function () {
        it('can check if user has meta with specific value', function () {
            $user = createUser();
            update_user_meta($user->ID, 'favorite_color', 'blue');

            expect($user)->toHaveUserMeta('favorite_color', 'blue');
        });

        it('can check if user has meta key regardless of value', function () {
            $user = createUser();
            update_user_meta($user->ID, 'newsletter_subscribed', true);

            expect($user)->toHaveUserMeta('newsletter_subscribed');
        });

        it('fails when user does not have expected meta value', function () {
            $user = createUser();
            update_user_meta($user->ID, 'favorite_color', 'blue');

            expect($user)->toHaveUserMeta('favorite_color', 'red');
        })->throws(ExpectationFailedException::class);

        it('throws exception when checking user meta on non-WP_User', function () {
            $notAUser = new stdClass();

            expect($notAUser)->toHaveUserMeta('key', 'value');
        })->throws(AssertionFailedError::class, 'Expected value to be a WP_User instance');
    });

    describe('Hook Expectations', function () {
        it('can check if action exists', function () {
            $callback = function () {
                echo 'test';
            };
            add_action('test_action', $callback);

            expect('test_action')->toHaveAction($callback);
        });

        it('can check if action exists with specific priority', function () {
            $callback = function () {
                echo 'test';
            };
            add_action('test_action_priority', $callback, 15);

            expect('test_action_priority')->toHaveAction($callback, 15);
        });

        it('can check if filter exists', function () {
            $callback = function ($value) {
                return $value;
            };
            add_filter('test_filter', $callback);

            expect('test_filter')->toHaveFilter($callback);
        });

        it('can check if filter exists with specific priority', function () {
            $callback = function ($value) {
                return $value;
            };
            add_filter('test_filter_priority', $callback, 20);

            expect('test_filter_priority')->toHaveFilter($callback, 20);
        });

        it('can check action with named function', function () {
            function test_action_callback()
            {
                echo 'test';
            }
            add_action('named_action', 'test_action_callback');

            expect('named_action')->toHaveAction('test_action_callback');
        });

        it('fails when action does not exist', function () {
            expect('nonexistent_action')->toHaveAction('some_callback');
        })->throws(ExpectationFailedException::class);

        it('fails when filter does not exist', function () {
            expect('nonexistent_filter')->toHaveFilter('some_callback');
        })->throws(ExpectationFailedException::class);

        it('throws exception when checking action on non-string', function () {
            expect(123)->toHaveAction('callback');
        })->throws(AssertionFailedError::class, 'Expected value to be a hook name (string)');
    });

    describe('Term Expectations', function () {
        it('can check if post has term', function () {
            $post = createPost();
            $termId = wp_insert_term('Featured', 'category');
            wp_set_post_terms($post->ID, [$termId['term_id']], 'category');

            expect($post)->toHaveTerm('Featured', 'category');
        });

        it('can check if post has term by ID', function () {
            $post = createPost();
            $termId = wp_insert_term('Technology', 'category');
            wp_set_post_terms($post->ID, [$termId['term_id']], 'category');

            expect($post)->toHaveTerm($termId['term_id'], 'category');
        });

        it('fails when post does not have term', function () {
            $post = createPost();

            expect($post)->toHaveTerm('Nonexistent', 'category');
        })->throws(ExpectationFailedException::class);

        it('throws exception when checking term on non-WP_Post', function () {
            $notAPost = new stdClass();

            expect($notAPost)->toHaveTerm('test', 'category');
        })->throws(AssertionFailedError::class, 'Expected value to be a WP_Post instance');
    });

    describe('Real-World Usage Examples', function () {
        it('can chain multiple expectations', function () {
            $post = createPost([
                'post_title' => 'My Published Post',
                'post_status' => 'publish',
            ]);
            update_post_meta($post->ID, 'views', 100);

            expect($post)
                ->toBeInstanceOf(WP_Post::class)
                ->toBePublished()
                ->toHaveMeta('views', '100');
        });

        it('can test complex post workflow', function () {
            // Create draft post
            $post = createPost([
                'post_title' => 'Draft Article',
                'post_status' => 'draft',
            ]);
            expect($post)->toBeDraft();

            // Update to published
            wp_update_post([
                'ID' => $post->ID,
                'post_status' => 'publish',
            ]);
            $updatedPost = get_post($post->ID);
            expect($updatedPost)->toBePublished();

            // Move to trash
            wp_trash_post($post->ID);
            $trashedPost = get_post($post->ID);
            expect($trashedPost)->toBeInTrash();
        });

        it('can test error conditions elegantly', function () {
            // Simulate an error
            $result = new WP_Error('upload_failed', 'File upload failed');

            expect($result)
                ->toBeWPError()
                ->toHaveErrorCode('upload_failed');
        });

        it('can test WordPress hooks registration', function () {
            // Register some hooks
            $initCallback = function () {
                // Initialize plugin
            };
            add_action('init', $initCallback, 10);

            $contentCallback = function ($content) {
                return $content . ' - Modified';
            };
            add_filter('the_content', $contentCallback, 15);

            expect('init')->toHaveAction($initCallback, 10);
            expect('the_content')->toHaveFilter($contentCallback, 15);
        });
    });
});
