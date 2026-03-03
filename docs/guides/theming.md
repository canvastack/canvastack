# Theming Guide

Complete guide for creating and customizing themes in CanvaStack.

## 📦 Overview

CanvaStack's Theme Engine provides:
- Dynamic theme switching
- Dark mode support
- CSS variable system
- Multiple pre-built themes
- Custom theme creation
- Locale-specific fonts
- RTL support integration

---

## 🎨 Pre-Built Themes

CanvaStack includes several pre-built themes:

### 1. Default Theme (Gradient)
- **Primary**: Indigo (#6366f1)
- **Secondary**: Purple (#8b5cf6)
- **Accent**: Fuchsia (#a855f7)
- **Style**: Modern gradient-based design

### 2. Ocean Theme
- **Primary**: Sky Blue (#0ea5e9)
- **Secondary**: Cyan (#06b6d4)
- **Accent**: Teal (#14b8a6)
- **Style**: Cool, professional ocean colors

### 3. Sunset Theme
- **Primary**: Orange (#f97316)
- **Secondary**: Rose (#f43f5e)
- **Accent**: Pink (#ec4899)
- **Style**: Warm, vibrant sunset colors

### 4. Forest Theme
- **Primary**: Green (#10b981)
- **Secondary**: Emerald (#059669)
- **Accent**: Lime (#84cc16)
- **Style**: Natural, earthy forest colors

### 5. Midnight Theme
- **Primary**: Slate (#64748b)
- **Secondary**: Gray (#6b7280)
- **Accent**: Zinc (#71717a)
- **Style**: Dark, professional monochrome

---

## 🚀 Quick Start

### Using a Pre-Built Theme

#### Step 1: Set Theme in Environment

Update `.env`:
```env
CANVASTACK_THEME=ocean
```

#### Step 2: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

#### Step 3: Verify Theme

Visit your application and the new theme should be active.

---

## 🎯 Creating Custom Themes

### Method 1: Configuration File (Recommended)

#### Step 1: Add Theme to Config

Edit `config/canvastack-ui.php`:

```php
return [
    'theme' => [
        'registry' => [
            [
                'name' => 'corporate',
                'display_name' => 'Corporate Theme',
                'version' => '1.0.0',
                'author' => 'Your Name',
                'description' => 'Professional corporate theme',
                'colors' => [
                    'primary' => '#1e40af',      // Blue
                    'secondary' => '#7c3aed',    // Violet
                    'accent' => '#db2777',       // Pink
                    'background' => '#ffffff',   // White
                    'text' => '#111827',         // Gray 900
                    'success' => '#10b981',      // Green
                    'warning' => '#f59e0b',      // Amber
                    'error' => '#ef4444',        // Red
                    'info' => '#3b82f6',         // Blue
                ],
                'fonts' => [
                    'sans' => 'Inter, system-ui, sans-serif',
                    'mono' => 'JetBrains Mono, monospace',
                ],
                'layout' => [
                    'container' => '1280px',
                    'spacing' => '1rem',
                ],
                'dark_mode' => [
                    'enabled' => true,
                    'default' => 'light',
                ],
            ],
        ],
    ],
];
```

#### Step 2: Activate Theme

Update `.env`:
```env
CANVASTACK_THEME=corporate
```

#### Step 3: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

---

### Method 2: JSON File

#### Step 1: Create Theme Directory

```bash
mkdir -p resources/themes/corporate
```

#### Step 2: Create theme.json

Create `resources/themes/corporate/theme.json`:

```json
{
    "name": "corporate",
    "display_name": "Corporate Theme",
    "version": "1.0.0",
    "author": "Your Name",
    "description": "Professional corporate theme",
    "colors": {
        "primary": "#1e40af",
        "secondary": "#7c3aed",
        "accent": "#db2777",
        "background": "#ffffff",
        "text": "#111827",
        "success": "#10b981",
        "warning": "#f59e0b",
        "error": "#ef4444",
        "info": "#3b82f6"
    },
    "fonts": {
        "sans": "Inter, system-ui, sans-serif",
        "mono": "JetBrains Mono, monospace"
    },
    "layout": {
        "container": "1280px",
        "spacing": "1rem"
    },
    "dark_mode": {
        "enabled": true,
        "default": "light"
    }
}
```

#### Step 3: Register Theme

Add to `config/canvastack-ui.php`:

```php
'theme' => [
    'paths' => [
        resource_path('themes'),
    ],
],
```

#### Step 4: Reload Themes

```bash
php artisan canvastack:theme:reload
```

---

## 🎨 Theme Structure

### Required Properties

Every theme MUST define:

```php
[
    'name' => 'theme-slug',              // Unique identifier (kebab-case)
    'display_name' => 'Theme Name',      // Human-readable name
    'version' => '1.0.0',                // Semantic version
    'author' => 'Author Name',           // Theme author
    'description' => 'Theme description', // Brief description
    'colors' => [...],                   // Color palette
    'fonts' => [...],                    // Font families
]
```

### Color Palette

#### Primary Colors (Required)

```php
'colors' => [
    'primary' => '#6366f1',      // Main brand color
    'secondary' => '#8b5cf6',    // Secondary brand color
    'accent' => '#a855f7',       // Accent color
    'background' => '#ffffff',   // Background color
    'text' => '#111827',         // Text color
]
```

#### Semantic Colors (Recommended)

```php
'colors' => [
    'success' => '#10b981',      // Success state
    'warning' => '#f59e0b',      // Warning state
    'error' => '#ef4444',        // Error state
    'info' => '#3b82f6',         // Info state
]
```

#### Extended Colors (Optional)

```php
'colors' => [
    'border' => '#e5e7eb',       // Border color
    'hover' => '#f3f4f6',        // Hover state
    'disabled' => '#9ca3af',     // Disabled state
    'link' => '#3b82f6',         // Link color
]
```

### Font Families

```php
'fonts' => [
    'sans' => 'Inter, system-ui, sans-serif',
    'mono' => 'JetBrains Mono, monospace',
    'serif' => 'Georgia, serif',  // Optional
]
```

### Layout Configuration

```php
'layout' => [
    'container' => '1280px',     // Max container width
    'spacing' => '1rem',         // Base spacing unit
    'radius' => '0.5rem',        // Border radius
]
```

### Dark Mode Configuration

```php
'dark_mode' => [
    'enabled' => true,           // Enable dark mode
    'default' => 'light',        // Default mode (light/dark)
    'colors' => [                // Optional: Override colors for dark mode
        'background' => '#111827',
        'text' => '#f9fafb',
    ],
]
```

---

## 🎯 Using Themes in Code

### Helper Functions

```php
// Get theme manager
$manager = theme();

// Get theme config value
$primary = theme('colors.primary');

// Get current theme
$theme = current_theme();

// Get color
$color = theme_color('primary');

// Get font
$font = theme_font('sans');

// Get compiled CSS
$css = theme_css();
```

### Blade Directives

```blade
{{-- Get config value --}}
<div style="color: @theme('colors.primary')">Text</div>

{{-- Get color --}}
<div style="background: @themeColor('primary')">Box</div>

{{-- Get font --}}
<div style="font-family: @themeFont('sans')">Text</div>

{{-- Output CSS --}}
<style>
    @themeCss
</style>

{{-- Inject complete theme --}}
@themeInject

{{-- Get theme info --}}
<p>Theme: @themeName</p>
<p>Version: @themeVersion</p>
```

### Facade

```php
use Canvastack\Canvastack\Facades\Theme;

// Get current theme
$theme = Theme::current();

// Get all themes
$themes = Theme::all();

// Check if theme exists
if (Theme::has('ocean')) {
    Theme::setCurrentTheme('ocean');
}

// Get theme colors
$colors = Theme::colors();

// Get compiled CSS
$css = Theme::getCompiledCss();

// Clear cache
Theme::clearCache();
```

---

## 🎨 CSS Variables

### Generated Variables

The system automatically generates CSS variables:

```css
:root {
    /* Colors */
    --cs-color-primary: #6366f1;
    --cs-color-secondary: #8b5cf6;
    --cs-color-accent: #a855f7;
    --cs-color-background: #ffffff;
    --cs-color-text: #111827;
    --cs-color-success: #10b981;
    --cs-color-warning: #f59e0b;
    --cs-color-error: #ef4444;
    --cs-color-info: #3b82f6;
    
    /* Fonts */
    --cs-font-sans: Inter, system-ui, sans-serif;
    --cs-font-mono: JetBrains Mono, monospace;
    
    /* Layout */
    --cs-layout-container: 1280px;
    --cs-layout-spacing: 1rem;
}
```

### Using CSS Variables

```css
/* In your CSS */
.button {
    background: var(--cs-color-primary);
    color: white;
    font-family: var(--cs-font-sans);
    padding: var(--cs-layout-spacing);
}

.container {
    max-width: var(--cs-layout-container);
    margin: 0 auto;
}
```

---

## 🌙 Dark Mode

### Automatic Dark Mode

The system automatically handles dark mode using Tailwind's `dark:` prefix:

```blade
<div class="bg-white dark:bg-gray-900 
            text-gray-900 dark:text-gray-100">
    Content
</div>
```

### Dark Mode Toggle

```blade
<button onclick="toggleDarkMode()" 
        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
    <i data-lucide="moon" class="w-5 h-5 dark:hidden"></i>
    <i data-lucide="sun" class="w-5 h-5 hidden dark:block"></i>
</button>

<script>
function toggleDarkMode() {
    document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', 
        document.documentElement.classList.contains('dark')
    );
}

// Initialize
if (localStorage.getItem('darkMode') === 'true' || 
    (!localStorage.getItem('darkMode') && 
     window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
}
</script>
```

### Dark Mode Colors

Override colors for dark mode in theme:

```php
'dark_mode' => [
    'enabled' => true,
    'default' => 'light',
    'colors' => [
        'background' => '#111827',
        'text' => '#f9fafb',
        'border' => '#374151',
    ],
]
```

---

## 🔄 Theme Switching

### Theme Switcher Component

```blade
<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="flex items-center gap-2">
        <span>{{ Theme::current()->getDisplayName() }}</span>
        <i data-lucide="chevron-down" class="w-4 h-4"></i>
    </button>
    
    <div x-show="open" @click.away="open = false" 
         class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-900 
                border border-gray-200 dark:border-gray-800 rounded-xl shadow-lg">
        @foreach(Theme::all() as $theme)
            <form method="POST" action="{{ route('theme.switch') }}">
                @csrf
                <input type="hidden" name="theme" value="{{ $theme->getName() }}">
                <button type="submit" 
                        class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-800">
                    {{ $theme->getDisplayName() }}
                </button>
            </form>
        @endforeach
    </div>
</div>
```

### Theme Switch Controller

```php
use Canvastack\Canvastack\Facades\Theme;
use Canvastack\Canvastack\Support\Integration\UserPreferences;

public function switchTheme(Request $request, UserPreferences $preferences)
{
    $themeName = $request->input('theme');
    
    // Validate theme exists
    if (!Theme::has($themeName)) {
        return back()->withErrors([
            'theme' => __('errors.theme_not_found')
        ]);
    }
    
    // Switch theme
    Theme::setCurrentTheme($themeName);
    
    // Save user preference
    $preferences->setTheme($themeName);
    
    return back()->with('success', __('ui.messages.theme_changed'));
}
```

---

## 🌍 Locale Integration

### Locale-Specific Fonts

The system automatically loads locale-specific fonts:

```php
use Canvastack\Canvastack\Support\Integration\ThemeLocaleIntegration;

$integration = app(ThemeLocaleIntegration::class);

// Get localized theme CSS (includes locale-specific fonts)
$css = $integration->getLocalizedThemeCss('default', 'ar');
```

### RTL Support

The system automatically handles RTL for RTL locales:

```blade
@php
    $integration = app('canvastack.theme.locale.integration');
    $attributes = $integration->getHtmlAttributes();
@endphp

<html lang="{{ $attributes['lang'] }}" 
      dir="{{ $attributes['dir'] }}" 
      class="{{ $attributes['class'] }}">
<head>
    @themeInject
</head>
<body class="{{ $integration->getBodyClasses() }}">
    {{-- Content automatically adapts to RTL --}}
</body>
</html>
```

---

## 🎨 Advanced Customization

### Custom CSS

Add custom CSS to your theme:

```php
'custom_css' => '
    .custom-button {
        background: linear-gradient(135deg, var(--cs-color-primary), var(--cs-color-secondary));
        border-radius: 1rem;
        padding: 0.75rem 1.5rem;
    }
',
```

### Custom JavaScript

Add custom JavaScript to your theme:

```php
'custom_js' => '
    document.addEventListener("DOMContentLoaded", function() {
        console.log("Theme loaded:", theme().name);
    });
',
```

### Theme Inheritance

Create child themes that inherit from parent themes:

```php
[
    'name' => 'corporate-dark',
    'display_name' => 'Corporate Dark',
    'parent' => 'corporate',  // Inherit from corporate theme
    'colors' => [
        'background' => '#111827',  // Override only specific colors
        'text' => '#f9fafb',
    ],
]
```

---

## 🔧 Tailwind Integration

### Dynamic Tailwind Config

The system generates Tailwind config from theme:

```javascript
// tailwind.config.js
const theme = require('./theme-config.json');

module.exports = {
    theme: {
        extend: {
            colors: {
                primary: theme.colors.primary,
                secondary: theme.colors.secondary,
                accent: theme.colors.accent,
            },
            fontFamily: {
                sans: theme.fonts.sans.split(','),
                mono: theme.fonts.mono.split(','),
            },
        },
    },
};
```

### JIT Compilation

Enable JIT for dynamic theme compilation:

```javascript
// tailwind.config.js
module.exports = {
    mode: 'jit',
    // ... rest of config
};
```

---

## 📊 Performance Optimization

### Theme Caching

The system automatically caches themes:

```php
// Clear theme cache
Theme::clearCache();

// Reload themes from filesystem
Theme::reload();
```

### Preloading

Preload theme in layout for better performance:

```blade
<!DOCTYPE html>
<html>
<head>
    {{-- Preload theme CSS --}}
    @themeInject
</head>
<body>
    @yield('content')
</body>
</html>
```

### Asset Optimization

Optimize theme assets:

```bash
# Build for production
npm run build

# This minifies CSS and JS
```

---

## 🧪 Testing Themes

### Unit Tests

```php
use Canvastack\Canvastack\Facades\Theme;

public function test_theme_colors_are_defined()
{
    $colors = Theme::colors();
    
    $this->assertArrayHasKey('primary', $colors);
    $this->assertArrayHasKey('secondary', $colors);
}

public function test_theme_supports_dark_mode()
{
    $this->assertTrue(Theme::supportsDarkMode());
}
```

### Feature Tests

```php
public function test_theme_css_is_injected()
{
    $response = $this->get('/dashboard');
    
    $response->assertSee('<style', false);
    $response->assertSee('--cs-color-primary', false);
}

public function test_theme_switching_works()
{
    $this->post('/theme/switch', ['theme' => 'ocean']);
    
    $this->assertEquals('ocean', Theme::current()->getName());
}
```

---

## 💡 Best Practices

### 1. Use Theme Variables

```blade
{{-- ✅ Good --}}
<div style="color: @themeColor('primary')">Text</div>

{{-- ❌ Bad --}}
<div style="color: #6366f1">Text</div>
```

### 2. Support Dark Mode

```blade
{{-- ✅ Good --}}
<div class="bg-white dark:bg-gray-900">Content</div>

{{-- ❌ Bad --}}
<div class="bg-white">Content</div>
```

### 3. Use Semantic Colors

```php
// ✅ Good
'success' => '#10b981',
'error' => '#ef4444',

// ❌ Bad
'green' => '#10b981',
'red' => '#ef4444',
```

### 4. Test All Themes

Test your application with all available themes to ensure compatibility.

### 5. Document Custom Themes

Document any custom themes you create for team reference.

---

## 📚 Resources

### Documentation

- [Theme API Reference](../api/theme-api.md)
- [Theme System Overview](../features/theming.md)
- [Theme + Locale Integration](../integration/theme-locale-integration.md)

### External Resources

- [Tailwind CSS Colors](https://tailwindcss.com/docs/customizing-colors)
- [Google Fonts](https://fonts.google.com)
- [Color Palette Generators](https://coolors.co)

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published
