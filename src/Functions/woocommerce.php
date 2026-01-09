<?php

declare(strict_types=1);

namespace PestWP\Functions;

/**
 * WooCommerce Locators - Selectors for WooCommerce admin and storefront elements.
 *
 * These helpers provide resilient selectors for WooCommerce UI elements,
 * designed to work with WooCommerce 8.0+ and WordPress 6.5+.
 */

// =============================================================================
// WOOCOMMERCE ADMIN - PRODUCTS
// =============================================================================

/**
 * Get URL for WooCommerce products list.
 */
function wooProductsUrl(): string
{
    return adminUrl('edit.php', ['post_type' => 'product']);
}

/**
 * Get URL for adding a new WooCommerce product.
 */
function wooNewProductUrl(): string
{
    return adminUrl('post-new.php', ['post_type' => 'product']);
}

/**
 * Get URL for editing a WooCommerce product.
 */
function wooEditProductUrl(int $productId): string
{
    return adminUrl('post.php', ['post' => $productId, 'action' => 'edit']);
}

/**
 * Get selector for product title field.
 */
function wooProductTitleSelector(): string
{
    return '#title, #post-title-0, .editor-post-title__input';
}

/**
 * Get selector for product regular price field.
 */
function wooProductPriceSelector(): string
{
    return '#_regular_price';
}

/**
 * Get selector for product sale price field.
 */
function wooProductSalePriceSelector(): string
{
    return '#_sale_price';
}

/**
 * Get selector for product SKU field.
 */
function wooProductSkuSelector(): string
{
    return '#_sku';
}

/**
 * Get selector for product stock quantity field.
 */
function wooProductStockSelector(): string
{
    return '#_stock';
}

/**
 * Get selector for product stock status dropdown.
 */
function wooProductStockStatusSelector(): string
{
    return '#_stock_status';
}

/**
 * Get selector for product weight field.
 */
function wooProductWeightSelector(): string
{
    return '#_weight';
}

/**
 * Get selector for product dimensions fields.
 *
 * @param string $dimension 'length', 'width', or 'height'
 */
function wooProductDimensionSelector(string $dimension): string
{
    return "#_$dimension";
}

/**
 * Get selector for product data tabs.
 *
 * @param string $tab Tab name: 'general', 'inventory', 'shipping', 'linked_product', 'attribute', 'variations', 'advanced'
 */
function wooProductTabSelector(string $tab): string
{
    return ".{$tab}_options a, #{$tab}_product_data";
}

/**
 * Get selector for product type dropdown.
 */
function wooProductTypeSelector(): string
{
    return '#product-type';
}

/**
 * Get selector for virtual product checkbox.
 */
function wooVirtualProductSelector(): string
{
    return '#_virtual';
}

/**
 * Get selector for downloadable product checkbox.
 */
function wooDownloadableProductSelector(): string
{
    return '#_downloadable';
}

/**
 * Get selector for product categories metabox.
 */
function wooProductCategoriesSelector(): string
{
    return '#product_cat-all, #taxonomy-product_cat';
}

/**
 * Get selector for product tags metabox.
 */
function wooProductTagsSelector(): string
{
    return '#tagsdiv-product_tag, .tagsdiv';
}

/**
 * Get selector for product gallery images.
 */
function wooProductGallerySelector(): string
{
    return '#product_images_container, .product_images';
}

/**
 * Get selector for add product gallery image button.
 */
function wooAddGalleryImageSelector(): string
{
    return '.add_product_images, #add_product_images';
}

/**
 * Get selector for product featured image.
 */
function wooProductImageSelector(): string
{
    return '#set-post-thumbnail, .set-post-thumbnail';
}

// =============================================================================
// WOOCOMMERCE ADMIN - ORDERS
// =============================================================================

/**
 * Get URL for WooCommerce orders list.
 */
function wooOrdersUrl(): string
{
    // WooCommerce 8.0+ uses custom order tables with new URL
    return adminUrl('admin.php', ['page' => 'wc-orders']);
}

/**
 * Get URL for legacy WooCommerce orders list.
 */
function wooOrdersLegacyUrl(): string
{
    return adminUrl('edit.php', ['post_type' => 'shop_order']);
}

/**
 * Get URL for adding a new WooCommerce order.
 */
function wooNewOrderUrl(): string
{
    return adminUrl('admin.php', ['page' => 'wc-orders', 'action' => 'new']);
}

/**
 * Get URL for editing a WooCommerce order.
 */
function wooEditOrderUrl(int $orderId): string
{
    return adminUrl('admin.php', ['page' => 'wc-orders', 'action' => 'edit', 'id' => $orderId]);
}

/**
 * Get selector for order status dropdown.
 */
function wooOrderStatusSelector(): string
{
    return '#order_status';
}

/**
 * Get selector for order customer dropdown.
 */
function wooOrderCustomerSelector(): string
{
    return '#customer_user, .wc-customer-search';
}

/**
 * Get selector for order items metabox.
 */
function wooOrderItemsSelector(): string
{
    return '#woocommerce-order-items, .woocommerce_order_items_wrapper';
}

/**
 * Get selector for add order item button.
 */
function wooAddOrderItemSelector(): string
{
    return '.add-line-item, button.add-line-item';
}

/**
 * Get selector for add product to order button.
 */
function wooAddOrderProductSelector(): string
{
    return '.add-order-item, button.add-order-item';
}

/**
 * Get selector for order notes metabox.
 */
function wooOrderNotesSelector(): string
{
    return '#woocommerce-order-notes';
}

/**
 * Get selector for add order note button.
 */
function wooAddOrderNoteSelector(): string
{
    return '.add_note, button.add_note';
}

/**
 * Get selector for order note textarea.
 */
function wooOrderNoteInputSelector(): string
{
    return '#add_order_note, textarea[name="order_note"]';
}

/**
 * Get selector for order billing fields.
 *
 * @param string $field Field name: 'first_name', 'last_name', 'email', 'phone', 'address_1', 'city', 'postcode', 'country', 'state'
 */
function wooOrderBillingFieldSelector(string $field): string
{
    return "#_billing_{$field}";
}

/**
 * Get selector for order shipping fields.
 *
 * @param string $field Field name: 'first_name', 'last_name', 'address_1', 'city', 'postcode', 'country', 'state'
 */
function wooOrderShippingFieldSelector(string $field): string
{
    return "#_shipping_{$field}";
}

/**
 * Get selector for order actions metabox.
 */
function wooOrderActionsSelector(): string
{
    return '#woocommerce-order-actions';
}

/**
 * Get selector for order action dropdown.
 */
function wooOrderActionDropdownSelector(): string
{
    return '#wc_order_action';
}

// =============================================================================
// WOOCOMMERCE ADMIN - COUPONS
// =============================================================================

/**
 * Get URL for WooCommerce coupons list.
 */
function wooCouponsUrl(): string
{
    return adminUrl('edit.php', ['post_type' => 'shop_coupon']);
}

/**
 * Get URL for adding a new coupon.
 */
function wooNewCouponUrl(): string
{
    return adminUrl('post-new.php', ['post_type' => 'shop_coupon']);
}

/**
 * Get selector for coupon code field.
 */
function wooCouponCodeSelector(): string
{
    return '#title, #post-title-0';
}

/**
 * Get selector for coupon discount type dropdown.
 */
function wooCouponTypeSelector(): string
{
    return '#discount_type';
}

/**
 * Get selector for coupon amount field.
 */
function wooCouponAmountSelector(): string
{
    return '#coupon_amount';
}

/**
 * Get selector for coupon expiry date field.
 */
function wooCouponExpirySelector(): string
{
    return '#expiry_date';
}

/**
 * Get selector for coupon usage limit field.
 */
function wooCouponUsageLimitSelector(): string
{
    return '#usage_limit';
}

/**
 * Get selector for coupon usage limit per user field.
 */
function wooCouponUsageLimitPerUserSelector(): string
{
    return '#usage_limit_per_user';
}

// =============================================================================
// WOOCOMMERCE ADMIN - SETTINGS
// =============================================================================

/**
 * Get URL for WooCommerce settings.
 *
 * @param string $tab Tab name: 'general', 'products', 'tax', 'shipping', 'payments', 'accounts', 'emails', 'integration', 'advanced'
 */
function wooSettingsUrl(string $tab = 'general'): string
{
    return adminUrl('admin.php', ['page' => 'wc-settings', 'tab' => $tab]);
}

/**
 * Get selector for WooCommerce settings tab.
 *
 * @param string $tab Tab name
 */
function wooSettingsTabSelector(string $tab): string
{
    return ".nav-tab-wrapper a[href*='tab={$tab}'], .nav-tab-wrapper a:text-is('" . ucfirst($tab) . "')";
}

/**
 * Get selector for WooCommerce settings section.
 *
 * @param string $section Section name
 */
function wooSettingsSectionSelector(string $section): string
{
    return ".subsubsub a[href*='section={$section}'], .wc-settings-sub-title:text-is('{$section}')";
}

/**
 * Get selector for a WooCommerce setting field by ID.
 */
function wooSettingFieldSelector(string $fieldId): string
{
    return "#{$fieldId}";
}

/**
 * Get selector for save settings button.
 */
function wooSaveSettingsSelector(): string
{
    return ".woocommerce-save-button, button[name='save']";
}

// =============================================================================
// WOOCOMMERCE ADMIN - REPORTS & ANALYTICS
// =============================================================================

/**
 * Get URL for WooCommerce analytics.
 *
 * @param string $report Report type: 'overview', 'products', 'revenue', 'orders', 'categories', 'coupons', 'taxes', 'downloads', 'stock', 'customers'
 */
function wooAnalyticsUrl(string $report = 'overview'): string
{
    return adminUrl('admin.php', ['page' => 'wc-admin', 'path' => "/analytics/{$report}"]);
}

/**
 * Get selector for analytics date range picker.
 */
function wooAnalyticsDateRangeSelector(): string
{
    return '.woocommerce-filters-date, .woocommerce-filters__date-picker';
}

/**
 * Get selector for analytics chart.
 */
function wooAnalyticsChartSelector(): string
{
    return '.woocommerce-chart, .woocommerce-chart__container';
}

/**
 * Get selector for analytics summary numbers.
 */
function wooAnalyticsSummarySelector(): string
{
    return '.woocommerce-summary, .woocommerce-summary__item';
}

/**
 * Get selector for analytics table.
 */
function wooAnalyticsTableSelector(): string
{
    return '.woocommerce-table, .woocommerce-report-table';
}

// =============================================================================
// WOOCOMMERCE STOREFRONT - SHOP
// =============================================================================

/**
 * Get selector for product card on shop page.
 */
function wooShopProductSelector(): string
{
    return '.product, .products .product, li.product';
}

/**
 * Get selector for product title on shop page.
 */
function wooShopProductTitleSelector(): string
{
    return '.woocommerce-loop-product__title, .product-title, h2.woocommerce-loop-product__title';
}

/**
 * Get selector for product price on shop page.
 */
function wooShopProductPriceSelector(): string
{
    return '.product .price, .woocommerce-Price-amount';
}

/**
 * Get selector for add to cart button on shop page.
 */
function wooShopAddToCartSelector(): string
{
    return '.add_to_cart_button, .ajax_add_to_cart, button.add_to_cart_button';
}

/**
 * Get selector for shop pagination.
 */
function wooShopPaginationSelector(): string
{
    return '.woocommerce-pagination, .woo-pagination, nav.woocommerce-pagination';
}

/**
 * Get selector for shop ordering dropdown.
 */
function wooShopOrderingSelector(): string
{
    return '.orderby, select.orderby, .woocommerce-ordering select';
}

/**
 * Get selector for shop result count.
 */
function wooShopResultCountSelector(): string
{
    return '.woocommerce-result-count, .result-count';
}

// =============================================================================
// WOOCOMMERCE STOREFRONT - SINGLE PRODUCT
// =============================================================================

/**
 * Get selector for single product title.
 */
function wooSingleProductTitleSelector(): string
{
    return '.product_title, h1.product_title, .single-product .entry-title';
}

/**
 * Get selector for single product price.
 */
function wooSingleProductPriceSelector(): string
{
    return '.single-product .price, .single-product .woocommerce-Price-amount, p.price';
}

/**
 * Get selector for single product add to cart button.
 */
function wooSingleAddToCartSelector(): string
{
    return '.single_add_to_cart_button, button[name="add-to-cart"]';
}

/**
 * Get selector for product quantity input.
 */
function wooProductQuantitySelector(): string
{
    return '.quantity input[type="number"], input.qty, .quantity .input-text';
}

/**
 * Get selector for product short description.
 */
function wooProductShortDescriptionSelector(): string
{
    return '.woocommerce-product-details__short-description, .product-short-description';
}

/**
 * Get selector for product description tab.
 */
function wooProductDescriptionTabSelector(): string
{
    return '.woocommerce-tabs #tab-description, #tab-title-description';
}

/**
 * Get selector for product reviews tab.
 */
function wooProductReviewsTabSelector(): string
{
    return '.woocommerce-tabs #tab-reviews, #tab-title-reviews';
}

/**
 * Get selector for product additional info tab.
 */
function wooProductInfoTabSelector(): string
{
    return '.woocommerce-tabs #tab-additional_information, #tab-title-additional_information';
}

/**
 * Get selector for product gallery images.
 */
function wooProductGalleryImagesSelector(): string
{
    return '.woocommerce-product-gallery, .woocommerce-product-gallery__image';
}

/**
 * Get selector for product main image.
 */
function wooProductMainImageSelector(): string
{
    return '.woocommerce-product-gallery__image--placeholder, .woocommerce-product-gallery__wrapper img';
}

/**
 * Get selector for related products section.
 */
function wooRelatedProductsSelector(): string
{
    return '.related.products, section.related';
}

/**
 * Get selector for upsell products section.
 */
function wooUpsellProductsSelector(): string
{
    return '.upsells.products, section.upsells';
}

/**
 * Get selector for product variations dropdown.
 */
function wooVariationSelector(string $attribute): string
{
    return "#$attribute, select[name='attribute_{$attribute}']";
}

/**
 * Get selector for variation price display.
 */
function wooVariationPriceSelector(): string
{
    return '.woocommerce-variation-price, .single_variation_wrap .price';
}

// =============================================================================
// WOOCOMMERCE STOREFRONT - CART
// =============================================================================

/**
 * Get selector for cart table.
 */
function wooCartTableSelector(): string
{
    return '.woocommerce-cart-form, .shop_table.cart, table.cart';
}

/**
 * Get selector for cart item row.
 */
function wooCartItemSelector(): string
{
    return '.woocommerce-cart-form__cart-item, tr.cart_item';
}

/**
 * Get selector for cart item remove button.
 */
function wooCartRemoveItemSelector(): string
{
    return '.remove, a.remove';
}

/**
 * Get selector for cart item quantity input.
 */
function wooCartQuantitySelector(): string
{
    return '.cart_item .qty, .cart_item input.qty';
}

/**
 * Get selector for update cart button.
 */
function wooUpdateCartSelector(): string
{
    return 'button[name="update_cart"], .update-cart';
}

/**
 * Get selector for cart subtotal.
 */
function wooCartSubtotalSelector(): string
{
    return '.cart-subtotal, .cart_totals .cart-subtotal';
}

/**
 * Get selector for cart total.
 */
function wooCartTotalSelector(): string
{
    return '.order-total, .cart_totals .order-total';
}

/**
 * Get selector for proceed to checkout button.
 */
function wooProceedToCheckoutSelector(): string
{
    return '.checkout-button, a.checkout-button, .wc-proceed-to-checkout a';
}

/**
 * Get selector for coupon code input.
 */
function wooCartCouponInputSelector(): string
{
    return '#coupon_code, input[name="coupon_code"]';
}

/**
 * Get selector for apply coupon button.
 */
function wooApplyCouponSelector(): string
{
    return 'button[name="apply_coupon"], .coupon button';
}

/**
 * Get selector for mini cart widget.
 */
function wooMiniCartSelector(): string
{
    return '.widget_shopping_cart, .mini-cart, .woocommerce-mini-cart';
}

/**
 * Get selector for cart icon/count in header.
 */
function wooCartIconSelector(): string
{
    return '.cart-contents, .cart-count, .mini-cart-icon';
}

// =============================================================================
// WOOCOMMERCE STOREFRONT - CHECKOUT
// =============================================================================

/**
 * Get selector for checkout form.
 */
function wooCheckoutFormSelector(): string
{
    return 'form.checkout, form.woocommerce-checkout';
}

/**
 * Get selector for billing field.
 *
 * @param string $field Field name: 'first_name', 'last_name', 'company', 'country', 'address_1', 'address_2', 'city', 'state', 'postcode', 'phone', 'email'
 */
function wooBillingFieldSelector(string $field): string
{
    return "#billing_{$field}";
}

/**
 * Get selector for shipping field.
 *
 * @param string $field Field name: 'first_name', 'last_name', 'company', 'country', 'address_1', 'address_2', 'city', 'state', 'postcode'
 */
function wooShippingFieldSelector(string $field): string
{
    return "#shipping_{$field}";
}

/**
 * Get selector for "ship to different address" checkbox.
 */
function wooShipDifferentAddressSelector(): string
{
    return '#ship-to-different-address-checkbox';
}

/**
 * Get selector for order notes textarea.
 */
function wooCheckoutNotesSelector(): string
{
    return '#order_comments';
}

/**
 * Get selector for payment method.
 *
 * @param string $methodId Payment method ID: 'bacs', 'cheque', 'cod', 'paypal', 'stripe', etc.
 */
function wooPaymentMethodSelector(string $methodId): string
{
    return "#payment_method_{$methodId}";
}

/**
 * Get selector for payment methods list.
 */
function wooPaymentMethodsSelector(): string
{
    return '.wc_payment_methods, #payment ul.payment_methods';
}

/**
 * Get selector for place order button.
 */
function wooPlaceOrderSelector(): string
{
    return '#place_order, button#place_order';
}

/**
 * Get selector for checkout login form.
 */
function wooCheckoutLoginSelector(): string
{
    return '.woocommerce-form-login, form.login';
}

/**
 * Get selector for create account checkbox.
 */
function wooCreateAccountSelector(): string
{
    return '#createaccount';
}

/**
 * Get selector for account password field (during checkout).
 */
function wooAccountPasswordSelector(): string
{
    return '#account_password';
}

/**
 * Get selector for shipping methods.
 */
function wooShippingMethodsSelector(): string
{
    return '.woocommerce-shipping-methods, #shipping_method';
}

/**
 * Get selector for a specific shipping method.
 */
function wooShippingMethodSelector(string $methodId): string
{
    return "#shipping_method_0_{$methodId}, input[value='{$methodId}']";
}

/**
 * Get selector for order review section.
 */
function wooOrderReviewSelector(): string
{
    return '#order_review, .woocommerce-checkout-review-order';
}

/**
 * Get selector for checkout order total.
 */
function wooCheckoutTotalSelector(): string
{
    return '.order-total, .woocommerce-checkout-review-order-table .order-total';
}

// =============================================================================
// WOOCOMMERCE STOREFRONT - MY ACCOUNT
// =============================================================================

/**
 * Get selector for my account navigation.
 */
function wooAccountNavSelector(): string
{
    return '.woocommerce-MyAccount-navigation, nav.woocommerce-MyAccount-navigation';
}

/**
 * Get selector for my account content area.
 */
function wooAccountContentSelector(): string
{
    return '.woocommerce-MyAccount-content';
}

/**
 * Get selector for my account navigation link.
 *
 * @param string $endpoint Endpoint: 'dashboard', 'orders', 'downloads', 'edit-address', 'payment-methods', 'edit-account', 'customer-logout'
 */
function wooAccountNavLinkSelector(string $endpoint): string
{
    return ".woocommerce-MyAccount-navigation-link--{$endpoint} a";
}

/**
 * Get selector for orders table in my account.
 */
function wooAccountOrdersSelector(): string
{
    return '.woocommerce-orders-table, table.woocommerce-orders-table';
}

/**
 * Get selector for view order button.
 */
function wooViewOrderSelector(): string
{
    return '.woocommerce-orders-table__cell-order-actions a.view, a.woocommerce-button.view';
}

/**
 * Get selector for downloads table in my account.
 */
function wooAccountDownloadsSelector(): string
{
    return '.woocommerce-table--order-downloads, table.woocommerce-table--order-downloads';
}

/**
 * Get selector for address edit link.
 *
 * @param string $type 'billing' or 'shipping'
 */
function wooEditAddressSelector(string $type): string
{
    return ".woocommerce-Addresses address.{$type} a, .woocommerce-Address-title a[href*='{$type}']";
}

/**
 * Get selector for account details form.
 */
function wooAccountDetailsFormSelector(): string
{
    return 'form.woocommerce-EditAccountForm, .edit-account';
}

// =============================================================================
// WOOCOMMERCE STOREFRONT - NOTICES
// =============================================================================

/**
 * Get selector for WooCommerce notices.
 */
function wooNoticeSelector(): string
{
    return '.woocommerce-notices-wrapper, .woocommerce-message, .woocommerce-error, .woocommerce-info';
}

/**
 * Get selector for success message.
 */
function wooSuccessNoticeSelector(): string
{
    return '.woocommerce-message';
}

/**
 * Get selector for error message.
 */
function wooErrorNoticeSelector(): string
{
    return '.woocommerce-error, .woocommerce-error li';
}

/**
 * Get selector for info message.
 */
function wooInfoNoticeSelector(): string
{
    return '.woocommerce-info';
}

/**
 * Get selector for "added to cart" message.
 */
function wooAddedToCartNoticeSelector(): string
{
    return ".woocommerce-message:text-matches('added'), .woocommerce-message:has-text('cart')";
}

// =============================================================================
// WOOCOMMERCE STOREFRONT - LOGIN/REGISTER
// =============================================================================

/**
 * Get selector for WooCommerce login form.
 */
function wooLoginFormSelector(): string
{
    return 'form.woocommerce-form-login, .woocommerce-form-login';
}

/**
 * Get selector for WooCommerce register form.
 */
function wooRegisterFormSelector(): string
{
    return 'form.woocommerce-form-register, .woocommerce-form-register';
}

/**
 * Get selector for login username field.
 */
function wooLoginUsernameSelector(): string
{
    return '#username';
}

/**
 * Get selector for login password field.
 */
function wooLoginPasswordSelector(): string
{
    return '#password';
}

/**
 * Get selector for login remember me checkbox.
 */
function wooRememberMeSelector(): string
{
    return '#rememberme';
}

/**
 * Get selector for login submit button.
 */
function wooLoginSubmitSelector(): string
{
    return 'button[name="login"], .woocommerce-form-login__submit';
}

/**
 * Get selector for register email field.
 */
function wooRegisterEmailSelector(): string
{
    return '#reg_email';
}

/**
 * Get selector for register password field.
 */
function wooRegisterPasswordSelector(): string
{
    return '#reg_password';
}

/**
 * Get selector for register submit button.
 */
function wooRegisterSubmitSelector(): string
{
    return 'button[name="register"], .woocommerce-form-register__submit';
}

/**
 * Get selector for lost password link.
 */
function wooLostPasswordSelector(): string
{
    return '.woocommerce-LostPassword a, a.lost_password';
}
