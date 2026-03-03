# Translation Key Naming Convention

## Overview

This document defines the standard naming convention for translation keys in CanvaStack. Following these conventions ensures consistency, maintainability, and ease of use across the entire application.

**Version**: 1.0.0  
**Last Updated**: 2026-02-27  
**Status**: Published

---

## 📋 Table of Contents

- [Key Format](#key-format)
- [Naming Rules](#naming-rules)
- [Group Organization](#group-organization)
- [Key Structure](#key-structure)
- [Examples](#examples)
- [Best Practices](#best-practices)
- [Common Patterns](#common-patterns)

---

## Key Format

### Basic Format

```
{group}.{section}.{key}
```

**Components:**
- **group**: Translation file name (e.g., `ui`, `components`, `auth`, `validation`)
- **section**: Logical grouping within the file (e.g., `buttons`, `labels`, `messages`)
- **key**: Specific translation key (e.g., `save`, `cancel`, `success`)

### Examples

```php
// Good examples
'ui.buttons.save'
'ui.labels.name'
'ui.messages.success'
'components.form.required_field'
'components.table.no_data'
'auth.login.title'
'validation.required'
'errors.http.404'
```

---

## Naming Rules

### 1. Use Snake Case

All keys must use `snake_case` (lowercase with underscores).

```php
// ✅ Good
'ui.buttons.save_and_continue'
'components.form.file_upload'
'auth.forgot_password.submit'

// ❌ Bad
'ui.buttons.SaveAndContinue'
'components.form.fileUpload'
'auth.forgot-password.submit'
```

### 2. Use Descriptive Names

Keys should be self-explanatory and describe their purpose.

```php
// ✅ Good
'ui.buttons.save'
'ui.messages.confirm_delete'
'components.table.no_data'

// ❌ Bad
'ui.buttons.btn1'
'ui.messages.msg'
'components.table.nd'
```

### 3. Use Singular Form

Use singular form for keys unless the context requires plural.

```php
// ✅ Good
'ui.labels.name'
'ui.labels.email'
'ui.labels.status'

// ✅ Good (plural context)
'ui.labels.permissions'
'ui.labels.tags'
'ui.navigation.users'
```

### 4. Avoid Abbreviations

Use full words instead of abbreviations for clarity.

```php
// ✅ Good
'ui.buttons.delete'
'ui.labels.description'
'ui.messages.information'

// ❌ Bad
'ui.buttons.del'
'ui.labels.desc'
'ui.messages.info'
```

### 5. Use Consistent Terminology

Use the same terms across all translations.

```php
// ✅ Good (consistent)
'ui.buttons.save'
'ui.messages.saved'
'components.form.save_changes'

// ❌ Bad (inconsistent)
'ui.buttons.save'
'ui.messages.stored'
'components.form.persist_changes'
```

---

## Group Organization

### Standard Groups

CanvaStack uses the following standard translation groups:

#### 1. `ui.php` - User Interface Elements

General UI elements like buttons, labels, messages, navigation, etc.

```php
'ui.buttons.*'      // Button labels
'ui.labels.*'       // Form labels
'ui.messages.*'     // General messages
'ui.navigation.*'   // Navigation items
'ui.pagination.*'   // Pagination text
'ui.table.*'        // Table-related text
'ui.form.*'         // Form-related text
'ui.status.*'       // Status labels
'ui.time.*'         // Time-related text
```

#### 2. `components.php` - Component-Specific Text

Text specific to CanvaStack components (FormBuilder, TableBuilder, etc.).

```php
'components.form.*'       // FormBuilder
'components.table.*'      // TableBuilder
'components.chart.*'      // ChartBuilder
'components.pagination.*' // Pagination
'components.modal.*'      // Modal dialogs
'components.dropdown.*'   // Dropdowns
'components.breadcrumb.*' // Breadcrumbs
'components.alert.*'      // Alerts
'components.card.*'       // Cards
```

#### 3. `auth.php` - Authentication

Authentication-related text (login, register, password reset, etc.).

```php
'auth.login.*'           // Login page
'auth.register.*'        // Registration page
'auth.forgot_password.*' // Forgot password
'auth.reset_password.*'  // Reset password
'auth.verify_email.*'    // Email verification
'auth.logout.*'          // Logout
```

#### 4. `validation.php` - Validation Messages

Laravel validation messages (standard Laravel format).

```php
'validation.required'
'validation.email'
'validation.min'
'validation.max'
// etc.
```

#### 5. `errors.php` - Error Messages

Error messages and HTTP status codes.

```php
'errors.http.*'       // HTTP status codes
'errors.messages.*'   // Error messages
'errors.actions.*'    // Error page actions
'errors.general.*'    // General errors
'errors.validation.*' // Validation errors
'errors.database.*'   // Database errors
'errors.file.*'       // File errors
'errors.permission.*' // Permission errors
```

---

## Key Structure

### Hierarchical Structure

Use nested arrays for logical grouping:

```php
// ui.php
return [
    'buttons' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
    ],
    'labels' => [
        'name' => 'Name',
        'email' => 'Email',
    ],
    'messages' => [
        'success' => 'Operation completed successfully.',
        'error' => 'An error occurred.',
    ],
];
```

### Maximum Nesting Depth

Limit nesting to 3 levels for maintainability:

```php
// ✅ Good (3 levels)
'components.form.file_upload.drag_drop'

// ❌ Bad (4+ levels)
'components.form.file_upload.messages.drag_drop.text'
```

---

## Examples

### UI Translations

```php
// Buttons
__('ui.buttons.save')              // "Save"
__('ui.buttons.cancel')            // "Cancel"
__('ui.buttons.delete')            // "Delete"

// Labels
__('ui.labels.name')               // "Name"
__('ui.labels.email')              // "Email"
__('ui.labels.password')           // "Password"

// Messages
__('ui.messages.success')          // "Operation completed successfully."
__('ui.messages.error')            // "An error occurred."
__('ui.messages.confirm_delete')   // "Are you sure you want to delete this item?"

// Navigation
__('ui.navigation.dashboard')      // "Dashboard"
__('ui.navigation.users')          // "Users"
__('ui.navigation.settings')       // "Settings"
```

### Component Translations

```php
// FormBuilder
__('components.form.required_field')           // "Required field"
__('components.form.select_placeholder')       // "Select an option"
__('components.form.file_upload.drag_drop')    // "Drag and drop files here"

// TableBuilder
__('components.table.no_data')                 // "No data available"
__('components.table.loading')                 // "Loading data..."
__('components.table.showing', [
    'from' => 1,
    'to' => 10,
    'total' => 100
])  // "Showing 1 to 10 of 100 entries"

// ChartBuilder
__('components.chart.no_data')                 // "No data available"
__('components.chart.loading')                 // "Loading chart..."
```

### Authentication Translations

```php
// Login
__('auth.login.title')                         // "Login"
__('auth.login.email')                         // "Email Address"
__('auth.login.password')                      // "Password"
__('auth.login.submit')                        // "Sign In"

// Register
__('auth.register.title')                      // "Register"
__('auth.register.name')                       // "Full Name"
__('auth.register.submit')                     // "Create Account"

// Forgot Password
__('auth.forgot_password.title')               // "Forgot Password"
__('auth.forgot_password.submit')              // "Send Reset Link"
```

### Error Translations

```php
// HTTP Errors
__('errors.http.404')                          // "Not Found"
__('errors.messages.404')                      // "The page you are looking for could not be found."

// General Errors
__('errors.general.something_wrong')           // "Something went wrong"
__('errors.actions.go_home')                   // "Go to Homepage"
```

---

## Best Practices

### 1. Use Placeholders for Dynamic Content

```php
// Translation file
'messages' => [
    'welcome' => 'Welcome, :name!',
    'items_count' => 'Showing :count items',
],

// Usage
__('ui.messages.welcome', ['name' => 'John'])
__('ui.messages.items_count', ['count' => 10])
```

### 2. Use Pluralization

```php
// Translation file
'time' => [
    'minutes_ago' => ':count minute ago|:count minutes ago',
],

// Usage
trans_choice('ui.time.minutes_ago', 1)  // "1 minute ago"
trans_choice('ui.time.minutes_ago', 5)  // "5 minutes ago"
```

### 3. Group Related Keys

```php
// ✅ Good (grouped)
'form' => [
    'file_upload' => [
        'drag_drop' => 'Drag and drop files here',
        'browse' => 'Browse files',
        'selected' => 'File selected',
    ],
],

// ❌ Bad (scattered)
'form' => [
    'file_upload_drag_drop' => 'Drag and drop files here',
    'file_upload_browse' => 'Browse files',
    'file_upload_selected' => 'File selected',
],
```

### 4. Avoid Hardcoded Text

```php
// ❌ Bad
<button>Save</button>

// ✅ Good
<button>{{ __('ui.buttons.save') }}</button>
```

### 5. Document Custom Keys

Add comments for complex or non-obvious keys:

```php
return [
    'messages' => [
        // Shown when user tries to delete their own account
        'cannot_delete_self' => 'You cannot delete your own account.',
        
        // Shown after successful password change
        'password_changed' => 'Your password has been changed successfully.',
    ],
];
```

---

## Common Patterns

### Action Buttons

```php
'buttons' => [
    'save' => 'Save',
    'save_and_continue' => 'Save and Continue',
    'save_and_close' => 'Save and Close',
    'cancel' => 'Cancel',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'create' => 'Create',
    'update' => 'Update',
    'view' => 'View',
    'back' => 'Back',
    'next' => 'Next',
    'previous' => 'Previous',
    'submit' => 'Submit',
    'reset' => 'Reset',
    'close' => 'Close',
    'confirm' => 'Confirm',
],
```

### Status Labels

```php
'status' => [
    'active' => 'Active',
    'inactive' => 'Inactive',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'draft' => 'Draft',
    'published' => 'Published',
    'archived' => 'Archived',
],
```

### Confirmation Messages

```php
'messages' => [
    'confirm_delete' => 'Are you sure you want to delete this item?',
    'confirm_action' => 'Are you sure you want to perform this action?',
    'confirm_logout' => 'Are you sure you want to logout?',
],
```

### Success/Error Messages

```php
'messages' => [
    'success' => 'Operation completed successfully.',
    'error' => 'An error occurred. Please try again.',
    'saved' => 'Changes saved successfully.',
    'deleted' => 'Item deleted successfully.',
    'created' => 'Item created successfully.',
    'updated' => 'Item updated successfully.',
],
```

---

## Migration from Old Keys

If you have existing translations with different naming conventions:

### Step 1: Identify Old Keys

```bash
# Find all translation keys in your codebase
grep -r "__(" app/ resources/
grep -r "trans(" app/ resources/
grep -r "@lang" resources/views/
```

### Step 2: Create Mapping

```php
// Create a mapping file
$keyMapping = [
    'old.key' => 'new.group.section.key',
    'btn.save' => 'ui.buttons.save',
    'lbl.name' => 'ui.labels.name',
];
```

### Step 3: Update Code

Use search and replace or a script to update all occurrences.

### Step 4: Update Translation Files

Reorganize translation files according to the new convention.

---

## Tools and Helpers

### Find Missing Translations

```bash
php artisan canvastack:translate:missing
```

### Extract Translation Keys

```bash
php artisan canvastack:translate
```

### Export Translations

```bash
php artisan canvastack:translate:export --format=json
php artisan canvastack:translate:export --format=csv
```

---

## Related Documentation

- [Internationalization (i18n) System](../features/i18n.md)
- [LocaleManager API](../api/locale-manager.md)
- [TranslationLoader API](../api/translation-loader.md)

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-02-27  
**Status**: Published  
**Maintainer**: CanvaStack Team
