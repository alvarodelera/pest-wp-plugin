# Accessibility Testing

PestWP provides built-in accessibility testing to ensure your WordPress site meets WCAG standards.

## Overview

Accessibility testing helps ensure your website is usable by everyone, including people with disabilities. PestWP integrates accessibility checks into browser tests.

## Basic Usage

### Assert Page Accessibility

```php
it('homepage is accessible', function () {
    visit('/')
        ->assertAccessible();
});
```

### Specify WCAG Level

```php
it('meets WCAG AA standards', function () {
    visit('/')
        ->assertAccessible('AA');
});

it('meets WCAG AAA standards', function () {
    visit('/')
        ->assertAccessible('AAA');
});
```

## Element Accessibility

### Check Specific Elements

```php
it('navigation is accessible', function () {
    visit('/')
        ->assertElementAccessible('nav.main-navigation');
});

it('form is accessible', function () {
    visit('/contact')
        ->assertElementAccessible('.contact-form');
});
```

## Built-in Accessibility Checks

PestWP includes 25+ accessibility checks:

### Images

```php
use function PestWP\Browser\accessibilityCheck;

it('images have alt text', function () {
    visit('/')
        ->assertPasses(accessibilityCheck('images-have-alt'));
});
```

| Check | Description |
|-------|-------------|
| `images-have-alt` | All `<img>` elements have alt text |
| `decorative-images` | Decorative images have empty alt |
| `svg-accessible` | SVGs have accessible names |

### Links

```php
it('links have accessible text', function () {
    visit('/')
        ->assertPasses(accessibilityCheck('links-have-text'));
});
```

| Check | Description |
|-------|-------------|
| `links-have-text` | Links have discernible text |
| `links-distinguishable` | Links are distinguishable from text |
| `skip-link` | Skip navigation link exists |

### Forms

```php
it('form inputs have labels', function () {
    visit('/contact')
        ->assertPasses(accessibilityCheck('form-labels'));
});
```

| Check | Description |
|-------|-------------|
| `form-labels` | Form controls have labels |
| `required-fields` | Required fields are marked |
| `error-messages` | Error messages are associated |
| `autocomplete` | Autocomplete attributes are correct |

### Color & Contrast

```php
it('has sufficient color contrast', function () {
    visit('/')
        ->assertPasses(accessibilityCheck('color-contrast'));
});
```

| Check | Description |
|-------|-------------|
| `color-contrast` | Text has 4.5:1 contrast ratio (AA) |
| `color-contrast-enhanced` | Text has 7:1 contrast ratio (AAA) |
| `color-not-sole-method` | Color isn't the only way to convey information |

### Document Structure

```php
it('has proper heading order', function () {
    visit('/')
        ->assertPasses(accessibilityCheck('heading-order'));
});
```

| Check | Description |
|-------|-------------|
| `heading-order` | Headings are in sequential order |
| `landmarks` | Page has proper landmarks |
| `lang-attribute` | HTML has lang attribute |
| `page-title` | Page has descriptive title |
| `one-h1` | Page has exactly one h1 |

### Keyboard Navigation

```php
it('is keyboard navigable', function () {
    visit('/')
        ->assertPasses(accessibilityCheck('keyboard-accessible'));
});
```

| Check | Description |
|-------|-------------|
| `keyboard-accessible` | All interactive elements are keyboard accessible |
| `focus-visible` | Focus indicators are visible |
| `tab-order` | Tab order is logical |
| `no-keyboard-trap` | No keyboard traps |

## Running Multiple Checks

```php
it('passes core accessibility checks', function () {
    visit('/')
        ->assertPasses([
            accessibilityCheck('images-have-alt'),
            accessibilityCheck('links-have-text'),
            accessibilityCheck('form-labels'),
            accessibilityCheck('color-contrast'),
            accessibilityCheck('heading-order'),
            accessibilityCheck('lang-attribute'),
        ]);
});
```

## Excluding Elements

Exclude specific elements from checks:

```php
it('is accessible (excluding ads)', function () {
    visit('/')
        ->assertAccessible('AA', exclude: [
            '.advertisement',
            '.third-party-widget',
        ]);
});
```

## Custom Accessibility Rules

### Define Custom Checks

```php
it('passes custom accessibility check', function () {
    visit('/')
        ->assertPasses(function ($page) {
            // Check all buttons have accessible names
            $buttons = $page->locator('button');
            
            for ($i = 0; $i < $buttons->count(); $i++) {
                $button = $buttons->nth($i);
                $name = $button->getAttribute('aria-label') 
                     ?? $button->textContent();
                
                if (empty(trim($name))) {
                    return false;
                }
            }
            
            return true;
        });
});
```

## Accessibility Report

Generate an accessibility report:

```php
it('generates accessibility report', function () {
    $report = visit('/')
        ->getAccessibilityReport();
    
    // Check violations
    expect($report->violations)->toBeEmpty();
    
    // Check passes
    expect($report->passes)->toContain('images-have-alt');
    
    // Check warnings (manual review needed)
    dump($report->warnings);
});
```

## Testing Common WordPress Elements

### Navigation Menus

```php
it('navigation is accessible', function () {
    visit('/')
        ->assertElementAccessible('nav.main-navigation')
        ->assertPasses([
            accessibilityCheck('landmarks'),
            accessibilityCheck('keyboard-accessible'),
        ]);
});
```

### WordPress Admin

```php
it('admin dashboard is accessible', function () {
    loginAsAdmin();
    
    visit('/wp-admin/')
        ->assertAccessible('AA');
});
```

### Plugin Settings Pages

```php
it('plugin settings are accessible', function () {
    loginAsAdmin();
    
    visit('/wp-admin/options-general.php?page=my-plugin')
        ->assertAccessible()
        ->assertPasses(accessibilityCheck('form-labels'));
});
```

### WooCommerce Pages

```php
describe('WooCommerce accessibility', function () {
    it('shop page is accessible', function () {
        visit('/shop')
            ->assertAccessible('AA');
    });
    
    it('cart is accessible', function () {
        addProductToCart();
        
        visit('/cart')
            ->assertAccessible()
            ->assertPasses(accessibilityCheck('form-labels'));
    });
    
    it('checkout is accessible', function () {
        addProductToCart();
        
        visit('/checkout')
            ->assertAccessible('AA')
            ->assertPasses([
                accessibilityCheck('form-labels'),
                accessibilityCheck('required-fields'),
            ]);
    });
});
```

## Responsive Accessibility

Test accessibility at different viewport sizes:

```php
use function PestWP\Browser\viewport;

$viewports = ['mobile', 'tablet', 'desktop'];

foreach ($viewports as $size) {
    it("is accessible on {$size}", function () use ($size) {
        visit('/')
            ->resize(viewport($size))
            ->assertAccessible('AA');
    });
}
```

## CI/CD Integration

### GitHub Actions

```yaml
name: Accessibility Tests

on: [push, pull_request]

jobs:
  accessibility:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          
      - name: Install dependencies
        run: composer install
        
      - name: Install browsers
        run: ./vendor/bin/pest-setup-browser
        
      - name: Run accessibility tests
        run: ./vendor/bin/pest tests/Browser/Accessibility
```

## Best Practices

### 1. Test Early and Often

Run accessibility tests on every commit:

```bash
./vendor/bin/pest tests/Browser/Accessibility
```

### 2. Start with WCAG AA

Begin with AA compliance, then work toward AAA:

```php
// Start here
->assertAccessible('AA');

// Progress to
->assertAccessible('AAA');
```

### 3. Test User Journeys

Test complete user flows, not just individual pages:

```php
it('checkout flow is accessible', function () {
    visit('/shop')
        ->assertAccessible()
        ->click('.add-to-cart')
        
    visit('/cart')
        ->assertAccessible()
        ->click('.checkout-button')
        
    visit('/checkout')
        ->assertAccessible()
        ->fill('#billing_first_name', 'John')
        // ... complete checkout
        ->assertAccessible();
});
```

### 4. Manual Review

Automated tests catch about 30-40% of issues. Supplement with manual testing:

```php
it('lists warnings for manual review', function () {
    $report = visit('/')->getAccessibilityReport();
    
    // Log warnings for human review
    foreach ($report->warnings as $warning) {
        echo "Review: {$warning->description}\n";
    }
});
```

### 5. Document Exceptions

If you must accept violations temporarily:

```php
it('is accessible (known issue #123)', function () {
    visit('/')
        ->assertAccessible('AA', exclude: [
            '.legacy-component', // TODO: Fix in #123
        ]);
})->todo('Fix legacy component accessibility - see issue #123');
```

## Resources

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [WebAIM Checklist](https://webaim.org/standards/wcag/checklist)
- [WordPress Accessibility Handbook](https://make.wordpress.org/accessibility/handbook/)

## Next Steps

- [Browser Testing](browser-testing.md) - Browser testing basics
- [Visual Regression](visual-regression.md) - Screenshot testing
- [WooCommerce](woocommerce.md) - E-commerce testing
