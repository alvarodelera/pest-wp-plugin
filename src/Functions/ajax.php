<?php

declare(strict_types=1);

/**
 * AJAX testing helper functions.
 *
 * Provides convenient functions for testing WordPress admin-ajax.php handlers.
 */

namespace PestWP\Functions;

use PestWP\Ajax\AjaxClient;
use PestWP\Ajax\AjaxResponse;

/**
 * Create a new AJAX client or execute an AJAX action directly.
 *
 * Usage:
 *   ajax()->action('my_action', ['param' => 'value']);
 *   ajax()->as($admin)->withNonce('my_nonce')->action('my_action');
 *   ajax('my_action', ['data' => 'value']); // Shorthand
 *
 * @param string|null $action Optional action name for direct execution
 * @param array<string, mixed> $data Optional data for direct execution
 * @return AjaxClient|AjaxResponse Returns client if no action, response if action provided
 */
function ajax(?string $action = null, array $data = []): AjaxClient|AjaxResponse
{
    $client = new AjaxClient();

    if ($action !== null) {
        return $client->action($action, $data);
    }

    return $client;
}

/**
 * Execute an AJAX action as an admin user.
 *
 * @param string $action The AJAX action name
 * @param array<string, mixed> $data POST data
 */
function ajaxAdmin(string $action, array $data = []): AjaxResponse
{
    $client = new AjaxClient();

    return $client->admin()->action($action, $data);
}

/**
 * Execute an AJAX action as a non-logged-in user (nopriv).
 *
 * @param string $action The AJAX action name
 * @param array<string, mixed> $data POST data
 */
function ajaxNopriv(string $action, array $data = []): AjaxResponse
{
    $client = new AjaxClient();

    return $client->nopriv()->action($action, $data);
}

/**
 * Check if an AJAX action handler is registered.
 *
 * @param string $action The action name
 * @param bool $admin Check admin handlers (default: true)
 * @param bool $nopriv Check nopriv handlers (default: true)
 */
function hasAjaxAction(string $action, bool $admin = true, bool $nopriv = true): bool
{
    $client = new AjaxClient();

    return $client->hasAction($action, $admin, $nopriv);
}

/**
 * Get all registered AJAX actions.
 *
 * @return array{admin: list<string>, nopriv: list<string>}
 */
function registeredAjaxActions(): array
{
    $client = new AjaxClient();

    return $client->registeredActions();
}
