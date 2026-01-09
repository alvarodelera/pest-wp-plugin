<?php

declare(strict_types=1);

/**
 * Custom Pest Expectations for WordPress
 *
 * These expectations extend Pest's expect() API with WordPress-specific assertions.
 * They provide a fluent, readable way to test WordPress objects, metadata, and hooks.
 *
 * @see https://pestphp.com/docs/expectations
 * @see https://pestphp.com/docs/custom-expectations
 */

namespace PestWP;

use function PestWP\Expectations\registerErrorExpectations;
use function PestWP\Expectations\registerHookExpectations;
use function PestWP\Expectations\registerMetadataExpectations;
use function PestWP\Expectations\registerOptionsExpectations;
use function PestWP\Expectations\registerPostExpectations;
use function PestWP\Expectations\registerPostTypeExpectations;
use function PestWP\Expectations\registerRestAjaxExpectations;
use function PestWP\Expectations\registerShortcodeExpectations;
use function PestWP\Expectations\registerTermExpectations;
use function PestWP\Expectations\registerUserExpectations;

/**
 * Register custom WordPress expectations.
 *
 * This function is called automatically when the plugin loads and registers
 * all custom expectations from categorized files.
 */
function registerExpectations(): void
{
    registerPostExpectations();
    registerErrorExpectations();
    registerMetadataExpectations();
    registerHookExpectations();
    registerTermExpectations();
    registerUserExpectations();
    registerShortcodeExpectations();
    registerOptionsExpectations();
    registerPostTypeExpectations();
    registerRestAjaxExpectations();
}
