# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-01-09

### Added

- **Stable Release**: First stable version with API stability commitment
- **Migration Guides**: Complete guides for migrating from wp-browser/Codeception and WP_UnitTestCase
- **Performance Benchmarks**: Documentation comparing PestWP to other testing solutions

### Changed

- **API Stability**: All public APIs are now stable and follow semantic versioning
- **Documentation**: All documentation is now in English and complete

### Removed

- **Deprecated methods**: Removed `TransactionManager::createSavepoint()` and `TransactionManager::rollbackToSavepoint()` - use `beginTransaction()` and `rollback()` instead

### Upgrade Guide

If you were using the deprecated methods, update your code:
```php
// Before (deprecated)
TransactionManager::createSavepoint();
TransactionManager::rollbackToSavepoint();

// After (v1.0.0)
TransactionManager::beginTransaction();
TransactionManager::rollback();
```

---

## [0.6.0] - 2026-01-09

### Added

- **Visual Regression Testing** (`ScreenshotManager`):
  - Baseline image management (create, compare, update, delete)
  - GD-based image comparison with configurable threshold
  - Diff image generation highlighting pixel differences
  - Update mode via `PEST_UPDATE_SCREENSHOTS=1` environment variable

- **WooCommerce Locators** (~90 helper functions):
  - Admin URLs: Products, Orders, Coupons, Settings, Analytics
  - Product admin selectors: Title, Price, SKU, Stock, Tabs, Categories
  - Order admin selectors: Status, Items, Notes, Billing/Shipping fields
  - Storefront selectors: Shop, Product grid, Add to cart, Cart
  - Checkout selectors: Form, Billing fields, Shipping, Payment methods
  - My Account selectors: Navigation, Orders, Downloads, Addresses
  - Notice selectors: Success, Error, Info, Added to cart

- **Extended Gutenberg Locators** (~70 helper functions):
  - Text blocks: Paragraph, Heading, List, Quote, Code, Preformatted, Verse
  - Media blocks: Image, Gallery, Audio, Video, Cover, Media & Text, File
  - Layout blocks: Buttons, Columns, Group, Row, Stack, Separator, Spacer
  - Widget blocks: Shortcode, Archives, Calendar, Categories, HTML, Search
  - Theme blocks: Site Title, Tagline, Logo, Navigation, Query, Comments
  - Table blocks with header/body/footer selectors
  - Embed blocks with provider filtering

- **Viewport Presets** (~35 helper functions):
  - Mobile: iPhone SE/12/14, Galaxy S21, Pixel 7
  - Tablet: iPad Mini/Air/Pro, Galaxy Tab S7
  - Desktop: HD, Full HD, QHD, 4K, MacBook Air/Pro
  - Collections: `mobileViewports()`, `tabletViewports()`, `allViewports()`
  - WordPress-specific: `viewportWPAdmin()`, `viewportGutenberg()`

- **Accessibility Testing** (~25 helper functions):
  - Image alt text checks
  - Form input label checks
  - Heading hierarchy validation
  - ARIA landmarks check
  - WCAG Level A, AA, AAA compliance checks
  - Impact filtering and formatted reports

- **Screenshot Helper Functions**:
  - Video recording config: `videoRecordingConfig()`, `videoOnFailure()`
  - Trace recording config: `traceConfig()`, `traceOnFailure()`

- **Documentation**: `docs/ADVANCED_BROWSER.md` with complete examples

---

## [0.5.0] - 2026-01-09

### Added

- **WordPress Function Mocking** (`mockFunction()`):
  - Mock any WordPress function with fluent API
  - Return values, throw exceptions, run callbacks
  - Call count expectations and argument capture
  - Automatic cleanup after each test

- **Hook Mocking** (`mockHook()`):
  - Intercept action and filter callbacks
  - Capture callback arguments
  - Replace hook behavior for testing

- **HTTP Mocking** (`mockHTTP()`):
  - Mock WordPress HTTP API responses
  - URL pattern matching with wildcards
  - Response queuing for multiple requests
  - Network error simulation

- **Time Mocking** (`mockTime()`):
  - Freeze time at specific moments
  - Control `time()`, `current_time()`, `wp_date()`
  - Time travel with `travel()` and `travelTo()`

- **Snapshot Testing**:
  - `toMatchSnapshot()` expectation
  - JSON and HTML snapshot support
  - Update mode via `PEST_UPDATE_SNAPSHOTS=1`
  - Automatic snapshot file management

- **Fixture Manager**:
  - YAML/JSON fixture loading
  - Factory integration
  - Reference resolution between fixtures
  - Automatic cleanup

- **Documentation**: `docs/MOCKING_FIXTURES.md` with examples

---

## [0.4.0] - 2026-01-09

### Added

- **REST API Testing** (`rest()` helper):
  - Fluent API: `rest()->get()`, `->post()`, `->put()`, `->delete()`
  - Authentication: `rest()->as($user)->get()`
  - JSON body and headers support
  - Response assertions: `->assertStatus()`, `->assertJson()`

- **AJAX Testing** (`ajax()` helper):
  - Test admin-ajax.php handlers
  - Nonce generation and validation
  - JSON response parsing
  - Action hooks testing

- **New Expectations**:
  - `toBeRESTEndpoint()` - Check if REST route exists
  - `toHaveRESTRoute()` - Verify route registration
  - `toReturnValidJSON()` - JSON response validation
  - JSON Schema validation support

- **Documentation**: `docs/REST_AJAX_TESTING.md` with examples

---

## [0.3.0] - 2026-01-09

### Added

- **Architecture Testing** with Pest `arch()` integration:
  - WordPress-specific presets
  - `wordpress()->noDebugFunctions()` - No dd, dump, var_dump
  - `wordpress()->properHookRegistration()` - Hooks in correct files
  - `wordpress()->namespaceConventions()` - PSR-4 compliance
  - `wordpress()->noDirectFileAccess()` - No direct includes
  - `wordpress()->securityBestPractices()` - No eval, exec, etc.

- **Custom Architecture Rules**:
  - Fluent API for defining rules
  - Class, function, and trait targeting
  - Namespace and dependency checks

- **Documentation**: `docs/ARCHITECTURE_TESTING.md` with examples

---

## [0.2.0] - 2026-01-09

### Added

- **Browser Testing Foundation**:
  - `pest-wp-serve` command for temporary WordPress server
  - Authentication state persistence (zero-login strategy)
  - `storageState()` helper for browser context
  - Docker Compose template for CI/CD

- **WP Admin Locators**:
  - URL helpers: `adminUrl()`, `loginUrl()`, `newPostUrl()`
  - Gutenberg selectors: `postTitleSelector()`, `publishButtonSelector()`
  - Admin UI selectors: `menuSelector()`, `noticeSelector()`

- **Documentation**: `docs/BROWSER_TESTING.md` translated to English

### Fixed

- All browser tests now execute properly
- Browser test GitHub Actions workflow

---

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

---

## [0.1.0] - 2025-12-06

### Added

- **Zero-config WordPress testing** with automatic SQLite setup
- **Database isolation** using SAVEPOINT/ROLLBACK (~1.7ms per test)
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

---

[1.0.0]: https://github.com/alvarodelera/pest-wp-plugin/compare/v0.6.0...v1.0.0
[0.6.0]: https://github.com/alvarodelera/pest-wp-plugin/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/alvarodelera/pest-wp-plugin/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/alvarodelera/pest-wp-plugin/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/alvarodelera/pest-wp-plugin/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/alvarodelera/pest-wp-plugin/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/alvarodelera/pest-wp-plugin/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/alvarodelera/pest-wp-plugin/releases/tag/v0.1.0
