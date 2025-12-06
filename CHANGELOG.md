# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.1] - 2025-12-07

### Added

- **Plugin configuration system** (`PestWP\Config`) for loading plugins during tests:
  - `Config::plugins()` - Register plugins to load when WordPress boots
  - `Config::muPlugins()` - Register MU-plugins to load
  - `Config::theme()` - Set active theme for tests
  - `Config::beforeWordPress()` - Execute callbacks before WordPress loads
  - `Config::afterWordPress()` - Execute callbacks after WordPress loads
- Comprehensive documentation for plugin developers in README
- Unit tests for Config class (21 new tests)

### Fixed

- **Cross-platform compatibility** for Windows, Linux, and macOS:
  - `WordPressInstaller` now uses `copyDirectory()` fallback when `rename()` fails
  - `SQLiteInstaller` now uses `copyDirectory()` fallback when `rename()` fails
  - Fixes "Access denied" errors on Windows during WordPress extraction

## [0.1.0] - 2025-12-06

### Added

- **Zero-config WordPress testing** with automatic SQLite setup
- **Database isolation** using file snapshots (~1.7ms per test)
- **Type-safe factory helpers**: `createPost()`, `createUser()`, `createTerm()`, `createAttachment()`
- **Authentication helpers**: `loginAs()`, `logout()`, `currentUser()`, `isUserLoggedIn()`
- **Custom Pest expectations** for WordPress:
  - Post status: `toBePublished()`, `toBeDraft()`, `toBePending()`, `toBePrivate()`, `toBeInTrash()`
  - WP_Error: `toBeWPError()`, `toHaveErrorCode()`
  - Metadata: `toHaveMeta()`, `toHaveMetaKey()`, `toHaveUserMeta()`
  - Hooks: `toHaveAction()`, `toHaveFilter()`
  - Terms: `toHaveTerm()`, `toBeRegisteredTaxonomy()`
  - Users: `toHaveCapability()`, `toHaveRole()`, `can()`
  - Post types: `toBeRegisteredPostType()`, `toSupportFeature()`
  - Options: `toHaveOption()`, `toHaveTransient()`
  - Shortcodes: `toBeRegisteredShortcode()`
- **Browser testing support** via Pest Browser Plugin integration
- **WP Admin locators** for browser tests (URL helpers + CSS selectors)
- **Architecture presets** for code quality (CodeAnalyzer)
- **GitHub Actions workflow templates** for CI/CD
- Full PHPStan level 9 compliance
- PSR-12 code style with Laravel Pint

### Requirements

- PHP 8.3+
- Pest v4.0+
- PHPUnit 12+

### Notes

This is the initial development release (0.x). The API may change based on feedback.
When stable, version 1.0.0 will be released.

[Unreleased]: https://github.com/alvarodelera/pest-wp-plugin/compare/v0.1.1...HEAD
[0.1.1]: https://github.com/alvarodelera/pest-wp-plugin/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/alvarodelera/pest-wp-plugin/releases/tag/v0.1.0
