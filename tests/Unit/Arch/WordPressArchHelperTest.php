<?php

declare(strict_types=1);

use PestWP\Arch\WordPressArchHelper;

describe('WordPress Arch Helper', function () {
    describe('Constructor', function () {
        it('can be instantiated without targets', function () {
            $helper = new WordPressArchHelper();

            expect($helper->getTargets())->toBe([]);
        });

        it('can be instantiated with a single target', function () {
            $helper = new WordPressArchHelper('App');

            expect($helper->getTargets())->toBe(['App']);
        });

        it('can be instantiated with multiple targets', function () {
            $helper = new WordPressArchHelper(['App', 'Domain']);

            expect($helper->getTargets())->toBe(['App', 'Domain']);
        });
    });

    describe('expect()', function () {
        it('sets target with string', function () {
            $helper = new WordPressArchHelper();
            $result = $helper->expect('MyNamespace');

            expect($result)->toBeInstanceOf(WordPressArchHelper::class);
            expect($helper->getTargets())->toBe(['MyNamespace']);
        });

        it('sets targets with array', function () {
            $helper = new WordPressArchHelper();
            $result = $helper->expect(['App', 'Domain', 'Infrastructure']);

            expect($result)->toBeInstanceOf(WordPressArchHelper::class);
            expect($helper->getTargets())->toBe(['App', 'Domain', 'Infrastructure']);
        });

        it('replaces previous targets', function () {
            $helper = new WordPressArchHelper('OldNamespace');
            $helper->expect('NewNamespace');

            expect($helper->getTargets())->toBe(['NewNamespace']);
        });
    });

    describe('ignoring()', function () {
        it('adds ignored paths with string', function () {
            $helper = new WordPressArchHelper('App');
            $result = $helper->ignoring('App\\Legacy');

            expect($result)->toBeInstanceOf(WordPressArchHelper::class);
            expect($helper->getIgnoring())->toBe(['App\\Legacy']);
        });

        it('adds ignored paths with array', function () {
            $helper = new WordPressArchHelper('App');
            $result = $helper->ignoring(['App\\Legacy', 'App\\Deprecated']);

            expect($result)->toBeInstanceOf(WordPressArchHelper::class);
            expect($helper->getIgnoring())->toBe(['App\\Legacy', 'App\\Deprecated']);
        });

        it('accumulates ignored paths', function () {
            $helper = new WordPressArchHelper('App');
            $helper->ignoring('App\\Legacy');
            $helper->ignoring('App\\Deprecated');

            expect($helper->getIgnoring())->toBe(['App\\Legacy', 'App\\Deprecated']);
        });
    });

    describe('Fluent Interface', function () {
        it('supports method chaining', function () {
            $helper = new WordPressArchHelper();

            $result = $helper
                ->expect('App')
                ->ignoring('App\\Legacy')
                ->ignoring('App\\Deprecated');

            expect($result)->toBeInstanceOf(WordPressArchHelper::class);
            expect($helper->getTargets())->toBe(['App']);
            expect($helper->getIgnoring())->toBe(['App\\Legacy', 'App\\Deprecated']);
        });
    });
});

describe('wordpress() helper function', function () {
    it('returns WordPressArchHelper instance', function () {
        $helper = \PestWP\Functions\wordpress();

        expect($helper)->toBeInstanceOf(WordPressArchHelper::class);
    });

    it('accepts string target', function () {
        $helper = \PestWP\Functions\wordpress('App');

        expect($helper->getTargets())->toBe(['App']);
    });

    it('accepts array targets', function () {
        $helper = \PestWP\Functions\wordpress(['App', 'Domain']);

        expect($helper->getTargets())->toBe(['App', 'Domain']);
    });
});

describe('wpArch() helper function', function () {
    it('returns WordPressArchHelper instance', function () {
        $helper = \PestWP\Functions\wpArch();

        expect($helper)->toBeInstanceOf(WordPressArchHelper::class);
    });

    it('is an alias for wordpress()', function () {
        $helper1 = \PestWP\Functions\wordpress('App');
        $helper2 = \PestWP\Functions\wpArch('App');

        expect($helper1->getTargets())->toBe($helper2->getTargets());
    });
});
