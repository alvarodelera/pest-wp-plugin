<?php

declare(strict_types=1);

namespace PestWP\Rest;

use ArrayAccess;
use JsonSerializable;

/**
 * REST API Response wrapper for testing.
 *
 * Provides a fluent interface to work with REST API responses,
 * making assertions easier and more readable.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class RestResponse implements ArrayAccess, JsonSerializable
{
    /**
     * @param int $status HTTP status code
     * @param array<string, mixed> $data Response data (decoded JSON)
     * @param array<string, string|list<string>> $headers Response headers
     */
    public function __construct(
        private readonly int $status,
        private readonly array $data,
        private readonly array $headers = [],
    ) {}

    /**
     * Create a RestResponse from a WP_REST_Response object.
     */
    public static function fromWpRestResponse(\WP_REST_Response $response): self
    {
        $data = $response->get_data();

        return new self(
            $response->get_status(),
            is_array($data) ? $data : ['data' => $data],
            $response->get_headers(),
        );
    }

    /**
     * Get the HTTP status code.
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Get the response data.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Get the response data as JSON string.
     */
    public function json(): string
    {
        $json = json_encode($this->data, JSON_THROW_ON_ERROR);

        return $json;
    }

    /**
     * Get all response headers.
     *
     * @return array<string, string|list<string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get a specific header value.
     */
    public function header(string $name): ?string
    {
        $normalized = strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $normalized) {
                return is_array($value) ? $value[0] : $value;
            }
        }

        return null;
    }

    /**
     * Check if response was successful (2xx status code).
     */
    public function isSuccessful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * Check if response indicates an error (4xx or 5xx).
     */
    public function isError(): bool
    {
        return $this->status >= 400;
    }

    /**
     * Check if response is a client error (4xx).
     */
    public function isClientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * Check if response is a server error (5xx).
     */
    public function isServerError(): bool
    {
        return $this->status >= 500;
    }

    /**
     * Check if response has a specific status code.
     */
    public function hasStatus(int $status): bool
    {
        return $this->status === $status;
    }

    /**
     * Get a value from the response data using dot notation.
     *
     * @param string $key Key using dot notation (e.g., 'user.name')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->data;

        foreach ($keys as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Check if a key exists in the response data.
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = $this->data;

        foreach ($keys as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

    /**
     * Get the error code if response is a WP_Error response.
     */
    public function errorCode(): ?string
    {
        if (isset($this->data['code']) && is_string($this->data['code'])) {
            return $this->data['code'];
        }

        return null;
    }

    /**
     * Get the error message if response is a WP_Error response.
     */
    public function errorMessage(): ?string
    {
        if (isset($this->data['message']) && is_string($this->data['message'])) {
            return $this->data['message'];
        }

        return null;
    }

    /**
     * Get item count for collection responses.
     */
    public function count(): int
    {
        if (array_is_list($this->data)) {
            return count($this->data);
        }

        return 1;
    }

    /**
     * Get first item for collection responses.
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        if (! array_is_list($this->data) || count($this->data) === 0) {
            return null;
        }

        $first = $this->data[0];

        if (! is_array($first)) {
            return null;
        }

        /** @var array<string, mixed> $first */
        return $first;
    }

    /**
     * Get items as collection for collection responses.
     *
     * @return list<array<string, mixed>>
     */
    public function items(): array
    {
        if (array_is_list($this->data)) {
            /** @var list<array<string, mixed>> */
            return $this->data;
        }

        return [$this->data];
    }

    /**
     * ArrayAccess: Check if offset exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * ArrayAccess: Get offset value.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * ArrayAccess: Set offset value (not supported, response is immutable).
     *
     * @throws \BadMethodCallException Always throws, response is immutable
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('RestResponse is immutable');
    }

    /**
     * ArrayAccess: Unset offset (not supported, response is immutable).
     *
     * @throws \BadMethodCallException Always throws, response is immutable
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('RestResponse is immutable');
    }

    /**
     * JsonSerializable: Get data for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
