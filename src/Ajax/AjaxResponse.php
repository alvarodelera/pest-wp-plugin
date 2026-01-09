<?php

declare(strict_types=1);

namespace PestWP\Ajax;

use ArrayAccess;
use JsonSerializable;

/**
 * AJAX Response wrapper for testing.
 *
 * Provides a fluent interface to work with admin-ajax.php responses,
 * making assertions easier and more readable.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class AjaxResponse implements ArrayAccess, JsonSerializable
{
    /**
     * @param bool $success Whether the AJAX request was successful
     * @param array<string, mixed> $data Response data
     * @param string $rawOutput Raw output captured during AJAX execution
     * @param int $statusCode HTTP status code
     */
    public function __construct(
        private readonly bool $success,
        private readonly array $data,
        private readonly string $rawOutput = '',
        private readonly int $statusCode = 200,
    ) {}

    /**
     * Create a successful AJAX response.
     *
     * @param array<string, mixed> $data
     */
    public static function success(array $data = []): self
    {
        return new self(true, $data);
    }

    /**
     * Create a failed AJAX response.
     *
     * @param array<string, mixed> $data
     */
    public static function error(array $data = []): self
    {
        return new self(false, $data);
    }

    /**
     * Create response from captured output.
     *
     * Parses the output as JSON if possible, otherwise stores as raw.
     */
    public static function fromOutput(string $output): self
    {
        $output = trim($output);

        if ($output === '') {
            return new self(false, [], '', 200);
        }

        // Try to decode as JSON
        $decoded = json_decode($output, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // wp_send_json_success/error format
            if (isset($decoded['success'])) {
                $success = (bool) $decoded['success'];
                $data = $decoded['data'] ?? [];

                /** @var array<string, mixed> $safeData */
                $safeData = is_array($data) ? $data : ['value' => $data];

                return new self($success, $safeData, $output);
            }

            // Plain JSON response
            /** @var array<string, mixed> $decoded */
            return new self(true, $decoded, $output);
        }

        // Non-JSON response (e.g., HTML, plain text, or '0'/'-1' for legacy AJAX)
        $success = ! in_array($output, ['0', '-1'], true);

        return new self($success, ['raw' => $output], $output);
    }

    /**
     * Check if the AJAX request was successful.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the AJAX request failed.
     */
    public function isError(): bool
    {
        return ! $this->success;
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
     * Get the raw output.
     */
    public function rawOutput(): string
    {
        return $this->rawOutput;
    }

    /**
     * Get the response data as JSON string.
     */
    public function json(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }

    /**
     * Get the HTTP status code.
     */
    public function statusCode(): int
    {
        return $this->statusCode;
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
     * Get the error message if response contains one.
     */
    public function errorMessage(): ?string
    {
        if (isset($this->data['message']) && is_string($this->data['message'])) {
            return $this->data['message'];
        }

        return null;
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
        throw new \BadMethodCallException('AjaxResponse is immutable');
    }

    /**
     * ArrayAccess: Unset offset (not supported, response is immutable).
     *
     * @throws \BadMethodCallException Always throws, response is immutable
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('AjaxResponse is immutable');
    }

    /**
     * JsonSerializable: Get data for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
        ];
    }
}
