# Contributing to CanvaStack

Alhamdulillah — thank you for considering contributing to this project.

## Code of Conduct

Be respectful, constructive, and inclusive. We welcome contributors of all backgrounds and experience levels.

## How to Contribute

### Reporting Bugs

1. Check [existing issues](https://github.com/canvastack/canvastack/issues) first
2. Open a new issue with:
   - Clear title and description
   - Steps to reproduce
   - Expected vs actual behavior
   - PHP/Laravel version
   - Relevant code snippets or error messages

### Suggesting Features

Open an issue with the `enhancement` label. Describe the use case and why it would benefit the library.

### Submitting a Pull Request

1. Fork the repository
2. Create a branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/your-bug-fix
   ```
3. Make your changes following the coding standards below
4. Write or update tests as needed
5. Run the test suite and ensure everything passes
6. Commit with a clear message (see commit conventions below)
7. Push and open a Pull Request against `main`

## Development Setup

```bash
git clone https://github.com/canvastack/canvastack.git
cd origin
composer install
```

Requirements:
- PHP 8.0+
- Laravel 8.x / 9.x / 10.x
- Extensions: `fileinfo`, `gd` or `imagick`, `openssl`

## Running Tests

```bash
# All tests
./vendor/bin/phpunit

# Specific suite
./vendor/bin/phpunit --testsuite=Security
./vendor/bin/phpunit --testsuite=Accessibility
./vendor/bin/phpunit --testsuite=PropertyBased
```

## Coding Standards

- PHP 8.0+ type hints required on all methods (parameters and return types)
- Use `FormConstants` class — no magic strings
- All user-facing output must be escaped via `canvastack_form_escape_html()`
- PHPDoc required for all public methods (`@param`, `@return`, `@throws`)
- Follow PSR-12 coding style
- Keep methods under 50 lines — extract helpers if needed
- Use descriptive variable names (no `$o`, `$s`, `$x`)

## Commit Conventions

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add new form element
fix: correct ARIA attribute on checkbox
docs: update API reference for Select
refactor: simplify tab rendering logic
test: add property tests for file upload
security: patch XSS in text input
```

## Security Contributions

If your contribution involves a security fix, please follow the [Security Policy](SECURITY.md) and report it privately before opening a PR.

## Documentation

If you add or change functionality, update the relevant docs in `docs/`. API changes must be reflected in `docs/COMPONENTS/FORM/`.

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).
