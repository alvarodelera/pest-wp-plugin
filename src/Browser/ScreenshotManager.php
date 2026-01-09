<?php

declare(strict_types=1);

namespace PestWP\Browser;

use RuntimeException;

/**
 * Screenshot manager for visual regression testing.
 *
 * Captures and compares screenshots to detect visual regressions.
 *
 * @example
 * ```php
 * // Basic screenshot comparison
 * $screenshot = screenshots()->capture('homepage');
 * expect($screenshot)->toMatchBaseline();
 *
 * // Compare with threshold
 * expect($screenshot)->toMatchBaseline(threshold: 0.1);
 *
 * // Full page screenshot
 * $screenshot = screenshots()->capture('full-page', ['fullPage' => true]);
 * ```
 */
final class ScreenshotManager
{
    /**
     * Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Path to screenshots directory
     */
    private string $screenshotsPath;

    /**
     * Path to baseline screenshots
     */
    private string $baselinesPath;

    /**
     * Path to diff screenshots
     */
    private string $diffsPath;

    /**
     * Current test context
     */
    private ?string $currentTestFile = null;

    /**
     * Current test name
     */
    private ?string $currentTestName = null;

    /**
     * Screenshot counter per test
     */
    private int $screenshotCounter = 0;

    /**
     * Whether to update baselines
     */
    private bool $updateMode = false;

    /**
     * Default comparison threshold (0-1, percentage of different pixels allowed)
     */
    private float $defaultThreshold = 0.01;

    /**
     * Screenshots taken in current run
     *
     * @var array<string, array{path: string, baseline: string|null, diff: string|null, matched: bool|null}>
     */
    private array $screenshots = [];

    public function __construct(?string $basePath = null)
    {
        $base = $basePath ?? getcwd() . '/tests/__screenshots__';
        $this->screenshotsPath = $base;
        $this->baselinesPath = $base . '/baselines';
        $this->diffsPath = $base . '/diffs';
        $this->updateMode = (bool) getenv('PEST_UPDATE_SCREENSHOTS');
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
     * Set the base path for screenshots
     *
     * @return $this
     */
    public function setPath(string $path): self
    {
        $this->screenshotsPath = $path;
        $this->baselinesPath = $path . '/baselines';
        $this->diffsPath = $path . '/diffs';

        return $this;
    }

    /**
     * Get the screenshots base path
     */
    public function getPath(): string
    {
        return $this->screenshotsPath;
    }

    /**
     * Get the baselines path
     */
    public function getBaselinesPath(): string
    {
        return $this->baselinesPath;
    }

    /**
     * Get the diffs path
     */
    public function getDiffsPath(): string
    {
        return $this->diffsPath;
    }

    /**
     * Set the current test context
     *
     * @return $this
     */
    public function setTest(string $file, string $name): self
    {
        $this->currentTestFile = $file;
        $this->currentTestName = $name;
        $this->screenshotCounter = 0;

        return $this;
    }

    /**
     * Enable update mode (regenerate baselines)
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
     * Set the default comparison threshold
     *
     * @param float $threshold Percentage of different pixels allowed (0-1)
     * @return $this
     */
    public function setThreshold(float $threshold): self
    {
        $this->defaultThreshold = max(0.0, min(1.0, $threshold));

        return $this;
    }

    /**
     * Get the default comparison threshold
     */
    public function getThreshold(): float
    {
        return $this->defaultThreshold;
    }

    /**
     * Capture a screenshot (returns path for comparison)
     *
     * @param string|null $name Screenshot name (auto-generated if null)
     * @param array{fullPage?: bool, clip?: array{x: int, y: int, width: int, height: int}} $options Capture options
     * @return string Path to the captured screenshot
     */
    public function capture(?string $name = null, array $options = []): string
    {
        $screenshotName = $name ?? $this->generateScreenshotName();
        $safeName = $this->sanitizeName($screenshotName);
        $path = $this->getScreenshotPath($safeName);

        $this->ensureDirectory(dirname($path));

        // Store metadata for later comparison
        $this->screenshots[$safeName] = [
            'path' => $path,
            'baseline' => $this->getBaselinePath($safeName),
            'diff' => null,
            'matched' => null,
            'options' => $options,
        ];

        return $path;
    }

    /**
     * Get the path for a new screenshot
     */
    public function getScreenshotPath(string $name): string
    {
        $this->ensureDirectory($this->screenshotsPath);

        return $this->screenshotsPath . '/' . $name . '.png';
    }

    /**
     * Get the baseline path for a screenshot
     */
    public function getBaselinePath(string $name): string
    {
        return $this->baselinesPath . '/' . $name . '.png';
    }

    /**
     * Get the diff path for a screenshot
     */
    public function getDiffPath(string $name): string
    {
        return $this->diffsPath . '/' . $name . '-diff.png';
    }

    /**
     * Check if a baseline exists
     */
    public function hasBaseline(string $name): bool
    {
        return file_exists($this->getBaselinePath($this->sanitizeName($name)));
    }

    /**
     * Compare a screenshot to its baseline
     *
     * @param string $screenshotPath Path to the screenshot to compare
     * @param float|null $threshold Difference threshold (0-1), null uses default
     * @return array{match: bool, difference: float, baseline: string, diff: string|null, message: string}
     */
    public function compare(string $screenshotPath, ?float $threshold = null): array
    {
        $threshold = $threshold ?? $this->defaultThreshold;
        $name = $this->getNameFromPath($screenshotPath);
        $baselinePath = $this->getBaselinePath($name);
        $diffPath = $this->getDiffPath($name);

        // If no baseline exists
        if (! file_exists($baselinePath)) {
            if ($this->updateMode) {
                $this->createBaseline($screenshotPath, $name);

                return [
                    'match' => true,
                    'difference' => 0.0,
                    'baseline' => $baselinePath,
                    'diff' => null,
                    'message' => 'Baseline created',
                ];
            }

            return [
                'match' => false,
                'difference' => 1.0,
                'baseline' => $baselinePath,
                'diff' => null,
                'message' => 'No baseline exists. Run with PEST_UPDATE_SCREENSHOTS=1 to create baseline.',
            ];
        }

        // Compare images
        $result = $this->compareImages($screenshotPath, $baselinePath, $diffPath);

        // Update baseline if in update mode and images differ
        if ($this->updateMode && ! $result['match']) {
            $this->createBaseline($screenshotPath, $name);

            return [
                'match' => true,
                'difference' => 0.0,
                'baseline' => $baselinePath,
                'diff' => null,
                'message' => 'Baseline updated',
            ];
        }

        // Check against threshold
        $withinThreshold = $result['difference'] <= $threshold;

        return [
            'match' => $withinThreshold,
            'difference' => $result['difference'],
            'baseline' => $baselinePath,
            'diff' => $result['match'] ? null : $diffPath,
            'message' => $withinThreshold
                ? sprintf('Visual match (%.2f%% difference)', $result['difference'] * 100)
                : sprintf('Visual mismatch: %.2f%% difference (threshold: %.2f%%)', $result['difference'] * 100, $threshold * 100),
        ];
    }

    /**
     * Assert that a screenshot matches its baseline
     *
     * @param string $screenshotPath Path to the screenshot
     * @param float|null $threshold Difference threshold (0-1)
     * @throws RuntimeException If comparison fails
     */
    public function assertMatch(string $screenshotPath, ?float $threshold = null): void
    {
        if (! file_exists($screenshotPath)) {
            throw new RuntimeException("Screenshot not found: {$screenshotPath}");
        }

        $result = $this->compare($screenshotPath, $threshold);

        if (! $result['match']) {
            $message = $result['message'];
            if ($result['diff'] !== null) {
                $message .= "\nDiff saved to: {$result['diff']}";
            }
            $message .= "\nBaseline: {$result['baseline']}";
            $message .= "\nScreenshot: {$screenshotPath}";

            throw new RuntimeException($message);
        }
    }

    /**
     * Create or update a baseline from a screenshot
     */
    public function createBaseline(string $screenshotPath, ?string $name = null): string
    {
        if (! file_exists($screenshotPath)) {
            throw new RuntimeException("Screenshot not found: {$screenshotPath}");
        }

        $name = $name ?? $this->getNameFromPath($screenshotPath);
        $baselinePath = $this->getBaselinePath($name);

        $this->ensureDirectory(dirname($baselinePath));

        if (! copy($screenshotPath, $baselinePath)) {
            throw new RuntimeException("Failed to create baseline: {$baselinePath}");
        }

        return $baselinePath;
    }

    /**
     * Delete a baseline
     */
    public function deleteBaseline(string $name): bool
    {
        $path = $this->getBaselinePath($this->sanitizeName($name));

        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Get all screenshots taken in this run
     *
     * @return array<string, array{path: string, baseline: string|null, diff: string|null, matched: bool|null}>
     */
    public function getScreenshots(): array
    {
        return $this->screenshots;
    }

    /**
     * Clear tracking data
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->screenshots = [];
        $this->currentTestFile = null;
        $this->currentTestName = null;
        $this->screenshotCounter = 0;

        return $this;
    }

    /**
     * Compare two images and generate a diff
     *
     * @return array{match: bool, difference: float}
     */
    private function compareImages(string $imagePath, string $baselinePath, string $diffPath): array
    {
        // Check if GD extension is available
        if (! extension_loaded('gd')) {
            // Fallback to simple file comparison
            return $this->compareFilesSimple($imagePath, $baselinePath);
        }

        $image = @imagecreatefrompng($imagePath);
        $baseline = @imagecreatefrompng($baselinePath);

        if ($image === false || $baseline === false) {
            // If we can't load as PNG, try simple comparison
            return $this->compareFilesSimple($imagePath, $baselinePath);
        }

        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);
        $baselineWidth = imagesx($baseline);
        $baselineHeight = imagesy($baseline);

        // Different dimensions = mismatch
        if ($imageWidth !== $baselineWidth || $imageHeight !== $baselineHeight) {
            imagedestroy($image);
            imagedestroy($baseline);

            return [
                'match' => false,
                'difference' => 1.0,
            ];
        }

        // Create diff image
        $diff = imagecreatetruecolor($imageWidth, $imageHeight);
        if ($diff === false) {
            imagedestroy($image);
            imagedestroy($baseline);

            return $this->compareFilesSimple($imagePath, $baselinePath);
        }

        $diffColor = imagecolorallocate($diff, 255, 0, 0); // Red for differences
        $sameColor = imagecolorallocate($diff, 200, 200, 200); // Gray for same

        if ($diffColor === false || $sameColor === false) {
            imagedestroy($image);
            imagedestroy($baseline);
            imagedestroy($diff);

            return $this->compareFilesSimple($imagePath, $baselinePath);
        }

        $differentPixels = 0;
        $totalPixels = $imageWidth * $imageHeight;

        for ($x = 0; $x < $imageWidth; $x++) {
            for ($y = 0; $y < $imageHeight; $y++) {
                $imageColor = imagecolorat($image, $x, $y);
                $baselineColor = imagecolorat($baseline, $x, $y);

                if ($imageColor !== $baselineColor) {
                    $differentPixels++;
                    imagesetpixel($diff, $x, $y, $diffColor);
                } else {
                    imagesetpixel($diff, $x, $y, $sameColor);
                }
            }
        }

        $difference = $differentPixels / $totalPixels;
        $match = $differentPixels === 0;

        // Save diff image if there are differences
        if (! $match) {
            $this->ensureDirectory(dirname($diffPath));
            imagepng($diff, $diffPath);
        }

        imagedestroy($image);
        imagedestroy($baseline);
        imagedestroy($diff);

        return [
            'match' => $match,
            'difference' => $difference,
        ];
    }

    /**
     * Simple file comparison fallback
     *
     * @return array{match: bool, difference: float}
     */
    private function compareFilesSimple(string $path1, string $path2): array
    {
        $content1 = file_get_contents($path1);
        $content2 = file_get_contents($path2);

        if ($content1 === false || $content2 === false) {
            return ['match' => false, 'difference' => 1.0];
        }

        $match = $content1 === $content2;

        return [
            'match' => $match,
            'difference' => $match ? 0.0 : 1.0,
        ];
    }

    /**
     * Generate a screenshot name from test context
     */
    private function generateScreenshotName(): string
    {
        $this->screenshotCounter++;

        $testFile = $this->currentTestFile ?? 'unknown';
        $testName = $this->currentTestName ?? 'unknown';

        // Get just the filename without extension
        $filename = pathinfo($testFile, PATHINFO_FILENAME);

        // Combine into a unique name
        $name = $filename . '__' . $testName;

        // Add counter if there are multiple screenshots in one test
        if ($this->screenshotCounter > 1) {
            $name .= '__' . $this->screenshotCounter;
        }

        return $this->sanitizeName($name);
    }

    /**
     * Sanitize a name for use as filename
     */
    private function sanitizeName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) ?? $name;
    }

    /**
     * Get screenshot name from path
     */
    private function getNameFromPath(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Ensure a directory exists
     */
    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path) && ! @mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException("Failed to create directory: {$path}");
        }
    }
}
