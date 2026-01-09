<?php

declare(strict_types=1);

/**
 * Browser Tests using Pest Browser Plugin with WP Admin Locators.
 *
 * These tests require a running WordPress instance.
 *
 * Quick setup:
 *   1. Start WordPress: vendor/bin/pest-wp-serve
 *   2. Configure browser: vendor/bin/pest-setup-browser --url http://localhost:8080 --pass password
 *   3. Run tests: vendor/bin/pest --browser tests/Browser/
 *
 * @see https://pestphp.com/docs/browser-testing
 */

use function PestWP\Functions\adminUrl;
use function PestWP\Functions\getBrowserConfig;
use function PestWP\Functions\hasBrowserAuthState;
use function PestWP\Functions\loginUrl;
use function PestWP\Functions\newPostUrl;
use function PestWP\Functions\postTitleSelector;
use function PestWP\Functions\publishButtonSelector;

/**
 * Check if browser testing is configured and WordPress is accessible.
 */
function browserTestsEnabled(): bool
{
    // Check for explicit skip flag
    if (getenv('PEST_SKIP_BROWSER') === 'true' || ($_ENV['PEST_SKIP_BROWSER'] ?? '') === 'true') {
        return false;
    }

    // Check if browser testing is explicitly enabled
    if (getenv('PEST_BROWSER_TESTS') === 'true' || ($_ENV['PEST_BROWSER_TESTS'] ?? '') === 'true') {
        return true;
    }

    // Check if a WordPress URL is configured
    $config = getBrowserConfig();

    if (empty($config['base_url'])) {
        return false;
    }

    // Try to connect to WordPress (with short timeout)
    $context = stream_context_create([
        'http' => [
            'timeout' => 2,
            'ignore_errors' => true,
        ],
    ]);

    $result = @file_get_contents($config['base_url'], false, $context);

    return $result !== false;
}

/**
 * Get skip reason for browser tests.
 */
function browserTestsSkipReason(): string
{
    if (getenv('PEST_SKIP_BROWSER') === 'true' || ($_ENV['PEST_SKIP_BROWSER'] ?? '') === 'true') {
        return 'Browser tests disabled via PEST_SKIP_BROWSER environment variable';
    }

    $config = getBrowserConfig();

    if (empty($config['base_url'])) {
        return 'No WordPress URL configured. Run: vendor/bin/pest-setup-browser --url <url> --pass <password>';
    }

    return 'WordPress not accessible at ' . $config['base_url'] . '. Start server with: vendor/bin/pest-wp-serve';
}

// Conditionally skip all browser tests in this file if WordPress is not available
beforeAll(function () {
    if (! browserTestsEnabled()) {
        test()->markTestSkipped(browserTestsSkipReason());
    }
});

describe('WordPress Login', function () {
    it('can access WordPress login page', function () {
        $config = getBrowserConfig();

        visit($config['base_url'] . loginUrl())
            ->assertSee('Log In');
    });

    it('can log into WordPress dashboard', function () {
        $config = getBrowserConfig();

        visit($config['base_url'] . loginUrl())
            ->type('user_login', $config['admin_user'])
            ->type('user_pass', $config['admin_password'])
            ->press('Log In')
            ->assertPathBeginsWith('/wp-admin')
            ->assertSee('Dashboard');
    });
});

describe('WordPress Admin Navigation', function () {
    beforeEach(function () {
        $config = getBrowserConfig();

        // Login before each test
        visit($config['base_url'] . loginUrl())
            ->type('user_login', $config['admin_user'])
            ->type('user_pass', $config['admin_password'])
            ->press('Log In')
            ->assertPathBeginsWith('/wp-admin');
    });

    it('can navigate to Posts page', function () {
        $config = getBrowserConfig();

        visit($config['base_url'] . adminUrl('edit.php'))
            ->assertSee('Posts');
    });

    it('can navigate to Media Library', function () {
        $config = getBrowserConfig();

        visit($config['base_url'] . adminUrl('upload.php'))
            ->assertSee('Media Library');
    });

    it('can navigate to Users page', function () {
        $config = getBrowserConfig();

        visit($config['base_url'] . adminUrl('users.php'))
            ->assertSee('Users');
    });
});

describe('WordPress Post Creation', function () {
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
            ->type(postTitleSelector(), 'PestWP Test Post - ' . time())
            ->click(publishButtonSelector())
            ->wait(1)
            ->click(publishButtonSelector()) // Confirm
            ->assertSee('Post published');
    });
});
