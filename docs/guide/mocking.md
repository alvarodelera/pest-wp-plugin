# Mocking

PestWP provides powerful mocking capabilities for functions, hooks, HTTP requests, and time.

## Function Mocking

Mock WordPress functions to control their behavior in tests.

### mockFunction()

```php
use function PestWP\Functions\mockFunction;

// Mock wp_mail to always return true
$mock = mockFunction('wp_mail')->andReturn(true);

// Call the function
$result = wp_mail('test@example.com', 'Subject', 'Message');

expect($result)->toBeTrue();
expect($mock)->toHaveBeenCalled();
```

### Return Values

```php
// Return a static value
mockFunction('get_option')->andReturn('my_value');

// Return using a callback
mockFunction('get_option')->andReturnUsing(function ($option, $default = false) {
    return match($option) {
        'siteurl' => 'https://example.com',
        'blogname' => 'Test Site',
        default => $default,
    };
});

// Return different values on each call
mockFunction('wp_generate_password')
    ->andReturn('first_password')
    ->andReturn('second_password');
```

### Assertions

```php
$mock = mockFunction('wp_mail')->andReturn(true);

// Send some emails
wp_mail('user1@example.com', 'Hello', 'Body');
wp_mail('user2@example.com', 'Hi', 'Content');

// Assert calls
expect($mock)->toHaveBeenCalled();
expect($mock)->toHaveBeenCalledTimes(2);
expect($mock)->toHaveBeenCalledWith(['user1@example.com', 'Hello', 'Body']);
expect($mock)->not->toHaveBeenCalledWith(['unknown@example.com']);
```

### Cleanup

```php
use function PestWP\Functions\clearFunctionMocks;
use function PestWP\Functions\clearMocks;

afterEach(function () {
    // Clear specific mocks
    clearFunctionMocks();
    
    // Or clear all mocks (functions, hooks, HTTP, time)
    clearMocks();
});
```

## Hook Mocking

Mock WordPress actions and filters.

### mockHook()

```php
use function PestWP\Functions\mockHook;

// Spy on filter execution
$mock = mockHook('the_content')->spy();

// Apply the filter
$result = apply_filters('the_content', 'Hello World');

expect($mock)->toHaveBeenCalled();
expect($mock)->toHaveBeenCalledWith(['Hello World']);
```

### Override Filter Results

```php
// Override filter return value
mockHook('the_title')->andReturn('Mocked Title');

$title = apply_filters('the_title', 'Original Title');
expect($title)->toBe('Mocked Title');

// Dynamic return
mockHook('the_content')->andReturnUsing(function ($content) {
    return strtoupper($content);
});

$content = apply_filters('the_content', 'hello');
expect($content)->toBe('HELLO');
```

### Capture Callbacks

```php
// Capture callbacks registered to a hook
$mock = mockHook('init')->capture($callbacks);

// Trigger the action
do_action('init');

// $callbacks contains all registered callbacks
expect($mock)->toHaveBeenCalled();
```

### Clear Hook Mocks

```php
use function PestWP\Functions\clearHookMocks;

afterEach(function () {
    clearHookMocks();
});
```

## HTTP Mocking

Mock external HTTP requests made with `wp_remote_*` functions.

### mockHTTP()

```php
use function PestWP\Functions\mockHTTP;

// Mock a specific URL
mockHTTP()
    ->whenUrl('https://api.example.com/users')
    ->andReturn(['users' => ['john', 'jane']]);

// Make the request
$response = wp_remote_get('https://api.example.com/users');
$body = json_decode(wp_remote_retrieve_body($response), true);

expect($body['users'])->toBe(['john', 'jane']);
```

### Pattern Matching

```php
// Match any URL with wildcard
mockHTTP()
    ->whenUrl('https://api.example.com/*')
    ->andReturn(['status' => 'ok']);

// Match specific pattern
mockHTTP()
    ->whenUrl('https://*.example.com/api')
    ->andReturn(['data' => 'mocked']);
```

### Response Configuration

```php
// Return with specific status
mockHTTP()
    ->whenUrl('https://api.example.com/error')
    ->andReturn(['error' => 'Not found'], 404);

// Return with headers
mockHTTP()
    ->whenUrl('https://api.example.com/data')
    ->andReturn(['data' => 'value'])
    ->withHeaders(['X-Custom-Header' => 'value']);
```

### Block Unmocked Requests

```php
// Block all requests not explicitly mocked
mockHTTP()->blockUnmatched();

// Trying to access unmocked URL throws exception
$response = wp_remote_get('https://unmocked-url.com');
// Throws: Unmocked HTTP request to https://unmocked-url.com
```

### HTTP Assertions

```php
$http = mockHTTP()
    ->whenUrl('https://api.example.com/users')
    ->andReturn(['users' => []]);

// Make requests
wp_remote_get('https://api.example.com/users');
wp_remote_get('https://api.example.com/users');

// Assert
expect($http)->toHaveRequested('https://api.example.com/users');
expect($http)->toHaveRequestCount(2);
```

### Clear HTTP Mocks

```php
use function PestWP\Functions\clearHTTPMocks;

afterEach(function () {
    clearHTTPMocks();
});
```

## Time Mocking

Freeze or control time in tests.

### mockTime()

```php
use function PestWP\Functions\mockTime;
use function PestWP\Functions\freezeTime;

// Freeze time at specific moment
$time = mockTime()->freeze('2024-01-15 10:30:00');

// Or use shorthand
freezeTime('2024-01-15 10:30:00');

// All time functions return frozen time
expect(time())->toBe(strtotime('2024-01-15 10:30:00'));
expect(current_time('timestamp'))->toBe(strtotime('2024-01-15 10:30:00'));
```

### Advance Time

```php
$time = freezeTime('2024-01-15 10:30:00');

// Get timestamp
$initial = $time->getTimestamp();

// Advance time
$time->advance('+1 hour');
expect($time->getTimestamp())->toBe($initial + 3600);

// Advance by specific units
$time->advanceSeconds(30);
$time->advanceMinutes(5);
$time->advanceHours(2);
$time->advanceDays(1);
$time->advanceWeeks(1);
$time->advanceMonths(1);
```

### Get Mocked Time

```php
use function PestWP\Functions\mockedTime;
use function PestWP\Functions\mockedDateTime;
use function PestWP\Functions\isTimeFrozen;

freezeTime('2024-01-15 10:30:00');

// Check if frozen
expect(isTimeFrozen())->toBeTrue();

// Get timestamp
$timestamp = mockedTime();

// Get DateTime object
$datetime = mockedDateTime();
expect($datetime->format('Y-m-d'))->toBe('2024-01-15');
```

### Time Assertions

```php
$createdAt = strtotime('2024-01-14');
$updatedAt = strtotime('2024-01-15');

expect($createdAt)->toBeBefore($updatedAt);
expect($updatedAt)->toBeAfter($createdAt);

// With DateTimeInterface
$date1 = new DateTime('2024-01-14');
$date2 = new DateTime('2024-01-15');

expect($date1)->toBeBefore($date2);
```

### Unfreeze Time

```php
use function PestWP\Functions\unfreezeTime;

freezeTime('2024-01-15');

// ... run tests ...

unfreezeTime(); // Time returns to normal

expect(isTimeFrozen())->toBeFalse();
```

## Complete Example

```php
use function PestWP\Functions\mockFunction;
use function PestWP\Functions\mockHTTP;
use function PestWP\Functions\freezeTime;
use function PestWP\Functions\clearMocks;

describe('notification system', function () {
    beforeEach(function () {
        $this->mailMock = mockFunction('wp_mail')->andReturn(true);
        
        mockHTTP()
            ->whenUrl('https://push-service.example.com/*')
            ->andReturn(['success' => true]);
        
        freezeTime('2024-01-15 09:00:00');
    });
    
    afterEach(function () {
        clearMocks();
    });
    
    it('sends email notification', function () {
        sendNotification('user@example.com', 'Hello!');
        
        expect($this->mailMock)->toHaveBeenCalled();
        expect($this->mailMock)->toHaveBeenCalledWith([
            'user@example.com',
            'Notification',
            'Hello!',
        ]);
    });
    
    it('sends push notification', function () {
        $http = mockHTTP();
        
        sendPushNotification('device_token', 'Hello!');
        
        expect($http)->toHaveRequested('https://push-service.example.com/send');
    });
    
    it('schedules notification for later', function () {
        scheduleNotification('user@example.com', '+1 hour');
        
        // Advance time
        mockTime()->advance('+1 hour');
        
        // Process scheduled notifications
        processScheduledNotifications();
        
        expect($this->mailMock)->toHaveBeenCalled();
    });
});
```

## Best Practices

1. **Always Clean Up**: Clear mocks in `afterEach()` to prevent leaks

2. **Mock Only What's Needed**: Don't over-mock; test real behavior when possible

3. **Use Specific URLs**: Prefer exact URL matching over wildcards

4. **Verify Call Counts**: Ensure functions are called the expected number of times

5. **Group Related Mocks**: Use `describe()` blocks to organize mock setup

## Next Steps

- [Fixtures](fixtures.md) - Reusable test data
- [Snapshots](snapshots.md) - Snapshot testing
- [HTTP Mocking Details](../MOCKING_FIXTURES.md) - Advanced HTTP mocking
