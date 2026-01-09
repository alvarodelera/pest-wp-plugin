# CI/CD Integration

Configure PestWP to run in continuous integration environments.

## GitHub Actions

### Basic Setup

```yaml
# .github/workflows/test.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, dom, pdo_sqlite
          coverage: xdebug
          
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
        
      - name: Run tests
        run: ./vendor/bin/pest
```

### With Browser Tests

```yaml
name: Tests

on: [push, pull_request]

jobs:
  unit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          
      - name: Install dependencies
        run: composer install
        
      - name: Run unit tests
        run: ./vendor/bin/pest tests/Unit

  browser:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          
      - name: Install dependencies
        run: composer install
        
      - name: Install Playwright
        run: npx playwright install chromium --with-deps
        
      - name: Start WordPress server
        run: |
          ./vendor/bin/pest-wp-serve &
          sleep 5
          
      - name: Run browser tests
        run: ./vendor/bin/pest tests/Browser
```

### Matrix Testing

Test across multiple PHP versions:

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.3', '8.4']
        
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP ${{ matrix.php }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          
      - name: Install dependencies
        run: composer install
        
      - name: Run tests
        run: ./vendor/bin/pest
```

### With Coverage

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: xdebug
          
      - name: Install dependencies
        run: composer install
        
      - name: Run tests with coverage
        run: ./vendor/bin/pest --coverage --coverage-clover=coverage.xml
        
      - name: Upload coverage to Codecov
        uses: codecov/codecov-action@v3
        with:
          files: coverage.xml
```

## GitLab CI

### Basic Setup

```yaml
# .gitlab-ci.yml
stages:
  - test

test:
  image: php:8.3
  stage: test
  before_script:
    - apt-get update && apt-get install -y git unzip
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install
  script:
    - ./vendor/bin/pest
```

### With Browser Tests

```yaml
test:unit:
  image: php:8.3
  stage: test
  script:
    - composer install
    - ./vendor/bin/pest tests/Unit

test:browser:
  image: mcr.microsoft.com/playwright:v1.40.0-focal
  stage: test
  script:
    - apt-get update && apt-get install -y php8.3 php8.3-cli
    - composer install
    - ./vendor/bin/pest-wp-serve &
    - sleep 5
    - ./vendor/bin/pest tests/Browser
```

### Matrix Testing

```yaml
.test_template: &test_template
  stage: test
  script:
    - composer install
    - ./vendor/bin/pest

test:php83:
  <<: *test_template
  image: php:8.3

test:php84:
  <<: *test_template
  image: php:8.4
```

## CircleCI

```yaml
# .circleci/config.yml
version: 2.1

jobs:
  test:
    docker:
      - image: cimg/php:8.3
    steps:
      - checkout
      - run: composer install
      - run: ./vendor/bin/pest

workflows:
  test:
    jobs:
      - test
```

## Bitbucket Pipelines

```yaml
# bitbucket-pipelines.yml
pipelines:
  default:
    - step:
        name: Run Tests
        image: php:8.3
        caches:
          - composer
        script:
          - apt-get update && apt-get install -y git unzip
          - curl -sS https://getcomposer.org/installer | php
          - php composer.phar install
          - ./vendor/bin/pest
```

## Best Practices

### 1. Separate Test Types

Run different test types as separate jobs:

```yaml
jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - run: composer install
      - run: ./vendor/bin/pint --test

  analyse:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - run: composer install
      - run: ./vendor/bin/phpstan analyse

  test:unit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - run: composer install
      - run: ./vendor/bin/pest tests/Unit

  test:integration:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - run: composer install
      - run: ./vendor/bin/pest tests/Integration

  test:browser:
    runs-on: ubuntu-latest
    needs: [test:unit, test:integration]
    steps:
      - uses: actions/checkout@v4
      - run: composer install
      - run: npx playwright install chromium --with-deps
      - run: ./vendor/bin/pest tests/Browser
```

### 2. Cache Dependencies

```yaml
- name: Get Composer cache directory
  id: composer-cache
  run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

- uses: actions/cache@v3
  with:
    path: ${{ steps.composer-cache.outputs.dir }}
    key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
    restore-keys: ${{ runner.os }}-composer-
```

### 3. Fail Fast

Stop on first failure to save time:

```yaml
strategy:
  fail-fast: true
  matrix:
    php: ['8.3', '8.4']
```

### 4. Upload Artifacts on Failure

Save screenshots and logs when tests fail:

```yaml
- name: Upload screenshots on failure
  if: failure()
  uses: actions/upload-artifact@v3
  with:
    name: screenshots
    path: tests/__screenshots__/
    retention-days: 7

- name: Upload logs on failure
  if: failure()
  uses: actions/upload-artifact@v3
  with:
    name: logs
    path: storage/logs/
```

### 5. Run in Parallel

```yaml
- name: Run tests in parallel
  run: ./vendor/bin/pest --parallel --processes=4
```

## Environment Variables

Set environment variables for tests:

```yaml
env:
  WP_PATH: /var/www/wordpress
  PEST_WP_SQLITE: true
  PEST_BROWSER_HEADLESS: true
```

## Scheduled Runs

Run tests on a schedule (e.g., nightly):

```yaml
on:
  schedule:
    - cron: '0 0 * * *'  # Every day at midnight
  push:
    branches: [main]
  pull_request:
```

## Status Badges

Add test status badges to your README:

```markdown
![Tests](https://github.com/user/repo/actions/workflows/test.yml/badge.svg)
![Coverage](https://codecov.io/gh/user/repo/branch/main/graph/badge.svg)
```

## Complete Example

```yaml
name: CI

on:
  push:
    branches: [main, develop]
  pull_request:

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install
      - run: ./vendor/bin/pint --test

  analyse:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install
      - run: ./vendor/bin/phpstan analyse

  test:
    runs-on: ubuntu-latest
    needs: [lint, analyse]
    strategy:
      matrix:
        php: ['8.3', '8.4']
    steps:
      - uses: actions/checkout@v4
      
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: xdebug
          
      - name: Cache Composer
        uses: actions/cache@v3
        with:
          path: vendor
          key: ${{ runner.os }}-php${{ matrix.php }}-${{ hashFiles('composer.lock') }}
          
      - run: composer install
      
      - name: Run tests
        run: ./vendor/bin/pest --coverage --coverage-clover=coverage.xml
        
      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: coverage.xml

  browser:
    runs-on: ubuntu-latest
    needs: test
    steps:
      - uses: actions/checkout@v4
      
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          
      - run: composer install
      - run: npx playwright install chromium --with-deps
      
      - name: Start server
        run: ./vendor/bin/pest-wp-serve &
        
      - name: Wait for server
        run: sleep 5
        
      - name: Run browser tests
        run: ./vendor/bin/pest tests/Browser
        
      - name: Upload screenshots
        if: failure()
        uses: actions/upload-artifact@v3
        with:
          name: browser-screenshots
          path: tests/__screenshots__/
```

## Next Steps

- [Configuration](configuration.md) - Configure tests
- [Architecture Testing](architecture-testing.md) - Code quality checks
- [Migration](migration.md) - Migrate from other frameworks
