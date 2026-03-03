# CanvaStack Theme System

## Overview

The CanvaStack theme system provides a flexible way to customize the appearance of your application. Themes are defined using JSON or PHP configuration files and can be easily switched at runtime.

## Theme Structure

Each theme is stored in its own directory under `resources/themes/` and must contain a `theme.json` or `theme.php` configuration file.

```
resources/themes/
├── default/
│   └── theme.json
├── ocean/
│   └── theme.json
└── sunset/
    └── theme.json
```

## Theme Configuration Format

### Required Fields

```json
{
  "name": "theme-name",
  "display_name": "Theme Display Name",
  "version": "1.0.0",
  "author": "Author Name",
  "description": "Theme description"
}
```

- **name**: Unique identifier in kebab-case (e.g., `my-custom-theme`)
- **display_name**: Human-readable name shown in UI
- **version**: Semantic version number
- **author**: Theme creator name
- **description**: Brief description of the theme

### Color Configuration

Define color palettes with shades (50-950):

```json
{
  "colors": {
    "primary": {
      "50": "#eef2ff",
      "100": "#e0e7ff",
      "500": "#6366f1",
      "900": "#312e81"
    },
    "secondary": { ... },
    "accent": { ... },
    "success": { ... },
    "warning": { ... },
    "error": { ... },
    "info": { ... },
    "gray": { ... }
  }
}
```

**Semantic Colors:**
- `primary`: Main brand color
- `secondary`: Secondary brand color
- `accent`: Accent/highlight color
- `success`: Success states (green)
- `warning`: Warning states (yellow/orange)
- `error`: Error states (red)
- `info`: Informational states (blue)
- `gray`: Neutral colors for text, borders, backgrounds

### Font Configuration

```json
{
  "fonts": {
    "sans": "Inter, system-ui, -apple-system, sans-serif",
    "mono": "JetBrains Mono, Fira Code, monospace"
  }
}
```

### Layout Configuration

```json
{
  "layout": {
    "sidebar_width": "16rem",
    "navbar_height": "4rem",
    "container_max_width": "80rem",
    "border_radius": {
      "sm": "0.375rem",
      "md": "0.5rem",
      "lg": "0.75rem",
      "xl": "1rem",
      "2xl": "1.5rem"
    }
  }
}
```

### Component Configuration

Customize individual component styles:

```json
{
  "components": {
    "button": {
      "border_radius": "xl",
      "padding": {
        "sm": "0.75rem 1rem",
        "md": "1rem 1.5rem",
        "lg": "1.25rem 2rem"
      }
    },
    "card": {
      "border_radius": "2xl",
      "shadow": "sm",
      "hover_shadow": "xl"
    },
    "input": {
      "border_radius": "xl",
      "height": {
        "sm": "2rem",
        "md": "2.5rem",
        "lg": "3rem"
      }
    }
  }
}
```

### Dark Mode Configuration

```json
{
  "dark_mode": {
    "enabled": true,
    "default": "light",
    "storage": "localStorage"
  }
}
```

- **enabled**: Whether dark mode is supported
- **default**: Default mode (`light` or `dark`)
- **storage**: Where to persist user preference (`localStorage` or `database`)

### Gradient Configuration

Define custom gradients:

```json
{
  "gradient": {
    "primary": "linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7)",
    "subtle": "linear-gradient(135deg, #eef2ff, #f5f3ff, #faf5ff)",
    "dark_subtle": "linear-gradient(135deg, #1e1b4b, #2e1065, #3b0764)"
  }
}
```

## Using Themes in Code

### Get Current Theme

```php
use Canvastack\Canvastack\Support\Facades\Theme;

// Get current theme
$theme = Theme::current();

// Get theme name
$name = $theme->getName();

// Get theme colors
$colors = $theme->getColors();
```

### Access Theme Configuration

```php
// Get specific config value
$primaryColor = Theme::config('colors.primary.500');

// Get colors
$colors = Theme::colors();

// Get fonts
$fonts = Theme::fonts();

// Get layout config
$layout = Theme::layout();
```

### Switch Themes

```php
// Set active theme
Theme::setCurrentTheme('ocean');

// Check if theme exists
if (Theme::has('sunset')) {
    Theme::setCurrentTheme('sunset');
}
```

### Generate CSS Variables

```php
// Get CSS variables for current theme
$variables = Theme::getCssVariables();

// Generate CSS
$css = Theme::generateCss();
```

## Using Themes in Blade

### Theme Helper

```blade
{{-- Get theme config --}}
{{ theme('colors.primary.500') }}

{{-- Get current theme name --}}
{{ theme()->current()->getName() }}

{{-- Check dark mode support --}}
@if(theme()->supportsDarkMode())
    <button onclick="toggleDark()">Toggle Dark Mode</button>
@endif
```

### Theme Directive

```blade
{{-- Get theme value --}}
@theme('colors.primary.500')

{{-- Conditional based on theme --}}
@if(theme()->current()->getName() === 'ocean')
    <div class="ocean-specific-content"></div>
@endif
```

## Creating Custom Themes

### Step 1: Create Theme Directory

```bash
mkdir resources/themes/my-theme
```

### Step 2: Create Configuration File

Create `resources/themes/my-theme/theme.json`:

```json
{
  "name": "my-theme",
  "display_name": "My Custom Theme",
  "version": "1.0.0",
  "author": "Your Name",
  "description": "My custom theme description",
  "colors": {
    "primary": {
      "500": "#your-color"
    }
  },
  "fonts": {
    "sans": "Your Font, sans-serif"
  },
  "dark_mode": {
    "enabled": true
  }
}
```

### Step 3: Load Theme

```php
// Themes are automatically loaded from resources/themes/
// Or manually load:
Theme::loadFromFile('path/to/theme.json');
```

### Step 4: Activate Theme

```php
Theme::setCurrentTheme('my-theme');
```

## PHP Configuration Format

Alternatively, use PHP arrays:

```php
<?php

return [
    'name' => 'my-theme',
    'display_name' => 'My Custom Theme',
    'version' => '1.0.0',
    'author' => 'Your Name',
    'description' => 'My custom theme',
    'colors' => [
        'primary' => [
            '500' => '#your-color',
        ],
    ],
    'fonts' => [
        'sans' => 'Your Font, sans-serif',
    ],
    'dark_mode' => [
        'enabled' => true,
    ],
];
```

## Theme Inheritance (Future Feature)

Child themes can extend parent themes:

```json
{
  "name": "my-child-theme",
  "extends": "default",
  "colors": {
    "primary": {
      "500": "#custom-color"
    }
  }
}
```

## Best Practices

1. **Use Semantic Colors**: Stick to the semantic color names (primary, secondary, etc.)
2. **Provide All Shades**: Include shades 50-900 for each color
3. **Test Dark Mode**: If enabled, test all components in dark mode
4. **Consistent Spacing**: Use consistent spacing values across components
5. **Accessible Colors**: Ensure color contrast meets WCAG AA standards
6. **Version Your Themes**: Use semantic versioning for theme updates

## Available Themes

- **default**: Modern gradient theme (indigo, purple, fuchsia)
- **ocean**: Cool ocean-inspired theme (cyan, teal, blue)
- **sunset**: Warm sunset theme (orange, red, pink)

## Configuration

Set the active theme in `config/canvastack-ui.php`:

```php
'theme' => [
    'active' => 'default',
    'cache_ttl' => 3600,
],
```

## Caching

Themes are cached for performance. Clear cache after changes:

```php
Theme::clearCache();
Theme::reload();
```

Or via Artisan:

```bash
php artisan cache:clear
```

## API Reference

See the [ThemeInterface](../../src/Contracts/ThemeInterface.php) for the complete API.
