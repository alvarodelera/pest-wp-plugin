# PestWP Project Review

> **Review Date**: January 2026  
> **Version Analyzed**: 0.1.1  
> **Focus Area**: Browser Testing

---

## Table of Contents

1. [Current State of the Project](#1-current-state-of-the-project)
2. [Things to Improve](#2-things-to-improve)
3. [Roadmap Suggestion](#3-roadmap-suggestion)
4. [Bugs and Missing Features](#4-bugs-and-missing-features)

---

## 1. Current State of the Project

### Overview

**PestWP** is a Pest PHP testing plugin for WordPress in early development (v0.1.1). It provides zero-configuration WordPress testing with SQLite database support, eliminating the need for MySQL setup during testing.

### Key Characteristics

- **PHP 8.3+** required
- **Pest v4.0+** and **PHPUnit 12+** based
- **PHPStan Level 9** compliant
- **PSR-12** code style with Laravel Pint
- **MIT License**

### Implementation Status

| Feature | Status | Notes |
|---------|--------|-------|
| **Zero-config WordPress** | ✅ Complete | Auto-downloads WP to `.pest/wordpress/` |
| **SQLite Integration** | ✅ Complete | No MySQL needed for tests |
| **Database Isolation** | ✅ Complete | SAVEPOINT/ROLLBACK (~1.7ms/test) |
| **Factory Helpers** | ✅ Complete | `createPost()`, `createUser()`, `createTerm()`, `createAttachment()` |
| **Auth Helpers** | ✅ Complete | `loginAs()`, `logout()`, `currentUser()`, `isUserLoggedIn()` |
| **Custom Expectations** | ✅ Complete | 20+ WordPress-specific expectations |
| **Unit Tests** | ✅ Complete | Tests without WordPress loaded |
| **Integration Tests** | ✅ Complete | Tests with WordPress + SQLite |
| **Browser Tests** | ⚠️ Partial | Structure exists, all tests skipped |
| **Architecture Tests** | ❌ Missing | No Pest `arch()` support |
| **Code Quality Presets** | ✅ Complete | `WordPressPreset` + `CodeAnalyzer` |
| **Plugin Configuration** | ✅ Complete | `Config::plugins()`, `Config::theme()`, etc. |
| **GitHub Actions Templates** | ✅ Complete | CI/CD workflow templates included |

### Test Types Supported

| Type | Location | Description | Database |
|------|----------|-------------|----------|
| **Unit** | `tests/Unit/` | Pure PHP, no WordPress loaded | None |
| **Integration** | `tests/Integration/` | WordPress with SQLite isolation | SQLite (auto) |
| **Browser** | `tests/Browser/` | E2E with Pest Browser Plugin | MySQL (external) |

### Custom Expectations Available

#### Post Status
- `toBePublished()` - Post has 'publish' status
- `toBeDraft()` - Post has 'draft' status
- `toBePending()` - Post has 'pending' status
- `toBePrivate()` - Post has 'private' status
- `toBeInTrash()` - Post has 'trash' status

#### WP_Error
- `toBeWPError()` - Value is WP_Error instance
- `toHaveErrorCode($code)` - WP_Error has specific code

#### Metadata
- `toHaveMeta($key, $value)` - Post has specific meta
- `toHaveMetaKey($key)` - Post has meta key
- `toHaveUserMeta($key, $value)` - User has specific meta

#### Hooks
- `toHaveAction($callback, $priority)` - Action is registered
- `toHaveFilter($callback, $priority)` - Filter is registered

#### Users
- `toHaveCapability($capability)` - User has capability
- `toHaveRole($role)` - User has role
- `can($capability)` - Alias for toHaveCapability

#### Post Types & Taxonomies
- `toBeRegisteredPostType()` - Post type exists
- `toSupportFeature($feature)` - Post type supports feature
- `toBeRegisteredTaxonomy()` - Taxonomy exists
- `toHaveTerm($term, $taxonomy)` - Post has term

#### Options & Transients
- `toHaveOption($value)` - Option exists (optionally with value)
- `toHaveTransient($value)` - Transient exists

#### Shortcodes
- `toBeRegisteredShortcode()` - Shortcode is registered

### Project Structure

```
pest-wp-plugin/
├── src/
│   ├── Commands/           # CLI commands (SetupBrowserCommand)
│   ├── Concerns/           # Traits (InteractsWithDatabase)
│   ├── Database/           # DatabaseManager, TransactionManager
│   ├── Expectations/       # 9 expectation files by category
│   ├── Functions/          # Helper functions (5 files)
│   ├── Installer/          # WordPress/SQLite installers
│   ├── Presets/            # Code quality (WordPressPreset, CodeAnalyzer)
│   ├── bootstrap.php       # WordPress bootstrap
│   ├── Config.php          # Configuration API
│   ├── Expectations.php    # Expectation registration
│   ├── functions.php       # Main entry point
│   ├── Plugin.php          # Pest plugin implementation
│   └── TestCase.php        # Base test case
├── tests/
│   ├── Browser/            # E2E browser tests
│   ├── Integration/        # WordPress integration tests
│   ├── Unit/               # Pure PHP unit tests
│   ├── bootstrap.php       # Test bootstrap
│   └── Pest.php            # Pest configuration
├── docs/
│   ├── BROWSER_TESTING.md  # Browser testing guide (Spanish)
│   └── TESTING_PLUGINS.md  # Plugin testing guide
└── .pest/                  # Auto-created runtime directory
    ├── wordpress/          # WordPress installation
    └── database/           # SQLite database
```

---

## 2. Things to Improve

### Browser Testing (Priority Focus)

| Improvement | Description | Impact |
|-------------|-------------|--------|
| **Authentication State Persistence** | Implement zero-login strategy mentioned in docs. Save auth state to `.pest/state/admin.json` and reuse across tests | High |
| **Browser Test Execution** | All 4 browser tests are skipped. Need integration with `wp-env` or Docker for CI/CD | High |
| **Automated WordPress Server** | Provide a command to spin up a temporary WordPress instance for browser tests (similar to SQLite auto-config) | High |
| **WooCommerce Locators** | Add specialized locators for WooCommerce admin (products, orders, customers, settings) | Medium |
| **Visual Regression Testing** | Add screenshot comparison utilities for detecting UI changes | Medium |
| **Parallel Browser Execution** | Support running browser tests in parallel with isolated contexts | Medium |
| **Translate Documentation** | `docs/BROWSER_TESTING.md` is in Spanish; translate to English | Medium |
| **More Gutenberg Selectors** | Add selectors for more block types (image, gallery, columns, etc.) and editor panels | Low |
| **Classic Editor Support** | Full support for sites using Classic Editor plugin | Low |
| **Mobile Viewport Testing** | Add helpers for testing responsive behavior | Low |

### Architecture Tests

| Improvement | Description | Impact |
|-------------|-------------|--------|
| **Implement Pest `arch()` Support** | Add true architecture testing with Pest's `arch()` function for enforcing code structure | High |
| **WordPress-Specific Arch Rules** | Presets for common WP architecture patterns (hooks in correct files, namespacing rules) | Medium |
| **Dependency Rules** | Enforce that certain classes only depend on specific WordPress APIs | Medium |
| **Layer Separation** | Enforce separation between data, business logic, and presentation layers | Medium |

### Developer Experience

| Improvement | Description | Impact |
|-------------|-------------|--------|
| **Mock Helpers** | Provide utilities for mocking WordPress functions without external libraries | High |
| **REST API Testing Helpers** | Add helpers for testing REST endpoints (`GET`, `POST`, `PUT`, `DELETE`) | High |
| **AJAX Request Testing** | Utilities for testing admin-ajax.php handlers | Medium |
| **WP-CLI Integration** | Helpers for testing WP-CLI commands | Medium |
| **Snapshot Testing** | JSON/HTML snapshot assertions for complex outputs | Medium |
| **Better Error Messages** | More descriptive failures with WordPress context | Low |
| **Debug Mode Improvements** | Enhanced `PEST_WP_DEBUG=1` output with SQL queries, hooks fired | Low |
| **IDE Stubs Generation** | Auto-generate IDE helper files for better autocomplete | Low |

### Additional Factory Helpers

| Helper | Description |
|--------|-------------|
| `createComment($postId, $args)` | Create WP_Comment objects with defaults |
| `createPage($args)` | Shorthand for `createPost(['post_type' => 'page'])` |
| `createMenu($name, $items)` | Create navigation menus and items |
| `createWidget($widget, $args)` | Create widget instances |
| `createOption($key, $value)` | Create options with auto-cleanup |
| `createTransient($key, $value, $expiration)` | Create transients with auto-cleanup |
| `createCronEvent($hook, $args, $timestamp)` | Schedule cron events for testing |
| `createBlock($blockName, $attrs, $content)` | Create Gutenberg block instances |

### Additional Expectations

| Expectation | Description |
|-------------|-------------|
| `toBeScheduled($hook)` | Check if cron event is scheduled |
| `toHaveEnqueuedScript($handle)` | Check if script is enqueued |
| `toHaveEnqueuedStyle($handle)` | Check if style is enqueued |
| `toBeBlockType()` | Check if Gutenberg block is registered |
| `toHaveRESTRoute($route)` | Check if REST route exists |
| `toHaveAdminMenu($slug)` | Check if admin menu item exists |
| `toHaveWidget($id)` | Check if widget is registered |
| `toHaveSidebar($id)` | Check if sidebar is registered |
| `toHaveImageSize($name)` | Check if image size is registered |
| `toHaveRewriteRule($pattern)` | Check if rewrite rule exists |

### Browser Testing Locators to Add

| Category | Locators |
|----------|----------|
| **WooCommerce** | `productTitleSelector()`, `productPriceSelector()`, `addToCartSelector()`, `checkoutButtonSelector()` |
| **Media Library** | `mediaUploadSelector()`, `mediaGridItemSelector()`, `mediaDetailsSelector()` |
| **Customizer** | `customizerPanelSelector()`, `customizerControlSelector()`, `customizerPublishSelector()` |
| **Widgets** | `widgetAreaSelector()`, `widgetFormSelector()`, `widgetSaveSelector()` |
| **Comments** | `commentFormSelector()`, `commentListSelector()`, `replyLinkSelector()` |

---

## 3. Roadmap Suggestion

### Phase 1: Browser Testing Foundation (v0.2.0)
**Timeline: 4-6 weeks**

**Goals:**
- Make browser tests actually runnable out of the box
- Implement authentication state persistence
- Provide automated WordPress server for browser tests

**Deliverables:**
- [ ] `pest-wp-serve` command to start temporary WordPress server with PHP built-in server
- [ ] Authentication state saving/loading (zero-login strategy)
- [ ] `storageState()` helper for browser context
- [ ] Docker Compose template for CI/CD browser tests
- [ ] wp-env integration guide
- [ ] Enable and fix all skipped browser tests
- [ ] Translate `docs/BROWSER_TESTING.md` to English
- [ ] Browser test GitHub Actions workflow

**Success Criteria:**
- Browser tests run successfully in CI
- New users can run browser tests within 5 minutes of setup

---

### Phase 2: Architecture Testing (v0.3.0)
**Timeline: 3-4 weeks**

**Goals:**
- Implement Pest `arch()` function support
- Create WordPress-specific architecture presets

**Deliverables:**
- [ ] `arch()` function integration with WordPress context
- [ ] Preset: `wordpress()->noDebugFunctions()` - No dd, dump, var_dump
- [ ] Preset: `wordpress()->properHookRegistration()` - Hooks registered in correct locations
- [ ] Preset: `wordpress()->namespaceConventions()` - PSR-4 compliant namespacing
- [ ] Preset: `wordpress()->noDirect FileAccess()` - No direct file includes
- [ ] Preset: `wordpress()->securityBestPractices()` - No eval, exec, etc.
- [ ] Documentation for architecture testing

**Success Criteria:**
- Users can write `arch()->preset()->wordpress()` in their tests
- Custom arch rules can be defined for WordPress patterns

---

### Phase 3: REST API & AJAX Testing (v0.4.0)
**Timeline: 3-4 weeks**

**Goals:**
- Complete REST API testing support
- AJAX testing utilities

**Deliverables:**
- [ ] `rest()->get('/wp/v2/posts')` fluent helper
- [ ] `rest()->post('/wp/v2/posts', $data)` with authentication
- [ ] `rest()->as($user)->get(...)` for authenticated requests
- [ ] `expectEndpoint('/my-plugin/v1/items')->toExist()` assertion
- [ ] `ajax('my_action', $data)` helper for admin-ajax requests
- [ ] Nonce generation and validation utilities
- [ ] JSON schema validation for REST responses
- [ ] Documentation and examples

**Success Criteria:**
- Users can test REST endpoints without manual cURL calls
- AJAX handlers can be tested in isolation

---

### Phase 4: Enhanced Mocking & Fixtures (v0.5.0)
**Timeline: 4-5 weeks**

**Goals:**
- Provide mocking utilities for WordPress functions
- Advanced fixture management

**Deliverables:**
- [ ] `mockFunction('wp_mail')->andReturn(true)` helper
- [ ] `mockHook('init')->capture($callback)` for hook interception
- [ ] `mockHTTP()->whenUrl('api.example.com')->return($response)` for HTTP mocking
- [ ] `mockTime()->freeze('2024-01-01')` for time-dependent tests
- [ ] Database seeding/fixtures system with YAML/JSON support
- [ ] Snapshot testing with `expect($html)->toMatchSnapshot()`
- [ ] Fixture auto-cleanup after tests
- [ ] Documentation and examples

**Success Criteria:**
- Users can mock any WordPress function without Mockery/Brain Monkey
- Fixtures can be defined declaratively

---

### Phase 5: Advanced Browser Testing (v0.6.0)
**Timeline: 4-5 weeks**

**Goals:**
- Visual regression testing
- Parallel execution
- WooCommerce support

**Deliverables:**
- [ ] `screenshot()->compare('baseline.png')` visual regression
- [ ] Parallel browser test execution with `--parallel`
- [ ] WooCommerce admin locators (50+ selectors)
- [ ] WooCommerce checkout flow helpers
- [ ] Extended Gutenberg locators for all core blocks
- [ ] Video recording on test failure
- [ ] Accessibility testing helpers (`assertAccessible()`)
- [ ] Mobile/tablet viewport presets
- [ ] Documentation for visual testing

**Success Criteria:**
- Visual regressions are automatically detected
- WooCommerce plugins can be fully tested

---

### Phase 6: Stable Release (v1.0.0)
**Timeline: 2-3 weeks**

**Goals:**
- API stabilization
- Complete documentation
- Migration guides

**Deliverables:**
- [ ] API freeze and backwards compatibility commitment
- [ ] Remove all deprecated methods
- [ ] Complete English documentation for all features
- [ ] Video tutorials for common workflows
- [ ] Migration guide from wp-browser/Codeception
- [ ] Migration guide from WP_UnitTestCase
- [ ] Performance benchmarks vs other solutions
- [ ] Security audit
- [ ] Published to Packagist with stable tag

**Success Criteria:**
- No breaking changes after v1.0.0
- Documentation covers all features
- Smooth migration path from existing solutions

---

### Timeline Overview

```
2026 Q1: Phase 1 (Browser Testing) + Phase 2 (Architecture)
2026 Q2: Phase 3 (REST/AJAX) + Phase 4 (Mocking)
2026 Q3: Phase 5 (Advanced Browser) + Phase 6 (Stable Release)
```

**Estimated v1.0.0 Release: Q3 2026**

---

## 4. Bugs and Missing Features

### Bugs Found

| Issue | Location | Severity | Description |
|-------|----------|----------|-------------|
| **Deprecated Methods Not Removed** | `src/Database/TransactionManager.php:115-127` | Low | `createSavepoint()` and `rollbackToSavepoint()` are deprecated but still present |
| **Silent Exception Handling** | `src/Database/DatabaseManager.php:163-165` | Medium | Exception caught and returns false without logging |
| **Silent ReflectionException** | `src/Database/DatabaseManager.php:192-194` | Low | ReflectionException caught and ignored with comment "// Ignore" |
| **Windows File Handle Delay** | `src/Database/DatabaseManager.php:142-143` | Low | Hardcoded 10ms `usleep()` workaround for Windows file handles |
| **All Browser Tests Skipped** | `tests/Browser/DashboardTest.php` | High | 4 tests always skipped, never run in CI |
| **Windows Rename Fallback** | `src/Installer/WordPressInstaller.php:210-215` | Low | Uses error suppression `@rename()` with fallback to copy |

### Missing Features for Production-Ready Library

| Feature | Priority | Notes |
|---------|----------|-------|
| **Pest `arch()` Support** | Critical | Mentioned in changelog but not actually implemented |
| **Working Browser Tests** | Critical | Tests exist but are always skipped |
| **REST API Helpers** | High | Modern WordPress heavily uses REST API |
| **Mock Utilities** | High | Essential for unit testing WP-dependent code |
| **AJAX Testing** | Medium | Common pattern in WordPress plugins |
| **Comment Factory** | Medium | Basic WordPress entity missing from factories |
| **Menu/Widget Factories** | Medium | Common WordPress entities not supported |
| **Snapshot Testing** | Medium | Popular testing pattern not available |
| **Visual Regression** | Medium | Important for theme/UI testing |
| **WP-CLI Testing** | Low | Useful for CLI-heavy plugins |
| **Multisite Support** | Low | No documentation or helpers for multisite |

### Technical Debt

| Item | Location | Recommended Action |
|------|----------|-------------------|
| Remove deprecated methods | `TransactionManager.php` | Remove in v1.0.0 with migration notice |
| Add logging for caught exceptions | `DatabaseManager.php` | Add optional debug logging via `PEST_WP_DEBUG` |
| Review Windows compatibility | Multiple installers | Add Windows to CI matrix |
| Browser test CI pipeline | `.github/workflows/` | Add browser test job with Docker WordPress |
| Reduce error suppression | Installers | Replace `@` operators with proper try/catch |

### Documentation Gaps

| Gap | Description | Priority |
|-----|-------------|----------|
| Architecture testing docs | No documentation on `arch()` usage | High (once implemented) |
| REST API testing docs | No examples for testing REST endpoints | High |
| English browser docs | `BROWSER_TESTING.md` is in Spanish only | Medium |
| WooCommerce testing guide | No guidance for WooCommerce plugin testing | Medium |
| Migration guide | No guide for migrating from wp-browser | Medium |
| Multisite testing guide | No documentation for multisite scenarios | Low |
| Performance tuning guide | No guide for optimizing test speed | Low |

### Code Quality Observations

**Strengths:**
- PHPStan Level 9 compliance shows strong typing
- Consistent code style with Pint
- Good separation of concerns in class structure
- Comprehensive error handling with RuntimeException
- Well-documented with inline comments

**Areas for Improvement:**
- Some methods have multiple responsibilities (could be split)
- Hardcoded test credentials in bootstrap (acceptable for testing but documented)
- Some magic strings could be constants
- Missing interface definitions for key classes (DatabaseManager, Installer)

---

## Summary

PestWP is a well-architected early-stage project with solid foundations for unit and integration testing. The SQLite integration and database isolation work exceptionally well, providing fast (~1.7ms) and reliable test isolation.

### Main Gaps

1. **Browser testing is incomplete** - tests exist but are always skipped, requiring external WordPress setup
2. **Architecture testing is missing** - `arch()` function is not implemented despite being mentioned
3. **No REST/AJAX helpers** - essential for modern WordPress development
4. **No mocking utilities** - needed for proper unit testing of WordPress-dependent code

### Strengths to Build On

1. **Zero-config philosophy** - extend this to browser testing
2. **Type safety** - maintain PHPStan Level 9 as features are added
3. **Clean API design** - factory helpers and expectations are intuitive
4. **Good documentation** - continue this standard for new features

### Recommended Priority

1. **Immediate (v0.2.0)**: Fix browser testing - this is the biggest gap
2. **Short-term (v0.3.0)**: Implement `arch()` support - advertised but missing
3. **Medium-term (v0.4.0-0.5.0)**: REST API and mocking - essential for real-world use
4. **Long-term (v1.0.0)**: Stabilize API and complete documentation

The suggested roadmap projects a stable v1.0.0 release in approximately 6-8 months with consistent development effort.

---

*This review was generated based on codebase analysis of PestWP v0.1.1*
