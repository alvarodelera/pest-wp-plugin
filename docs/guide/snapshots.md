# Snapshot Testing

PestWP supports snapshot testing for comparing outputs against stored baselines.

## Overview

Snapshot testing captures the output of code and compares it against a previously stored "snapshot". If the output changes, the test fails, alerting you to potential regressions.

## Basic Usage

### toMatchSnapshot()

```php
it('matches snapshot', function () {
    $html = render_component('button', ['label' => 'Click Me']);
    
    expect($html)->toMatchSnapshot();
});
```

On first run, a snapshot file is created. On subsequent runs, the output is compared against the stored snapshot.

### Named Snapshots

```php
it('matches named snapshot', function () {
    $html = render_component('button', ['label' => 'Submit']);
    
    expect($html)->toMatchSnapshot('submit-button');
});
```

## Snapshot Types

### Plain Text Snapshots

```php
it('renders text correctly', function () {
    $text = generate_excerpt($post);
    
    expect($text)->toMatchSnapshot();
});
```

### JSON Snapshots

```php
it('returns correct API response', function () {
    $data = [
        'id' => 1,
        'title' => 'Hello',
        'meta' => ['views' => 100],
    ];
    
    expect($data)->toMatchJsonSnapshot();
});
```

JSON snapshots format the output nicely and ignore key order differences.

### HTML Snapshots

```php
it('renders HTML correctly', function () {
    $html = render_template('header.php');
    
    expect($html)->toMatchHtmlSnapshot();
});
```

HTML snapshots normalize whitespace and formatting for consistent comparisons.

## Snapshot Storage

Snapshots are stored in `tests/__snapshots__/` by default:

```
tests/
├── __snapshots__/
│   └── ComponentTest/
│       ├── it_renders_button__1.snap
│       ├── it_renders_header__1.snap
│       └── submit-button.snap
└── Unit/
    └── ComponentTest.php
```

### Custom Path

```php
use function PestWP\Functions\snapshots;

// Set custom path
snapshots('/custom/path/to/snapshots');

it('uses custom path', function () {
    expect($output)->toMatchSnapshot();
});
```

## Updating Snapshots

When output changes intentionally, update the snapshots:

```bash
# Update all snapshots
./vendor/bin/pest --update-snapshots

# Update specific test's snapshots
./vendor/bin/pest tests/Unit/ComponentTest.php --update-snapshots
```

## Use Cases

### Testing HTML Output

```php
describe('template rendering', function () {
    it('renders post card', function () {
        $post = createPost([
            'post_title' => 'Test Post',
            'post_content' => 'Content here',
        ]);
        
        $html = render_post_card($post);
        
        expect($html)->toMatchHtmlSnapshot();
    });
    
    it('renders navigation menu', function () {
        $html = render_navigation('primary');
        
        expect($html)->toMatchHtmlSnapshot();
    });
});
```

### Testing API Responses

```php
describe('REST API', function () {
    it('returns post data', function () {
        $post = createPost(['post_title' => 'API Test']);
        
        $response = rest()->get("/wp/v2/posts/{$post->ID}");
        
        // Exclude dynamic fields
        $data = $response->data();
        unset($data['date'], $data['modified'], $data['id']);
        
        expect($data)->toMatchJsonSnapshot();
    });
});
```

### Testing Shortcode Output

```php
it('renders gallery shortcode', function () {
    $attachments = [];
    for ($i = 1; $i <= 3; $i++) {
        $attachments[] = createAttachment();
    }
    
    $ids = implode(',', $attachments);
    $output = do_shortcode("[gallery ids=\"{$ids}\"]");
    
    expect($output)->toMatchHtmlSnapshot();
});
```

### Testing Email Content

```php
it('generates welcome email', function () {
    $user = createUser(['display_name' => 'John Doe']);
    
    $content = generate_welcome_email($user);
    
    expect($content)->toMatchSnapshot();
});
```

## Handling Dynamic Content

### Exclude Dynamic Values

```php
it('matches snapshot with dynamic content', function () {
    $data = get_api_response();
    
    // Remove dynamic fields before snapshot
    $data = array_diff_key($data, array_flip([
        'timestamp',
        'request_id',
        'nonce',
    ]));
    
    expect($data)->toMatchJsonSnapshot();
});
```

### Replace Dynamic Values

```php
it('normalizes dynamic content', function () {
    $html = render_page();
    
    // Replace dynamic values with placeholders
    $html = preg_replace('/nonce=[a-f0-9]+/', 'nonce=XXX', $html);
    $html = preg_replace('/\d{4}-\d{2}-\d{2}/', 'YYYY-MM-DD', $html);
    
    expect($html)->toMatchHtmlSnapshot();
});
```

### Freeze Time for Consistent Dates

```php
use function PestWP\Functions\freezeTime;

it('renders date consistently', function () {
    freezeTime('2024-01-15 10:00:00');
    
    $html = render_post_date();
    
    expect($html)->toMatchSnapshot();
});
```

## Custom Serializers

### For Objects

```php
it('serializes custom objects', function () {
    $order = new Order([
        'id' => 123,
        'items' => [new Product('Widget')],
    ]);
    
    // Convert to array for snapshot
    $data = $order->toArray();
    
    expect($data)->toMatchJsonSnapshot();
});
```

### For Complex HTML

```php
it('normalizes complex HTML', function () {
    $html = get_page_content();
    
    // Remove IDs that change
    $html = preg_replace('/id="[^"]*"/', '', $html);
    
    // Normalize whitespace
    $html = preg_replace('/\s+/', ' ', $html);
    
    expect(trim($html))->toMatchSnapshot();
});
```

## Best Practices

### 1. Review Snapshot Changes

Always review snapshot changes in code review:

```bash
git diff tests/__snapshots__/
```

### 2. Keep Snapshots Small

Test specific components rather than entire pages:

```php
// Good: Focused snapshot
it('renders button', function () {
    expect(render_button())->toMatchHtmlSnapshot();
});

// Avoid: Large snapshots
it('renders entire page', function () {
    expect(render_page())->toMatchHtmlSnapshot(); // Too large!
});
```

### 3. Use Descriptive Names

```php
expect($html)->toMatchSnapshot('primary-navigation-mobile');
expect($html)->toMatchSnapshot('footer-with-widgets');
```

### 4. Commit Snapshots

Always commit snapshot files:

```gitignore
# .gitignore
# Do NOT ignore snapshots
# tests/__snapshots__/  <- Don't add this!
```

### 5. Update Intentionally

Only update snapshots when changes are intentional:

```bash
# Review what will change first
./vendor/bin/pest --dry-run

# Then update
./vendor/bin/pest --update-snapshots
```

## Debugging Failed Snapshots

### View Differences

```php
it('shows diff on failure', function () {
    $html = '<div class="new-class">Content</div>';
    
    expect($html)->toMatchSnapshot();
    // Failure shows:
    // - <div class="old-class">Content</div>
    // + <div class="new-class">Content</div>
});
```

### Regenerate Specific Snapshot

```bash
# Delete the snapshot file and re-run
rm tests/__snapshots__/ComponentTest/it_renders_button__1.snap
./vendor/bin/pest tests/Unit/ComponentTest.php
```

## CI/CD Considerations

### Fail on Missing Snapshots

```bash
# In CI, fail if snapshots need to be created
./vendor/bin/pest --ci
```

### Consistent Environment

Ensure CI environment matches development for consistent snapshots:

- Same PHP version
- Same WordPress version
- Same timezone setting

## Next Steps

- [Visual Regression](visual-regression.md) - Screenshot snapshots
- [Mocking](mocking.md) - Mock dynamic content
- [Fixtures](fixtures.md) - Consistent test data
