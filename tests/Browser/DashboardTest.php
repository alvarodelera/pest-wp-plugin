<?php

declare(strict_types=1);

/**
 * Example Browser Tests using Pest Browser Plugin with WP Admin Locators.
 *
 * These tests require a running WordPress instance.
 * Configure credentials with: vendor/bin/pest-setup-browser
 *
 * Run with: vendor/bin/pest --browser tests/Browser/
 *
 * Pest Browser Plugin uses visit() which returns a $page object.
 * @see https://pestphp.com/docs/browser-testing
 */

use function PestWP\Functions\adminUrl;
use function PestWP\Functions\getBrowserConfig;
use function PestWP\Functions\loginUrl;
use function PestWP\Functions\newPostUrl;
use function PestWP\Functions\postTitleSelector;
use function PestWP\Functions\publishButtonSelector;

it('can access WordPress login page', function () {
    $config = getBrowserConfig();

    visit($config['base_url'] . loginUrl())
        ->assertSee('Log In');
})->skip('Requires running WordPress instance and Pest Browser plugin configured');

it('can log into WordPress dashboard', function () {
    $config = getBrowserConfig();

    visit($config['base_url'] . loginUrl())
        ->type('user_login', $config['admin_user'])
        ->type('user_pass', $config['admin_password'])
        ->press('Log In')
        ->assertPathBeginsWith('/wp-admin')
        ->assertSee('Dashboard');
})->skip('Requires running WordPress instance and Pest Browser plugin configured');

it('can create a new post using locators', function () {
    $config = getBrowserConfig();

    // Login first
    visit($config['base_url'] . loginUrl())
        ->type('user_login', $config['admin_user'])
        ->type('user_pass', $config['admin_password'])
        ->press('Log In')
        ->assertPathBeginsWith('/wp-admin');

    // Navigate to new post using locator helper
    visit($config['base_url'] . newPostUrl())
        ->wait(2) // Wait for Gutenberg editor to load
        ->type(postTitleSelector(), 'My Test Post')
        ->click(publishButtonSelector())
        ->wait(1)
        ->click(publishButtonSelector()) // Confirm
        ->assertSee('Post published');
})->skip('Requires running WordPress instance and Pest Browser plugin configured');

it('can navigate to admin pages using helpers', function () {
    $config = getBrowserConfig();

    // Login
    visit($config['base_url'] . loginUrl())
        ->type('user_login', $config['admin_user'])
        ->type('user_pass', $config['admin_password'])
        ->press('Log In')
        ->assertPathBeginsWith('/wp-admin');

    // Navigate to various admin pages using locator helpers
    visit($config['base_url'] . adminUrl('edit.php'))
        ->assertSee('Posts');

    visit($config['base_url'] . adminUrl('upload.php'))
        ->assertSee('Media Library');

    visit($config['base_url'] . adminUrl('users.php'))
        ->assertSee('Users');
})->skip('Requires running WordPress instance and Pest Browser plugin configured');
