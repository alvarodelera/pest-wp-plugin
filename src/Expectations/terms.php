<?php

declare(strict_types=1);

/**
 * Term and taxonomy-related custom expectations for WordPress.
 */

namespace PestWP\Expectations;

use function expect;
use function test;

/**
 * Register term expectations.
 */
function registerTermExpectations(): void
{
    expect()->extend('toHaveTerm', function ($term, string $taxonomy) {
        $post = $this->value;

        if (! $post instanceof \WP_Post) {
            test()->fail('Expected value to be a WP_Post instance.');
        }

        $hasTerm = has_term($term, $taxonomy, $post);

        expect($hasTerm)->toBeTrue("Expected post #{$post->ID} to have term '{$term}' in taxonomy '{$taxonomy}'");

        return $this;
    });

    expect()->extend('toBeRegisteredTaxonomy', function () {
        $taxonomy = $this->value;

        if (! is_string($taxonomy)) {
            test()->fail('Expected value to be a taxonomy (string).');
        }

        expect(taxonomy_exists($taxonomy))->toBeTrue("Expected taxonomy '{$taxonomy}' to be registered");

        return $this;
    });
}
