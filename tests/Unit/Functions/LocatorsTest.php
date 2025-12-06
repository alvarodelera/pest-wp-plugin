<?php

declare(strict_types=1);

use function PestWP\Functions\adminUrl;
use function PestWP\Functions\blockInserterSelector;
use function PestWP\Functions\blockSearchSelector;
use function PestWP\Functions\blockSelector;
use function PestWP\Functions\bulkActionSelector;
use function PestWP\Functions\bulkApplySelector;
use function PestWP\Functions\buttonSelector;
use function PestWP\Functions\classicContentSelector;
use function PestWP\Functions\classicPublishSelector;
use function PestWP\Functions\classicTitleSelector;
use function PestWP\Functions\editorNoticeSelector;
use function PestWP\Functions\editPostUrl;
use function PestWP\Functions\editUserUrl;
use function PestWP\Functions\fieldByLabelSelector;
use function PestWP\Functions\inserterBlockSelector;
use function PestWP\Functions\loginUrl;
use function PestWP\Functions\mediaLibraryUrl;
use function PestWP\Functions\menuSelector;
use function PestWP\Functions\newPostUrl;
use function PestWP\Functions\newUserUrl;
use function PestWP\Functions\noticeSelector;
use function PestWP\Functions\pluginsUrl;
use function PestWP\Functions\postPermalinkSelector;
use function PestWP\Functions\postsListUrl;
use function PestWP\Functions\postStatusSelector;
use function PestWP\Functions\postTitleSelector;
use function PestWP\Functions\publishButtonSelector;
use function PestWP\Functions\rowActionSelector;
use function PestWP\Functions\saveDraftSelector;
use function PestWP\Functions\selectAllSelector;
use function PestWP\Functions\settingsFieldSelector;
use function PestWP\Functions\settingsUrl;
use function PestWP\Functions\submenuSelector;
use function PestWP\Functions\tableRowSelector;
use function PestWP\Functions\themesUrl;
use function PestWP\Functions\updateButtonSelector;
use function PestWP\Functions\usersListUrl;

beforeEach(function () {
    // Set environment variables for browser config
    putenv('WP_BASE_URL=http://localhost:8080');
    putenv('WP_ADMIN_USER=admin');
    putenv('WP_ADMIN_PASSWORD=password');
});

afterEach(function () {
    // Clean up environment variables
    putenv('WP_BASE_URL');
    putenv('WP_ADMIN_USER');
    putenv('WP_ADMIN_PASSWORD');
});

describe('WP Admin Locators', function () {
    describe('URL Helpers', function () {
        it('returns admin dashboard URL when no page specified', function () {
            expect(adminUrl())->toBe('http://localhost:8080/wp-admin/');
        });

        it('returns admin URL for core PHP pages', function () {
            expect(adminUrl('edit.php'))->toBe('http://localhost:8080/wp-admin/edit.php');
            expect(adminUrl('post-new.php'))->toBe('http://localhost:8080/wp-admin/post-new.php');
            expect(adminUrl('options-general.php'))->toBe('http://localhost:8080/wp-admin/options-general.php');
        });

        it('returns admin URL for plugin pages', function () {
            expect(adminUrl('my-plugin'))->toBe('http://localhost:8080/wp-admin/admin.php?page=my-plugin');
            expect(adminUrl('woocommerce'))->toBe('http://localhost:8080/wp-admin/admin.php?page=woocommerce');
        });

        it('handles additional query parameters', function () {
            $url = adminUrl('edit.php', ['post_type' => 'page']);
            expect($url)->toBe('http://localhost:8080/wp-admin/edit.php?post_type=page');
        });

        it('handles absolute paths', function () {
            expect(adminUrl('/wp-admin/index.php'))->toBe('http://localhost:8080/wp-admin/index.php');
        });

        it('returns login URL', function () {
            expect(loginUrl())->toBe('http://localhost:8080/wp-login.php');
        });

        it('returns new post URL', function () {
            expect(newPostUrl())->toBe('http://localhost:8080/wp-admin/post-new.php');
            expect(newPostUrl('page'))->toBe('http://localhost:8080/wp-admin/post-new.php?post_type=page');
        });

        it('returns edit post URL', function () {
            expect(editPostUrl(123))->toBe('http://localhost:8080/wp-admin/post.php?post=123&action=edit');
        });

        it('returns posts list URL', function () {
            expect(postsListUrl())->toBe('http://localhost:8080/wp-admin/edit.php');
            expect(postsListUrl('page'))->toBe('http://localhost:8080/wp-admin/edit.php?post_type=page');
            expect(postsListUrl('post', 'draft'))->toBe('http://localhost:8080/wp-admin/edit.php?post_status=draft');
        });

        it('returns media library URL', function () {
            expect(mediaLibraryUrl())->toBe('http://localhost:8080/wp-admin/upload.php');
        });

        it('returns users list URL', function () {
            expect(usersListUrl())->toBe('http://localhost:8080/wp-admin/users.php');
        });

        it('returns new user URL', function () {
            expect(newUserUrl())->toBe('http://localhost:8080/wp-admin/user-new.php');
        });

        it('returns edit user URL', function () {
            expect(editUserUrl(1))->toBe('http://localhost:8080/wp-admin/user-edit.php?user_id=1');
        });

        it('returns plugins URL', function () {
            expect(pluginsUrl())->toBe('http://localhost:8080/wp-admin/plugins.php');
        });

        it('returns themes URL', function () {
            expect(themesUrl())->toBe('http://localhost:8080/wp-admin/themes.php');
        });

        it('returns settings URL', function () {
            expect(settingsUrl())->toBe('http://localhost:8080/wp-admin/options-general.php');
            expect(settingsUrl('reading'))->toBe('http://localhost:8080/wp-admin/options-reading.php');
            expect(settingsUrl('writing'))->toBe('http://localhost:8080/wp-admin/options-writing.php');
        });
    });

    describe('Menu Selectors', function () {
        it('returns menu selector for text', function () {
            $selector = menuSelector('Posts');
            expect($selector)->toContain('#adminmenu');
            expect($selector)->toContain('Posts');
        });

        it('returns submenu selector', function () {
            $selector = submenuSelector('Settings', 'General');
            expect($selector)->toContain('#adminmenu');
            expect($selector)->toContain('Settings');
            expect($selector)->toContain('General');
            expect($selector)->toContain('.wp-submenu');
        });
    });

    describe('Notice Selectors', function () {
        it('returns generic notice selector', function () {
            $selector = noticeSelector();
            expect($selector)->toContain('.notice');
            expect($selector)->toContain('.updated');
            expect($selector)->toContain('.error');
        });

        it('returns success notice selector', function () {
            $selector = noticeSelector('success');
            expect($selector)->toContain('.notice-success');
            expect($selector)->toContain('.updated');
        });

        it('returns error notice selector', function () {
            $selector = noticeSelector('error');
            expect($selector)->toContain('.notice-error');
        });

        it('returns warning notice selector', function () {
            $selector = noticeSelector('warning');
            expect($selector)->toContain('.notice-warning');
        });

        it('returns info notice selector', function () {
            $selector = noticeSelector('info');
            expect($selector)->toContain('.notice-info');
        });
    });

    describe('Button Selectors', function () {
        it('returns button selector for text', function () {
            $selector = buttonSelector('Save Changes');
            expect($selector)->toContain('Save Changes');
            expect($selector)->toContain('button');
            expect($selector)->toContain('input');
        });

        it('returns primary button selector', function () {
            $selector = buttonSelector('Publish', 'primary');
            expect($selector)->toContain('.button-primary');
        });
    });

    describe('Gutenberg Selectors', function () {
        it('returns post title selector', function () {
            $selector = postTitleSelector();
            expect($selector)->toContain("[aria-label='Add title']");
            expect($selector)->toContain('.editor-post-title__input');
        });

        it('returns publish button selector', function () {
            $selector = publishButtonSelector();
            expect($selector)->toContain('.editor-post-publish-button');
            expect($selector)->toContain('Publish');
        });

        it('returns update button selector', function () {
            $selector = updateButtonSelector();
            expect($selector)->toContain('Update');
        });

        it('returns save draft selector', function () {
            $selector = saveDraftSelector();
            expect($selector)->toContain('.editor-post-save-draft');
        });

        it('returns block selector by name', function () {
            expect(blockSelector('core/paragraph'))->toBe("[data-type='core/paragraph']");
            expect(blockSelector('core/heading'))->toBe("[data-type='core/heading']");
        });

        it('returns block inserter selector', function () {
            $selector = blockInserterSelector();
            expect($selector)->toContain('.block-editor-inserter__toggle');
            expect($selector)->toContain('[aria-label=');
        });

        it('returns block search selector', function () {
            $selector = blockSearchSelector();
            expect($selector)->toContain('.block-editor-inserter__search');
        });

        it('returns inserter block selector', function () {
            $selector = inserterBlockSelector('Paragraph');
            expect($selector)->toContain('Paragraph');
            expect($selector)->toContain('.block-editor-block-types-list__item');
        });

        it('returns editor notice selector', function () {
            $selector = editorNoticeSelector();
            expect($selector)->toContain('.components-snackbar');
            expect($selector)->toContain('.components-notice');
        });

        it('returns post permalink selector', function () {
            $selector = postPermalinkSelector();
            expect($selector)->toContain('.editor-post-link__link');
        });

        it('returns post status selector', function () {
            $selector = postStatusSelector();
            expect($selector)->toContain('.editor-post-status');
        });
    });

    describe('Classic Editor Selectors', function () {
        it('returns classic title selector', function () {
            expect(classicTitleSelector())->toBe('#title');
        });

        it('returns classic content selector', function () {
            expect(classicContentSelector())->toBe('#content');
        });

        it('returns classic publish selector', function () {
            expect(classicPublishSelector())->toBe('#publish');
        });
    });

    describe('Data Table Selectors', function () {
        it('returns table row selector by title', function () {
            $selector = tableRowSelector('My Post Title');
            expect($selector)->toContain('.wp-list-table');
            expect($selector)->toContain('My Post Title');
            expect($selector)->toContain('.row-title');
        });

        it('returns row action selector', function () {
            expect(rowActionSelector('edit'))->toContain('.edit');
            expect(rowActionSelector('trash'))->toContain('.trash');
            expect(rowActionSelector('view'))->toContain('.view');
        });

        it('returns bulk action selector', function () {
            expect(bulkActionSelector())->toBe('#bulk-action-selector-top');
        });

        it('returns bulk apply selector', function () {
            expect(bulkApplySelector())->toBe('#doaction');
        });

        it('returns select all selector', function () {
            $selector = selectAllSelector();
            expect($selector)->toContain('#cb-select-all');
        });
    });

    describe('Form Field Selectors', function () {
        it('returns field by label selector', function () {
            $selector = fieldByLabelSelector('Email Address');
            expect($selector)->toContain('Email Address');
            expect($selector)->toContain('input');
            expect($selector)->toContain('textarea');
            expect($selector)->toContain('select');
        });

        it('returns settings field selector', function () {
            expect(settingsFieldSelector('blogname'))->toBe('#blogname');
            expect(settingsFieldSelector('siteurl'))->toBe('#siteurl');
        });
    });
});
