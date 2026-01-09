<?php

declare(strict_types=1);

namespace PestWP\Functions;

/**
 * Accessibility Testing Helpers
 *
 * Provides utilities for testing web accessibility (a11y) in browser tests.
 * These helpers check for common accessibility issues based on WCAG guidelines.
 */

// =============================================================================
// ACCESSIBILITY VIOLATION TYPES
// =============================================================================

/**
 * @phpstan-type AccessibilityViolation array{
 *     id: string,
 *     impact: string,
 *     description: string,
 *     help: string,
 *     helpUrl: string,
 *     nodes: array<array{html: string, target: array<string>}>
 * }
 */

// =============================================================================
// CORE ACCESSIBILITY CHECKS
// =============================================================================

/**
 * Check for images without alt attributes.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkImagesWithoutAlt(string $html): array
{
    $violations = [];

    // Match img tags without alt attribute
    if (preg_match_all('/<img\s+(?![^>]*\balt\s*=)[^>]*>/i', $html, $matches)) {
        foreach ($matches[0] as $img) {
            $violations[] = [
                'element' => $img,
                'issue' => 'Image missing alt attribute',
                'impact' => 'critical',
            ];
        }
    }

    // Match img tags with empty alt (only a warning for decorative images)
    if (preg_match_all('/<img\s+[^>]*alt\s*=\s*["\']["\'][^>]*>/i', $html, $matches)) {
        foreach ($matches[0] as $img) {
            // Empty alt is valid for decorative images, but flag for review
            $violations[] = [
                'element' => $img,
                'issue' => 'Image has empty alt attribute (verify if decorative)',
                'impact' => 'minor',
            ];
        }
    }

    return $violations;
}

/**
 * Check for form inputs without labels.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkInputsWithoutLabels(string $html): array
{
    $violations = [];

    // Find all input elements
    if (preg_match_all('/<input\s+[^>]*>/i', $html, $matches)) {
        foreach ($matches[0] as $input) {
            // Skip hidden, submit, button, image, reset types
            if (preg_match('/type\s*=\s*["\']?(hidden|submit|button|image|reset)["\']?/i', $input)) {
                continue;
            }

            // Check for aria-label or aria-labelledby
            if (preg_match('/aria-label(?:ledby)?\s*=/i', $input)) {
                continue;
            }

            // Check for id and corresponding label
            if (preg_match('/id\s*=\s*["\']?([^"\'>\s]+)["\']?/i', $input, $idMatch)) {
                $id = preg_quote($idMatch[1], '/');
                if (preg_match('/<label\s+[^>]*for\s*=\s*["\']?' . $id . '["\']?/i', $html)) {
                    continue;
                }
            }

            // Check if input is wrapped in label
            if (preg_match('/<label[^>]*>.*?' . preg_quote($input, '/') . '.*?<\/label>/is', $html)) {
                continue;
            }

            $violations[] = [
                'element' => $input,
                'issue' => 'Form input missing associated label',
                'impact' => 'critical',
            ];
        }
    }

    return $violations;
}

/**
 * Check for missing document language.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkDocumentLanguage(string $html): array
{
    $violations = [];

    if (preg_match('/<html[^>]*>/i', $html, $match)) {
        if (! preg_match('/lang\s*=\s*["\'][^"\']+["\']/i', $match[0])) {
            $violations[] = [
                'element' => $match[0],
                'issue' => 'Document missing lang attribute on html element',
                'impact' => 'serious',
            ];
        }
    }

    return $violations;
}

/**
 * Check for missing page title.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkPageTitle(string $html): array
{
    $violations = [];

    if (! preg_match('/<title[^>]*>[^<]+<\/title>/i', $html)) {
        $violations[] = [
            'element' => '<head>',
            'issue' => 'Document missing title element',
            'impact' => 'serious',
        ];
    }

    return $violations;
}

/**
 * Check for heading hierarchy issues.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkHeadingHierarchy(string $html): array
{
    $violations = [];

    // Extract all headings with their levels
    if (preg_match_all('/<h([1-6])[^>]*>.*?<\/h\1>/is', $html, $matches, PREG_OFFSET_CAPTURE)) {
        $levels = [];
        foreach ($matches[1] as $index => $match) {
            $levels[] = [
                'level' => (int) $match[0],
                'element' => $matches[0][$index][0],
            ];
        }

        // Check for skipped heading levels
        $previousLevel = 0;
        foreach ($levels as $heading) {
            $level = $heading['level'];
            if ($previousLevel > 0 && $level > $previousLevel + 1) {
                $violations[] = [
                    'element' => $heading['element'],
                    'issue' => "Heading level skipped from h{$previousLevel} to h{$level}",
                    'impact' => 'moderate',
                ];
            }
            $previousLevel = $level;
        }

        // Check if first heading is not h1
        if (! empty($levels) && $levels[0]['level'] !== 1) {
            $violations[] = [
                'element' => $levels[0]['element'],
                'issue' => 'First heading is not h1',
                'impact' => 'moderate',
            ];
        }
    }

    return $violations;
}

/**
 * Check for links without accessible text.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkLinksWithoutText(string $html): array
{
    $violations = [];

    // Find all anchor tags
    if (preg_match_all('/<a\s+[^>]*>.*?<\/a>/is', $html, $matches)) {
        foreach ($matches[0] as $link) {
            // Check for aria-label
            if (preg_match('/aria-label\s*=\s*["\'][^"\']+["\']/i', $link)) {
                continue;
            }

            // Check for visible text content (excluding whitespace)
            $textContent = preg_replace('/<[^>]+>/', '', $link);
            $textContent = trim($textContent ?? '');

            if ($textContent === '') {
                // Check for images with alt text inside
                if (preg_match('/<img[^>]+alt\s*=\s*["\'][^"\']+["\']/i', $link)) {
                    continue;
                }

                $violations[] = [
                    'element' => $link,
                    'issue' => 'Link has no accessible text',
                    'impact' => 'critical',
                ];
            }
        }
    }

    return $violations;
}

/**
 * Check for buttons without accessible text.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkButtonsWithoutText(string $html): array
{
    $violations = [];

    // Find all button tags
    if (preg_match_all('/<button\s*[^>]*>.*?<\/button>/is', $html, $matches)) {
        foreach ($matches[0] as $button) {
            // Check for aria-label
            if (preg_match('/aria-label\s*=\s*["\'][^"\']+["\']/i', $button)) {
                continue;
            }

            // Check for visible text content
            $textContent = preg_replace('/<[^>]+>/', '', $button);
            $textContent = trim($textContent ?? '');

            if ($textContent === '') {
                $violations[] = [
                    'element' => $button,
                    'issue' => 'Button has no accessible text',
                    'impact' => 'critical',
                ];
            }
        }
    }

    return $violations;
}

/**
 * Check for missing ARIA landmarks.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkAriaLandmarks(string $html): array
{
    $violations = [];

    // Check for main landmark
    if (! preg_match('/<main[^>]*>|role\s*=\s*["\']main["\']/i', $html)) {
        $violations[] = [
            'element' => '<body>',
            'issue' => 'Document missing main landmark',
            'impact' => 'moderate',
        ];
    }

    // Check for navigation landmark (only if nav-like content exists)
    if (preg_match('/<nav[^>]*>|role\s*=\s*["\']navigation["\']/i', $html)) {
        // Has navigation, good
    } elseif (preg_match('/class\s*=\s*["\'][^"\']*nav[^"\']*["\']/i', $html)) {
        $violations[] = [
            'element' => 'nav-like element',
            'issue' => 'Navigation-like element missing nav landmark',
            'impact' => 'minor',
        ];
    }

    return $violations;
}

/**
 * Check for tables without headers.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkTablesWithoutHeaders(string $html): array
{
    $violations = [];

    // Find all tables
    if (preg_match_all('/<table[^>]*>.*?<\/table>/is', $html, $matches)) {
        foreach ($matches[0] as $table) {
            // Skip tables that appear to be for layout (role="presentation")
            if (preg_match('/role\s*=\s*["\']presentation["\']/i', $table)) {
                continue;
            }

            // Check for th elements
            if (! preg_match('/<th[^>]*>/i', $table)) {
                $violations[] = [
                    'element' => substr($table, 0, 100) . '...',
                    'issue' => 'Data table missing header cells (th)',
                    'impact' => 'serious',
                ];
            }
        }
    }

    return $violations;
}

/**
 * Check for color contrast issues (basic check).
 * Note: Full color contrast checking requires computed styles.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkInlineColorContrast(string $html): array
{
    $violations = [];

    // Check for very light colors on white backgrounds (basic heuristic)
    if (preg_match_all('/style\s*=\s*["\'][^"\']*color\s*:\s*(#[fFeE]{3,6}|white|rgb\s*\(\s*2[45]\d|lightgray|lightgrey)[^"\']*["\']/i', $html, $matches)) {
        foreach ($matches[0] as $style) {
            $violations[] = [
                'element' => $style,
                'issue' => 'Potentially low contrast text color detected',
                'impact' => 'serious',
            ];
        }
    }

    return $violations;
}

// =============================================================================
// MAIN ACCESSIBILITY FUNCTIONS
// =============================================================================

/**
 * Get all accessibility violations from HTML content.
 *
 * @param string $html HTML content to check
 * @param array<string> $checks Specific checks to run (empty for all)
 * @return array<array{element: string, issue: string, impact: string}>
 */
function getAccessibilityViolations(string $html, array $checks = []): array
{
    $allChecks = [
        'images' => 'checkImagesWithoutAlt',
        'inputs' => 'checkInputsWithoutLabels',
        'language' => 'checkDocumentLanguage',
        'title' => 'checkPageTitle',
        'headings' => 'checkHeadingHierarchy',
        'links' => 'checkLinksWithoutText',
        'buttons' => 'checkButtonsWithoutText',
        'landmarks' => 'checkAriaLandmarks',
        'tables' => 'checkTablesWithoutHeaders',
        'contrast' => 'checkInlineColorContrast',
    ];

    $checksToRun = empty($checks) ? array_keys($allChecks) : $checks;
    $violations = [];

    foreach ($checksToRun as $check) {
        if (isset($allChecks[$check])) {
            $function = __NAMESPACE__ . '\\' . $allChecks[$check];
            /** @var callable(string): array<array{element: string, issue: string, impact: string}> $function */
            $violations = array_merge($violations, $function($html));
        }
    }

    return $violations;
}

/**
 * Get accessibility violations filtered by impact level.
 *
 * @param string $html HTML content to check
 * @param string $minImpact Minimum impact level: 'minor', 'moderate', 'serious', 'critical'
 * @return array<array{element: string, issue: string, impact: string}>
 */
function getAccessibilityViolationsByImpact(string $html, string $minImpact = 'serious'): array
{
    $impactLevels = [
        'minor' => 1,
        'moderate' => 2,
        'serious' => 3,
        'critical' => 4,
    ];

    $minLevel = $impactLevels[$minImpact] ?? 3;
    $violations = getAccessibilityViolations($html);

    return array_filter($violations, function ($violation) use ($impactLevels, $minLevel) {
        $level = $impactLevels[$violation['impact']] ?? 0;

        return $level >= $minLevel;
    });
}

/**
 * Check if HTML content has no critical accessibility violations.
 *
 * @param string $html HTML content to check
 * @return bool True if no critical violations found
 */
function isAccessible(string $html): bool
{
    $violations = getAccessibilityViolationsByImpact($html, 'critical');

    return empty($violations);
}

/**
 * Format accessibility violations as a readable report.
 *
 * @param array<array{element: string, issue: string, impact: string}> $violations
 * @return string Formatted report
 */
function formatAccessibilityReport(array $violations): string
{
    if (empty($violations)) {
        return "No accessibility violations found.\n";
    }

    $report = 'Accessibility Violations Found: ' . count($violations) . "\n";
    $report .= str_repeat('=', 50) . "\n\n";

    // Group by impact
    $grouped = [];
    foreach ($violations as $violation) {
        $grouped[$violation['impact']][] = $violation;
    }

    $impactOrder = ['critical', 'serious', 'moderate', 'minor'];

    foreach ($impactOrder as $impact) {
        if (! isset($grouped[$impact])) {
            continue;
        }

        $report .= strtoupper($impact) . ' (' . count($grouped[$impact]) . ")\n";
        $report .= str_repeat('-', 30) . "\n";

        foreach ($grouped[$impact] as $index => $violation) {
            $report .= ($index + 1) . '. ' . $violation['issue'] . "\n";
            $element = strlen($violation['element']) > 80
                ? substr($violation['element'], 0, 80) . '...'
                : $violation['element'];
            $report .= '   Element: ' . $element . "\n\n";
        }
    }

    return $report;
}

// =============================================================================
// WCAG GUIDELINE HELPERS
// =============================================================================

/**
 * Get WCAG 2.1 Level A checks.
 *
 * @return array<string> Check names for Level A compliance
 */
function wcagLevelAChecks(): array
{
    return [
        'images',
        'inputs',
        'language',
        'title',
        'links',
        'buttons',
    ];
}

/**
 * Get WCAG 2.1 Level AA checks.
 *
 * @return array<string> Check names for Level AA compliance
 */
function wcagLevelAAChecks(): array
{
    return array_merge(wcagLevelAChecks(), [
        'headings',
        'contrast',
        'landmarks',
    ]);
}

/**
 * Get WCAG 2.1 Level AAA checks.
 *
 * @return array<string> Check names for Level AAA compliance
 */
function wcagLevelAAAChecks(): array
{
    return array_merge(wcagLevelAAChecks(), [
        'tables',
    ]);
}

/**
 * Check HTML for WCAG Level A compliance.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkWcagLevelA(string $html): array
{
    return getAccessibilityViolations($html, wcagLevelAChecks());
}

/**
 * Check HTML for WCAG Level AA compliance.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkWcagLevelAA(string $html): array
{
    return getAccessibilityViolations($html, wcagLevelAAChecks());
}

/**
 * Check HTML for WCAG Level AAA compliance.
 *
 * @param string $html HTML content to check
 * @return array<array{element: string, issue: string, impact: string}>
 */
function checkWcagLevelAAA(string $html): array
{
    return getAccessibilityViolations($html, wcagLevelAAAChecks());
}
