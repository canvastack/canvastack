# Element Traits API Reference

## Overview

Element traits provide methods for generating specific form input types. All traits are used by the `Objects` class and provide automatic XSS protection, ARIA attributes, and validation support.

## Table of Contents

1. [Text Trait](#text-trait) - Text inputs, textarea, email, number, password, tags
2. [Check Trait](#check-trait) - Checkboxes and switches
3. [Radio Trait](#radio-trait) - Radio buttons
4. [Select Trait](#select-trait) - Dropdowns and month picker
5. [File Trait](#file-trait) - File uploads with security
6. [DateTime Trait](#datetime-trait) - Date, time, datetime, daterange pickers
7. [Tab Trait](#tab-trait) - Tab navigation

---

## Text Trait

### `text()`

Generates a standard text input field.

```php
public function text(
    string $name,
    ?string $value = null,
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Parameters:**
- `$name` - Field name
- `$value` - Default value
- `$attributes` - HTML attributes
- `$label` - Show label (true), hide (false), or custom label text

**Example:**
```php
// Basic text input
$form->text('username', null, [], true);

// With default value
$form->text('username', 'john_doe', [], true);

// With custom attributes
$form->text('username', null, ['class' => 'custom', 'maxlength' => 50], true);

// Without label
$form->text('username', null, [], false);

// Custom label
$form->text('username', null, [], 'Your Username');
```

**Security:** All values are automatically escaped.

**Accessibility:**
- Label `for` attribute matches input `id`
- `aria-label` added if label is hidden
- `aria-required` added for required fields

---


### `textarea()`

Generates a textarea element with optional character limit.

```php
public function textarea(
    string $name,
    ?string $value = null,
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Parameters:**
- `$name` - Field name (supports `name|limit:N` format for character limit)
- `$value` - Default content
- `$attributes` - HTML attributes
- `$label` - Show/hide/custom label

**Example:**
```php
// Basic textarea
$form->textarea('description', null, [], true);

// With character limit
$form->textarea('bio|limit:500', null, [], true);

// With CKEditor
$form->textarea('content', null, ['class' => 'ckeditor'], true);

// With default value
$form->textarea('notes', 'Default text here', [], true);
```

**Features:**
- Character limit with visual counter
- CKEditor integration via class attribute
- Auto-resize support

---

### `email()`

Generates an email input with HTML5 validation.

```php
public function email(
    string $name,
    ?string $value = null,
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Example:**
```php
$form->email('user_email', null, [], true);
```

**Features:**
- HTML5 email validation
- Browser-native email keyboard on mobile
- Automatic format validation

---

### `number()`

Generates a number input with HTML5 validation.

```php
public function number(
    string $name,
    ?string $value = null,
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Example:**
```php
// Basic number input
$form->number('age', null, [], true);

// With min/max
$form->number('quantity', null, ['min' => 1, 'max' => 100, 'step' => 1], true);
```

**Features:**
- HTML5 number validation
- Numeric keyboard on mobile
- Min/max/step support

---

### `password()`

Generates a password input field.

```php
public function password(
    string $name,
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Example:**
```php
$form->password('user_password', [], true);

// With minimum length
$form->password('new_password', ['minlength' => 8], true);
```

**Security:**
- Password values are bcrypt hashed before storage
- Never displays existing values
- Masked input for privacy

---

### `tags()`

Generates a tags input field using Bootstrap Tags Input plugin.

```php
public function tags(
    string $name,
    ?string $value = null,
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Example:**
```php
// Basic tags input
$form->tags('keywords', null, [], true);

// With default tags
$form->tags('skills', 'php,laravel,mysql', [], true);
```

**Features:**
- Multiple tag entry
- Comma-separated values
- Tag removal support
- Custom styling

---

## Check Trait

### `checkbox()`

Generates checkbox elements (single or multiple).

```php
public function checkbox(
    string $name,
    array $values = [],
    array|string $selected = [],
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Parameters:**
- `$name` - Field name
- `$values` - Array of checkbox options `[value => label]`
- `$selected` - Selected value(s)
- `$attributes` - HTML attributes (supports `check_type` for styling)
- `$label` - Show/hide/custom label

**Example:**
```php
// Single checkbox
$form->checkbox('terms', [1 => 'I agree to terms'], [1], [], true);

// Multiple checkboxes
$form->checkbox('interests', [
    'sports' => 'Sports',
    'music' => 'Music',
    'reading' => 'Reading'
], ['sports', 'music'], [], true);

// Styled checkbox
$form->checkbox('active', [1 => 'Active'], [1], ['check_type' => 'success'], true);

// Switch style
$form->checkbox('enabled', [1 => 'Enabled'], [1], ['check_type' => 'switch'], true);
```

**Checkbox Types:**
- `primary` (default) - Blue checkbox
- `success` - Green checkbox
- `danger` - Red checkbox
- `warning` - Yellow checkbox
- `info` - Light blue checkbox
- `switch` - Toggle switch style

**Security:** All labels and values are escaped.

**Accessibility:**
- `aria-checked` indicates selection state
- `aria-label` for checkboxes without visible labels
- `aria-required` for required checkboxes

---

## Radio Trait

### `radiobox()`

Generates radio button elements.

```php
public function radiobox(
    string $name,
    array $values = [],
    bool|string $selected = false,
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Parameters:**
- `$name` - Field name
- `$values` - Array of radio options `[value => label]`
- `$selected` - Selected value
- `$attributes` - HTML attributes (supports `radio_type` for styling)
- `$label` - Show/hide/custom label

**Example:**
```php
// Basic radio buttons
$form->radiobox('gender', [
    'male' => 'Male',
    'female' => 'Female',
    'other' => 'Other'
], 'male', [], true);

// Styled radio buttons
$form->radiobox('status', [
    'active' => 'Active',
    'inactive' => 'Inactive'
], 'active', ['radio_type' => 'success'], true);
```

**Radio Types:**
- `primary` (default) - Blue radio
- `success` - Green radio
- `danger` - Red radio
- `warning` - Yellow radio
- `info` - Light blue radio

**Security:** All labels and values are escaped.

**Accessibility:**
- `aria-checked` indicates selection state
- `aria-label` for radio buttons without visible labels
- `aria-required` for required radio groups

---


## Select Trait

### `selectbox()`

Generates a select dropdown element.

```php
public function selectbox(
    string $name,
    array $values = [],
    bool|string|int|array|null $selected = false,
    array $attributes = [],
    bool|string|null $label = true,
    array|bool $set_first_value = [null => '']
): void
```

**Parameters:**
- `$name` - Field name
- `$values` - Array of options `[value => label]`
- `$selected` - Selected value(s) (array for multi-select)
- `$attributes` - HTML attributes
- `$label` - Show/hide/custom label
- `$set_first_value` - First empty option (false to skip)

**Example:**
```php
// Basic select
$form->selectbox('country', [
    'us' => 'United States',
    'uk' => 'United Kingdom',
    'ca' => 'Canada'
], false, [], true);

// With pre-selected value
$form->selectbox('status', [
    'active' => 'Active',
    'inactive' => 'Inactive'
], 'active', [], true);

// Without empty first option
$form->selectbox('role', [
    'admin' => 'Administrator',
    'user' => 'User'
], false, [], true, false);

// Multi-select
$form->selectbox('tags[]', [
    'php' => 'PHP',
    'laravel' => 'Laravel',
    'mysql' => 'MySQL'
], ['php', 'laravel'], ['multiple' => true], true);
```

**Features:**
- Chosen.js plugin for enhanced dropdowns
- Search functionality
- Multi-select support
- Keyboard navigation

**Security:** All option labels and values are escaped.

**Accessibility:**
- `aria-required` for required selects
- `aria-invalid` for validation errors
- `aria-describedby` for help text

---

### `month()`

Generates a month picker select dropdown.

```php
public function month(
    string $name,
    ?string $value = null,
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Example:**
```php
// Basic month picker
$form->month('birth_month', null, [], true);

// With default value
$form->month('report_month', '2026-03', [], true);
```

**Features:**
- Month selection (January - December)
- Year selection
- Chosen.js integration

---

## File Trait

### `file()`

Generates a file upload input with comprehensive security.

```php
public function file(
    string $name,
    array $attributes = [],
    bool $label = true
): void
```

**Parameters:**
- `$name` - Field name
- `$attributes` - HTML attributes (supports `imagepreview` for image preview)
- `$label` - Show/hide label

**Example:**
```php
// Basic file input
$form->file('document', [], true);

// With image preview
$form->file('avatar', ['imagepreview'], true);

// With existing file
$form->file('photo', ['value' => 'assets/uploads/photo.jpg', 'imagepreview'], true);
```

**Features:**
- Image preview support
- File type validation
- Size validation
- Drag & drop support

---

### `fileUpload()`

Processes file upload with security validation.

```php
public function fileUpload(
    string $upload_path,
    object $request,
    array $fileInfo
): void
```

**Parameters:**
- `$upload_path` - Upload directory path
- `$request` - Laravel request object
- `$fileInfo` - File configuration array

**Example:**
```php
$form->fileUpload('uploads/avatars', $request, [
    'input_name' => 'avatar',
    'validation' => 'required|image|max:2048',
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif'],
    'allowed_mimes' => ['image/jpeg', 'image/png', 'image/gif'],
    'max_size' => 2097152, // 2MB in bytes
    'generate_thumbnail' => true
]);
```

**Security Features:**
- File extension whitelist validation
- MIME type content validation (not just extension)
- Path traversal prevention
- Random filename generation
- File permissions set to 0644
- Thumbnail validation
- Comprehensive error handling
- Security logging

**Allowed File Types (Default):**
- Images: jpg, jpeg, png, gif, webp, svg
- Documents: pdf, doc, docx, xls, xlsx, ppt, pptx
- Other: txt, csv, zip, rar

**Maximum File Size (Default):** 10MB

**Security Warnings:**
- ⚠️ Never trust file extensions alone - MIME type is validated from content
- ⚠️ Filenames are randomized to prevent overwrite attacks
- ⚠️ Paths are validated to prevent directory traversal
- ⚠️ Failed uploads are automatically cleaned up

---


## DateTime Trait

### `date()`

Generates a date picker input field.

```php
public function date(
    string $name,
    ?string $value = null,
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Example:**
```php
// Basic date input
$form->date('birth_date', null, [], true);

// With default value
$form->date('start_date', '2026-03-31', [], true);

// With min/max dates
$form->date('event_date', null, [
    'min' => '2026-01-01',
    'max' => '2026-12-31'
], true);
```

**Features:**
- Calendar popup
- Date format validation
- Min/max date restrictions
- Keyboard navigation

---

### `datetime()`

Generates a datetime picker input field.

```php
public function datetime(
    string $name,
    ?string $value = null,
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Example:**
```php
// Basic datetime input
$form->datetime('event_time', null, [], true);

// With default value
$form->datetime('meeting_time', '2026-03-31 14:30:00', [], true);
```

**Features:**
- Combined date and time selection
- Calendar and time picker
- Format validation

---

### `daterange()`

Generates a date range picker input field.

```php
public function daterange(
    string $name,
    ?string $value = null,
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Example:**
```php
// Basic daterange input
$form->daterange('booking_period', null, [], true);

// With default range
$form->daterange('report_period', '2026-03-01 - 2026-03-31', [], true);
```

**Features:**
- Start and end date selection
- Range validation
- Visual range display

---

### `time()`

Generates a time picker input field.

```php
public function time(
    string $name,
    ?string $value = null,
    array $attributes = [],
    bool|string|null $label = true
): void
```

**Example:**
```php
// Basic time input
$form->time('start_time', null, [], true);

// With default value
$form->time('alarm_time', '14:30', [], true);

// 24-hour format
$form->time('meeting_time', null, ['data-format' => 'HH:mm'], true);
```

**Features:**
- Time picker popup
- 12/24 hour format support
- Minute intervals
- Keyboard input

**Accessibility:**
- `aria-required` for required date/time fields
- `aria-invalid` for validation errors
- `aria-describedby` for format hints

---

## Tab Trait

### `openTab()`

Opens a new tab section.

```php
public function openTab(
    string $label,
    string|false $class = false
): void
```

**Parameters:**
- `$label` - Tab label text
- `$class` - Optional CSS class for tab styling

**Example:**
```php
$form->openTab('Personal Information');
$form->text('name');
$form->email('email');

$form->openTab('Address Details', 'custom-tab-class');
$form->text('street');
$form->text('city');

$form->closeTab();
```

---

### `addTabContent()`

Adds custom HTML content to a tab.

```php
public function addTabContent(string $content): void
```

**Example:**
```php
$form->openTab('Instructions');
$form->addTabContent('<div class="alert alert-info">Please fill all required fields.</div>');
$form->closeTab();
```

**Security:** Content is escaped to prevent XSS.

---

### `closeTab()`

Closes all open tabs.

```php
public function closeTab(): void
```

**Example:**
```php
$form->openTab('Tab 1');
$form->text('field1');

$form->openTab('Tab 2');
$form->text('field2');

$form->closeTab(); // Closes all tabs
```

**Note:** Only one `closeTab()` call is needed to close all tabs.

---

### Complete Tab Example

```php
$form->open('/users/store');

$form->openTab('Basic Info');
$form->text('name', null, [], true);
$form->email('email', null, [], true);

$form->openTab('Profile');
$form->textarea('bio', null, [], true);
$form->file('avatar', ['imagepreview'], true);

$form->openTab('Settings');
$form->checkbox('notifications', [1 => 'Enable notifications'], [], [], true);
$form->selectbox('timezone', [
    'UTC' => 'UTC',
    'EST' => 'Eastern Time',
    'PST' => 'Pacific Time'
], false, [], true);

$form->closeTab();

$form->close();
```

**Features:**
- Bootstrap tab navigation
- ARIA attributes for accessibility
- Keyboard navigation
- Active tab highlighting

**Accessibility:**
- `role="tablist"` for tab container
- `role="tab"` for tab links
- `role="tabpanel"` for tab content
- `aria-selected` for active tab
- `aria-controls` for tab-panel association
- `aria-labelledby` for panel-tab association

**Security:**
- Tab labels are escaped
- Tab markers are validated
- Content is sanitized

---

## Common Patterns

### Form with All Element Types

```php
$form->setValidations([
    'username' => 'required|min:3',
    'email' => 'required|email',
    'age' => 'required|numeric|min:18',
    'bio' => 'required|max:500',
    'avatar' => 'required|image|max:2048',
    'country' => 'required',
    'interests' => 'required|array',
    'gender' => 'required',
    'birth_date' => 'required|date',
    'terms' => 'required'
]);

$form->open('/users/store', 'POST', 'horizontal', true);

// Text inputs
$form->text('username', null, [], true);
$form->email('email', null, [], true);
$form->number('age', null, ['min' => 18], true);
$form->password('password', [], true);

// Textarea
$form->textarea('bio|limit:500', null, [], true);

// File upload
$form->file('avatar', ['imagepreview'], true);

// Select
$form->selectbox('country', [
    'us' => 'United States',
    'uk' => 'United Kingdom'
], false, [], true);

// Checkboxes
$form->checkbox('interests', [
    'sports' => 'Sports',
    'music' => 'Music'
], [], [], true);

// Radio buttons
$form->radiobox('gender', [
    'male' => 'Male',
    'female' => 'Female'
], false, [], true);

// Date picker
$form->date('birth_date', null, [], true);

// Terms checkbox
$form->checkbox('terms', [1 => 'I agree to terms and conditions'], [], [], true);

$form->close();
```

---

*For Objects class methods, see [OBJECTS_CLASS.md](OBJECTS_CLASS.md)*
*For security guidelines, see [SECURITY.md](SECURITY.md)*
*For accessibility guidelines, see [ACCESSIBILITY.md](ACCESSIBILITY.md)*
