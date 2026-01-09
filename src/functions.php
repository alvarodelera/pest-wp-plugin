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
require_once __DIR__ . '/Functions/locators.php';
require_once __DIR__ . '/Functions/arch.php';
require_once __DIR__ . '/Functions/rest.php';
require_once __DIR__ . '/Functions/ajax.php';
require_once __DIR__ . '/Functions/nonce.php';
require_once __DIR__ . '/Functions/mock.php';

// Load REST and AJAX classes
require_once __DIR__ . '/Rest/RestResponse.php';
require_once __DIR__ . '/Rest/RestClient.php';
require_once __DIR__ . '/Ajax/AjaxResponse.php';
require_once __DIR__ . '/Ajax/AjaxClient.php';

// Load Mock, Fixture, and Snapshot classes
require_once __DIR__ . '/Mock/FunctionMock.php';
require_once __DIR__ . '/Mock/HookMock.php';
require_once __DIR__ . '/Mock/HTTPMock.php';
require_once __DIR__ . '/Mock/TimeMock.php';
require_once __DIR__ . '/Fixtures/FixtureManager.php';
require_once __DIR__ . '/Snapshot/SnapshotManager.php';

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
require_once __DIR__ . '/Expectations/rest-ajax.php';
require_once __DIR__ . '/Expectations/mocking.php';

// Load and register custom expectations
require_once __DIR__ . '/Expectations.php';
\PestWP\registerExpectations();

// Load and register architecture expectations
require_once __DIR__ . '/Arch/WordPressArchPreset.php';
require_once __DIR__ . '/Arch/WordPressArchHelper.php';
require_once __DIR__ . '/Arch/Expectations.php';
\PestWP\Arch\Expectations::register();
