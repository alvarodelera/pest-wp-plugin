<?php

declare(strict_types=1);

// Pest plugins expose their public helpers and expectations through one
// Composer autoload entrypoint. Bootstrap remains a separate Pest plugin
// concern because it must run once before the test suite starts.
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/bootstrap.php';
