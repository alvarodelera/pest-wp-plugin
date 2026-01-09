<?php

declare(strict_types=1);

use PestWP\Browser\ScreenshotManager;

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir() . '/pestwp-screenshot-test-' . uniqid();
    if (PHP_OS_FAMILY === 'Windows' && str_starts_with($this->tempDir, 'C:\\Windows\\TEMP')) {
        $this->tempDir = dirname(__DIR__, 3) . '/.pest/tests/screenshots-' . uniqid();
    }
    if (! is_dir($this->tempDir)) {
        mkdir($this->tempDir, 0755, true);
    }
    $this->manager = new ScreenshotManager($this->tempDir);
});

afterEach(function (): void {
    // Clean up temp directory
    if (is_dir($this->tempDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($this->tempDir);
    }
    ScreenshotManager::resetInstance();
});

describe('ScreenshotManager', function (): void {
    test('can be instantiated with custom path', function (): void {
        expect($this->manager)->toBeInstanceOf(ScreenshotManager::class);
        expect($this->manager->getPath())->toBe($this->tempDir);
    });

    test('singleton returns same instance', function (): void {
        $instance1 = ScreenshotManager::getInstance();
        $instance2 = ScreenshotManager::getInstance();
        expect($instance1)->toBe($instance2);
    });

    test('can set custom path', function (): void {
        $customPath = $this->tempDir . '/custom';
        $this->manager->setPath($customPath);
        expect($this->manager->getPath())->toBe($customPath);
        expect($this->manager->getBaselinesPath())->toBe($customPath . '/baselines');
        expect($this->manager->getDiffsPath())->toBe($customPath . '/diffs');
    });

    test('can set test context', function (): void {
        $result = $this->manager->setTest('/path/to/test.php', 'test name');
        expect($result)->toBe($this->manager);
    });

    test('can enable and disable update mode', function (): void {
        expect($this->manager->isUpdateMode())->toBeFalse();

        $this->manager->enableUpdate();
        expect($this->manager->isUpdateMode())->toBeTrue();

        $this->manager->disableUpdate();
        expect($this->manager->isUpdateMode())->toBeFalse();
    });

    test('can set threshold', function (): void {
        $this->manager->setThreshold(0.05);
        expect($this->manager->getThreshold())->toBe(0.05);
    });

    test('threshold is clamped between 0 and 1', function (): void {
        $this->manager->setThreshold(-0.5);
        expect($this->manager->getThreshold())->toBe(0.0);

        $this->manager->setThreshold(1.5);
        expect($this->manager->getThreshold())->toBe(1.0);
    });

    test('capture returns screenshot path', function (): void {
        $path = $this->manager->capture('test-screenshot');
        expect($path)->toEndWith('.png');
        expect($path)->toContain('test-screenshot');
    });

    test('getScreenshotPath returns correct path', function (): void {
        $path = $this->manager->getScreenshotPath('my-screenshot');
        expect($path)->toBe($this->tempDir . '/my-screenshot.png');
    });

    test('getBaselinePath returns correct path', function (): void {
        $path = $this->manager->getBaselinePath('my-screenshot');
        expect($path)->toBe($this->tempDir . '/baselines/my-screenshot.png');
    });

    test('getDiffPath returns correct path', function (): void {
        $path = $this->manager->getDiffPath('my-screenshot');
        expect($path)->toBe($this->tempDir . '/diffs/my-screenshot-diff.png');
    });

    test('hasBaseline returns false when no baseline exists', function (): void {
        expect($this->manager->hasBaseline('nonexistent'))->toBeFalse();
    });

    test('hasBaseline returns true when baseline exists', function (): void {
        $baselinePath = $this->tempDir . '/baselines/test-baseline.png';
        mkdir(dirname($baselinePath), 0755, true);
        file_put_contents($baselinePath, 'fake image data');

        expect($this->manager->hasBaseline('test-baseline'))->toBeTrue();
    });

    test('createBaseline copies screenshot to baselines directory', function (): void {
        // Create a fake screenshot
        $screenshotPath = $this->tempDir . '/test.png';
        file_put_contents($screenshotPath, 'fake image data');

        $baselinePath = $this->manager->createBaseline($screenshotPath, 'my-baseline');

        expect(file_exists($baselinePath))->toBeTrue();
        expect(file_get_contents($baselinePath))->toBe('fake image data');
    });

    test('createBaseline throws when screenshot does not exist', function (): void {
        $this->manager->createBaseline('/nonexistent/path.png');
    })->throws(RuntimeException::class, 'Screenshot not found');

    test('deleteBaseline removes baseline file', function (): void {
        $baselinePath = $this->tempDir . '/baselines/to-delete.png';
        mkdir(dirname($baselinePath), 0755, true);
        file_put_contents($baselinePath, 'fake image data');

        expect($this->manager->deleteBaseline('to-delete'))->toBeTrue();
        expect(file_exists($baselinePath))->toBeFalse();
    });

    test('deleteBaseline returns false for nonexistent baseline', function (): void {
        expect($this->manager->deleteBaseline('nonexistent'))->toBeFalse();
    });

    test('compare returns no match when baseline does not exist', function (): void {
        $screenshotPath = $this->tempDir . '/test.png';
        file_put_contents($screenshotPath, 'fake image data');

        $result = $this->manager->compare($screenshotPath);

        expect($result['match'])->toBeFalse();
        expect($result['difference'])->toBe(1.0);
        expect($result['message'])->toContain('No baseline exists');
    });

    test('compare creates baseline in update mode when missing', function (): void {
        $this->manager->enableUpdate();

        $screenshotPath = $this->tempDir . '/test.png';
        // 1x1 Transparent PNG
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        file_put_contents($screenshotPath, $png);

        $result = $this->manager->compare($screenshotPath);

        expect($result['match'])->toBeTrue();
        expect($result['message'])->toBe('Baseline created');
    });

    test('compare returns match for identical files', function (): void {
        // Create baseline
        $baselinePath = $this->tempDir . '/baselines/identical.png';
        mkdir(dirname($baselinePath), 0755, true);
        // 1x1 Transparent PNG
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        file_put_contents($baselinePath, $png);

        // Create screenshot with same content
        $screenshotPath = $this->tempDir . '/identical.png';
        file_put_contents($screenshotPath, $png);

        $result = $this->manager->compare($screenshotPath);

        expect($result['match'])->toBeTrue();
        expect($result['difference'])->toEqual(0);
    });

    test('compare returns no match for different files', function (): void {
        // Create baseline
        $baselinePath = $this->tempDir . '/baselines/different.png';
        mkdir(dirname($baselinePath), 0755, true);
        // 1x1 Transparent PNG
        $png1 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        file_put_contents($baselinePath, $png1);

        // Create screenshot with different content (1x1 White PNG)
        $screenshotPath = $this->tempDir . '/different.png';
        $png2 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=');
        file_put_contents($screenshotPath, $png2);

        $result = $this->manager->compare($screenshotPath);

        expect($result['match'])->toBeFalse();
        expect($result['difference'])->toBeGreaterThan(0.0);
    });

    test('assertMatch throws for missing screenshot', function (): void {
        $this->manager->assertMatch('/nonexistent/path.png');
    })->throws(RuntimeException::class, 'Screenshot not found');

    test('assertMatch throws for mismatched screenshots', function (): void {
        // Create baseline
        $baselinePath = $this->tempDir . '/baselines/mismatch.png';
        mkdir(dirname($baselinePath), 0755, true);
        // 1x1 Transparent PNG
        $png1 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        file_put_contents($baselinePath, $png1);

        // Create screenshot with different content (1x1 White PNG)
        $screenshotPath = $this->tempDir . '/mismatch.png';
        $png2 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=');
        file_put_contents($screenshotPath, $png2);

        $this->manager->assertMatch($screenshotPath);
    })->throws(RuntimeException::class);

    test('getScreenshots returns tracked screenshots', function (): void {
        $this->manager->capture('screenshot-1');
        $this->manager->capture('screenshot-2');

        $screenshots = $this->manager->getScreenshots();

        expect($screenshots)->toHaveCount(2);
        expect(array_keys($screenshots))->toContain('screenshot-1');
        expect(array_keys($screenshots))->toContain('screenshot-2');
    });

    test('reset clears all tracking data', function (): void {
        $this->manager->capture('test');
        $this->manager->setTest('file.php', 'test name');

        $this->manager->reset();

        expect($this->manager->getScreenshots())->toBeEmpty();
    });
});
