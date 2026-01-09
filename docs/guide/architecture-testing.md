# Architecture Testing

PestWP extends Pest's architecture testing with WordPress-specific presets and helpers.

## Overview

Architecture testing ensures your codebase follows best practices and coding standards. PestWP provides WordPress-specific rules to catch common security and performance issues.

## The wordpress() Helper

```php
use function PestWP\Functions\wordpress;

// Test a namespace
wordpress('App')->noDebugFunctions();

// Test multiple namespaces
wordpress(['App', 'MyPlugin'])->noSecuritySensitiveFunctions();

// Alias: wpArch()
wpArch('App')->noDebugFunctions();
```

## Debug Functions

Ensure no debug functions are left in production code:

```php
arch('no debug functions in production code')
    ->expect('App')
    ->not->toUse(['var_dump', 'print_r', 'dump', 'dd', 'error_log']);

// Using the wordpress() helper
test('no debug functions', function () {
    wordpress('App')->noDebugFunctions();
});
```

### Detected Debug Functions

- `var_dump`
- `print_r`
- `var_export` (when output to screen)
- `dump`
- `dd`
- `error_log`
- `debug_print_backtrace`
- `debug_backtrace` (when outputting)

## Security-Sensitive Functions

Detect potentially dangerous functions:

```php
test('no security-sensitive functions', function () {
    wordpress('App')->noSecuritySensitiveFunctions();
});
```

### Detected Security Functions

- `eval`
- `exec`
- `shell_exec`
- `system`
- `passthru`
- `popen`
- `proc_open`
- `pcntl_exec`

## WordPress Coding Standards

### Direct Database Queries

Ensure proper database access:

```php
arch('uses wpdb properly')
    ->expect('App\\Repositories')
    ->toUseWpdb()
    ->not->toUseRawSql();

test('no direct database queries', function () {
    wordpress('App')->usesWpdbProperly();
});
```

### Escaping Output

Verify output is properly escaped:

```php
test('escapes output', function () {
    wordpress('App\\Views')
        ->usesEscaping(['esc_html', 'esc_attr', 'esc_url', 'wp_kses']);
});
```

### Nonce Verification

Ensure forms and AJAX handlers verify nonces:

```php
test('verifies nonces', function () {
    wordpress('App\\Controllers')
        ->verifiesNonces();
});
```

### Capability Checks

Ensure proper capability checks:

```php
test('checks capabilities', function () {
    wordpress('App\\Admin')
        ->checksCapabilities(['current_user_can', 'user_can']);
});
```

## Namespace Rules

### Strict Namespacing

```php
arch('controllers have proper namespace')
    ->expect('App\\Controllers')
    ->toBeClasses()
    ->toHaveSuffix('Controller');

arch('models use proper namespace')
    ->expect('App\\Models')
    ->toBeClasses()
    ->toHaveSuffix('Model');
```

### No Global Functions

```php
arch('no global functions')
    ->expect('App')
    ->toOnlyUseClasses();
```

### Interface Implementation

```php
arch('repositories implement interface')
    ->expect('App\\Repositories')
    ->toImplement('App\\Contracts\\Repository');

arch('services are final')
    ->expect('App\\Services')
    ->toBeFinal();
```

## Dependency Rules

### Layer Architecture

```php
arch('controllers only use services')
    ->expect('App\\Controllers')
    ->toOnlyUse('App\\Services');

arch('services don\'t use controllers')
    ->expect('App\\Services')
    ->not->toUse('App\\Controllers');

arch('models are independent')
    ->expect('App\\Models')
    ->not->toUse(['App\\Controllers', 'App\\Services']);
```

### External Dependencies

```php
arch('limits external dependencies')
    ->expect('App')
    ->toOnlyUse([
        'App',
        'WordPress',
        'Psr\\Log',
    ]);
```

## WordPress-Specific Presets

### Plugin Preset

```php
test('follows plugin standards', function () {
    wordpress('MyPlugin')
        ->noDebugFunctions()
        ->noSecuritySensitiveFunctions()
        ->usesWpdbProperly()
        ->prefixesFunctions('myplugin_');
});
```

### Theme Preset

```php
test('follows theme standards', function () {
    wordpress('MyTheme')
        ->noDebugFunctions()
        ->escapesOutput()
        ->usesTemplateHierarchy();
});
```

## Custom Rules

### Define Custom Checks

```php
use PestWP\Arch\WordPressArchHelper;

// Extend with custom check
WordPressArchHelper::extend('noHardcodedUrls', function () {
    return $this->expect->not->toMatch('/https?:\\/\\/[^"\']+/');
});

// Use it
test('no hardcoded URLs', function () {
    wordpress('App')->noHardcodedUrls();
});
```

## Example Test Suite

```php
<?php

// tests/Arch/ArchitectureTest.php

describe('architecture', function () {
    test('no debug functions in app code', function () {
        wordpress('App')->noDebugFunctions();
    });

    test('no security-sensitive functions', function () {
        wordpress('App')->noSecuritySensitiveFunctions();
    });

    test('controllers are properly namespaced', function () {
        arch()
            ->expect('App\\Controllers')
            ->toBeClasses()
            ->toHaveSuffix('Controller')
            ->toExtend('App\\Controllers\\BaseController');
    });

    test('models use strict types', function () {
        arch()
            ->expect('App\\Models')
            ->toUseStrictTypes();
    });

    test('services are final', function () {
        arch()
            ->expect('App\\Services')
            ->toBeFinal();
    });

    test('no direct database queries in controllers', function () {
        arch()
            ->expect('App\\Controllers')
            ->not->toUse('wpdb');
    });

    test('repositories handle database access', function () {
        arch()
            ->expect('App\\Repositories')
            ->toUse('wpdb');
    });
});
```

## Running Architecture Tests

```bash
# Run all architecture tests
./vendor/bin/pest tests/Arch

# Run with verbose output
./vendor/bin/pest tests/Arch --verbose

# Filter by test name
./vendor/bin/pest --filter="no debug"
```

## Best Practices

1. **Run Early**: Include architecture tests in CI/CD pipeline

2. **Be Specific**: Target specific namespaces rather than entire codebase

3. **Incremental Adoption**: Start with critical rules, add more over time

4. **Document Exceptions**: If rules need exceptions, document why

5. **Layer Testing**: Test each architectural layer separately

```php
describe('layers', function () {
    test('presentation layer')
        ->expect('App\\Http')
        ->not->toUse(['App\\Models', 'wpdb']);

    test('business layer')
        ->expect('App\\Services')
        ->not->toUse('App\\Http');

    test('data layer')
        ->expect('App\\Repositories')
        ->not->toUse('App\\Http');
});
```

## Integration with Pest arch()

PestWP works alongside Pest's native `arch()` function:

```php
// Native Pest arch testing
arch('uses strict types')
    ->expect('App')
    ->toUseStrictTypes();

// Combined with WordPress helper
test('wordpress and pest arch', function () {
    wordpress('App')->noDebugFunctions();
    
    arch()
        ->expect('App')
        ->toUseStrictTypes();
});
```

## Next Steps

- [Configuration](configuration.md) - Configure architecture tests
- [CI/CD](ci-cd.md) - Run architecture tests in pipelines
- [Pest Architecture Testing](https://pestphp.com/docs/arch-testing) - Pest documentation
