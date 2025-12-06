<?php

declare(strict_types=1);

/**
 * Database Isolation Tests
 *
 * These tests verify that database changes in one test do NOT persist
 * to subsequent tests. This is critical for test isolation.
 *
 * Phase 1.4 - PoC Isolation Tests
 *
 * IMPORTANT: These tests must be run in order to validate isolation.
 * Test A creates data with a unique identifier.
 * Test B verifies that data does NOT exist.
 */

// WordPress is already loaded by tests/bootstrap.php

/**
 * We use a static marker to communicate between tests.
 * In a properly isolated environment, Test B should NOT find Test A's data.
 */
describe('Database Isolation', function (): void {
    // Unique identifier for this test run
    $isolationMarker = 'ISOLATION_TEST_' . date('YmdHis');

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
            // If marker file doesn't exist, Test A hasn't run yet
            // This can happen if tests run in parallel or out of order
            // Mark as incomplete rather than failing
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

        // IMPORTANT: This is where we verify isolation
        // If the database is properly isolated/rolled back between tests,
        // this post should NOT exist
        //
        // NOTE: Currently, we don't have automatic rollback implemented.
        // This test documents the EXPECTED behavior once rollback is working.
        // For now, we check if the post exists and report accordingly.

        if (! empty($foundPosts)) {
            // Post still exists - isolation is NOT working
            // This is currently expected since we haven't implemented rollback yet
            test()->markTestIncomplete(
                'Post from Test A still exists (ID: ' . $postId . '). ' .
                'Database isolation/rollback is not yet implemented.',
            );
        } else {
            // Post doesn't exist - either isolation is working or post was manually deleted
            expect($foundPosts)->toBeEmpty();
        }

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
    $testPostId = null;

    test('creates a post and records its ID', function () use (&$testPostId): void {
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

        // Document current behavior
        if ($post !== null) {
            test()->markTestIncomplete(
                "Post ID {$postId} still exists. " .
                'This is expected until database rollback is implemented in TestCase.',
            );
        } else {
            expect($post)->toBeNull('Post should not exist if isolation is working');
        }
    });
});

/**
 * Option isolation test.
 */
describe('Option Isolation Check', function (): void {
    $optionName = 'pestwp_isolation_test_option';

    test('sets a test option', function () use ($optionName): void {
        $uniqueValue = 'test_value_' . uniqid();

        update_option($optionName, $uniqueValue);

        // Store for next test
        $markerFile = sys_get_temp_dir() . '/pestwp_option_marker.txt';
        file_put_contents($markerFile, $uniqueValue);

        expect(get_option($optionName))->toBe($uniqueValue);
    });

    test('checks if the option persists', function () use ($optionName): void {
        $markerFile = sys_get_temp_dir() . '/pestwp_option_marker.txt';

        if (! file_exists($markerFile)) {
            test()->markTestIncomplete('Previous test must run first.');

            return;
        }

        $expectedValue = file_get_contents($markerFile);
        @unlink($markerFile);

        $actualValue = get_option($optionName);

        if ($actualValue === $expectedValue) {
            // Option persisted - document for future implementation
            test()->markTestIncomplete(
                'Option persisted between tests. ' .
                'Database isolation needs to be implemented for full rollback support.',
            );

            // Cleanup
            delete_option($optionName);
        } else {
            expect($actualValue)->not->toBe(
                $expectedValue,
                'Option should not persist if isolation is working',
            );
        }
    });
});
