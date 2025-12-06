<?php

declare(strict_types=1);

namespace PestWP\Installer;

use RuntimeException;

/**
 * Downloads and manages WordPress core installation for testing.
 *
 * This class handles the idempotent download of WordPress core files
 * to a dedicated testing directory (.pest/wordpress/).
 */
final class WordPressInstaller
{
    private const WP_DOWNLOAD_URL = 'https://wordpress.org/latest.zip';

    private const WP_VERSION_URL = 'https://api.wordpress.org/core/version-check/1.7/';

    private string $installPath;

    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? getcwd() ?: __DIR__;
        $this->installPath = $this->basePath . DIRECTORY_SEPARATOR . '.pest' . DIRECTORY_SEPARATOR . 'wordpress';
    }

    /**
     * Get the installation path for WordPress.
     */
    public function getInstallPath(): string
    {
        return $this->installPath;
    }

    /**
     * Get the base path for the .pest directory.
     */
    public function getPestPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . '.pest';
    }

    /**
     * Check if WordPress is already installed.
     */
    public function isInstalled(): bool
    {
        return file_exists($this->installPath . DIRECTORY_SEPARATOR . 'wp-load.php');
    }

    /**
     * Get the currently installed WordPress version.
     *
     * @return string|null The version string or null if not installed
     */
    public function getInstalledVersion(): ?string
    {
        $versionFile = $this->installPath . DIRECTORY_SEPARATOR . 'wp-includes' . DIRECTORY_SEPARATOR . 'version.php';

        if (! file_exists($versionFile)) {
            return null;
        }

        $content = file_get_contents($versionFile);
        if ($content === false) {
            return null;
        }

        if (preg_match("/\\\$wp_version\s*=\s*'([^']+)'/", $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get the latest WordPress version available.
     *
     * @return string|null The latest version or null if unable to fetch
     */
    public function getLatestVersion(): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'PestWP/1.0',
            ],
        ]);

        $response = @file_get_contents(self::WP_VERSION_URL, false, $context);
        if ($response === false) {
            return null;
        }

        /** @var array{offers?: array<int, array{version?: string}>}|false $data */
        $data = json_decode($response, true);
        if (! is_array($data) || ! isset($data['offers'][0]['version'])) {
            return null;
        }

        return $data['offers'][0]['version'];
    }

    /**
     * Check if an update is available.
     */
    public function needsUpdate(): bool
    {
        $installed = $this->getInstalledVersion();
        if ($installed === null) {
            return true;
        }

        $latest = $this->getLatestVersion();
        if ($latest === null) {
            return false; // Can't determine, assume no update needed
        }

        return version_compare($installed, $latest, '<');
    }

    /**
     * Install WordPress (download and extract).
     *
     * @param  bool  $force  Force reinstallation even if already installed
     *
     * @throws RuntimeException If installation fails
     */
    public function install(bool $force = false): void
    {
        if ($this->isInstalled() && ! $force && ! $this->needsUpdate()) {
            return; // Already installed and up to date
        }

        $this->ensureDirectoryExists($this->getPestPath());

        $zipPath = $this->downloadWordPress();
        $this->extractWordPress($zipPath);
        $this->cleanup($zipPath);
    }

    /**
     * Download WordPress zip file.
     *
     * @throws RuntimeException If download fails
     */
    private function downloadWordPress(): string
    {
        $zipPath = $this->getPestPath() . DIRECTORY_SEPARATOR . 'wordpress.zip';

        $context = stream_context_create([
            'http' => [
                'timeout' => 300,
                'user_agent' => 'PestWP/1.0',
            ],
        ]);

        $content = @file_get_contents(self::WP_DOWNLOAD_URL, false, $context);
        if ($content === false) {
            throw new RuntimeException('Failed to download WordPress from ' . self::WP_DOWNLOAD_URL);
        }

        if (file_put_contents($zipPath, $content) === false) {
            throw new RuntimeException('Failed to save WordPress zip file to ' . $zipPath);
        }

        return $zipPath;
    }

    /**
     * Extract WordPress from zip file.
     *
     * @throws RuntimeException If extraction fails
     */
    private function extractWordPress(string $zipPath): void
    {
        if (! class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive extension is required to extract WordPress');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Failed to open WordPress zip file');
        }

        // Remove existing installation if present
        if (is_dir($this->installPath)) {
            $this->removeDirectory($this->installPath);
        }

        // Extract to temporary directory first
        $tempPath = $this->getPestPath() . DIRECTORY_SEPARATOR . 'wordpress_temp';
        $this->ensureDirectoryExists($tempPath);

        if (! $zip->extractTo($tempPath)) {
            $zip->close();

            throw new RuntimeException('Failed to extract WordPress zip file');
        }

        $zip->close();

        // Move the wordpress folder to the correct location
        $extractedPath = $tempPath . DIRECTORY_SEPARATOR . 'wordpress';
        if (is_dir($extractedPath)) {
            // Try rename first (faster), fall back to copy on Windows
            if (! @rename($extractedPath, $this->installPath)) {
                // rename() often fails on Windows, use copy instead
                $this->copyDirectory($extractedPath, $this->installPath);
                $this->removeDirectory($extractedPath);
            }
        }

        // Cleanup temp directory
        $this->removeDirectory($tempPath);
    }

    /**
     * Cleanup temporary files.
     */
    private function cleanup(string $zipPath): void
    {
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
    }

    /**
     * Copy a directory recursively.
     *
     * @throws RuntimeException If copy fails
     */
    private function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($source)) {
            throw new RuntimeException('Source directory does not exist: ' . $source);
        }

        $this->ensureDirectoryExists($destination);

        $items = scandir($source);
        if ($items === false) {
            throw new RuntimeException('Failed to read source directory: ' . $source);
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
            $destPath = $destination . DIRECTORY_SEPARATOR . $item;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                if (! copy($sourcePath, $destPath)) {
                    throw new RuntimeException('Failed to copy file: ' . $sourcePath);
                }
            }
        }
    }

    /**
     * Ensure a directory exists.
     *
     * @throws RuntimeException If directory creation fails
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (! is_dir($path)) {
            if (! mkdir($path, 0755, true)) {
                throw new RuntimeException('Failed to create directory: ' . $path);
            }
        }
    }

    /**
     * Remove a directory and its contents recursively.
     */
    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
            } else {
                unlink($itemPath);
            }
        }

        rmdir($path);
    }
}
