# Bootstrap 4 Guide (DefaultAdapter)

**Version:** 2.0.0  
**Last Updated:** April 4, 2026

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

## Overview

This guide covers the Bootstrap 4 implementation in the Theme Engine through the `DefaultAdapter`. This adapter maintains 100% backward compatibility with existing CanvaStack code.

**Template Name:** `default`  
**Adapter Class:** `Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter`  
**Framework:** Bootstrap 4.6.2  
**Status:** Production Ready

---

## Key Characteristics

### Bootstrap 4 Specific Features

- **Data Attributes:** `data-toggle`, `data-dismiss`, `data-target`
- **CSS Classes:** `pull-right`, `pull-left`, `hide`, `show`, `btn-xs`, `alert-block`
- **Select Plugin:** Chosen.js for enhanced select elements
- **Modal API:** Bootstrap 4 modal JavaScript API
- **Tooltip API:** Bootstrap 4 tooltip JavaScript API

### Backward Compatibility

The `DefaultAdapter` produces **byte-for-byte identical output** to pre-Theme Engine helpers:

```php
// Before Theme Engine
function canvastack_form_create_header_tab($data, $pointer, $active, $class) {
    // ... hardcoded Bootstrap 4 HTML
    return '<li class="nav-item"><a data-toggle="tab"...';
}

// After Theme Engine (DefaultAdapter)
// Output is IDENTICAL for same inputs
```

---

## Configuration

### Template Configuration

```php
// config/canvastack.templates.php
return [
    'template' => 'default',
    
    'default' => [
        'position' => [
            'top' => [
                'css' => [
                    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css',
                    'https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css',
                ],
                'js' => [
                    'https://code.jquery.com/jquery-3.6.0.min.js',
                    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js',
                    'https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js',
                ],
            ],
        ],
        
        'datatable' => [
            'js' => [
                'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
                'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js',
            ],
            'css' => [
                'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css',
            ],
        ],
        
        'select' => [
            'plugin' => 'chosen',
            'js' => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js'],
            'css' => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css'],
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
    <a class="nav-link active custom-class" data-toggle="tab" href="#users-tab">Users</a>
</li>
```

**Bootstrap 4 Features:**
- `data-toggle="tab"` - Bootstrap 4 tab toggle attribute
- `nav-item` and `nav-link` - Bootstrap 4 nav classes
- `active` class for active tab

---

### Alert Messages

```php
echo canvastack_form_alert_message('Operation successful!', 'success', 'Success', 'msg', false);
```

**Output:**
```html
<div class="alert alert-block alert-success alert-dismissible fade show" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    <h4 class="alert-heading">Success</h4>
    <p>Operation successful!</p>
</div>
```

**Bootstrap 4 Features:**
- `alert-block` - Bootstrap 4 block alert class
- `data-dismiss="alert"` - Bootstrap 4 dismiss attribute
- `close` class for close button
- `&times;` HTML entity for × symbol

---

### Checkboxes

```php
echo canvastack_form_checkList('terms', '1', 'I agree to terms', false, 'primary', 'terms-cb', null);
```

**Output:**
```html
<div class="ckbox ckbox-primary">
    <input type="checkbox" name="terms" value="1" id="terms-cb">
    <label for="terms-cb">I agree to terms</label>
</div>
```

**Bootstrap 4 Features:**
- `ckbox` - Custom checkbox wrapper class
- `ckbox-primary` - Color variant class

---

### Select Elements

```php
$countries = ['US' => 'United States', 'UK' => 'United Kingdom', 'CA' => 'Canada'];
echo canvastack_form_selectbox('country', $countries, 'US', ['class' => 'form-control'], true, false);
```

**Output:**
```html
<select name="country" class="chosen-select-deselect chosen-selectbox form-control">
    <option value="US" selected>United States</option>
    <option value="UK">United Kingdom</option>
    <option value="CA">Canada</option>
</select>
```

**Bootstrap 4 Features:**
- `chosen-select-deselect chosen-selectbox` - Chosen.js plugin classes
- Chosen.js provides search and multi-select functionality

---

### Modals

```php
$elements = ['<p>Modal content here</p>'];
echo canvastack_modal_content_html('myModal', 'Modal Title', $elements);
```

**Output:**
```html
<div class="modal fade" id="myModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modal Title</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Modal content here</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
```

**Bootstrap 4 Features:**
- `data-dismiss="modal"` - Bootstrap 4 modal dismiss attribute
- `close` class for close button
- `modal-dialog` and `modal-content` structure

---

### Action Buttons

```php
$row = (object)['id' => 123, 'name' => 'John Doe'];
echo canvastack_table_action_button($row, 'id', '/users', ['view', 'edit', 'delete'], null);
```

**Output:**
```html
<div class="btn-group btn-group-sm" role="group">
    <a href="/users/123" class="btn btn-xs btn-info" title="View">
        <i class="fa fa-eye"></i>
    </a>
    <a href="/users/123/edit" class="btn btn-xs btn-warning" title="Edit">
        <i class="fa fa-edit"></i>
    </a>
    <a href="/users/123/delete" class="btn btn-xs btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
        <i class="fa fa-trash"></i>
    </a>
</div>
```

**Bootstrap 4 Features:**
- `btn-xs` - Extra small button size (Bootstrap 4 specific)
- `btn-group` - Button group wrapper
- Font Awesome icons

---

## CSS Classes Reference

### Layout Classes

| Class | Purpose | Example |
|-------|---------|---------|
| `container` | Fixed-width container | `<div class="container">` |
| `container-fluid` | Full-width container | `<div class="container-fluid">` |
| `row` | Flex row | `<div class="row">` |
| `col-*` | Grid columns | `<div class="col-md-6">` |

### Utility Classes

| Class | Purpose | Replacement in BS5 |
|-------|---------|-------------------|
| `pull-right` | Float right | `float-end` |
| `pull-left` | Float left | `float-start` |
| `hide` | Hide element | `d-none` |
| `show` | Show element | `d-block` |
| `text-hide` | Hide text | Removed |

### Button Classes

| Class | Purpose | Replacement in BS5 |
|-------|---------|-------------------|
| `btn-xs` | Extra small button | `btn-sm` |
| `btn-sm` | Small button | `btn-sm` |
| `btn-lg` | Large button | `btn-lg` |

### Alert Classes

| Class | Purpose | Replacement in BS5 |
|-------|---------|-------------------|
| `alert-block` | Block alert | Removed |
| `alert-dismissible` | Dismissible alert | `alert-dismissible` |

---

## JavaScript API

### Modal API

```javascript
// Show modal
$('#myModal').modal('show');

// Hide modal
$('#myModal').modal('hide');

// Toggle modal
$('#myModal').modal('toggle');

// Modal events
$('#myModal').on('show.bs.modal', function (e) {
    console.log('Modal is about to be shown');
});

$('#myModal').on('hidden.bs.modal', function (e) {
    console.log('Modal has been hidden');
});
```

### Tooltip API

```javascript
// Initialize tooltips
$('[data-toggle="tooltip"]').tooltip();

// Show tooltip
$('#myElement').tooltip('show');

// Hide tooltip
$('#myElement').tooltip('hide');

// Destroy tooltip
$('#myElement').tooltip('dispose');
```

### Dropdown API

```javascript
// Show dropdown
$('#myDropdown').dropdown('show');

// Hide dropdown
$('#myDropdown').dropdown('hide');

// Toggle dropdown
$('#myDropdown').dropdown('toggle');
```

---

## Chosen.js Integration

### Basic Usage

```html
<select class="chosen-select-deselect chosen-selectbox">
    <option value="">Select an option</option>
    <option value="1">Option 1</option>
    <option value="2">Option 2</option>
</select>

<script>
    $('.chosen-select-deselect').chosen({
        allow_single_deselect: true,
        width: '100%'
    });
</script>
```

### Multi-Select

```html
<select class="chosen-select" multiple>
    <option value="1">Option 1</option>
    <option value="2">Option 2</option>
    <option value="3">Option 3</option>
</select>

<script>
    $('.chosen-select').chosen({
        width: '100%'
    });
</script>
```

### Search Functionality

Chosen.js automatically provides search functionality for select elements with more than 10 options.

---

## DataTables Integration

### Basic Configuration

```javascript
$('#myTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '/users/datatables',
        type: 'POST',
        data: function(d) {
            d._token = $('meta[name="csrf-token"]').attr('content');
        }
    },
    columns: [
        { data: 'id', name: 'id' },
        { data: 'name', name: 'name' },
        { data: 'email', name: 'email' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    // Bootstrap 4 specific styling
    dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
         "<'row'<'col-sm-12'tr>>" +
         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
});
```

---

## Best Practices

### 1. Use Helper Functions

```php
// Good - uses helper function
echo canvastack_form_create_header_tab('Users', 'users-tab', true, false);

// Avoid - direct HTML
echo '<li class="nav-item"><a data-toggle="tab"...';
```

### 2. Consistent Data Attributes

```html
<!-- Good - Bootstrap 4 attributes -->
<button data-toggle="modal" data-target="#myModal">Open</button>

<!-- Bad - mixed attributes -->
<button data-bs-toggle="modal" data-target="#myModal">Open</button>
```

### 3. Use Chosen.js for Select Elements

```html
<!-- Good - uses Chosen.js -->
<select class="chosen-select-deselect chosen-selectbox">
    <option value="1">Option 1</option>
</select>

<!-- Avoid - native select (no search functionality) -->
<select class="form-control">
    <option value="1">Option 1</option>
</select>
```

---

## Migration from Bootstrap 3

If migrating from Bootstrap 3 to Bootstrap 4:

| Bootstrap 3 | Bootstrap 4 |
|-------------|-------------|
| `panel` | `card` |
| `panel-heading` | `card-header` |
| `panel-body` | `card-body` |
| `panel-footer` | `card-footer` |
| `well` | `card` |
| `thumbnail` | `card` |
| `label` | `badge` |
| `label-default` | `badge-secondary` |

---

## Troubleshooting

### Chosen.js Not Working

**Problem:** Select elements don't have search functionality.

**Solution:**
```javascript
// Ensure Chosen.js is loaded
console.log(typeof $.fn.chosen); // Should return 'function'

// Initialize Chosen.js
$('.chosen-select-deselect').chosen({
    allow_single_deselect: true,
    width: '100%'
});
```

### Modals Not Opening

**Problem:** Modals don't open when clicking trigger button.

**Solution:**
```html
<!-- Verify data attributes -->
<button data-toggle="modal" data-target="#myModal">Open</button>

<!-- Verify modal ID matches -->
<div class="modal" id="myModal">...</div>

<!-- Verify Bootstrap JS is loaded -->
<script>
    console.log(typeof $.fn.modal); // Should return 'function'
</script>
```

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may this Bootstrap 4 guide serve developers well.
