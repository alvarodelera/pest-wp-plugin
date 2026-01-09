<?php

declare(strict_types=1);

namespace PestWP\Functions;

/**
 * Extended Gutenberg Block Selectors
 *
 * Comprehensive selectors for all core WordPress blocks.
 * Designed for browser testing with Playwright.
 */

// =============================================================================
// TEXT BLOCKS
// =============================================================================

/**
 * Get selector for paragraph block.
 *
 * @param int|null $index Zero-based index if targeting a specific paragraph
 * @return string CSS selector
 */
function paragraphBlockSelector(?int $index = null): string
{
    $base = "[data-type='core/paragraph'], .wp-block-paragraph";
    if ($index !== null) {
        return "{$base}:nth-of-type(" . ($index + 1) . ')';
    }

    return $base;
}

/**
 * Get selector for heading block.
 *
 * @param int|null $level Heading level (1-6)
 * @return string CSS selector
 */
function headingBlockSelector(?int $level = null): string
{
    if ($level !== null) {
        return "[data-type='core/heading'] h{$level}, .wp-block-heading h{$level}";
    }

    return "[data-type='core/heading'], .wp-block-heading";
}

/**
 * Get selector for list block.
 *
 * @param string $type 'ordered' or 'unordered'
 * @return string CSS selector
 */
function listBlockSelector(string $type = ''): string
{
    if ($type === 'ordered') {
        return "[data-type='core/list'] ol, .wp-block-list ol";
    }
    if ($type === 'unordered') {
        return "[data-type='core/list'] ul, .wp-block-list ul";
    }

    return "[data-type='core/list'], .wp-block-list";
}

/**
 * Get selector for quote block.
 *
 * @return string CSS selector
 */
function quoteBlockSelector(): string
{
    return "[data-type='core/quote'], .wp-block-quote";
}

/**
 * Get selector for pullquote block.
 *
 * @return string CSS selector
 */
function pullquoteBlockSelector(): string
{
    return "[data-type='core/pullquote'], .wp-block-pullquote";
}

/**
 * Get selector for code block.
 *
 * @return string CSS selector
 */
function codeBlockSelector(): string
{
    return "[data-type='core/code'], .wp-block-code";
}

/**
 * Get selector for preformatted block.
 *
 * @return string CSS selector
 */
function preformattedBlockSelector(): string
{
    return "[data-type='core/preformatted'], .wp-block-preformatted";
}

/**
 * Get selector for verse block.
 *
 * @return string CSS selector
 */
function verseBlockSelector(): string
{
    return "[data-type='core/verse'], .wp-block-verse";
}

// =============================================================================
// MEDIA BLOCKS
// =============================================================================

/**
 * Get selector for image block.
 *
 * @return string CSS selector
 */
function imageBlockSelector(): string
{
    return "[data-type='core/image'], .wp-block-image";
}

/**
 * Get selector for gallery block.
 *
 * @return string CSS selector
 */
function galleryBlockSelector(): string
{
    return "[data-type='core/gallery'], .wp-block-gallery";
}

/**
 * Get selector for audio block.
 *
 * @return string CSS selector
 */
function audioBlockSelector(): string
{
    return "[data-type='core/audio'], .wp-block-audio";
}

/**
 * Get selector for video block.
 *
 * @return string CSS selector
 */
function videoBlockSelector(): string
{
    return "[data-type='core/video'], .wp-block-video";
}

/**
 * Get selector for cover block.
 *
 * @return string CSS selector
 */
function coverBlockSelector(): string
{
    return "[data-type='core/cover'], .wp-block-cover";
}

/**
 * Get selector for media & text block.
 *
 * @return string CSS selector
 */
function mediaTextBlockSelector(): string
{
    return "[data-type='core/media-text'], .wp-block-media-text";
}

/**
 * Get selector for file block.
 *
 * @return string CSS selector
 */
function fileBlockSelector(): string
{
    return "[data-type='core/file'], .wp-block-file";
}

// =============================================================================
// DESIGN / LAYOUT BLOCKS
// =============================================================================

/**
 * Get selector for buttons block.
 *
 * @return string CSS selector
 */
function buttonsBlockSelector(): string
{
    return "[data-type='core/buttons'], .wp-block-buttons";
}

/**
 * Get selector for single button block.
 *
 * @return string CSS selector
 */
function buttonBlockSelector(): string
{
    return "[data-type='core/button'], .wp-block-button";
}

/**
 * Get selector for columns block.
 *
 * @return string CSS selector
 */
function columnsBlockSelector(): string
{
    return "[data-type='core/columns'], .wp-block-columns";
}

/**
 * Get selector for single column block.
 *
 * @param int|null $index Zero-based column index
 * @return string CSS selector
 */
function columnBlockSelector(?int $index = null): string
{
    $base = "[data-type='core/column'], .wp-block-column";
    if ($index !== null) {
        return "{$base}:nth-of-type(" . ($index + 1) . ')';
    }

    return $base;
}

/**
 * Get selector for group block.
 *
 * @return string CSS selector
 */
function groupBlockSelector(): string
{
    return "[data-type='core/group'], .wp-block-group";
}

/**
 * Get selector for row block.
 *
 * @return string CSS selector
 */
function rowBlockSelector(): string
{
    return "[data-type='core/group'].is-layout-flex, .wp-block-group.is-layout-flex";
}

/**
 * Get selector for stack block.
 *
 * @return string CSS selector
 */
function stackBlockSelector(): string
{
    return "[data-type='core/group'].is-layout-flow, .wp-block-group.is-layout-flow";
}

/**
 * Get selector for separator block.
 *
 * @return string CSS selector
 */
function separatorBlockSelector(): string
{
    return "[data-type='core/separator'], .wp-block-separator";
}

/**
 * Get selector for spacer block.
 *
 * @return string CSS selector
 */
function spacerBlockSelector(): string
{
    return "[data-type='core/spacer'], .wp-block-spacer";
}

/**
 * Get selector for details block (accordion).
 *
 * @return string CSS selector
 */
function detailsBlockSelector(): string
{
    return "[data-type='core/details'], .wp-block-details";
}

// =============================================================================
// WIDGET BLOCKS
// =============================================================================

/**
 * Get selector for shortcode block.
 *
 * @return string CSS selector
 */
function shortcodeBlockSelector(): string
{
    return "[data-type='core/shortcode'], .wp-block-shortcode";
}

/**
 * Get selector for archives block.
 *
 * @return string CSS selector
 */
function archivesBlockSelector(): string
{
    return "[data-type='core/archives'], .wp-block-archives";
}

/**
 * Get selector for calendar block.
 *
 * @return string CSS selector
 */
function calendarBlockSelector(): string
{
    return "[data-type='core/calendar'], .wp-block-calendar";
}

/**
 * Get selector for categories block.
 *
 * @return string CSS selector
 */
function categoriesBlockSelector(): string
{
    return "[data-type='core/categories'], .wp-block-categories";
}

/**
 * Get selector for custom HTML block.
 *
 * @return string CSS selector
 */
function htmlBlockSelector(): string
{
    return "[data-type='core/html'], .wp-block-html";
}

/**
 * Get selector for latest comments block.
 *
 * @return string CSS selector
 */
function latestCommentsBlockSelector(): string
{
    return "[data-type='core/latest-comments'], .wp-block-latest-comments";
}

/**
 * Get selector for latest posts block.
 *
 * @return string CSS selector
 */
function latestPostsBlockSelector(): string
{
    return "[data-type='core/latest-posts'], .wp-block-latest-posts";
}

/**
 * Get selector for page list block.
 *
 * @return string CSS selector
 */
function pageListBlockSelector(): string
{
    return "[data-type='core/page-list'], .wp-block-page-list";
}

/**
 * Get selector for RSS block.
 *
 * @return string CSS selector
 */
function rssBlockSelector(): string
{
    return "[data-type='core/rss'], .wp-block-rss";
}

/**
 * Get selector for search block.
 *
 * @return string CSS selector
 */
function searchBlockSelector(): string
{
    return "[data-type='core/search'], .wp-block-search";
}

/**
 * Get selector for social links block.
 *
 * @return string CSS selector
 */
function socialLinksBlockSelector(): string
{
    return "[data-type='core/social-links'], .wp-block-social-links";
}

/**
 * Get selector for tag cloud block.
 *
 * @return string CSS selector
 */
function tagCloudBlockSelector(): string
{
    return "[data-type='core/tag-cloud'], .wp-block-tag-cloud";
}

// =============================================================================
// THEME / SITE EDITOR BLOCKS
// =============================================================================

/**
 * Get selector for site title block.
 *
 * @return string CSS selector
 */
function siteTitleBlockSelector(): string
{
    return "[data-type='core/site-title'], .wp-block-site-title";
}

/**
 * Get selector for site tagline block.
 *
 * @return string CSS selector
 */
function siteTaglineBlockSelector(): string
{
    return "[data-type='core/site-tagline'], .wp-block-site-tagline";
}

/**
 * Get selector for site logo block.
 *
 * @return string CSS selector
 */
function siteLogoBlockSelector(): string
{
    return "[data-type='core/site-logo'], .wp-block-site-logo";
}

/**
 * Get selector for navigation block.
 *
 * @return string CSS selector
 */
function navigationBlockSelector(): string
{
    return "[data-type='core/navigation'], .wp-block-navigation";
}

/**
 * Get selector for query block.
 *
 * @return string CSS selector
 */
function queryBlockSelector(): string
{
    return "[data-type='core/query'], .wp-block-query";
}

/**
 * Get selector for post template block.
 *
 * @return string CSS selector
 */
function postTemplateBlockSelector(): string
{
    return "[data-type='core/post-template'], .wp-block-post-template";
}

/**
 * Get selector for post title block.
 *
 * @return string CSS selector
 */
function postTitleBlockSelector(): string
{
    return "[data-type='core/post-title'], .wp-block-post-title";
}

/**
 * Get selector for post content block.
 *
 * @return string CSS selector
 */
function postContentBlockSelector(): string
{
    return "[data-type='core/post-content'], .wp-block-post-content";
}

/**
 * Get selector for post excerpt block.
 *
 * @return string CSS selector
 */
function postExcerptBlockSelector(): string
{
    return "[data-type='core/post-excerpt'], .wp-block-post-excerpt";
}

/**
 * Get selector for post featured image block.
 *
 * @return string CSS selector
 */
function postFeaturedImageBlockSelector(): string
{
    return "[data-type='core/post-featured-image'], .wp-block-post-featured-image";
}

/**
 * Get selector for post date block.
 *
 * @return string CSS selector
 */
function postDateBlockSelector(): string
{
    return "[data-type='core/post-date'], .wp-block-post-date";
}

/**
 * Get selector for post author block.
 *
 * @return string CSS selector
 */
function postAuthorBlockSelector(): string
{
    return "[data-type='core/post-author'], .wp-block-post-author";
}

/**
 * Get selector for post categories block.
 *
 * @return string CSS selector
 */
function postCategoriesBlockSelector(): string
{
    return "[data-type='core/post-terms'][data-term='category'], .wp-block-post-terms";
}

/**
 * Get selector for post tags block.
 *
 * @return string CSS selector
 */
function postTagsBlockSelector(): string
{
    return "[data-type='core/post-terms'][data-term='post_tag'], .wp-block-post-terms";
}

/**
 * Get selector for comments block.
 *
 * @return string CSS selector
 */
function commentsBlockSelector(): string
{
    return "[data-type='core/comments'], .wp-block-comments";
}

/**
 * Get selector for comment form block.
 *
 * @return string CSS selector
 */
function commentFormBlockSelector(): string
{
    return "[data-type='core/post-comments-form'], .wp-block-post-comments-form";
}

/**
 * Get selector for template part block.
 *
 * @param string $slug Template part slug (e.g., 'header', 'footer')
 * @return string CSS selector
 */
function templatePartBlockSelector(string $slug = ''): string
{
    if ($slug !== '') {
        return "[data-type='core/template-part'][data-slug='{$slug}'], .wp-block-template-part";
    }

    return "[data-type='core/template-part'], .wp-block-template-part";
}

// =============================================================================
// TABLE BLOCK
// =============================================================================

/**
 * Get selector for table block.
 *
 * @return string CSS selector
 */
function tableBlockSelector(): string
{
    return "[data-type='core/table'], .wp-block-table";
}

/**
 * Get selector for table header in table block.
 *
 * @return string CSS selector
 */
function tableHeaderSelector(): string
{
    return "[data-type='core/table'] thead, .wp-block-table thead";
}

/**
 * Get selector for table body in table block.
 *
 * @return string CSS selector
 */
function tableBodySelector(): string
{
    return "[data-type='core/table'] tbody, .wp-block-table tbody";
}

/**
 * Get selector for table footer in table block.
 *
 * @return string CSS selector
 */
function tableFooterSelector(): string
{
    return "[data-type='core/table'] tfoot, .wp-block-table tfoot";
}

// =============================================================================
// EMBED BLOCKS
// =============================================================================

/**
 * Get selector for embed block.
 *
 * @param string $provider Optional provider name (e.g., 'youtube', 'twitter', 'vimeo')
 * @return string CSS selector
 */
function embedBlockSelector(string $provider = ''): string
{
    if ($provider !== '') {
        return "[data-type='core/embed'][data-provider-name='{$provider}'], " .
               ".wp-block-embed.is-provider-{$provider}";
    }

    return "[data-type='core/embed'], .wp-block-embed";
}

/**
 * Get selector for YouTube embed block.
 *
 * @return string CSS selector
 */
function youtubeBlockSelector(): string
{
    return embedBlockSelector('youtube');
}

/**
 * Get selector for Vimeo embed block.
 *
 * @return string CSS selector
 */
function vimeoBlockSelector(): string
{
    return embedBlockSelector('vimeo');
}

/**
 * Get selector for Twitter embed block.
 *
 * @return string CSS selector
 */
function twitterBlockSelector(): string
{
    return embedBlockSelector('twitter');
}

// =============================================================================
// BLOCK EDITOR UI HELPERS
// =============================================================================

/**
 * Get selector for block toolbar.
 *
 * @return string CSS selector
 */
function blockToolbarSelector(): string
{
    return '.block-editor-block-toolbar, .block-editor-block-contextual-toolbar';
}

/**
 * Get selector for block settings sidebar.
 *
 * @return string CSS selector
 */
function blockSettingsSidebarSelector(): string
{
    return '.block-editor-block-inspector, .edit-post-sidebar';
}

/**
 * Get selector for block mover (up/down arrows).
 *
 * @return string CSS selector
 */
function blockMoverSelector(): string
{
    return '.block-editor-block-mover, .block-editor-block-toolbar__block-mover';
}

/**
 * Get selector for block options menu (three dots).
 *
 * @return string CSS selector
 */
function blockOptionsMenuSelector(): string
{
    return '.block-editor-block-settings-menu__toggle, ' .
           "[aria-label='Options'], " .
           "[aria-label='More options']";
}

/**
 * Get selector for block transform button in options.
 *
 * @return string CSS selector
 */
function blockTransformSelector(): string
{
    return ".components-menu-item__button:has-text('Transform to')";
}

/**
 * Get selector for duplicate block button.
 *
 * @return string CSS selector
 */
function duplicateBlockSelector(): string
{
    return ".components-menu-item__button:has-text('Duplicate')";
}

/**
 * Get selector for remove block button.
 *
 * @return string CSS selector
 */
function removeBlockSelector(): string
{
    return ".components-menu-item__button:has-text('Delete'), " .
           ".components-menu-item__button:has-text('Remove')";
}

/**
 * Get selector for block alignment control.
 *
 * @return string CSS selector
 */
function blockAlignmentSelector(): string
{
    return ".block-editor-block-toolbar__slot [aria-label*='Align'], " .
           ".components-toolbar-group [aria-label*='Align']";
}

/**
 * Get selector for rich text formatting toolbar.
 *
 * @return string CSS selector
 */
function richTextToolbarSelector(): string
{
    return '.block-editor-format-toolbar, .components-toolbar';
}

/**
 * Get selector for bold button in formatting toolbar.
 *
 * @return string CSS selector
 */
function boldButtonSelector(): string
{
    return "[aria-label='Bold'], button:has-text('B')";
}

/**
 * Get selector for italic button in formatting toolbar.
 *
 * @return string CSS selector
 */
function italicButtonSelector(): string
{
    return "[aria-label='Italic'], button:has-text('I')";
}

/**
 * Get selector for link button in formatting toolbar.
 *
 * @return string CSS selector
 */
function linkButtonSelector(): string
{
    return "[aria-label='Link'], .block-editor-format-toolbar__link-button";
}

/**
 * Get selector for the link URL input in the link popover.
 *
 * @return string CSS selector
 */
function linkInputSelector(): string
{
    return '.block-editor-link-control__search-input input, ' .
           "[aria-label='URL'] input, " .
           '.components-form-token-field__input';
}

/**
 * Get selector for document/page settings panel toggle.
 *
 * @return string CSS selector
 */
function documentSettingsToggleSelector(): string
{
    return ".edit-post-header__settings button[aria-label='Settings'], " .
           ".interface-pinned-items button[aria-label='Settings']";
}

/**
 * Get selector for post visibility control.
 *
 * @return string CSS selector
 */
function visibilityControlSelector(): string
{
    return '.editor-post-visibility__toggle, ' .
           "button:has-text('Visibility')";
}

/**
 * Get selector for scheduled publish control.
 *
 * @return string CSS selector
 */
function scheduleControlSelector(): string
{
    return '.editor-post-schedule__toggle, ' .
           "button:has-text('Immediately'), " .
           "button:has-text('Publish:')";
}

/**
 * Get selector for featured image control.
 *
 * @return string CSS selector
 */
function featuredImageControlSelector(): string
{
    return '.editor-post-featured-image, ' .
           '.editor-post-featured-image__toggle';
}

/**
 * Get selector for excerpt panel.
 *
 * @return string CSS selector
 */
function excerptPanelSelector(): string
{
    return '.editor-post-excerpt, ' .
           ".components-panel__body:has-text('Excerpt')";
}

/**
 * Get selector for discussion/comments panel.
 *
 * @return string CSS selector
 */
function discussionPanelSelector(): string
{
    return '.editor-post-discussion-panel, ' .
           ".components-panel__body:has-text('Discussion')";
}
