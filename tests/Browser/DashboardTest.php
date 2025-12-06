<?php

declare(strict_types=1);

/**
 * Example Browser Tests using Pest Browser Plugin with WP Admin Locators.
 *
 * These tests require a running WordPress instance.
 * Configure credentials with: vendor/bin/pest-setup-browser
 *
 * Run with: vendor/bin/pest --browser tests/Browser/
 */

use function PestWP\Functions\adminUrl;
use function PestWP\Functions\editorNoticeSelector;
use function PestWP\Functions\getBrowserConfig;
use function PestWP\Functions\loginUrl;
use function PestWP\Functions\newPostUrl;
use function PestWP\Functions\postTitleSelector;
use function PestWP\Functions\publishButtonSelector;

it('can access WordPress login page', function () {
    $this->browse(function ($browser) {
        $browser->visit(loginUrl())
            ->assertSee('Log In');
    });
})->skip('Requires running WordPress instance and Pest Browser plugin configured');

it('can log into WordPress dashboard', function () {
    $config = getBrowserConfig();

    $this->browse(function ($browser) use ($config) {
        $browser->visit(loginUrl())
            ->type('user_login', $config['admin_user'])
            ->type('user_pass', $config['admin_password'])
            ->press('Log In')
            ->waitForLocation('/wp-admin/')
            ->assertSee('Dashboard');
    });
})->skip('Requires running WordPress instance and Pest Browser plugin configured');

it('can create a new post using locators', function () {
    $config = getBrowserConfig();

    $this->browse(function ($browser) use ($config) {
        // Login first
        $browser->visit(loginUrl())
            ->type('user_login', $config['admin_user'])
            ->type('user_pass', $config['admin_password'])
            ->press('Log In')
            ->waitForLocation('/wp-admin/');

        // Navigate to new post using locator helper
        $browser->visit(newPostUrl())
            ->waitFor(postTitleSelector())
            ->type(postTitleSelector(), 'My Test Post')
            ->click(publishButtonSelector())
            ->click(publishButtonSelector()) // Confirm
            ->waitFor(editorNoticeSelector())
            ->assertSee('Post published');
    });
})->skip('Requires running WordPress instance and Pest Browser plugin configured');

it('can navigate to admin pages using helpers', function () {
    $config = getBrowserConfig();

    $this->browse(function ($browser) use ($config) {
        // Login
        $browser->visit(loginUrl())
            ->type('user_login', $config['admin_user'])
            ->type('user_pass', $config['admin_password'])
            ->press('Log In')
            ->waitForLocation('/wp-admin/');

        // Navigate to various admin pages using locator helpers
        $browser->visit(adminUrl('edit.php'))
            ->assertSee('Posts');

        $browser->visit(adminUrl('upload.php'))
            ->assertSee('Media Library');

        $browser->visit(adminUrl('users.php'))
            ->assertSee('Users');
    });
})->skip('Requires running WordPress instance and Pest Browser plugin configured');
