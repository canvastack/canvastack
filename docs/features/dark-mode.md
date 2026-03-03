# Dark Mode

**Version**: 1.0.0  
**Last Updated**: 2026-02-26  
**Status**: Complete

---

## Table of Contents

1. [Overview](#overview)
2. [Configuration](#configuration)
3. [Implementation](#implementation)
4. [Color System](#color-system)
5. [Component Support](#component-support)
6. [Custom Themes](#custom-themes)
7. [JavaScript API](#javascript-api)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)

---

## Overview

CanvaStack includes built-in dark mode support with smooth transitions, localStorage persistence, and full component compatibility.

### Features

- ✅ Class-based dark mode (`dark:` prefix)
- ✅ Automatic system preference detection
- ✅ LocalStorage persistence
- ✅ Smooth transitions
- ✅ Full component support
- ✅ Customizable color palette
- ✅ Alpine.js integration

### Browser Support

- Chrome/Edge 76+
- Firefox 67+
- Safari 12.1+
- Opera 62+

---

## Configuration

### Enable Dark Mode

`config/canvastack-ui.php`:

```php
return [
    'dark_mode' => [
        // Enable dark mode
        'enabled' => true,
        
        // Default mode (light, dark, system)
        'default' => 'system',
        
        // Storage key for localStorage
        'storage_key' => 'canvastack_theme',
        
        // Transition duration (ms)
        'transition_duration' => 200,
        
        // Class name for dark mode
        'class' => 'dark',
        
        // Selector for theme toggle button
        'toggle_selector' => '[data-theme-toggle]',
    ],
];
```

### Tailwind Configuration

`tailwind.config.js`:

```javascript
module.exports = {
    // Enable dark mode with class strategy
    darkMode: 'class',
    
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    
    theme: {
        extend: {
            colors: {
                // Custom dark mode colors
                dark: {
                    bg: '#1a1a1a',
                    surface: '#2d2d2d',
                    border: '#404040',
                    text: '#e5e5e5',
                },
            },
        },
    },
};
```

---

## Implementation

### Basic Setup

Add dark mode script to your layout:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    
    {{-- Dark mode initialization (before body renders) --}}
    <script>
        // Prevent flash of unstyled content
        (function() {
            const theme = localStorage.getItem('canvastack_theme') || 'system';
            const isDark = theme === 'dark' || 
                (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            
            if (isDark) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white dark:bg-dark-bg text-gray-900 dark:text-dark-text">
    {{-- Theme toggle button --}}
    <button data-theme-toggle class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-dark-surface">
        <svg class="w-6 h-6 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
            {{-- Sun icon (shown in dark mode) --}}
            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/>
        </svg>
        <svg class="w-6 h-6 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
            {{-- Moon icon (shown in light mode) --}}
            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
        </svg>
    </button>
    
    {{ $slot }}
</body>
</html>
```

### JavaScript Implementation

`resources/js/theme.js`:

```javascript
// Theme management
class ThemeManager {
    constructor() {
        this.storageKey = 'canvastack_theme';
        this.theme = this.getTheme();
        this.init();
    }
    
    init() {
        // Apply initial theme
        this.applyTheme(this.theme);
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', (e) => {
                if (this.theme === 'system') {
                    this.applyTheme('system');
                }
            });
        
        // Setup toggle buttons
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            button.addEventListener('click', () => this.toggle());
        });
    }
    
    getTheme() {
        return localStorage.getItem(this.storageKey) || 'system';
    }
    
    setTheme(theme) {
        this.theme = theme;
        localStorage.setItem(this.storageKey, theme);
        this.applyTheme(theme);
    }
    
    applyTheme(theme) {
        const isDark = theme === 'dark' || 
            (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        // Dispatch event for other components
        window.dispatchEvent(new CustomEvent('theme-changed', { 
            detail: { theme, isDark } 
        }));
    }
    
    toggle() {
        const isDark = document.documentElement.classList.contains('dark');
        this.setTheme(isDark ? 'light' : 'dark');
    }
}

// Initialize theme manager
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
});
```

### Alpine.js Integration

```blade
<div x-data="{ 
    theme: localStorage.getItem('canvastack_theme') || 'system',
    isDark: false,
    
    init() {
        this.updateTheme();
        
        // Listen for theme changes
        window.addEventListener('theme-changed', (e) => {
            this.theme = e.detail.theme;
            this.isDark = e.detail.isDark;
        });
    },
    
    updateTheme() {
        this.isDark = this.theme === 'dark' || 
            (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    },
    
    toggle() {
        window.themeManager.toggle();
    }
}">
    <button @click="toggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-dark-surface">
        <svg x-show="!isDark" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            {{-- Moon icon --}}
            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
        </svg>
        <svg x-show="isDark" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            {{-- Sun icon --}}
            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
    </button>
</div>
```

---

## Color System

### Base Colors

```css
/* Light mode (default) */
:root {
    --color-bg: #ffffff;
    --color-surface: #f9fafb;
    --color-border: #e5e7eb;
    --color-text: #111827;
    --color-text-secondary: #6b7280;
}

/* Dark mode */
.dark {
    --color-bg: #1a1a1a;
    --color-surface: #2d2d2d;
    --color-border: #404040;
    --color-text: #e5e5e5;
    --color-text-secondary: #a3a3a3;
}
```

### Tailwind Classes

```blade
{{-- Background colors --}}
<div class="bg-white dark:bg-dark-bg">
    <div class="bg-gray-50 dark:bg-dark-surface">
        Content
    </div>
</div>

{{-- Text colors --}}
<h1 class="text-gray-900 dark:text-dark-text">Title</h1>
<p class="text-gray-600 dark:text-gray-400">Description</p>

{{-- Border colors --}}
<div class="border border-gray-200 dark:border-dark-border">
    Content
</div>

{{-- Hover states --}}
<button class="hover:bg-gray-100 dark:hover:bg-dark-surface">
    Button
</button>
```

### Component Colors

```blade
{{-- Cards --}}
<div class="bg-white dark:bg-dark-surface border border-gray-200 dark:border-dark-border rounded-lg shadow-sm">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-dark-text">Card Title</h3>
        <p class="text-gray-600 dark:text-gray-400">Card content</p>
    </div>
</div>

{{-- Buttons --}}
<button class="px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600">
    Primary Button
</button>

{{-- Inputs --}}
<input type="text" class="w-full px-3 py-2 bg-white dark:bg-dark-surface border border-gray-300 dark:border-dark-border text-gray-900 dark:text-dark-text rounded-lg focus:ring-2 focus:ring-indigo-500">
```

---

## Component Support

### Table Component

```php
use Canvastack\Components\Table\TableBuilder;

$table = new TableBuilder();

// Dark mode is automatically supported
$table->column('name', 'Name');
$table->column('email', 'Email');

// Custom dark mode styling
$table->column('status', 'Status')
    ->format(function ($value) {
        $colors = [
            'active' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
            'inactive' => 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200',
        ];
        
        return "<span class='px-2 py-1 rounded {$colors[$value]}'>{$value}</span>";
    });
```

### Form Component

```php
use Canvastack\Components\Form\FormBuilder;

$form = new FormBuilder();

// Dark mode is automatically supported
$form->text('name', 'Name')
    ->placeholder('Enter your name');

$form->select('status', 'Status', [
    'active' => 'Active',
    'inactive' => 'Inactive',
]);

// Custom dark mode styling
$form->text('email', 'Email')
    ->class('bg-white dark:bg-dark-surface border-gray-300 dark:border-dark-border');
```

### Chart Component

```php
use Canvastack\Components\Chart\ChartBuilder;

$chart = new ChartBuilder();

// Dark mode colors
$chart->line([
    'labels' => ['Jan', 'Feb', 'Mar'],
    'datasets' => [
        [
            'label' => 'Sales',
            'data' => [100, 200, 150],
            'borderColor' => 'rgb(99, 102, 241)', // Indigo
            'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
        ],
    ],
], [
    'theme' => [
        'mode' => 'dark', // Auto-detect from document
        'palette' => 'palette1',
    ],
]);
```

---

## Custom Themes

### Define Custom Theme

`config/canvastack-ui.php`:

```php
return [
    'themes' => [
        'light' => [
            'bg' => '#ffffff',
            'surface' => '#f9fafb',
            'border' => '#e5e7eb',
            'text' => '#111827',
            'primary' => '#6366f1',
        ],
        
        'dark' => [
            'bg' => '#1a1a1a',
            'surface' => '#2d2d2d',
            'border' => '#404040',
            'text' => '#e5e5e5',
            'primary' => '#818cf8',
        ],
        
        'custom' => [
            'bg' => '#0f172a',
            'surface' => '#1e293b',
            'border' => '#334155',
            'text' => '#f1f5f9',
            'primary' => '#3b82f6',
        ],
    ],
];
```

### Apply Custom Theme

```javascript
// Set custom theme
window.themeManager.setTheme('custom');

// Or via Alpine.js
<button @click="$dispatch('set-theme', { theme: 'custom' })">
    Custom Theme
</button>
```

### Theme Switcher

```blade
<div x-data="{ 
    themes: ['light', 'dark', 'custom'],
    currentTheme: localStorage.getItem('canvastack_theme') || 'system'
}">
    <select x-model="currentTheme" @change="window.themeManager.setTheme(currentTheme)">
        <option value="system">System</option>
        <option value="light">Light</option>
        <option value="dark">Dark</option>
        <option value="custom">Custom</option>
    </select>
</div>
```

---

## JavaScript API

### ThemeManager API

```javascript
// Get current theme
const theme = window.themeManager.getTheme();
// Returns: 'light', 'dark', or 'system'

// Set theme
window.themeManager.setTheme('dark');

// Toggle theme
window.themeManager.toggle();

// Check if dark mode is active
const isDark = document.documentElement.classList.contains('dark');

// Listen for theme changes
window.addEventListener('theme-changed', (e) => {
    console.log('Theme:', e.detail.theme);
    console.log('Is dark:', e.detail.isDark);
});
```

### Detect System Preference

```javascript
// Check system preference
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

// Listen for system preference changes
window.matchMedia('(prefers-color-scheme: dark)')
    .addEventListener('change', (e) => {
        console.log('System prefers dark:', e.matches);
    });
```

---

## Best Practices

### 1. Use Semantic Colors

```blade
{{-- ✅ GOOD - Semantic colors --}}
<div class="bg-white dark:bg-dark-bg">
    <p class="text-gray-900 dark:text-dark-text">Content</p>
</div>

{{-- ❌ BAD - Hard-coded colors --}}
<div class="bg-white dark:bg-gray-900">
    <p class="text-black dark:text-white">Content</p>
</div>
```

### 2. Test Both Modes

Always test your UI in both light and dark modes:

```bash
# Toggle dark mode in browser DevTools
document.documentElement.classList.toggle('dark');
```

### 3. Provide Smooth Transitions

```css
/* Add transitions for smooth theme switching */
* {
    transition: background-color 200ms ease-in-out,
                border-color 200ms ease-in-out,
                color 200ms ease-in-out;
}
```

### 4. Respect User Preference

```javascript
// Default to system preference
const defaultTheme = 'system';

// Allow user to override
const userTheme = localStorage.getItem('canvastack_theme') || defaultTheme;
```

### 5. Accessible Toggle Button

```blade
<button 
    data-theme-toggle 
    aria-label="Toggle dark mode"
    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-dark-surface"
>
    <span class="sr-only">Toggle dark mode</span>
    {{-- Icon --}}
</button>
```

---

## Troubleshooting

### Flash of Unstyled Content

**Problem**: Page flashes light mode before switching to dark

**Solution**: Add inline script before body:

```blade
<script>
    (function() {
        const theme = localStorage.getItem('canvastack_theme') || 'system';
        const isDark = theme === 'dark' || 
            (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        
        if (isDark) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
```

### Colors Not Changing

**Problem**: Some colors don't change in dark mode

**Solution**: Ensure all colors have dark mode variants:

```blade
{{-- ✅ GOOD --}}
<div class="bg-white dark:bg-dark-bg text-gray-900 dark:text-dark-text">

{{-- ❌ BAD - Missing dark mode variant --}}
<div class="bg-white text-gray-900">
```

### Theme Not Persisting

**Problem**: Theme resets on page reload

**Solution**: Check localStorage is working:

```javascript
// Test localStorage
try {
    localStorage.setItem('test', 'test');
    localStorage.removeItem('test');
} catch (e) {
    console.error('localStorage not available:', e);
}
```

### System Preference Not Detected

**Problem**: System preference not being detected

**Solution**: Check media query support:

```javascript
// Check support
if (window.matchMedia) {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    console.log('System prefers dark:', prefersDark);
} else {
    console.error('matchMedia not supported');
}
```

---

## See Also

- [UI Configuration](../getting-started/configuration.md)
- [Component Styling](../components/README.md)
- [Best Practices](../guides/best-practices.md)

---

**Next**: [Eager Loading Guide](eager-loading.md)
