<?php

declare(strict_types=1);

/**
 * Database Isolation Tests
 *
 * These tests verify that database changes in one test do NOT persist
 * to subsequent tests. This is critical for test isolation.
 *
 * Phase 2.0 - Database Isolation Implementation
 *
 * IMPORTANT: These tests must be run in order to validate isolation.
 * Test A creates data with a unique identifier.
 * Test B verifies that data does NOT exist.
 *
 * The isolation is achieved using SQLite SAVEPOINT/ROLLBACK:
 * - Before each test: SAVEPOINT is created
 * - After each test: ROLLBACK TO SAVEPOINT undoes all changes
 */

// WordPress is already loaded by tests/bootstrap.php

/**
 * We use a file-based marker to communicate between tests.
 * In a properly isolated environment, Test B should NOT find Test A's data.
 */
describe('Database Isolation', function (): void {
    // Unique identifier for this test run
    $isolationMarker = 'ISOLATION_TEST_' . date('YmdHis') . '_' . mt_rand(1000, 9999);

    it('Test A: creates a post with unique marker', function () use ($isolationMarker): void {
        $postId = wp_insert_post([
            'post_title' => $isolationMarker,
            'post_content' => 'This post should be rolled back after the test.',
            'post_status' => 'publish',
            'post_type' => 'post',
        ]);

        expect($postId)->toBeGreaterThan(0);

        // Store the marker in a way that persists between tests (file-based)
        $markerFile = sys_get_temp_dir() . '/pestwp_isolation_marker.txt';
        file_put_contents($markerFile, $isolationMarker . ':' . $postId);

        // Verify the post exists right now
        $post = get_post($postId);
        expect($post)->not->toBeNull();
        expect($post->post_title)->toBe($isolationMarker);
    });

    it('Test B: verifies the post from Test A does NOT exist (isolation check)', function (): void {
        // Read the marker from Test A
        $markerFile = sys_get_temp_dir() . '/pestwp_isolation_marker.txt';

        if (! file_exists($markerFile)) {
            test()->markTestIncomplete('Test A must run before Test B for isolation verification.');

            return;
        }

        $markerData = file_get_contents($markerFile);
        [$marker, $postId] = explode(':', $markerData);

        // Query for posts with the marker title
        $foundPosts = get_posts([
            'post_type' => 'post',
            'post_status' => 'any',
            'title' => $marker,
            'posts_per_page' => 1,
        ]);

        // The post should NOT exist because Test A's changes were rolled back
        expect($foundPosts)->toBeEmpty(
            "Post with title '{$marker}' should have been rolled back, but it still exists.",
        );

        // Also verify by direct ID lookup
        $directPost = get_post((int) $postId);
        expect($directPost)->toBeNull(
            "Post ID {$postId} should have been rolled back, but it still exists.",
        );

        // Cleanup marker file
        @unlink($markerFile);
    });
});

/**
 * Alternative isolation test using direct post ID check.
 *
 * This approach stores the post ID and directly checks if it exists.
 */
describe('Post ID Isolation Check', function (): void {
    test('creates a post and records its ID', function (): void {
        $postId = wp_insert_post([
            'post_title' => 'Isolation Test Post - ' . uniqid(),
            'post_content' => 'Testing database isolation.',
            'post_status' => 'publish',
            'post_type' => 'post',
        ]);

        expect($postId)->toBeGreaterThan(0);

        // Store for next test
        $markerFile = sys_get_temp_dir() . '/pestwp_post_id_marker.txt';
        file_put_contents($markerFile, (string) $postId);

        // Verify exists in current test context
        expect(get_post($postId))->not->toBeNull();
    });

    test('checks if the previous post ID exists', function (): void {
        $markerFile = sys_get_temp_dir() . '/pestwp_post_id_marker.txt';

        if (! file_exists($markerFile)) {
            test()->markTestIncomplete('Previous test must run first.');

            return;
        }

        $postId = (int) file_get_contents($markerFile);
        @unlink($markerFile);

        $post = get_post($postId);

        // Post should NOT exist because the previous test was rolled back
        expect($post)->toBeNull(
            "Post ID {$postId} should have been rolled back, but it still exists.",
        );
    });
});

/**
 * Option isolation test.
 */
describe('Option Isolation Check', function (): void {
    $optionName = 'pestwp_isolation_test_option_' . mt_rand(1000, 9999);

    test('sets a test option', function () use ($optionName): void {
        $uniqueValue = 'test_value_' . uniqid();

        update_option($optionName, $uniqueValue);

        // Store for next test
        $markerFile = sys_get_temp_dir() . '/pestwp_option_marker.txt';
        file_put_contents($markerFile, $optionName . ':' . $uniqueValue);

        expect(get_option($optionName))->toBe($uniqueValue);
    });

    test('checks if the option persists', function (): void {
        $markerFile = sys_get_temp_dir() . '/pestwp_option_marker.txt';

        if (! file_exists($markerFile)) {
            test()->markTestIncomplete('Previous test must run first.');

            return;
        }

        $markerData = file_get_contents($markerFile);
        [$optionName, $expectedValue] = explode(':', $markerData);
        @unlink($markerFile);

        $actualValue = get_option($optionName);

        // Option should NOT exist or have the value because the previous test was rolled back
        expect($actualValue)->not->toBe(
            $expectedValue,
            "Option '{$optionName}' should have been rolled back, but it still has value '{$expectedValue}'.",
        );
    });
});
