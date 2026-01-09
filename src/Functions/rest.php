<?php

declare(strict_types=1);

/**
 * REST API testing helper functions.
 *
 * Provides convenient functions for testing WordPress REST API endpoints.
 */

namespace PestWP\Functions;

use PestWP\Rest\RestClient;
use PestWP\Rest\RestResponse;

/**
 * Create a new REST client for testing REST API endpoints.
 *
 * Usage:
 *   rest()->get('/wp/v2/posts');
 *   rest()->as($admin)->post('/wp/v2/posts', ['title' => 'Test']);
 *   rest()->withNonce()->delete('/wp/v2/posts/1');
 *
 * @return RestClient
 */
function rest(): RestClient
{
    return new RestClient();
}

/**
 * Make a GET request to a REST endpoint.
 *
 * Shorthand for rest()->get($route, $params).
 *
 * @param string $route The REST route (e.g., '/wp/v2/posts')
 * @param array<string, mixed> $params Query parameters
 */
function restGet(string $route, array $params = []): RestResponse
{
    return rest()->get($route, $params);
}

/**
 * Make a POST request to a REST endpoint.
 *
 * Shorthand for rest()->post($route, $data).
 *
 * @param string $route The REST route
 * @param array<string, mixed> $data Request body data
 */
function restPost(string $route, array $data = []): RestResponse
{
    return rest()->post($route, $data);
}

/**
 * Make a PUT request to a REST endpoint.
 *
 * Shorthand for rest()->put($route, $data).
 *
 * @param string $route The REST route
 * @param array<string, mixed> $data Request body data
 */
function restPut(string $route, array $data = []): RestResponse
{
    return rest()->put($route, $data);
}

/**
 * Make a PATCH request to a REST endpoint.
 *
 * Shorthand for rest()->patch($route, $data).
 *
 * @param string $route The REST route
 * @param array<string, mixed> $data Request body data
 */
function restPatch(string $route, array $data = []): RestResponse
{
    return rest()->patch($route, $data);
}

/**
 * Make a DELETE request to a REST endpoint.
 *
 * Shorthand for rest()->delete($route, $params).
 *
 * @param string $route The REST route
 * @param array<string, mixed> $params Query parameters
 */
function restDelete(string $route, array $params = []): RestResponse
{
    return rest()->delete($route, $params);
}

/**
 * Check if a REST route exists.
 *
 * @param string $route The REST route to check
 * @param string|null $method Optional HTTP method to check
 */
function restRouteExists(string $route, ?string $method = null): bool
{
    return rest()->routeExists($route, $method);
}

/**
 * Get all registered REST routes.
 *
 * @return array<string, array<int, array<string, mixed>>>
 */
function restRoutes(): array
{
    return rest()->routes();
}

/**
 * Get REST routes for a specific namespace.
 *
 * @param string $namespace The namespace (e.g., 'wp/v2', 'my-plugin/v1')
 * @return array<string, array<int, array<string, mixed>>>
 */
function restRoutesForNamespace(string $namespace): array
{
    return rest()->routesForNamespace($namespace);
}
