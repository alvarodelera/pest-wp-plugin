<?php

declare(strict_types=1);

/**
 * Test to verify transaction/snapshot isolation between tests
 */
describe('Transaction Debug', function (): void {
    it('test 1: creates a post and checks level', function (): void {
        $postId = wp_insert_post([
            'post_title' => 'Debug Post - ' . uniqid(),
            'post_content' => 'Content',
            'post_status' => 'publish',
        ]);

        // Store post ID for next test
        file_put_contents(sys_get_temp_dir() . '/pest_debug_post_id.txt', (string) $postId);

        expect($postId)->toBeGreaterThan(0);
    });

    it('test 2: checks if previous post exists', function (): void {
        global $wpdb;

        $postId = (int) file_get_contents(sys_get_temp_dir() . '/pest_debug_post_id.txt');

        wp_cache_flush();
        $post = get_post($postId);

        // Direct SQL check
        $directCheck = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE ID = {$postId}");

        expect($post)->toBeNull('Post should have been rolled back');
        expect($directCheck)->toBeNull('Post should not exist in database');
    });
});
