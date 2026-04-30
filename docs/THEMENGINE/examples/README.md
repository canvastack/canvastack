# Theme Engine Examples

**Version:** 2.0.0  
**Last Updated:** April 4, 2026

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

## Overview

This directory contains practical code examples for using the CanvaStack Theme Engine.

---

## Available Examples

### 1. Form Rendering Example
**File:** `form-rendering-example.php`

Demonstrates form element rendering with all three templates (Bootstrap 4, Bootstrap 5, TailwindCSS).

**Includes:**
- Tab headers and content
- Alert messages
- Checkboxes
- Select elements
- Form groups

---

### 2. Table Rendering Example
**File:** `table-rendering-example.php`

Demonstrates table component rendering with all three templates.

**Includes:**
- DataTables setup
- Filter modals
- Action buttons
- Table CSS classes

---

### 3. Modal Example
**File:** `modal-example.php`

Demonstrates modal implementation with all three templates.

**Includes:**
- Modal HTML structure
- JavaScript integration
- Framework-agnostic modal API

---

### 4. Custom Adapter Example
**File:** `custom-adapter-example.php`

Demonstrates creating a custom adapter for a new CSS framework (Material Design).

**Includes:**
- Adapter class implementation
- Registration in service provider
- Configuration setup
- View customization

---

### 5. Custom Template Example
**File:** `custom-template-example.php`

Demonstrates registering and configuring a custom template.

**Includes:**
- Template configuration
- Asset loading
- View path setup
- Testing custom template

---

## Usage

### Running Examples

```bash
# Copy example to your project
cp vendor/canvastack/canvastack/docs/THEMENGINE/examples/form-rendering-example.php app/Examples/

# Create route
Route::get('/examples/form-rendering', function() {
    require app_path('Examples/form-rendering-example.php');
});

# Visit in browser
http://localhost/examples/form-rendering
```

### Modifying Examples

All examples are fully functional and can be modified to suit your needs:

1. Copy example file to your project
2. Modify as needed
3. Test with different templates
4. Integrate into your application

---

## Example Structure

Each example follows this structure:

```php
<?php
/**
 * Example Title
 * 
 * Description of what this example demonstrates.
 * 
 * @version 2.0.0
 * @author CanvaStack Team
 */

// 1. Setup and configuration
// 2. Example code for each template
// 3. Output demonstration
// 4. Inline comments explaining key concepts
```

---

## Testing Examples

### Test with Different Templates

```php
// Test with Bootstrap 4
config(['canvastack.templates.template' => 'default']);
require 'form-rendering-example.php';

// Test with Bootstrap 5
config(['canvastack.templates.template' => 'canvasign']);
require 'form-rendering-example.php';

// Test with TailwindCSS
config(['canvastack.templates.template' => 'canvas']);
require 'form-rendering-example.php';
```

### Verify Output

1. Check HTML structure
2. Verify CSS classes
3. Test JavaScript functionality
4. Validate accessibility

---

## Contributing Examples

To contribute a new example:

1. Create example file following the structure above
2. Add inline comments explaining key concepts
3. Test with all three templates
4. Update this README with example description
5. Submit pull request

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may these examples help developers learn the Theme Engine.
