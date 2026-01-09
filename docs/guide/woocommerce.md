# WooCommerce Testing

PestWP provides comprehensive browser testing support for WooCommerce stores with **90+ CSS selectors** covering admin, storefront, and checkout flows. These selectors are designed to work with WooCommerce 8.0+ and WordPress 6.5+.

## Overview

WooCommerce testing involves:
- **Admin testing**: Product management, orders, coupons, settings
- **Storefront testing**: Shop pages, product pages, cart, checkout
- **Account testing**: Customer registration, login, order history

## Quick Start

```php
use function PestWP\Functions\wooShopAddToCartSelector;
use function PestWP\Functions\wooProceedToCheckoutSelector;
use function PestWP\Functions\wooPlaceOrderSelector;

it('completes a purchase', function () {
    $this->browse(function ($browser) {
        $browser->visit('/shop')
            ->click(wooShopAddToCartSelector())
            ->visit('/cart')
            ->click(wooProceedToCheckoutSelector())
            ->type('#billing_first_name', 'John')
            ->type('#billing_last_name', 'Doe')
            ->type('#billing_email', 'john@example.com')
            ->click(wooPlaceOrderSelector())
            ->waitForText('Order received');
    });
});
```

---

## Admin Selectors

### Products

| Function | Description | Selector |
|----------|-------------|----------|
| `wooProductsUrl()` | Products list page URL | `edit.php?post_type=product` |
| `wooNewProductUrl()` | New product page URL | `post-new.php?post_type=product` |
| `wooEditProductUrl($id)` | Edit product URL | `post.php?post={$id}&action=edit` |
| `wooProductTitleSelector()` | Product title field | `#title, #post-title-0, .editor-post-title__input` |
| `wooProductPriceSelector()` | Regular price field | `#_regular_price` |
| `wooProductSalePriceSelector()` | Sale price field | `#_sale_price` |
| `wooProductSkuSelector()` | SKU field | `#_sku` |
| `wooProductStockSelector()` | Stock quantity field | `#_stock` |
| `wooProductStockStatusSelector()` | Stock status dropdown | `#_stock_status` |
| `wooProductWeightSelector()` | Weight field | `#_weight` |
| `wooProductDimensionSelector($dim)` | Dimension fields | `#_{$dimension}` |
| `wooProductTabSelector($tab)` | Product data tabs | `.{$tab}_options a` |
| `wooProductTypeSelector()` | Product type dropdown | `#product-type` |
| `wooVirtualProductSelector()` | Virtual checkbox | `#_virtual` |
| `wooDownloadableProductSelector()` | Downloadable checkbox | `#_downloadable` |
| `wooProductCategoriesSelector()` | Categories metabox | `#product_cat-all` |
| `wooProductTagsSelector()` | Tags metabox | `#tagsdiv-product_tag` |
| `wooProductGallerySelector()` | Gallery container | `#product_images_container` |
| `wooAddGalleryImageSelector()` | Add gallery image button | `.add_product_images` |
| `wooProductImageSelector()` | Featured image | `#set-post-thumbnail` |

#### Example: Create a Product

```php
use function PestWP\Functions\wooNewProductUrl;
use function PestWP\Functions\wooProductTitleSelector;
use function PestWP\Functions\wooProductPriceSelector;
use function PestWP\Functions\wooProductTabSelector;
use function PestWP\Functions\wooProductStockSelector;

it('creates a simple product', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit(wooNewProductUrl())
            ->type(wooProductTitleSelector(), 'Test Product')
            ->type(wooProductPriceSelector(), '29.99')
            ->click(wooProductTabSelector('inventory'))
            ->type(wooProductStockSelector(), '100')
            ->click('#publish')
            ->waitForText('Product published');
    });
});
```

### Orders

| Function | Description | Selector |
|----------|-------------|----------|
| `wooOrdersUrl()` | Orders list (HPOS) | `admin.php?page=wc-orders` |
| `wooOrdersLegacyUrl()` | Orders list (legacy) | `edit.php?post_type=shop_order` |
| `wooNewOrderUrl()` | New order page | `admin.php?page=wc-orders&action=new` |
| `wooEditOrderUrl($id)` | Edit order URL | `admin.php?page=wc-orders&action=edit&id={$id}` |
| `wooOrderStatusSelector()` | Order status dropdown | `#order_status` |
| `wooOrderCustomerSelector()` | Customer selector | `#customer_user, .wc-customer-search` |
| `wooOrderItemsSelector()` | Order items metabox | `#woocommerce-order-items` |
| `wooAddOrderItemSelector()` | Add line item button | `.add-line-item` |
| `wooAddOrderProductSelector()` | Add product button | `.add-order-item` |
| `wooOrderNotesSelector()` | Order notes metabox | `#woocommerce-order-notes` |
| `wooAddOrderNoteSelector()` | Add note button | `.add_note` |
| `wooOrderNoteInputSelector()` | Note textarea | `#add_order_note` |
| `wooOrderBillingFieldSelector($field)` | Billing fields | `#_billing_{$field}` |
| `wooOrderShippingFieldSelector($field)` | Shipping fields | `#_shipping_{$field}` |
| `wooOrderActionsSelector()` | Order actions metabox | `#woocommerce-order-actions` |
| `wooOrderActionDropdownSelector()` | Action dropdown | `#wc_order_action` |

#### Example: Process an Order

```php
use function PestWP\Functions\wooEditOrderUrl;
use function PestWP\Functions\wooOrderStatusSelector;
use function PestWP\Functions\wooAddOrderNoteSelector;
use function PestWP\Functions\wooOrderNoteInputSelector;

it('processes an order to completed', function () {
    $order = wc_create_order();
    
    $this->browse(function ($browser) use ($order) {
        $browser->loginAs(1)
            ->visit(wooEditOrderUrl($order->get_id()))
            ->select(wooOrderStatusSelector(), 'wc-completed')
            ->type(wooOrderNoteInputSelector(), 'Order shipped via FedEx')
            ->click(wooAddOrderNoteSelector())
            ->click('button.save_order')
            ->waitForText('Order updated');
    });
});
```

### Coupons

| Function | Description | Selector |
|----------|-------------|----------|
| `wooCouponsUrl()` | Coupons list URL | `edit.php?post_type=shop_coupon` |
| `wooNewCouponUrl()` | New coupon URL | `post-new.php?post_type=shop_coupon` |
| `wooCouponCodeSelector()` | Coupon code field | `#title` |
| `wooCouponTypeSelector()` | Discount type dropdown | `#discount_type` |
| `wooCouponAmountSelector()` | Discount amount | `#coupon_amount` |
| `wooCouponExpirySelector()` | Expiry date | `#expiry_date` |
| `wooCouponUsageLimitSelector()` | Usage limit | `#usage_limit` |
| `wooCouponUsageLimitPerUserSelector()` | Per-user limit | `#usage_limit_per_user` |

#### Example: Create a Coupon

```php
use function PestWP\Functions\wooNewCouponUrl;
use function PestWP\Functions\wooCouponCodeSelector;
use function PestWP\Functions\wooCouponTypeSelector;
use function PestWP\Functions\wooCouponAmountSelector;

it('creates a percentage discount coupon', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit(wooNewCouponUrl())
            ->type(wooCouponCodeSelector(), 'SAVE20')
            ->select(wooCouponTypeSelector(), 'percent')
            ->type(wooCouponAmountSelector(), '20')
            ->click('#publish')
            ->waitForText('Coupon published');
    });
});
```

### Settings

| Function | Description | Selector |
|----------|-------------|----------|
| `wooSettingsUrl($tab)` | Settings page URL | `admin.php?page=wc-settings&tab={$tab}` |
| `wooSettingsTabSelector($tab)` | Settings tab link | `.nav-tab-wrapper a[href*='tab={$tab}']` |
| `wooSettingsSectionSelector($section)` | Settings section | `.subsubsub a[href*='section={$section}']` |
| `wooSettingFieldSelector($id)` | Setting field by ID | `#{$fieldId}` |
| `wooSaveSettingsSelector()` | Save button | `.woocommerce-save-button` |

### Analytics

| Function | Description | Selector |
|----------|-------------|----------|
| `wooAnalyticsUrl($report)` | Analytics page URL | `admin.php?page=wc-admin&path=/analytics/{$report}` |
| `wooAnalyticsDateRangeSelector()` | Date range picker | `.woocommerce-filters-date` |
| `wooAnalyticsChartSelector()` | Chart container | `.woocommerce-chart` |
| `wooAnalyticsSummarySelector()` | Summary numbers | `.woocommerce-summary` |
| `wooAnalyticsTableSelector()` | Data table | `.woocommerce-table` |

---

## Storefront Selectors

### Shop Page

| Function | Description | Selector |
|----------|-------------|----------|
| `wooShopProductSelector()` | Product card | `.product, li.product` |
| `wooShopProductTitleSelector()` | Product title | `.woocommerce-loop-product__title` |
| `wooShopProductPriceSelector()` | Product price | `.product .price` |
| `wooShopAddToCartSelector()` | Add to cart button | `.add_to_cart_button, .ajax_add_to_cart` |
| `wooShopPaginationSelector()` | Pagination | `.woocommerce-pagination` |
| `wooShopOrderingSelector()` | Sort dropdown | `select.orderby` |
| `wooShopResultCountSelector()` | Result count | `.woocommerce-result-count` |

#### Example: Browse Shop

```php
use function PestWP\Functions\wooShopProductSelector;
use function PestWP\Functions\wooShopOrderingSelector;
use function PestWP\Functions\wooShopAddToCartSelector;

it('adds product to cart from shop page', function () {
    $this->browse(function ($browser) {
        $browser->visit('/shop')
            ->assertVisible(wooShopProductSelector())
            ->select(wooShopOrderingSelector(), 'price')
            ->waitForReload()
            ->click(wooShopAddToCartSelector())
            ->waitForText('added to your cart');
    });
});
```

### Single Product Page

| Function | Description | Selector |
|----------|-------------|----------|
| `wooSingleProductTitleSelector()` | Product title | `.product_title, h1.product_title` |
| `wooSingleProductPriceSelector()` | Product price | `.single-product .price` |
| `wooSingleAddToCartSelector()` | Add to cart button | `.single_add_to_cart_button` |
| `wooProductQuantitySelector()` | Quantity input | `.quantity input[type="number"]` |
| `wooProductShortDescriptionSelector()` | Short description | `.woocommerce-product-details__short-description` |
| `wooProductDescriptionTabSelector()` | Description tab | `#tab-title-description` |
| `wooProductReviewsTabSelector()` | Reviews tab | `#tab-title-reviews` |
| `wooProductInfoTabSelector()` | Additional info tab | `#tab-title-additional_information` |
| `wooProductGalleryImagesSelector()` | Gallery images | `.woocommerce-product-gallery` |
| `wooProductMainImageSelector()` | Main image | `.woocommerce-product-gallery__wrapper img` |
| `wooRelatedProductsSelector()` | Related products | `.related.products` |
| `wooUpsellProductsSelector()` | Upsell products | `.upsells.products` |
| `wooVariationSelector($attr)` | Variation dropdown | `select[name='attribute_{$attribute}']` |
| `wooVariationPriceSelector()` | Variation price | `.woocommerce-variation-price` |

#### Example: Variable Product

```php
use function PestWP\Functions\wooVariationSelector;
use function PestWP\Functions\wooVariationPriceSelector;
use function PestWP\Functions\wooProductQuantitySelector;
use function PestWP\Functions\wooSingleAddToCartSelector;

it('selects product variation and adds to cart', function () {
    $this->browse(function ($browser) {
        $browser->visit('/product/variable-tshirt')
            ->select(wooVariationSelector('pa_size'), 'large')
            ->select(wooVariationSelector('pa_color'), 'blue')
            ->waitFor(wooVariationPriceSelector())
            ->assertVisible(wooVariationPriceSelector())
            ->clear(wooProductQuantitySelector())
            ->type(wooProductQuantitySelector(), '2')
            ->click(wooSingleAddToCartSelector())
            ->waitForText('have been added');
    });
});
```

### Cart

| Function | Description | Selector |
|----------|-------------|----------|
| `wooCartTableSelector()` | Cart table | `.woocommerce-cart-form, table.cart` |
| `wooCartItemSelector()` | Cart item row | `tr.cart_item` |
| `wooCartRemoveItemSelector()` | Remove item button | `a.remove` |
| `wooCartQuantitySelector()` | Item quantity input | `.cart_item input.qty` |
| `wooUpdateCartSelector()` | Update cart button | `button[name="update_cart"]` |
| `wooCartSubtotalSelector()` | Cart subtotal | `.cart-subtotal` |
| `wooCartTotalSelector()` | Cart total | `.order-total` |
| `wooProceedToCheckoutSelector()` | Proceed to checkout | `.checkout-button` |
| `wooCartCouponInputSelector()` | Coupon code input | `#coupon_code` |
| `wooApplyCouponSelector()` | Apply coupon button | `button[name="apply_coupon"]` |
| `wooMiniCartSelector()` | Mini cart widget | `.widget_shopping_cart` |
| `wooCartIconSelector()` | Cart icon in header | `.cart-contents, .cart-count` |

#### Example: Cart Operations

```php
use function PestWP\Functions\wooCartQuantitySelector;
use function PestWP\Functions\wooUpdateCartSelector;
use function PestWP\Functions\wooCartCouponInputSelector;
use function PestWP\Functions\wooApplyCouponSelector;
use function PestWP\Functions\wooCartTotalSelector;

it('applies coupon and updates quantity', function () {
    // Add product to cart first
    addProductToCart($productId);
    
    $this->browse(function ($browser) {
        $browser->visit('/cart')
            // Update quantity
            ->clear(wooCartQuantitySelector())
            ->type(wooCartQuantitySelector(), '3')
            ->click(wooUpdateCartSelector())
            ->waitForText('Cart updated')
            // Apply coupon
            ->type(wooCartCouponInputSelector(), 'SAVE10')
            ->click(wooApplyCouponSelector())
            ->waitForText('Coupon code applied')
            // Verify total updated
            ->assertVisible(wooCartTotalSelector());
    });
});
```

### Checkout

| Function | Description | Selector |
|----------|-------------|----------|
| `wooCheckoutFormSelector()` | Checkout form | `form.woocommerce-checkout` |
| `wooBillingFieldSelector($field)` | Billing fields | `#billing_{$field}` |
| `wooShippingFieldSelector($field)` | Shipping fields | `#shipping_{$field}` |
| `wooShipDifferentAddressSelector()` | Ship to different checkbox | `#ship-to-different-address-checkbox` |
| `wooCheckoutNotesSelector()` | Order notes | `#order_comments` |
| `wooPaymentMethodSelector($id)` | Payment method radio | `#payment_method_{$methodId}` |
| `wooPaymentMethodsSelector()` | Payment methods list | `#payment ul.payment_methods` |
| `wooPlaceOrderSelector()` | Place order button | `#place_order` |
| `wooCheckoutLoginSelector()` | Checkout login form | `.woocommerce-form-login` |
| `wooCreateAccountSelector()` | Create account checkbox | `#createaccount` |
| `wooAccountPasswordSelector()` | Account password field | `#account_password` |
| `wooShippingMethodsSelector()` | Shipping methods list | `#shipping_method` |
| `wooShippingMethodSelector($id)` | Specific shipping method | `#shipping_method_0_{$methodId}` |
| `wooOrderReviewSelector()` | Order review section | `#order_review` |
| `wooCheckoutTotalSelector()` | Checkout total | `.order-total` |

#### Billing/Shipping Fields

Available fields for `wooBillingFieldSelector()` and `wooShippingFieldSelector()`:
- `first_name`, `last_name`, `company`
- `country`, `address_1`, `address_2`
- `city`, `state`, `postcode`
- `phone`, `email` (billing only)

#### Example: Complete Checkout

```php
use function PestWP\Functions\wooBillingFieldSelector;
use function PestWP\Functions\wooPaymentMethodSelector;
use function PestWP\Functions\wooPlaceOrderSelector;

it('completes checkout with COD payment', function () {
    addProductToCart($productId);
    
    $this->browse(function ($browser) {
        $browser->visit('/checkout')
            // Billing details
            ->type(wooBillingFieldSelector('first_name'), 'John')
            ->type(wooBillingFieldSelector('last_name'), 'Doe')
            ->select(wooBillingFieldSelector('country'), 'US')
            ->type(wooBillingFieldSelector('address_1'), '123 Main St')
            ->type(wooBillingFieldSelector('city'), 'New York')
            ->select(wooBillingFieldSelector('state'), 'NY')
            ->type(wooBillingFieldSelector('postcode'), '10001')
            ->type(wooBillingFieldSelector('phone'), '555-1234')
            ->type(wooBillingFieldSelector('email'), 'john@example.com')
            // Payment
            ->click(wooPaymentMethodSelector('cod'))
            ->click(wooPlaceOrderSelector())
            ->waitForText('Order received')
            ->assertSee('Thank you');
    });
});
```

#### Example: Guest vs Account Checkout

```php
use function PestWP\Functions\wooCreateAccountSelector;
use function PestWP\Functions\wooAccountPasswordSelector;

it('creates account during checkout', function () {
    $this->browse(function ($browser) {
        $browser->visit('/checkout')
            ->fillCheckoutForm()
            // Create account
            ->check(wooCreateAccountSelector())
            ->waitFor(wooAccountPasswordSelector())
            ->type(wooAccountPasswordSelector(), 'SecurePass123!')
            ->click(wooPlaceOrderSelector())
            ->waitForText('Order received');
    });
    
    // Verify account was created
    expect(email_exists('john@example.com'))->toBeInt();
});
```

### My Account

| Function | Description | Selector |
|----------|-------------|----------|
| `wooAccountNavSelector()` | Account navigation | `.woocommerce-MyAccount-navigation` |
| `wooAccountContentSelector()` | Account content area | `.woocommerce-MyAccount-content` |
| `wooAccountNavLinkSelector($endpoint)` | Navigation link | `.woocommerce-MyAccount-navigation-link--{$endpoint} a` |
| `wooAccountOrdersSelector()` | Orders table | `.woocommerce-orders-table` |
| `wooViewOrderSelector()` | View order button | `a.woocommerce-button.view` |
| `wooAccountDownloadsSelector()` | Downloads table | `.woocommerce-table--order-downloads` |
| `wooEditAddressSelector($type)` | Edit address link | `.woocommerce-Address-title a[href*='{$type}']` |
| `wooAccountDetailsFormSelector()` | Account details form | `form.woocommerce-EditAccountForm` |

#### Account Endpoints

Available endpoints for `wooAccountNavLinkSelector()`:
- `dashboard`, `orders`, `downloads`
- `edit-address`, `payment-methods`
- `edit-account`, `customer-logout`

#### Example: My Account Navigation

```php
use function PestWP\Functions\wooAccountNavLinkSelector;
use function PestWP\Functions\wooAccountOrdersSelector;
use function PestWP\Functions\wooViewOrderSelector;

it('views order history', function () {
    $customer = createUser(['role' => 'customer']);
    createWooOrder(['customer_id' => $customer->ID]);
    
    $this->browse(function ($browser) use ($customer) {
        $browser->loginAs($customer)
            ->visit('/my-account')
            ->click(wooAccountNavLinkSelector('orders'))
            ->assertVisible(wooAccountOrdersSelector())
            ->click(wooViewOrderSelector())
            ->assertSee('Order details');
    });
});
```

### Login & Registration

| Function | Description | Selector |
|----------|-------------|----------|
| `wooLoginFormSelector()` | Login form | `form.woocommerce-form-login` |
| `wooRegisterFormSelector()` | Register form | `form.woocommerce-form-register` |
| `wooLoginUsernameSelector()` | Username field | `#username` |
| `wooLoginPasswordSelector()` | Password field | `#password` |
| `wooRememberMeSelector()` | Remember me checkbox | `#rememberme` |
| `wooLoginSubmitSelector()` | Login button | `button[name="login"]` |
| `wooRegisterEmailSelector()` | Register email | `#reg_email` |
| `wooRegisterPasswordSelector()` | Register password | `#reg_password` |
| `wooRegisterSubmitSelector()` | Register button | `button[name="register"]` |
| `wooLostPasswordSelector()` | Lost password link | `.woocommerce-LostPassword a` |

#### Example: Customer Login

```php
use function PestWP\Functions\wooLoginUsernameSelector;
use function PestWP\Functions\wooLoginPasswordSelector;
use function PestWP\Functions\wooLoginSubmitSelector;

it('logs in customer', function () {
    $customer = createUser([
        'role' => 'customer',
        'user_login' => 'testcustomer',
        'user_pass' => 'password123'
    ]);
    
    $this->browse(function ($browser) {
        $browser->visit('/my-account')
            ->type(wooLoginUsernameSelector(), 'testcustomer')
            ->type(wooLoginPasswordSelector(), 'password123')
            ->click(wooLoginSubmitSelector())
            ->waitForText('Dashboard')
            ->assertPathIs('/my-account/');
    });
});
```

### Notices

| Function | Description | Selector |
|----------|-------------|----------|
| `wooNoticeSelector()` | Any notice | `.woocommerce-notices-wrapper, .woocommerce-message` |
| `wooSuccessNoticeSelector()` | Success message | `.woocommerce-message` |
| `wooErrorNoticeSelector()` | Error message | `.woocommerce-error` |
| `wooInfoNoticeSelector()` | Info message | `.woocommerce-info` |
| `wooAddedToCartNoticeSelector()` | Added to cart notice | `.woocommerce-message:has-text('cart')` |

#### Example: Verify Notices

```php
use function PestWP\Functions\wooSuccessNoticeSelector;
use function PestWP\Functions\wooErrorNoticeSelector;

it('shows validation errors on checkout', function () {
    addProductToCart($productId);
    
    $this->browse(function ($browser) {
        $browser->visit('/checkout')
            ->click(wooPlaceOrderSelector())
            ->waitFor(wooErrorNoticeSelector())
            ->assertSeeIn(wooErrorNoticeSelector(), 'required field');
    });
});

it('shows success message after order', function () {
    $this->browse(function ($browser) {
        $browser->visit('/checkout')
            ->fillCheckoutForm()
            ->click(wooPlaceOrderSelector())
            ->waitForText('Order received');
        
        // Success page doesn't use notice, but thank you message
        $browser->assertSee('Thank you');
    });
});
```

---

## Complete Checkout Flow Example

```php
use function PestWP\Functions\{
    wooShopAddToCartSelector,
    wooCartQuantitySelector,
    wooUpdateCartSelector,
    wooProceedToCheckoutSelector,
    wooBillingFieldSelector,
    wooPaymentMethodSelector,
    wooPlaceOrderSelector,
    wooSuccessNoticeSelector
};

it('completes full purchase flow', function () {
    // Create test product
    $product = createWooProduct([
        'name' => 'Test Product',
        'regular_price' => '49.99',
        'stock_quantity' => 100
    ]);
    
    $this->browse(function ($browser) use ($product) {
        // 1. Add to cart from shop
        $browser->visit('/shop')
            ->click(wooShopAddToCartSelector() . ':first-child')
            ->waitForText('added to your cart')
            ->visit('/cart');
        
        // 2. Update quantity
        $browser->clear(wooCartQuantitySelector())
            ->type(wooCartQuantitySelector(), '2')
            ->click(wooUpdateCartSelector())
            ->waitForText('Cart updated');
        
        // 3. Proceed to checkout
        $browser->click(wooProceedToCheckoutSelector())
            ->waitFor(wooCheckoutFormSelector());
        
        // 4. Fill billing details
        $browser->type(wooBillingFieldSelector('first_name'), 'Jane')
            ->type(wooBillingFieldSelector('last_name'), 'Smith')
            ->select(wooBillingFieldSelector('country'), 'US')
            ->type(wooBillingFieldSelector('address_1'), '456 Oak Ave')
            ->type(wooBillingFieldSelector('city'), 'Los Angeles')
            ->select(wooBillingFieldSelector('state'), 'CA')
            ->type(wooBillingFieldSelector('postcode'), '90001')
            ->type(wooBillingFieldSelector('phone'), '555-9876')
            ->type(wooBillingFieldSelector('email'), 'jane@example.com');
        
        // 5. Select payment and place order
        $browser->click(wooPaymentMethodSelector('cod'))
            ->click(wooPlaceOrderSelector())
            ->waitForText('Order received');
        
        // 6. Verify order confirmation
        $browser->assertSee('Thank you')
            ->assertSee('Jane Smith')
            ->assertSee('$99.98'); // 2 x $49.99
    });
    
    // Verify order in database
    $orders = wc_get_orders(['customer' => 'jane@example.com']);
    expect($orders)->toHaveCount(1);
    expect($orders[0]->get_total())->toBe('99.98');
});
```

---

## Testing Payment Gateways

### Stripe Test Mode

```php
it('processes Stripe payment', function () {
    $this->browse(function ($browser) {
        $browser->visit('/checkout')
            ->fillCheckoutForm()
            ->click(wooPaymentMethodSelector('stripe'))
            ->waitFor('#stripe-card-element iframe')
            ->withinFrame('#stripe-card-element iframe', function ($frame) {
                $frame->type('[name="cardnumber"]', '4242424242424242')
                    ->type('[name="exp-date"]', '12/25')
                    ->type('[name="cvc"]', '123');
            })
            ->click(wooPlaceOrderSelector())
            ->waitForText('Order received', 30);
    });
});
```

### PayPal Sandbox

```php
it('redirects to PayPal', function () {
    $this->browse(function ($browser) {
        $browser->visit('/checkout')
            ->fillCheckoutForm()
            ->click(wooPaymentMethodSelector('paypal'))
            ->click(wooPlaceOrderSelector())
            ->waitForLocation('/checkout/order-pay/')
            ->assertSee('PayPal');
    });
});
```

---

## Best Practices

### 1. Use Test Mode

Always run WooCommerce in test mode:
```php
beforeEach(function () {
    update_option('woocommerce_sandbox_mode', 'yes');
});
```

### 2. Create Products Programmatically

```php
function createWooProduct(array $data = []): WC_Product
{
    $product = new WC_Product_Simple();
    $product->set_name($data['name'] ?? 'Test Product');
    $product->set_regular_price($data['regular_price'] ?? '10.00');
    $product->set_stock_quantity($data['stock_quantity'] ?? 100);
    $product->set_stock_status('instock');
    $product->save();
    
    return $product;
}
```

### 3. Helper for Cart Operations

```php
function addProductToCart(int $productId, int $quantity = 1): void
{
    WC()->cart->add_to_cart($productId, $quantity);
}

function emptyCart(): void
{
    WC()->cart->empty_cart();
}
```

### 4. Isolate Test Orders

```php
afterEach(function () {
    // Clean up test orders
    $orders = wc_get_orders(['limit' => -1]);
    foreach ($orders as $order) {
        $order->delete(true);
    }
});
```

---

## Next Steps

- [Browser Testing](browser-testing.md) - Learn about Playwright integration
- [Factories](factories.md) - Create test data easily
- [Database Isolation](database-isolation.md) - Keep tests isolated
- [Accessibility Testing](accessibility-testing.md) - Test WCAG compliance
