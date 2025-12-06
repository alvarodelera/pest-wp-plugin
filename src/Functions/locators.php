<?php

declare(strict_types=1);

namespace PestWP\Functions;

/**
 * WP Admin Locators - Abstractions for WordPress Admin selectors.
 *
 * These helpers provide resilient selectors for WordPress admin UI elements,
 * designed to work across different WordPress versions (6.5+).
 */

/**
 * Build admin URL for a specific page.
 *
 * @param  string  $page  The admin page slug (e.g., 'options-general.php', 'edit.php')
 * @param  array<string, string|int>  $params  Additional query parameters
 */
function adminUrl(string $page = '', array $params = []): string
{
    $config = getBrowserConfig();
    $baseUrl = rtrim($config['base_url'], '/');

    if ($page === '') {
        return $baseUrl . '/wp-admin/';
    }

    // Handle pages that are already full paths
    if (str_starts_with($page, '/')) {
        $url = $baseUrl . $page;
    } elseif (str_contains($page, '.php')) {
        // Core admin pages (edit.php, post-new.php, options-general.php, etc.)
        $url = $baseUrl . '/wp-admin/' . $page;
    } else {
        // Custom plugin pages (admin.php?page=slug)
        $url = $baseUrl . '/wp-admin/admin.php?page=' . urlencode($page);
    }

    if (! empty($params)) {
        $separator = str_contains($url, '?') ? '&' : '?';
        $url .= $separator . http_build_query($params);
    }

    return $url;
}

/**
 * Get URL for WordPress login page.
 */
function loginUrl(): string
{
    $config = getBrowserConfig();

    return rtrim($config['base_url'], '/') . '/wp-login.php';
}

/**
 * Get URL for creating a new post.
 *
 * @param  string  $postType  The post type (default: 'post')
 */
function newPostUrl(string $postType = 'post'): string
{
    if ($postType === 'post') {
        return adminUrl('post-new.php');
    }

    return adminUrl('post-new.php', ['post_type' => $postType]);
}

/**
 * Get URL for editing a specific post.
 */
function editPostUrl(int $postId): string
{
    return adminUrl('post.php', ['post' => $postId, 'action' => 'edit']);
}

/**
 * Get URL for posts list.
 *
 * @param  string  $postType  The post type (default: 'post')
 * @param  string  $status  Filter by status (optional)
 */
function postsListUrl(string $postType = 'post', string $status = ''): string
{
    $params = [];

    if ($postType !== 'post') {
        $params['post_type'] = $postType;
    }

    if ($status !== '') {
        $params['post_status'] = $status;
    }

    return adminUrl('edit.php', $params);
}

/**
 * Get URL for media library.
 */
function mediaLibraryUrl(): string
{
    return adminUrl('upload.php');
}

/**
 * Get URL for users list.
 */
function usersListUrl(): string
{
    return adminUrl('users.php');
}

/**
 * Get URL for adding a new user.
 */
function newUserUrl(): string
{
    return adminUrl('user-new.php');
}

/**
 * Get URL for editing a specific user.
 */
function editUserUrl(int $userId): string
{
    return adminUrl('user-edit.php', ['user_id' => $userId]);
}

/**
 * Get URL for plugins page.
 */
function pluginsUrl(): string
{
    return adminUrl('plugins.php');
}

/**
 * Get URL for themes page.
 */
function themesUrl(): string
{
    return adminUrl('themes.php');
}

/**
 * Get URL for general settings.
 */
function settingsUrl(string $page = 'general'): string
{
    return adminUrl('options-' . $page . '.php');
}

// =============================================================================
// CSS SELECTORS - WordPress Admin UI Elements
// =============================================================================

/**
 * Get selector for admin menu item by text.
 *
 * Works with both top-level and submenu items.
 *
 * @param  string  $menuText  The visible text of the menu item
 * @return string CSS selector
 */
function menuSelector(string $menuText): string
{
    // WordPress admin menu uses .wp-menu-name for text
    return "#adminmenu a:has(.wp-menu-name:text-is('{$menuText}')), " .
           "#adminmenu a.menu-top:text-is('{$menuText}'), " .
           "#adminmenu .wp-submenu a:text-is('{$menuText}')";
}

/**
 * Get selector for admin submenu item.
 *
 * @param  string  $parentMenu  Parent menu text
 * @param  string  $submenuText  Submenu item text
 * @return string CSS selector
 */
function submenuSelector(string $parentMenu, string $submenuText): string
{
    return "#adminmenu li:has(.wp-menu-name:text-is('{$parentMenu}')) .wp-submenu a:text-is('{$submenuText}')";
}

/**
 * Get selector for admin notice by type.
 *
 * @param  string  $type  Notice type: 'success', 'error', 'warning', 'info'
 * @return string CSS selector
 */
function noticeSelector(string $type = ''): string
{
    if ($type === '') {
        return '.notice, .updated, .error';
    }

    return match ($type) {
        'success' => '.notice-success, .updated',
        'error' => '.notice-error, .error',
        'warning' => '.notice-warning',
        'info' => '.notice-info',
        default => ".notice-{$type}",
    };
}

/**
 * Get selector for admin button by text.
 *
 * @param  string  $text  Button text
 * @param  string  $type  Button type: 'primary', 'secondary', or empty for any
 * @return string CSS selector
 */
function buttonSelector(string $text, string $type = ''): string
{
    $baseSelector = "button:text-is('{$text}'), input[type='submit'][value='{$text}']";

    if ($type !== '') {
        return ".button-{$type}:text-is('{$text}'), " . $baseSelector;
    }

    return $baseSelector;
}

// =============================================================================
// GUTENBERG / BLOCK EDITOR SELECTORS
// =============================================================================

/**
 * Get selector for Gutenberg post title field.
 *
 * Compatible with WP 6.5+
 *
 * @return string CSS selector
 */
function postTitleSelector(): string
{
    // Multiple selectors for compatibility across WP versions
    return "[aria-label='Add title'], " .
           '.editor-post-title__input, ' .
           '.wp-block-post-title, ' .
           "h1[contenteditable='true']";
}

/**
 * Get selector for Gutenberg publish button.
 *
 * @return string CSS selector
 */
function publishButtonSelector(): string
{
    return '.editor-post-publish-button, ' .
           '.editor-post-publish-panel__toggle, ' .
           "button:text-is('Publish')";
}

/**
 * Get selector for Gutenberg update button.
 *
 * @return string CSS selector
 */
function updateButtonSelector(): string
{
    return ".editor-post-publish-button:text-is('Update'), " .
           "button:text-is('Update')";
}

/**
 * Get selector for Gutenberg save draft button.
 *
 * @return string CSS selector
 */
function saveDraftSelector(): string
{
    return '.editor-post-save-draft, ' .
           "button:text-is('Save draft')";
}

/**
 * Get selector for a specific Gutenberg block by name.
 *
 * @param  string  $blockName  Block name (e.g., 'core/paragraph', 'core/heading')
 * @return string CSS selector
 */
function blockSelector(string $blockName): string
{
    return "[data-type='{$blockName}']";
}

/**
 * Get selector for block inserter button.
 *
 * @return string CSS selector
 */
function blockInserterSelector(): string
{
    return '.block-editor-inserter__toggle, ' .
           '.edit-post-header-toolbar__inserter-toggle, ' .
           "[aria-label='Toggle block inserter'], " .
           "[aria-label='Add block']";
}

/**
 * Get selector for block search input in inserter.
 *
 * @return string CSS selector
 */
function blockSearchSelector(): string
{
    return '.block-editor-inserter__search input, ' .
           '.block-editor-inserter__search-input, ' .
           "[placeholder='Search']";
}

/**
 * Get selector for a block in the inserter panel.
 *
 * @param  string  $blockTitle  The display title of the block (e.g., 'Paragraph', 'Heading')
 * @return string CSS selector
 */
function inserterBlockSelector(string $blockTitle): string
{
    return ".block-editor-block-types-list__item:has-text('{$blockTitle}'), " .
           ".editor-block-list-item-{$blockTitle}";
}

/**
 * Get selector for Gutenberg snackbar notices.
 *
 * @return string CSS selector
 */
function editorNoticeSelector(): string
{
    return '.components-snackbar, ' .
           '.components-notice, ' .
           '.editor-post-publish-panel__postpublish';
}

/**
 * Get selector for post permalink in editor.
 *
 * @return string CSS selector
 */
function postPermalinkSelector(): string
{
    return '.editor-post-link__link, ' .
           ".components-external-link[href*='?p='], " .
           "a:text-is('View Post')";
}

/**
 * Get selector for post status badge.
 *
 * @return string CSS selector
 */
function postStatusSelector(): string
{
    return '.editor-post-status, ' .
           '.editor-post-publish-panel__header-post-status';
}

// =============================================================================
// CLASSIC EDITOR SELECTORS (for backward compatibility)
// =============================================================================

/**
 * Get selector for Classic Editor title field.
 *
 * @return string CSS selector
 */
function classicTitleSelector(): string
{
    return '#title';
}

/**
 * Get selector for Classic Editor content field.
 *
 * @return string CSS selector
 */
function classicContentSelector(): string
{
    return '#content';
}

/**
 * Get selector for Classic Editor publish button.
 *
 * @return string CSS selector
 */
function classicPublishSelector(): string
{
    return '#publish';
}

// =============================================================================
// DATA TABLE SELECTORS (Posts list, Users list, etc.)
// =============================================================================

/**
 * Get selector for a row in a WordPress data table by title.
 *
 * @param  string  $title  The title text to find
 * @return string CSS selector
 */
function tableRowSelector(string $title): string
{
    return ".wp-list-table tr:has(.row-title:text-is('{$title}')), " .
           ".wp-list-table tr:has(a.row-title:text-is('{$title}'))";
}

/**
 * Get selector for row action links.
 *
 * @param  string  $action  Action name: 'edit', 'trash', 'view', 'delete', etc.
 * @return string CSS selector
 */
function rowActionSelector(string $action): string
{
    return ".row-actions .{$action} a, " .
           ".row-actions a:text-is('" . ucfirst($action) . "')";
}

/**
 * Get selector for bulk action dropdown.
 *
 * @return string CSS selector
 */
function bulkActionSelector(): string
{
    return '#bulk-action-selector-top';
}

/**
 * Get selector for bulk action apply button.
 *
 * @return string CSS selector
 */
function bulkApplySelector(): string
{
    return '#doaction';
}

/**
 * Get selector for "select all" checkbox in tables.
 *
 * @return string CSS selector
 */
function selectAllSelector(): string
{
    return "#cb-select-all-1, .check-column input[type='checkbox']:first-of-type";
}

// =============================================================================
// FORM FIELD SELECTORS
// =============================================================================

/**
 * Get selector for a form field by label text.
 *
 * @param  string  $labelText  The label text
 * @return string CSS selector
 */
function fieldByLabelSelector(string $labelText): string
{
    return "input[aria-label='{$labelText}'], " .
           "textarea[aria-label='{$labelText}'], " .
           "select[aria-label='{$labelText}'], " .
           "label:text-is('{$labelText}') + input, " .
           "label:text-is('{$labelText}') + textarea, " .
           "label:text-is('{$labelText}') + select";
}

/**
 * Get selector for a settings field by ID.
 *
 * @param  string  $fieldId  The field ID
 * @return string CSS selector
 */
function settingsFieldSelector(string $fieldId): string
{
    return "#{$fieldId}";
}
