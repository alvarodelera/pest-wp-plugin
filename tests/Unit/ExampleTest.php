<?php

declare(strict_types=1);

namespace PestWP\Tests\Unit;

it('returns the correct version', function (): void {
    expect(\PestWP\version())->toBe('1.0.0-dev');
});
