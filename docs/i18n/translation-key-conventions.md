# Translation Key Conventions

Standard conventions for naming and organizing translation keys in CanvaStack applications.

## 📋 Overview

Consistent translation key naming is essential for:
- Easy maintenance and updates
- Quick key discovery
- Team collaboration
- Automated tooling
- Code readability

## 🎯 Naming Conventions

### General Rules

1. **Use lowercase with underscores**
   ```php
   // Good
   __('ui.user_name')
   __('validation.email_required')
   
   // Bad
   __('ui.userName')
   __('validation.EmailRequired')
   ```

2. **Use dot notation for hierarchy**
   ```php
   __('ui.button.save')
   __('components.table.actions.edit')
   __('features.users.create.title')
   ```

3. **Be descriptive but concise**
   ```php
   // Good
   __('ui.delete_confirm')
   __('validation.password_min_length')
   
   // Bad
   __('ui.dc')
   __('validation.the_password_must_be_at_least_8_characters')
   ```

4. **Use singular form for keys**
   ```php
   // Good
   __('ui.user')
   __('ui.product')
   
   // Bad
   __('ui.users')
   __('ui.products')
   ```

## 📁 File Organization

### Standard Files

```
resources/lang/{locale}/
├── ui.php              # General UI elements
├── auth.php            # Authentication & authorization
├── validation.php      # Validation messages
├── components.php      # Component-specific translations
├── errors.php          # Error messages
├── emails.php          # Email templates
└── features/           # Feature-specific translations
    ├── users.php
    ├── products.php
    └── orders.php
```

### File Naming

- Use singular form: `user.php` not `users.php`
- Use lowercase: `product.php` not `Product.php`
- Use hyphens for multi-word: `user-profile.php`

## 🔑 Key Structure

### UI Elements (`ui.php`)

```php
return [
    // Common actions
    'create' => 'Create',
    'read' => 'Read',
    'update' => 'Update',
    'delete' => 'Delete',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'submit' => 'Submit',
    'search' => 'Search',
    'filter' => 'Filter',
    'export' => 'Export',
    'import' => 'Import',
    
    // Common labels
    'name' => 'Name',
    'email' => 'Email',
    'password' => 'Password',
    'phone' => 'Phone',
    'address' => 'Address',
    'city' => 'City',
    'country' => 'Country',
    'status' => 'Status',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    
    // Navigation
    'dashboard' => 'Dashboard',
    'home' => 'Home',
    'profile' => 'Profile',
    'settings' => 'Settings',
    'logout' => 'Logout',
    
    // Messages
    'success' => 'Success!',
    'error' => 'Error!',
    'warning' => 'Warning!',
    'info' => 'Information',
    
    // Confirmations
    'delete_confirm' => 'Are you sure you want to delete this item?',
    'cancel_confirm' => 'Are you sure you want to cancel?',
    'save_confirm' => 'Save changes?',
    
    // Pagination
    'showing' => 'Showing',
    'to' => 'to',
    'of' => 'of',
    'results' => 'results',
    'previous' => 'Previous',
    'next' => 'Next',
];
```

### Authentication (`auth.php`)

```php
return [
    // Login
    'login' => 'Login',
    'login_title' => 'Login to your account',
    'login_button' => 'Sign In',
    'remember_me' => 'Remember me',
    'forgot_password' => 'Forgot password?',
    
    // Register
    'register' => 'Register',
    'register_title' => 'Create new account',
    'register_button' => 'Sign Up',
    'already_have_account' => 'Already have an account?',
    
    // Password Reset
    'reset_password' => 'Reset Password',
    'reset_password_title' => 'Reset your password',
    'reset_password_button' => 'Reset Password',
    'send_reset_link' => 'Send Reset Link',
    
    // Messages
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'logout_success' => 'You have been logged out successfully.',
];
```

### Validation (`validation.php`)

```php
return [
    // Field-specific
    'email_required' => 'Email is required',
    'email_invalid' => 'Email must be a valid email address',
    'password_required' => 'Password is required',
    'password_min_length' => 'Password must be at least :min characters',
    'password_confirmed' => 'Password confirmation does not match',
    
    // Generic
    'required' => 'The :attribute field is required',
    'min' => 'The :attribute must be at least :min characters',
    'max' => 'The :attribute may not be greater than :max characters',
    'unique' => 'The :attribute has already been taken',
    'exists' => 'The selected :attribute is invalid',
    
    // Custom attributes
    'attributes' => [
        'email' => 'email address',
        'password' => 'password',
        'name' => 'name',
    ],
];
```

### Components (`components.php`)

```php
return [
    // Form component
    'form' => [
        'required_field' => 'Required field',
        'optional_field' => 'Optional',
        'placeholder' => 'Enter :field',
        'select_option' => 'Select an option',
        'no_options' => 'No options available',
    ],
    
    // Table component
    'table' => [
        'search_placeholder' => 'Search...',
        'filter' => 'Filter',
        'export' => 'Export',
        'actions' => 'Actions',
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'no_data' => 'No data available',
        'showing_entries' => 'Showing',
        'to' => 'to',
        'of' => 'of',
        'results' => 'results',
    ],
    
    // Chart component
    'chart' => [
        'loading' => 'Loading chart...',
        'no_data' => 'No data to display',
        'export_image' => 'Export as image',
        'export_csv' => 'Export as CSV',
    ],
    
    // Modal component
    'modal' => [
        'close' => 'Close',
        'confirm' => 'Confirm',
        'cancel' => 'Cancel',
    ],
];
```

### Errors (`errors.php`)

```php
return [
    // HTTP errors
    '404' => [
        'title' => 'Page Not Found',
        'message' => 'The page you are looking for could not be found.',
    ],
    '403' => [
        'title' => 'Forbidden',
        'message' => 'You do not have permission to access this resource.',
    ],
    '500' => [
        'title' => 'Server Error',
        'message' => 'Something went wrong on our end. Please try again later.',
    ],
    
    // Application errors
    'database_error' => 'Database connection error',
    'file_not_found' => 'File not found',
    'permission_denied' => 'Permission denied',
    'invalid_input' => 'Invalid input provided',
];
```

### Feature-Specific (`features/users.php`)

```php
return [
    // Page titles
    'index_title' => 'Users',
    'create_title' => 'Create User',
    'edit_title' => 'Edit User',
    'show_title' => 'User Details',
    
    // Form labels
    'name_label' => 'Full Name',
    'email_label' => 'Email Address',
    'role_label' => 'User Role',
    'status_label' => 'Account Status',
    
    // Actions
    'create_button' => 'Create User',
    'update_button' => 'Update User',
    'delete_button' => 'Delete User',
    'activate_button' => 'Activate',
    'deactivate_button' => 'Deactivate',
    
    // Messages
    'created_success' => 'User created successfully',
    'updated_success' => 'User updated successfully',
    'deleted_success' => 'User deleted successfully',
    'not_found' => 'User not found',
    
    // Confirmations
    'delete_confirm' => 'Are you sure you want to delete this user?',
    'deactivate_confirm' => 'Are you sure you want to deactivate this user?',
];
```

## 🎨 Naming Patterns

### Action Keys

Format: `{action}` or `{action}_{object}`

```php
'create' => 'Create',
'create_user' => 'Create User',
'update' => 'Update',
'update_profile' => 'Update Profile',
'delete' => 'Delete',
'delete_account' => 'Delete Account',
```

### Label Keys

Format: `{field}_label` or just `{field}`

```php
'name' => 'Name',
'email_label' => 'Email Address',
'password' => 'Password',
'phone_label' => 'Phone Number',
```

### Message Keys

Format: `{action}_{status}` or `{context}_{type}`

```php
'created_success' => 'Created successfully',
'updated_error' => 'Failed to update',
'validation_failed' => 'Validation failed',
'permission_denied' => 'Permission denied',
```

### Confirmation Keys

Format: `{action}_confirm`

```php
'delete_confirm' => 'Are you sure you want to delete?',
'cancel_confirm' => 'Are you sure you want to cancel?',
'logout_confirm' => 'Are you sure you want to logout?',
```

### Title Keys

Format: `{page}_title` or `{section}_title`

```php
'index_title' => 'Users List',
'create_title' => 'Create New User',
'edit_title' => 'Edit User',
'profile_title' => 'User Profile',
```

## 📊 Hierarchical Structure

### Nested Keys

Use dot notation for logical grouping:

```php
// Good - Clear hierarchy
'components.table.actions.edit'
'features.users.messages.created_success'
'ui.button.primary.save'

// Bad - Flat structure
'table_actions_edit'
'users_created_success'
'primary_save_button'
```

### Maximum Depth

Limit nesting to 3-4 levels:

```php
// Good - 3 levels
'components.form.validation.required'

// Acceptable - 4 levels
'features.users.profile.settings.privacy'

// Bad - Too deep
'app.admin.features.users.profile.settings.privacy.email.notifications'
```

## 🌍 Pluralization

### Laravel Pluralization

Use pipe syntax for pluralization:

```php
'items_count' => '{0} No items|{1} One item|[2,*] :count items',
'users_online' => '{0} No users online|{1} One user online|[2,*] :count users online',
```

### Usage

```php
trans_choice('ui.items_count', 0)  // "No items"
trans_choice('ui.items_count', 1)  // "One item"
trans_choice('ui.items_count', 5)  // "5 items"
```

## 🔤 Parameters

### Parameter Naming

Use descriptive parameter names:

```php
// Good
'welcome_message' => 'Welcome, :name!',
'items_found' => 'Found :count items in :category',

// Bad
'welcome_message' => 'Welcome, :x!',
'items_found' => 'Found :a items in :b',
```

### Parameter Format

Use lowercase with underscores:

```php
// Good
':user_name'
':created_at'
':item_count'

// Bad
':userName'
':CreatedAt'
':ItemCount'
```

## ✅ Best Practices

### 1. Consistency

Maintain consistent naming across all translation files:

```php
// Good - Consistent
'ui.create'
'ui.update'
'ui.delete'

// Bad - Inconsistent
'ui.create'
'ui.edit'  // Should be 'update'
'ui.remove'  // Should be 'delete'
```

### 2. Avoid Duplication

Don't duplicate keys across files:

```php
// Bad - Duplicated in ui.php and features/users.php
'ui.name' => 'Name'
'features.users.name' => 'Name'

// Good - Use ui.name everywhere
'ui.name' => 'Name'
// In features/users.php, reference ui.name or use specific context
'features.users.full_name' => 'Full Name'
```

### 3. Context-Specific Keys

Use context-specific keys when meaning differs:

```php
// Good - Different contexts
'ui.save' => 'Save'  // General save button
'features.users.save_profile' => 'Save Profile'  // Specific context
'features.products.save_draft' => 'Save as Draft'  // Specific context
```

### 4. Avoid Hardcoded Text

Never hardcode translatable text:

```php
// Bad
<button>Save</button>

// Good
<button>{{ __('ui.save') }}</button>
```

### 5. Document Custom Keys

Add comments for complex or non-obvious keys:

```php
return [
    // User status values - must match database enum
    'status_active' => 'Active',
    'status_inactive' => 'Inactive',
    'status_pending' => 'Pending',
    
    // Special formatting for currency display
    'price_format' => ':currency :amount',
];
```

## 🔍 Key Discovery

### Finding Keys

Use grep to find translation keys:

```bash
# Find all uses of a specific key
grep -r "__('ui.save')" resources/views/

# Find all translation keys in a file
grep -o "__('[^']*')" resources/views/users/index.blade.php
```

### Extracting Keys

Use the CanvaStack translation command:

```bash
# Extract all translation keys from views
php artisan canvastack:translate --path=resources/views

# Extract keys and export to CSV
php artisan canvastack:translate --path=resources/views --format=csv
```

## 📚 Related Documentation

- [Implementation Guide](implementation-guide.md) - Complete i18n implementation guide
- [Translation Management](translation-management.md) - Managing translations
- [Developer Tools](developer-tools.md) - Translation developer tools
- [Translation API](translation-api.md) - Translation API reference

---

**Last Updated**: 2026-02-27  
**Version**: 1.0.0  
**Status**: Published
