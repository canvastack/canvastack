# JavaScript Integration

**Version:** 2.0.0  
**Last Updated:** April 4, 2026

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

## Overview

The Theme Engine includes JavaScript adapters that provide framework-agnostic APIs for modals, tooltips, and CSS classes. These adapters automatically detect the active template and route to the correct framework API.

---

## Template Detection

### Setting Template in JavaScript

The active template is exposed to JavaScript via a global variable:

```blade
{{-- In your layout file (e.g., resources/views/layouts/app.blade.php) --}}
<script>
    window.canvastackTemplate = '{{ canvastack_current_template() }}';
</script>
```

**Values:**
- `'default'` - Bootstrap 4
- `'canvasign'` - Bootstrap 5
- `'canvas'` - TailwindCSS

---

## Modal Adapter

**File:** `public/js/canvastack-modal-adapter.js`

**Purpose:** Provides framework-agnostic modal API that works with Bootstrap 4, Bootstrap 5, and TailwindCSS.

### Implementation

```javascript
const CanvaStackModal = {
    /**
     * Show modal
     * @param {string} modalId - Modal element ID
     */
    show: function(modalId) {
        const template = window.canvastackTemplate || 'default';
        
        if (template === 'default') {
            // Bootstrap 4
            $('#' + modalId).modal('show');
        } else if (template === 'canvasign') {
            // Bootstrap 5
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        } else if (template === 'canvas') {
            // TailwindCSS (custom implementation)
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }
    },
    
    /**
     * Hide modal
     * @param {string} modalId - Modal element ID
     */
    hide: function(modalId) {
        const template = window.canvastackTemplate || 'default';
        
        if (template === 'default') {
            // Bootstrap 4
            $('#' + modalId).modal('hide');
        } else if (template === 'canvasign') {
            // Bootstrap 5
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
        } else if (template === 'canvas') {
            // TailwindCSS (custom implementation)
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }
    },
    
    /**
     * Toggle modal
     * @param {string} modalId - Modal element ID
     */
    toggle: function(modalId) {
        const template = window.canvastackTemplate || 'default';
        
        if (template === 'default') {
            // Bootstrap 4
            $('#' + modalId).modal('toggle');
        } else if (template === 'canvasign') {
            // Bootstrap 5
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                modal.toggle();
            }
        } else if (template === 'canvas') {
            // TailwindCSS (custom implementation)
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.toggle('hidden');
                modal.classList.toggle('flex');
            }
        }
    }
};
```

### Usage

```javascript
// Show modal
CanvaStackModal.show('myModal');

// Hide modal
CanvaStackModal.hide('myModal');

// Toggle modal
CanvaStackModal.toggle('myModal');
```

### HTML Structure

```html
<!-- Bootstrap 4/5 -->
<div class="modal fade" id="myModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modal Title</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                Modal content
            </div>
        </div>
    </div>
</div>

<!-- TailwindCSS -->
<div id="myModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="flex items-center justify-between p-4 border-b">
            <h5 class="text-lg font-bold">Modal Title</h5>
            <button onclick="CanvaStackModal.hide('myModal')">&times;</button>
        </div>
        <div class="p-4">
            Modal content
        </div>
    </div>
</div>
```

---

## Tooltip Adapter

**File:** `public/js/canvastack-tooltip-adapter.js`

**Purpose:** Provides framework-agnostic tooltip initialization.

### Implementation

```javascript
const CanvaStackTooltip = {
    /**
     * Initialize tooltips
     */
    init: function() {
        const template = window.canvastackTemplate || 'default';
        
        if (template === 'default') {
            // Bootstrap 4
            $('[data-toggle="tooltip"]').tooltip();
        } else if (template === 'canvasign') {
            // Bootstrap 5
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        } else if (template === 'canvas') {
            // TailwindCSS (Tippy.js)
            if (typeof tippy !== 'undefined') {
                tippy('[data-tippy-content]', {
                    theme: 'light',
                    placement: 'top',
                });
            }
        }
    }
};

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    CanvaStackTooltip.init();
});
```

### Usage

```html
<!-- Bootstrap 4 -->
<button data-toggle="tooltip" title="Tooltip text">Hover me</button>

<!-- Bootstrap 5 -->
<button data-bs-toggle="tooltip" title="Tooltip text">Hover me</button>

<!-- TailwindCSS (Tippy.js) -->
<button data-tippy-content="Tooltip text">Hover me</button>
```

```javascript
// Manual initialization
CanvaStackTooltip.init();
```

---

## CSS Class Adapter

**File:** `public/js/canvastack-class-adapter.js`

**Purpose:** Translates generic class names to framework-specific classes.

### Implementation

```javascript
const CanvaStackClass = {
    /**
     * Get framework-specific class
     * @param {string} genericClass - Generic class name
     * @returns {string} Framework-specific class
     */
    get: function(genericClass) {
        const template = window.canvastackTemplate || 'default';
        
        const classMap = {
            'hide': {
                'default': 'hide',
                'canvasign': 'd-none',
                'canvas': 'hidden'
            },
            'show': {
                'default': 'show',
                'canvasign': 'd-block',
                'canvas': 'block'
            },
            'float-right': {
                'default': 'pull-right',
                'canvasign': 'float-end',
                'canvas': 'ml-auto'
            },
            'float-left': {
                'default': 'pull-left',
                'canvasign': 'float-start',
                'canvas': 'mr-auto'
            },
            'btn-xs': {
                'default': 'btn-xs',
                'canvasign': 'btn-sm',
                'canvas': 'px-2 py-1 text-xs'
            }
        };
        
        return classMap[genericClass] && classMap[genericClass][template] 
            ? classMap[genericClass][template] 
            : genericClass;
    },
    
    /**
     * Apply framework-specific class to element
     * @param {HTMLElement} element - DOM element
     * @param {string} genericClass - Generic class name
     */
    apply: function(element, genericClass) {
        const frameworkClass = this.get(genericClass);
        element.classList.add(frameworkClass);
    }
};
```

### Usage

```javascript
// Get framework-specific class
const hideClass = CanvaStackClass.get('hide');
// Returns: 'hide' (BS4), 'd-none' (BS5), 'hidden' (Tailwind)

// Apply class to element
const element = document.getElementById('myElement');
CanvaStackClass.apply(element, 'hide');

// Use in dynamic HTML generation
const html = `<div class="${CanvaStackClass.get('float-right')}">Content</div>`;
```

---

## Complete Integration Example

### Layout File

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title')</title>
    
    {{-- Template-specific CSS/JS loaded by Theme Engine --}}
    
    {{-- Set template for JavaScript --}}
    <script>
        window.canvastackTemplate = '{{ canvastack_current_template() }}';
    </script>
</head>
<body>
    @yield('content')
    
    {{-- JavaScript adapters --}}
    <script src="{{ asset('js/canvastack-modal-adapter.js') }}"></script>
    <script src="{{ asset('js/canvastack-tooltip-adapter.js') }}"></script>
    <script src="{{ asset('js/canvastack-class-adapter.js') }}"></script>
    
    {{-- Application scripts --}}
    <script>
        // Framework-agnostic code
        document.getElementById('openModal').addEventListener('click', function() {
            CanvaStackModal.show('myModal');
        });
        
        // Initialize tooltips
        CanvaStackTooltip.init();
    </script>
</body>
</html>
```

### View File

```blade
{{-- resources/views/users/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Users</h1>
    
    {{-- Button to open modal --}}
    <button id="openModal" class="btn btn-primary">Open Modal</button>
    
    {{-- Tooltip example --}}
    <button data-toggle="tooltip" data-bs-toggle="tooltip" data-tippy-content="Delete user" title="Delete user">
        Delete
    </button>
    
    {{-- Modal --}}
    <div class="modal fade" id="myModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="close" onclick="CanvaStackModal.hide('myModal')">&times;</button>
                </div>
                <div class="modal-body">
                    User information here
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

---

## Browser Compatibility

### Supported Browsers

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Polyfills

For older browsers, include polyfills:

```html
<!-- Polyfill for older browsers -->
<script src="https://cdn.jsdelivr.net/npm/core-js-bundle@3.25.0/minified.js"></script>
```

---

## Best Practices

### 1. Always Set Template Variable

```blade
<script>
    window.canvastackTemplate = '{{ canvastack_current_template() }}';
</script>
```

### 2. Use Adapters for Framework-Agnostic Code

```javascript
// Good - works with all frameworks
CanvaStackModal.show('myModal');

// Avoid - framework-specific
$('#myModal').modal('show');
```

### 3. Initialize Tooltips After Dynamic Content

```javascript
// After adding dynamic content
CanvaStackTooltip.init();
```

### 4. Handle Missing Dependencies

```javascript
if (typeof CanvaStackModal !== 'undefined') {
    CanvaStackModal.show('myModal');
} else {
    console.error('CanvaStackModal adapter not loaded');
}
```

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may this JavaScript integration guide serve developers well.
