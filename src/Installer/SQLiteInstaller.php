<?php

declare(strict_types=1);

namespace PestWP\Installer;

use RuntimeException;

/**
 * Handles SQLite database integration for WordPress testing.
 *
 * This class downloads the official WordPress SQLite integration plugin
 * and configures it for use with the test suite.
 */
final class SQLiteInstaller
{
    /**
     * GitHub repository for the SQLite integration plugin.
     */
    private const SQLITE_PLUGIN_REPO = 'WordPress/sqlite-database-integration';

    /**
     * GitHub API URL for releases.
     */
    private const RELEASES_API_URL = 'https://api.github.com/repos/WordPress/sqlite-database-integration/releases/latest';

    private string $wpPath;

    private string $pestPath;

    public function __construct(string $wpPath, string $pestPath)
    {
        $this->wpPath = $wpPath;
        $this->pestPath = $pestPath;
    }

    /**
     * Check if SQLite integration is installed.
     */
    public function isInstalled(): bool
    {
        return file_exists($this->getDbDropInPath());
    }

    /**
     * Get the path to the db.php drop-in.
     */
    public function getDbDropInPath(): string
    {
        return $this->wpPath . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . 'db.php';
    }

    /**
     * Get the path to the SQLite plugin directory.
     */
    public function getPluginPath(): string
    {
        return $this->wpPath . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'sqlite-database-integration';
    }

    /**
     * Get the path to the SQLite database file.
     */
    public function getDatabasePath(): string
    {
        return $this->pestPath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . '.ht.sqlite';
    }

    /**
     * Install SQLite integration.
     *
     * @param  bool  $force  Force reinstallation
     *
     * @throws RuntimeException If installation fails
     */
    public function install(bool $force = false): void
    {
        if ($this->isInstalled() && ! $force) {
            return;
        }

        $this->downloadPlugin();
        $this->installDbDropIn();
        $this->ensureDatabaseDirectory();
    }

    /**
     * Download the SQLite integration plugin.
     *
     * @throws RuntimeException If download fails
     */
    private function downloadPlugin(): void
    {
        $pluginPath = $this->getPluginPath();

        // Remove existing installation
        if (is_dir($pluginPath)) {
            $this->removeDirectory($pluginPath);
        }

        // Get the latest release URL
        $releaseUrl = $this->getLatestReleaseUrl();
        if ($releaseUrl === null) {
            // Fall back to downloading from main branch
            $releaseUrl = 'https://github.com/' . self::SQLITE_PLUGIN_REPO . '/archive/refs/heads/main.zip';
        }

        $zipPath = $this->pestPath . DIRECTORY_SEPARATOR . 'sqlite-plugin.zip';
        $this->downloadFile($releaseUrl, $zipPath);
        $this->extractPlugin($zipPath, $pluginPath);

        // Cleanup
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
    }

    /**
     * Get the latest release URL from GitHub API.
     */
    private function getLatestReleaseUrl(): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'PestWP/1.0',
                'header' => 'Accept: application/vnd.github.v3+json',
            ],
        ]);

        $response = @file_get_contents(self::RELEASES_API_URL, false, $context);
        if ($response === false) {
            return null;
        }

        /** @var array{zipball_url?: string}|false $data */
        $data = json_decode($response, true);
        if (! is_array($data) || ! isset($data['zipball_url'])) {
            return null;
        }

        return $data['zipball_url'];
    }

    /**
     * Download a file from URL.
     *
     * @throws RuntimeException If download fails
     */
    private function downloadFile(string $url, string $destination): void
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 300,
                'user_agent' => 'PestWP/1.0',
                'follow_location' => true,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            throw new RuntimeException('Failed to download file from ' . $url);
        }

        if (file_put_contents($destination, $content) === false) {
            throw new RuntimeException('Failed to save file to ' . $destination);
        }
    }

    /**
     * Extract the plugin from zip file.
     *
     * @throws RuntimeException If extraction fails
     */
    private function extractPlugin(string $zipPath, string $destination): void
    {
        if (! class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive extension is required');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Failed to open zip file: ' . $zipPath);
        }

        // Get the root folder name in the zip (GitHub adds a folder with repo-branch name)
        $rootFolder = $zip->getNameIndex(0);
        if ($rootFolder === false) {
            $zip->close();

            throw new RuntimeException('Empty zip file');
        }

        // Extract to temp location
        $tempPath = dirname($destination) . DIRECTORY_SEPARATOR . 'sqlite_temp';
        if (! is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        if (! $zip->extractTo($tempPath)) {
            $zip->close();

            throw new RuntimeException('Failed to extract zip file');
        }

        $zip->close();

        // Find the extracted folder and move it
        $extractedPath = $tempPath . DIRECTORY_SEPARATOR . rtrim($rootFolder, '/');
        if (is_dir($extractedPath)) {
            if (! is_dir(dirname($destination))) {
                mkdir(dirname($destination), 0755, true);
            }
            rename($extractedPath, $destination);
        }

        // Cleanup temp
        $this->removeDirectory($tempPath);
    }

    /**
     * Install the db.php drop-in file.
     *
     * @throws RuntimeException If installation fails
     */
    private function installDbDropIn(): void
    {
        $pluginPath = $this->getPluginPath();
        $sourceDbPhp = $pluginPath . DIRECTORY_SEPARATOR . 'db.copy';

        if (! file_exists($sourceDbPhp)) {
            throw new RuntimeException('db.copy not found in SQLite plugin. The plugin structure may have changed.');
        }

        $wpContentDir = dirname($this->getDbDropInPath());
        if (! is_dir($wpContentDir)) {
            mkdir($wpContentDir, 0755, true);
        }

        // Read and modify db.php to set correct paths
        $dbContent = file_get_contents($sourceDbPhp);
        if ($dbContent === false) {
            throw new RuntimeException('Failed to read db.copy file');
        }

        // The db.copy file needs to be configured with the correct paths
        // We'll create our own db.php that loads the plugin correctly
        $dbPhpContent = $this->generateDbPhp();

        if (file_put_contents($this->getDbDropInPath(), $dbPhpContent) === false) {
            throw new RuntimeException('Failed to write db.php drop-in');
        }
    }

    /**
     * Generate the db.php content with correct paths.
     */
    private function generateDbPhp(): string
    {
        $pluginPath = str_replace('\\', '/', $this->getPluginPath());
        $databaseDir = str_replace('\\', '/', dirname($this->getDatabasePath()));

        return <<<PHP
<?php
/**
 * SQLite database drop-in for PestWP.
 *
 * This file is auto-generated by PestWP.
 */

// Define SQLite database path
if ( ! defined( 'FQDB' ) ) {
    define( 'FQDB', '{$databaseDir}/.ht.sqlite' );
}

// Define the path to the SQLite plugin
if ( ! defined( 'SQLITE_MAIN_FILE' ) ) {
    define( 'SQLITE_MAIN_FILE', '{$pluginPath}/load.php' );
}

// Load the SQLite integration
if ( file_exists( SQLITE_MAIN_FILE ) ) {
    require_once SQLITE_MAIN_FILE;
}
PHP;
    }

    /**
     * Ensure the database directory exists.
     */
    private function ensureDatabaseDirectory(): void
    {
        $dbDir = dirname($this->getDatabasePath());
        if (! is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        // Create .htaccess to protect the database
        $htaccessPath = $dbDir . DIRECTORY_SEPARATOR . '.htaccess';
        if (! file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all\n");
        }
    }

    /**
     * Remove a directory recursively.
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
