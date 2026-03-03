# Contributing to CanvaStack

Thank you for considering contributing to CanvaStack! This document provides guidelines and instructions for contributing.

---

## Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [Getting Started](#getting-started)
3. [Development Setup](#development-setup)
4. [Coding Standards](#coding-standards)
5. [Testing](#testing)
6. [Pull Request Process](#pull-request-process)
7. [Reporting Bugs](#reporting-bugs)
8. [Suggesting Features](#suggesting-features)
9. [Documentation](#documentation)

---

## Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inclusive environment for all contributors, regardless of experience level, gender, gender identity and expression, sexual orientation, disability, personal appearance, body size, race, ethnicity, age, religion, or nationality.

### Our Standards

Examples of behavior that contributes to a positive environment:
- Using welcoming and inclusive language
- Being respectful of differing viewpoints and experiences
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

Examples of unacceptable behavior:
- Trolling, insulting/derogatory comments, and personal or political attacks
- Public or private harassment
- Publishing others' private information without explicit permission
- Other conduct which could reasonably be considered inappropriate

---

## Getting Started

### Prerequisites

Before contributing, ensure you have:
- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- MySQL 8.0+
- Redis 7.x
- Git

### Fork and Clone

1. Fork the repository on GitHub
2. Clone your fork locally:
```bash
git clone https://github.com/YOUR-USERNAME/canvastack.git
cd canvastack
```

3. Add upstream remote:
```bash
git remote add upstream https://github.com/canvastack/canvastack.git
```

---

## Development Setup

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 2. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Update database credentials
nano .env
```

### 3. Setup Database

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE canvastack_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed
```

### 4. Build Assets

```bash
# Development mode (with watch)
npm run dev

# Production build
npm run build
```

### 5. Run Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=TableBuilderTest
```

---

## Coding Standards

### PHP Code Style

We follow **PSR-12** coding standard.

#### Format Code

```bash
# Format all files
./vendor/bin/pint

# Format specific directory
./vendor/bin/pint src/Components

# Dry run (preview changes)
./vendor/bin/pint --test
```

#### Static Analysis

```bash
# Run PHPStan
./vendor/bin/phpstan analyse

# Run with specific level
./vendor/bin/phpstan analyse --level=8
```

### Code Style Guidelines

#### 1. Use Type Declarations

```php
// ✅ Good
public function createUser(string $name, string $email): User
{
    return User::create(['name' => $name, 'email' => $email]);
}

// ❌ Bad
public function createUser($name, $email)
{
    return User::create(['name' => $name, 'email' => $email]);
}
```

#### 2. Use Dependency Injection

```php
// ✅ Good
public function __construct(
    private UserRepository $userRepository,
    private CacheManager $cache
) {}

// ❌ Bad
public function __construct()
{
    $this->userRepository = new UserRepository();
}
```

#### 3. Write Descriptive Names

```php
// ✅ Good
$activeUsers = User::where('status', 'active')->get();

// ❌ Bad
$u = User::where('status', 'active')->get();
```

#### 4. Add DocBlocks

```php
/**
 * Create a new user account.
 *
 * @param array $data User data including name, email, and password
 * @return User The created user instance
 * @throws ValidationException If validation fails
 */
public function createUser(array $data): User
{
    return User::create($data);
}
```

---

## Testing

### Writing Tests

#### Test Structure

```php
<?php

namespace Canvastack\Canvastack\Tests\Unit;

use Canvastack\Canvastack\Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_example_functionality(): void
    {
        // Arrange
        $user = User::factory()->create();
        
        // Act
        $result = $user->doSomething();
        
        // Assert
        $this->assertTrue($result);
    }
}
```

#### Test Naming

Use descriptive test names that explain what is being tested:

```php
// ✅ Good
public function test_user_can_create_post_with_valid_data(): void
public function test_user_cannot_create_post_without_title(): void

// ❌ Bad
public function test_create(): void
public function test_validation(): void
```

#### Test Coverage

- Aim for 80%+ test coverage
- Write tests for all new features
- Write tests for bug fixes
- Update tests when modifying existing code

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Unit/Components/TableBuilderTest.php

# Run with coverage
php artisan test --coverage

# Run with filter
php artisan test --filter=test_table_can_be_rendered
```

---

## Pull Request Process

### 1. Create a Branch

Create a descriptive branch name:

```bash
# Feature branch
git checkout -b feature/add-chart-component

# Bug fix branch
git checkout -b fix/table-pagination-issue

# Documentation branch
git checkout -b docs/update-installation-guide
```

### 2. Make Changes

- Write clean, well-documented code
- Follow coding standards (PSR-12)
- Add tests for new features
- Update documentation if needed

### 3. Commit Changes

Write clear, descriptive commit messages:

```bash
# Good commit messages
git commit -m "Add chart component with ApexCharts integration"
git commit -m "Fix N+1 query in table component"
git commit -m "Update installation guide with Redis setup"

# Bad commit messages
git commit -m "Update"
git commit -m "Fix bug"
git commit -m "Changes"
```

### 4. Run Quality Checks

Before submitting, ensure:

```bash
# Format code
./vendor/bin/pint

# Run static analysis
./vendor/bin/phpstan analyse

# Run tests
php artisan test

# Build assets
npm run build
```

### 5. Push Changes

```bash
git push origin feature/add-chart-component
```

### 6. Create Pull Request

1. Go to GitHub and create a pull request
2. Fill in the PR template:
   - Description of changes
   - Related issues
   - Testing performed
   - Screenshots (if UI changes)

### 7. Code Review

- Address reviewer feedback
- Make requested changes
- Push updates to the same branch
- Request re-review when ready

### 8. Merge

Once approved:
- Squash commits if needed
- Merge to main branch
- Delete feature branch

---

## Reporting Bugs

### Before Reporting

1. Check if the bug has already been reported
2. Verify it's not a configuration issue
3. Test with the latest version
4. Gather relevant information

### Bug Report Template

```markdown
**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Environment:**
- PHP Version: [e.g. 8.2.0]
- Laravel Version: [e.g. 12.0]
- CanvaStack Version: [e.g. 1.0.0]
- Database: [e.g. MySQL 8.0]
- OS: [e.g. Ubuntu 22.04]

**Additional context**
Add any other context about the problem here.
```

---

## Suggesting Features

### Feature Request Template

```markdown
**Is your feature request related to a problem?**
A clear and concise description of what the problem is.

**Describe the solution you'd like**
A clear and concise description of what you want to happen.

**Describe alternatives you've considered**
A clear and concise description of any alternative solutions or features you've considered.

**Additional context**
Add any other context or screenshots about the feature request here.

**Would you like to implement this feature?**
Let us know if you're willing to contribute the implementation.
```

---

## Documentation

### Writing Documentation

- Use clear, concise language
- Include code examples
- Add screenshots for UI features
- Cross-reference related documentation
- Follow Markdown best practices

### Documentation Structure

```
docs/
├── getting-started/     # Installation, quick start, configuration
├── components/          # Component documentation
├── architecture/        # Architecture and design patterns
├── features/           # Feature-specific guides
├── guides/             # How-to guides
└── README.md           # Documentation index
```

### Updating Documentation

When adding features or making changes:
1. Update relevant documentation
2. Add code examples
3. Update API reference if needed
4. Add to CHANGELOG.md

---

## Development Workflow

### Daily Development

```bash
# 1. Update your fork
git fetch upstream
git checkout main
git merge upstream/main

# 2. Create feature branch
git checkout -b feature/my-feature

# 3. Make changes and test
# ... code changes ...
php artisan test

# 4. Format and analyze
./vendor/bin/pint
./vendor/bin/phpstan analyse

# 5. Commit and push
git add .
git commit -m "Add my feature"
git push origin feature/my-feature

# 6. Create pull request on GitHub
```

### Keeping Your Fork Updated

```bash
# Fetch upstream changes
git fetch upstream

# Merge into your main branch
git checkout main
git merge upstream/main

# Push to your fork
git push origin main
```

---

## Questions?

If you have questions about contributing:

- Check the [documentation](docs/)
- Ask in [GitHub Discussions](https://github.com/canvastack/canvastack/discussions)
- Open an issue for clarification

---

## Recognition

Contributors will be recognized in:
- CHANGELOG.md
- GitHub contributors page
- Release notes

Thank you for contributing to CanvaStack! 🎉

---

**Last Updated**: 2026-02-26  
**Version**: 1.0.0
