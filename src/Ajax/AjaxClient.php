<?php

declare(strict_types=1);

namespace PestWP\Ajax;

use RuntimeException;
use WP_User;

/**
 * AJAX Client for testing WordPress admin-ajax.php handlers.
 *
 * Provides a fluent interface for testing AJAX actions without HTTP requests,
 * simulating the complete AJAX request lifecycle.
 *
 * Usage:
 *   ajax('my_action', ['param' => 'value']);
 *   ajax()->as($admin)->action('my_action', $data);
 *   ajax()->withNonce('my_nonce_action')->action('my_action');
 */
final class AjaxClient
{
    /**
     * @var int|null User ID for authenticated requests
     */
    private ?int $userId = null;

    /**
     * @var string|null Nonce value
     */
    private ?string $nonce = null;

    /**
     * @var string|null Nonce action for automatic generation
     */
    private ?string $nonceAction = null;

    /**
     * @var string Nonce field name in POST data
     */
    private string $nonceField = '_wpnonce';

    /**
     * @var bool Whether this is an admin AJAX request (wp-admin context)
     */
    private bool $isAdmin = true;

    /**
     * @var array<string, string> Custom $_SERVER variables
     */
    private array $serverVars = [];

    /**
     * Create a new AJAX client instance.
     */
    public function __construct()
    {
        $this->ensureAjaxAvailable();
    }

    /**
     * Set the user for authenticated requests.
     *
     * @param int|WP_User $user User ID or WP_User object
     * @return static
     */
    public function as(int|WP_User $user): static
    {
        $clone = clone $this;
        $clone->userId = $user instanceof WP_User ? $user->ID : $user;

        return $clone;
    }

    /**
     * Alias for as() - authenticate as a user.
     *
     * @param int|WP_User $user User ID or WP_User object
     * @return static
     */
    public function actingAs(int|WP_User $user): static
    {
        return $this->as($user);
    }

    /**
     * Include a nonce with the request (auto-generated).
     *
     * @param string $action Nonce action name
     * @param string $field Field name for nonce in POST data (default: '_wpnonce')
     * @return static
     */
    public function withNonce(string $action, string $field = '_wpnonce'): static
    {
        $clone = clone $this;
        $clone->nonceAction = $action;
        $clone->nonceField = $field;
        $clone->nonce = null;

        return $clone;
    }

    /**
     * Include a specific nonce value with the request.
     *
     * @param string $nonce The nonce value
     * @param string $field Field name for nonce in POST data (default: '_wpnonce')
     * @return static
     */
    public function withNonceValue(string $nonce, string $field = '_wpnonce'): static
    {
        $clone = clone $this;
        $clone->nonce = $nonce;
        $clone->nonceField = $field;
        $clone->nonceAction = null;

        return $clone;
    }

    /**
     * Set whether this is an admin context request.
     *
     * @return static
     */
    public function admin(bool $isAdmin = true): static
    {
        $clone = clone $this;
        $clone->isAdmin = $isAdmin;

        return $clone;
    }

    /**
     * Set this as a front-end (nopriv) AJAX request.
     *
     * @return static
     */
    public function nopriv(): static
    {
        return $this->admin(false);
    }

    /**
     * Set custom $_SERVER variable.
     *
     * @return static
     */
    public function withServerVar(string $name, string $value): static
    {
        $clone = clone $this;
        $clone->serverVars[$name] = $value;

        return $clone;
    }

    /**
     * Execute an AJAX action.
     *
     * @param string $action The AJAX action name
     * @param array<string, mixed> $data POST data to send
     */
    public function action(string $action, array $data = []): AjaxResponse
    {
        return $this->dispatch($action, $data);
    }

    /**
     * Execute an AJAX action (alias for action()).
     *
     * @param string $action The AJAX action name
     * @param array<string, mixed> $data POST data to send
     */
    public function dispatch(string $action, array $data = []): AjaxResponse
    {
        $this->ensureAjaxAvailable();

        $previousUser = get_current_user_id();
        $previousPost = $_POST;
        $previousGet = $_GET;
        $previousRequest = $_REQUEST;
        $previousServer = $_SERVER;

        try {
            // Set user for this request if specified
            if ($this->userId !== null) {
                wp_set_current_user($this->userId);
            }

            // Prepare POST data
            $postData = $data;
            $postData['action'] = $action;

            // Add nonce if configured
            if ($this->nonceAction !== null) {
                $postData[$this->nonceField] = wp_create_nonce($this->nonceAction);
            } elseif ($this->nonce !== null) {
                $postData[$this->nonceField] = $this->nonce;
            }

            // Set up superglobals
            $_POST = $postData;
            $_GET = ['action' => $action];
            $_REQUEST = array_merge($_GET, $_POST);

            // Set up server vars
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

            foreach ($this->serverVars as $name => $value) {
                $_SERVER[$name] = $value;
            }

            // Define DOING_AJAX if not already defined
            if (! defined('DOING_AJAX')) {
                define('DOING_AJAX', true);
            }

            // Capture output
            ob_start();

            try {
                // Trigger the appropriate hook
                $hookPrefix = $this->isAdmin ? 'wp_ajax_' : 'wp_ajax_nopriv_';
                do_action($hookPrefix . $action);
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }

            $output = ob_get_clean();

            if ($output === false) {
                $output = '';
            }

            return AjaxResponse::fromOutput($output);
        } finally {
            // Restore previous state
            wp_set_current_user($previousUser);
            $_POST = $previousPost;
            $_GET = $previousGet;
            $_REQUEST = $previousRequest;
            $_SERVER = $previousServer;
        }
    }

    /**
     * Check if an AJAX action is registered.
     *
     * @param string $action The action name
     * @param bool $admin Check admin (logged-in) handlers (default: true)
     * @param bool $nopriv Check nopriv (logged-out) handlers (default: true)
     */
    public function hasAction(string $action, bool $admin = true, bool $nopriv = true): bool
    {
        $this->ensureAjaxAvailable();

        if ($admin && has_action('wp_ajax_' . $action)) {
            return true;
        }

        if ($nopriv && has_action('wp_ajax_nopriv_' . $action)) {
            return true;
        }

        return false;
    }

    /**
     * Check if an AJAX action has admin (logged-in) handler.
     */
    public function hasAdminAction(string $action): bool
    {
        return $this->hasAction($action, true, false);
    }

    /**
     * Check if an AJAX action has nopriv (logged-out) handler.
     */
    public function hasNoprivAction(string $action): bool
    {
        return $this->hasAction($action, false, true);
    }

    /**
     * Get all registered AJAX actions.
     *
     * @return array{admin: list<string>, nopriv: list<string>}
     */
    public function registeredActions(): array
    {
        $this->ensureAjaxAvailable();

        global $wp_filter;

        $actions = ['admin' => [], 'nopriv' => []];

        if (! is_array($wp_filter)) {
            return $actions;
        }

        foreach (array_keys($wp_filter) as $tag) {
            if (str_starts_with($tag, 'wp_ajax_nopriv_')) {
                $actions['nopriv'][] = substr($tag, 16); // Remove 'wp_ajax_nopriv_' prefix
            } elseif (str_starts_with($tag, 'wp_ajax_')) {
                $actions['admin'][] = substr($tag, 8); // Remove 'wp_ajax_' prefix
            }
        }

        $actions['admin'] = array_values(array_unique($actions['admin']));
        $actions['nopriv'] = array_values(array_unique($actions['nopriv']));

        return $actions;
    }

    /**
     * Ensure WordPress AJAX functionality is available.
     *
     * @throws RuntimeException If WordPress AJAX is not available
     */
    private function ensureAjaxAvailable(): void
    {
        if (! function_exists('do_action')) {
            throw new RuntimeException(
                'WordPress is not available. Ensure WordPress is loaded for AJAX testing.'
            );
        }
    }
}
