<?php

declare(strict_types=1);

namespace PestWP\Snapshot;

use RuntimeException;

/**
 * Snapshot testing utility.
 *
 * Captures output and compares it to stored snapshots.
 * New snapshots are created automatically, and mismatches are reported as failures.
 *
 * @example
 * ```php
 * // Basic snapshot testing
 * expect($html)->toMatchSnapshot();
 *
 * // Named snapshot
 * expect($output)->toMatchSnapshot('my-feature-output');
 *
 * // JSON snapshot
 * expect($data)->toMatchJsonSnapshot();
 *
 * // Update snapshots via environment variable
 * // PEST_UPDATE_SNAPSHOTS=1 php vendor/bin/pest
 * ```
 */
final class SnapshotManager
{
    /**
     * Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Path to snapshots directory
     */
    private string $snapshotsPath;

    /**
     * Current test file path
     */
    private ?string $currentTestFile = null;

    /**
     * Current test name
     */
    private ?string $currentTestName = null;

    /**
     * Snapshot counter per test (for multiple snapshots in one test)
     */
    private int $snapshotCounter = 0;

    /**
     * Whether to update snapshots
     */
    private bool $updateMode = false;

    /**
     * Snapshots that were updated in this run
     *
     * @var array<string>
     */
    private array $updatedSnapshots = [];

    /**
     * Snapshots that were created in this run
     *
     * @var array<string>
     */
    private array $createdSnapshots = [];

    public function __construct(?string $snapshotsPath = null)
    {
        $this->snapshotsPath = $snapshotsPath ?? getcwd() . '/tests/__snapshots__';
        $this->updateMode = (bool) getenv('PEST_UPDATE_SNAPSHOTS');
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
        self::$instance = null;
    }

    /**
     * Set the snapshots directory path
     *
     * @return $this
     */
    public function setPath(string $path): self
    {
        $this->snapshotsPath = $path;

        return $this;
    }

    /**
     * Get the snapshots directory path
     */
    public function getPath(): string
    {
        return $this->snapshotsPath;
    }

    /**
     * Set current test context
     *
     * @return $this
     */
    public function setTest(string $file, string $name): self
    {
        $this->currentTestFile = $file;
        $this->currentTestName = $name;
        $this->snapshotCounter = 0;

        return $this;
    }

    /**
     * Enable update mode
     *
     * @return $this
     */
    public function enableUpdate(): self
    {
        $this->updateMode = true;

        return $this;
    }

    /**
     * Disable update mode
     *
     * @return $this
     */
    public function disableUpdate(): self
    {
        $this->updateMode = false;

        return $this;
    }

    /**
     * Check if in update mode
     */
    public function isUpdateMode(): bool
    {
        return $this->updateMode;
    }

    /**
     * Assert that a value matches its snapshot
     *
     * @throws RuntimeException if snapshot doesn't match
     */
    public function assertMatch(mixed $value, ?string $name = null): void
    {
        $snapshotPath = $this->getSnapshotPath($name);
        $serialized = $this->serialize($value);

        if (! file_exists($snapshotPath)) {
            $this->createSnapshot($snapshotPath, $serialized);

            return;
        }

        $existing = file_get_contents($snapshotPath);
        if ($existing === false) {
            throw new RuntimeException("Failed to read snapshot: {$snapshotPath}");
        }

        if ($existing !== $serialized) {
            if ($this->updateMode) {
                $this->updateSnapshot($snapshotPath, $serialized);

                return;
            }

            throw new RuntimeException($this->formatMismatch($existing, $serialized, $snapshotPath));
        }
    }

    /**
     * Assert that JSON matches its snapshot
     *
     * @throws RuntimeException if snapshot doesn't match
     */
    public function assertJsonMatch(mixed $value, ?string $name = null): void
    {
        $snapshotPath = $this->getSnapshotPath($name, 'json');
        $serialized = $this->serializeJson($value);

        if (! file_exists($snapshotPath)) {
            $this->createSnapshot($snapshotPath, $serialized);

            return;
        }

        $existing = file_get_contents($snapshotPath);
        if ($existing === false) {
            throw new RuntimeException("Failed to read snapshot: {$snapshotPath}");
        }

        // Normalize JSON for comparison
        $existingNormalized = $this->normalizeJson($existing);
        $newNormalized = $this->normalizeJson($serialized);

        if ($existingNormalized !== $newNormalized) {
            if ($this->updateMode) {
                $this->updateSnapshot($snapshotPath, $serialized);

                return;
            }

            throw new RuntimeException($this->formatMismatch($existing, $serialized, $snapshotPath));
        }
    }

    /**
     * Assert HTML matches snapshot (with normalization)
     *
     * @throws RuntimeException if snapshot doesn't match
     */
    public function assertHtmlMatch(string $html, ?string $name = null): void
    {
        $snapshotPath = $this->getSnapshotPath($name, 'html');
        $normalized = $this->normalizeHtml($html);

        if (! file_exists($snapshotPath)) {
            $this->createSnapshot($snapshotPath, $normalized);

            return;
        }

        $existing = file_get_contents($snapshotPath);
        if ($existing === false) {
            throw new RuntimeException("Failed to read snapshot: {$snapshotPath}");
        }

        $existingNormalized = $this->normalizeHtml($existing);

        if ($existingNormalized !== $normalized) {
            if ($this->updateMode) {
                $this->updateSnapshot($snapshotPath, $normalized);

                return;
            }

            throw new RuntimeException($this->formatMismatch($existing, $normalized, $snapshotPath));
        }
    }

    /**
     * Get the path for a snapshot file
     */
    public function getSnapshotPath(?string $name = null, string $extension = 'snap'): string
    {
        $this->ensureDirectory();

        $baseName = $name ?? $this->generateSnapshotName();

        // Sanitize the name for use as filename
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);

        return $this->snapshotsPath . '/' . $safeName . '.' . $extension;
    }

    /**
     * Generate a snapshot name from test context
     */
    private function generateSnapshotName(): string
    {
        $this->snapshotCounter++;

        $testFile = $this->currentTestFile ?? 'unknown';
        $testName = $this->currentTestName ?? 'unknown';

        // Get just the filename without extension
        $filename = pathinfo($testFile, PATHINFO_FILENAME);

        // Combine into a unique name
        $name = $filename . '__' . $testName;

        // Add counter if there are multiple snapshots in one test
        if ($this->snapshotCounter > 1) {
            $name .= '__' . $this->snapshotCounter;
        }

        return $name;
    }

    /**
     * Create a new snapshot
     */
    private function createSnapshot(string $path, string $content): void
    {
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true)) {
            throw new RuntimeException("Failed to create snapshot directory: {$dir}");
        }

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException("Failed to write snapshot: {$path}");
        }

        $this->createdSnapshots[] = $path;
    }

    /**
     * Update an existing snapshot
     */
    private function updateSnapshot(string $path, string $content): void
    {
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException("Failed to update snapshot: {$path}");
        }

        $this->updatedSnapshots[] = $path;
    }

    /**
     * Serialize a value for snapshot storage
     */
    private function serialize(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return $this->serializeJson($value);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Serialize a value as formatted JSON
     */
    private function serializeJson(mixed $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Normalize JSON for comparison
     */
    private function normalizeJson(string $json): string
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return json_encode($decoded, JSON_THROW_ON_ERROR);
    }

    /**
     * Normalize HTML for comparison
     */
    private function normalizeHtml(string $html): string
    {
        // Remove excessive whitespace
        $html = preg_replace('/\s+/', ' ', $html) ?? $html;

        // Trim whitespace around tags
        $html = preg_replace('/>\s+</', '><', $html) ?? $html;

        // Normalize self-closing tags
        $html = preg_replace('/<(\w+)([^>]*)\s*\/\s*>/', '<$1$2 />', $html) ?? $html;

        return trim($html);
    }

    /**
     * Format a mismatch error message
     */
    private function formatMismatch(string $expected, string $actual, string $path): string
    {
        $lines = [
            "Snapshot mismatch: {$path}",
            '',
            '--- Expected (stored snapshot)',
            '+++ Actual (current output)',
            '',
        ];

        $expectedLines = explode("\n", $expected);
        $actualLines = explode("\n", $actual);

        // Simple diff output
        $maxLines = max(count($expectedLines), count($actualLines));
        for ($i = 0; $i < $maxLines; $i++) {
            $expLine = $expectedLines[$i] ?? '';
            $actLine = $actualLines[$i] ?? '';

            if ($expLine !== $actLine) {
                if ($expLine !== '') {
                    $lines[] = '- ' . $expLine;
                }
                if ($actLine !== '') {
                    $lines[] = '+ ' . $actLine;
                }
            }
        }

        $lines[] = '';
        $lines[] = 'Run with PEST_UPDATE_SNAPSHOTS=1 to update the snapshot.';

        return implode("\n", $lines);
    }

    /**
     * Ensure the snapshots directory exists
     */
    private function ensureDirectory(): void
    {
        if (! is_dir($this->snapshotsPath) && ! mkdir($this->snapshotsPath, 0755, true)) {
            throw new RuntimeException("Failed to create snapshots directory: {$this->snapshotsPath}");
        }
    }

    /**
     * Get created snapshots list
     *
     * @return array<string>
     */
    public function getCreatedSnapshots(): array
    {
        return $this->createdSnapshots;
    }

    /**
     * Get updated snapshots list
     *
     * @return array<string>
     */
    public function getUpdatedSnapshots(): array
    {
        return $this->updatedSnapshots;
    }

    /**
     * Check if a snapshot exists
     */
    public function exists(?string $name = null, string $extension = 'snap'): bool
    {
        return file_exists($this->getSnapshotPath($name, $extension));
    }

    /**
     * Delete a snapshot
     */
    public function delete(?string $name = null, string $extension = 'snap'): bool
    {
        $path = $this->getSnapshotPath($name, $extension);

        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Reset state for new test
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->currentTestFile = null;
        $this->currentTestName = null;
        $this->snapshotCounter = 0;

        return $this;
    }

    /**
     * Clear all tracking
     *
     * @return $this
     */
    public function clearTracking(): self
    {
        $this->createdSnapshots = [];
        $this->updatedSnapshots = [];

        return $this;
    }
}
