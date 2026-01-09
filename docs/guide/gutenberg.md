# Gutenberg Block Testing

PestWP provides comprehensive browser testing support for the WordPress Block Editor (Gutenberg) with **70+ CSS selectors** covering all core blocks, editor UI, and site editor elements. These selectors work with WordPress 6.5+ and the latest block editor.

## Overview

Gutenberg testing involves:
- **Block manipulation**: Creating, editing, and transforming blocks
- **Editor UI**: Toolbar, sidebar, inserter, and settings
- **Content verification**: Validating block output on frontend
- **Site Editor**: Testing Full Site Editing (FSE) templates

## Quick Start

```php
use function PestWP\Functions\paragraphBlockSelector;
use function PestWP\Functions\headingBlockSelector;
use function PestWP\Functions\blockToolbarSelector;

it('creates content with blocks', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php')
            ->waitFor('.block-editor')
            ->type(paragraphBlockSelector(), 'Hello World')
            ->assertVisible(blockToolbarSelector());
    });
});
```

---

## Text Blocks

| Function | Description | Selector |
|----------|-------------|----------|
| `paragraphBlockSelector($index)` | Paragraph block | `[data-type='core/paragraph']` |
| `headingBlockSelector($level)` | Heading block (H1-H6) | `[data-type='core/heading']` |
| `listBlockSelector($type)` | List block (ordered/unordered) | `[data-type='core/list']` |
| `quoteBlockSelector()` | Quote block | `[data-type='core/quote']` |
| `pullquoteBlockSelector()` | Pullquote block | `[data-type='core/pullquote']` |
| `codeBlockSelector()` | Code block | `[data-type='core/code']` |
| `preformattedBlockSelector()` | Preformatted block | `[data-type='core/preformatted']` |
| `verseBlockSelector()` | Verse block | `[data-type='core/verse']` |

### Example: Working with Text Blocks

```php
use function PestWP\Functions\paragraphBlockSelector;
use function PestWP\Functions\headingBlockSelector;
use function PestWP\Functions\listBlockSelector;

it('creates a blog post with text blocks', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php')
            ->waitFor('.block-editor');
        
        // Add title
        $browser->type('.editor-post-title__input', 'My Blog Post');
        
        // Add heading
        $browser->click('[aria-label="Add block"]')
            ->type('[placeholder="Search"]', 'Heading')
            ->click('[aria-label="Heading"]')
            ->type(headingBlockSelector(), 'Introduction');
        
        // Add paragraph
        $browser->press('Enter')
            ->type(paragraphBlockSelector(), 'Welcome to my blog post.');
        
        // Add list
        $browser->click('[aria-label="Add block"]')
            ->click('[aria-label="List"]')
            ->type(listBlockSelector(), 'First item');
        
        $browser->click('.editor-post-publish-button')
            ->waitForText('Published');
    });
});
```

### Heading Levels

```php
use function PestWP\Functions\headingBlockSelector;

it('creates headings at different levels', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php?post_type=page')
            ->waitFor('.block-editor');
        
        // H1 heading
        $browser->addBlock('Heading')
            ->type(headingBlockSelector(1), 'Main Title')
            ->assertVisible(headingBlockSelector(1));
        
        // Change to H2
        $browser->click(headingBlockSelector(1))
            ->click('[aria-label="Change level"]')
            ->click('button:has-text("H2")')
            ->assertVisible(headingBlockSelector(2));
    });
});
```

---

## Media Blocks

| Function | Description | Selector |
|----------|-------------|----------|
| `imageBlockSelector()` | Image block | `[data-type='core/image']` |
| `galleryBlockSelector()` | Gallery block | `[data-type='core/gallery']` |
| `audioBlockSelector()` | Audio block | `[data-type='core/audio']` |
| `videoBlockSelector()` | Video block | `[data-type='core/video']` |
| `coverBlockSelector()` | Cover block | `[data-type='core/cover']` |
| `mediaTextBlockSelector()` | Media & Text block | `[data-type='core/media-text']` |
| `fileBlockSelector()` | File block | `[data-type='core/file']` |

### Example: Media Blocks

```php
use function PestWP\Functions\imageBlockSelector;
use function PestWP\Functions\galleryBlockSelector;
use function PestWP\Functions\coverBlockSelector;

it('adds media blocks to post', function () {
    $imageId = createAttachment('test-image.jpg');
    
    $this->browse(function ($browser) use ($imageId) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php')
            ->waitFor('.block-editor');
        
        // Add image block
        $browser->addBlock('Image')
            ->click('.block-editor-media-placeholder__upload-button')
            ->waitFor('.media-modal')
            ->click(".attachment[data-id='{$imageId}']")
            ->click('.media-button-select')
            ->waitFor(imageBlockSelector() . ' img');
        
        // Add cover block
        $browser->addBlock('Cover')
            ->click('.block-editor-media-placeholder__media-library-button')
            ->waitFor('.media-modal')
            ->click(".attachment[data-id='{$imageId}']")
            ->click('.media-button-select')
            ->type(coverBlockSelector() . ' p', 'Featured Content');
    });
});
```

---

## Design & Layout Blocks

| Function | Description | Selector |
|----------|-------------|----------|
| `buttonsBlockSelector()` | Buttons container | `[data-type='core/buttons']` |
| `buttonBlockSelector()` | Single button | `[data-type='core/button']` |
| `columnsBlockSelector()` | Columns layout | `[data-type='core/columns']` |
| `columnBlockSelector($index)` | Single column | `[data-type='core/column']` |
| `groupBlockSelector()` | Group block | `[data-type='core/group']` |
| `rowBlockSelector()` | Row layout (flex) | `[data-type='core/group'].is-layout-flex` |
| `stackBlockSelector()` | Stack layout (flow) | `[data-type='core/group'].is-layout-flow` |
| `separatorBlockSelector()` | Separator/divider | `[data-type='core/separator']` |
| `spacerBlockSelector()` | Spacer block | `[data-type='core/spacer']` |
| `detailsBlockSelector()` | Details/accordion | `[data-type='core/details']` |

### Example: Layout Blocks

```php
use function PestWP\Functions\columnsBlockSelector;
use function PestWP\Functions\columnBlockSelector;
use function PestWP\Functions\buttonBlockSelector;

it('creates a two-column layout with CTA', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php?post_type=page')
            ->waitFor('.block-editor');
        
        // Add columns
        $browser->addBlock('Columns')
            ->click('.block-editor-block-variation-picker__variation:first-child'); // 50/50
        
        // Add content to first column
        $browser->click(columnBlockSelector(0))
            ->type('p', 'Left column content');
        
        // Add button to second column
        $browser->click(columnBlockSelector(1))
            ->addBlock('Buttons')
            ->type(buttonBlockSelector() . ' [contenteditable]', 'Learn More')
            ->assertVisible(buttonBlockSelector());
        
        // Verify structure
        $browser->assertVisible(columnsBlockSelector())
            ->assertElementsCount(columnBlockSelector(), 2);
    });
});
```

---

## Widget Blocks

| Function | Description | Selector |
|----------|-------------|----------|
| `shortcodeBlockSelector()` | Shortcode block | `[data-type='core/shortcode']` |
| `archivesBlockSelector()` | Archives widget | `[data-type='core/archives']` |
| `calendarBlockSelector()` | Calendar widget | `[data-type='core/calendar']` |
| `categoriesBlockSelector()` | Categories widget | `[data-type='core/categories']` |
| `htmlBlockSelector()` | Custom HTML block | `[data-type='core/html']` |
| `latestCommentsBlockSelector()` | Latest Comments | `[data-type='core/latest-comments']` |
| `latestPostsBlockSelector()` | Latest Posts | `[data-type='core/latest-posts']` |
| `pageListBlockSelector()` | Page List | `[data-type='core/page-list']` |
| `rssBlockSelector()` | RSS feed | `[data-type='core/rss']` |
| `searchBlockSelector()` | Search form | `[data-type='core/search']` |
| `socialLinksBlockSelector()` | Social links | `[data-type='core/social-links']` |
| `tagCloudBlockSelector()` | Tag cloud | `[data-type='core/tag-cloud']` |

### Example: Dynamic Widget Blocks

```php
use function PestWP\Functions\latestPostsBlockSelector;
use function PestWP\Functions\searchBlockSelector;

it('adds widget blocks to sidebar', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/widgets.php')
            ->waitFor('.block-editor');
        
        // Add Latest Posts
        $browser->addBlock('Latest Posts')
            ->waitFor(latestPostsBlockSelector())
            ->assertVisible(latestPostsBlockSelector());
        
        // Configure via sidebar
        $browser->click(latestPostsBlockSelector())
            ->click('.edit-post-header__settings button[aria-label="Settings"]')
            ->type('input[aria-label="Number of items"]', '5')
            ->check('input[aria-label="Display post date"]');
        
        // Add Search
        $browser->addBlock('Search')
            ->assertVisible(searchBlockSelector());
        
        $browser->click('.edit-widgets-header__actions button')
            ->waitForText('Widgets saved');
    });
});
```

---

## Theme & Site Editor Blocks

| Function | Description | Selector |
|----------|-------------|----------|
| `siteTitleBlockSelector()` | Site title | `[data-type='core/site-title']` |
| `siteTaglineBlockSelector()` | Site tagline | `[data-type='core/site-tagline']` |
| `siteLogoBlockSelector()` | Site logo | `[data-type='core/site-logo']` |
| `navigationBlockSelector()` | Navigation menu | `[data-type='core/navigation']` |
| `queryBlockSelector()` | Query Loop | `[data-type='core/query']` |
| `postTemplateBlockSelector()` | Post Template | `[data-type='core/post-template']` |
| `postTitleBlockSelector()` | Post Title | `[data-type='core/post-title']` |
| `postContentBlockSelector()` | Post Content | `[data-type='core/post-content']` |
| `postExcerptBlockSelector()` | Post Excerpt | `[data-type='core/post-excerpt']` |
| `postFeaturedImageBlockSelector()` | Featured Image | `[data-type='core/post-featured-image']` |
| `postDateBlockSelector()` | Post Date | `[data-type='core/post-date']` |
| `postAuthorBlockSelector()` | Post Author | `[data-type='core/post-author']` |
| `postCategoriesBlockSelector()` | Post Categories | `[data-type='core/post-terms']` |
| `postTagsBlockSelector()` | Post Tags | `[data-type='core/post-terms']` |
| `commentsBlockSelector()` | Comments | `[data-type='core/comments']` |
| `commentFormBlockSelector()` | Comment Form | `[data-type='core/post-comments-form']` |
| `templatePartBlockSelector($slug)` | Template Part | `[data-type='core/template-part']` |

### Example: Site Editor

```php
use function PestWP\Functions\siteTitleBlockSelector;
use function PestWP\Functions\navigationBlockSelector;
use function PestWP\Functions\templatePartBlockSelector;

it('edits site header in FSE', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/site-editor.php?postType=wp_template_part&postId=theme//header')
            ->waitFor('.block-editor');
        
        // Edit site title
        $browser->click(siteTitleBlockSelector())
            ->assertVisible('.block-editor-block-inspector')
            ->check('[aria-label="Make title link to home"]');
        
        // Edit navigation
        $browser->click(navigationBlockSelector())
            ->click('.wp-block-navigation__responsive-container-open')
            ->assertVisible('.wp-block-navigation-link');
        
        // Verify header template part
        $browser->assertVisible(templatePartBlockSelector('header'));
        
        $browser->click('.edit-site-save-button')
            ->waitForText('Saved');
    });
});
```

### Example: Query Loop

```php
use function PestWP\Functions\queryBlockSelector;
use function PestWP\Functions\postTemplateBlockSelector;
use function PestWP\Functions\postTitleBlockSelector;
use function PestWP\Functions\postExcerptBlockSelector;

it('creates a custom blog layout', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php?post_type=page')
            ->waitFor('.block-editor');
        
        // Add Query Loop
        $browser->addBlock('Query Loop')
            ->click('.block-editor-block-variation-picker__variation:first-child')
            ->waitFor(queryBlockSelector());
        
        // Verify template blocks
        $browser->assertVisible(postTemplateBlockSelector())
            ->assertVisible(postTitleBlockSelector())
            ->assertVisible(postExcerptBlockSelector());
        
        // Configure query
        $browser->click(queryBlockSelector())
            ->click('.edit-post-header__settings button[aria-label="Settings"]')
            ->select('[aria-label="Post Type"]', 'post')
            ->type('[aria-label="Items Per Page"]', '6');
    });
});
```

---

## Table Block

| Function | Description | Selector |
|----------|-------------|----------|
| `tableBlockSelector()` | Table block | `[data-type='core/table']` |
| `tableHeaderSelector()` | Table header | `[data-type='core/table'] thead` |
| `tableBodySelector()` | Table body | `[data-type='core/table'] tbody` |
| `tableFooterSelector()` | Table footer | `[data-type='core/table'] tfoot` |

### Example: Table Block

```php
use function PestWP\Functions\tableBlockSelector;
use function PestWP\Functions\tableHeaderSelector;
use function PestWP\Functions\tableBodySelector;

it('creates a pricing table', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php?post_type=page')
            ->waitFor('.block-editor');
        
        $browser->addBlock('Table')
            ->type('[aria-label="Column count"]', '3')
            ->type('[aria-label="Row count"]', '4')
            ->click('.blocks-table__placeholder-button')
            ->waitFor(tableBlockSelector());
        
        // Enable header
        $browser->click('.block-editor-block-inspector [aria-label="Header section"]')
            ->waitFor(tableHeaderSelector())
            ->type(tableHeaderSelector() . ' th:first-child', 'Plan')
            ->type(tableHeaderSelector() . ' th:nth-child(2)', 'Features')
            ->type(tableHeaderSelector() . ' th:nth-child(3)', 'Price');
        
        // Fill body
        $browser->type(tableBodySelector() . ' tr:first-child td:first-child', 'Basic')
            ->type(tableBodySelector() . ' tr:first-child td:last-child', '$9/mo');
    });
});
```

---

## Embed Blocks

| Function | Description | Selector |
|----------|-------------|----------|
| `embedBlockSelector($provider)` | Embed block | `[data-type='core/embed']` |
| `youtubeBlockSelector()` | YouTube embed | `[data-type='core/embed'][data-provider-name='youtube']` |
| `vimeoBlockSelector()` | Vimeo embed | `[data-type='core/embed'][data-provider-name='vimeo']` |
| `twitterBlockSelector()` | Twitter/X embed | `[data-type='core/embed'][data-provider-name='twitter']` |

### Example: Embed Content

```php
use function PestWP\Functions\youtubeBlockSelector;
use function PestWP\Functions\embedBlockSelector;

it('embeds a YouTube video', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php')
            ->waitFor('.block-editor');
        
        $browser->addBlock('YouTube')
            ->type('.block-editor-media-placeholder__url-input-container input', 
                   'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->click('.block-editor-media-placeholder__url-input-container button')
            ->waitFor(youtubeBlockSelector())
            ->assertVisible(embedBlockSelector('youtube'));
    });
});
```

---

## Editor UI Helpers

| Function | Description | Selector |
|----------|-------------|----------|
| `blockToolbarSelector()` | Block toolbar | `.block-editor-block-toolbar` |
| `blockSettingsSidebarSelector()` | Settings sidebar | `.block-editor-block-inspector` |
| `blockMoverSelector()` | Block mover arrows | `.block-editor-block-mover` |
| `blockOptionsMenuSelector()` | Options menu (⋮) | `[aria-label='Options']` |
| `blockTransformSelector()` | Transform option | `:has-text('Transform to')` |
| `duplicateBlockSelector()` | Duplicate option | `:has-text('Duplicate')` |
| `removeBlockSelector()` | Delete option | `:has-text('Delete')` |
| `blockAlignmentSelector()` | Alignment control | `[aria-label*='Align']` |
| `richTextToolbarSelector()` | Formatting toolbar | `.block-editor-format-toolbar` |
| `boldButtonSelector()` | Bold button | `[aria-label='Bold']` |
| `italicButtonSelector()` | Italic button | `[aria-label='Italic']` |
| `linkButtonSelector()` | Link button | `[aria-label='Link']` |
| `linkInputSelector()` | Link URL input | `.block-editor-link-control__search-input input` |
| `documentSettingsToggleSelector()` | Settings toggle | `button[aria-label='Settings']` |
| `visibilityControlSelector()` | Visibility setting | `.editor-post-visibility__toggle` |
| `scheduleControlSelector()` | Schedule setting | `.editor-post-schedule__toggle` |
| `featuredImageControlSelector()` | Featured image | `.editor-post-featured-image` |
| `excerptPanelSelector()` | Excerpt panel | `.editor-post-excerpt` |
| `discussionPanelSelector()` | Discussion panel | `.editor-post-discussion-panel` |

### Example: Block Operations

```php
use function PestWP\Functions\blockToolbarSelector;
use function PestWP\Functions\blockOptionsMenuSelector;
use function PestWP\Functions\duplicateBlockSelector;
use function PestWP\Functions\removeBlockSelector;
use function PestWP\Functions\blockMoverSelector;

it('manipulates blocks with toolbar', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php')
            ->waitFor('.block-editor');
        
        // Create paragraph
        $browser->type(paragraphBlockSelector(), 'Original content')
            ->click(paragraphBlockSelector());
        
        // Verify toolbar
        $browser->assertVisible(blockToolbarSelector())
            ->assertVisible(blockMoverSelector());
        
        // Duplicate block
        $browser->click(blockOptionsMenuSelector())
            ->click(duplicateBlockSelector())
            ->assertElementsCount(paragraphBlockSelector(), 2);
        
        // Delete one
        $browser->click(paragraphBlockSelector() . ':last-child')
            ->click(blockOptionsMenuSelector())
            ->click(removeBlockSelector())
            ->assertElementsCount(paragraphBlockSelector(), 1);
    });
});
```

### Example: Text Formatting

```php
use function PestWP\Functions\boldButtonSelector;
use function PestWP\Functions\italicButtonSelector;
use function PestWP\Functions\linkButtonSelector;
use function PestWP\Functions\linkInputSelector;

it('applies formatting to text', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php')
            ->waitFor('.block-editor');
        
        // Type and select text
        $browser->type(paragraphBlockSelector(), 'Important text here')
            ->keys(paragraphBlockSelector(), ['{ctrl}', 'a']); // Select all
        
        // Apply bold
        $browser->click(boldButtonSelector())
            ->assertAttribute(paragraphBlockSelector() . ' strong', 'textContent', 'Important text here');
        
        // Add link
        $browser->click(linkButtonSelector())
            ->waitFor(linkInputSelector())
            ->type(linkInputSelector(), 'https://example.com')
            ->press('Enter')
            ->assertVisible(paragraphBlockSelector() . ' a[href="https://example.com"]');
    });
});
```

### Example: Document Settings

```php
use function PestWP\Functions\documentSettingsToggleSelector;
use function PestWP\Functions\visibilityControlSelector;
use function PestWP\Functions\featuredImageControlSelector;
use function PestWP\Functions\excerptPanelSelector;

it('configures post settings', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php')
            ->waitFor('.block-editor');
        
        // Open settings sidebar
        $browser->click(documentSettingsToggleSelector())
            ->waitFor('.edit-post-sidebar');
        
        // Set visibility to private
        $browser->click(visibilityControlSelector())
            ->click('input[value="private"]')
            ->assertSee('Private');
        
        // Set featured image
        $browser->click(featuredImageControlSelector())
            ->waitFor('.media-modal')
            ->click('.attachment:first-child')
            ->click('.media-button-select')
            ->assertVisible('.editor-post-featured-image__preview');
        
        // Add excerpt
        $browser->click(excerptPanelSelector() . ' button')
            ->type(excerptPanelSelector() . ' textarea', 'This is the post excerpt.');
    });
});
```

---

## Complete Post Creation Example

```php
use function PestWP\Functions\{
    paragraphBlockSelector,
    headingBlockSelector,
    imageBlockSelector,
    columnsBlockSelector,
    columnBlockSelector,
    buttonBlockSelector,
    blockToolbarSelector,
    documentSettingsToggleSelector,
    featuredImageControlSelector
};

it('creates a complete landing page', function () {
    $imageId = createAttachment('hero-image.jpg');
    
    $this->browse(function ($browser) use ($imageId) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php?post_type=page')
            ->waitFor('.block-editor');
        
        // 1. Add title
        $browser->type('.editor-post-title__input', 'Welcome to Our Site');
        
        // 2. Add hero heading
        $browser->addBlock('Heading')
            ->type(headingBlockSelector(), 'Build Something Amazing')
            ->click(headingBlockSelector())
            ->click('[aria-label="Align text center"]');
        
        // 3. Add intro paragraph
        $browser->press('Enter')
            ->type(paragraphBlockSelector(), 'We help businesses grow with modern web solutions.')
            ->click(paragraphBlockSelector())
            ->click('[aria-label="Align text center"]');
        
        // 4. Add two-column feature section
        $browser->addBlock('Columns')
            ->click('.block-editor-block-variation-picker__variation:first-child');
        
        // Left column
        $browser->click(columnBlockSelector(0))
            ->addBlock('Heading')
            ->type(headingBlockSelector() . ':last-child', 'Our Services');
        
        // Right column  
        $browser->click(columnBlockSelector(1))
            ->addBlock('Heading')
            ->type(headingBlockSelector() . ':last-child', 'Why Choose Us');
        
        // 5. Add CTA button
        $browser->addBlock('Buttons')
            ->type(buttonBlockSelector() . ' [contenteditable]', 'Get Started')
            ->click(buttonBlockSelector())
            ->click('[aria-label="Align text center"]');
        
        // 6. Set featured image
        $browser->click(documentSettingsToggleSelector())
            ->click(featuredImageControlSelector())
            ->waitFor('.media-modal')
            ->click(".attachment[data-id='{$imageId}']")
            ->click('.media-button-select');
        
        // 7. Publish
        $browser->click('.editor-post-publish-button')
            ->waitFor('.editor-post-publish-panel')
            ->click('.editor-post-publish-panel__header-publish-button button')
            ->waitForText('is now live');
        
        // 8. View page
        $browser->click('.post-publish-panel__postpublish-buttons a')
            ->assertSee('Build Something Amazing')
            ->assertSee('Get Started');
    });
});
```

---

## Testing Block Output on Frontend

```php
use function PestWP\Functions\paragraphBlockSelector;
use function PestWP\Functions\headingBlockSelector;
use function PestWP\Functions\columnsBlockSelector;

it('verifies block content renders correctly', function () {
    $post = createPost([
        'post_content' => '<!-- wp:heading -->
            <h2 class="wp-block-heading">Test Heading</h2>
            <!-- /wp:heading -->
            <!-- wp:paragraph -->
            <p>Test paragraph content.</p>
            <!-- /wp:paragraph -->
            <!-- wp:columns -->
            <div class="wp-block-columns">
                <div class="wp-block-column"><p>Column 1</p></div>
                <div class="wp-block-column"><p>Column 2</p></div>
            </div>
            <!-- /wp:columns -->'
    ]);
    
    $this->browse(function ($browser) use ($post) {
        $browser->visit(get_permalink($post))
            ->assertVisible('.wp-block-heading')
            ->assertSeeIn('.wp-block-heading', 'Test Heading')
            ->assertVisible('.wp-block-paragraph')
            ->assertVisible('.wp-block-columns')
            ->assertElementsCount('.wp-block-column', 2);
    });
});
```

---

## Best Practices

### 1. Use Block Helper Functions

Create reusable helpers for common block operations:

```php
// tests/Helpers/BlockHelpers.php
function addBlock(Browser $browser, string $blockName): Browser
{
    return $browser
        ->click('[aria-label="Add block"]')
        ->waitFor('.block-editor-inserter__panel-content')
        ->type('[placeholder="Search"]', $blockName)
        ->click("[aria-label='{$blockName}']")
        ->waitUntilMissing('.block-editor-inserter__panel-content');
}

function selectBlock(Browser $browser, string $selector): Browser
{
    return $browser
        ->click($selector)
        ->waitFor('.block-editor-block-toolbar');
}
```

### 2. Wait for Editor Ready

```php
it('waits for editor to be ready', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php')
            ->waitFor('.block-editor')
            ->waitUntilMissing('.block-editor-block-list__layout.is-loading')
            ->assertVisible('.editor-post-title__input');
    });
});
```

### 3. Handle Block Selection States

```php
it('handles block focus states', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php')
            ->waitFor('.block-editor');
        
        // Click block to select
        $browser->type(paragraphBlockSelector(), 'Content')
            ->click(paragraphBlockSelector())
            ->assertHasClass(paragraphBlockSelector(), 'is-selected')
            ->assertVisible(blockToolbarSelector());
        
        // Click away to deselect
        $browser->click('.editor-post-title__input')
            ->assertMissing(paragraphBlockSelector() . '.is-selected');
    });
});
```

### 4. Test Block Transforms

```php
it('transforms paragraph to heading', function () {
    $this->browse(function ($browser) {
        $browser->loginAs(1)
            ->visit('/wp-admin/post-new.php')
            ->waitFor('.block-editor');
        
        $browser->type(paragraphBlockSelector(), 'This becomes a heading')
            ->click(paragraphBlockSelector())
            ->click(blockOptionsMenuSelector())
            ->click(blockTransformSelector())
            ->click('button:has-text("Heading")')
            ->assertMissing(paragraphBlockSelector())
            ->assertVisible(headingBlockSelector());
    });
});
```

---

## Next Steps

- [Browser Testing](browser-testing.md) - Learn about Playwright integration
- [WooCommerce Testing](woocommerce.md) - Test WooCommerce stores
- [Visual Regression](visual-regression.md) - Screenshot comparison testing
- [Accessibility Testing](accessibility-testing.md) - Test WCAG compliance
