# Changelog — canvastack/canvastack v0.1.0-alpha

**Release Date:** 2026-04-27
**Release Type:** Alpha
**Package:** `canvastack/canvastack`

---

## 🔁 Rebranding Notice

This is the first release under the new package name `canvastack/canvastack`, rebranded from `canvastack/origin`
(which itself was a rebranding of `incody/codiy`).

The version has been reset to `0.1.0-alpha` to reflect the new package identity and ongoing alpha development.
All features from `canvastack/origin` are carried forward.

**Migration from `canvastack/origin`:**

```bash
# Remove old package
composer remove canvastack/origin

# Install new package
composer require canvastack/canvastack
```

Update your service provider reference if manually registered:
```php
// Before
Canvastack\Origin\CanvastackServiceProvider::class

// After
Canvastack\Canvastack\CanvastackServiceProvider::class
```

> For full legacy release history, see [CHANGELOG-legacy-origin.md](./CHANGELOG-legacy-origin.md).

---

## 🚀 New Features

### Dynamic SMTP Configuration
Database-driven mail configuration system replacing static `.env`-only setup:
- New `MailConfigService` for managing SMTP settings from preferences
- Runtime SMTP configuration loading from database
- Automatic configuration reload after preference updates
- Fallback to `.env` configuration when preference settings are empty
- Password encryption/decryption for secure storage
- SMTP connection testing with detailed error messages
- Gmail App Password support with helpful error messages
- Configuration caching for improved performance

### SMTP Test Connection
Interactive SMTP testing directly from the preference page:
- Real-time SMTP connection testing via AJAX
- Test button with loading states and visual feedback
- Detailed success/error messages for troubleshooting
- Support for testing with current or new credentials
- Automatic field validation before testing
- User-friendly error messages for common issues (authentication, connection, timeout)

### Group Model Helper
- Added `getFirstRouteOptions($groupId)` method for form sync
- Returns array of `route_path => module_name` for dropdown options
- Supports group-based route filtering with proper joins

### Cache Management System
- New `CacheManagementController.php` for cache operations
- Cache monitoring and statistics
- Cache warming and invalidation

### Exception Handling
- New `src/Exceptions/` directory with custom exceptions
- Structured error handling across the application

### Service Layer
- New `src/Services/` directory for business logic separation
- Improved code organization and maintainability

### New Configuration File
`config/canvastack.mail.php`:
- `use_preference` - Enable/disable preference-based SMTP
- `fallback_to_env` - Fallback to `.env` when preference is empty
- `encrypt_password` - Enable password encryption
- `test_on_save` - Auto-test connection after saving
- `cache_ttl` - Configuration cache duration

### New Helper Functions
Mail configuration utilities:
- `canvastack_mail_config_service()` - Get MailConfigService instance
- `canvastack_mail_reload_config()` - Reload configuration from preference
- `canvastack_mail_test_smtp()` - Test SMTP connection
- `canvastack_mail_encrypt_password()` - Encrypt password for storage

---

## 🔒 Security Enhancements

### Password Encryption
- Laravel Crypt-based SMTP password encryption
- Automatic encryption on save, automatic decryption on load
- Backward compatibility with plain text passwords
- Configurable encryption enable/disable

### AJAX Security
- Encrypted parameters in POST body (not URL)
- CSRF token validation for SMTP test endpoint
- Input validation for all SMTP test parameters
- Secure password handling (never logged)
- User ID logging for audit trail

### Group Management Security
- CSRF token validation for AJAX rolemapage requests
- Root group protection (non-root users cannot modify root group)
- Input validation for group IDs and AJAX parameters
- Security event logging for unauthorized access attempts
- Constant-time CSRF token comparison to prevent timing attacks

### New Exception Classes
- `CSRFException` - CSRF token validation failures (HTTP 419)
- `ControllerException` - General controller errors (HTTP 500)
- `ControllerValidationException` - Input validation failures (HTTP 422)

### Privilege Management Constants
- `PrivilegeConstants` class with bitwise privilege flags: READ (8), WRITE (4), MODIFY (2), DELETE (1)
- Helper methods for privilege validation and checking

### Broad Security Hardening
- CSRF Protection across all controllers
- XSS Prevention with enhanced output escaping and input sanitization
- SQL Injection Protection with parameterized queries
- File Upload Security with enhanced validation
- New `Security.php` helper with security utilities

---

## 🔄 Refactoring

### PreferenceController.php
- Added comprehensive SMTP field validations (host, port, secure, user, password)
- Enhanced form fields with better UX (placeholders, labels, dropdown)
- Added `testSmtpConnection()` method for AJAX testing endpoint
- Added `addSmtpTestButton()` method for test button injection
- Implemented password encryption on update
- Added automatic configuration reload after update

### CanvastackServiceProvider.php
- Registered `MailConfigService` as singleton
- Added `loadMailConfiguration()` method for boot-time config loading
- Console context detection to avoid DB issues during migrations
- Error handling with graceful fallback

### AjaxController.php
- Added comprehensive PHPDoc documentation for all methods and properties
- Added security annotations (`@security CRITICAL`) for sensitive operations
- Added proper type hints for class properties
- Enhanced error handling with proper exception handling
- Added logging support and cache management support

### GroupController.php
- Added database transaction management for data consistency
- Implemented root group protection with authorization checks
- Enhanced CSRF validation for AJAX requests
- Added comprehensive error handling with try-catch blocks
- Improved logging for all operations (create, update, delete)
- Added cache invalidation after group modifications

### Privileges.php (Admin/System/Includes)
- Refactored `privileges_before_insert()` with improved data structure
- Enhanced `privileges_after_insert()` with "clear first, then apply" strategy
- Added menu caching with 1-hour TTL
- Implemented `invalidateMenuCache()` for cache management

### MappingPage.php (Admin/System/Includes)
- Refactored `mapping_before_insert()` with better data validation
- Added hierarchical row building methods (buildParentRow, buildChildRows, buildSubChildRows)
- Enhanced AJAX URL generation with security validation
- Added `invalidateMappingCache()` for cache management

### Action.php
- Added Request to array conversion for error handling compatibility
- Preserves merged data from request
- Better compatibility with file upload processing

### firscripts.js
- Refactored `ajaxSelectionProcess()` to use POST body instead of URL parameters
- Fixed encrypted parameter handling (raw POST data, not URL encoded)
- Implemented complete SMTP test connection handler
- Field monitoring for dynamic test button visibility

---

## 🐛 Bug Fixes

### AJAX Parameter Handling
- Changed from URL parameters to POST body for encrypted data
- Prevents URL length limitations and improves security

### UserController.php
- Fixed filterGroups chain configuration (`group_name` → `group_info`)
- Removed username from filter dropdown (now searchable via search box)
- Fixed email sending to use direct Email Objects instance instead of `$this->email`
- Added comprehensive error handling and logging for email failures
- Added validation for empty `credential_info` in email templates

### Datatables.php
- Fixed errors when using custom relational fields (via JOINs) instead of Eloquent relationships
- Added `filterValidRelations()` method to validate model relationships before eager loading
- Prevented "Relationship does not exist" errors for non-existent Eloquent relations
- Enhanced lazy loading threshold handling for large datasets

### Search.php & QueryBuilder.php
- Enhanced query builder validation and error handling
- Improved search term sanitization
- Better handling of complex search queries

### Group Management
- Fixed privilege data structure handling in `privileges_before_insert()`
- Fixed "clear all privileges" functionality when no modules selected
- Fixed cache invalidation timing (now after transaction commit)
- Fixed privilege clearing strategy (UPDATE to NULL instead of DELETE)

### Security Fixes
- Fixed CSRF token validation for AJAX requests
- Fixed XSS vulnerabilities in module name display
- Fixed SQL injection risks in privilege queries
- Fixed unauthorized access to root group modifications

---

## 🎨 Frontend Enhancements

### SMTP Test JavaScript Handler
- Real-time field monitoring for test button visibility
- AJAX-based connection testing with loading states
- Success/error message display with icons
- Detailed error information in expandable alert boxes
- CSRF token handling for secure requests

### DataTables Improvements
- Enhanced `canvastack-datatables.js` with new features
- Added `table-search-enhancements.css` for better search UI
- Added `apexcharts.min.js` for advanced charting

### Other Frontend Changes
- Updated `canvastackscripts.js` with new functionality
- Enhanced `canvastack.css` with improved styling
- Updated `config.css` and `responsive.css`
- Updated header template with new features
- Removed obsolete `scripts.jsxx` file

---

## 📚 Documentation

### Added
- `docs/CORE/GROUP/` - Group management documentation suite (7 files)
- `docs/COMPONENTS/TOOLS/CACHE_MANAGEMENT.md`
- `docs/CORE/API_DOCUMENTATION.md`
- `docs/CORE/MIGRATION_GUIDE.md`
- `docs/CORE/MONITORING_AND_LOGGING.md`
- `docs/SECURITY/CSRF_PROTECTION.md`
- `docs/TEST/CONFIGURATION_GUIDE.md`
- `docs/TEST/PERFORMANCE_IMPROVEMENTS.md`

### Removed
- Cleaned up obsolete security test documentation from `src/Publisher/tests/Security/docs/`

---

## 🔧 Configuration

### New Files
- `src/Library/Exceptions/CSRFException.php`
- `src/Library/Exceptions/ControllerException.php`
- `src/Library/Exceptions/ControllerValidationException.php`
- `src/Library/Constants/PrivilegeConstants.php`
- `config/canvastack.mail.php`
- `config/canvastack.controller.php`
- `config/canvastack.monitoring.php`
- `.env.canvastack.example`
- `phpunit.xml`

---

## ⚠️ Breaking Changes

### Package Name Change
- **Old:** `canvastack/origin`
- **New:** `canvastack/canvastack`

### Namespace Change
- **Old:** `Canvastack\Origin\`
- **New:** `Canvastack\Canvastack\`

All other changes are backward compatible with `canvastack/origin` v2.0.0.

---

## 📦 Dependencies

No changes to dependencies from `canvastack/origin` v2.0.0.

```json
"require": {
    "laravel/ui": "~4.0",
    "laravelcollective/html": "~6.4",
    "doctrine/dbal": "~3.4",
    "intervention/image": "~3.9",
    "yajra/laravel-datatables": "~9.0",
    "jlawrence/eos": "~3.2"
},
"require-dev": {
    "giorgiosironi/eris": "^1.1"
}
```

---

## 🔗 Links

- **Repository:** [github.com/canvastack/canvastack](https://github.com/canvastack/canvastack)
- **Issues:** [github.com/canvastack/canvastack/issues](https://github.com/canvastack/canvastack/issues)
- **Legacy Changelog:** [CHANGELOG-legacy-origin.md](./CHANGELOG-legacy-origin.md)
