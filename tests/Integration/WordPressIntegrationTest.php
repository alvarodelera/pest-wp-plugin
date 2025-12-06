<?php

declare(strict_types=1);

/**
 * WordPress Integration Tests
 *
 * These tests verify that WordPress is correctly loaded and functional
 * with the SQLite database backend.
 *
 * Phase 1.4 - PoC Integration Tests
 */

// WordPress is already loaded by tests/bootstrap.php

describe('WordPress Integration', function (): void {
    it('loads WordPress successfully', function (): void {
        expect(function_exists('wp_insert_post'))->toBeTrue();
        expect(function_exists('get_post'))->toBeTrue();
        expect(function_exists('wp_delete_post'))->toBeTrue();
    });

    it('has correct WordPress version', function (): void {
        global $wp_version;

        expect($wp_version)->toBeString();
        expect(version_compare($wp_version, '6.0', '>='))->toBeTrue();
    });

    it('has SQLite as database engine', function (): void {
        expect(defined('DB_ENGINE'))->toBeTrue();
        expect(DB_ENGINE)->toBe('sqlite');
    });

    it('can access wpdb instance', function (): void {
        global $wpdb;

        expect($wpdb)->toBeInstanceOf(WP_SQLite_DB::class);
    });
});

describe('WordPress Post Operations (Persistence Test)', function (): void {
    it('can create a post with wp_insert_post', function (): void {
        $postData = [
            'post_title' => 'Test Post - Persistence',
            'post_content' => 'This is a test post content for persistence verification.',
            'post_status' => 'publish',
            'post_type' => 'post',
        ];

        $postId = wp_insert_post($postData);

        expect($postId)->toBeInt();
        expect($postId)->toBeGreaterThan(0);
        expect(is_wp_error($postId))->toBeFalse();
    });

    it('can retrieve the created post with get_post', function (): void {
        $postData = [
            'post_title' => 'Test Post - Retrieval',
            'post_content' => 'Content for retrieval test.',
            'post_status' => 'publish',
            'post_type' => 'post',
        ];

        $postId = wp_insert_post($postData);
        $post = get_post($postId);

        expect($post)->toBeInstanceOf(WP_Post::class);
        expect($post->post_title)->toBe('Test Post - Retrieval');
        expect($post->post_content)->toBe('Content for retrieval test.');
        expect($post->post_status)->toBe('publish');
    });

    it('can update a post', function (): void {
        $postId = wp_insert_post([
            'post_title' => 'Original Title',
            'post_content' => 'Original content.',
            'post_status' => 'draft',
            'post_type' => 'post',
        ]);

        wp_update_post([
            'ID' => $postId,
            'post_title' => 'Updated Title',
            'post_status' => 'publish',
        ]);

        $post = get_post($postId);

        expect($post->post_title)->toBe('Updated Title');
        expect($post->post_status)->toBe('publish');
    });

    it('can delete a post', function (): void {
        $postId = wp_insert_post([
            'post_title' => 'Post to Delete',
            'post_content' => 'This post will be deleted.',
            'post_status' => 'publish',
            'post_type' => 'post',
        ]);

        // Force delete (bypass trash)
        $result = wp_delete_post($postId, true);

        expect($result)->toBeInstanceOf(WP_Post::class);
        expect(get_post($postId))->toBeNull();
    });
});

describe('WordPress User Operations', function (): void {
    it('has admin user created during installation', function (): void {
        $user = get_user_by('login', 'admin');

        expect($user)->toBeInstanceOf(WP_User::class);
        expect($user->user_login)->toBe('admin');
    });

    it('can create a new user', function (): void {
        $uniqueId = uniqid();
        $userId = wp_create_user(
            'testuser_' . $uniqueId,
            'password123',
            'testuser_' . $uniqueId . '@example.org',
        );

        expect($userId)->toBeInt();
        expect($userId)->toBeGreaterThan(0);
        expect(is_wp_error($userId))->toBeFalse();
    });
});

describe('WordPress Options', function (): void {
    it('can get site options', function (): void {
        $blogName = get_option('blogname');

        expect($blogName)->toBeString();
    });

    it('can add and retrieve custom options', function (): void {
        $optionName = 'pestwp_test_option_' . uniqid();
        $optionValue = ['test' => 'value', 'number' => 42];

        add_option($optionName, $optionValue);
        $retrieved = get_option($optionName);

        expect($retrieved)->toBe($optionValue);

        // Cleanup
        delete_option($optionName);
    });

    it('can update options', function (): void {
        $optionName = 'pestwp_update_test_' . uniqid();

        add_option($optionName, 'initial');
        update_option($optionName, 'updated');

        expect(get_option($optionName))->toBe('updated');

        // Cleanup
        delete_option($optionName);
    });
});

describe('WordPress Database Direct Access', function (): void {
    it('can execute direct SQL queries via wpdb', function (): void {
        global $wpdb;

        // Get tables using SQLite syntax
        $tables = $wpdb->get_results(
            "SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '{$wpdb->prefix}%'",
            ARRAY_A,
        );

        expect($tables)->toBeArray();
        expect(count($tables))->toBeGreaterThan(0);
    });

    it('has standard WordPress tables', function (): void {
        global $wpdb;

        $expectedTables = [
            $wpdb->prefix . 'posts',
            $wpdb->prefix . 'postmeta',
            $wpdb->prefix . 'options',
            $wpdb->prefix . 'users',
            $wpdb->prefix . 'usermeta',
        ];

        foreach ($expectedTables as $table) {
            $exists = $wpdb->get_var(
                "SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'",
            );
            expect($exists)->toBe($table, "Table {$table} should exist");
        }
    });
});
