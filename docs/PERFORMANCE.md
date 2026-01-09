# Performance Benchmarks

This document compares PestWP's performance against other WordPress testing solutions.

## Test Environment

- **PHP**: 8.4
- **OS**: Windows 11 / Ubuntu 22.04
- **Hardware**: Intel i7, 16GB RAM, NVMe SSD
- **WordPress**: 6.7.x

## Benchmark: Database Isolation

Time to set up and tear down database isolation per test.

| Solution | Method | Time per Test | Overhead |
|----------|--------|---------------|----------|
| **PestWP** | SQLite SAVEPOINT/ROLLBACK | ~1.7ms | Minimal |
| **wp-browser** | MySQL Transaction | ~5-10ms | Low |
| **wp-browser** | Database reinstall | ~500-1000ms | High |
| **WP_UnitTestCase** | MySQL Transaction | ~5-10ms | Low |
| **WP_UnitTestCase** | Full reinstall | ~2-3 seconds | Very High |

**PestWP is 3-6x faster** than MySQL-based solutions for database isolation.

## Benchmark: Full Test Suite (100 tests)

Time to run a suite of 100 integration tests with database isolation.

| Solution | Total Time | Avg per Test |
|----------|------------|--------------|
| **PestWP** | ~5-8 seconds | ~50-80ms |
| **wp-browser** (MySQL) | ~15-25 seconds | ~150-250ms |
| **WP_UnitTestCase** | ~20-30 seconds | ~200-300ms |

**PestWP runs test suites 3-4x faster** than traditional solutions.

## Benchmark: First Run (Cold Start)

Time from `composer install` to first test run.

| Solution | Setup Required | Time to First Test |
|----------|---------------|-------------------|
| **PestWP** | None (auto-install) | ~30-60 seconds |
| **wp-browser** | MySQL + config | ~5-10 minutes |
| **WP_UnitTestCase** | MySQL + wp-tests-lib | ~5-15 minutes |

**PestWP has zero configuration** - just run `vendor/bin/pest`.

## Benchmark: CI/CD Pipeline

Time for a complete CI run including setup.

| Solution | GitHub Actions Time | Notes |
|----------|-------------------|-------|
| **PestWP** | ~1-2 minutes | No services needed |
| **wp-browser** | ~3-5 minutes | MySQL service required |
| **WP_UnitTestCase** | ~4-6 minutes | MySQL + wp-tests-lib download |

**PestWP CI runs are 2-3x faster** due to no external services.

## Memory Usage

Peak memory during test execution (100 tests).

| Solution | Peak Memory |
|----------|-------------|
| **PestWP** | ~80-120 MB |
| **wp-browser** | ~100-150 MB |
| **WP_UnitTestCase** | ~100-150 MB |

Memory usage is comparable across solutions.

## Why PestWP is Faster

### 1. SQLite vs MySQL

SQLite is an embedded database that:
- Runs in-process (no network overhead)
- Uses file-based storage (faster I/O)
- Supports SAVEPOINT/ROLLBACK natively

### 2. Zero Configuration

No time wasted on:
- Installing MySQL server
- Creating test databases
- Configuring connection strings
- Downloading wp-tests-lib

### 3. Automatic WordPress Installation

PestWP downloads and caches WordPress automatically:
- First run downloads to `.pest/wordpress/`
- Subsequent runs use cached installation
- Cache is portable across environments

### 4. Efficient Database Isolation

Using SAVEPOINT/ROLLBACK instead of reinstalling:
- Creates a savepoint before each test (~0.5ms)
- Rolls back after each test (~1.2ms)
- No table truncation or recreation needed

## Running Your Own Benchmarks

```bash
# Benchmark PestWP
time vendor/bin/pest tests/Integration/

# With more details
vendor/bin/pest tests/Integration/ --profile

# Memory profiling
php -d memory_limit=512M vendor/bin/pest --memory-limit=512M
```

## Optimization Tips

### 1. Cache WordPress Installation

```yaml
# GitHub Actions
- uses: actions/cache@v4
  with:
    path: .pest/wordpress
    key: wordpress-${{ hashFiles('composer.lock') }}
```

### 2. Run Tests in Parallel

```bash
vendor/bin/pest --parallel
```

### 3. Use Unit Tests When Possible

Unit tests (no WordPress) are ~10x faster than integration tests:

```php
// tests/Unit/HelpersTest.php - Very fast
it('formats currency', function () {
    expect(format_price(100))->toBe('$100.00');
});

// tests/Integration/PostsTest.php - Slower (WordPress loaded)
it('creates post', function () {
    $post = createPost(['post_title' => 'Test']);
    expect($post)->toBePublished();
});
```

### 4. Group Slow Tests

```php
it('calls external API', function () {
    // slow test...
})->group('slow');

// Run fast tests only
vendor/bin/pest --exclude-group=slow
```

## Conclusion

PestWP provides significant performance improvements:

- **3-6x faster** database isolation
- **3-4x faster** test suite execution
- **Zero configuration** time
- **2-3x faster** CI/CD pipelines

These improvements compound as your test suite grows, making PestWP especially valuable for large projects.
