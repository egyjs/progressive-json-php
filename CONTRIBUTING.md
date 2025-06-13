# Contributing to PHP Progressive JSON Stream

Thank you for your interest in contributing to the PHP Progressive JSON Stream project! 🎉

We welcome contributions from developers of all skill levels. Whether you're fixing a bug, adding a feature, improving documentation, or just asking questions, your contributions help make this project better for everyone.

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [How to Contribute](#how-to-contribute)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Documentation](#documentation)
- [Pull Request Process](#pull-request-process)
- [Issue Reporting](#issue-reporting)
- [Community](#community)

## 🤝 Code of Conduct

This project and everyone participating in it is governed by our [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code. Please report unacceptable behavior to [el3zahaby@gmail.com](mailto:el3zahaby@gmail.com).

## 🚀 Getting Started

### Prerequisites

- **PHP 8.0+** (we test on 8.0, 8.1, 8.2, 8.3)
- **Composer** for dependency management
- **Git** for version control
- **PHPUnit** for testing (installed via Composer)

### First Contribution

Looking for a good first issue? Check out:
- Issues labeled [`good first issue`](https://github.com/egyjs/progressive-json-php/labels/good%20first%20issue)
- Issues labeled [`help wanted`](https://github.com/egyjs/progressive-json-php/labels/help%20wanted)
- Documentation improvements
- Test coverage improvements

## 🛠️ Development Setup

### 1. Fork and Clone

```bash
# Fork the repository on GitHub, then clone your fork
git clone https://github.com/YOUR_USERNAME/progressive-json-php.git
cd progressive-json-php

# Add upstream remote
git remote add upstream https://github.com/egyjs/progressive-json-php.git
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install development dependencies
composer install --dev
```

### 3. Verify Setup

```bash
# Run tests to ensure everything works
composer test

# Run code style checks
composer cs-check

# Run static analysis (if available)
composer analyze
```

### 4. Create a Branch

```bash
# Create a feature branch
git checkout -b feature/your-feature-name

# Or for bug fixes
git checkout -b fix/issue-description
```

## 🔄 How to Contribute

### Types of Contributions

1. **🐛 Bug Fixes**
   - Fix existing functionality that doesn't work as expected
   - Include test cases that reproduce the bug
   - Update documentation if needed

2. **✨ New Features**
   - Add new functionality that enhances the library
   - Discuss major features in an issue first
   - Include comprehensive tests and documentation

3. **📚 Documentation**
   - Improve README, code comments, or examples
   - Fix typos or clarify confusing sections
   - Add usage examples or tutorials

4. **🧪 Testing**
   - Add test cases for uncovered scenarios
   - Improve test quality or performance
   - Add integration tests

5. **🔧 Maintenance**
   - Code refactoring for better performance
   - Dependency updates
   - CI/CD improvements

## 📝 Coding Standards

### PHP Standards

We follow **PSR-12** coding standards with some additional conventions:

```php
<?php

namespace Egyjs\ProgressiveJson;

/**
 * Class documentation should be comprehensive
 * 
 * @package Egyjs\ProgressiveJson
 */
class ExampleClass
{
    /**
     * Property documentation
     * 
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Method documentation with clear description
     * 
     * @param string $key The key to set
     * @param mixed $value The value to store
     * @return self For method chaining
     * @throws InvalidArgumentException When key is empty
     */
    public function setData(string $key, mixed $value): self
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Key cannot be empty');
        }

        $this->data[$key] = $value;
        return $this;
    }
}
```

### Naming Conventions

- **Classes**: `PascalCase` (e.g., `ProgressiveJsonStreamer`)
- **Methods**: `camelCase` (e.g., `addPlaceholder`)
- **Properties**: `camelCase` (e.g., `$placeholderMarker`)
- **Constants**: `UPPER_SNAKE_CASE` (e.g., `DEFAULT_MAX_DEPTH`)
- **Files**: Match class names (e.g., `ProgressiveJsonStreamer.php`)

### Code Quality

- **Type Declarations**: Use strict typing (`declare(strict_types=1)`)
- **Return Types**: Always specify return types
- **Documentation**: All public methods must have PHPDoc comments
- **Error Handling**: Use appropriate exceptions with clear messages
- **Fluent Interface**: Support method chaining where appropriate

## 🧪 Testing Guidelines

### Writing Tests

All contributions should include appropriate tests:

```php
<?php

use PHPUnit\Framework\TestCase;
use Egyjs\ProgressiveJson\ProgressiveJsonStreamer;

class FeatureTest extends TestCase
{
    public function testFeatureWorksAsExpected(): void
    {
        // Arrange
        $streamer = new ProgressiveJsonStreamer();
        $expectedResult = 'expected output';
        
        // Act
        $result = $streamer->someMethod();
        
        // Assert
        $this->assertEquals($expectedResult, $result);
    }
    
    public function testErrorHandling(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Specific error message');
        
        $streamer = new ProgressiveJsonStreamer();
        $streamer->invalidOperation();
    }
}
```

### Test Types

1. **Unit Tests**: Test individual methods and classes
2. **Integration Tests**: Test component interactions
3. **Behavioral Tests**: Test real-world usage scenarios
4. **Error Tests**: Test exception handling and edge cases

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test file
./vendor/bin/phpunit tests/ProgressiveJsonStreamerTest.php

# Run specific test method
./vendor/bin/phpunit --filter testMethodName
```

### Test Coverage

- Aim for **90%+ code coverage** for new features
- All public methods must be tested
- Critical paths and error conditions must be covered
- Use `@covers` annotations to specify what each test covers

## 📖 Documentation

### Code Documentation

- **PHPDoc blocks** for all public methods and properties
- **Clear parameter descriptions** with types
- **Return value documentation**
- **Exception documentation** with conditions
- **Usage examples** in complex methods

### README Updates

When adding features:
- Update the feature list
- Add usage examples
- Update installation instructions if needed
- Update the table of contents

### Examples

Create practical examples that demonstrate:
- Common use cases
- Framework integration
- Error handling
- Performance considerations

## 🔀 Pull Request Process

### Before Submitting

1. **Update from upstream**:
   ```bash
   git fetch upstream
   git rebase upstream/main
   ```

2. **Run quality checks**:
   ```bash
   composer test
   composer cs-check
   composer analyze  # if available
   ```

3. **Update documentation** as needed

4. **Write clear commit messages**:
   ```bash
   git commit -m "feat: add custom placeholder validation
   
   - Add validation for placeholder format
   - Include helpful error messages
   - Add comprehensive test coverage
   
   Closes #123"
   ```

### PR Template

When creating a pull request, include:

```markdown
## Description
Brief description of changes and motivation.

## Type of Change
- [ ] Bug fix (non-breaking change fixing an issue)
- [ ] New feature (non-breaking change adding functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to change)
- [ ] Documentation update

## Testing
- [ ] New tests added for new functionality
- [ ] All existing tests pass
- [ ] Manual testing completed

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No new warnings introduced
```

### Review Process

1. **Automated checks** must pass (tests, style, static analysis)
2. **Code review** by maintainers
3. **Discussion** and requested changes if needed
4. **Approval** and merge by maintainers

## 🐛 Issue Reporting

### Bug Reports

Use this template for bug reports:

```markdown
**Bug Description**
Clear description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Create streamer with '...'
2. Add placeholder '...'
3. Call stream() method
4. See error

**Expected Behavior**
What you expected to happen.

**Actual Behavior**
What actually happened.

**Environment**
- PHP Version: [e.g., 8.2.0]
- Library Version: [e.g., 1.0.0]
- Framework: [e.g., Symfony 6.3, Laravel 10.x, or None]
- OS: [e.g., Ubuntu 22.04, Windows 11, macOS 13]

**Additional Context**
Any other context about the problem.
```

### Feature Requests

Use this template for feature requests:

```markdown
**Feature Description**
Clear description of the feature you'd like to see.

**Use Case**
Describe the problem this feature would solve.

**Proposed Solution**
Your ideas for how this could be implemented.

**Alternatives Considered**
Other solutions you've considered.

**Additional Context**
Any other context or screenshots.
```

## 💬 Community

### Getting Help

- **GitHub Issues**: For bugs and feature requests
- **GitHub Discussions**: For questions and general discussion
- **Email**: [el3zahaby@gmail.com](mailto:el3zahaby@gmail.com) for sensitive issues

### Communication Guidelines

- **Be respectful** and constructive
- **Search existing issues** before creating new ones
- **Provide context** and examples
- **Be patient** - maintainers are volunteers
- **Follow up** on your issues and PRs

## 🎯 Development Workflow

### Branch Naming

- `feature/description` - New features
- `fix/description` - Bug fixes  
- `docs/description` - Documentation changes
- `refactor/description` - Code refactoring
- `test/description` - Test improvements

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```bash
feat: add support for custom error handlers
fix: resolve memory leak in stream processing
docs: update README with new examples
test: add integration tests for Symfony
refactor: optimize placeholder resolution performance
```

### Release Process

1. Features are merged to `main` branch
2. Semantic versioning is used (`x.y.z`)
3. Releases are tagged and published to Packagist
4. Changelog is maintained

## 🏆 Recognition

Contributors are recognized in:
- **CHANGELOG.md** for significant contributions
- **README.md** acknowledgments section
- **GitHub releases** notes

## 📞 Questions?

Don't hesitate to ask questions! We're here to help:

- Create a [GitHub Discussion](https://github.com/egyjs/progressive-json-php/discussions)
- Open an issue with the `question` label
- Email the maintainer: [el3zahaby@gmail.com](mailto:el3zahaby@gmail.com)

---

Thank you for contributing to PHP Progressive JSON Stream! Your efforts help make streaming JSON responses better for everyone. 🚀

*Happy coding!* ✨
