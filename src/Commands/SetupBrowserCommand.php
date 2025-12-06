<?php

declare(strict_types=1);

namespace PestWP\Commands;

use RuntimeException;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function sprintf;
use function trim;

/**
 * Command to setup browser testing configuration.
 */
class SetupBrowserCommand
{
    private string $configPath;

    public function __construct(?string $configPath = null)
    {
        $this->configPath = $configPath ?? getcwd() . '/tests/Pest.php';
    }

    /**
     * Execute the setup command.
     */
    public function execute(string $baseUrl, string $adminUser, string $adminPassword): bool
    {
        $this->validateInputs($baseUrl, $adminUser, $adminPassword);
        $this->ensureConfigFileExists();

        $config = $this->generateBrowserConfig($baseUrl, $adminUser, $adminPassword);

        return $this->updateConfigFile($config);
    }

    /**
     * Validate user inputs.
     */
    private function validateInputs(string $baseUrl, string $adminUser, string $adminPassword): void
    {
        if (empty($baseUrl)) {
            throw new RuntimeException('Base URL cannot be empty');
        }

        if (! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Base URL must be a valid URL');
        }

        if (empty($adminUser)) {
            throw new RuntimeException('Admin username cannot be empty');
        }

        if (empty($adminPassword)) {
            throw new RuntimeException('Admin password cannot be empty');
        }
    }

    /**
     * Ensure the config file exists.
     */
    private function ensureConfigFileExists(): void
    {
        $dir = dirname($this->configPath);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (! file_exists($this->configPath)) {
            $template = <<<'PHP'
<?php

declare(strict_types=1);

use PestWP\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Integration');

/*
|--------------------------------------------------------------------------
| Browser Testing Configuration
|--------------------------------------------------------------------------
|
| This section will be automatically managed by the setup:browser command.
| Do not edit manually.
|
*/

PHP;

            file_put_contents($this->configPath, $template);
        }
    }

    /**
     * Generate browser configuration.
     */
    private function generateBrowserConfig(string $baseUrl, string $adminUser, string $adminPassword): string
    {
        return sprintf(
            <<<'PHP'

/*
|--------------------------------------------------------------------------
| Browser Testing Configuration
|--------------------------------------------------------------------------
|
| Configure your WordPress site for browser testing.
|
*/

function browser(): array
{
    return [
        'base_url' => '%s',
        'admin_user' => '%s',
        'admin_password' => '%s',
    ];
}

PHP,
            $baseUrl,
            $adminUser,
            $adminPassword,
        );
    }

    /**
     * Update config file with browser configuration.
     */
    private function updateConfigFile(string $config): bool
    {
        $content = file_get_contents($this->configPath);

        if ($content === false) {
            throw new RuntimeException('Failed to read config file');
        }

        // Remove existing browser configuration if present
        $pattern = '/\/\*\s*\|--------------------------------------------------------------------------\s*\| Browser Testing Configuration\s*\|--------------------------------------------------------------------------.*?\*\/\s*function browser\(\):.*?\{.*?\}/s';
        $content = (string) preg_replace($pattern, '', $content);

        // Ensure no double blank lines
        $content = (string) preg_replace('/\n{3,}/', "\n\n", $content);

        // Append new configuration
        $content = trim($content) . "\n" . $config;

        return file_put_contents($this->configPath, $content) !== false;
    }

    /**
     * Get the current browser configuration.
     *
     * @return array<string, string>|null
     */
    public function getCurrentConfig(): ?array
    {
        if (! file_exists($this->configPath)) {
            return null;
        }

        $content = file_get_contents($this->configPath);

        if ($content === false) {
            return null;
        }

        // Extract browser configuration
        if (preg_match('/function browser\(\):.*?\{(.*?)\}/s', $content, $matches)) {
            // Parse the configuration array
            if (preg_match_all("/'(\w+)'\s*=>\s*'([^']+)'/", $matches[1], $configMatches, PREG_SET_ORDER)) {
                $config = [];
                foreach ($configMatches as $match) {
                    $config[$match[1]] = $match[2];
                }

                return $config;
            }
        }

        return null;
    }
}
