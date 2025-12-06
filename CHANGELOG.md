# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-12-06

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

[Unreleased]: https://github.com/alvarodelera/pest-wp-plugin/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/alvarodelera/pest-wp-plugin/releases/tag/v1.0.0
