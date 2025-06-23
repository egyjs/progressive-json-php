# Laravel Installation Tests

This directory contains comprehensive tests to verify that the `egyjs/progressive-json-php` package installs and works correctly across multiple Laravel versions (9.x through 12.x).

## Overview

The installation tests verify:

- ✅ **Package Installation**: Composer can successfully install the package
- ✅ **Autoloading**: Classes are properly autoloaded in Laravel context
- ✅ **Basic Functionality**: Core package features work as expected
- ✅ **Laravel Integration**: Package integrates well with Laravel's ecosystem
- ✅ **Service Provider Support**: Can be registered as a Laravel service
- ✅ **Route Context**: Works correctly in HTTP request/response cycle
- ✅ **HTTP Foundation Compatibility**: Compatible with Symfony HTTP Foundation
- ✅ **Performance**: Acceptable performance characteristics
- ✅ **Clean Removal**: Package can be cleanly uninstalled

## Test Matrix

| PHP Version | Laravel 9.x | Laravel 10.x | Laravel 11.x | Laravel 12.x |
|-------------|-------------|--------------|--------------|--------------|
| PHP 8.1     | ✅          | ✅           | ❌*          | ❌*          |
| PHP 8.2     | ✅          | ✅           | ✅           | ❌*          |
| PHP 8.3     | ✅          | ✅           | ✅           | ✅           |
| PHP 8.4     | ✅          | ✅           | ✅           | ✅           |

_*Laravel version requirements exclude certain PHP versions_

## Automated Testing (GitHub Actions)

The GitHub Actions workflow (`.github/workflows/laravel-installation-test.yml`) automatically runs these tests:

- **Triggers**: Push to master, Pull requests, Weekly schedule
- **Matrix Strategy**: Tests all compatible PHP/Laravel version combinations
- **Comprehensive Testing**: Full integration testing in clean Laravel installations
- **Performance Monitoring**: Tracks performance across versions
- **Summary Reporting**: Provides detailed test results and summaries

### Viewing Test Results

1. Go to the "Actions" tab in your GitHub repository
2. Look for "Laravel Installation Tests" workflow runs
3. Click on any run to see detailed results for each PHP/Laravel combination
4. Check the summary for a quick overview of test results

## Local Testing

### Prerequisites

- PHP 8.1+ with Composer installed
- Git (for cloning Laravel projects)
- Sufficient disk space (each Laravel installation ~100MB)

### Running Tests

#### On Linux/macOS:

```bash
# Make the script executable
chmod +x scripts/test-laravel-installation.sh

# Run all tests
./scripts/test-laravel-installation.sh

# Keep test files for inspection (don't auto-cleanup)
./scripts/test-laravel-installation.sh --keep-files
```

### Understanding Test Output

The scripts provide colored output to help you understand the test progress:

- 🔵 **[INFO]**: General information about test progress
- 🟢 **[SUCCESS]**: Test step completed successfully  
- 🟡 **[WARNING]**: Non-critical issues or warnings
- 🔴 **[ERROR]**: Test failures that need attention

### Example Output

```
[INFO] Starting Laravel Installation Tests
[INFO] PHP Version: 8.3.0
[INFO] Package: egyjs/progressive-json-php

[INFO] Testing Laravel 10.*
[INFO] Creating Laravel 10.* application...
[INFO] Created: Laravel Framework 10.48.22
[INFO] Installing egyjs/progressive-json-php...
[SUCCESS] Autoloading: ✓
[SUCCESS] Basic functionality: ✓
[SUCCESS] Service provider integration: ✓
[SUCCESS] Route registration: ✓
[SUCCESS] Performance test: 0.0234s ✓
[SUCCESS] Package removal: ✓
[SUCCESS] Laravel 10.* tests completed successfully!

=== TEST SUMMARY ===
[SUCCESS] All Laravel installation tests passed! 🎉
[INFO] Your package is compatible with Laravel versions: 9.* 10.* 11.* 12.*
```

## Test Details

### Package Installation Test
- Creates fresh Laravel applications using `composer create-project`
- Configures local package repository to simulate Packagist installation
- Installs package via `composer require egyjs/progressive-json-php:@dev`
- Verifies package appears in `composer show` output

### Autoloading Test
- Requires vendor autoload file
- Attempts to instantiate `Egyjs\ProgressiveJson\ProgressiveJsonStreamer`
- Verifies class exists and can be loaded

### Functionality Test
- Creates ProgressiveJsonStreamer instance
- Processes sample data including Laravel version information
- Verifies output is generated correctly
- Tests within Laravel application context

### Laravel Integration Test
- Creates custom service provider
- Registers ProgressiveJsonStreamer as singleton service
- Tests Laravel application boot process with service provider
- Verifies `php artisan about` command works

### Route Context Test
- Creates test route using the package
- Registers route in Laravel routing system
- Verifies route can be called and returns expected output
- Tests HTTP response generation

### Performance Test
- Processes larger dataset (1000 items)
- Measures execution time
- Warns if performance is unexpectedly slow
- Ensures package handles reasonable data volumes

### Cleanup Test
- Removes package via `composer remove`
- Verifies Laravel application still boots properly
- Ensures no orphaned files or configuration


## Continuous Integration

### GitHub Actions Integration

The workflow is designed to:
- Run automatically on code changes
- Test against all supported Laravel/PHP combinations
- Provide detailed failure information
- Cache dependencies for faster execution
- Generate test summaries

### Adding New Laravel Versions

To test new Laravel versions:

1. Update the matrix in `.github/workflows/laravel-installation-test.yml`
2. Add version to `LARAVEL_VERSIONS` array in test scripts
3. Update compatibility matrix in this documentation
4. Test locally before committing

### Custom Test Scenarios

You can extend the tests by:
- Adding more complex integration scenarios
- Testing specific Laravel features (queues, events, etc.)
- Adding performance benchmarks
- Testing with different PHP extensions

## Contributing

When contributing to the package:

1. Run installation tests locally before submitting PRs
2. Update tests if adding Laravel-specific features
3. Ensure tests pass in CI before merging
4. Update this documentation for any test changes

## Support

If you encounter issues with the installation tests:

1. Check the troubleshooting section above
2. Review GitHub Actions logs for detailed error information
3. Run tests locally with `--keep-files` for debugging
4. Open an issue with test output and environment details
