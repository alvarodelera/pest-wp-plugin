<?php

declare(strict_types=1);

/**
 * REST API and AJAX Expectations for Pest.
 *
 * Provides custom expectations for testing REST API endpoints and AJAX handlers.
 */

namespace PestWP\Expectations;

use Pest\Expectation;
use PestWP\Rest\RestResponse;
use PestWP\Ajax\AjaxResponse;

/**
 * Register REST API and AJAX expectations.
 */
function registerRestAjaxExpectations(): void
{
    // REST Response expectations

    /**
     * Assert that a REST response has a specific status code.
     */
    expect()->extend('toHaveStatus', function (int $status): Expectation {
        /** @var Expectation<RestResponse|AjaxResponse> $this */
        $value = $this->value;

        if ($value instanceof RestResponse) {
            expect($value->status())->toBe($status);
        } elseif ($value instanceof AjaxResponse) {
            expect($value->statusCode())->toBe($status);
        } else {
            throw new \InvalidArgumentException('Expected RestResponse or AjaxResponse');
        }

        return $this;
    });

    /**
     * Assert that a REST response is successful (2xx status code).
     */
    expect()->extend('toBeSuccessful', function (): Expectation {
        /** @var Expectation<RestResponse|AjaxResponse> $this */
        $value = $this->value;

        if ($value instanceof RestResponse) {
            expect($value->isSuccessful())->toBeTrue(
                "Expected response to be successful, got status {$value->status()}"
            );
        } elseif ($value instanceof AjaxResponse) {
            expect($value->isSuccess())->toBeTrue(
                'Expected AJAX response to be successful'
            );
        } else {
            throw new \InvalidArgumentException('Expected RestResponse or AjaxResponse');
        }

        return $this;
    });

    /**
     * Assert that a REST response is an error (4xx or 5xx).
     */
    expect()->extend('toBeError', function (): Expectation {
        /** @var Expectation<RestResponse|AjaxResponse> $this */
        $value = $this->value;

        if ($value instanceof RestResponse) {
            expect($value->isError())->toBeTrue(
                "Expected response to be an error, got status {$value->status()}"
            );
        } elseif ($value instanceof AjaxResponse) {
            expect($value->isError())->toBeTrue(
                'Expected AJAX response to be an error'
            );
        } else {
            throw new \InvalidArgumentException('Expected RestResponse or AjaxResponse');
        }

        return $this;
    });

    /**
     * Assert that a REST response contains specific data.
     */
    expect()->extend('toHaveResponseData', function (string $key, mixed $value = null): Expectation {
        /** @var Expectation<RestResponse|AjaxResponse> $this */
        $response = $this->value;

        if (! $response instanceof RestResponse && ! $response instanceof AjaxResponse) {
            throw new \InvalidArgumentException('Expected RestResponse or AjaxResponse');
        }

        /** @var RestResponse|AjaxResponse $response */
        $hasKey = $response->has($key);
        expect($hasKey)->toBeTrue(
            "Expected response to have key '$key'"
        );

        if (func_num_args() > 1) {
            $gotValue = $response->get($key);
            expect($gotValue)->toBe($value);
        }

        return $this;
    });

    /**
     * Assert that a REST response has a specific error code.
     */
    expect()->extend('toHaveErrorCode', function (string $code): Expectation {
        /** @var Expectation<RestResponse> $this */
        $response = $this->value;

        if (! $response instanceof RestResponse) {
            throw new \InvalidArgumentException('Expected RestResponse');
        }

        expect($response->errorCode())->toBe($code);

        return $this;
    });

    /**
     * Assert that a REST response contains a specific error message.
     */
    expect()->extend('toHaveErrorMessage', function (string $message): Expectation {
        /** @var Expectation<RestResponse|AjaxResponse> $this */
        $response = $this->value;

        if ($response instanceof RestResponse) {
            expect($response->errorMessage())->toBe($message);
        } elseif ($response instanceof AjaxResponse) {
            expect($response->errorMessage())->toBe($message);
        } else {
            throw new \InvalidArgumentException('Expected RestResponse or AjaxResponse');
        }

        return $this;
    });

    /**
     * Assert that a REST response contains a specific header.
     */
    expect()->extend('toHaveHeader', function (string $name, ?string $value = null): Expectation {
        /** @var Expectation<RestResponse> $this */
        $response = $this->value;

        if (! $response instanceof RestResponse) {
            throw new \InvalidArgumentException('Expected RestResponse');
        }

        $headerValue = $response->header($name);
        expect($headerValue)->not->toBeNull("Expected response to have header '$name'");

        if ($value !== null) {
            expect($headerValue)->toBe($value);
        }

        return $this;
    });

    /**
     * Assert that a REST response is a collection with a specific count.
     */
    expect()->extend('toHaveCount', function (int $count): Expectation {
        /** @var Expectation<RestResponse> $this */
        $response = $this->value;

        if (! $response instanceof RestResponse) {
            throw new \InvalidArgumentException('Expected RestResponse');
        }

        expect($response->count())->toBe($count);

        return $this;
    });

    /**
     * Assert that a REST route exists.
     */
    expect()->extend('toBeRegisteredRestRoute', function (?string $method = null): Expectation {
        /** @var Expectation<string> $this */
        $route = $this->value;

        if (! is_string($route)) {
            throw new \InvalidArgumentException('Expected string route');
        }

        if (! function_exists('rest_get_server')) {
            throw new \RuntimeException('WordPress REST API is not available');
        }

        $server = rest_get_server();
        $routes = $server->get_routes();

        // Normalize route
        $route = '/' . ltrim($route, '/');

        $routeExists = isset($routes[$route]);

        if (! $routeExists) {
            // Check pattern matching
            foreach (array_keys($routes) as $registeredRoute) {
                $pattern = '#^' . preg_quote($registeredRoute, '#') . '$#';
                $pattern = (string) preg_replace('/\\\\\(\\\\\?P<[^>]+>[^\)]+\\\\\)/', '([^/]+)', $pattern);

                if (preg_match($pattern, $route)) {
                    $routeExists = true;
                    $route = $registeredRoute; // Use the registered route for method check
                    break;
                }
            }
        }

        expect($routeExists)->toBeTrue("Expected REST route '$route' to be registered");

        if ($method !== null && $routeExists) {
            $endpoints = $routes[$route];
            $methodSupported = false;

            foreach ($endpoints as $endpoint) {
                if (isset($endpoint['methods'][$method])) {
                    $methodSupported = true;
                    break;
                }
            }

            expect($methodSupported)->toBeTrue(
                "Expected REST route '$route' to support method '$method'"
            );
        }

        return $this;
    });

    /**
     * Assert that an AJAX action is registered.
     */
    expect()->extend('toBeRegisteredAjaxAction', function (bool $admin = true, bool $nopriv = true): Expectation {
        /** @var Expectation<string> $this */
        $action = $this->value;

        if (! is_string($action)) {
            throw new \InvalidArgumentException('Expected string action name');
        }

        $hasAction = false;

        if ($admin && has_action('wp_ajax_' . $action)) {
            $hasAction = true;
        }

        if ($nopriv && has_action('wp_ajax_nopriv_' . $action)) {
            $hasAction = true;
        }

        $context = [];
        if ($admin) {
            $context[] = 'admin';
        }
        if ($nopriv) {
            $context[] = 'nopriv';
        }

        expect($hasAction)->toBeTrue(
            "Expected AJAX action '$action' to be registered for: " . implode(', ', $context)
        );

        return $this;
    });

    /**
     * Assert that an AJAX response is successful.
     */
    expect()->extend('toBeAjaxSuccess', function (): Expectation {
        /** @var Expectation<AjaxResponse> $this */
        $response = $this->value;

        if (! $response instanceof AjaxResponse) {
            throw new \InvalidArgumentException('Expected AjaxResponse');
        }

        expect($response->isSuccess())->toBeTrue('Expected AJAX response to be successful');

        return $this;
    });

    /**
     * Assert that an AJAX response is an error.
     */
    expect()->extend('toBeAjaxError', function (): Expectation {
        /** @var Expectation<AjaxResponse> $this */
        $response = $this->value;

        if (! $response instanceof AjaxResponse) {
            throw new \InvalidArgumentException('Expected AjaxResponse');
        }

        expect($response->isError())->toBeTrue('Expected AJAX response to be an error');

        return $this;
    });
}
