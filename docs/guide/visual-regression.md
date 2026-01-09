# Visual Regression Testing

PestWP supports visual regression testing by comparing screenshots against baselines.

## Overview

Visual regression testing captures screenshots of your pages and compares them against stored baseline images. If the visual appearance changes, the test fails.

## Setup

### Configure Screenshot Directory

```bash
# Set environment variable
export PEST_SCREENSHOT_DIR=tests/__screenshots__
```

Or in `phpunit.xml`:

```xml
<php>
    <env name="PEST_SCREENSHOT_DIR" value="tests/__screenshots__"/>
</php>
```

## Basic Usage

### Capture Screenshots

```php
it('captures homepage screenshot', function () {
    visit('/')
        ->screenshot('homepage.png');
});
```

### Compare Against Baseline

```php
it('homepage matches baseline', function () {
    visit('/')
        ->assertMatchesScreenshot('homepage');
});
```

On first run, a baseline is created. On subsequent runs, the current screenshot is compared against the baseline.

## Screenshot Types

### Full Page Screenshots

```php
it('captures full page', function () {
    visit('/long-page')
        ->screenshotFullPage('full-page.png');
});
```

### Element Screenshots

```php
it('captures specific element', function () {
    visit('/')
        ->screenshotElement('.hero', 'hero.png');
});
```

### Viewport Screenshots

```php
use function PestWP\Browser\viewport;

it('captures mobile view', function () {
    visit('/')
        ->resize(viewport('mobile'))
        ->screenshot('homepage-mobile.png');
});
```

## Visual Comparison

### Default Comparison

```php
it('matches baseline', function () {
    visit('/about')
        ->assertMatchesScreenshot('about-page');
});
```

### With Threshold

Allow small differences (anti-aliasing, font rendering):

```php
it('matches with tolerance', function () {
    visit('/about')
        ->assertMatchesScreenshot('about-page', threshold: 0.5); // 0.5% difference allowed
});
```

### Element Comparison

```php
it('header matches baseline', function () {
    visit('/')
        ->assertElementMatchesScreenshot('.site-header', 'header');
});
```

## Responsive Testing

Test across multiple viewports:

```php
use function PestWP\Browser\viewport;

$viewports = [
    'mobile' => viewport('mobile'),
    'tablet' => viewport('tablet'),
    'desktop' => viewport('desktop'),
];

foreach ($viewports as $name => $size) {
    it("homepage matches on {$name}", function () use ($size, $name) {
        visit('/')
            ->resize($size)
            ->assertMatchesScreenshot("homepage-{$name}");
    });
}
```

## Masking Dynamic Content

Hide elements that change between runs:

```php
it('matches with masked elements', function () {
    visit('/')
        ->mask('.timestamp')      // Hide timestamp
        ->mask('.random-ad')      // Hide ads
        ->mask('.user-avatar')    // Hide avatars
        ->assertMatchesScreenshot('homepage');
});
```

## File Organization

```
tests/
├── __screenshots__/
│   ├── baselines/           # Stored baseline images
│   │   ├── homepage.png
│   │   ├── about-page.png
│   │   └── homepage-mobile.png
│   ├── current/             # Current test screenshots
│   │   └── ...
│   └── diffs/               # Difference images (failures)
│       └── ...
└── Browser/
    └── VisualTest.php
```

## Updating Baselines

When visual changes are intentional:

```bash
# Update all baselines
./vendor/bin/pest --update-screenshots

# Update specific test
./vendor/bin/pest tests/Browser/VisualTest.php --update-screenshots
```

## Viewing Differences

When a test fails, a diff image is generated showing:
- Baseline image
- Current image  
- Highlighted differences

```php
it('shows diff on failure', function () {
    visit('/changed-page')
        ->assertMatchesScreenshot('page');
    
    // Failure creates:
    // tests/__screenshots__/diffs/page.png
});
```

## Cross-Browser Testing

Test in different browsers:

```php
it('looks correct in chromium', function () {
    visit('/', browser: 'chromium')
        ->assertMatchesScreenshot('homepage-chromium');
});

it('looks correct in firefox', function () {
    visit('/', browser: 'firefox')
        ->assertMatchesScreenshot('homepage-firefox');
});

it('looks correct in webkit', function () {
    visit('/', browser: 'webkit')
        ->assertMatchesScreenshot('homepage-webkit');
});
```

## Common Use Cases

### Testing Theme Changes

```php
describe('theme visual tests', function () {
    it('header matches', function () {
        visit('/')
            ->assertElementMatchesScreenshot('.site-header', 'header');
    });
    
    it('footer matches', function () {
        visit('/')
            ->assertElementMatchesScreenshot('.site-footer', 'footer');
    });
    
    it('sidebar matches', function () {
        visit('/blog')
            ->assertElementMatchesScreenshot('.sidebar', 'sidebar');
    });
});
```

### Testing Plugin Output

```php
it('contact form renders correctly', function () {
    visit('/contact')
        ->assertElementMatchesScreenshot('.contact-form', 'contact-form');
});

it('gallery shortcode renders correctly', function () {
    $post = createPost([
        'post_content' => '[gallery ids="1,2,3"]',
    ]);
    
    visit("/?p={$post->ID}")
        ->assertElementMatchesScreenshot('.gallery', 'gallery');
});
```

### Testing Admin Pages

```php
it('plugin settings page renders correctly', function () {
    loginAsAdmin();
    
    visit('/wp-admin/options-general.php?page=my-plugin')
        ->assertMatchesScreenshot('settings-page');
});
```

## Best Practices

### 1. Consistent Environment

Ensure consistent rendering:
- Same browser version
- Same fonts installed
- Same screen DPI
- Disable animations

```php
beforeEach(function () {
    // Disable animations for consistent screenshots
    visit('/')->evaluate(<<<JS
        document.querySelectorAll('*').forEach(el => {
            el.style.animation = 'none';
            el.style.transition = 'none';
        });
    JS);
});
```

### 2. Stable Selectors

Use stable selectors for element screenshots:

```php
// Good: Semantic class
->assertElementMatchesScreenshot('.product-card', 'product');

// Avoid: Dynamic or positional
->assertElementMatchesScreenshot('.col:nth-child(3)', 'product');
```

### 3. Appropriate Thresholds

Set thresholds based on element type:

```php
// Text-heavy: lower threshold
->assertMatchesScreenshot('article', threshold: 0.1);

// Images: slightly higher
->assertMatchesScreenshot('gallery', threshold: 0.5);
```

### 4. Organize by Feature

```
__screenshots__/
├── baselines/
│   ├── homepage/
│   │   ├── desktop.png
│   │   ├── tablet.png
│   │   └── mobile.png
│   ├── blog/
│   │   └── ...
│   └── shop/
│       └── ...
```

### 5. CI Considerations

```yaml
# .github/workflows/visual.yml
- name: Run Visual Tests
  run: ./vendor/bin/pest tests/Browser/Visual

- name: Upload Diffs on Failure
  if: failure()
  uses: actions/upload-artifact@v3
  with:
    name: visual-diffs
    path: tests/__screenshots__/diffs/
```

## Troubleshooting

### Inconsistent Screenshots

**Problem**: Screenshots differ slightly between runs.

**Solutions**:
- Disable animations
- Use consistent fonts
- Mask dynamic content
- Increase threshold slightly

### Large Image Files

**Problem**: Baseline images are too large.

**Solutions**:
- Capture smaller viewports
- Screenshot specific elements
- Use PNG compression
- Limit full-page screenshots

### Slow Tests

**Problem**: Visual tests are slow.

**Solutions**:
- Run visual tests separately
- Use parallel execution
- Reduce number of viewports
- Cache browser instance

## Next Steps

- [Browser Testing](browser-testing.md) - Browser testing basics
- [Accessibility Testing](accessibility-testing.md) - WCAG compliance
- [Snapshots](snapshots.md) - Snapshot testing
