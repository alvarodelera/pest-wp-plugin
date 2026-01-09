<?php

declare(strict_types=1);

namespace PestWP\Arch;

use Pest\Arch\SingleArchExpectation;
use Pest\Expectation;

/**
 * WordPress Architecture Helper.
 *
 * Provides a fluent API for WordPress-specific architecture testing.
 * This class is used internally by the wordpress() helper function.
 *
 * @example
 * ```php
 * // Using the helper function
 * arch('wordpress preset')
 *     ->expect('App')
 *     ->not->toUseDebugFunctions();
 * ```
 */
final class WordPressArchHelper
{
    /**
     * The target namespaces or paths to test.
     *
     * @var array<int, string>
     */
    private array $targets = [];

    /**
     * The namespaces or paths to ignore.
     *
     * @var array<int, string>
     */
    private array $ignoring = [];

    /**
     * Create a new WordPressArchHelper instance.
     *
     * @param array<int, string>|string $targets
     */
    public function __construct(array|string $targets = [])
    {
        $this->targets = is_array($targets) ? $targets : [$targets];
    }

    /**
     * Set the target namespaces or paths.
     *
     * @param array<int, string>|string $targets
     */
    public function expect(array|string $targets): self
    {
        $this->targets = is_array($targets) ? $targets : [$targets];

        return $this;
    }

    /**
     * Add namespaces or paths to ignore.
     *
     * @param array<int, string>|string $ignore
     */
    public function ignoring(array|string $ignore): self
    {
        $ignoreArray = is_array($ignore) ? $ignore : [$ignore];
        $this->ignoring = array_merge($this->ignoring, $ignoreArray);

        return $this;
    }

    /**
     * Get the target paths.
     *
     * @return array<int, string>
     */
    public function getTargets(): array
    {
        return $this->targets;
    }

    /**
     * Get the ignored paths.
     *
     * @return array<int, string>
     */
    public function getIgnoring(): array
    {
        return $this->ignoring;
    }

    /**
     * Apply the no debug functions preset.
     *
     * Ensures no debug functions (dd, dump, var_dump, etc.) are used.
     */
    public function noDebugFunctions(): SingleArchExpectation
    {
        return expect($this->targets)->not->toUseDebugFunctions();
    }

    /**
     * Apply the no security sensitive functions preset.
     *
     * Ensures no security-sensitive functions (eval, exec, etc.) are used.
     */
    public function noSecuritySensitiveFunctions(): SingleArchExpectation
    {
        return expect($this->targets)->not->toUseSecuritySensitiveFunctions();
    }

    /**
     * Apply the no deprecated MySQL functions preset.
     *
     * Ensures no deprecated mysql_* functions are used.
     */
    public function noDeprecatedMySQLFunctions(): SingleArchExpectation
    {
        return expect($this->targets)->not->toUseDeprecatedMySQLFunctions();
    }

    /**
     * Apply the no direct file functions preset.
     *
     * Ensures WordPress Filesystem API is used instead of direct file functions.
     */
    public function noDirectFileFunctions(): SingleArchExpectation
    {
        return expect($this->targets)->not->toUseDirectFileFunctions();
    }

    /**
     * Apply the no global variables preset.
     *
     * Ensures no global variables are used.
     */
    public function noGlobalVariables(): SingleArchExpectation
    {
        return expect($this->targets)->not->toUseGlobalVariables();
    }

    /**
     * Apply the no deprecated WordPress functions preset.
     *
     * Ensures no deprecated WordPress functions are used.
     */
    public function noDeprecatedWordPressFunctions(): SingleArchExpectation
    {
        return expect($this->targets)->not->toUseDeprecatedWordPressFunctions();
    }

    /**
     * Apply the proper hook registration preset.
     *
     * Ensures hooks are registered with named callbacks (not inline closures).
     */
    public function properHookRegistration(): SingleArchExpectation
    {
        return expect($this->targets)->toHaveProperHookRegistration();
    }

    /**
     * Apply the nonce verification preset.
     *
     * Ensures form handlers verify nonces.
     */
    public function verifyNonces(): SingleArchExpectation
    {
        return expect($this->targets)->toVerifyNonces();
    }

    /**
     * Apply the capability check preset.
     *
     * Ensures admin actions check user capabilities.
     */
    public function checkCapabilities(): SingleArchExpectation
    {
        return expect($this->targets)->toCheckCapabilities();
    }

    /**
     * Apply the strict types preset.
     *
     * Ensures all files declare strict types.
     */
    public function useStrictTypes(): SingleArchExpectation
    {
        return expect($this->targets)->toUseStrictTypes();
    }

    /**
     * Apply the final classes preset.
     *
     * Ensures all classes are declared as final.
     */
    public function useFinalClasses(): SingleArchExpectation
    {
        return expect($this->targets)->toBeFinalClasses();
    }

    /**
     * Apply the readonly classes preset.
     *
     * Ensures all classes are declared as readonly (PHP 8.2+).
     */
    public function useReadonlyClasses(): SingleArchExpectation
    {
        return expect($this->targets)->toBeReadonlyClasses();
    }

    /**
     * Apply the WordPress coding standards preset.
     *
     * Comprehensive check for WordPress best practices.
     */
    public function followWordPressPreset(): SingleArchExpectation
    {
        return expect($this->targets)->toFollowWordPressPreset();
    }

    /**
     * Get an expectation for the targets.
     *
     * @return Expectation<array<int, string>|string>
     */
    public function getExpectation(): Expectation
    {
        $target = count($this->targets) === 1 ? $this->targets[0] : $this->targets;

        /** @var Expectation<array<int, string>|string> */
        return expect($target);
    }
}
