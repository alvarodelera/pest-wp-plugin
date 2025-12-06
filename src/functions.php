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

// Load and register custom expectations
require_once __DIR__ . '/Expectations.php';
\PestWP\registerExpectations();
