<?php

declare(strict_types=1);

/**
 * Pest Plugin for WordPress
 *
 * Main entry point that loads all helper functions.
 * Functions are organized by category in separate files.
 */

// Load function categories
require_once __DIR__ . '/Functions/helpers.php';
require_once __DIR__ . '/Functions/factories.php';
require_once __DIR__ . '/Functions/auth.php';
require_once __DIR__ . '/Functions/browser.php';

// Load expectation categories
require_once __DIR__ . '/Expectations/posts.php';
require_once __DIR__ . '/Expectations/errors.php';
require_once __DIR__ . '/Expectations/metadata.php';
require_once __DIR__ . '/Expectations/hooks.php';
require_once __DIR__ . '/Expectations/terms.php';
require_once __DIR__ . '/Expectations/users.php';
require_once __DIR__ . '/Expectations/shortcodes.php';
require_once __DIR__ . '/Expectations/options.php';
require_once __DIR__ . '/Expectations/post-types.php';

// Load and register custom expectations
require_once __DIR__ . '/Expectations.php';
\PestWP\registerExpectations();
