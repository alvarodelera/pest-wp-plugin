<?php

declare(strict_types=1);

namespace PestWP\Functions;

use PestWP\Browser\ScreenshotManager;

/**
 * Screenshot Helper Functions
 *
 * Provides convenient access to visual regression testing utilities.
 */

/**
 * Get the ScreenshotManager singleton instance.
 *
 * @param string|null $basePath Custom base path for screenshots
 * @return ScreenshotManager
 */
function screenshots(?string $basePath = null): ScreenshotManager
{
    if ($basePath !== null) {
        $manager = new ScreenshotManager($basePath);

        return $manager;
    }

    return ScreenshotManager::getInstance();
}

/**
 * Capture a screenshot path for later comparison.
 *
 * @param string|null $name Screenshot name (auto-generated if null)
 * @param array{fullPage?: bool, clip?: array{x: int, y: int, width: int, height: int}} $options Capture options
 * @return string Path to save the screenshot
 */
function captureScreenshot(?string $name = null, array $options = []): string
{
    return screenshots()->capture($name, $options);
}

/**
 * Compare a screenshot against its baseline.
 *
 * @param string $screenshotPath Path to the screenshot file
 * @param float|null $threshold Maximum allowed difference (0.0 to 1.0)
 * @return array{match: bool, difference: float, baseline: string, diff: string|null, message: string}
 */
function compareScreenshot(string $screenshotPath, ?float $threshold = null): array
{
    return screenshots()->compare($screenshotPath, $threshold);
}

/**
 * Assert that a screenshot matches its baseline.
 *
 * @param string $screenshotPath Path to the screenshot file
 * @param float|null $threshold Maximum allowed difference (0.0 to 1.0)
 * @throws \RuntimeException If screenshots don't match
 */
function assertScreenshotMatches(string $screenshotPath, ?float $threshold = null): void
{
    screenshots()->assertMatch($screenshotPath, $threshold);
}

/**
 * Create or update a baseline from a screenshot.
 *
 * @param string $screenshotPath Path to the screenshot file
 * @param string|null $name Optional name for the baseline
 * @return string Path to the created baseline
 */
function createBaseline(string $screenshotPath, ?string $name = null): string
{
    return screenshots()->createBaseline($screenshotPath, $name);
}

/**
 * Check if a baseline exists for a screenshot.
 *
 * @param string $name Screenshot name
 * @return bool True if baseline exists
 */
function hasBaseline(string $name): bool
{
    return screenshots()->hasBaseline($name);
}

/**
 * Get the path to a baseline screenshot.
 *
 * @param string $name Screenshot name
 * @return string Full path to baseline file
 */
function baselinePath(string $name): string
{
    return screenshots()->getBaselinePath($name);
}

/**
 * Get the path for a new screenshot.
 *
 * @param string $name Screenshot name
 * @return string Full path for screenshot file
 */
function screenshotPath(string $name): string
{
    return screenshots()->getScreenshotPath($name);
}

/**
 * Get all screenshots taken in the current run.
 *
 * @return array<string, array{path: string, baseline: string|null, diff: string|null, matched: bool|null}>
 */
function getScreenshots(): array
{
    return screenshots()->getScreenshots();
}

/**
 * Set the test context for screenshot naming.
 *
 * @param string $file Test file path
 * @param string $name Test name
 * @return ScreenshotManager
 */
function setScreenshotTest(string $file, string $name): ScreenshotManager
{
    return screenshots()->setTest($file, $name);
}

/**
 * Enable screenshot update mode (regenerate baselines).
 *
 * @return ScreenshotManager
 */
function enableScreenshotUpdate(): ScreenshotManager
{
    return screenshots()->enableUpdate();
}

/**
 * Set the default comparison threshold.
 *
 * @param float $threshold Percentage of different pixels allowed (0-1)
 * @return ScreenshotManager
 */
function setScreenshotThreshold(float $threshold): ScreenshotManager
{
    return screenshots()->setThreshold($threshold);
}

/**
 * Reset the screenshot manager state.
 *
 * @return ScreenshotManager
 */
function resetScreenshots(): ScreenshotManager
{
    return screenshots()->reset();
}

// =============================================================================
// VIDEO RECORDING HELPERS
// =============================================================================

/**
 * Get video recording configuration for Playwright.
 *
 * @param string $mode Recording mode: 'off', 'on', 'retain-on-failure', 'on-first-retry'
 * @param string|null $dir Directory to save videos (null for default)
 * @param array{width: int, height: int}|null $size Video size (null for viewport size)
 * @return array{mode: string, dir?: string, size?: array{width: int, height: int}}
 */
function videoRecordingConfig(
    string $mode = 'retain-on-failure',
    ?string $dir = null,
    ?array $size = null,
): array {
    $config = ['mode' => $mode];

    if ($dir !== null) {
        $config['dir'] = $dir;
    }

    if ($size !== null) {
        $config['size'] = $size;
    }

    return $config;
}

/**
 * Get video config for recording on test failure only.
 *
 * @param string|null $dir Directory to save videos
 * @return array{mode: string, dir?: string}
 */
function videoOnFailure(?string $dir = null): array
{
    return videoRecordingConfig('retain-on-failure', $dir);
}

/**
 * Get video config for always recording.
 *
 * @param string|null $dir Directory to save videos
 * @return array{mode: string, dir?: string}
 */
function videoAlways(?string $dir = null): array
{
    return videoRecordingConfig('on', $dir);
}

/**
 * Get video config with no recording.
 *
 * @return array{mode: string}
 */
function videoOff(): array
{
    return ['mode' => 'off'];
}

// =============================================================================
// TRACE RECORDING HELPERS
// =============================================================================

/**
 * Get trace recording configuration for Playwright.
 *
 * @param string $mode Trace mode: 'off', 'on', 'retain-on-failure', 'on-first-retry'
 * @param array{screenshots?: bool, snapshots?: bool, sources?: bool} $options Trace options
 * @return array{mode: string, screenshots?: bool, snapshots?: bool, sources?: bool}
 */
function traceConfig(string $mode = 'retain-on-failure', array $options = []): array
{
    return array_merge(['mode' => $mode], $options);
}

/**
 * Get trace config for recording on test failure only.
 *
 * @return array{mode: string, screenshots: bool, snapshots: bool}
 */
function traceOnFailure(): array
{
    return [
        'mode' => 'retain-on-failure',
        'screenshots' => true,
        'snapshots' => true,
    ];
}

/**
 * Get full trace config with all options enabled.
 *
 * @return array{mode: string, screenshots: bool, snapshots: bool, sources: bool}
 */
function traceFull(): array
{
    return [
        'mode' => 'on',
        'screenshots' => true,
        'snapshots' => true,
        'sources' => true,
    ];
}
