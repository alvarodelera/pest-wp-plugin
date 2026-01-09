# API Stability

This document outlines the API stability commitment for PestWP v1.0.0 and beyond.

## Semantic Versioning

Starting with v1.0.0, PestWP follows [Semantic Versioning](https://semver.org/):

- **MAJOR** (x.0.0): Breaking changes
- **MINOR** (0.x.0): New features, backwards compatible
- **PATCH** (0.0.x): Bug fixes, backwards compatible

## Stable Public APIs

The following APIs are stable and will not have breaking changes without a major version bump:

### Factory Helpers (`PestWP\Functions\helpers.php`)

```php
createPost(array $args = []): WP_Post
createUser(string|array $roleOrArgs = 'subscriber', array $args = []): WP_User
createTerm(string $name, string $taxonomy = 'category', array $args = []): int
createAttachment(string $file = '', int $parentId = 0, array $args = []): int
createComment(int $postId, array $args = []): int
createPage(array $args = []): WP_Post
createOption(string $key, mixed $value): void
createTransient(string $key, mixed $value, int $expiration = 0): void
```

### Authentication Helpers (`PestWP\Functions\auth.php`)

```php
loginAs(WP_User|int $user): void
logout(): void
currentUser(): WP_User
isUserLoggedIn(): bool
```

### Configuration (`PestWP\Config`)

```php
Config::plugins(string|array $plugins): void
Config::muPlugins(string|array $plugins): void
Config::theme(string $theme): void
Config::beforeWordPress(callable $callback): void
Config::afterWordPress(callable $callback): void
```

### Database (`PestWP\Database`)

```php
TransactionManager::beginTransaction(): void
TransactionManager::rollback(): void
TransactionManager::isTransactionActive(): bool
TransactionManager::isAvailable(): bool
```

### REST API Testing (`PestWP\Functions\rest.php`)

```php
rest(): RestTester
RestTester::get(string $endpoint): RestResponse
RestTester::post(string $endpoint, array $data = []): RestResponse
RestTester::put(string $endpoint, array $data = []): RestResponse
RestTester::delete(string $endpoint): RestResponse
RestTester::as(WP_User|int $user): self
```

### AJAX Testing (`PestWP\Functions\ajax.php`)

```php
ajax(string $action, array $data = []): AjaxResponse
```

### Custom Expectations

All expectations in `src/Expectations/` are stable:

- `toBePublished()`, `toBeDraft()`, `toBePending()`, `toBePrivate()`, `toBeInTrash()`
- `toBeWPError()`, `toHaveErrorCode()`
- `toHaveMeta()`, `toHaveMetaKey()`, `toHaveUserMeta()`
- `toHaveAction()`, `toHaveFilter()`
- `toHaveTerm()`, `toBeRegisteredTaxonomy()`
- `toHaveCapability()`, `toHaveRole()`, `can()`
- `toBeRegisteredPostType()`, `toSupportFeature()`
- `toHaveOption()`, `toHaveTransient()`
- `toBeRegisteredShortcode()`
- `toMatchSnapshot()`

### Browser Testing Locators

All functions in `src/Functions/browser.php`, `gutenberg.php`, `woocommerce.php`, `viewport.php`, and `accessibility.php` are stable.

## Internal APIs

The following are considered internal and may change without notice:

- Classes in `src/Installer/`
- Classes in `src/Commands/` (CLI implementation details)
- Private/protected methods in all classes
- The `.pest/` directory structure

## Deprecation Policy

When an API needs to change:

1. The old API will be marked `@deprecated` with migration guidance
2. The deprecated API will continue to work for at least one minor version
3. Deprecated APIs will be removed only in major versions
4. The CHANGELOG will document all deprecations

## Backwards Compatibility

We guarantee:

1. **No breaking changes in minor/patch releases**: Your tests will continue to work
2. **Explicit deprecation notices**: You'll know what to update before it breaks
3. **Migration guides**: Clear documentation for any required changes

## Reporting Issues

If you encounter unexpected API behavior or breaking changes, please report them at:
https://github.com/alvarodelera/pest-wp-plugin/issues

## Version History

| Version | Date | Status |
|---------|------|--------|
| 1.0.0 | 2026-01-09 | Stable (current) |
| 0.x.x | 2025-2026 | Development (API may change) |
