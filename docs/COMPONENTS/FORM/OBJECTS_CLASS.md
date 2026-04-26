# Objects Class API Reference

## Overview

The `Objects` class is the main entry point for form generation. It manages the form lifecycle (open → elements → close), handles model binding, validation propagation, and coordinates with Laravel's Form Facade.

**Namespace**: `Canvastack\Canvastack\Library\Components\Form\Objects`

## Public Methods

### Form Lifecycle Methods

#### `open()`

Opens a form tag with specified action, method, and options.

```php
public function open(
    string|false $path = false,
    string|false $method = false,
    string|false $type = false,
    bool $file = false
): void
```

**Parameters:**
- `$path` - Form action URL (default: current route)
- `$method` - HTTP method: 'POST', 'GET', 'PUT', 'PATCH', 'DELETE' (default: 'POST')
- `$type` - Form type: 'horizontal', 'vertical', 'inline' (default: 'horizontal')
- `$file` - Enable file upload support (adds enctype="multipart/form-data")

**Example:**
```php
// Basic form
$form->open('/users/store', 'POST');

// Form with file upload
$form->open('/profile/update', 'POST', 'horizontal', true);

// Form with custom type
$form->open('/search', 'GET', 'inline');
```

**Security:** All parameters are escaped to prevent XSS attacks.

---


#### `model()`

Binds a model to the form for automatic value population.

```php
public function model(
    object|string|null $model = null,
    int|false $row_selected = false,
    string|false $path = false,
    bool $file = false,
    string|false $type = false
): void
```

**Parameters:**
- `$model` - Eloquent model instance or model class name
- `$row_selected` - Model ID to load (if string model name provided)
- `$path` - Form action URL
- `$file` - Enable file upload support
- `$type` - Form type

**Example:**
```php
// Bind existing model
$user = User::find(1);
$form->model($user, false, '/users/1', false, 'horizontal');

// Load model by ID
$form->model('App\Models\User', 1, '/users/1');
```

**Security:**
- Model name is encrypted to prevent tampering
- Hidden attributes are respected (not exposed in form)
- Mass assignment protection is enforced

---

#### `close()`

Closes the form tag and optionally adds action buttons.

```php
public function close(
    string|false $action_buttons = false,
    array|false $option_buttons = false,
    string|false $prefix = false,
    string|false $suffix = false
): void
```

**Parameters:**
- `$action_buttons` - Button type: 'submit', 'reset', 'button', or false for default submit
- `$option_buttons` - Array of additional button configurations
- `$prefix` - HTML to prepend before buttons
- `$suffix` - HTML to append after buttons

**Example:**
```php
// Default submit button
$form->close();

// Custom button
$form->close('submit');

// Multiple buttons
$form->close('submit', [
    ['type' => 'reset', 'label' => 'Clear'],
    ['type' => 'button', 'label' => 'Cancel', 'onclick' => 'history.back()']
]);
```

---

### Validation Methods

#### `setValidations()`

Sets validation rules that will be propagated to form elements.

```php
public function setValidations(array $data = []): void
```

**Parameters:**
- `$data` - Array of validation rules (Laravel validation format)

**Example:**
```php
$form->setValidations([
    'username' => 'required|min:3|max:50',
    'email' => 'required|email',
    'age' => 'required|numeric|min:18',
    'avatar' => 'required|image|max:2048'
]);
```

**Automatic Attribute Propagation:**
- `required` → adds `required` attribute
- `email` → sets `type="email"`
- `numeric` → sets `type="number"`
- `min:N` → adds `min="N"` attribute
- `max:N` → adds `max="N"` or `maxlength="N"` attribute
- `mimes:jpg,png` → adds `accept=".jpg,.png"` attribute

---


### Utility Methods

#### `label()`

Generates a label element for a form field.

```php
public function label(
    string $name,
    string $value,
    array $attributes = []
): string
```

**Parameters:**
- `$name` - Field name (for attribute)
- `$value` - Label text
- `$attributes` - HTML attributes

**Example:**
```php
echo $form->label('username', 'Username', ['class' => 'control-label']);
// Output: <label for="username" class="control-label">Username</label>
```

**Security:** Label text is automatically escaped.

---

#### `sync()`

Creates an AJAX-powered relational field (dependent dropdown).

```php
public function sync(
    string $source_field,
    string $target_field,
    string $values,
    ?string $labels = null,
    string $query,
    mixed $selected = null
): void
```

**Parameters:**
- `$source_field` - Source field name (triggers change)
- `$target_field` - Target field name (gets updated)
- `$values` - Database column for option values
- `$labels` - Database column for option labels
- `$query` - Encrypted database query
- `$selected` - Pre-selected value

**Example:**
```php
// Country → City dropdown
$form->sync(
    'country_id',
    'city_id',
    'id',
    'name',
    encrypt('SELECT id, name FROM cities WHERE country_id = ?'),
    null
);
```

**Security:**
- Query is encrypted to prevent SQL injection
- Parameters are validated before execution
- Results are sanitized before return

---

#### `token()`

Adds CSRF token field to the form.

```php
public function token(): void
```

**Example:**
```php
$form->open('/users/store');
$form->token(); // Adds {{ csrf_field() }}
```

**Note:** CSRF token is automatically added by `open()` method. This method is for manual token insertion.

---

### Advanced Methods

#### `addAttributes()`

Adds custom attributes to the next form element.

```php
public function addAttributes(array $attributes = []): void
```

**Parameters:**
- `$attributes` - Array of HTML attributes

**Example:**
```php
$form->addAttributes(['data-validate' => 'true', 'data-min' => '5']);
$form->text('username');
```

**Security:** Dangerous event handlers (onclick, onerror, etc.) are automatically blocked.

---

## Security Features

### XSS Protection

All user-controllable data is automatically escaped:
- Form field names
- Field values
- Attribute values
- Label text
- Error messages

### Input Validation

- Attributes are validated to block dangerous event handlers
- File uploads are validated for type, size, and MIME type
- Paths are validated to prevent directory traversal
- Encrypted data is validated for integrity

### Encryption Security

- Model names are encrypted with integrity checking
- AJAX queries are encrypted to prevent SQL injection
- Encrypted data is validated before decryption

---

## Accessibility Features

### ARIA Attributes

Automatically added based on field state:
- `aria-required="true"` for required fields
- `aria-invalid="true"` for fields with errors
- `aria-describedby` for error messages and help text
- `aria-label` for fields without visible labels

### Label Associations

- Label `for` attribute matches input `id`
- Proper label-input association for screen readers
- Required fields include both visual (*) and `aria-required`

---

## Best Practices

### 1. Always Set Validation Rules

```php
// Good
$form->setValidations(['email' => 'required|email']);
$form->email('email');

// Bad - no client-side validation
$form->email('email');
```

### 2. Use Model Binding for Edit Forms

```php
// Good
$user = User::find($id);
$form->model($user, false, "/users/{$id}");

// Bad - manual value setting
$form->text('name', $user->name);
```

### 3. Enable File Upload When Needed

```php
// Good
$form->open('/profile/update', 'POST', 'horizontal', true);
$form->file('avatar');

// Bad - missing file upload support
$form->open('/profile/update', 'POST');
$form->file('avatar'); // Won't work!
```

### 4. Always Close Forms

```php
// Good
$form->open('/users/store');
$form->text('name');
$form->close();

// Bad - unclosed form
$form->open('/users/store');
$form->text('name');
// Missing close()!
```

---

## Common Patterns

### Create Form

```php
$form->open('/users/store', 'POST');
$form->text('name', null, [], true);
$form->email('email', null, [], true);
$form->password('password', [], true);
$form->close();
```

### Edit Form

```php
$user = User::find($id);
$form->model($user, false, "/users/{$id}");
$form->text('name');
$form->email('email');
$form->close();
```

### Form with File Upload

```php
$form->open('/profile/update', 'POST', 'horizontal', true);
$form->file('avatar', ['imagepreview'], true);
$form->text('bio');
$form->close();
```

### Form with Validation

```php
$form->setValidations([
    'username' => 'required|min:3|max:50',
    'email' => 'required|email|unique:users',
    'age' => 'required|numeric|min:18'
]);

$form->open('/users/store');
$form->text('username');
$form->email('email');
$form->number('age');
$form->close();
```

---

*For element-specific methods, see [ELEMENT_TRAITS.md](ELEMENT_TRAITS.md)*
*For security guidelines, see [SECURITY.md](SECURITY.md)*
