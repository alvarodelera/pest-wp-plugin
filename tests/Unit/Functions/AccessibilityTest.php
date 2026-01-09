<?php

declare(strict_types=1);

use function PestWP\Functions\checkButtonsWithoutText;
use function PestWP\Functions\checkDocumentLanguage;
use function PestWP\Functions\checkHeadingHierarchy;
use function PestWP\Functions\checkImagesWithoutAlt;
use function PestWP\Functions\checkInputsWithoutLabels;
use function PestWP\Functions\checkLinksWithoutText;
use function PestWP\Functions\checkPageTitle;
use function PestWP\Functions\checkWcagLevelA;
use function PestWP\Functions\formatAccessibilityReport;
use function PestWP\Functions\getAccessibilityViolations;
use function PestWP\Functions\getAccessibilityViolationsByImpact;
use function PestWP\Functions\isAccessible;
use function PestWP\Functions\wcagLevelAAChecks;
use function PestWP\Functions\wcagLevelAChecks;

describe('Image Alt Text Checks', function (): void {
    test('detects images without alt attribute', function (): void {
        $html = '<img src="photo.jpg">';
        $violations = checkImagesWithoutAlt($html);

        expect($violations)->toHaveCount(1);
        expect($violations[0]['impact'])->toBe('critical');
        expect($violations[0]['issue'])->toContain('missing alt');
    });

    test('passes images with alt attribute', function (): void {
        $html = '<img src="photo.jpg" alt="A description">';
        $violations = checkImagesWithoutAlt($html);

        // Should not have critical violations
        $critical = array_filter($violations, fn ($v) => $v['impact'] === 'critical');
        expect($critical)->toBeEmpty();
    });

    test('flags empty alt as minor for review', function (): void {
        $html = '<img src="photo.jpg" alt="">';
        $violations = checkImagesWithoutAlt($html);

        expect($violations)->toHaveCount(1);
        expect($violations[0]['impact'])->toBe('minor');
    });
});

describe('Form Input Label Checks', function (): void {
    test('detects inputs without labels', function (): void {
        $html = '<input type="text" name="email">';
        $violations = checkInputsWithoutLabels($html);

        expect($violations)->toHaveCount(1);
        expect($violations[0]['impact'])->toBe('critical');
    });

    test('passes inputs with aria-label', function (): void {
        $html = '<input type="text" aria-label="Email address">';
        $violations = checkInputsWithoutLabels($html);

        expect($violations)->toBeEmpty();
    });

    test('passes inputs with associated label', function (): void {
        $html = '<label for="email">Email</label><input type="text" id="email">';
        $violations = checkInputsWithoutLabels($html);

        expect($violations)->toBeEmpty();
    });

    test('passes hidden inputs', function (): void {
        $html = '<input type="hidden" name="token" value="abc">';
        $violations = checkInputsWithoutLabels($html);

        expect($violations)->toBeEmpty();
    });

    test('passes submit buttons', function (): void {
        $html = '<input type="submit" value="Submit">';
        $violations = checkInputsWithoutLabels($html);

        expect($violations)->toBeEmpty();
    });
});

describe('Document Language Checks', function (): void {
    test('detects missing lang attribute', function (): void {
        $html = '<html><head></head><body></body></html>';
        $violations = checkDocumentLanguage($html);

        expect($violations)->toHaveCount(1);
        expect($violations[0]['impact'])->toBe('serious');
    });

    test('passes with lang attribute', function (): void {
        $html = '<html lang="en"><head></head><body></body></html>';
        $violations = checkDocumentLanguage($html);

        expect($violations)->toBeEmpty();
    });
});

describe('Page Title Checks', function (): void {
    test('detects missing title', function (): void {
        $html = '<html><head></head><body></body></html>';
        $violations = checkPageTitle($html);

        expect($violations)->toHaveCount(1);
        expect($violations[0]['impact'])->toBe('serious');
    });

    test('passes with title', function (): void {
        $html = '<html><head><title>My Page</title></head><body></body></html>';
        $violations = checkPageTitle($html);

        expect($violations)->toBeEmpty();
    });
});

describe('Heading Hierarchy Checks', function (): void {
    test('detects skipped heading levels', function (): void {
        $html = '<h1>Title</h1><h3>Subsection</h3>';
        $violations = checkHeadingHierarchy($html);

        expect($violations)->not->toBeEmpty();
        expect($violations[0]['issue'])->toContain('skipped');
    });

    test('passes proper heading hierarchy', function (): void {
        $html = '<h1>Title</h1><h2>Section</h2><h3>Subsection</h3>';
        $violations = checkHeadingHierarchy($html);

        expect($violations)->toBeEmpty();
    });

    test('detects when first heading is not h1', function (): void {
        $html = '<h2>Section</h2><h3>Subsection</h3>';
        $violations = checkHeadingHierarchy($html);

        expect($violations)->not->toBeEmpty();
        expect($violations[0]['issue'])->toContain('not h1');
    });
});

describe('Link Accessibility Checks', function (): void {
    test('detects links without text', function (): void {
        $html = '<a href="/page"></a>';
        $violations = checkLinksWithoutText($html);

        expect($violations)->toHaveCount(1);
        expect($violations[0]['impact'])->toBe('critical');
    });

    test('passes links with text', function (): void {
        $html = '<a href="/page">Read more</a>';
        $violations = checkLinksWithoutText($html);

        expect($violations)->toBeEmpty();
    });

    test('passes links with aria-label', function (): void {
        $html = '<a href="/page" aria-label="Go to page"></a>';
        $violations = checkLinksWithoutText($html);

        expect($violations)->toBeEmpty();
    });

    test('passes links with image alt text', function (): void {
        $html = '<a href="/page"><img src="icon.png" alt="Go to page"></a>';
        $violations = checkLinksWithoutText($html);

        expect($violations)->toBeEmpty();
    });
});

describe('Button Accessibility Checks', function (): void {
    test('detects buttons without text', function (): void {
        $html = '<button></button>';
        $violations = checkButtonsWithoutText($html);

        expect($violations)->toHaveCount(1);
        expect($violations[0]['impact'])->toBe('critical');
    });

    test('passes buttons with text', function (): void {
        $html = '<button>Click me</button>';
        $violations = checkButtonsWithoutText($html);

        expect($violations)->toBeEmpty();
    });

    test('passes buttons with aria-label', function (): void {
        $html = '<button aria-label="Close dialog"></button>';
        $violations = checkButtonsWithoutText($html);

        expect($violations)->toBeEmpty();
    });
});

describe('Main Accessibility Functions', function (): void {
    test('getAccessibilityViolations runs all checks', function (): void {
        $html = '<html><body><img src="photo.jpg"><a href="/"></a></body></html>';
        $violations = getAccessibilityViolations($html);

        // Should find multiple violations
        expect($violations)->not->toBeEmpty();
    });

    test('getAccessibilityViolations accepts specific checks', function (): void {
        $html = '<img src="photo.jpg">';
        $violations = getAccessibilityViolations($html, ['images']);

        expect($violations)->toHaveCount(1);
    });

    test('getAccessibilityViolationsByImpact filters by severity', function (): void {
        $html = '<img src="photo.jpg"><img src="photo2.jpg" alt="">';

        $critical = getAccessibilityViolationsByImpact($html, 'critical');
        $all = getAccessibilityViolationsByImpact($html, 'minor');

        expect(count($critical))->toBeLessThan(count($all));
    });

    test('isAccessible returns true for accessible HTML', function (): void {
        $html = '<!DOCTYPE html>
            <html lang="en">
            <head><title>Test</title></head>
            <body>
                <h1>Welcome</h1>
                <img src="photo.jpg" alt="Description">
                <a href="/page">Link text</a>
                <button>Click me</button>
            </body>
            </html>';

        expect(isAccessible($html))->toBeTrue();
    });

    test('isAccessible returns false for inaccessible HTML', function (): void {
        $html = '<img src="photo.jpg"><a href="/"></a>';

        expect(isAccessible($html))->toBeFalse();
    });

    test('formatAccessibilityReport formats violations nicely', function (): void {
        $violations = [
            ['element' => '<img>', 'issue' => 'Missing alt', 'impact' => 'critical'],
            ['element' => '<a>', 'issue' => 'Empty link', 'impact' => 'serious'],
        ];

        $report = formatAccessibilityReport($violations);

        expect($report)->toContain('Accessibility Violations Found: 2');
        expect($report)->toContain('CRITICAL');
        expect($report)->toContain('SERIOUS');
    });

    test('formatAccessibilityReport handles empty violations', function (): void {
        $report = formatAccessibilityReport([]);

        expect($report)->toContain('No accessibility violations found');
    });
});

describe('WCAG Level Checks', function (): void {
    test('wcagLevelAChecks returns basic checks', function (): void {
        $checks = wcagLevelAChecks();

        expect($checks)->toContain('images');
        expect($checks)->toContain('inputs');
        expect($checks)->toContain('language');
    });

    test('wcagLevelAAChecks includes Level A checks', function (): void {
        $levelA = wcagLevelAChecks();
        $levelAA = wcagLevelAAChecks();

        foreach ($levelA as $check) {
            expect($levelAA)->toContain($check);
        }

        expect(count($levelAA))->toBeGreaterThan(count($levelA));
    });

    test('checkWcagLevelA runs only Level A checks', function (): void {
        $html = '<img src="photo.jpg">';
        $violations = checkWcagLevelA($html);

        expect($violations)->not->toBeEmpty();
    });
});
