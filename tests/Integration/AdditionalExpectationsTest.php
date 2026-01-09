<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;

use function PestWP\createUser;
use function PestWP\deleteOption;
use function PestWP\deleteTransient;
use function PestWP\registerTestShortcode;
use function PestWP\setOption;
use function PestWP\setTransient;
use function PestWP\unregisterShortcode;

describe('Additional Custom Expectations', function () {
    describe('User Capabilities & Permissions', function () {
        it('can check if user has capability', function () {
            $admin = createUser('administrator');

            expect($admin)->toHaveCapability('manage_options');
        });

        it('fails when user does not have capability', function () {
            $subscriber = createUser();

            expect($subscriber)->toHaveCapability('manage_options');
        })->throws(ExpectationFailedException::class);

        it('can check if user has role', function () {
            $editor = createUser('editor');

            expect($editor)->toHaveRole('editor');
        });

        it('fails when user does not have role', function () {
            $subscriber = createUser();

            expect($subscriber)->toHaveRole('administrator');
        })->throws(ExpectationFailedException::class);

        it('can use can() as capability alias', function () {
            $author = createUser('author');

            expect($author)->can('publish_posts');
        });

        it('fails when user cannot perform action', function () {
            $contributor = createUser('contributor');

            expect($contributor)->can('publish_posts');
        })->throws(ExpectationFailedException::class);

        it('throws exception when checking capability on non-WP_User', function () {
            $notAUser = new stdClass();

            expect($notAUser)->toHaveCapability('read');
        })->throws(AssertionFailedError::class, 'Expected value to be a WP_User instance');

        it('can chain capability expectations', function () {
            $admin = createUser('administrator');

            expect($admin)
                ->toHaveRole('administrator')
                ->toHaveCapability('manage_options')
                ->can('delete_users');
        });
    });

    describe('Shortcode Expectations', function () {
        afterEach(function () {
            // Clean up any registered shortcodes
            unregisterShortcode('test_shortcode');
            unregisterShortcode('another_shortcode');
        });

        it('can check if shortcode is registered', function () {
            registerTestShortcode('test_shortcode', fn () => 'output');

            expect('test_shortcode')->toBeRegisteredShortcode();
        });

        it('fails when shortcode is not registered', function () {
            expect('nonexistent_shortcode')->toBeRegisteredShortcode();
        })->throws(ExpectationFailedException::class);

        it('throws exception when checking non-string value', function () {
            expect(123)->toBeRegisteredShortcode();
        })->throws(AssertionFailedError::class, 'Expected value to be a shortcode tag (string)');

        it('can verify built-in WordPress shortcodes', function () {
            // WordPress registers some shortcodes by default
            expect('caption')->toBeRegisteredShortcode();
            expect('gallery')->toBeRegisteredShortcode();
        });
    });

    describe('Options Expectations', function () {
        afterEach(function () {
            deleteOption('test_option');
            deleteOption('another_option');
        });

        it('can check if option exists', function () {
            setOption('test_option', 'test_value');

            expect('test_option')->toHaveOption();
        });

        it('can check option with specific value', function () {
            setOption('test_option', 'expected_value');

            expect('test_option')->toHaveOption('expected_value');
        });

        it('fails when option does not exist', function () {
            expect('nonexistent_option')->toHaveOption();
        })->throws(ExpectationFailedException::class);

        it('fails when option value does not match', function () {
            setOption('test_option', 'actual_value');

            expect('test_option')->toHaveOption('expected_value');
        })->throws(ExpectationFailedException::class);

        it('throws exception when checking non-string value', function () {
            expect(123)->toHaveOption();
        })->throws(AssertionFailedError::class, 'Expected value to be an option name (string)');

        it('can check built-in WordPress options', function () {
            expect('blogname')->toHaveOption();
            expect('siteurl')->toHaveOption();
        });
    });

    describe('Transients Expectations', function () {
        afterEach(function () {
            deleteTransient('test_transient');
            deleteTransient('another_transient');
        });

        it('can check if transient exists', function () {
            setTransient('test_transient', 'test_value');

            expect('test_transient')->toHaveTransient();
        });

        it('can check transient with specific value', function () {
            setTransient('test_transient', 'expected_value');

            expect('test_transient')->toHaveTransient('expected_value');
        });

        it('fails when transient does not exist', function () {
            expect('nonexistent_transient')->toHaveTransient();
        })->throws(ExpectationFailedException::class);

        it('fails when transient value does not match', function () {
            setTransient('test_transient', 'actual_value');

            expect('test_transient')->toHaveTransient('expected_value');
        })->throws(ExpectationFailedException::class);

        it('throws exception when checking non-string value', function () {
            expect(123)->toHaveTransient();
        })->throws(AssertionFailedError::class, 'Expected value to be a transient name (string)');

        it('can set transient with expiration', function () {
            setTransient('test_transient', 'value', 3600);

            expect('test_transient')->toHaveTransient('value');
        });
    });

    describe('Post Type Expectations', function () {
        afterEach(function () {
            // Unregister custom post types
            unregister_post_type('book');
            unregister_post_type('movie');
        });

        it('can check if post type is registered', function () {
            register_post_type('book');

            expect('book')->toBeRegisteredPostType();
        });

        it('fails when post type is not registered', function () {
            expect('nonexistent_post_type')->toBeRegisteredPostType();
        })->throws(ExpectationFailedException::class);

        it('can check built-in post types', function () {
            expect('post')->toBeRegisteredPostType();
            expect('page')->toBeRegisteredPostType();
            expect('attachment')->toBeRegisteredPostType();
        });

        it('can check if post type supports feature', function () {
            register_post_type('book', [
                'supports' => ['title', 'editor', 'thumbnail'],
            ]);

            expect('book')->toSupportFeature('title');
            expect('book')->toSupportFeature('thumbnail');
        });

        it('fails when post type does not support feature', function () {
            register_post_type('book', [
                'supports' => ['title'],
            ]);

            expect('book')->toSupportFeature('thumbnail');
        })->throws(ExpectationFailedException::class);

        it('throws exception when checking non-string value for post type', function () {
            expect(123)->toBeRegisteredPostType();
        })->throws(AssertionFailedError::class, 'Expected value to be a post type (string)');

        it('can check built-in post type features', function () {
            expect('post')->toSupportFeature('title');
            expect('post')->toSupportFeature('editor');
            expect('page')->toSupportFeature('page-attributes');
        });

        it('can chain post type expectations', function () {
            register_post_type('book', [
                'supports' => ['title', 'editor', 'thumbnail'],
            ]);

            expect('book')
                ->toBeRegisteredPostType()
                ->toSupportFeature('title')
                ->toSupportFeature('editor');
        });
    });

    describe('Taxonomy Expectations', function () {
        afterEach(function () {
            // Unregister custom taxonomies
            unregister_taxonomy('genre');
            unregister_taxonomy('author_type');
        });

        it('can check if taxonomy is registered', function () {
            register_taxonomy('genre', 'book');

            expect('genre')->toBeRegisteredTaxonomy();
        });

        it('fails when taxonomy is not registered', function () {
            expect('nonexistent_taxonomy')->toBeRegisteredTaxonomy();
        })->throws(ExpectationFailedException::class);

        it('can check built-in taxonomies', function () {
            expect('category')->toBeRegisteredTaxonomy();
            expect('post_tag')->toBeRegisteredTaxonomy();
        });

        it('throws exception when checking non-string value', function () {
            expect(123)->toBeRegisteredTaxonomy();
        })->throws(AssertionFailedError::class, 'Expected value to be a taxonomy (string)');
    });

    describe('Helper Functions', function () {
        it('can set and get options', function () {
            setOption('test_helper_option', 'test_value');

            expect(get_option('test_helper_option'))->toBe('test_value');

            deleteOption('test_helper_option');

            expect(get_option('test_helper_option', false))->toBeFalse();
        });

        it('can set and get transients', function () {
            setTransient('test_helper_transient', 'test_value', 3600);

            expect(get_transient('test_helper_transient'))->toBe('test_value');

            deleteTransient('test_helper_transient');

            expect(get_transient('test_helper_transient'))->toBeFalse();
        });

        it('can register and use shortcodes', function () {
            registerTestShortcode('test_helper', fn () => 'Hello World');

            $output = do_shortcode('[test_helper]');

            expect($output)->toBe('Hello World');

            unregisterShortcode('test_helper');
        });
    });

    describe('Real-World Scenarios', function () {
        it('can test custom post type with full setup', function () {
            register_post_type('book', [
                'public' => true,
                'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            ]);

            register_taxonomy('genre', 'book');

            expect('book')
                ->toBeRegisteredPostType()
                ->toSupportFeature('title')
                ->toSupportFeature('thumbnail')
                ->toSupportFeature('custom-fields');

            expect('genre')->toBeRegisteredTaxonomy();

            // Clean up
            unregister_taxonomy('genre');
            unregister_post_type('book');
        });

        it('can test plugin activation with options', function () {
            // Simulate plugin activation
            setOption('my_plugin_version', '1.0.0');
            setOption('my_plugin_installed', true);
            setTransient('my_plugin_activation_notice', true, 60);

            expect('my_plugin_version')->toHaveOption('1.0.0');
            expect('my_plugin_installed')->toHaveOption(true);
            expect('my_plugin_activation_notice')->toHaveTransient(true);

            // Clean up
            deleteOption('my_plugin_version');
            deleteOption('my_plugin_installed');
            deleteTransient('my_plugin_activation_notice');
        });

        it('can test user permissions workflow', function () {
            $admin = createUser('administrator');
            $editor = createUser('editor');
            $author = createUser('author');

            expect($admin)
                ->toHaveRole('administrator')
                ->can('manage_options')
                ->can('delete_users')
                ->and($editor)
                ->toHaveRole('editor')
                ->can('edit_posts')
                ->can('edit_others_posts')
                ->and($author)
                ->toHaveRole('author')
                ->can('edit_posts')
                ->can('publish_posts');

        });

        it('can test shortcode registration and output', function () {
            registerTestShortcode('price', fn ($atts) => '$' . ($atts['amount'] ?? '0'));

            expect('price')->toBeRegisteredShortcode();

            $output = do_shortcode('[price amount="99"]');
            expect($output)->toBe('$99');

            unregisterShortcode('price');
        });
    });
});
