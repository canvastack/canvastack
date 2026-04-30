# Bootstrap 5 Guide (Bootstrap5Adapter)

**Version:** 2.0.0  
**Last Updated:** April 4, 2026

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

## Overview

This guide covers the Bootstrap 5 implementation in the Theme Engine through the `Bootstrap5Adapter`.

**Template Name:** `canvasign`  
**Adapter Class:** `Canvastack\Canvastack\Library\Theme\Adapters\Bootstrap5Adapter`  
**Framework:** Bootstrap 5.3.0  
**Status:** Production Ready

---

## Key Differences from Bootstrap 4

### Data Attributes

| Bootstrap 4 | Bootstrap 5 |
|-------------|-------------|
| `data-toggle` | `data-bs-toggle` |
| `data-dismiss` | `data-bs-dismiss` |
| `data-target` | `data-bs-target` |
| `data-parent` | `data-bs-parent` |

### CSS Classes

| Bootstrap 4 | Bootstrap 5 |
|-------------|-------------|
| `pull-right` | `float-end` |
| `pull-left` | `float-start` |
| `hide` | `d-none` |
| `btn-xs` | `btn-sm` |
| `alert-block` | (removed) |
| `form-group` | `mb-3` |
| `ml-*` | `ms-*` |
| `mr-*` | `me-*` |
| `pl-*` | `ps-*` |
| `pr-*` | `pe-*` |

### Select Elements

| Bootstrap 4 | Bootstrap 5 |
|-------------|-------------|
| Chosen.js plugin | Native `form-select` or Choices.js |
| `chosen-select-deselect` | `form-select` |

---

## Configuration

```php
// config/canvastack.templates.php
return [
    'template' => 'canvasign',
    
    'canvasign' => [
        'position' => [
            'top' => [
                'css' => [
                    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
                    'https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css',
                ],
                'js' => [
                    'https://code.jquery.com/jquery-3.6.0.min.js',
                    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
                    'https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js',
                ],
            ],
            'bottom' => [
                'last' => [
                    'js' => [
                        'js/canvastack-modal-adapter.js',
                        'js/canvastack-tooltip-adapter.js',
                    ],
                ],
            ],
        ],
        
        'select' => [
            'plugin' => 'choices',
            'js' => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js'],
            'css' => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css'],
        ],
    ],
];
```

---

## Component Examples

### Tab Headers

```php
echo canvastack_form_create_header_tab('Users', 'users-tab', true, 'custom-class');
```

**Output:**
```html
<li class="nav-item">
    <a class="nav-link active custom-class" data-bs-toggle="tab" href="#users-tab">Users</a>
</li>
```

**Key Change:** `data-bs-toggle` instead of `data-toggle`

---

### Alert Messages

```php
echo canvastack_form_alert_message('Operation successful!', 'success', 'Success', 'msg', false);
```

**Output:**
```html
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    <h4 class="alert-heading">Success</h4>
    <p>Operation successful!</p>
</div>
```

**Key Changes:**
- No `alert-block` class
- `btn-close` instead of `close` class
- `data-bs-dismiss` instead of `data-dismiss`
- No `&times;` symbol (CSS handles it)

---

### Checkboxes

```php
echo canvastack_form_checkList('terms', '1', 'I agree to terms', false, 'primary', 'terms-cb', null);
```

**Output:**
```html
<div class="form-check">
    <input class="form-check-input" type="checkbox" name="terms" value="1" id="terms-cb">
    <label class="form-check-label" for="terms-cb">I agree to terms</label>
</div>
```

**Key Changes:**
- `form-check` wrapper instead of `ckbox`
- `form-check-input` and `form-check-label` classes

---

### Select Elements

```php
$countries = ['US' => 'United States', 'UK' => 'United Kingdom'];
echo canvastack_form_selectbox('country', $countries, 'US', ['class' => 'form-control'], true, false);
```

**Output:**
```html
<select name="country" class="form-select form-control">
    <option value="US" selected>United States</option>
    <option value="UK">United Kingdom</option>
</select>
```

**Key Change:** `form-select` class instead of Chosen.js classes

---

## JavaScript API Changes

### Modal API

```javascript
// Bootstrap 4
$('#myModal').modal('show');

// Bootstrap 5
var myModal = new bootstrap.Modal(document.getElementById('myModal'));
myModal.show();

// Or use CanvaStack adapter (framework-agnostic)
CanvaStackModal.show('myModal');
```

### Tooltip API

```javascript
// Bootstrap 4
$('[data-toggle="tooltip"]').tooltip();

// Bootstrap 5
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Or use CanvaStack adapter (framework-agnostic)
CanvaStackTooltip.init();
```

---

## Choices.js Integration

### Basic Usage

```html
<select class="form-select" id="mySelect">
    <option value="">Select an option</option>
    <option value="1">Option 1</option>
    <option value="2">Option 2</option>
</select>

<script>
    const choices = new Choices('#mySelect', {
        removeItemButton: true,
        searchEnabled: true,
    });
</script>
```

### Multi-Select

```html
<select class="form-select" multiple id="myMultiSelect">
    <option value="1">Option 1</option>
    <option value="2">Option 2</option>
    <option value="3">Option 3</option>
</select>

<script>
    const choices = new Choices('#myMultiSelect', {
        removeItemButton: true,
    });
</script>
```

---

## Migration from Bootstrap 4

### Update Data Attributes

```html
<!-- Before (Bootstrap 4) -->
<button data-toggle="modal" data-target="#myModal">Open</button>
<button data-dismiss="modal">Close</button>

<!-- After (Bootstrap 5) -->
<button data-bs-toggle="modal" data-bs-target="#myModal">Open</button>
<button data-bs-dismiss="modal">Close</button>
```

### Update CSS Classes

```html
<!-- Before (Bootstrap 4) -->
<div class="pull-right hide">Content</div>
<button class="btn btn-xs">Button</button>
<div class="alert alert-block alert-success">Alert</div>

<!-- After (Bootstrap 5) -->
<div class="float-end d-none">Content</div>
<button class="btn btn-sm">Button</button>
<div class="alert alert-success">Alert</div>
```

### Update JavaScript

```javascript
// Before (Bootstrap 4)
$('#myModal').modal('show');
$('[data-toggle="tooltip"]').tooltip();

// After (Bootstrap 5)
var myModal = new bootstrap.Modal(document.getElementById('myModal'));
myModal.show();

var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl);
});

// Or use CanvaStack adapters
CanvaStackModal.show('myModal');
CanvaStackTooltip.init();
```

---

## Best Practices

### 1. Use CanvaStack Adapters

```javascript
// Good - framework-agnostic
CanvaStackModal.show('myModal');

// Avoid - Bootstrap 5 specific
var myModal = new bootstrap.Modal(document.getElementById('myModal'));
myModal.show();
```

### 2. Use form-select for Native Selects

```html
<!-- Good - native Bootstrap 5 -->
<select class="form-select">
    <option>Option 1</option>
</select>

<!-- Use Choices.js for enhanced functionality -->
<select class="form-select" id="enhanced">
    <option>Option 1</option>
</select>
<script>new Choices('#enhanced');</script>
```

### 3. Update All Data Attributes

```html
<!-- Ensure all data attributes use data-bs- prefix -->
<div data-bs-toggle="collapse" data-bs-target="#collapseExample">
    Toggle
</div>
```

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may this Bootstrap 5 guide serve developers well.
