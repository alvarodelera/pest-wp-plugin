# Advanced Browser Testing

This document covers advanced browser testing features in PestWP, including visual regression testing, WooCommerce support, extended Gutenberg locators, viewport presets, and accessibility testing.

## Table of Contents

- [Visual Regression Testing](#visual-regression-testing)
- [WooCommerce Testing](#woocommerce-testing)
- [Extended Gutenberg Locators](#extended-gutenberg-locators)
- [Viewport Presets](#viewport-presets)
- [Accessibility Testing](#accessibility-testing)
- [Video & Trace Recording](#video--trace-recording)

---

## Visual Regression Testing

PestWP provides a `ScreenshotManager` for capturing and comparing screenshots to detect visual regressions.

### Basic Usage

```php
use function PestWP\Functions\screenshots;
use function PestWP\Functions\captureScreenshot;
use function PestWP\Functions\compareScreenshot;

test('homepage looks correct', function () {
    // Navigate to page
    $page->goto('/');
    
    // Get screenshot path
    $path = captureScreenshot('homepage');
    
    // Take screenshot with Playwright
    $page->screenshot(['path' => $path]);
    
    // Compare against baseline
    $result = compareScreenshot($path);
    
    expect($result['match'])->toBeTrue();
});
```

### Screenshot Manager API

```php
use function PestWP\Functions\screenshots;

// Get the singleton instance
$manager = screenshots();

// Capture a screenshot (returns path)
$path = $manager->capture('my-screenshot');

// Compare against baseline
$result = $manager->compare($path);
// Returns: ['match' => bool, 'difference' => float, 'baseline' => string, 'diff' => string|null, 'message' => string]

// Assert match (throws on mismatch)
$manager->assertMatch($path);

// Create/update baseline
$manager->createBaseline($path);

// Check if baseline exists
$manager->hasBaseline('my-screenshot');

// Set comparison threshold (0-1)
$manager->setThreshold(0.05); // 5% difference allowed
```

### Updating Baselines

Set the `PEST_UPDATE_SCREENSHOTS=1` environment variable to update baselines:

```bash
PEST_UPDATE_SCREENSHOTS=1 vendor/bin/pest --filter="visual"
```

### Helper Functions

```php
use function PestWP\Functions\captureScreenshot;
use function PestWP\Functions\compareScreenshot;
use function PestWP\Functions\assertScreenshotMatches;
use function PestWP\Functions\createBaseline;
use function PestWP\Functions\hasBaseline;
use function PestWP\Functions\baselinePath;
use function PestWP\Functions\screenshotPath;
use function PestWP\Functions\setScreenshotThreshold;
use function PestWP\Functions\enableScreenshotUpdate;
```

---

## WooCommerce Testing

PestWP includes 90+ locators and helpers specifically for WooCommerce testing.

### Admin URLs

```php
use function PestWP\Functions\wooProductsUrl;
use function PestWP\Functions\wooOrdersUrl;
use function PestWP\Functions\wooSettingsUrl;
use function PestWP\Functions\wooAnalyticsUrl;
use function PestWP\Functions\wooCouponsUrl;

// Navigate to WooCommerce pages
$page->goto(wooProductsUrl());
$page->goto(wooOrdersUrl());
$page->goto(wooSettingsUrl('products'));
$page->goto(wooAnalyticsUrl('revenue'));
```

### Product Selectors (Admin)

```php
use function PestWP\Functions\wooProductTitleSelector;
use function PestWP\Functions\wooProductPriceSelector;
use function PestWP\Functions\wooProductSalePriceSelector;
use function PestWP\Functions\wooProductSkuSelector;
use function PestWP\Functions\wooProductStockSelector;
use function PestWP\Functions\wooProductTypeSelector;
use function PestWP\Functions\wooProductTabSelector;

// Fill product details
$page->fill(wooProductTitleSelector(), 'My Product');
$page->fill(wooProductPriceSelector(), '29.99');
$page->click(wooProductTabSelector('inventory'));
$page->fill(wooProductSkuSelector(), 'SKU-001');
```

### Order Selectors (Admin)

```php
use function PestWP\Functions\wooOrderStatusSelector;
use function PestWP\Functions\wooOrderItemsSelector;
use function PestWP\Functions\wooOrderNotesSelector;
use function PestWP\Functions\wooOrderBillingFieldSelector;

// Manage orders
$page->selectOption(wooOrderStatusSelector(), 'wc-completed');
$page->fill(wooOrderBillingFieldSelector('first_name'), 'John');
```

### Storefront Selectors

```php
use function PestWP\Functions\wooShopProductSelector;
use function PestWP\Functions\wooShopAddToCartSelector;
use function PestWP\Functions\wooCartTotalSelector;
use function PestWP\Functions\wooMiniCartSelector;

// Shop page interactions
$page->click(wooShopAddToCartSelector());
expect($page->locator(wooMiniCartSelector()))->toBeVisible();
```

### Checkout Selectors

```php
use function PestWP\Functions\wooCheckoutFormSelector;
use function PestWP\Functions\wooBillingFieldSelector;
use function PestWP\Functions\wooShippingFieldSelector;
use function PestWP\Functions\wooPaymentMethodSelector;
use function PestWP\Functions\wooPlaceOrderSelector;

test('complete checkout flow', function () {
    // Fill billing details
    $page->fill(wooBillingFieldSelector('first_name'), 'John');
    $page->fill(wooBillingFieldSelector('last_name'), 'Doe');
    $page->fill(wooBillingFieldSelector('email'), 'john@example.com');
    $page->fill(wooBillingFieldSelector('phone'), '555-1234');
    $page->fill(wooBillingFieldSelector('address_1'), '123 Main St');
    $page->fill(wooBillingFieldSelector('city'), 'New York');
    $page->fill(wooBillingFieldSelector('postcode'), '10001');
    
    // Select payment method
    $page->click(wooPaymentMethodSelector('cod'));
    
    // Place order
    $page->click(wooPlaceOrderSelector());
});
```

### My Account Selectors

```php
use function PestWP\Functions\wooAccountNavSelector;
use function PestWP\Functions\wooAccountNavLinkSelector;
use function PestWP\Functions\wooLoginFormSelector;
use function PestWP\Functions\wooRegisterFormSelector;
```

### Notice Selectors

```php
use function PestWP\Functions\wooNoticeSelector;
use function PestWP\Functions\wooSuccessNoticeSelector;
use function PestWP\Functions\wooErrorNoticeSelector;
use function PestWP\Functions\wooAddedToCartNoticeSelector;

// Check for notices
expect($page->locator(wooSuccessNoticeSelector()))->toBeVisible();
```

---

## Extended Gutenberg Locators

Comprehensive selectors for all WordPress core blocks.

### Text Blocks

```php
use function PestWP\Functions\paragraphBlockSelector;
use function PestWP\Functions\headingBlockSelector;
use function PestWP\Functions\listBlockSelector;
use function PestWP\Functions\quoteBlockSelector;
use function PestWP\Functions\codeBlockSelector;

// Target specific block types
$page->click(paragraphBlockSelector());
$page->click(headingBlockSelector(2)); // h2 specifically
$page->click(listBlockSelector('ordered')); // ordered list
```

### Media Blocks

```php
use function PestWP\Functions\imageBlockSelector;
use function PestWP\Functions\galleryBlockSelector;
use function PestWP\Functions\videoBlockSelector;
use function PestWP\Functions\coverBlockSelector;
use function PestWP\Functions\mediaTextBlockSelector;
```

### Layout Blocks

```php
use function PestWP\Functions\columnsBlockSelector;
use function PestWP\Functions\columnBlockSelector;
use function PestWP\Functions\groupBlockSelector;
use function PestWP\Functions\buttonsBlockSelector;
use function PestWP\Functions\buttonBlockSelector;
use function PestWP\Functions\separatorBlockSelector;
use function PestWP\Functions\spacerBlockSelector;

// Target specific columns
$page->click(columnBlockSelector(0)); // First column
$page->click(columnBlockSelector(1)); // Second column
```

### Widget Blocks

```php
use function PestWP\Functions\searchBlockSelector;
use function PestWP\Functions\latestPostsBlockSelector;
use function PestWP\Functions\categoriesBlockSelector;
use function PestWP\Functions\socialLinksBlockSelector;
use function PestWP\Functions\tableBlockSelector;
```

### Embed Blocks

```php
use function PestWP\Functions\embedBlockSelector;
use function PestWP\Functions\youtubeBlockSelector;
use function PestWP\Functions\vimeoBlockSelector;
use function PestWP\Functions\twitterBlockSelector;

// Generic embed with provider filter
$page->click(embedBlockSelector('youtube'));
```

### Theme/Site Editor Blocks

```php
use function PestWP\Functions\siteTitleBlockSelector;
use function PestWP\Functions\siteLogoBlockSelector;
use function PestWP\Functions\navigationBlockSelector;
use function PestWP\Functions\queryBlockSelector;
use function PestWP\Functions\postTitleBlockSelector;
use function PestWP\Functions\postContentBlockSelector;
use function PestWP\Functions\templatePartBlockSelector;

// Target template parts by slug
$page->click(templatePartBlockSelector('header'));
$page->click(templatePartBlockSelector('footer'));
```

### Block Editor UI

```php
use function PestWP\Functions\blockToolbarSelector;
use function PestWP\Functions\blockSettingsSidebarSelector;
use function PestWP\Functions\blockMoverSelector;
use function PestWP\Functions\blockOptionsMenuSelector;
use function PestWP\Functions\boldButtonSelector;
use function PestWP\Functions\italicButtonSelector;
use function PestWP\Functions\linkButtonSelector;
```

---

## Viewport Presets

Pre-configured viewport sizes for responsive testing.

### Basic Viewports

```php
use function PestWP\Functions\viewportMobile;
use function PestWP\Functions\viewportTablet;
use function PestWP\Functions\viewportDesktop;
use function PestWP\Functions\viewportDesktopLarge;

// Returns: ['width' => 375, 'height' => 667, 'isMobile' => true, 'hasTouch' => true]
$viewport = viewportMobile();

// Use with Playwright
$page->setViewportSize($viewport);
```

### Device-Specific Viewports

```php
// Mobile Devices
use function PestWP\Functions\viewportIPhoneSE;
use function PestWP\Functions\viewportIPhone12;
use function PestWP\Functions\viewportIPhone14ProMax;
use function PestWP\Functions\viewportGalaxyS21;
use function PestWP\Functions\viewportPixel7;

// Tablets
use function PestWP\Functions\viewportIPadMini;
use function PestWP\Functions\viewportIPadAir;
use function PestWP\Functions\viewportIPadPro11;
use function PestWP\Functions\viewportIPadPro12;

// Laptops/Desktops
use function PestWP\Functions\viewportMacBookAir;
use function PestWP\Functions\viewportMacBookPro14;
use function PestWP\Functions\viewport4K;
```

### Viewport Collections

```php
use function PestWP\Functions\mobileViewports;
use function PestWP\Functions\tabletViewports;
use function PestWP\Functions\desktopViewports;
use function PestWP\Functions\allViewports;
use function PestWP\Functions\responsiveViewports;

// Test across multiple viewports
foreach (responsiveViewports() as $name => $viewport) {
    test("homepage renders correctly on {$name}", function () use ($viewport) {
        $page->setViewportSize($viewport);
        $page->goto('/');
        // assertions...
    });
}
```

### WordPress-Specific Viewports

```php
use function PestWP\Functions\viewportWPAdmin;         // 1280x800
use function PestWP\Functions\viewportWPAdminCollapsed; // 960x800
use function PestWP\Functions\viewportWPAdminMobile;   // 782x667
use function PestWP\Functions\viewportGutenberg;       // 1440x900
use function PestWP\Functions\viewportSiteEditor;      // 1600x900
```

### Custom Viewports

```php
use function PestWP\Functions\createViewport;
use function PestWP\Functions\getViewport;

// Create custom viewport
$viewport = createViewport(
    width: 800,
    height: 600,
    isMobile: true,
    hasTouch: true,
    deviceScaleFactor: 2.0
);

// Get preset by name
$iphone = getViewport('iphone-12');
```

---

## Accessibility Testing

Built-in accessibility checking based on WCAG guidelines.

### Basic Usage

```php
use function PestWP\Functions\getAccessibilityViolations;
use function PestWP\Functions\isAccessible;

test('homepage is accessible', function () {
    $page->goto('/');
    $html = $page->content();
    
    expect(isAccessible($html))->toBeTrue();
});
```

### Detailed Violations

```php
use function PestWP\Functions\getAccessibilityViolations;
use function PestWP\Functions\formatAccessibilityReport;

$html = $page->content();
$violations = getAccessibilityViolations($html);

if (!empty($violations)) {
    echo formatAccessibilityReport($violations);
}
```

### Filter by Impact Level

```php
use function PestWP\Functions\getAccessibilityViolationsByImpact;

// Only get serious and critical issues
$violations = getAccessibilityViolationsByImpact($html, 'serious');

// Impact levels: 'minor', 'moderate', 'serious', 'critical'
```

### Specific Checks

```php
use function PestWP\Functions\checkImagesWithoutAlt;
use function PestWP\Functions\checkInputsWithoutLabels;
use function PestWP\Functions\checkDocumentLanguage;
use function PestWP\Functions\checkPageTitle;
use function PestWP\Functions\checkHeadingHierarchy;
use function PestWP\Functions\checkLinksWithoutText;
use function PestWP\Functions\checkButtonsWithoutText;
use function PestWP\Functions\checkAriaLandmarks;
use function PestWP\Functions\checkTablesWithoutHeaders;

// Run specific checks
$imageViolations = checkImagesWithoutAlt($html);
$formViolations = checkInputsWithoutLabels($html);
```

### WCAG Level Compliance

```php
use function PestWP\Functions\checkWcagLevelA;
use function PestWP\Functions\checkWcagLevelAA;
use function PestWP\Functions\checkWcagLevelAAA;

// Check against specific WCAG levels
$levelAViolations = checkWcagLevelA($html);
$levelAAViolations = checkWcagLevelAA($html);
```

---

## Video & Trace Recording

Configuration helpers for Playwright video and trace recording.

### Video Recording

```php
use function PestWP\Functions\videoRecordingConfig;
use function PestWP\Functions\videoOnFailure;
use function PestWP\Functions\videoAlways;
use function PestWP\Functions\videoOff;

// Record on failure only (recommended)
$config = videoOnFailure('/path/to/videos');

// Always record
$config = videoAlways('/path/to/videos');

// Disable recording
$config = videoOff();

// Custom configuration
$config = videoRecordingConfig(
    mode: 'retain-on-failure',
    dir: '/path/to/videos',
    size: ['width' => 1280, 'height' => 720]
);
```

### Trace Recording

```php
use function PestWP\Functions\traceConfig;
use function PestWP\Functions\traceOnFailure;
use function PestWP\Functions\traceFull;

// Record trace on failure
$config = traceOnFailure();
// Returns: ['mode' => 'retain-on-failure', 'screenshots' => true, 'snapshots' => true]

// Full trace with sources
$config = traceFull();
// Returns: ['mode' => 'on', 'screenshots' => true, 'snapshots' => true, 'sources' => true]
```

---

## Complete Example

```php
<?php

use function PestWP\Functions\viewportMobile;
use function PestWP\Functions\viewportDesktop;
use function PestWP\Functions\wooShopAddToCartSelector;
use function PestWP\Functions\wooCartTotalSelector;
use function PestWP\Functions\isAccessible;
use function PestWP\Functions\captureScreenshot;
use function PestWP\Functions\compareScreenshot;

describe('WooCommerce Shop', function () {
    test('shop page is accessible', function () {
        $page->goto('/shop');
        expect(isAccessible($page->content()))->toBeTrue();
    });
    
    test('add to cart works on mobile', function () {
        $page->setViewportSize(viewportMobile());
        $page->goto('/shop');
        $page->click(wooShopAddToCartSelector());
        expect($page->locator(wooCartTotalSelector()))->toContainText('$');
    });
    
    test('shop page visual regression', function () {
        $page->setViewportSize(viewportDesktop());
        $page->goto('/shop');
        
        $path = captureScreenshot('shop-page');
        $page->screenshot(['path' => $path]);
        
        $result = compareScreenshot($path);
        expect($result['match'])->toBeTrue();
    });
});
```

---

## Function Reference

### Screenshot Functions
- `screenshots(?string $basePath = null)` - Get ScreenshotManager instance
- `captureScreenshot(?string $name = null, array $options = [])` - Get screenshot path
- `compareScreenshot(string $path, ?float $threshold = null)` - Compare against baseline
- `assertScreenshotMatches(string $path, ?float $threshold = null)` - Assert match
- `createBaseline(string $path, ?string $name = null)` - Create/update baseline
- `hasBaseline(string $name)` - Check if baseline exists
- `baselinePath(string $name)` - Get baseline file path
- `screenshotPath(string $name)` - Get screenshot file path
- `setScreenshotThreshold(float $threshold)` - Set comparison threshold
- `enableScreenshotUpdate()` - Enable update mode

### Viewport Functions
- `viewportMobile()`, `viewportTablet()`, `viewportDesktop()`, `viewportDesktopLarge()`
- `viewportIPhoneSE()`, `viewportIPhone12()`, `viewportIPhone14ProMax()`
- `viewportIPadMini()`, `viewportIPadAir()`, `viewportIPadPro11()`, `viewportIPadPro12()`
- `viewportMacBookAir()`, `viewportMacBookPro14()`, `viewport4K()`
- `mobileViewports()`, `tabletViewports()`, `desktopViewports()`, `allViewports()`
- `responsiveViewports()` - Common breakpoints for responsive testing
- `getViewport(string $name)` - Get preset by name
- `createViewport(...)` - Create custom viewport
- `viewportWPAdmin()`, `viewportGutenberg()`, `viewportSiteEditor()`

### Accessibility Functions
- `getAccessibilityViolations(string $html, array $checks = [])`
- `getAccessibilityViolationsByImpact(string $html, string $minImpact = 'serious')`
- `isAccessible(string $html)` - Check for critical violations
- `formatAccessibilityReport(array $violations)` - Format as readable report
- `checkWcagLevelA()`, `checkWcagLevelAA()`, `checkWcagLevelAAA()`

### Video/Trace Functions
- `videoRecordingConfig(...)`, `videoOnFailure()`, `videoAlways()`, `videoOff()`
- `traceConfig(...)`, `traceOnFailure()`, `traceFull()`
