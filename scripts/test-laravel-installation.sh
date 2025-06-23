#!/bin/bash

# Laravel Installation Test Script
# This script tests the installation of egyjs/progressive-json-php across multiple Laravel versions

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Configuration
LARAVEL_VERSIONS=("9.*" "10.*" "11.*" "12.*")
PHP_VERSION=$(php -r "echo PHP_VERSION;")
TEST_DIR="laravel-installation-tests"
PACKAGE_NAME="egyjs/progressive-json-php"

print_status "Starting Laravel Installation Tests"
print_status "PHP Version: $PHP_VERSION (Supports PHP 8.1+)"
print_status "Package: $PACKAGE_NAME"

# Create test directory
if [ -d "$TEST_DIR" ]; then
    print_warning "Test directory exists, cleaning up..."
    rm -rf "$TEST_DIR"
fi

mkdir -p "$TEST_DIR"
cd "$TEST_DIR"

# Function to test Laravel version
test_laravel_version() {
    local laravel_version=$1
    local test_app_dir="laravel-${laravel_version//\*/x}"
    
    print_status "Testing Laravel $laravel_version"
    
    # Create Laravel application
    print_status "Creating Laravel $laravel_version application..."
    if ! composer create-project laravel/laravel:$laravel_version $test_app_dir --prefer-dist --no-progress --no-interaction; then
        print_error "Failed to create Laravel $laravel_version application"
        return 1
    fi
    
    cd $test_app_dir
    
    # Show Laravel version
    local actual_version=$(php artisan --version)
    print_status "Created: $actual_version"
    
    # Configure local package repository
    print_status "Configuring local package repository..."
    composer config repositories.local-package path ../../
    
    # Install the package
    print_status "Installing $PACKAGE_NAME..."
    if ! composer require $PACKAGE_NAME:@dev --no-interaction; then
        print_error "Failed to install package in Laravel $laravel_version"
        cd ..
        return 1
    fi
    
    # Verify package installation
    print_status "Verifying package installation..."
    composer show $PACKAGE_NAME
    
    # Test autoloading
    print_status "Testing autoloading..."
    php -r "
        require_once 'vendor/autoload.php';
        if (class_exists('Egyjs\ProgressiveJson\ProgressiveJsonStreamer')) {
            echo 'Autoloading: ✓' . PHP_EOL;
        } else {
            echo 'Autoloading: ✗' . PHP_EOL;
            exit(1);
        }
    "
    
    # Test basic functionality
    print_status "Testing basic functionality..."
    php -r "
        require_once 'vendor/autoload.php';
        \$streamer = new \Egyjs\ProgressiveJson\ProgressiveJsonStreamer();
        \$data = ['test' => 'Laravel ' . app()->version(), 'php' => PHP_VERSION];
        \$result = \$streamer->stream(\$data);
        if (!empty(\$result)) {
            echo 'Basic functionality: ✓' . PHP_EOL;
        } else {
            echo 'Basic functionality: ✗' . PHP_EOL;
            exit(1);
        }
    "
    
    cd ..
    print_success "Laravel $laravel_version tests completed successfully!"
    echo ""
    
    return 0
}

# Main test execution
failed_tests=()
successful_tests=()

for version in "${LARAVEL_VERSIONS[@]}"; do
    if test_laravel_version "$version"; then
        successful_tests+=("$version")
    else
        failed_tests+=("$version")
        print_error "Laravel $version tests failed!"
    fi
done

# Summary
echo ""
print_status "=== TEST SUMMARY ==="
print_status "PHP Version: $PHP_VERSION"
print_status "Package: $PACKAGE_NAME"
echo ""

if [ ${#successful_tests[@]} -gt 0 ]; then
    print_success "Successful tests (${#successful_tests[@]}):"
    for version in "${successful_tests[@]}"; do
        echo "  ✓ Laravel $version"
    done
fi

if [ ${#failed_tests[@]} -gt 0 ]; then
    echo ""
    print_error "Failed tests (${#failed_tests[@]}):"
    for version in "${failed_tests[@]}"; do
        echo "  ✗ Laravel $version"
    done
    echo ""
    print_error "Some tests failed. Please check the output above for details."
    exit 1
else
    echo ""
    print_success "All Laravel installation tests passed! 🎉"
    print_status "Your package is compatible with Laravel versions: ${successful_tests[*]}"
fi

# Cleanup
cd ..
if [ "$1" != "--keep-files" ]; then
    print_status "Cleaning up test files..."
    rm -rf "$TEST_DIR"
fi

print_success "Laravel installation tests completed!"
