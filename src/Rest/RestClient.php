<?php

declare(strict_types=1);

namespace PestWP\Rest;

use RuntimeException;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

/**
 * REST API Client for testing WordPress REST endpoints.
 *
 * Provides a fluent interface for making REST API requests in tests,
 * simulating authenticated and unauthenticated requests.
 *
 * Usage:
 *   rest()->get('/wp/v2/posts');
 *   rest()->as($admin)->post('/wp/v2/posts', ['title' => 'Test']);
 *   rest()->withHeader('X-Custom', 'value')->get('/my-plugin/v1/items');
 */
final class RestClient
{
    /**
     * @var int|null User ID for authenticated requests
     */
    private ?int $userId = null;

    /**
     * @var array<string, string> Custom headers for request
     */
    private array $headers = [];

    /**
     * @var array<string, mixed> Query parameters
     */
    private array $queryParams = [];

    /**
     * @var string|null Nonce for request
     */
    private ?string $nonce = null;

    /**
     * @var string|null Nonce action name
     */
    private ?string $nonceAction = null;

    /**
     * Create a new REST client instance.
     */
    public function __construct()
    {
        $this->ensureRestApiAvailable();
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
     * Add a custom header to the request.
     *
     * @return static
     */
    public function withHeader(string $name, string $value): static
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    /**
     * Add multiple custom headers to the request.
     *
     * @param array<string, string> $headers
     * @return static
     */
    public function withHeaders(array $headers): static
    {
        $clone = clone $this;
        $clone->headers = array_merge($clone->headers, $headers);

        return $clone;
    }

    /**
     * Add query parameters to the request.
     *
     * @param array<string, mixed> $params
     * @return static
     */
    public function withQuery(array $params): static
    {
        $clone = clone $this;
        $clone->queryParams = array_merge($clone->queryParams, $params);

        return $clone;
    }

    /**
     * Include a nonce with the request.
     *
     * @param string $action Nonce action name (default: 'wp_rest')
     * @return static
     */
    public function withNonce(string $action = 'wp_rest'): static
    {
        $clone = clone $this;
        $clone->nonceAction = $action;
        $clone->nonce = null; // Will be generated at request time

        return $clone;
    }

    /**
     * Include a specific nonce value with the request.
     *
     * @return static
     */
    public function withNonceValue(string $nonce): static
    {
        $clone = clone $this;
        $clone->nonce = $nonce;
        $clone->nonceAction = null;

        return $clone;
    }

    /**
     * Make a GET request.
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function get(string $route, array $params = []): RestResponse
    {
        return $this->request('GET', $route, $params);
    }

    /**
     * Make a POST request.
     *
     * @param array<string, mixed> $data Request body data
     */
    public function post(string $route, array $data = []): RestResponse
    {
        return $this->request('POST', $route, $data);
    }

    /**
     * Make a PUT request.
     *
     * @param array<string, mixed> $data Request body data
     */
    public function put(string $route, array $data = []): RestResponse
    {
        return $this->request('PUT', $route, $data);
    }

    /**
     * Make a PATCH request.
     *
     * @param array<string, mixed> $data Request body data
     */
    public function patch(string $route, array $data = []): RestResponse
    {
        return $this->request('PATCH', $route, $data);
    }

    /**
     * Make a DELETE request.
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function delete(string $route, array $params = []): RestResponse
    {
        return $this->request('DELETE', $route, $params);
    }

    /**
     * Make a custom method request.
     *
     * @param array<string, mixed> $data Request data
     */
    public function request(string $method, string $route, array $data = []): RestResponse
    {
        $this->ensureRestApiAvailable();

        $previousUser = get_current_user_id();

        try {
            // Set user for this request if specified
            if ($this->userId !== null) {
                wp_set_current_user($this->userId);
            }

            $request = $this->buildRequest($method, $route, $data);
            $response = $this->dispatchRequest($request);

            return RestResponse::fromWpRestResponse($response);
        } finally {
            // Restore previous user
            wp_set_current_user($previousUser);
        }
    }

    /**
     * Check if a REST route exists.
     */
    public function routeExists(string $route, ?string $method = null): bool
    {
        $this->ensureRestApiAvailable();

        $server = rest_get_server();
        $routes = $server->get_routes();

        // Normalize route
        $route = '/' . ltrim($route, '/');

        // Check if route exists
        if (! isset($routes[$route])) {
            // Try pattern matching for routes with parameters
            foreach (array_keys($routes) as $registeredRoute) {
                $pattern = $this->routeToPattern($registeredRoute);
                if (preg_match($pattern, $route)) {
                    if ($method === null) {
                        return true;
                    }
                    // Check method
                    $endpoints = $routes[$registeredRoute];
                    foreach ($endpoints as $endpoint) {
                        if (isset($endpoint['methods'][$method])) {
                            return true;
                        }
                    }
                }
            }

            return false;
        }

        if ($method === null) {
            return true;
        }

        // Check if route supports the specified method
        $endpoints = $routes[$route];
        foreach ($endpoints as $endpoint) {
            if (isset($endpoint['methods'][$method])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all registered REST routes.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function routes(): array
    {
        $this->ensureRestApiAvailable();

        $server = rest_get_server();

        /** @var array<string, array<int, array<string, mixed>>> */
        return $server->get_routes();
    }

    /**
     * Get routes matching a namespace.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function routesForNamespace(string $namespace): array
    {
        $this->ensureRestApiAvailable();

        $server = rest_get_server();
        $allRoutes = $server->get_routes();
        $routes = [];

        $prefix = '/' . ltrim($namespace, '/');

        foreach ($allRoutes as $route => $endpoints) {
            if (str_starts_with($route, $prefix)) {
                $routes[$route] = $endpoints;
            }
        }

        /** @var array<string, array<int, array<string, mixed>>> */
        return $routes;
    }

    /**
     * Build a WP_REST_Request from the given parameters.
     *
     * @param array<string, mixed> $data
     * @return WP_REST_Request<array<string, mixed>>
     */
    private function buildRequest(string $method, string $route, array $data): WP_REST_Request
    {
        // Normalize route
        $route = '/' . ltrim($route, '/');

        $request = new WP_REST_Request($method, $route);

        // Set headers
        foreach ($this->headers as $name => $value) {
            $request->set_header($name, $value);
        }

        // Set nonce if configured
        if ($this->nonceAction !== null) {
            $nonce = wp_create_nonce($this->nonceAction);
            $request->set_header('X-WP-Nonce', $nonce);
        } elseif ($this->nonce !== null) {
            $request->set_header('X-WP-Nonce', $this->nonce);
        }

        // Set parameters based on method
        if (in_array($method, ['GET', 'DELETE', 'HEAD'], true)) {
            // Query parameters
            $params = array_merge($this->queryParams, $data);
            foreach ($params as $key => $value) {
                $request->set_param($key, $value);
            }
        } else {
            // Body parameters
            $request->set_body_params($data);

            // Also set query params if any
            foreach ($this->queryParams as $key => $value) {
                $request->set_query_params(array_merge($request->get_query_params(), [$key => $value]));
            }
        }

        return $request;
    }

    /**
     * Dispatch the request and get response.
     *
     * @param WP_REST_Request<array<string, mixed>> $request
     */
    private function dispatchRequest(WP_REST_Request $request): WP_REST_Response
    {
        $server = rest_get_server();

        // Dispatch the request
        $response = $server->dispatch($request);

        // Ensure we have a WP_REST_Response
        if (! $response instanceof WP_REST_Response) {
            $response = rest_ensure_response($response);
        }

        // The return type of rest_ensure_response can be WP_REST_Response|WP_Error
        // but we've already ensured it's a WP_REST_Response above
        if (! $response instanceof WP_REST_Response) {
            throw new RuntimeException('Failed to dispatch REST request');
        }

        return $response;
    }

    /**
     * Convert a route pattern to a regex pattern.
     */
    private function routeToPattern(string $route): string
    {
        // Escape regex special chars except for route parameter placeholders
        $pattern = preg_quote($route, '#');

        // Convert (?P<name>...) patterns back
        $pattern = (string) preg_replace(
            '/\\\\\(\\\\\?P<([^>]+)>[^\)]+\\\\\)/',
            '(?P<$1>[^/]+)',
            $pattern,
        );

        return '#^' . $pattern . '$#';
    }

    /**
     * Ensure the REST API is available.
     *
     * @throws RuntimeException If WordPress REST API is not available
     */
    private function ensureRestApiAvailable(): void
    {
        if (! function_exists('rest_get_server')) {
            throw new RuntimeException(
                'WordPress REST API is not available. Ensure WordPress is loaded and REST API is initialized.',
            );
        }
    }
}
