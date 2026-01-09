<?php

declare(strict_types=1);

use function PestWP\Functions\blockSettingsSidebarSelector;
use function PestWP\Functions\blockToolbarSelector;
use function PestWP\Functions\buttonBlockSelector;
use function PestWP\Functions\buttonsBlockSelector;
use function PestWP\Functions\columnBlockSelector;
use function PestWP\Functions\columnsBlockSelector;
use function PestWP\Functions\coverBlockSelector;
use function PestWP\Functions\embedBlockSelector;
use function PestWP\Functions\galleryBlockSelector;
use function PestWP\Functions\groupBlockSelector;
use function PestWP\Functions\headingBlockSelector;
use function PestWP\Functions\imageBlockSelector;
use function PestWP\Functions\listBlockSelector;
use function PestWP\Functions\navigationBlockSelector;
use function PestWP\Functions\paragraphBlockSelector;
use function PestWP\Functions\postTitleBlockSelector;
use function PestWP\Functions\queryBlockSelector;
use function PestWP\Functions\tableBlockSelector;
use function PestWP\Functions\templatePartBlockSelector;
use function PestWP\Functions\youtubeBlockSelector;

describe('Gutenberg Text Block Selectors', function (): void {
    test('paragraphBlockSelector returns correct selector', function (): void {
        $selector = paragraphBlockSelector();

        expect($selector)->toContain("data-type='core/paragraph'");
        expect($selector)->toContain('wp-block-paragraph');
    });

    test('paragraphBlockSelector with index targets specific paragraph', function (): void {
        $selector = paragraphBlockSelector(0);

        expect($selector)->toContain(':nth-of-type(1)');
    });

    test('headingBlockSelector returns correct selector', function (): void {
        $selector = headingBlockSelector();

        expect($selector)->toContain("data-type='core/heading'");
        expect($selector)->toContain('wp-block-heading');
    });

    test('headingBlockSelector with level targets specific heading', function (): void {
        $selector = headingBlockSelector(2);

        expect($selector)->toContain('h2');
    });

    test('listBlockSelector returns correct selector', function (): void {
        $selector = listBlockSelector();

        expect($selector)->toContain("data-type='core/list'");
    });

    test('listBlockSelector for ordered list', function (): void {
        $selector = listBlockSelector('ordered');

        expect($selector)->toContain('ol');
    });

    test('listBlockSelector for unordered list', function (): void {
        $selector = listBlockSelector('unordered');

        expect($selector)->toContain('ul');
    });
});

describe('Gutenberg Media Block Selectors', function (): void {
    test('imageBlockSelector returns correct selector', function (): void {
        $selector = imageBlockSelector();

        expect($selector)->toContain("data-type='core/image'");
        expect($selector)->toContain('wp-block-image');
    });

    test('galleryBlockSelector returns correct selector', function (): void {
        $selector = galleryBlockSelector();

        expect($selector)->toContain("data-type='core/gallery'");
        expect($selector)->toContain('wp-block-gallery');
    });

    test('coverBlockSelector returns correct selector', function (): void {
        $selector = coverBlockSelector();

        expect($selector)->toContain("data-type='core/cover'");
        expect($selector)->toContain('wp-block-cover');
    });
});

describe('Gutenberg Layout Block Selectors', function (): void {
    test('columnsBlockSelector returns correct selector', function (): void {
        $selector = columnsBlockSelector();

        expect($selector)->toContain("data-type='core/columns'");
        expect($selector)->toContain('wp-block-columns');
    });

    test('columnBlockSelector returns correct selector', function (): void {
        $selector = columnBlockSelector();

        expect($selector)->toContain("data-type='core/column'");
    });

    test('columnBlockSelector with index targets specific column', function (): void {
        $selector = columnBlockSelector(1);

        expect($selector)->toContain(':nth-of-type(2)');
    });

    test('groupBlockSelector returns correct selector', function (): void {
        $selector = groupBlockSelector();

        expect($selector)->toContain("data-type='core/group'");
        expect($selector)->toContain('wp-block-group');
    });

    test('buttonsBlockSelector returns correct selector', function (): void {
        $selector = buttonsBlockSelector();

        expect($selector)->toContain("data-type='core/buttons'");
    });

    test('buttonBlockSelector returns correct selector', function (): void {
        $selector = buttonBlockSelector();

        expect($selector)->toContain("data-type='core/button'");
    });

    test('tableBlockSelector returns correct selector', function (): void {
        $selector = tableBlockSelector();

        expect($selector)->toContain("data-type='core/table'");
        expect($selector)->toContain('wp-block-table');
    });
});

describe('Gutenberg Embed Block Selectors', function (): void {
    test('embedBlockSelector returns generic embed selector', function (): void {
        $selector = embedBlockSelector();

        expect($selector)->toContain("data-type='core/embed'");
        expect($selector)->toContain('wp-block-embed');
    });

    test('embedBlockSelector with provider filters by provider', function (): void {
        $selector = embedBlockSelector('youtube');

        expect($selector)->toContain("data-provider-name='youtube'");
        expect($selector)->toContain('is-provider-youtube');
    });

    test('youtubeBlockSelector is convenience for YouTube embed', function (): void {
        $selector = youtubeBlockSelector();

        expect($selector)->toContain('youtube');
    });
});

describe('Gutenberg Theme Block Selectors', function (): void {
    test('navigationBlockSelector returns correct selector', function (): void {
        $selector = navigationBlockSelector();

        expect($selector)->toContain("data-type='core/navigation'");
        expect($selector)->toContain('wp-block-navigation');
    });

    test('queryBlockSelector returns correct selector', function (): void {
        $selector = queryBlockSelector();

        expect($selector)->toContain("data-type='core/query'");
    });

    test('postTitleBlockSelector returns correct selector', function (): void {
        $selector = postTitleBlockSelector();

        expect($selector)->toContain("data-type='core/post-title'");
        expect($selector)->toContain('wp-block-post-title');
    });

    test('templatePartBlockSelector returns correct selector', function (): void {
        $selector = templatePartBlockSelector();

        expect($selector)->toContain("data-type='core/template-part'");
    });

    test('templatePartBlockSelector with slug filters by slug', function (): void {
        $selector = templatePartBlockSelector('header');

        expect($selector)->toContain("data-slug='header'");
    });
});

describe('Gutenberg UI Helper Selectors', function (): void {
    test('blockToolbarSelector returns correct selector', function (): void {
        $selector = blockToolbarSelector();

        expect($selector)->toContain('block-editor-block-toolbar');
    });

    test('blockSettingsSidebarSelector returns correct selector', function (): void {
        $selector = blockSettingsSidebarSelector();

        expect($selector)->toContain('block-editor-block-inspector');
    });
});
