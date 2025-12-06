<?php

declare(strict_types=1);

namespace PestWP\Installer;

use RuntimeException;

/**
 * Main installer that orchestrates the complete WordPress test environment setup.
 *
 * This class coordinates the installation of:
 * - WordPress core
 * - SQLite database integration
 * - Test configuration files
 * - WordPress test library
 */
final class Installer
{
    private WordPressInstaller $wordPressInstaller;

    private SQLiteInstaller $sqliteInstaller;

    private ConfigGenerator $configGenerator;

    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? getcwd() ?: __DIR__;

        $this->wordPressInstaller = new WordPressInstaller($this->basePath);
        $this->sqliteInstaller = new SQLiteInstaller(
            $this->wordPressInstaller->getInstallPath(),
            $this->wordPressInstaller->getPestPath(),
        );
        $this->configGenerator = new ConfigGenerator(
            $this->wordPressInstaller->getInstallPath(),
            $this->wordPressInstaller->getPestPath(),
        );
    }

    /**
     * Check if the complete environment is installed.
     */
    public function isInstalled(): bool
    {
        return $this->wordPressInstaller->isInstalled()
            && $this->sqliteInstaller->isInstalled()
            && $this->configGenerator->configExists();
    }

    /**
     * Install the complete test environment.
     *
     * @param  bool  $force  Force reinstallation
     * @param  callable|null  $onProgress  Progress callback: fn(string $message, int $step, int $total)
     *
     * @throws RuntimeException If installation fails
     */
    public function install(bool $force = false, ?callable $onProgress = null): void
    {
        $steps = 3;
        $progress = $onProgress ?? fn () => null;

        // Step 1: Install WordPress
        $progress('Downloading WordPress...', 1, $steps);
        $this->wordPressInstaller->install($force);

        // Step 2: Install SQLite integration
        $progress('Installing SQLite integration...', 2, $steps);
        $this->sqliteInstaller->install($force);

        // Step 3: Generate configuration
        $progress('Generating test configuration...', 3, $steps);
        $this->configGenerator->generate($force);
    }

    /**
     * Get the WordPress installation path.
     */
    public function getWordPressPath(): string
    {
        return $this->wordPressInstaller->getInstallPath();
    }

    /**
     * Get the .pest directory path.
     */
    public function getPestPath(): string
    {
        return $this->wordPressInstaller->getPestPath();
    }

    /**
     * Get the test configuration file path.
     */
    public function getConfigPath(): string
    {
        return $this->configGenerator->getConfigPath();
    }

    /**
     * Get the test library path.
     */
    public function getTestLibraryPath(): string
    {
        return $this->configGenerator->getTestLibraryPath();
    }

    /**
     * Get the SQLite database path.
     */
    public function getDatabasePath(): string
    {
        return $this->sqliteInstaller->getDatabasePath();
    }

    /**
     * Get the installed WordPress version.
     */
    public function getWordPressVersion(): ?string
    {
        return $this->wordPressInstaller->getInstalledVersion();
    }

    /**
     * Get the WordPress installer instance.
     */
    public function getWordPressInstaller(): WordPressInstaller
    {
        return $this->wordPressInstaller;
    }

    /**
     * Get the SQLite installer instance.
     */
    public function getSQLiteInstaller(): SQLiteInstaller
    {
        return $this->sqliteInstaller;
    }

    /**
     * Get the config generator instance.
     */
    public function getConfigGenerator(): ConfigGenerator
    {
        return $this->configGenerator;
    }
}
