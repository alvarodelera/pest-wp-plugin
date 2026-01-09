<?php

declare(strict_types=1);

use function PestWP\Functions\wooProductsUrl;
use function PestWP\Functions\wooOrdersUrl;
use function PestWP\Functions\wooSettingsUrl;
use function PestWP\Functions\wooProductTitleSelector;
use function PestWP\Functions\wooProductPriceSelector;
use function PestWP\Functions\wooProductStockSelector;
use function PestWP\Functions\wooOrderStatusSelector;
use function PestWP\Functions\wooShopProductSelector;
use function PestWP\Functions\wooShopAddToCartSelector;
use function PestWP\Functions\wooCartTotalSelector;
use function PestWP\Functions\wooCheckoutFormSelector;
use function PestWP\Functions\wooBillingFieldSelector;
use function PestWP\Functions\wooPlaceOrderSelector;
use function PestWP\Functions\wooNoticeSelector;
use function PestWP\Functions\wooSuccessNoticeSelector;
use function PestWP\Functions\wooErrorNoticeSelector;
use function PestWP\Functions\wooAccountNavSelector;
use function PestWP\Functions\wooAnalyticsUrl;
use function PestWP\Functions\wooCouponsUrl;

describe('WooCommerce Admin URLs', function (): void {
    test('wooProductsUrl returns correct URL', function (): void {
        $url = wooProductsUrl();
        
        expect($url)->toContain('/wp-admin/');
        expect($url)->toContain('post_type=product');
    });

    test('wooOrdersUrl returns correct URL', function (): void {
        $url = wooOrdersUrl();
        
        expect($url)->toContain('/wp-admin/');
        expect($url)->toContain('wc-orders');
    });

    test('wooSettingsUrl returns general settings by default', function (): void {
        $url = wooSettingsUrl();
        
        expect($url)->toContain('page=wc-settings');
    });

    test('wooSettingsUrl with tab parameter', function (): void {
        $url = wooSettingsUrl('products');
        
        expect($url)->toContain('tab=products');
    });

    test('wooAnalyticsUrl returns analytics URL', function (): void {
        $url = wooAnalyticsUrl();
        
        expect($url)->toContain('wc-admin');
        expect($url)->toContain('analytics');
    });

    test('wooCouponsUrl returns coupons URL', function (): void {
        $url = wooCouponsUrl();
        
        expect($url)->toContain('/wp-admin/');
        expect($url)->toContain('shop_coupon');
    });
});

describe('WooCommerce Admin Selectors', function (): void {
    test('wooProductTitleSelector returns correct selector', function (): void {
        $selector = wooProductTitleSelector();
        
        expect($selector)->not->toBeEmpty();
    });

    test('wooProductPriceSelector returns correct selector', function (): void {
        $selector = wooProductPriceSelector();
        
        expect($selector)->toContain('_regular_price');
    });

    test('wooProductStockSelector returns correct selector', function (): void {
        $selector = wooProductStockSelector();
        
        expect($selector)->toContain('_stock');
    });

    test('wooOrderStatusSelector returns correct selector', function (): void {
        $selector = wooOrderStatusSelector();
        
        expect($selector)->toContain('order_status');
    });
});

describe('WooCommerce Storefront Selectors', function (): void {
    test('wooShopProductSelector returns correct selector', function (): void {
        $selector = wooShopProductSelector();
        
        expect($selector)->toContain('product');
    });

    test('wooShopAddToCartSelector returns correct selector', function (): void {
        $selector = wooShopAddToCartSelector();
        
        expect($selector)->toContain('add_to_cart');
    });

    test('wooCartTotalSelector returns correct selector', function (): void {
        $selector = wooCartTotalSelector();
        
        expect($selector)->toContain('cart');
    });
});

describe('WooCommerce Checkout Selectors', function (): void {
    test('wooCheckoutFormSelector returns correct selector', function (): void {
        $selector = wooCheckoutFormSelector();
        
        expect($selector)->toContain('checkout');
    });

    test('wooBillingFieldSelector returns correct selector for field', function (): void {
        $selector = wooBillingFieldSelector('first_name');
        
        expect($selector)->toContain('billing_first_name');
    });

    test('wooPlaceOrderSelector returns correct selector', function (): void {
        $selector = wooPlaceOrderSelector();
        
        expect($selector)->toContain('place_order');
    });
});

describe('WooCommerce Notice Selectors', function (): void {
    test('wooNoticeSelector returns generic notice selector', function (): void {
        $selector = wooNoticeSelector();
        
        expect($selector)->toContain('woocommerce');
        expect($selector)->toContain('message');
    });

    test('wooSuccessNoticeSelector returns success notice selector', function (): void {
        $selector = wooSuccessNoticeSelector();
        
        expect($selector)->toContain('woocommerce-message');
    });

    test('wooErrorNoticeSelector returns error notice selector', function (): void {
        $selector = wooErrorNoticeSelector();
        
        expect($selector)->toContain('error');
    });
});

describe('WooCommerce My Account Selectors', function (): void {
    test('wooAccountNavSelector returns correct selector', function (): void {
        $selector = wooAccountNavSelector();
        
        expect($selector)->toContain('woocommerce-MyAccount-navigation');
    });
});
