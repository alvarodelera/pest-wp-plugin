<?php

declare(strict_types=1);

namespace PestWP\Arch;

use Pest\Arch\Expectations\Targeted;
use Pest\Arch\SingleArchExpectation;
use Pest\Expectation;
use PHPUnit\Architecture\Elements\ObjectDescription;

/**
 * WordPress Architecture Expectations.
 *
 * Extends Pest's expect() with WordPress-specific architecture checks.
 *
 * @internal
 */
final class Expectations
{
    /**
     * Register all WordPress architecture expectations.
     */
    public static function register(): void
    {
        self::registerDebugFunctionsExpectation();
        self::registerSecuritySensitiveFunctionsExpectation();
        self::registerDeprecatedMySQLFunctionsExpectation();
        self::registerDirectFileFunctionsExpectation();
        self::registerGlobalVariablesExpectation();
        self::registerDeprecatedWordPressFunctionsExpectation();
        self::registerWordPressPresetExpectation();
        self::registerProperHookRegistrationExpectation();
        self::registerNonceVerificationExpectation();
        self::registerCapabilityCheckExpectation();
        self::registerStrictTypesExpectation();
        self::registerFinalClassesExpectation();
        self::registerReadonlyClassesExpectation();
    }

    /**
     * Register the toUseDebugFunctions expectation.
     *
     * Checks if the target uses any debug functions like dd(), dump(), var_dump(), etc.
     */
    private static function registerDebugFunctionsExpectation(): void
    {

        expect()->extend('toUseDebugFunctions', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    foreach (WordPressArchPreset::DEBUG_FUNCTIONS as $function) {
                        if (self::containsFunctionCall($content, $function)) {
                            return false;
                        }
                    }

                    return true;
                },
                'to not use debug functions (dd, dump, var_dump, print_r, etc.)',
                static fn (string $path): int => self::findFirstOccurrenceLine(
                    $path,
                    WordPressArchPreset::DEBUG_FUNCTIONS,
                ),
            );
        });
    }

    /**
     * Register the toUseSecuritySensitiveFunctions expectation.
     *
     * Checks if the target uses security-sensitive functions like eval(), exec(), etc.
     */
    private static function registerSecuritySensitiveFunctionsExpectation(): void
    {

        expect()->extend('toUseSecuritySensitiveFunctions', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    foreach (WordPressArchPreset::SECURITY_SENSITIVE_FUNCTIONS as $function) {
                        if (self::containsFunctionCall($content, $function)) {
                            return false;
                        }
                    }

                    return true;
                },
                'to not use security-sensitive functions (eval, exec, shell_exec, etc.)',
                static fn (string $path): int => self::findFirstOccurrenceLine(
                    $path,
                    WordPressArchPreset::SECURITY_SENSITIVE_FUNCTIONS,
                ),
            );
        });
    }

    /**
     * Register the toUseDeprecatedMySQLFunctions expectation.
     *
     * Checks if the target uses deprecated mysql_* functions.
     */
    private static function registerDeprecatedMySQLFunctionsExpectation(): void
    {

        expect()->extend('toUseDeprecatedMySQLFunctions', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    foreach (WordPressArchPreset::DEPRECATED_MYSQL_FUNCTIONS as $function) {
                        if (self::containsFunctionCall($content, $function)) {
                            return false;
                        }
                    }

                    return true;
                },
                'to not use deprecated mysql_* functions',
                static fn (string $path): int => self::findFirstOccurrenceLine(
                    $path,
                    WordPressArchPreset::DEPRECATED_MYSQL_FUNCTIONS,
                ),
            );
        });
    }

    /**
     * Register the toUseDirectFileFunctions expectation.
     *
     * Checks if the target uses direct file functions instead of WordPress Filesystem API.
     */
    private static function registerDirectFileFunctionsExpectation(): void
    {

        expect()->extend('toUseDirectFileFunctions', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    foreach (WordPressArchPreset::DIRECT_FILE_FUNCTIONS as $function) {
                        if (self::containsFunctionCall($content, $function)) {
                            return false;
                        }
                    }

                    return true;
                },
                'to not use direct file functions (use WordPress Filesystem API instead)',
                static fn (string $path): int => self::findFirstOccurrenceLine(
                    $path,
                    WordPressArchPreset::DIRECT_FILE_FUNCTIONS,
                ),
            );
        });
    }

    /**
     * Register the toUseGlobalVariables expectation.
     *
     * Checks if the target uses global variables.
     */
    private static function registerGlobalVariablesExpectation(): void
    {

        expect()->extend('toUseGlobalVariables', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    // Check for global keyword
                    if (preg_match('/\bglobal\s+\$/', $content) === 1) {
                        return false;
                    }

                    // Check for $GLOBALS usage
                    if (str_contains($content, '$GLOBALS[')) {
                        return false;
                    }

                    return true;
                },
                'to not use global variables',
                static function (string $path): int {
                    $content = file_get_contents($path);
                    if ($content === false) {
                        return 1;
                    }

                    $lines = explode("\n", $content);
                    foreach ($lines as $lineNumber => $line) {
                        if (preg_match('/\bglobal\s+\$/', $line) === 1) {
                            return $lineNumber + 1;
                        }
                        if (str_contains($line, '$GLOBALS[')) {
                            return $lineNumber + 1;
                        }
                    }

                    return 1;
                },
            );
        });
    }

    /**
     * Register the toUseDeprecatedWordPressFunctions expectation.
     *
     * Checks if the target uses deprecated WordPress functions.
     */
    private static function registerDeprecatedWordPressFunctionsExpectation(): void
    {

        expect()->extend('toUseDeprecatedWordPressFunctions', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    foreach (array_keys(WordPressArchPreset::DEPRECATED_WP_FUNCTIONS) as $function) {
                        if (self::containsFunctionCall($content, $function)) {
                            return false;
                        }
                    }

                    return true;
                },
                'to not use deprecated WordPress functions',
                static fn (string $path): int => self::findFirstOccurrenceLine(
                    $path,
                    array_keys(WordPressArchPreset::DEPRECATED_WP_FUNCTIONS),
                ),
            );
        });
    }

    /**
     * Register the toUseWordPressPreset expectation.
     *
     * Comprehensive WordPress preset that checks for all common issues.
     */
    private static function registerWordPressPresetExpectation(): void
    {

        expect()->extend('toFollowWordPressPreset', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    // Check all forbidden functions
                    foreach (WordPressArchPreset::getAllForbiddenFunctions() as $function) {
                        if (self::containsFunctionCall($content, $function)) {
                            return false;
                        }
                    }

                    // Check for global variables
                    if (preg_match('/\bglobal\s+\$/', $content) === 1) {
                        return false;
                    }

                    return true;
                },
                'to follow WordPress coding standards',
                static fn (string $path): int => self::findFirstOccurrenceLine(
                    $path,
                    WordPressArchPreset::getAllForbiddenFunctions(),
                ),
            );
        });
    }

    /**
     * Register the toHaveProperHookRegistration expectation.
     *
     * Checks if hooks are registered with proper priority (not magic numbers).
     */
    private static function registerProperHookRegistrationExpectation(): void
    {

        expect()->extend('toHaveProperHookRegistration', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    // Check for add_action/add_filter with inline closures
                    // This is a heuristic - inline closures are harder to test
                    // Allow short closures (fn) as they're typically simple
                    $hookPattern = '/\b(add_action|add_filter)\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*function\s*\(/';
                    if (preg_match($hookPattern, $content) === 1) {
                        return false;
                    }

                    return true;
                },
                'to have proper hook registration (avoid inline anonymous functions in add_action/add_filter)',
                static function (string $path): int {
                    $content = file_get_contents($path);
                    if ($content === false) {
                        return 1;
                    }

                    $lines = explode("\n", $content);
                    $hookPattern = '/\b(add_action|add_filter)\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*function\s*\(/';

                    foreach ($lines as $lineNumber => $line) {
                        if (preg_match($hookPattern, $line) === 1) {
                            return $lineNumber + 1;
                        }
                    }

                    return 1;
                },
            );
        });
    }

    /**
     * Register the toVerifyNonces expectation.
     *
     * Checks if form handlers verify nonces.
     */
    private static function registerNonceVerificationExpectation(): void
    {

        expect()->extend('toVerifyNonces', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    // Check if file handles POST data
                    if (! str_contains($content, '$_POST') && ! str_contains($content, '$_REQUEST')) {
                        return true; // No POST handling, skip check
                    }

                    // If handling POST, should have nonce verification
                    $noncePatterns = [
                        'wp_verify_nonce',
                        'check_admin_referer',
                        'wp_nonce_field',
                        'check_ajax_referer',
                    ];

                    foreach ($noncePatterns as $pattern) {
                        if (str_contains($content, $pattern)) {
                            return true;
                        }
                    }

                    return false;
                },
                'to verify nonces when handling POST/REQUEST data',
                static function (string $path): int {
                    $content = file_get_contents($path);
                    if ($content === false) {
                        return 1;
                    }

                    $lines = explode("\n", $content);
                    foreach ($lines as $lineNumber => $line) {
                        if (str_contains($line, '$_POST') || str_contains($line, '$_REQUEST')) {
                            return $lineNumber + 1;
                        }
                    }

                    return 1;
                },
            );
        });
    }

    /**
     * Register the toCheckCapabilities expectation.
     *
     * Checks if admin actions check user capabilities.
     */
    private static function registerCapabilityCheckExpectation(): void
    {

        expect()->extend('toCheckCapabilities', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    // Check if file has admin-related hooks
                    $adminPatterns = [
                        'admin_init',
                        'admin_menu',
                        'admin_post_',
                        'wp_ajax_',
                    ];

                    $hasAdminCode = false;
                    foreach ($adminPatterns as $pattern) {
                        if (str_contains($content, $pattern)) {
                            $hasAdminCode = true;
                            break;
                        }
                    }

                    if (! $hasAdminCode) {
                        return true; // No admin code, skip check
                    }

                    // Should have capability check
                    $capabilityPatterns = [
                        'current_user_can',
                        'user_can',
                        'map_meta_cap',
                    ];

                    foreach ($capabilityPatterns as $pattern) {
                        if (str_contains($content, $pattern)) {
                            return true;
                        }
                    }

                    return false;
                },
                'to check user capabilities in admin code',
                static function (string $path): int {
                    $content = file_get_contents($path);
                    if ($content === false) {
                        return 1;
                    }

                    $adminPatterns = ['admin_init', 'admin_menu', 'admin_post_', 'wp_ajax_'];
                    $lines = explode("\n", $content);

                    foreach ($lines as $lineNumber => $line) {
                        foreach ($adminPatterns as $pattern) {
                            if (str_contains($line, $pattern)) {
                                return $lineNumber + 1;
                            }
                        }
                    }

                    return 1;
                },
            );
        });
    }

    /**
     * Register the toUseStrictTypes expectation.
     *
     * Checks if files declare strict types.
     */
    private static function registerStrictTypesExpectation(): void
    {

        expect()->extend('toUseStrictTypes', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    return str_contains($content, 'declare(strict_types=1)');
                },
                'to use declare(strict_types=1)',
                static fn (string $path): int => 1,
            );
        });
    }

    /**
     * Register the toBeFinal expectation.
     *
     * Checks if classes are declared as final.
     */
    private static function registerFinalClassesExpectation(): void
    {

        expect()->extend('toBeFinalClasses', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    // Check if file contains a non-final class
                    // Matches "class Name" but not "final class Name" or "abstract class Name"
                    $pattern = '/(?<!final\s)(?<!abstract\s)\bclass\s+\w+/';
                    if (preg_match($pattern, $content) === 1) {
                        // Verify it's not an interface or trait
                        if (preg_match('/\b(interface|trait)\s+/', $content) === 0) {
                            return false;
                        }
                    }

                    return true;
                },
                'to be final classes',
                static function (string $path): int {
                    $content = file_get_contents($path);
                    if ($content === false) {
                        return 1;
                    }

                    $lines = explode("\n", $content);
                    $pattern = '/(?<!final\s)(?<!abstract\s)\bclass\s+\w+/';

                    foreach ($lines as $lineNumber => $line) {
                        if (preg_match($pattern, $line) === 1) {
                            return $lineNumber + 1;
                        }
                    }

                    return 1;
                },
            );
        });
    }

    /**
     * Register the toBeReadonly expectation.
     *
     * Checks if classes are declared as readonly (PHP 8.2+).
     */
    private static function registerReadonlyClassesExpectation(): void
    {

        expect()->extend('toBeReadonlyClasses', function (): SingleArchExpectation {
            /** @var Expectation<array<int, string>|string> $this */
            return Targeted::make(
                $this,
                static function (ObjectDescription $object): bool {
                    $content = file_get_contents($object->path);
                    if ($content === false) {
                        return true;
                    }

                    // Check if file contains a non-readonly class
                    if (preg_match('/\bclass\s+\w+/', $content) === 1) {
                        // Verify it has readonly
                        if (preg_match('/\breadonly\s+(final\s+)?class\s+/', $content) === 0
                            && preg_match('/\bfinal\s+readonly\s+class\s+/', $content) === 0) {
                            // Skip interfaces and traits
                            if (preg_match('/\b(interface|trait)\s+/', $content) === 0) {
                                return false;
                            }
                        }
                    }

                    return true;
                },
                'to be readonly classes',
                static function (string $path): int {
                    $content = file_get_contents($path);
                    if ($content === false) {
                        return 1;
                    }

                    $lines = explode("\n", $content);

                    foreach ($lines as $lineNumber => $line) {
                        if (preg_match('/\bclass\s+\w+/', $line) === 1) {
                            return $lineNumber + 1;
                        }
                    }

                    return 1;
                },
            );
        });
    }

    /**
     * Check if content contains a function call.
     */
    private static function containsFunctionCall(string $content, string $function): bool
    {
        // Remove string contents to avoid false positives
        $contentWithoutStrings = preg_replace('/"(?:[^"\\\\]|\\\\.)*"/', '""', $content) ?? $content;
        $contentWithoutStrings = preg_replace("/\'(?:[^\'\\\\]|\\\\.)*\'/", "''", $contentWithoutStrings) ?? $contentWithoutStrings;

        // Remove comments
        $contentWithoutComments = preg_replace('/\/\/.*$/m', '', $contentWithoutStrings) ?? $contentWithoutStrings;
        $contentWithoutComments = preg_replace('/\/\*.*?\*\//s', '', $contentWithoutComments) ?? $contentWithoutComments;

        // Look for function call pattern
        $pattern = '/\b' . preg_quote($function, '/') . '\s*\(/';

        return preg_match($pattern, $contentWithoutComments) === 1;
    }

    /**
     * Find the first occurrence line of any function in the list.
     *
     * @param array<int|string, string> $functions
     */
    private static function findFirstOccurrenceLine(string $path, array $functions): int
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return 1;
        }

        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            foreach ($functions as $function) {
                $pattern = '/\b' . preg_quote($function, '/') . '\s*\(/';
                if (preg_match($pattern, $line) === 1) {
                    return $lineNumber + 1;
                }
            }
        }

        return 1;
    }
}
