# TailwindCSS Guide (TailwindAdapter)

**Version:** 2.0.0  
**Last Updated:** April 4, 2026

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

## Overview

This guide covers the TailwindCSS implementation in the Theme Engine through the `TailwindAdapter`.

**Template Name:** `canvas`  
**Adapter Class:** `Canvastack\Canvastack\Library\Theme\Adapters\TailwindAdapter`  
**Framework:** TailwindCSS 3.x  
**Status:** Production Ready

---

## Key Characteristics

### Utility-First Approach

TailwindCSS uses utility classes instead of component classes:

```html
<!-- Bootstrap -->
<button class="btn btn-primary">Submit</button>

<!-- TailwindCSS -->
<button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Submit</button>
```

### No Bootstrap Dependencies

TailwindAdapter is completely independent of Bootstrap:
- No Bootstrap CSS or JS required
- Custom JavaScript for modals and tooltips
- Native HTML elements with Tailwind styling

---

## Configuration

### CDN (Development)

```php
// config/canvastack.templates.php
'canvas' => [
    'position' => [
        'top' => [
            'js' => ['https://cdn.tailwindcss.com'],
            'css' => [null],
        ],
    ],
],
```

### Custom Build (Production)

```bash
# Install Tailwind
npm install -D tailwindcss

# Create config
npx tailwindcss init

# Configure tailwind.config.js
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./vendor/canvastack/canvastack/src/**/*.php",
    ],
    theme: {
        extend: {},
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
}

# Build CSS
npx tailwindcss -i ./resources/css/app.css -o ./public/css/canvas.css --minify
```

```php
// config/canvastack.templates.php
'canvas' => [
    'position' => [
        'bottom' => [
            'first' => [
                'css' => ['css/canvas.css'],
            ],
        ],
    ],
],
```

---

## Component Examples

### Tab Headers

```php
echo canvastack_form_create_header_tab('Users', 'users-tab', true, 'custom-class');
```

**Output:**
```html
<div class="flex items-center px-4 py-2 cursor-pointer border-b-2 border-blue-500 custom-class" data-tab="users-tab">
    Users
</div>
```

**Tailwind Classes:**
- `flex items-center` - Flexbox layout
- `px-4 py-2` - Padding
- `cursor-pointer` - Pointer cursor
- `border-b-2 border-blue-500` - Active tab indicator

---

### Alert Messages

```php
echo canvastack_form_alert_message('Operation successful!', 'success', 'Success', 'msg', false);
```

**Output:**
```html
<div class="flex items-start gap-3 p-4 rounded-lg bg-green-100 border border-green-400 text-green-700" role="alert">
    <button class="ml-auto text-green-700 hover:text-green-900" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    <div>
        <h4 class="font-bold text-lg mb-2">Success</h4>
        <p>Operation successful!</p>
    </div>
</div>
```

**Tailwind Classes:**
- `flex items-start gap-3` - Flexbox layout with gap
- `p-4 rounded-lg` - Padding and rounded corners
- `bg-green-100 border border-green-400 text-green-700` - Success colors

---

### Checkboxes

```php
echo canvastack_form_checkList('terms', '1', 'I agree to terms', false, 'primary', 'terms-cb', null);
```

**Output:**
```html
<div class="flex items-center gap-2">
    <input type="checkbox" name="terms" value="1" id="terms-cb" 
           class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
    <label for="terms-cb" class="text-sm text-gray-700">I agree to terms</label>
</div>
```

**Tailwind Classes:**
- `flex items-center gap-2` - Flexbox layout
- `form-checkbox` - Tailwind Forms plugin class
- `h-4 w-4` - Size
- `text-blue-600` - Color
- `focus:ring-blue-500` - Focus state

---

### Select Elements

```php
$countries = ['US' => 'United States', 'UK' => 'United Kingdom'];
echo canvastack_form_selectbox('country', $countries, 'US', ['class' => 'w-full'], true, false);
```

**Output:**
```html
<select name="country" class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
    <option value="US" selected>United States</option>
    <option value="UK">United Kingdom</option>
</select>
```

**Tailwind Classes:**
- `form-input` - Tailwind Forms plugin class
- `rounded-md border-gray-300` - Border styling
- `focus:border-blue-500 focus:ring focus:ring-blue-200` - Focus states

---

## CSS Class Mapping

### Layout Classes

| Bootstrap | TailwindCSS |
|-----------|-------------|
| `container` | `container mx-auto` |
| `row` | `flex flex-wrap` |
| `col-md-6` | `w-full md:w-1/2` |
| `col-md-4` | `w-full md:w-1/3` |
| `col-md-3` | `w-full md:w-1/4` |

### Utility Classes

| Bootstrap | TailwindCSS |
|-----------|-------------|
| `pull-right` / `float-end` | `ml-auto` |
| `pull-left` / `float-start` | `mr-auto` |
| `hide` / `d-none` | `hidden` |
| `show` / `d-block` | `block` |
| `text-center` | `text-center` |
| `text-right` | `text-right` |

### Spacing Classes

| Bootstrap | TailwindCSS |
|-----------|-------------|
| `m-0` | `m-0` |
| `m-1` | `m-1` (0.25rem) |
| `m-2` | `m-2` (0.5rem) |
| `m-3` | `m-3` (0.75rem) |
| `m-4` | `m-4` (1rem) |
| `m-5` | `m-5` (1.25rem) |
| `mt-3` | `mt-3` |
| `mb-3` | `mb-3` |
| `mx-auto` | `mx-auto` |

### Button Classes

| Bootstrap | TailwindCSS |
|-----------|-------------|
| `btn btn-primary` | `px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600` |
| `btn btn-secondary` | `px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600` |
| `btn btn-success` | `px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600` |
| `btn btn-danger` | `px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600` |
| `btn btn-sm` | `px-3 py-1 text-sm` |
| `btn btn-lg` | `px-6 py-3 text-lg` |

---

## JavaScript Integration

### Custom Modal Implementation

```javascript
// canvastack-modal-adapter.js
const CanvaStackModal = {
    show: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    },
    
    hide: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    },
    
    toggle: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
        }
    }
};
```

### Tooltip Implementation (Tippy.js)

```html
<!-- Include Tippy.js -->
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>

<script>
    // Initialize tooltips
    tippy('[data-tippy-content]', {
        theme: 'light',
        placement: 'top',
    });
</script>
```

---

## Tailwind Forms Plugin

### Installation

```bash
npm install -D @tailwindcss/forms
```

```javascript
// tailwind.config.js
module.exports = {
    plugins: [
        require('@tailwindcss/forms'),
    ],
}
```

### Form Classes

```html
<!-- Input -->
<input type="text" class="form-input rounded-md">

<!-- Select -->
<select class="form-select rounded-md">
    <option>Option 1</option>
</select>

<!-- Checkbox -->
<input type="checkbox" class="form-checkbox rounded">

<!-- Radio -->
<input type="radio" class="form-radio">

<!-- Textarea -->
<textarea class="form-textarea rounded-md"></textarea>
```

---

## Best Practices

### 1. Use Tailwind Forms Plugin

```bash
npm install -D @tailwindcss/forms
```

### 2. Purge Unused CSS in Production

```javascript
// tailwind.config.js
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./vendor/canvastack/canvastack/src/**/*.php",
    ],
}
```

### 3. Use JIT Mode

```javascript
// tailwind.config.js
module.exports = {
    mode: 'jit',
    content: [...],
}
```

### 4. Create Component Classes

```css
/* resources/css/app.css */
@layer components {
    .btn-primary {
        @apply px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
    }
    
    .btn-secondary {
        @apply px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2;
    }
}
```

---

## Migration from Bootstrap

### Step 1: Install Tailwind

```bash
npm install -D tailwindcss @tailwindcss/forms
npx tailwindcss init
```

### Step 2: Configure Tailwind

```javascript
// tailwind.config.js
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./vendor/canvastack/canvastack/src/**/*.php",
    ],
    theme: {
        extend: {},
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
}
```

### Step 3: Update Views

```blade
{{-- Before (Bootstrap) --}}
<div class="container">
    <div class="row">
        <div class="col-md-6">
            <button class="btn btn-primary">Submit</button>
        </div>
    </div>
</div>

{{-- After (TailwindCSS) --}}
<div class="container mx-auto">
    <div class="flex flex-wrap">
        <div class="w-full md:w-1/2">
            <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Submit</button>
        </div>
    </div>
</div>
```

### Step 4: Update Configuration

```php
// config/canvastack.templates.php
'template' => 'canvas',
```

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may this TailwindCSS guide serve developers well.
