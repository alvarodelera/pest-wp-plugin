<?php

declare(strict_types=1);

/**
 * Example Browser Tests using Pest Browser Plugin.
 *
 * These tests require a running WordPress instance.
 * Configure credentials with: vendor/bin/pest-setup-browser
 *
 * Run with: vendor/bin/pest --browser tests/Browser/
 */

use function PestWP\Functions\getBrowserConfig;

it('can access WordPress login page', function () {
    $config = getBrowserConfig();

    $this->browse(function ($browser) use ($config) {
        $browser->visit($config['base_url'] . '/wp-login.php')
            ->assertSee('Log In');
    });
})->skip('Requires running WordPress instance and Pest Browser plugin configured');

it('can log into WordPress dashboard', function () {
    $config = getBrowserConfig();

    $this->browse(function ($browser) use ($config) {
        $browser->visit($config['base_url'] . '/wp-login.php')
            ->type('user_login', $config['admin_user'])
            ->type('user_pass', $config['admin_password'])
            ->press('Log In')
            ->waitForLocation('/wp-admin/')
            ->assertSee('Dashboard');
    });
})->skip('Requires running WordPress instance and Pest Browser plugin configured');
