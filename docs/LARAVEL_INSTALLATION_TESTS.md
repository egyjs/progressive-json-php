# Laravel Installation Tests

This directory contains comprehensive tests to verify that the `egyjs/progressive-json-php` package installs and works correctly across multiple Laravel versions (9.x through 12.x).

## Overview

The installation tests verify:

- ✅ **Package Installation**: Composer can successfully install the package
- ✅ **Autoloading**: Classes are properly autoloaded in Laravel context
- ✅ **Basic Functionality**: Core package features work as expected
- ✅ **Laravel Integration**: Package integrates well with Laravel's ecosystem

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
- **Unified Testing**: Uses the same `scripts/test-laravel-installation.sh` script for consistency
- **Comprehensive Testing**: Full integration testing in clean Laravel installations
- **Performance Monitoring**: Tracks performance across versions
- **Summary Reporting**: Provides detailed test results and summaries

### Workflow Architecture

The GitHub Actions workflow leverages the same local test script for consistency:

1. **Setup**: Configures PHP environment with required extensions
2. **Script Execution**: Runs `./scripts/test-laravel-installation.sh [VERSION] --keep-files`
3. **PHPUnit Integration**: Executes package tests within the Laravel context
4. **Summary Generation**: Provides comprehensive test results

This unified approach ensures that:
- Local and CI testing use identical logic
- Debugging is easier (run the same script locally)
- Maintenance is simplified (single source of truth)

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

The test script accepts Laravel version parameters for flexible testing:

#### On Linux/macOS:

```bash
# Make the script executable
chmod +x scripts/test-laravel-installation.sh

# Run all tests (default: Laravel 9.*, 10.*, 11.*, 12.*)
./scripts/test-laravel-installation.sh

# Test specific Laravel version
./scripts/test-laravel-installation.sh 10.*

# Test multiple specific versions
./scripts/test-laravel-installation.sh 9.* 11.*

# Keep test files for inspection (don't auto-cleanup)
./scripts/test-laravel-installation.sh --keep-files

# Combine version selection with file preservation
./scripts/test-laravel-installation.sh 10.* 11.* --keep-files

# Show help information
./scripts/test-laravel-installation.sh --help
```

#### Script Parameters

- **Laravel Versions**: Specify one or more Laravel versions (e.g., `9.*`, `10.*`, `11.*`, `12.*`)
- **`--keep-files`**: Preserve test directories after completion for debugging
- **`--help`**: Display usage information and examples

If no Laravel versions are specified, the script tests all default versions (9.* through 12.*).

#### Examples

```bash
# Test only Laravel 10 and 11
./scripts/test-laravel-installation.sh 10.* 11.*

# Test Laravel 9 and keep files for inspection  
./scripts/test-laravel-installation.sh 9.* --keep-files

# Quick test of latest Laravel version
./scripts/test-laravel-installation.sh 12.*
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

### Basic Functionality Test
- Creates ProgressiveJsonStreamer instance within Laravel application context
- Processes sample data including Laravel version and PHP version information
- Uses Laravel's `app()->version()` helper to verify Laravel integration
- Verifies output is generated correctly and not empty
- Tests that the package works seamlessly within Laravel's environment


## Continuous Integration

### GitHub Actions Integration

The workflow is designed to:
- Run automatically on code changes
- Test against all supported Laravel/PHP combinations
- Provide detailed failure information
- Cache dependencies for faster execution
- Generate test summaries

### Test Scope

The current implementation focuses on essential compatibility testing:
- **Installation verification**: Ensures the package can be installed via Composer
- **Autoloading verification**: Confirms classes load correctly in Laravel
- **Basic functionality**: Tests core features work within Laravel context
- **Future enhancements**: Additional tests (service providers, routes, performance) may be added

### Adding New Laravel Versions

To test new Laravel versions:

1. Update the matrix in `.github/workflows/laravel-installation-test.yml`
2. Add version to `LARAVEL_VERSIONS` array in test scripts
3. Update compatibility matrix in this documentation
4. Test locally before committing

### Custom Test Scenarios

You can extend the tests by:
- Adding service provider integration tests
- Testing route context functionality  
- Adding performance benchmarks
- Testing HTTP response generation
- Adding package removal verification
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
