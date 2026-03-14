# Tom Select Setup in Your Application

## Overview

This guide explains how to use Tom Select when you install CanvaStack package in your Laravel application.

---

## Automatic Setup (Recommended)

When you install CanvaStack package, Tom Select is **automatically included** and configured. No additional setup required!

### What's Included

✅ Tom Select library (npm package)  
✅ Custom DaisyUI-compatible theme  
✅ Dark mode support  
✅ Alpine.js integration  
✅ Automatic initialization in filter modals

---

## Installation Steps

### Step 1: Install CanvaStack Package

```bash
composer require canvastack/canvastack
```

### Step 2: Publish Assets

```bash
php artisan vendor:publish --tag=canvastack-assets
```

This will publish:
- CSS files (including Tom Select theme)
- JavaScript files (including Tom Select integration)
- Configuration files

### Step 3: Include Assets in Your Layout

```blade
<!DOCTYPE html>
<html>
<head>
    {{-- CanvaStack CSS (includes Tom Select theme) --}}
    <link rel="stylesheet" href="{{ asset('vendor/canvastack/css/canvastack.css') }}">
    
    {{-- Your app CSS --}}
    @vite(['resources/css/app.css'])
</head>
<body>
    @yield('content')
    
    {{-- CanvaStack JS (includes Tom Select) --}}
    <script src="{{ asset('vendor/canvastack/js/canvastack.js') }}"></script>
    
    {{-- Your app JS --}}
    @vite(['resources/js/app.js'])
</body>
</html>
```

### Step 4: Build Your Assets

```bash
npm install
npm run build
```

---

## Using Tom Select in Filter Modal

Tom Select is **automatically initialized** in CanvaStack filter modals. Just use the standard select element:

```blade
{{-- In your controller --}}
$table->addFilter('status', 'Status', 'selectbox', [
    ['value' => 'active', 'label' => 'Active'],
    ['value' => 'inactive', 'label' => 'Inactive'],
]);
```

That's it! Tom Select will automatically:
- Initialize when modal opens
- Destroy when modal closes
- Sync with Alpine.js x-model
- Apply DaisyUI theme
- Support dark mode

---

## Using Tom Select Outside Filter Modal

If you want to use Tom Select in your own forms:

### Method 1: Via CDN (Quick Start)

```blade
{{-- In your view --}}
<select id="my-select" class="select select-bordered">
    <option value="">Select...</option>
    <option value="1">Option 1</option>
    <option value="2">Option 2</option>
</select>

<script>
    // Tom Select is already available globally via CanvaStack
    new TomSelect('#my-select', {
        plugins: {
            'dropdown_input': {},
            'clear_button': { title: 'Clear' }
        }
    });
</script>
```

### Method 2: Via NPM (Recommended)

```bash
# Tom Select is already installed as CanvaStack dependency
# No need to install separately
```

```javascript
// In your app.js
import TomSelect from 'tom-select';

document.addEventListener('DOMContentLoaded', function() {
    new TomSelect('#my-select', {
        plugins: {
            'dropdown_input': {},
            'clear_button': { title: 'Clear' }
        }
    });
});
```

---

## Configuration

### Using CanvaStack Config

You can configure Tom Select defaults in `config/canvastack-ui.php`:

```php
return [
    'tom_select' => [
        'enabled' => true,
        'auto_init' => true, // Auto-initialize in filter modals
        'default_plugins' => [
            'dropdown_input',
            'clear_button',
        ],
        'default_options' => [
            'maxOptions' => 1000,
            'sortField' => [
                'field' => 'text',
                'direction' => 'asc',
            ],
        ],
    ],
];
```

### Per-Instance Configuration

Override defaults per instance:

```javascript
new TomSelect('#my-select', {
    maxOptions: 500,
    create: true, // Allow creating new options
    plugins: {
        'dropdown_input': {},
        'clear_button': {},
        'remove_button': {},
    }
});
```

---

## Customizing Theme

### Method 1: CSS Variables (Recommended)

Customize via DaisyUI theme variables:

```css
/* In your app.css */
:root {
    --p: 239 84% 67%;        /* Primary color */
    --pc: 0 0% 100%;         /* Primary content */
    --b1: 0 0% 100%;         /* Background */
    --bc: 0 0% 20%;          /* Text color */
}

/* Tom Select will automatically use these colors */
```

### Method 2: Override CSS

Override specific Tom Select styles:

```css
/* In your app.css */
.ts-control {
    height: 2.5rem !important;
    border-radius: 0.75rem !important;
}

.ts-dropdown .option:hover {
    background-color: #your-color !important;
}
```

### Method 3: Custom Theme File

Create your own theme file:

```css
/* resources/css/tom-select-custom.css */
@import 'tom-select/dist/css/tom-select.bootstrap5.css';

/* Your custom styles */
.ts-control {
    /* ... */
}
```

Then import in your app.css:

```css
@import 'tom-select-custom.css';
```

---

## Dark Mode Setup

Dark mode is **automatically supported** via DaisyUI. Just toggle the dark class:

```javascript
// Toggle dark mode
document.documentElement.classList.toggle('dark');

// Tom Select will automatically adapt
```

### Custom Dark Mode Colors

```css
:root.dark {
    --p: 239 84% 67%;        /* Primary color (dark mode) */
    --b1: 222 47% 11%;       /* Background (dark mode) */
    --bc: 0 0% 90%;          /* Text color (dark mode) */
}
```

---

## Alpine.js Integration

Tom Select works seamlessly with Alpine.js:

```blade
<div x-data="{ selected: '' }">
    <select 
        id="my-select"
        x-model="selected"
        class="select select-bordered">
        <option value="">Select...</option>
        <option value="1">Option 1</option>
        <option value="2">Option 2</option>
    </select>
    
    <p x-show="selected">Selected: <span x-text="selected"></span></p>
</div>

<script>
    new TomSelect('#my-select', {
        onChange: (value) => {
            // Trigger Alpine.js update
            document.getElementById('my-select')
                .dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
</script>
```

---

## Livewire Integration

Tom Select works with Livewire:

```blade
<div wire:ignore>
    <select 
        id="my-select"
        wire:model="selectedValue"
        class="select select-bordered">
        <option value="">Select...</option>
        @foreach($options as $option)
            <option value="{{ $option->id }}">{{ $option->name }}</option>
        @endforeach
    </select>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function() {
        const tomSelect = new TomSelect('#my-select', {
            onChange: (value) => {
                @this.set('selectedValue', value);
            }
        });
        
        // Listen for Livewire updates
        Livewire.on('updateSelect', (value) => {
            tomSelect.setValue(value);
        });
    });
</script>
@endpush
```

---

## Troubleshooting

### Issue: Tom Select not found

**Solution**: Ensure CanvaStack assets are published:

```bash
php artisan vendor:publish --tag=canvastack-assets --force
```

### Issue: Styling not applied

**Solution**: Clear cache and rebuild:

```bash
php artisan cache:clear
php artisan view:clear
npm run build
```

### Issue: Dark mode not working

**Solution**: Ensure dark class is on html element:

```javascript
document.documentElement.classList.add('dark');
```

### Issue: Alpine.js not syncing

**Solution**: Dispatch change event:

```javascript
new TomSelect('#my-select', {
    onChange: (value) => {
        document.getElementById('my-select')
            .dispatchEvent(new Event('change', { bubbles: true }));
    }
});
```

---

## Performance Tips

### 1. Lazy Loading

Initialize Tom Select only when needed:

```javascript
document.querySelectorAll('select.tom-select').forEach(select => {
    select.addEventListener('focus', function() {
        if (!this.tomselect) {
            new TomSelect(this);
        }
    }, { once: true });
});
```

### 2. Limit Options

For large datasets:

```javascript
new TomSelect('#my-select', {
    maxOptions: 100,
    load: function(query, callback) {
        // Load options via AJAX
    }
});
```

### 3. Destroy When Not Needed

```javascript
// Destroy instance to free memory
if (tomSelect) {
    tomSelect.destroy();
}
```

---

## Examples

### Example 1: Basic Form

```blade
<form method="POST" action="/submit">
    @csrf
    
    <div class="form-control">
        <label class="label">
            <span class="label-text">Country</span>
        </label>
        <select name="country" id="country" class="select select-bordered">
            <option value="">Select country...</option>
            <option value="us">United States</option>
            <option value="uk">United Kingdom</option>
        </select>
    </div>
    
    <button type="submit" class="btn btn-primary">Submit</button>
</form>

<script>
    new TomSelect('#country', {
        plugins: ['clear_button']
    });
</script>
```

### Example 2: Multi-Select with Tags

```blade
<div class="form-control">
    <label class="label">
        <span class="label-text">Skills</span>
    </label>
    <select name="skills[]" id="skills" multiple class="select select-bordered">
        <option value="php">PHP</option>
        <option value="javascript">JavaScript</option>
        <option value="python">Python</option>
    </select>
</div>

<script>
    new TomSelect('#skills', {
        plugins: {
            'remove_button': {},
            'dropdown_input': {}
        },
        maxItems: null,
        create: true
    });
</script>
```

### Example 3: AJAX Search

```blade
<div class="form-control">
    <label class="label">
        <span class="label-text">Search Users</span>
    </label>
    <select name="user_id" id="user-search" class="select select-bordered"></select>
</div>

<script>
    new TomSelect('#user-search', {
        valueField: 'id',
        labelField: 'name',
        searchField: 'name',
        load: function(query, callback) {
            if (!query.length) return callback();
            
            fetch('/api/users/search?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(json => callback(json.data))
                .catch(() => callback());
        }
    });
</script>
```

---

## Resources

- [Tom Select Documentation](https://tom-select.js.org/)
- [CanvaStack Documentation](../README.md)
- [DaisyUI Documentation](https://daisyui.com/)
- [Alpine.js Documentation](https://alpinejs.dev/)

---

**Last Updated**: 2026-03-12  
**Version**: 1.0.0  
**Status**: Published
