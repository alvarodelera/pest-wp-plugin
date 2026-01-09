# Architecture Testing

PestWP provides WordPress-specific architecture testing that integrates with Pest's `arch()` function. This allows you to enforce coding standards, security best practices, and architectural patterns in your WordPress plugins and themes.

## Quick Start

```php
// tests/Arch/PluginArchTest.php

arch('no debug functions in production code')
    ->expect('MyPlugin')
    ->not->toUseDebugFunctions();

arch('no security vulnerabilities')
    ->expect('MyPlugin')
    ->not->toUseSecuritySensitiveFunctions();

arch('use strict types everywhere')
    ->expect('MyPlugin')
    ->toUseStrictTypes();
```

## Available Expectations

### Debug Functions

Ensures no debug functions are used in production code:

```php
arch('no debug functions')
    ->expect('MyPlugin')
    ->not->toUseDebugFunctions();
```

Detected functions: `dd`, `dump`, `var_dump`, `print_r`, `var_export`, `debug_print_backtrace`, `debug_backtrace`, `error_log`

### Security-Sensitive Functions

Ensures no security-sensitive functions are used:

```php
arch('no security vulnerabilities')
    ->expect('MyPlugin')
    ->not->toUseSecuritySensitiveFunctions();
```

Detected functions: `eval`, `exec`, `shell_exec`, `system`, `passthru`, `popen`, `proc_open`, `pcntl_exec`, `extract`, `parse_str`, `unserialize`, `assert`

### Deprecated MySQL Functions

Ensures no deprecated `mysql_*` functions are used:

```php
arch('no deprecated MySQL')
    ->expect('MyPlugin')
    ->not->toUseDeprecatedMySQLFunctions();
```

Detected functions: `mysql_connect`, `mysql_query`, `mysql_fetch_array`, `mysql_fetch_assoc`, etc.

### Direct File Functions

Ensures WordPress Filesystem API is used instead of direct file operations:

```php
arch('use WordPress Filesystem API')
    ->expect('MyPlugin')
    ->not->toUseDirectFileFunctions();
```

Detected functions: `file_put_contents`, `file_get_contents`, `fopen`, `fwrite`, `mkdir`, `unlink`, etc.

### Global Variables

Ensures no global variables are used:

```php
arch('no globals')
    ->expect('MyPlugin')
    ->not->toUseGlobalVariables();
```

Detected patterns: `global $variable`, `$GLOBALS['key']`

### Deprecated WordPress Functions

Ensures no deprecated WordPress functions are used:

```php
arch('no deprecated WordPress functions')
    ->expect('MyPlugin')
    ->not->toUseDeprecatedWordPressFunctions();
```

Detected functions: `get_currentuserinfo`, `user_pass_ok`, `wp_get_single_post`, etc.

### Strict Types

Ensures all files declare strict types:

```php
arch('strict types')
    ->expect('MyPlugin')
    ->toUseStrictTypes();
```

### Final Classes

Ensures all classes are declared as final:

```php
arch('final classes')
    ->expect('MyPlugin')
    ->toBeFinalClasses();
```

### Readonly Classes

Ensures all classes are declared as readonly (PHP 8.2+):

```php
arch('readonly classes')
    ->expect('MyPlugin')
    ->toBeReadonlyClasses();
```

### WordPress Coding Standards Preset

Comprehensive check for WordPress best practices:

```php
arch('wordpress standards')
    ->expect('MyPlugin')
    ->toFollowWordPressPreset();
```

This checks for:
- No debug functions
- No deprecated MySQL functions
- No security-sensitive functions
- No global variables

### Proper Hook Registration

Ensures hooks are registered with named callbacks (not inline anonymous functions):

```php
arch('proper hooks')
    ->expect('MyPlugin')
    ->toHaveProperHookRegistration();
```

This fails if you use:
```php
add_action('init', function() {
    // This makes testing harder
});
```

Instead, use:
```php
add_action('init', [$this, 'onInit']);
// or
add_action('init', 'my_plugin_init');
```

### Nonce Verification

Ensures form handlers verify nonces:

```php
arch('nonce verification')
    ->expect('MyPlugin\\Admin')
    ->toVerifyNonces();
```

### Capability Checks

Ensures admin actions check user capabilities:

```php
arch('capability checks')
    ->expect('MyPlugin\\Admin')
    ->toCheckCapabilities();
```

## Using the Helper Function

For convenience, you can use the `wordpress()` helper function with preset methods:

```php
use function PestWP\Functions\wordpress;

test('wordpress architecture', function () {
    wordpress('MyPlugin')->noDebugFunctions();
    wordpress('MyPlugin')->noSecuritySensitiveFunctions();
    wordpress('MyPlugin')->useStrictTypes();
});
```

Available preset methods:
- `noDebugFunctions()` - No debug functions
- `noSecuritySensitiveFunctions()` - No security-sensitive functions
- `noDeprecatedMySQLFunctions()` - No deprecated MySQL functions
- `noDirectFileFunctions()` - Use WordPress Filesystem API
- `noGlobalVariables()` - No global variables
- `noDeprecatedWordPressFunctions()` - No deprecated WP functions
- `properHookRegistration()` - Named hook callbacks
- `verifyNonces()` - Nonce verification in forms
- `checkCapabilities()` - Capability checks in admin
- `useStrictTypes()` - Declare strict types
- `useFinalClasses()` - Final classes only
- `useReadonlyClasses()` - Readonly classes (PHP 8.2+)
- `followWordPressPreset()` - All WordPress best practices

## Ignoring Paths

You can ignore specific namespaces or paths:

```php
arch('no debug functions')
    ->expect('MyPlugin')
    ->not->toUseDebugFunctions()
    ->ignoring('MyPlugin\\Legacy');
```

Or ignore multiple paths:

```php
arch('no debug functions')
    ->expect('MyPlugin')
    ->not->toUseDebugFunctions()
    ->ignoring([
        'MyPlugin\\Legacy',
        'MyPlugin\\Deprecated',
    ]);
```

## Ignoring Lines

You can ignore specific lines using comments:

```php
// @pest-arch-ignore-next-line
eval($code); // This line is ignored

eval($code); // @pest-arch-ignore-line
```

## Complete Example

Here's a complete architecture test file for a WordPress plugin:

```php
<?php
// tests/Arch/PluginArchTest.php

declare(strict_types=1);

// Security
arch('no debug functions in production')
    ->expect('MyPlugin')
    ->not->toUseDebugFunctions()
    ->ignoring('MyPlugin\\Debug');

arch('no security vulnerabilities')
    ->expect('MyPlugin')
    ->not->toUseSecuritySensitiveFunctions();

arch('no deprecated mysql functions')
    ->expect('MyPlugin')
    ->not->toUseDeprecatedMySQLFunctions();

// WordPress Best Practices
arch('use wordpress filesystem api')
    ->expect('MyPlugin\\FileSystem')
    ->not->toUseDirectFileFunctions();

arch('no global variables')
    ->expect('MyPlugin')
    ->not->toUseGlobalVariables()
    ->ignoring('MyPlugin\\Legacy');

arch('proper hook registration')
    ->expect('MyPlugin')
    ->toHaveProperHookRegistration();

// Admin Security
arch('admin handlers verify nonces')
    ->expect('MyPlugin\\Admin')
    ->toVerifyNonces();

arch('admin handlers check capabilities')
    ->expect('MyPlugin\\Admin')
    ->toCheckCapabilities();

// Code Quality
arch('all files use strict types')
    ->expect('MyPlugin')
    ->toUseStrictTypes();

arch('classes are final')
    ->expect('MyPlugin')
    ->toBeFinalClasses()
    ->ignoring([
        'MyPlugin\\Abstracts',
        'MyPlugin\\Contracts',
    ]);
```

## Preset Constants

The `WordPressArchPreset` class provides constants you can use in custom checks:

```php
use PestWP\Arch\WordPressArchPreset;

// Debug functions
WordPressArchPreset::DEBUG_FUNCTIONS;

// Security-sensitive functions
WordPressArchPreset::SECURITY_SENSITIVE_FUNCTIONS;

// Deprecated MySQL functions
WordPressArchPreset::DEPRECATED_MYSQL_FUNCTIONS;

// Direct file functions
WordPressArchPreset::DIRECT_FILE_FUNCTIONS;

// WordPress escaping functions
WordPressArchPreset::ESCAPING_FUNCTIONS;

// WordPress sanitization functions
WordPressArchPreset::SANITIZATION_FUNCTIONS;

// Deprecated WordPress functions with messages
WordPressArchPreset::DEPRECATED_WP_FUNCTIONS;
```

## Helper Methods

```php
use PestWP\Arch\WordPressArchPreset;

// Check if a function is in a category
WordPressArchPreset::isDebugFunction('dd'); // true
WordPressArchPreset::isSecuritySensitive('eval'); // true
WordPressArchPreset::isDirectFileFunction('fopen'); // true
WordPressArchPreset::isDeprecatedWordPressFunction('user_pass_ok'); // true

// Get deprecation messages
$message = WordPressArchPreset::getDeprecationMessage('get_currentuserinfo');
// "get_currentuserinfo() is deprecated since 4.5, use wp_get_current_user()"

// Get all functions in a category
$debugFunctions = WordPressArchPreset::getDebugFunctions();
$allForbidden = WordPressArchPreset::getAllForbiddenFunctions();
```

## Best Practices

1. **Create a dedicated test directory**: Put architecture tests in `tests/Arch/` to separate them from unit and integration tests.

2. **Run arch tests in CI**: Architecture tests are fast and should run on every commit:
   ```bash
   vendor/bin/pest --group=arch
   ```

3. **Start strict, relax as needed**: Begin with strict rules and add exceptions as needed using `ignoring()`.

4. **Document exceptions**: When ignoring paths, add comments explaining why:
   ```php
   ->ignoring('MyPlugin\\Legacy') // Legacy code, will be refactored in v2.0
   ```

5. **Use descriptive test names**: Make it clear what architectural rule is being enforced:
   ```php
   arch('admin handlers must verify nonces before processing forms')
   ```
