<?php

declare(strict_types=1);

namespace PestWP\Mock;

use Closure;
use RuntimeException;

/**
 * HTTP request mocking utility for WordPress.
 *
 * Allows mocking HTTP requests made via wp_remote_get, wp_remote_post, etc.
 * Intercepts requests by URL pattern and returns mocked responses.
 *
 * @example
 * ```php
 * // Mock a specific URL
 * mockHTTP()
 *     ->whenUrl('https://api.example.com/users')
 *     ->andReturn(['users' => []]);
 *
 * // Mock with pattern matching
 * mockHTTP()
 *     ->whenUrl('https://api.example.com/*')
 *     ->andReturn(['status' => 'ok']);
 *
 * // Mock with callback for dynamic responses
 * mockHTTP()
 *     ->whenUrl('https://api.example.com/user/*')
 *     ->andReturnUsing(function($url, $args) {
 *         $id = basename($url);
 *         return ['id' => $id, 'name' => 'User ' . $id];
 *     });
 *
 * // Simulate HTTP errors
 * mockHTTP()
 *     ->whenUrl('https://api.example.com/error')
 *     ->andReturnError('http_request_failed', 'Connection timeout');
 * ```
 */
final class HTTPMock
{
    /**
     * Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Registered URL mocks
     *
     * @var array<int, array{pattern: string, response: mixed, callback: Closure|null, error: array{code: string, message: string}|null, times: int|null, called: int}>
     */
    private array $mocks = [];

    /**
     * Whether mocking is active
     */
    private bool $active = true;

    /**
     * Request history
     *
     * @var array<int, array{url: string, args: array<string, mixed>, response: mixed}>
     */
    private array $requests = [];

    /**
     * Default response for unmatched requests
     */
    private mixed $defaultResponse = null;

    /**
     * Whether to use default response
     */
    private bool $hasDefault = false;

    /**
     * Current mock being built
     *
     * @var array{pattern: string, response: mixed, callback: Closure|null, error: array{code: string, message: string}|null, times: int|null, called: int}|null
     */
    private ?array $currentMock = null;

    private function __construct()
    {
        // Private constructor for singleton
    }

    /**
     * Get or create the singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Reset the singleton instance
     */
    public static function resetInstance(): void
    {
        if (self::$instance !== null) {
            self::$instance->reset();
        }
        self::$instance = null;
    }

    /**
     * Register a URL pattern to mock
     *
     * @return $this
     */
    public function whenUrl(string $pattern): self
    {
        $this->currentMock = [
            'pattern' => $pattern,
            'response' => null,
            'callback' => null,
            'error' => null,
            'times' => null,
            'called' => 0,
        ];

        return $this;
    }

    /**
     * Set the mock response body
     *
     * @param mixed $body Response body (will be JSON encoded if array/object)
     * @param int $statusCode HTTP status code
     * @param array<string, string> $headers Response headers
     * @return $this
     */
    public function andReturn(mixed $body, int $statusCode = 200, array $headers = []): self
    {
        if ($this->currentMock === null) {
            throw new RuntimeException('Must call whenUrl() before andReturn()');
        }

        $this->currentMock['response'] = $this->createResponse($body, $statusCode, $headers);
        $this->mocks[] = $this->currentMock;
        $this->currentMock = null;

        return $this;
    }

    /**
     * Set a callback for dynamic responses
     *
     * @param Closure(string, array<string, mixed>): mixed $callback
     * @return $this
     */
    public function andReturnUsing(Closure $callback): self
    {
        if ($this->currentMock === null) {
            throw new RuntimeException('Must call whenUrl() before andReturnUsing()');
        }

        $this->currentMock['callback'] = $callback;
        $this->mocks[] = $this->currentMock;
        $this->currentMock = null;

        return $this;
    }

    /**
     * Return a WP_Error for the request
     *
     * @return $this
     */
    public function andReturnError(string $code, string $message): self
    {
        if ($this->currentMock === null) {
            throw new RuntimeException('Must call whenUrl() before andReturnError()');
        }

        $this->currentMock['error'] = ['code' => $code, 'message' => $message];
        $this->mocks[] = $this->currentMock;
        $this->currentMock = null;

        return $this;
    }

    /**
     * Return a JSON response
     *
     * @param array<string, mixed>|object $data
     * @return $this
     */
    public function andReturnJson(array|object $data, int $statusCode = 200): self
    {
        return $this->andReturn($data, $statusCode, ['Content-Type' => 'application/json']);
    }

    /**
     * Limit how many times this mock should respond
     *
     * @return $this
     */
    public function times(int $count): self
    {
        if ($this->currentMock === null) {
            throw new RuntimeException('Must call whenUrl() before times()');
        }

        $this->currentMock['times'] = $count;

        return $this;
    }

    /**
     * Respond only once
     *
     * @return $this
     */
    public function once(): self
    {
        return $this->times(1);
    }

    /**
     * Set default response for unmatched requests
     *
     * @return $this
     */
    public function default(mixed $body, int $statusCode = 200): self
    {
        $this->defaultResponse = $this->createResponse($body, $statusCode);
        $this->hasDefault = true;

        return $this;
    }

    /**
     * Block all unmatched requests
     *
     * @return $this
     */
    public function blockUnmatched(): self
    {
        $this->defaultResponse = $this->createError('blocked', 'Request blocked by HTTPMock');
        $this->hasDefault = true;

        return $this;
    }

    /**
     * Allow unmatched requests to pass through
     *
     * @return $this
     */
    public function allowUnmatched(): self
    {
        $this->hasDefault = false;
        $this->defaultResponse = null;

        return $this;
    }

    /**
     * Handle an HTTP request
     *
     * @param string $url The request URL
     * @param array<string, mixed> $args Request arguments
     * @return mixed Response or null if not mocked
     */
    public function handle(string $url, array $args = []): mixed
    {
        if (! $this->active) {
            return null;
        }

        // Find matching mock
        foreach ($this->mocks as &$mock) {
            if ($this->matchesPattern($url, $mock['pattern'])) {
                // Check if mock has reached its limit
                if ($mock['times'] !== null && $mock['called'] >= $mock['times']) {
                    continue;
                }

                $mock['called']++;
                $response = $this->getMockResponse($mock, $url, $args);
                $this->recordRequest($url, $args, $response);

                return $response;
            }
        }

        // Use default response if set
        if ($this->hasDefault) {
            $this->recordRequest($url, $args, $this->defaultResponse);

            return $this->defaultResponse;
        }

        return null;
    }

    /**
     * Check if mocking is active
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Check if a URL would be mocked
     */
    public function willMock(string $url): bool
    {
        if (! $this->active) {
            return false;
        }

        foreach ($this->mocks as $mock) {
            if ($this->matchesPattern($url, $mock['pattern'])) {
                if ($mock['times'] === null || $mock['called'] < $mock['times']) {
                    return true;
                }
            }
        }

        return $this->hasDefault;
    }

    /**
     * Get request history
     *
     * @return array<int, array{url: string, args: array<string, mixed>, response: mixed}>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * Get the number of requests made
     */
    public function getRequestCount(): int
    {
        return count($this->requests);
    }

    /**
     * Check if a URL was requested
     */
    public function wasRequested(string $url): bool
    {
        foreach ($this->requests as $request) {
            if ($request['url'] === $url) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a URL pattern was requested
     */
    public function wasRequestedMatching(string $pattern): bool
    {
        foreach ($this->requests as $request) {
            if ($this->matchesPattern($request['url'], $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enable mocking
     *
     * @return $this
     */
    public function enable(): self
    {
        $this->active = true;

        return $this;
    }

    /**
     * Disable mocking
     *
     * @return $this
     */
    public function disable(): self
    {
        $this->active = false;

        return $this;
    }

    /**
     * Reset all mocks and history
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->mocks = [];
        $this->requests = [];
        $this->defaultResponse = null;
        $this->hasDefault = false;
        $this->currentMock = null;
        $this->active = true;

        return $this;
    }

    /**
     * Check if a URL matches a pattern
     */
    private function matchesPattern(string $url, string $pattern): bool
    {
        // Exact match
        if ($url === $pattern) {
            return true;
        }

        // Wildcard pattern (convert * to regex)
        $regex = str_replace(
            ['*', '/'],
            ['.*', '\/'],
            $pattern
        );

        return (bool) preg_match('/^' . $regex . '$/i', $url);
    }

    /**
     * Get the response for a mock
     *
     * @param array{pattern: string, response: mixed, callback: Closure|null, error: array{code: string, message: string}|null, times: int|null, called: int} $mock
     * @param array<string, mixed> $args
     */
    private function getMockResponse(array $mock, string $url, array $args): mixed
    {
        if ($mock['error'] !== null) {
            return $this->createError($mock['error']['code'], $mock['error']['message']);
        }

        if ($mock['callback'] !== null) {
            $result = ($mock['callback'])($url, $args);

            if (is_array($result) && isset($result['body'])) {
                return $result;
            }

            return $this->createResponse($result);
        }

        return $mock['response'];
    }

    /**
     * Create a WordPress HTTP response array
     *
     * @param mixed $body
     * @param array<string, string> $headers
     * @return array{headers: array<string, string>, body: string, response: array{code: int, message: string}, cookies: array<mixed>}
     */
    private function createResponse(mixed $body, int $statusCode = 200, array $headers = []): array
    {
        $responseBody = is_array($body) || is_object($body)
            ? json_encode($body, JSON_THROW_ON_ERROR)
            : (is_scalar($body) || $body === null ? (string) $body : '');

        return [
            'headers' => $headers,
            'body' => $responseBody,
            'response' => [
                'code' => $statusCode,
                'message' => $this->getStatusMessage($statusCode),
            ],
            'cookies' => [],
        ];
    }

    /**
     * Create a WP_Error-like response
     *
     * @return array{errors: array<string, array<string>>, error_data: array<string, mixed>}
     */
    private function createError(string $code, string $message): array
    {
        // Return a structure that can be detected as an error
        // In real WordPress, this would be a WP_Error object
        return [
            'errors' => [$code => [$message]],
            'error_data' => [],
        ];
    }

    /**
     * Record a request
     *
     * @param array<string, mixed> $args
     */
    private function recordRequest(string $url, array $args, mixed $response): void
    {
        $this->requests[] = [
            'url' => $url,
            'args' => $args,
            'response' => $response,
        ];
    }

    /**
     * Get HTTP status message
     */
    private function getStatusMessage(int $code): string
    {
        $messages = [
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
        ];

        return $messages[$code] ?? 'Unknown';
    }
}
