# FormConstants Reference

## Overview

The `FormConstants` class provides centralized constants for Form Components, replacing magic strings with type-safe constants. This improves code maintainability, prevents typos, and enables IDE autocomplete.

**Namespace**: `Canvastack\Canvastack\Library\Constants\FormConstants`

## Benefits

✅ **Type Safety** - Constants are defined once, used everywhere
✅ **IDE Autocomplete** - Full IntelliSense support
✅ **Typo Prevention** - Compile-time error detection
✅ **Maintainability** - Change once, update everywhere
✅ **Documentation** - Self-documenting code

## Usage

```php
use Canvastack\Canvastack\Library\Constants\FormConstants;

// Instead of magic strings
$attributes['class'] = 'form-control';  // ❌ Bad

// Use constants
$attributes[FormConstants::ATTR_CLASS] = FormConstants::CLASS_FORM_CONTROL;  // ✅ Good
```

---

## CSS Classes

### Form Control Classes

```php
// Bootstrap form control class
FormConstants::CLASS_FORM_CONTROL = 'form-control'

// Bootstrap button class
FormConstants::CLASS_BTN = 'btn'

// Chosen select plugin classes
FormConstants::CLASS_CHOSEN_SELECT = 'chosen-select-deselect chosen-selectbox'
FormConstants::DEFAULT_SELECTBOX_CLASS = 'chosen-select-deselect chosen-selectbox'
```

**Example:**
```php
$attributes[FormConstants::ATTR_CLASS] = FormConstants::CLASS_FORM_CONTROL;
```

### Checkbox Classes

```php
// Checkbox wrapper class
FormConstants::CLASS_CKBOX = 'ckbox'

// Primary styled checkbox
FormConstants::CLASS_CKBOX_PRIMARY = 'ckbox-primary'

// Switch toggle class
FormConstants::CLASS_SWITCH = 'switch'
```

**Example:**
```php
$form->checkbox('enabled', [1 => 'Enabled'], [], [
    'check_type' => FormConstants::CLASS_SWITCH
], true);
```

### Plugin Classes

```php
// CKEditor rich text editor
FormConstants::CLASS_CKEDITOR = 'ckeditor'

// Tags input plugin
FormConstants::CLASS_TAGSINPUT = 'tagsinput'

// Date picker plugin
FormConstants::CLASS_DATEPICKER = 'datepicker'

// Time picker plugin
FormConstants::CLASS_TIMEPICKER = 'timepicker'
```

**Example:**
```php
$form->textarea('content', null, [
    FormConstants::ATTR_CLASS => FormConstants::CLASS_CKEDITOR
], true);
```

---

## HTML Attributes

### Standard Attributes

```php
// HTML class attribute
FormConstants::ATTR_CLASS = 'class'

// HTML id attribute
FormConstants::ATTR_ID = 'id'

// HTML role attribute
FormConstants::ATTR_ROLE = 'role'

// Data-role attribute
FormConstants::ATTR_DATA_ROLE = 'data-role'

// Placeholder attribute
FormConstants::ATTR_PLACEHOLDER = 'placeholder'

// Maxlength attribute
FormConstants::ATTR_MAXLENGTH = 'maxlength'

// Disabled attribute
FormConstants::ATTR_DISABLED = 'disabled'

// Readonly attribute
FormConstants::ATTR_READONLY = 'readonly'

// Required attribute
FormConstants::ATTR_REQUIRED = 'required'
```

**Example:**
```php
$attributes = [
    FormConstants::ATTR_CLASS => FormConstants::CLASS_FORM_CONTROL,
    FormConstants::ATTR_PLACEHOLDER => 'Enter your name',
    FormConstants::ATTR_MAXLENGTH => 50,
    FormConstants::ATTR_REQUIRED => true
];
```

---

## ARIA Attributes

### Basic ARIA Attributes

```php
// ARIA label for accessible naming
FormConstants::ARIA_LABEL = 'aria-label'

// ARIA checked state
FormConstants::ARIA_CHECKED = 'aria-checked'

// ARIA disabled state
FormConstants::ARIA_DISABLED = 'aria-disabled'

// ARIA required state
FormConstants::ARIA_REQUIRED = 'aria-required'

// ARIA invalid state
FormConstants::ARIA_INVALID = 'aria-invalid'

// ARIA describedby for associations
FormConstants::ARIA_DESCRIBEDBY = 'aria-describedby'

// ARIA hidden for hiding from screen readers
FormConstants::ARIA_HIDDEN = 'aria-hidden'
```

**Example:**
```php
$attributes = [
    FormConstants::ARIA_LABEL => 'Username',
    FormConstants::ARIA_REQUIRED => 'true',
    FormConstants::ARIA_DESCRIBEDBY => 'username-help'
];
```

### Tab Navigation ARIA

```php
// ARIA selected state for tabs
FormConstants::ARIA_SELECTED = 'aria-selected'

// ARIA controls for tab associations
FormConstants::ARIA_CONTROLS = 'aria-controls'

// ARIA labelledby for panel associations
FormConstants::ARIA_LABELLEDBY = 'aria-labelledby'
```

**Example:**
```php
// Used internally by Tab trait
$tabAttributes = [
    FormConstants::ARIA_SELECTED => 'true',
    FormConstants::ARIA_CONTROLS => 'panel-1'
];
```

### ARIA Live Regions

```php
// ARIA live assertive (interrupts immediately)
FormConstants::ARIA_LIVE = 'aria-live'
FormConstants::ARIA_LIVE_ASSERTIVE = 'assertive'

// ARIA live polite (waits for screen reader)
FormConstants::ARIA_LIVE_POLITE = 'polite'

// ARIA live off (disables announcements)
FormConstants::ARIA_LIVE_OFF = 'off'
```

**Example:**
```php
// Error message
$errorAttributes = [
    FormConstants::ATTR_ROLE => 'alert',
    FormConstants::ARIA_LIVE => FormConstants::ARIA_LIVE_ASSERTIVE
];

// Success message
$successAttributes = [
    FormConstants::ATTR_ROLE => 'alert',
    FormConstants::ARIA_LIVE => FormConstants::ARIA_LIVE_POLITE
];
```

---

## File Paths

```php
// Thumbnail directory path
FormConstants::PATH_THUMB = 'thumb'

// Assets directory path
FormConstants::PATH_ASSETS = 'assets'
```

**Example:**
```php
$thumbnailPath = $uploadPath . '/' . FormConstants::PATH_THUMB;
$assetPath = FormConstants::PATH_ASSETS . '/uploads';
```

---

## Tab Markers

```php
// Opening marker for tab sections
FormConstants::MARKER_OPEN_TAB = '--[openTabHTMLForm]--'

// Closing marker for tab sections
FormConstants::MARKER_CLOSE_TAB = '--[closeTabHTMLForm]--'
```

**Example:**
```php
// Used internally by Tab trait
if (str_contains($content, FormConstants::MARKER_OPEN_TAB)) {
    // Parse tab structure
}
```

---

## Plugin Names

```php
// CKEditor plugin identifier
FormConstants::PLUGIN_CKEDITOR = 'ckeditor'

// Tags input plugin identifier
FormConstants::PLUGIN_TAGSINPUT = 'tagsinput'

// Date picker plugin identifier
FormConstants::PLUGIN_DATEPICKER = 'datepicker'

// Time picker plugin identifier
FormConstants::PLUGIN_TIMEPICKER = 'timepicker'

// Chosen select plugin identifier
FormConstants::PLUGIN_CHOSEN = 'chosen'
```

**Example:**
```php
if (canvastack_form_check_str_attr($attributes, FormConstants::PLUGIN_CKEDITOR)) {
    $this->element_plugins[$name] = FormConstants::PLUGIN_CKEDITOR;
}
```

---

## Validation Rules

```php
// Required field validation
FormConstants::VALIDATION_REQUIRED = 'required'

// Email format validation
FormConstants::VALIDATION_EMAIL = 'email'

// Numeric value validation
FormConstants::VALIDATION_NUMERIC = 'numeric'

// Minimum value validation
FormConstants::VALIDATION_MIN = 'min'

// Maximum value validation
FormConstants::VALIDATION_MAX = 'max'

// MIME type validation for files
FormConstants::VALIDATION_MIMES = 'mimes'

// Maximum file size validation
FormConstants::VALIDATION_MAX_FILE_SIZE = 'max'
```

**Example:**
```php
$rules = [
    'username' => FormConstants::VALIDATION_REQUIRED . '|' . FormConstants::VALIDATION_MIN . ':3',
    'email' => FormConstants::VALIDATION_REQUIRED . '|' . FormConstants::VALIDATION_EMAIL,
    'age' => FormConstants::VALIDATION_REQUIRED . '|' . FormConstants::VALIDATION_NUMERIC
];
```

---

## Check Types

```php
// Primary checkbox style (blue)
FormConstants::CHECK_TYPE_PRIMARY = 'primary'

// Success checkbox style (green)
FormConstants::CHECK_TYPE_SUCCESS = 'success'

// Danger checkbox style (red)
FormConstants::CHECK_TYPE_DANGER = 'danger'

// Warning checkbox style (yellow)
FormConstants::CHECK_TYPE_WARNING = 'warning'

// Info checkbox style (light blue)
FormConstants::CHECK_TYPE_INFO = 'info'

// Switch toggle type
FormConstants::CHECK_TYPE_SWITCH = 'switch'
```

**Example:**
```php
// Success checkbox
$form->checkbox('active', [1 => 'Active'], [1], [
    'check_type' => FormConstants::CHECK_TYPE_SUCCESS
], true);

// Switch toggle
$form->checkbox('enabled', [1 => 'Enabled'], [1], [
    'check_type' => FormConstants::CHECK_TYPE_SWITCH
], true);
```

---

## Alert Types

```php
// Success alert (green)
FormConstants::ALERT_SUCCESS = 'success'

// Danger alert (red)
FormConstants::ALERT_DANGER = 'danger'

// Warning alert (yellow)
FormConstants::ALERT_WARNING = 'warning'

// Info alert (blue)
FormConstants::ALERT_INFO = 'info'
```

**Example:**
```php
// Used internally for alert messages
$alertClass = 'alert alert-' . FormConstants::ALERT_SUCCESS;
$ariaLive = FormConstants::ARIA_LIVE_POLITE;
```

---

## Status Values

```php
// Active status values
FormConstants::ACTIVE_STATUS_YES = 1
FormConstants::ACTIVE_STATUS_NO = 0

// Request status values
FormConstants::REQUEST_STATUS_PENDING = 0
FormConstants::REQUEST_STATUS_ACCEPT = 1
FormConstants::REQUEST_STATUS_BLOCKED = 2
FormConstants::REQUEST_STATUS_BAN = 3
```

**Example:**
```php
$user->status = FormConstants::ACTIVE_STATUS_YES;
$request->status = FormConstants::REQUEST_STATUS_PENDING;
```

---

## Migration from Magic Strings

### Before (Magic Strings)

```php
// ❌ Bad - magic strings
$attributes['class'] = 'form-control';
$attributes['placeholder'] = 'Enter text';
$attributes['required'] = true;
$attributes['aria-label'] = 'Username';
$attributes['aria-required'] = 'true';

$form->checkbox('active', [1 => 'Active'], [], [
    'check_type' => 'success'
], true);
```

### After (Constants)

```php
// ✅ Good - type-safe constants
$attributes[FormConstants::ATTR_CLASS] = FormConstants::CLASS_FORM_CONTROL;
$attributes[FormConstants::ATTR_PLACEHOLDER] = 'Enter text';
$attributes[FormConstants::ATTR_REQUIRED] = true;
$attributes[FormConstants::ARIA_LABEL] = 'Username';
$attributes[FormConstants::ARIA_REQUIRED] = 'true';

$form->checkbox('active', [1 => 'Active'], [], [
    'check_type' => FormConstants::CHECK_TYPE_SUCCESS
], true);
```

---

## Best Practices

### 1. Always Use Constants

```php
// ✅ Good
$attributes[FormConstants::ATTR_CLASS] = FormConstants::CLASS_FORM_CONTROL;

// ❌ Bad
$attributes['class'] = 'form-control';
```

### 2. Import at Top of File

```php
use Canvastack\Canvastack\Library\Constants\FormConstants;

class MyController {
    public function create() {
        $attributes[FormConstants::ATTR_CLASS] = FormConstants::CLASS_FORM_CONTROL;
    }
}
```

### 3. Use for Validation Rules

```php
// ✅ Good
$rules = [
    'email' => FormConstants::VALIDATION_REQUIRED . '|' . FormConstants::VALIDATION_EMAIL
];

// ❌ Bad
$rules = [
    'email' => 'required|email'
];
```

### 4. Use for ARIA Attributes

```php
// ✅ Good
$attributes[FormConstants::ARIA_LABEL] = 'Username';
$attributes[FormConstants::ARIA_REQUIRED] = 'true';

// ❌ Bad
$attributes['aria-label'] = 'Username';
$attributes['aria-required'] = 'true';
```

---

## IDE Autocomplete

With FormConstants, your IDE provides full autocomplete:

```php
FormConstants::  // Type this and see all available constants
FormConstants::ATTR_  // See all attribute constants
FormConstants::ARIA_  // See all ARIA constants
FormConstants::CLASS_  // See all CSS class constants
FormConstants::VALIDATION_  // See all validation constants
```

---

*For SafeHtml system, see [SAFEHTML.md](SAFEHTML.md)*
*For API reference, see [API_REFERENCE.md](API_REFERENCE.md)*
