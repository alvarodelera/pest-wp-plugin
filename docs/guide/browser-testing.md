# Browser Testing

PestWP integrates with Pest's browser testing plugin (Playwright) for end-to-end testing.

## Overview

Browser testing allows you to test your WordPress site in a real browser environment, simulating user interactions like clicking, typing, and navigating.

## Setup

### Install Browser Binaries

```bash
# Install Playwright browsers
./vendor/bin/pest-setup-browser

# Or manually
npx playwright install chromium
```

### Configuration

The browser testing configuration can be customized:

```php
// tests/Pest.php
uses()->group('browser')->in('Browser');
```

## Basic Usage

### Visit Pages

```php
it('loads the homepage', function () {
    visit('/')
        ->assertSee('Welcome');
});

it('loads the admin dashboard', function () {
    visit('/wp-admin/')
        ->assertSee('Dashboard');
});
```

### Interact with Elements

```php
it('submits a contact form', function () {
    visit('/contact')
        ->fill('input[name="name"]', 'John Doe')
        ->fill('input[name="email"]', 'john@example.com')
        ->fill('textarea[name="message"]', 'Hello!')
        ->click('button[type="submit"]')
        ->assertSee('Message sent');
});
```

### Assertions

```php
visit('/about')
    ->assertSee('About Us')                    // Text is visible
    ->assertDontSee('Error')                   // Text is not visible
    ->assertTitle('About - My Site')           // Page title
    ->assertUrlContains('/about')              // URL check
    ->assertElementExists('.hero')             // Element exists
    ->assertElementVisible('.hero')            // Element is visible
    ->assertElementHidden('.modal');           // Element is hidden
```

## WordPress Admin Testing

### Login to Admin

```php
use function PestWP\createUser;

it('logs into wp-admin', function () {
    $admin = createUser('administrator', [
        'user_login' => 'testadmin',
        'user_pass' => 'password123',
    ]);
    
    visit('/wp-login.php')
        ->fill('#user_login', 'testadmin')
        ->fill('#user_pass', 'password123')
        ->click('#wp-submit')
        ->assertUrlContains('/wp-admin/')
        ->assertSee('Dashboard');
});
```

### Using WP Admin Locators

PestWP provides pre-built CSS selectors for WordPress admin elements:

```php
use function PestWP\Browser\wpAdminUrl;
use function PestWP\Browser\wpAdminSelector;

it('navigates wp-admin', function () {
    // Get admin URLs
    $dashboardUrl = wpAdminUrl('dashboard');      // /wp-admin/
    $postsUrl = wpAdminUrl('posts');              // /wp-admin/edit.php
    $newPostUrl = wpAdminUrl('post-new');         // /wp-admin/post-new.php
    
    // Get admin selectors
    $menuSelector = wpAdminSelector('menu');              // #adminmenu
    $contentSelector = wpAdminSelector('content');        // #wpbody-content
    $noticeSelector = wpAdminSelector('notice');          // .notice
    $submitButton = wpAdminSelector('publish-button');    // #publish
    
    visit($postsUrl)
        ->assertElementExists($contentSelector);
});
```

### Available Admin URLs

| Key | URL |
|-----|-----|
| `dashboard` | `/wp-admin/` |
| `posts` | `/wp-admin/edit.php` |
| `post-new` | `/wp-admin/post-new.php` |
| `pages` | `/wp-admin/edit.php?post_type=page` |
| `media` | `/wp-admin/upload.php` |
| `comments` | `/wp-admin/edit-comments.php` |
| `themes` | `/wp-admin/themes.php` |
| `plugins` | `/wp-admin/plugins.php` |
| `users` | `/wp-admin/users.php` |
| `settings` | `/wp-admin/options-general.php` |

### Available Admin Selectors

| Key | Selector |
|-----|----------|
| `menu` | `#adminmenu` |
| `content` | `#wpbody-content` |
| `title-input` | `#title` |
| `content-editor` | `#content` |
| `publish-button` | `#publish` |
| `notice` | `.notice` |
| `notice-success` | `.notice-success` |
| `notice-error` | `.notice-error` |

## Gutenberg Block Testing

PestWP provides selectors for Gutenberg block editor elements:

```php
use function PestWP\Browser\gutenbergSelector;

it('creates a post with blocks', function () {
    loginToAdmin();
    
    visit(wpAdminUrl('post-new'))
        // Wait for editor
        ->waitFor(gutenbergSelector('editor'))
        
        // Add a paragraph block
        ->click(gutenbergSelector('inserter-toggle'))
        ->fill(gutenbergSelector('inserter-search'), 'Paragraph')
        ->click(gutenbergSelector('block-paragraph'))
        ->type('Hello, World!')
        
        // Publish
        ->click(gutenbergSelector('publish-button'))
        ->click(gutenbergSelector('confirm-publish'))
        ->assertSee('Post published');
});
```

### Available Gutenberg Selectors

| Key | Description |
|-----|-------------|
| `editor` | Main editor container |
| `inserter-toggle` | Block inserter button |
| `inserter-search` | Block search input |
| `publish-button` | Publish button |
| `save-draft` | Save draft button |
| `block-paragraph` | Paragraph block |
| `block-heading` | Heading block |
| `block-image` | Image block |
| `block-list` | List block |
| ... | 70+ block selectors |

## Viewport Testing

Test responsive designs with viewport presets:

```php
use function PestWP\Browser\viewport;

it('displays mobile menu on small screens', function () {
    visit('/')
        ->resize(viewport('mobile'))
        ->assertElementVisible('.mobile-menu-toggle')
        ->assertElementHidden('.desktop-menu');
});

it('displays desktop menu on large screens', function () {
    visit('/')
        ->resize(viewport('desktop'))
        ->assertElementHidden('.mobile-menu-toggle')
        ->assertElementVisible('.desktop-menu');
});
```

### Available Viewports

| Key | Dimensions |
|-----|------------|
| `mobile` | 375 x 667 (iPhone SE) |
| `mobile-lg` | 414 x 896 (iPhone 11) |
| `tablet` | 768 x 1024 (iPad) |
| `tablet-lg` | 1024 x 1366 (iPad Pro) |
| `laptop` | 1366 x 768 |
| `desktop` | 1920 x 1080 |
| `4k` | 3840 x 2160 |

## Screenshots

### Capture Screenshots

```php
it('captures homepage screenshot', function () {
    visit('/')
        ->screenshot('homepage.png');
});

it('captures element screenshot', function () {
    visit('/')
        ->screenshotElement('.hero', 'hero.png');
});
```

### Full Page Screenshots

```php
visit('/long-page')
    ->screenshotFullPage('full-page.png');
```

## Visual Regression Testing

Compare screenshots against baselines:

```php
use function PestWP\Browser\screenshot;

it('matches visual baseline', function () {
    visit('/')
        ->assertMatchesScreenshot('homepage-baseline');
});

it('element matches baseline', function () {
    visit('/')
        ->assertElementMatchesScreenshot('.hero', 'hero-baseline');
});
```

### Threshold Configuration

```php
// Allow small differences (default: 0.1%)
visit('/')
    ->assertMatchesScreenshot('homepage', threshold: 0.5);
```

## Accessibility Testing

PestWP includes WCAG accessibility checks:

```php
use function PestWP\Browser\accessibilityCheck;

it('passes accessibility checks', function () {
    visit('/')
        ->assertAccessible();
});

it('checks specific WCAG level', function () {
    visit('/')
        ->assertAccessible('AA'); // WCAG 2.1 Level AA
});

it('checks specific element accessibility', function () {
    visit('/')
        ->assertElementAccessible('.main-content');
});
```

### Available Checks

| Check | Description |
|-------|-------------|
| `images-have-alt` | All images have alt text |
| `links-have-text` | All links have accessible text |
| `form-labels` | Form inputs have labels |
| `color-contrast` | Text has sufficient contrast |
| `heading-order` | Headings are in correct order |
| `skip-link` | Skip navigation link exists |
| `lang-attribute` | HTML has lang attribute |

## WooCommerce Testing

Test WooCommerce storefronts and checkout:

```php
use function PestWP\Browser\wooSelector;

it('adds product to cart', function () {
    visit('/shop')
        ->click(wooSelector('add-to-cart'))
        ->assertSee('added to your cart');
});

it('completes checkout', function () {
    // Add product to cart
    visit('/shop')
        ->click(wooSelector('add-to-cart'));
    
    // Go to checkout
    visit('/checkout')
        ->fill(wooSelector('billing-first-name'), 'John')
        ->fill(wooSelector('billing-last-name'), 'Doe')
        ->fill(wooSelector('billing-email'), 'john@example.com')
        ->fill(wooSelector('billing-phone'), '555-1234')
        ->fill(wooSelector('billing-address'), '123 Main St')
        ->fill(wooSelector('billing-city'), 'New York')
        ->fill(wooSelector('billing-postcode'), '10001')
        ->select(wooSelector('billing-state'), 'NY')
        ->click(wooSelector('place-order'))
        ->assertSee('Order received');
});
```

### Available WooCommerce Selectors

See [WooCommerce Testing](woocommerce.md) for the full list of 90+ selectors.

## Advanced Interactions

### Wait for Elements

```php
visit('/async-page')
    ->waitFor('.loaded-content')
    ->waitForText('Content loaded')
    ->waitUntil(fn($page) => $page->locator('.spinner')->isHidden());
```

### JavaScript Execution

```php
visit('/')
    ->evaluate('window.scrollTo(0, document.body.scrollHeight)')
    ->evaluate('localStorage.setItem("key", "value")');
```

### File Upload

```php
visit('/upload-form')
    ->attachFile('input[type="file"]', '/path/to/file.pdf')
    ->click('button[type="submit"]')
    ->assertSee('Upload successful');
```

### Keyboard and Mouse

```php
visit('/editor')
    ->click('.editor-area')
    ->type('Hello World')
    ->press('Enter')
    ->type('New line')
    ->keyboard('Control+A')  // Select all
    ->keyboard('Control+C'); // Copy
```

## Configuration Options

Set browser options via environment variables:

```bash
# Run headless (default: true)
PEST_BROWSER_HEADLESS=true

# Slow down actions for debugging
PEST_BROWSER_SLOW_MO=100

# Set default timeout
PEST_BROWSER_TIMEOUT=30000

# Screenshot directory
PEST_SCREENSHOT_DIR=tests/__screenshots__
```

## Running Browser Tests

```bash
# Run all browser tests
./vendor/bin/pest tests/Browser

# Run with headed browser (see the browser)
PEST_BROWSER_HEADLESS=false ./vendor/bin/pest tests/Browser

# Run specific test
./vendor/bin/pest tests/Browser/AdminTest.php
```

## Next Steps

- [Visual Regression](visual-regression.md) - Screenshot comparison
- [Accessibility Testing](accessibility-testing.md) - WCAG compliance
- [WooCommerce](woocommerce.md) - E-commerce testing
- [Gutenberg](gutenberg.md) - Block editor testing
