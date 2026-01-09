# PestWP Phase 4: Enhanced Mocking & Fixtures

This document covers the mocking utilities, fixture system, and snapshot testing capabilities added in Phase 4 of PestWP.

## Table of Contents

- [Function Mocking](#function-mocking)
- [Hook Mocking](#hook-mocking)
- [HTTP Mocking](#http-mocking)
- [Time Mocking](#time-mocking)
- [Fixtures](#fixtures)
- [Snapshot Testing](#snapshot-testing)
- [Custom Expectations](#custom-expectations)

## Function Mocking

Mock WordPress functions to control their behavior during tests.

### Basic Usage

```php
use function PestWP\Functions\mockFunction;

test('mocks wp_mail', function () {
    $mock = mockFunction('wp_mail')->andReturn(true);
    
    // Your code that calls wp_mail()
    $result = wp_mail('user@example.com', 'Subject', 'Body');
    
    expect($result)->toBeTrue();
    expect($mock)->toHaveBeenCalled();
});
```

### Return Values

```php
// Fixed return value
mockFunction('get_option')->andReturn('value');

// Dynamic return using callback
mockFunction('get_option')->andReturnUsing(function ($option, $default = false) {
    return match($option) {
        'siteurl' => 'https://example.com',
        'blogname' => 'Test Site',
        default => $default,
    };
});

// Consecutive returns (different value each call)
mockFunction('wp_generate_password')
    ->andReturnConsecutive(['pass1', 'pass2', 'pass3']);

// Convenience methods
mockFunction('is_admin')->andReturnTrue();
mockFunction('is_user_logged_in')->andReturnFalse();
mockFunction('wp_die')->andReturnVoid();
mockFunction('esc_html')->andReturnFirstArg();
mockFunction('get_post_field')->andReturnArg(1); // Return second argument

// Throw exception
mockFunction('wp_remote_get')->andThrow(new \Exception('Network error'));
```

### Call Tracking

```php
$mock = mockFunction('wp_mail')->andReturn(true);

// ... code that calls wp_mail multiple times ...

// Check if called
expect($mock->wasCalled())->toBeTrue();

// Check call count
expect($mock->getCallCount())->toBe(3);

// Check specific arguments
expect($mock->wasCalledWith(['user@example.com', 'Subject', 'Body']))->toBeTrue();

// Get all calls
$calls = $mock->getCalls();
// [['user@example.com', 'Subject', 'Body'], [...], [...]]

// Get specific call
$firstCall = $mock->getCall(0);
$lastCall = $mock->getLastCall();

// Check with callback
$mock->wasCalledWithMatching(fn($args) => str_contains($args[0], '@example.com'));
```

### Expectations

```php
// Expect exact number of calls
mockFunction('wp_mail')->times(3)->andReturn(true);
// ... must be called exactly 3 times, verify() throws otherwise

// Expect exactly one call
mockFunction('wp_delete_post')->once()->andReturn(true);

// Expect exactly two calls
mockFunction('update_option')->twice()->andReturn(true);

// Expect never called
mockFunction('wp_die')->never();

// Expect minimum calls
mockFunction('get_option')->atLeast(2)->andReturn('value');

// Expect maximum calls
mockFunction('update_option')->atMost(5)->andReturn(true);

// Manual verification
$mock = mockFunction('wp_mail')->times(2)->andReturn(true);
// ... code ...
$mock->verify(); // Throws if not called exactly 2 times
```

### Mock Control

```php
$mock = mockFunction('wp_mail')->andReturn(true);

// Temporarily disable
$mock->disable();
// Now wp_mail() calls the real function

// Re-enable
$mock->enable();

// Reset call history (keep behavior)
$mock->reset();

// Completely remove mock
$mock->restore();

// Clear all function mocks
clearFunctionMocks();
```

## Hook Mocking

Intercept and mock WordPress hooks (actions and filters).

### Basic Usage

```php
use function PestWP\Functions\mockHook;

test('captures init action callbacks', function () {
    $captured = [];
    $mock = mockHook('init')->capture($captured);
    
    // Simulate WordPress registering a callback
    $mock->recordCallback(fn() => 'init action', 10);
    
    expect($captured)->toHaveCount(1);
    expect($captured[0]['priority'])->toBe(10);
});
```

### Hook Types

```php
// Default is filter
$mock = mockHook('the_content');

// Explicitly set as action
$mock = mockHook('init')->action();

// Explicitly set as filter
$mock = mockHook('the_title')->filter();
```

### Filter Overrides

```php
// Override filter return value
$mock = mockHook('the_content')->andReturn('<p>Mocked content</p>');

// Get the overridden value
$value = $mock->getFilterValue('Original content', []);
expect($value)->toBe('<p>Mocked content</p>');

// Dynamic override
mockHook('the_title')->andReturnUsing(function ($title, ...$args) {
    return strtoupper($title);
});

// Remove override (passthrough)
$mock->andPassthrough();
```

### Execution Tracking

```php
$mock = mockHook('save_post')->action();

// Record that the hook was executed
$mock->recordExecution([123, get_post(123), true]);

// Check if executed
expect($mock->wasCalled())->toBeTrue();
expect($mock->getCallCount())->toBe(1);

// Check arguments
expect($mock->wasCalledWith([123]))->toBeTrue(); // Partial match

// Get call details
$lastCall = $mock->getLastCall();
expect($lastCall[0])->toBe(123); // Post ID
```

### Callback Capture

```php
$callbacks = [];
$mock = mockHook('admin_init')->capture($callbacks);

// Simulate plugin registering callbacks
$mock->recordCallback('my_admin_init_function', 10);
$mock->recordCallback(['MyClass', 'method'], 20);
$mock->recordCallback(fn() => 'closure', 5);

expect($callbacks)->toHaveCount(3);

// Get callbacks sorted by priority
$sorted = $mock->getCallbacks();
// Closure (5), my_admin_init_function (10), ['MyClass', 'method'] (20)
```

## HTTP Mocking

Mock HTTP requests made via `wp_remote_get`, `wp_remote_post`, etc.

### Basic Usage

```php
use function PestWP\Functions\mockHTTP;

test('mocks API request', function () {
    mockHTTP()
        ->whenUrl('https://api.example.com/users')
        ->andReturn(['users' => [['id' => 1, 'name' => 'John']]]);
    
    // Code that makes HTTP request
    $response = wp_remote_get('https://api.example.com/users');
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    expect($body['users'])->toHaveCount(1);
});
```

### Response Types

```php
// Simple response
mockHTTP()
    ->whenUrl('https://api.example.com/status')
    ->andReturn(['status' => 'ok']);

// With status code
mockHTTP()
    ->whenUrl('https://api.example.com/error')
    ->andReturn(['error' => 'Not found'], 404);

// With headers
mockHTTP()
    ->whenUrl('https://api.example.com/data')
    ->andReturn($data, 200, ['X-Custom-Header' => 'value']);

// JSON response (sets Content-Type header)
mockHTTP()
    ->whenUrl('https://api.example.com/json')
    ->andReturnJson(['data' => 'value']);

// Error response (WP_Error)
mockHTTP()
    ->whenUrl('https://api.example.com/fail')
    ->andReturnError('http_request_failed', 'Connection timeout');

// Dynamic response
mockHTTP()
    ->whenUrl('https://api.example.com/user/*')
    ->andReturnUsing(function ($url, $args) {
        $id = basename($url);
        return ['id' => $id, 'name' => "User {$id}"];
    });
```

### URL Pattern Matching

```php
// Exact match
mockHTTP()->whenUrl('https://api.example.com/users')->andReturn([]);

// Wildcard pattern
mockHTTP()->whenUrl('https://api.example.com/*')->andReturn([]);
mockHTTP()->whenUrl('https://*.example.com/api/*')->andReturn([]);
```

### Request Limits

```php
// Respond only once
mockHTTP()
    ->whenUrl('https://api.example.com/token')
    ->once()
    ->andReturn(['token' => 'abc123']);

// Respond N times
mockHTTP()
    ->whenUrl('https://api.example.com/poll')
    ->times(3)
    ->andReturn(['status' => 'pending']);
```

### Default Responses

```php
// Set default for unmatched URLs
mockHTTP()->default(['error' => 'Not mocked'], 500);

// Block all unmatched requests
mockHTTP()->blockUnmatched();

// Allow unmatched (pass through to real HTTP)
mockHTTP()->allowUnmatched();
```

### Request History

```php
// Make some requests
$http = mockHTTP()->whenUrl('*')->andReturn([]);

// ... code makes requests ...

// Check history
$requests = $http->getRequests();
// [['url' => '...', 'args' => [...], 'response' => [...]], ...]

$count = $http->getRequestCount();

// Check if specific URL was requested
expect($http->wasRequested('https://api.example.com/users'))->toBeTrue();

// Check pattern
expect($http->wasRequestedMatching('https://api.example.com/*'))->toBeTrue();
```

### Mock Control

```php
$http = mockHTTP();

// Disable mocking (real requests go through)
$http->disable();

// Re-enable
$http->enable();

// Reset all state
$http->reset();

// Clear via helper
clearHTTPMocks();
```

## Time Mocking

Freeze and manipulate time during tests.

### Basic Usage

```php
use function PestWP\Functions\freezeTime;
use function PestWP\Functions\unfreezeTime;

test('freezes time', function () {
    $time = freezeTime('2024-01-15 10:30:00');
    
    expect($time->getTimestamp())->toBe(strtotime('2024-01-15 10:30:00'));
    expect($time->format('Y-m-d'))->toBe('2024-01-15');
    
    unfreezeTime();
});
```

### Freezing Methods

```php
use function PestWP\Functions\mockTime;

// Freeze at specific time
$time = mockTime()->freeze('2024-01-15 10:30:00');

// Freeze at timestamp
$time = mockTime()->freeze(1705315800);

// Freeze at DateTime
$time = mockTime()->freeze(new DateTime('2024-01-15'));

// Freeze at now
$time = mockTime()->now();

// Freeze at start of today
$time = mockTime()->today();

// Freeze at start of yesterday
$time = mockTime()->yesterday();

// Freeze at start of tomorrow
$time = mockTime()->tomorrow();

// Travel to time (alias for freeze)
$time = mockTime()->travelTo('2024-12-25 00:00:00');
```

### Time Advancement

```php
$time = freezeTime('2024-01-15 10:00:00');

// Advance by relative string
$time->advance('+1 hour');
$time->advance('+30 minutes');
$time->advance('+2 days');

// Convenience methods
$time->advanceSeconds(30);
$time->advanceMinutes(15);
$time->advanceHours(2);
$time->advanceDays(7);
$time->advanceWeeks(2);
$time->advanceMonths(1);

// Go back in time
$time->rewind('1 hour');    // Goes back 1 hour
$time->rewind('-2 days');   // Also goes back 2 days
```

### Formatting

```php
$time = freezeTime('2024-01-15 10:30:00');

// Custom format
$time->format('Y-m-d H:i:s');  // "2024-01-15 10:30:00"

// WordPress/MySQL format
$time->toMySql();     // "2024-01-15 10:30:00"
$time->toWordPress(); // "2024-01-15 10:30:00"

// ISO 8601
$time->toIso8601();   // "2024-01-15T10:30:00+00:00"

// Date and time parts
$time->toDate();      // "2024-01-15"
$time->toTime();      // "10:30:00"
```

### Date/Time Components

```php
$time = freezeTime('2024-01-15 10:30:45');

$time->getYear();       // 2024
$time->getMonth();      // 1
$time->getDay();        // 15
$time->getHour();       // 10
$time->getMinute();     // 30
$time->getSecond();     // 45
$time->getDayOfWeek();  // 1 (Monday)
$time->getTimestamp();  // 1705315845
$time->getDateTime();   // DateTimeImmutable
```

### Time Checks

```php
$time = freezeTime('2024-01-15'); // Tuesday

$time->isWeekend();   // false
$time->isWeekday();   // true
$time->isPast();      // Compared to real time
$time->isFuture();    // Compared to real time
$time->isToday();     // Compared to real date
```

### Ticking Time

```php
// Freeze time but let it tick naturally
$time = mockTime()->freeze('2024-01-15 10:00:00')->tick();

// Now getTimestamp() advances naturally from frozen point
sleep(2);
// Timestamp is now 2 seconds ahead

// Stop ticking
$time->stopTicking();
```

### Timezone

```php
$time = mockTime()
    ->setTimezone('America/New_York')
    ->freeze('2024-01-15 10:00:00');

$tz = $time->getTimezone(); // DateTimeZone
```

## Fixtures

Database seeding and fixture management.

### Basic Usage

```php
use function PestWP\Functions\fixtures;

test('uses fixtures', function () {
    fixtures()
        ->define([
            'users' => [
                'admin' => ['login' => 'admin', 'role' => 'administrator'],
                'editor' => ['login' => 'editor', 'role' => 'editor'],
            ],
            'posts' => [
                ['title' => 'Hello World', 'status' => 'publish'],
                ['title' => 'Draft Post', 'status' => 'draft'],
            ],
        ])
        ->seed();
    
    // Access fixtures
    $admin = fixtures()->get('users.admin');
    $posts = fixtures()->get('posts');
    
    expect($admin)->not->toBeNull();
    expect($posts)->toHaveCount(2);
});
```

### Loading from Files

```php
// Load from JSON file
fixtures()->load('users.json')->seed();

// Load from YAML file (requires yaml extension or symfony/yaml)
fixtures()->load('posts.yaml')->seed();

// Load from PHP file (must return array)
fixtures()->load('comments.php')->seed();

// Set fixtures path
fixtures()->setPath('/path/to/fixtures');

// Multiple files
fixtures()
    ->load('users.json')
    ->load('posts.yaml')
    ->seed();
```

### Built-in Types

```php
fixtures()->define([
    // Users
    'users' => [
        ['login' => 'testuser', 'email' => 'test@example.com', 'role' => 'subscriber'],
    ],
    
    // Posts
    'posts' => [
        ['title' => 'Test Post', 'content' => 'Content', 'status' => 'publish', 'type' => 'post'],
    ],
    
    // Pages
    'pages' => [
        ['title' => 'About Us', 'content' => 'About page content'],
    ],
    
    // Terms
    'terms' => [
        ['name' => 'Technology', 'taxonomy' => 'category'],
        ['name' => 'news', 'taxonomy' => 'post_tag'],
    ],
    
    // Comments
    'comments' => [
        ['content' => 'Great post!', 'author' => 'John', 'post_id' => 1],
    ],
    
    // Options
    'options' => [
        ['name' => 'my_plugin_setting', 'value' => 'enabled'],
    ],
    
    // Transients
    'transients' => [
        ['name' => 'my_cache', 'value' => ['data'], 'expiration' => 3600],
    ],
])->seed();
```

### Custom Factories

```php
fixtures()
    ->factory('products', function (array $data) {
        // Custom creation logic
        return create_product([
            'name' => $data['name'] ?? 'Test Product',
            'price' => $data['price'] ?? 9.99,
            'sku' => $data['sku'] ?? 'TEST-' . uniqid(),
        ]);
    })
    ->define([
        'products' => [
            ['name' => 'Widget', 'price' => 19.99],
            ['name' => 'Gadget', 'price' => 29.99],
        ],
    ])
    ->seed();
```

### Accessing Fixtures

```php
fixtures()->seed();

// Get by type and key
$admin = fixtures()->get('users.admin');

// Get by type and numeric index
$firstPost = fixtures()->get('posts.0');

// Get all of a type
$allUsers = fixtures()->get('users');

// Check existence
if (fixtures()->has('users.admin')) {
    // ...
}

// Get everything
$all = fixtures()->all();
```

### Cleanup

```php
// Automatic cleanup on test teardown (when using singleton)
fixtures()->cleanup();

// Reset definitions without cleanup
fixtures()->reset();

// Reset singleton entirely
FixtureManager::resetInstance();
```

## Snapshot Testing

Compare output against stored snapshots.

### Basic Usage

```php
test('matches snapshot', function () {
    $html = '<div class="widget"><h2>Title</h2><p>Content</p></div>';
    
    expect($html)->toMatchSnapshot();
    
    // First run: Creates snapshot file
    // Subsequent runs: Compares against stored snapshot
});
```

### Named Snapshots

```php
test('creates multiple snapshots', function () {
    expect($output1)->toMatchSnapshot('header-output');
    expect($output2)->toMatchSnapshot('footer-output');
});
```

### JSON Snapshots

```php
test('API response matches snapshot', function () {
    $data = [
        'status' => 'success',
        'users' => [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ],
    ];
    
    expect($data)->toMatchJsonSnapshot();
});
```

### HTML Snapshots

```php
test('rendered HTML matches snapshot', function () {
    $html = render_template('header');
    
    // Normalizes whitespace before comparing
    expect($html)->toMatchHtmlSnapshot();
});
```

### Updating Snapshots

When your expected output changes, update snapshots:

```bash
# Update all snapshots
PEST_UPDATE_SNAPSHOTS=1 php vendor/bin/pest

# Or on Windows
set PEST_UPDATE_SNAPSHOTS=1 && php vendor/bin/pest
```

### Snapshot Locations

Snapshots are stored in `tests/__snapshots__/` by default:

```
tests/
├── __snapshots__/
│   ├── MyFeatureTest__it_renders_correctly.snap
│   ├── MyFeatureTest__it_renders_correctly.json
│   └── MyFeatureTest__it_renders_correctly.html
└── Feature/
    └── MyFeatureTest.php
```

### Configuration

```php
use PestWP\Snapshot\SnapshotManager;

// Set custom path
snapshots()->setPath('/custom/path/__snapshots__');

// Enable update mode programmatically
snapshots()->enableUpdate();

// Check mode
if (snapshots()->isUpdateMode()) {
    // ...
}
```

## Custom Expectations

PestWP adds custom Pest expectations for mocking.

### Function Mock Expectations

```php
$mock = mockFunction('wp_mail')->andReturn(true);

// ... code ...

expect($mock)->toHaveBeenCalled();
expect($mock)->toHaveBeenCalledTimes(3);
expect($mock)->toHaveBeenCalledWith(['user@example.com', 'Subject', 'Body']);
expect($mock)->toHaveNotBeenCalled(); // Or: ->not->toHaveBeenCalled()
```

### HTTP Mock Expectations

```php
$http = mockHTTP()->whenUrl('*')->andReturn([]);

// ... code ...

expect($http)->toHaveRequested('https://api.example.com/users');
expect($http)->toHaveRequestCount(3);
```

### Time Expectations

```php
$created = strtotime('2024-01-01');
$updated = strtotime('2024-01-15');

expect($created)->toBeBefore($updated);
expect($updated)->toBeAfter($created);

// Also works with DateTime objects
expect(new DateTime('2024-01-01'))->toBeBefore(new DateTime('2024-01-15'));
```

### Snapshot Expectations

```php
expect($html)->toMatchSnapshot();
expect($data)->toMatchJsonSnapshot();
expect($html)->toMatchHtmlSnapshot();
```

## Helper Functions Reference

```php
use function PestWP\Functions\{
    // Function mocking
    mockFunction,
    clearFunctionMocks,
    isFunctionMocked,
    
    // Hook mocking
    mockHook,
    clearHookMocks,
    isHookMocked,
    
    // HTTP mocking
    mockHTTP,
    clearHTTPMocks,
    
    // Time mocking
    mockTime,
    freezeTime,
    unfreezeTime,
    isTimeFrozen,
    mockedTime,
    mockedDateTime,
    
    // Fixtures
    fixtures,
    
    // Snapshots
    snapshots,
    
    // Clear all
    clearMocks,
};
```

## Best Practices

### 1. Clean Up After Tests

```php
afterEach(function () {
    clearMocks(); // Clears all function, hook, and HTTP mocks
    unfreezeTime();
    fixtures()->cleanup();
});
```

### 2. Use Specific Assertions

```php
// Good: Specific expectations
expect($mock)->toHaveBeenCalledWith(['expected', 'args']);
expect($mock)->toHaveBeenCalledTimes(2);

// Less good: Only checking if called
expect($mock)->toHaveBeenCalled();
```

### 3. Mock at the Right Level

```php
// Mock WordPress functions, not your own code
mockFunction('wp_mail')->andReturn(true);
mockFunction('get_option')->andReturn('value');

// Let your actual code run against the mocks
$result = my_plugin_send_notification();
```

### 4. Use Fixtures for Complex Data

```php
// Instead of inline data
$user = wp_insert_user([...]);
$post = wp_insert_post([...]);

// Use fixtures
fixtures()->define([...])->seed();
$user = fixtures()->get('users.admin');
```

### 5. Snapshot Judiciously

```php
// Good: Stable, meaningful output
expect($api_response)->toMatchJsonSnapshot();

// Avoid: Volatile data (timestamps, IDs)
// expect($response_with_timestamp)->toMatchSnapshot(); // Will fail often
```
