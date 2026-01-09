<?php

declare(strict_types=1);

use PestWP\Snapshot\SnapshotManager;

beforeEach(function () {
    SnapshotManager::resetInstance();
    $this->tempDir = sys_get_temp_dir() . '/pestwp-snapshots-' . uniqid();
    mkdir($this->tempDir, 0755, true);
});

afterEach(function () {
    SnapshotManager::resetInstance();

    // Clean up temp directory
    if (is_dir($this->tempDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
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
});

describe('SnapshotManager', function () {
    describe('singleton', function () {
        test('getInstance() returns singleton', function () {
            $instance1 = SnapshotManager::getInstance();
            $instance2 = SnapshotManager::getInstance();

            expect($instance1)->toBe($instance2);
        });

        test('resetInstance() clears singleton', function () {
            $instance1 = SnapshotManager::getInstance();
            SnapshotManager::resetInstance();
            $instance2 = SnapshotManager::getInstance();

            expect($instance1)->not->toBe($instance2);
        });
    });

    describe('configuration', function () {
        test('setPath() changes snapshot directory', function () {
            $manager = SnapshotManager::getInstance()->setPath($this->tempDir);

            expect($manager->getPath())->toBe($this->tempDir);
        });

        test('setTest() sets test context', function () {
            $manager = SnapshotManager::getInstance()
                ->setPath($this->tempDir)
                ->setTest('/path/to/MyTest.php', 'my test name');

            // Verify by checking the generated snapshot path
            $path = $manager->getSnapshotPath();
            expect($path)->toContain('MyTest');
        });

        test('enableUpdate() enables update mode', function () {
            $manager = SnapshotManager::getInstance()->enableUpdate();
            expect($manager->isUpdateMode())->toBeTrue();
        });

        test('disableUpdate() disables update mode', function () {
            $manager = SnapshotManager::getInstance()->enableUpdate()->disableUpdate();
            expect($manager->isUpdateMode())->toBeFalse();
        });
    });

    describe('assertMatch()', function () {
        test('creates snapshot if not exists', function () {
            $manager = SnapshotManager::getInstance()->setPath($this->tempDir);

            $manager->assertMatch('Hello World', 'test-snapshot');

            expect($manager->exists('test-snapshot'))->toBeTrue();
        });

        test('passes when snapshot matches', function () {
            $manager = SnapshotManager::getInstance()->setPath($this->tempDir);

            // Create initial snapshot
            $manager->assertMatch('Hello World', 'test-match');

            // Should pass when matching
            $manager->assertMatch('Hello World', 'test-match');

            expect(true)->toBeTrue(); // If we get here, test passed
        });

        test('throws when snapshot mismatches', function () {
            $manager = SnapshotManager::getInstance()->setPath($this->tempDir);

            // Create initial snapshot
            $manager->assertMatch('Original Value', 'test-mismatch');

            // Should throw on mismatch
            expect(fn () => $manager->assertMatch('Different Value', 'test-mismatch'))
                ->toThrow(\RuntimeException::class);
        });

        test('updates snapshot in update mode', function () {
            $manager = SnapshotManager::getInstance()
                ->setPath($this->tempDir)
                ->enableUpdate();

            // Create initial snapshot
            $manager->assertMatch('Original', 'test-update');

            // Update with new value
            $manager->assertMatch('Updated', 'test-update');

            // Verify update was recorded
            expect($manager->getUpdatedSnapshots())->toContain(
                $manager->getSnapshotPath('test-update')
            );
        });

        test('serializes arrays as JSON', function () {
            $manager = SnapshotManager::getInstance()->setPath($this->tempDir);

            $manager->assertMatch(['key' => 'value'], 'test-array');

            $path = $manager->getSnapshotPath('test-array');
            $content = file_get_contents($path);

            expect($content)->toContain('"key"');
            expect($content)->toContain('"value"');
        });
    });

    describe('assertJsonMatch()', function () {
        test('creates JSON snapshot', function () {
            $manager = SnapshotManager::getInstance()->setPath($this->tempDir);

            $manager->assertJsonMatch(['status' => 'ok'], 'test-json');

            expect($manager->exists('test-json', 'json'))->toBeTrue();
        });

        test('normalizes JSON for comparison', function () {
            $manager = SnapshotManager::getInstance()->setPath($this->tempDir);

            // Create with one formatting
            $manager->assertJsonMatch(['a' => 1, 'b' => 2], 'test-json-normalize');

            // Should match regardless of key order or formatting
            $manager->assertJsonMatch(['a' => 1, 'b' => 2], 'test-json-normalize');

            expect(true)->toBeTrue();
        });
    });

    describe('assertHtmlMatch()', function () {
        test('creates HTML snapshot', function () {
            $manager = SnapshotManager::getInstance()->setPath($this->tempDir);

            $manager->assertHtmlMatch('<div>Hello</div>', 'test-html');

            expect($manager->exists('test-html', 'html'))->toBeTrue();
        });

        test('normalizes HTML whitespace', function () {
            $manager = SnapshotManager::getInstance()->setPath($this->tempDir);

            // Create with extra whitespace
            $manager->assertHtmlMatch('<div>   Hello   </div>', 'test-html-normalize');

            // Should match normalized version
            $manager->assertHtmlMatch('<div> Hello </div>', 'test-html-normalize');

            expect(true)->toBeTrue();
        });
    });

    describe('snapshot management', function () {
        test('exists() checks if snapshot exists', function () {
            $manager = SnapshotManager::getInstance()->setPath($this->tempDir);

            expect($manager->exists('nonexistent'))->toBeFalse();

            $manager->assertMatch('test', 'exists-test');

            expect($manager->exists('exists-test'))->toBeTrue();
        });

        test('delete() removes snapshot', function () {
            $manager = SnapshotManager::getInstance()->setPath($this->tempDir);

            $manager->assertMatch('test', 'delete-test');
            expect($manager->exists('delete-test'))->toBeTrue();

            $result = $manager->delete('delete-test');

            expect($result)->toBeTrue();
            expect($manager->exists('delete-test'))->toBeFalse();
        });

        test('delete() returns false for nonexistent', function () {
            $manager = SnapshotManager::getInstance()->setPath($this->tempDir);

            expect($manager->delete('nonexistent'))->toBeFalse();
        });

        test('getCreatedSnapshots() returns list', function () {
            $manager = SnapshotManager::getInstance()
                ->setPath($this->tempDir)
                ->clearTracking();

            $manager->assertMatch('test1', 'created-1');
            $manager->assertMatch('test2', 'created-2');

            expect($manager->getCreatedSnapshots())->toHaveCount(2);
        });
    });

    describe('counter for multiple snapshots', function () {
        test('auto-increments snapshot counter', function () {
            $manager = SnapshotManager::getInstance()
                ->setPath($this->tempDir)
                ->setTest('/path/to/Test.php', 'my test');

            // Create multiple snapshots without names
            $path1 = $manager->getSnapshotPath();
            $path2 = $manager->getSnapshotPath();

            expect($path1)->not->toBe($path2);
        });

        test('reset() clears counter', function () {
            $manager = SnapshotManager::getInstance()
                ->setPath($this->tempDir)
                ->setTest('/path/to/Test.php', 'my test');

            $manager->getSnapshotPath();
            $manager->getSnapshotPath();

            $manager->reset();
            $manager->setTest('/path/to/Test.php', 'my test');

            // Counter should be reset
            $path = $manager->getSnapshotPath();
            expect($path)->not->toContain('__2');
        });
    });
});
