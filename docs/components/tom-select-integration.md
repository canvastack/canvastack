# Tom Select Integration

## Overview

CanvaStack includes Tom Select library for enhanced dropdown/select functionality with custom styling that matches DaisyUI theme. Tom Select provides searchable, customizable dropdowns that work seamlessly with Alpine.js and support both light and dark modes.

**Location**: Integrated in FilterModal component  
**Version**: 2.5.2+  
**Theme**: Custom DaisyUI-compatible theme

---

## Features

✅ **Custom Styling** - Matches DaisyUI design system  
✅ **Dark Mode Support** - Automatic theme switching  
✅ **Searchable Dropdowns** - Built-in search functionality  
✅ **Clear Button** - Easy selection clearing  
✅ **Keyboard Navigation** - Full keyboard support  
✅ **Accessibility** - ARIA labels and focus management  
✅ **Alpine.js Integration** - Works with x-model bindings  
✅ **Responsive** - Mobile-friendly design

---

## Automatic Integration

Tom Select is **automatically initialized** for all select elements in the filter modal. No manual setup required!

### How It Works

1. When filter modal opens → Tom Select initializes all `<select>` elements
2. When filter modal closes → Tom Select instances are destroyed
3. When filter changes → Alpine.js x-model is updated automatically

### Example (Automatic)

```blade
{{-- In filter modal - Tom Select auto-initializes --}}
<select 
    id="filter_{{ $filter['column'] }}"
    class="select select-bordered w-full"
    x-model="filterValues.{{ $filter['column'] }}"
    @change="handleFilterChange({{ json_encode($filter) }})">
    <option value="">{{ __('ui.filters.select_option') }}</option>
    @foreach($filter['options'] as $option)
        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
    @endforeach
</select>
```

---

## Manual Integration (Outside Filter Modal)

If you want to use Tom Select outside the filter modal:

### Step 1: Import Tom Select

```javascript
import TomSelect from 'tom-select';
```

### Step 2: Initialize

```javascript
const select = document.getElementById('my-select');

const tomSelect = new TomSelect(select, {
    // Plugins
    plugins: {
        'dropdown_input': {},
        'clear_button': {
            title: 'Clear selection'
        }
    },
    
    // Behavior
    create: false,
    sortField: {
        field: 'text',
        direction: 'asc'
    },
    maxOptions: 1000,
    
    // Callbacks
    onChange: (value) => {
        console.log('Selected:', value);
    }
});
```

### Step 3: Include CSS

The CSS is automatically included when you import CanvaStack CSS:

```blade
{{-- In your layout --}}
<link rel="stylesheet" href="{{ asset('vendor/canvastack/css/canvastack.css') }}">
```

---

## Configuration Options

### Basic Options

```javascript
new TomSelect(select, {
    // Allow creating new options
    create: false,
    
    // Maximum options to display
    maxOptions: 1000,
    
    // Maximum items to select (for multi-select)
    maxItems: 1,
    
    // Placeholder text
    placeholder: 'Select an option...',
    
    // Sort options
    sortField: {
        field: 'text',
        direction: 'asc'
    }
});
```

### Plugins

```javascript
new TomSelect(select, {
    plugins: {
        // Search input in dropdown
        'dropdown_input': {},
        
        // Clear button
        'clear_button': {
            title: 'Clear selection'
        },
        
        // Remove button for multi-select
        'remove_button': {
            title: 'Remove this item'
        },
        
        // Checkbox options (multi-select)
        'checkbox_options': {}
    }
});
```

### Callbacks

```javascript
new TomSelect(select, {
    // When item is added
    onItemAdd: (value, item) => {
        console.log('Added:', value);
    },
    
    // When item is removed
    onItemRemove: (value) => {
        console.log('Removed:', value);
    },
    
    // When selection changes
    onChange: (value) => {
        console.log('Changed:', value);
    },
    
    // When cleared
    onClear: () => {
        console.log('Cleared');
    },
    
    // When dropdown opens
    onDropdownOpen: (dropdown) => {
        console.log('Dropdown opened');
    },
    
    // When dropdown closes
    onDropdownClose: (dropdown) => {
        console.log('Dropdown closed');
    }
});
```

---

## Alpine.js Integration

Tom Select works seamlessly with Alpine.js x-model:

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
    
    <p x-text="'Selected: ' + selected"></p>
</div>

<script>
    // Initialize Tom Select
    const tomSelect = new TomSelect('#my-select', {
        onChange: (value) => {
            // Trigger Alpine.js update
            const select = document.getElementById('my-select');
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
</script>
```

---

## Styling Customization

### Using CSS Variables

Tom Select theme uses DaisyUI CSS variables, so it automatically adapts to your theme:

```css
/* Custom colors */
:root {
    --p: 239 84% 67%;        /* Primary color */
    --pc: 0 0% 100%;         /* Primary content */
    --b1: 0 0% 100%;         /* Background */
    --bc: 0 0% 20%;          /* Text color */
}
```

### Custom CSS Classes

You can add custom classes to Tom Select elements:

```javascript
new TomSelect(select, {
    controlClass: 'ts-control my-custom-class',
    dropdownClass: 'ts-dropdown my-dropdown-class'
});
```

### Override Styles

```css
/* Override control height */
.ts-control {
    height: 2.5rem !important;
}

/* Override dropdown max height */
.ts-dropdown {
    max-height: 400px !important;
}

/* Custom option hover color */
.ts-dropdown .option:hover {
    background-color: hsl(var(--s)) !important;
}
```

---

## Dark Mode Support

Tom Select automatically supports dark mode using DaisyUI's dark mode system:

```html
<!-- Toggle dark mode -->
<button onclick="document.documentElement.classList.toggle('dark')">
    Toggle Dark Mode
</button>
```

The theme will automatically switch between light and dark styles.

---

## Multi-Select

Enable multi-select mode:

```javascript
new TomSelect(select, {
    maxItems: null, // Unlimited selections
    plugins: {
        'remove_button': {
            title: 'Remove this item'
        }
    }
});
```

```blade
<select id="multi-select" multiple class="select select-bordered">
    <option value="1">Option 1</option>
    <option value="2">Option 2</option>
    <option value="3">Option 3</option>
</select>
```

---

## AJAX Loading

Load options dynamically via AJAX:

```javascript
new TomSelect(select, {
    valueField: 'id',
    labelField: 'name',
    searchField: 'name',
    load: function(query, callback) {
        if (!query.length) return callback();
        
        fetch('/api/search?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(json => {
                callback(json.items);
            })
            .catch(() => {
                callback();
            });
    }
});
```

---

## Programmatic Control

### Get/Set Value

```javascript
// Get value
const value = tomSelect.getValue();

// Set value
tomSelect.setValue('option-1');

// Clear value
tomSelect.clear();
```

### Add/Remove Options

```javascript
// Add option
tomSelect.addOption({
    value: 'new-option',
    text: 'New Option'
});

// Remove option
tomSelect.removeOption('option-1');

// Clear all options
tomSelect.clearOptions();
```

### Enable/Disable

```javascript
// Disable
tomSelect.disable();

// Enable
tomSelect.enable();
```

### Destroy

```javascript
// Destroy instance
tomSelect.destroy();
```

---

## Performance Tips

### 1. Lazy Initialization

Only initialize Tom Select when needed:

```javascript
// Initialize on focus
select.addEventListener('focus', function() {
    if (!this.tomselect) {
        new TomSelect(this, options);
    }
}, { once: true });
```

### 2. Limit Options

For large datasets, limit displayed options:

```javascript
new TomSelect(select, {
    maxOptions: 100, // Show max 100 options
    render: {
        option: function(data, escape) {
            // Custom rendering for better performance
            return '<div>' + escape(data.text) + '</div>';
        }
    }
});
```

### 3. Virtual Scrolling

For very large lists, consider virtual scrolling:

```javascript
new TomSelect(select, {
    plugins: ['virtual_scroll'],
    maxOptions: 10000
});
```

---

## Troubleshooting

### Issue: Tom Select not initializing

**Solution**: Ensure the select element exists in DOM before initialization:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    new TomSelect('#my-select', options);
});
```

### Issue: Alpine.js x-model not updating

**Solution**: Dispatch change event after Tom Select changes:

```javascript
new TomSelect(select, {
    onChange: (value) => {
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }
});
```

### Issue: Styling not applied

**Solution**: Ensure CanvaStack CSS is loaded:

```blade
<link rel="stylesheet" href="{{ asset('vendor/canvastack/css/canvastack.css') }}">
```

### Issue: Dropdown hidden behind modal

**Solution**: Increase z-index:

```css
.ts-dropdown {
    z-index: 9999 !important;
}
```

---

## Browser Support

Tom Select supports all modern browsers:

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Opera 76+

---

## Resources

### Official Documentation
- [Tom Select Documentation](https://tom-select.js.org/)
- [Tom Select GitHub](https://github.com/orchidjs/tom-select)

### CanvaStack Documentation
- [Filter Modal Component](./filter-modal.md)
- [DaisyUI Integration](../frontend/daisyui.md)
- [Dark Mode Support](../frontend/dark-mode.md)

---

## Examples

### Example 1: Basic Select

```blade
<select id="country" class="select select-bordered">
    <option value="">Select country...</option>
    <option value="us">United States</option>
    <option value="uk">United Kingdom</option>
    <option value="ca">Canada</option>
</select>

<script>
    new TomSelect('#country', {
        plugins: ['clear_button']
    });
</script>
```

### Example 2: Searchable Multi-Select

```blade
<select id="tags" multiple class="select select-bordered">
    <option value="php">PHP</option>
    <option value="javascript">JavaScript</option>
    <option value="python">Python</option>
    <option value="ruby">Ruby</option>
</select>

<script>
    new TomSelect('#tags', {
        plugins: {
            'remove_button': {},
            'dropdown_input': {}
        },
        maxItems: null
    });
</script>
```

### Example 3: AJAX with Loading State

```blade
<select id="users" class="select select-bordered"></select>

<script>
    new TomSelect('#users', {
        valueField: 'id',
        labelField: 'name',
        searchField: 'name',
        load: function(query, callback) {
            const url = '/api/users?search=' + encodeURIComponent(query);
            
            fetch(url)
                .then(response => response.json())
                .then(json => callback(json.data))
                .catch(() => callback());
        },
        render: {
            option: function(item, escape) {
                return `
                    <div class="flex items-center gap-2">
                        <img src="${escape(item.avatar)}" class="w-8 h-8 rounded-full">
                        <div>
                            <div class="font-semibold">${escape(item.name)}</div>
                            <div class="text-sm text-gray-500">${escape(item.email)}</div>
                        </div>
                    </div>
                `;
            }
        }
    });
</script>
```

---

**Last Updated**: 2026-03-12  
**Version**: 1.0.0  
**Status**: Published
