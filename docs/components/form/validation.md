# Form Component Validation

Complete guide to validation in the CanvaStack Enhanced Form Component.

## Table of Contents

1. [Overview](#overview)
2. [Setting Validation Rules](#setting-validation-rules)
3. [Field-Level Validation](#field-level-validation)
4. [Validation Caching](#validation-caching)
5. [Custom Validation](#custom-validation)
6. [Error Display](#error-display)

---

## Overview

The Form Component integrates seamlessly with Laravel's validation system while providing enhanced features like validation caching and automatic error display.

### Features

- ✅ Laravel validation rules support
- ✅ Validation rule caching (~95% hit ratio)
- ✅ Field-level validation methods
- ✅ Automatic error display
- ✅ Custom validation rules
- ✅ Real-time validation (optional)

---

## Setting Validation Rules

### Form-Level Validation

Set validation rules for the entire form:

```php
$form->setValidations([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8|confirmed',
    'age' => 'required|integer|min:18|max:100'
]);
```

### Array Syntax

```php
$form->setValidations([
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email', 'unique:users'],
    'password' => ['required', 'min:8', 'confirmed']
]);
```

### Retrieving Rules

```php
$rules = $form->getValidations();
// Returns cached rules if available
```

---

## Field-Level Validation

### Using Fluent Interface

Add validation rules directly to fields:

```php
$form->text('name', 'Name')
    ->required()
    ->rule('string')
    ->rule('max:255');

$form->email('email', 'Email')
    ->required()
    ->rule('email')
    ->rule('unique:users');

$form->password('password', 'Password')
    ->required()
    ->minLength(8)
    ->rule('confirmed');
```

### Multiple Rules at Once

```php
$form->text('username', 'Username')
    ->rules(['required', 'string', 'min:3', 'max:50', 'unique:users']);
```

### Built-in Validation Methods

Many fields have built-in validation methods:

```php
// TextField / TextareaField
$form->text('title', 'Title')
    ->required()
    ->maxLength(200)
    ->minLength(10);

// NumberField
$form->number('age', 'Age')
    ->required()
    ->min(18)
    ->max(100);

// EmailField
$form->email('email', 'Email')
    ->required(); // Automatically adds email validation

// FileField
$form->file('avatar', 'Avatar')
    ->required()
    ->accept('image/*')
    ->maxSize(2048); // 2MB
```

---

## Validation Caching

### How It Works

Validation rules are automatically cached for performance:

1. Rules are compiled on first request
2. Cached with unique key based on form identity
3. Retrieved from cache on subsequent requests
4. Cache invalidated when rules change

### Performance Benefits

- ~95% cache hit ratio
- < 10ms rule compilation (cached)
- Reduced memory usage
- Faster form rendering

### Cache Configuration

```php
// Set form identity for caching
$form->setFormIdentity('user-registration-form');

// Set validation rules (will be cached)
$form->setValidations($rules);

// Cache TTL (default: 3600 seconds)
$form->cache(7200); // 2 hours
```

---

## Custom Validation

### Custom Rules

Use Laravel's custom validation rules:

```php
$form->text('username', 'Username')
    ->rule('required')
    ->rule('unique:users')
    ->rule(new CustomUsernameRule);
```

### Closure Rules

```php
$form->text('code', 'Promo Code')
    ->rule('required')
    ->rule(function ($attribute, $value, $fail) {
        if (!PromoCode::where('code', $value)->exists()) {
            $fail('The promo code is invalid.');
        }
    });
```

### Conditional Validation

```php
$form->text('company', 'Company Name')
    ->rule('required_if:account_type,business')
    ->rule('string')
    ->rule('max:255');
```

---

## Error Display

### Automatic Error Display

Errors are automatically displayed below fields:

```php
// In controller
public function store(Request $request)
{
    $validated = $request->validate($form->getValidations());
    // If validation fails, errors are automatically shown
}
```

### Error Styling

Errors are styled with Tailwind CSS:

```html
<div class="text-red-600 dark:text-red-400 text-sm mt-1">
    The email field is required.
</div>
```

### Custom Error Messages

```php
$request->validate($form->getValidations(), [
    'email.required' => 'Please provide your email address.',
    'email.email' => 'Please provide a valid email address.',
    'email.unique' => 'This email is already registered.'
]);
```

### Tab Error Highlighting

When using tabs, tabs with validation errors are automatically highlighted:

```php
$form->openTab('Personal Info', 'active');
$form->text('name', 'Name')->required();
$form->email('email', 'Email')->required();
$form->closeTab();

// If validation fails, tab shows error indicator
```

---

## Common Validation Patterns

### User Registration

```php
$form->setValidations([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8|confirmed',
    'terms' => 'required|accepted'
]);
```

### Profile Update

```php
$form->setValidations([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email,' . $user->id,
    'phone' => 'nullable|string|max:20',
    'avatar' => 'nullable|image|max:2048'
]);
```

### Product Form

```php
$form->setValidations([
    'name' => 'required|string|max:200',
    'sku' => 'required|string|unique:products',
    'price' => 'required|numeric|min:0',
    'stock' => 'required|integer|min:0',
    'description' => 'required|string|max:5000',
    'category_id' => 'required|exists:categories,id',
    'images.*' => 'image|max:5120'
]);
```

---

## Validation Rules Reference

### Common Rules

| Rule | Description | Example |
|------|-------------|---------|
| required | Field must have value | `required` |
| nullable | Field can be null | `nullable` |
| string | Must be string | `string` |
| numeric | Must be numeric | `numeric` |
| integer | Must be integer | `integer` |
| email | Must be valid email | `email` |
| url | Must be valid URL | `url` |
| date | Must be valid date | `date` |
| boolean | Must be boolean | `boolean` |
| array | Must be array | `array` |

### Size Rules

| Rule | Description | Example |
|------|-------------|---------|
| min:value | Minimum value/length | `min:8` |
| max:value | Maximum value/length | `max:255` |
| between:min,max | Between range | `between:18,100` |
| size:value | Exact size | `size:10` |

### String Rules

| Rule | Description | Example |
|------|-------------|---------|
| alpha | Only letters | `alpha` |
| alpha_dash | Letters, numbers, dashes | `alpha_dash` |
| alpha_num | Letters and numbers | `alpha_num` |
| regex:pattern | Match regex pattern | `regex:/^[A-Z]/` |

### File Rules

| Rule | Description | Example |
|------|-------------|---------|
| file | Must be file | `file` |
| image | Must be image | `image` |
| mimes:types | Allowed MIME types | `mimes:jpg,png,pdf` |
| max:size | Max size in KB | `max:2048` |

### Database Rules

| Rule | Description | Example |
|------|-------------|---------|
| unique:table,column | Must be unique | `unique:users,email` |
| exists:table,column | Must exist | `exists:categories,id` |

### Conditional Rules

| Rule | Description | Example |
|------|-------------|---------|
| required_if:field,value | Required if condition | `required_if:type,business` |
| required_with:field | Required with other field | `required_with:password` |
| required_without:field | Required without other field | `required_without:email` |

---

## Best Practices

### 1. Use Field-Level Validation

```php
// Good: Clear and maintainable
$form->text('email', 'Email')
    ->required()
    ->rule('email')
    ->rule('unique:users');

// Also good: Form-level for complex rules
$form->setValidations([
    'email' => 'required|email|unique:users'
]);
```

### 2. Provide Clear Error Messages

```php
$request->validate($form->getValidations(), [
    'email.required' => 'Please enter your email address.',
    'email.unique' => 'This email is already registered.'
]);
```

### 3. Use Help Text

```php
$form->password('password', 'Password')
    ->required()
    ->minLength(8)
    ->help('Minimum 8 characters, include numbers and symbols');
```

### 4. Validate File Uploads

```php
$form->file('avatar', 'Avatar')
    ->accept('image/*')
    ->maxSize(2048)
    ->help('Maximum 2MB. Accepted: JPG, PNG, GIF');

$form->setValidations([
    'avatar' => 'required|image|max:2048|dimensions:min_width=100,min_height=100'
]);
```

### 5. Use Unique Validation Correctly

```php
// Create form
'email' => 'required|email|unique:users'

// Update form (exclude current record)
'email' => 'required|email|unique:users,email,' . $user->id
```

---

## Performance Tips

1. **Use Validation Caching**: Set form identity for automatic caching
2. **Minimize Rules**: Only use necessary validation rules
3. **Database Rules**: Use `exists` and `unique` sparingly
4. **File Validation**: Validate size before upload

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-25  
**Status**: Production Ready
