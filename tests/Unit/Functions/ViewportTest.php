<?php

declare(strict_types=1);

use function PestWP\Functions\viewportMobile;
use function PestWP\Functions\viewportTablet;
use function PestWP\Functions\viewportDesktop;
use function PestWP\Functions\viewportDesktopLarge;
use function PestWP\Functions\viewportIPhoneSE;
use function PestWP\Functions\viewportIPhone12;
use function PestWP\Functions\viewportIPadMini;
use function PestWP\Functions\viewportIPadPro12;
use function PestWP\Functions\viewportMacBookAir;
use function PestWP\Functions\mobileViewports;
use function PestWP\Functions\tabletViewports;
use function PestWP\Functions\desktopViewports;
use function PestWP\Functions\allViewports;
use function PestWP\Functions\responsiveViewports;
use function PestWP\Functions\getViewport;
use function PestWP\Functions\createViewport;
use function PestWP\Functions\viewportWPAdmin;
use function PestWP\Functions\viewportGutenberg;

describe('Viewport Presets', function (): void {
    test('viewportMobile returns correct dimensions', function (): void {
        $viewport = viewportMobile();
        
        expect($viewport['width'])->toBe(375);
        expect($viewport['height'])->toBe(667);
        expect($viewport['isMobile'])->toBeTrue();
        expect($viewport['hasTouch'])->toBeTrue();
    });

    test('viewportTablet returns correct dimensions', function (): void {
        $viewport = viewportTablet();
        
        expect($viewport['width'])->toBe(768);
        expect($viewport['height'])->toBe(1024);
        expect($viewport['isMobile'])->toBeTrue();
        expect($viewport['hasTouch'])->toBeTrue();
    });

    test('viewportDesktop returns correct dimensions', function (): void {
        $viewport = viewportDesktop();
        
        expect($viewport['width'])->toBe(1280);
        expect($viewport['height'])->toBe(720);
        expect($viewport['isMobile'])->toBeFalse();
        expect($viewport['hasTouch'])->toBeFalse();
    });

    test('viewportDesktopLarge returns 1920x1080', function (): void {
        $viewport = viewportDesktopLarge();
        
        expect($viewport['width'])->toBe(1920);
        expect($viewport['height'])->toBe(1080);
    });

    test('viewportIPhoneSE has correct device scale factor', function (): void {
        $viewport = viewportIPhoneSE();
        
        expect($viewport['deviceScaleFactor'])->toBe(2.0);
        expect($viewport['width'])->toBe(375);
    });

    test('viewportIPhone12 has correct device scale factor', function (): void {
        $viewport = viewportIPhone12();
        
        expect($viewport['deviceScaleFactor'])->toBe(3.0);
        expect($viewport['width'])->toBe(390);
    });

    test('viewportIPadMini returns tablet dimensions', function (): void {
        $viewport = viewportIPadMini();
        
        expect($viewport['width'])->toBe(768);
        expect($viewport['height'])->toBe(1024);
        expect($viewport['isMobile'])->toBeTrue();
    });

    test('viewportIPadPro12 returns large tablet dimensions', function (): void {
        $viewport = viewportIPadPro12();
        
        expect($viewport['width'])->toBe(1024);
        expect($viewport['height'])->toBe(1366);
    });

    test('viewportMacBookAir returns laptop dimensions', function (): void {
        $viewport = viewportMacBookAir();
        
        expect($viewport['width'])->toBe(1440);
        expect($viewport['height'])->toBe(900);
        expect($viewport['isMobile'])->toBeFalse();
        expect($viewport['deviceScaleFactor'])->toBe(2.0);
    });
});

describe('Viewport Collections', function (): void {
    test('mobileViewports returns all mobile presets', function (): void {
        $viewports = mobileViewports();
        
        expect($viewports)->toBeArray();
        expect($viewports)->toHaveKey('mobile');
        expect($viewports)->toHaveKey('iphone-se');
        expect($viewports)->toHaveKey('iphone-12');
        expect($viewports)->toHaveKey('mobile-landscape');
        
        foreach ($viewports as $viewport) {
            expect($viewport['isMobile'])->toBeTrue();
        }
    });

    test('tabletViewports returns all tablet presets', function (): void {
        $viewports = tabletViewports();
        
        expect($viewports)->toBeArray();
        expect($viewports)->toHaveKey('tablet');
        expect($viewports)->toHaveKey('ipad-mini');
        expect($viewports)->toHaveKey('ipad-air');
        expect($viewports)->toHaveKey('tablet-landscape');
    });

    test('desktopViewports returns all desktop presets', function (): void {
        $viewports = desktopViewports();
        
        expect($viewports)->toBeArray();
        expect($viewports)->toHaveKey('desktop');
        expect($viewports)->toHaveKey('desktop-large');
        expect($viewports)->toHaveKey('macbook-air');
        
        foreach ($viewports as $viewport) {
            expect($viewport['isMobile'])->toBeFalse();
        }
    });

    test('allViewports combines all presets', function (): void {
        $all = allViewports();
        $mobile = mobileViewports();
        $tablet = tabletViewports();
        $desktop = desktopViewports();
        
        expect(count($all))->toBe(count($mobile) + count($tablet) + count($desktop));
    });

    test('responsiveViewports returns common breakpoints', function (): void {
        $viewports = responsiveViewports();
        
        expect($viewports)->toHaveCount(4);
        expect($viewports)->toHaveKey('mobile');
        expect($viewports)->toHaveKey('tablet');
        expect($viewports)->toHaveKey('desktop');
        expect($viewports)->toHaveKey('desktop-large');
    });
});

describe('Viewport Helpers', function (): void {
    test('getViewport returns viewport by name', function (): void {
        $viewport = getViewport('mobile');
        
        expect($viewport)->not->toBeNull();
        expect($viewport['width'])->toBe(375);
    });

    test('getViewport returns null for unknown name', function (): void {
        $viewport = getViewport('unknown-device');
        
        expect($viewport)->toBeNull();
    });

    test('createViewport creates custom viewport', function (): void {
        $viewport = createViewport(800, 600, true, true, 2.0);
        
        expect($viewport['width'])->toBe(800);
        expect($viewport['height'])->toBe(600);
        expect($viewport['isMobile'])->toBeTrue();
        expect($viewport['hasTouch'])->toBeTrue();
        expect($viewport['deviceScaleFactor'])->toBe(2.0);
    });

    test('createViewport without deviceScaleFactor omits it', function (): void {
        $viewport = createViewport(1024, 768);
        
        expect($viewport)->not->toHaveKey('deviceScaleFactor');
        expect($viewport['isMobile'])->toBeFalse();
        expect($viewport['hasTouch'])->toBeFalse();
    });
});

describe('WordPress Viewports', function (): void {
    test('viewportWPAdmin returns admin-optimized dimensions', function (): void {
        $viewport = viewportWPAdmin();
        
        expect($viewport['width'])->toBe(1280);
        expect($viewport['height'])->toBe(800);
        expect($viewport['isMobile'])->toBeFalse();
    });

    test('viewportGutenberg returns editor-optimized dimensions', function (): void {
        $viewport = viewportGutenberg();
        
        expect($viewport['width'])->toBe(1440);
        expect($viewport['height'])->toBe(900);
        expect($viewport['isMobile'])->toBeFalse();
    });
});
