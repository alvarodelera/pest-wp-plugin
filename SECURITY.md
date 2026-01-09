# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

If you discover a security vulnerability in PestWP, please report it by:

1. **Email**: Send details to the maintainers (do not open a public issue)
2. **GitHub Security Advisory**: Use GitHub's private vulnerability reporting

Please include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

We will respond within 48 hours and aim to release a fix within 7 days for critical issues.

## Security Considerations

### Development/Testing Tool

PestWP is a **development and testing tool** intended for use in local development and CI/CD environments. It is **not designed for production use**.

### File System Access

PestWP requires file system access for:

1. **WordPress Installation**: Downloads and extracts WordPress to `.pest/wordpress/`
2. **SQLite Database**: Creates and manages SQLite database files
3. **Snapshots**: Stores database snapshots for test isolation
4. **Screenshots**: Stores visual regression test images
5. **Configuration**: Writes configuration files for tests

All file operations:
- Use controlled paths within the project directory
- Do not accept arbitrary user input for file paths
- Are limited to the `.pest/` directory and `tests/` directory

### Network Access

PestWP makes outbound network requests to:

1. **wordpress.org**: Download WordPress core
2. **api.github.com**: Check for SQLite integration plugin releases
3. **github.com**: Download SQLite integration plugin

No sensitive data is transmitted. All downloads are from official sources.

### No Dangerous Functions

PestWP does not use:
- `eval()`
- `exec()`
- `shell_exec()`
- `system()`
- `passthru()`
- `popen()`

These functions are only referenced in architecture testing rules to help users detect them in their own code.

### Database Security

- Uses SQLite (no network database connections)
- Database files are stored in `.pest/database/`
- Test data is isolated and rolled back after each test
- No production databases are accessed

### Credential Handling

For browser tests, PestWP stores WordPress credentials in:
- `tests/Pest.php` (user-configured)
- `.pest/state/` (authentication state for browser tests)

**Recommendations:**
- Add `.pest/` to `.gitignore`
- Use environment variables for sensitive credentials
- Do not commit real credentials to version control

Example using environment variables:
```php
function browser(): array
{
    return [
        'base_url' => getenv('WP_BASE_URL') ?: 'http://localhost:8080',
        'admin_user' => getenv('WP_ADMIN_USER') ?: 'admin',
        'admin_password' => getenv('WP_ADMIN_PASSWORD') ?: 'password',
    ];
}
```

### CI/CD Security

When using PestWP in CI/CD:

1. **Cache WordPress safely**: The `.pest/wordpress/` directory can be cached
2. **Protect credentials**: Use CI secrets for browser test credentials
3. **Isolate test environment**: Browser tests should run against isolated containers
4. **Clean up**: Ensure test artifacts are not persisted

### Dependencies

PestWP depends on:
- `pestphp/pest` - The Pest testing framework
- `pestphp/pest-plugin-browser` - Playwright-based browser testing

All dependencies are well-maintained open-source projects. Run `composer audit` regularly to check for known vulnerabilities.

## Best Practices

1. **Keep PestWP updated**: Install latest versions for security fixes
2. **Review `.gitignore`**: Ensure `.pest/` is not committed
3. **Use environment variables**: For any sensitive configuration
4. **Audit dependencies**: Run `composer audit` regularly
5. **Limit CI permissions**: Use minimal permissions for test runners

## Code Quality

PestWP maintains high code quality standards:

- **PHPStan Level 9**: Strict static analysis
- **PSR-12**: Consistent code style
- **Test Coverage**: Comprehensive unit and integration tests
- **Architecture Rules**: Built-in security pattern detection

## Acknowledgments

We appreciate responsible disclosure of security issues. Contributors who report valid vulnerabilities will be acknowledged in our release notes (with permission).
