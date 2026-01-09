<?php

declare(strict_types=1);

namespace PestWP\Functions;

/**
 * Viewport Presets for Responsive Testing
 *
 * Provides device viewport configurations for testing responsive designs.
 * Use with Playwright's setViewportSize() or browser.newContext({ viewport: ... }).
 */

// =============================================================================
// VIEWPORT TYPES
// =============================================================================

/**
 * Viewport configuration array.
 *
 * @phpstan-type ViewportConfig array{width: int, height: int, deviceScaleFactor?: float, isMobile?: bool, hasTouch?: bool}
 */

// =============================================================================
// MOBILE VIEWPORTS
// =============================================================================

/**
 * Get iPhone SE viewport (small mobile).
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportIPhoneSE(): array
{
    return [
        'width' => 375,
        'height' => 667,
        'deviceScaleFactor' => 2.0,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get iPhone 12/13/14 viewport (standard mobile).
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportIPhone12(): array
{
    return [
        'width' => 390,
        'height' => 844,
        'deviceScaleFactor' => 3.0,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get iPhone 14 Pro Max viewport (large mobile).
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportIPhone14ProMax(): array
{
    return [
        'width' => 430,
        'height' => 932,
        'deviceScaleFactor' => 3.0,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get Samsung Galaxy S21 viewport.
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportGalaxyS21(): array
{
    return [
        'width' => 360,
        'height' => 800,
        'deviceScaleFactor' => 3.0,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get Pixel 7 viewport.
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportPixel7(): array
{
    return [
        'width' => 412,
        'height' => 915,
        'deviceScaleFactor' => 2.625,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get generic mobile viewport (375x667).
 * This is the most common mobile testing viewport.
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportMobile(): array
{
    return [
        'width' => 375,
        'height' => 667,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get mobile landscape viewport.
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportMobileLandscape(): array
{
    return [
        'width' => 667,
        'height' => 375,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

// =============================================================================
// TABLET VIEWPORTS
// =============================================================================

/**
 * Get iPad Mini viewport.
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportIPadMini(): array
{
    return [
        'width' => 768,
        'height' => 1024,
        'deviceScaleFactor' => 2.0,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get iPad Air viewport.
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportIPadAir(): array
{
    return [
        'width' => 820,
        'height' => 1180,
        'deviceScaleFactor' => 2.0,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get iPad Pro 11" viewport.
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportIPadPro11(): array
{
    return [
        'width' => 834,
        'height' => 1194,
        'deviceScaleFactor' => 2.0,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get iPad Pro 12.9" viewport.
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportIPadPro12(): array
{
    return [
        'width' => 1024,
        'height' => 1366,
        'deviceScaleFactor' => 2.0,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get Samsung Galaxy Tab S7 viewport.
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportGalaxyTabS7(): array
{
    return [
        'width' => 800,
        'height' => 1280,
        'deviceScaleFactor' => 2.0,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get generic tablet viewport (768x1024).
 * This is the most common tablet testing viewport.
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportTablet(): array
{
    return [
        'width' => 768,
        'height' => 1024,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get tablet landscape viewport.
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportTabletLandscape(): array
{
    return [
        'width' => 1024,
        'height' => 768,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

// =============================================================================
// DESKTOP VIEWPORTS
// =============================================================================

/**
 * Get small desktop/laptop viewport (1280x720 - HD).
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportDesktop(): array
{
    return [
        'width' => 1280,
        'height' => 720,
        'isMobile' => false,
        'hasTouch' => false,
    ];
}

/**
 * Get standard desktop viewport (1366x768).
 * Most common laptop resolution.
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportDesktopStandard(): array
{
    return [
        'width' => 1366,
        'height' => 768,
        'isMobile' => false,
        'hasTouch' => false,
    ];
}

/**
 * Get large desktop viewport (1920x1080 - Full HD).
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportDesktopLarge(): array
{
    return [
        'width' => 1920,
        'height' => 1080,
        'isMobile' => false,
        'hasTouch' => false,
    ];
}

/**
 * Get extra large desktop viewport (2560x1440 - QHD/2K).
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportDesktopXL(): array
{
    return [
        'width' => 2560,
        'height' => 1440,
        'isMobile' => false,
        'hasTouch' => false,
    ];
}

/**
 * Get 4K desktop viewport (3840x2160).
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewport4K(): array
{
    return [
        'width' => 3840,
        'height' => 2160,
        'isMobile' => false,
        'hasTouch' => false,
    ];
}

/**
 * Get MacBook Air 13" viewport.
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportMacBookAir(): array
{
    return [
        'width' => 1440,
        'height' => 900,
        'deviceScaleFactor' => 2.0,
        'isMobile' => false,
        'hasTouch' => false,
    ];
}

/**
 * Get MacBook Pro 14" viewport.
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportMacBookPro14(): array
{
    return [
        'width' => 1512,
        'height' => 982,
        'deviceScaleFactor' => 2.0,
        'isMobile' => false,
        'hasTouch' => false,
    ];
}

/**
 * Get MacBook Pro 16" viewport.
 *
 * @return array{width: int, height: int, deviceScaleFactor: float, isMobile: bool, hasTouch: bool}
 */
function viewportMacBookPro16(): array
{
    return [
        'width' => 1728,
        'height' => 1117,
        'deviceScaleFactor' => 2.0,
        'isMobile' => false,
        'hasTouch' => false,
    ];
}

// =============================================================================
// VIEWPORT PRESETS COLLECTION
// =============================================================================

/**
 * Get all mobile viewport presets.
 *
 * @return array<string, array{width: int, height: int, isMobile: bool, hasTouch: bool, deviceScaleFactor?: float}>
 */
function mobileViewports(): array
{
    return [
        'iphone-se' => viewportIPhoneSE(),
        'iphone-12' => viewportIPhone12(),
        'iphone-14-pro-max' => viewportIPhone14ProMax(),
        'galaxy-s21' => viewportGalaxyS21(),
        'pixel-7' => viewportPixel7(),
        'mobile' => viewportMobile(),
        'mobile-landscape' => viewportMobileLandscape(),
    ];
}

/**
 * Get all tablet viewport presets.
 *
 * @return array<string, array{width: int, height: int, isMobile: bool, hasTouch: bool, deviceScaleFactor?: float}>
 */
function tabletViewports(): array
{
    return [
        'ipad-mini' => viewportIPadMini(),
        'ipad-air' => viewportIPadAir(),
        'ipad-pro-11' => viewportIPadPro11(),
        'ipad-pro-12' => viewportIPadPro12(),
        'galaxy-tab-s7' => viewportGalaxyTabS7(),
        'tablet' => viewportTablet(),
        'tablet-landscape' => viewportTabletLandscape(),
    ];
}

/**
 * Get all desktop viewport presets.
 *
 * @return array<string, array{width: int, height: int, isMobile: bool, hasTouch: bool, deviceScaleFactor?: float}>
 */
function desktopViewports(): array
{
    return [
        'desktop' => viewportDesktop(),
        'desktop-standard' => viewportDesktopStandard(),
        'desktop-large' => viewportDesktopLarge(),
        'desktop-xl' => viewportDesktopXL(),
        '4k' => viewport4K(),
        'macbook-air' => viewportMacBookAir(),
        'macbook-pro-14' => viewportMacBookPro14(),
        'macbook-pro-16' => viewportMacBookPro16(),
    ];
}

/**
 * Get all viewport presets.
 *
 * @return array<string, array{width: int, height: int, isMobile: bool, hasTouch: bool, deviceScaleFactor?: float}>
 */
function allViewports(): array
{
    return array_merge(
        mobileViewports(),
        tabletViewports(),
        desktopViewports()
    );
}

/**
 * Get common responsive breakpoint viewports for testing.
 * Returns the most commonly used viewports for responsive testing.
 *
 * @return array<string, array{width: int, height: int, isMobile: bool, hasTouch: bool}>
 */
function responsiveViewports(): array
{
    return [
        'mobile' => viewportMobile(),
        'tablet' => viewportTablet(),
        'desktop' => viewportDesktop(),
        'desktop-large' => viewportDesktopLarge(),
    ];
}

/**
 * Get a viewport preset by name.
 *
 * @param string $name Viewport name (e.g., 'mobile', 'tablet', 'iphone-12')
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool, deviceScaleFactor?: float}|null
 */
function getViewport(string $name): ?array
{
    $all = allViewports();
    return $all[$name] ?? null;
}

/**
 * Create a custom viewport configuration.
 *
 * @param int $width Viewport width in pixels
 * @param int $height Viewport height in pixels
 * @param bool $isMobile Whether to emulate mobile device
 * @param bool $hasTouch Whether device has touch capability
 * @param float|null $deviceScaleFactor Device pixel ratio
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool, deviceScaleFactor?: float}
 */
function createViewport(
    int $width,
    int $height,
    bool $isMobile = false,
    bool $hasTouch = false,
    ?float $deviceScaleFactor = null
): array {
    $viewport = [
        'width' => $width,
        'height' => $height,
        'isMobile' => $isMobile,
        'hasTouch' => $hasTouch,
    ];

    if ($deviceScaleFactor !== null) {
        $viewport['deviceScaleFactor'] = $deviceScaleFactor;
    }

    return $viewport;
}

// =============================================================================
// WORDPRESS-SPECIFIC VIEWPORTS
// =============================================================================

/**
 * Get viewport for WordPress admin (wide enough for sidebar).
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportWPAdmin(): array
{
    return [
        'width' => 1280,
        'height' => 800,
        'isMobile' => false,
        'hasTouch' => false,
    ];
}

/**
 * Get viewport for WordPress admin collapsed sidebar.
 * Tests responsive admin at smaller widths.
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportWPAdminCollapsed(): array
{
    return [
        'width' => 960,
        'height' => 800,
        'isMobile' => false,
        'hasTouch' => false,
    ];
}

/**
 * Get viewport for WordPress mobile admin.
 * Admin bar collapses and sidebar becomes hidden at this width.
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportWPAdminMobile(): array
{
    return [
        'width' => 782,
        'height' => 667,
        'isMobile' => true,
        'hasTouch' => true,
    ];
}

/**
 * Get viewport for Gutenberg editor.
 * Wide enough for comfortable editing with sidebars.
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportGutenberg(): array
{
    return [
        'width' => 1440,
        'height' => 900,
        'isMobile' => false,
        'hasTouch' => false,
    ];
}

/**
 * Get viewport for Site Editor (FSE).
 * Full Site Editor needs more width for template parts.
 *
 * @return array{width: int, height: int, isMobile: bool, hasTouch: bool}
 */
function viewportSiteEditor(): array
{
    return [
        'width' => 1600,
        'height' => 900,
        'isMobile' => false,
        'hasTouch' => false,
    ];
}
