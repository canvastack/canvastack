# Theme Development Guide

A comprehensive guide to creating custom themes for CanvaStack using the Theme Engine System.

## 📦 Location

- **Theme Files**: `resources/themes/{theme-name}/`
- **Theme Manager**: `src/Support/Theme/ThemeManager.php`
- **Theme Configuration**: `config/canvastack-ui.php`
- **Theme Service Provider**: `src/Support/Theme/ThemeServiceProvider.php`

## 🎯 Overview

CanvaStack's Theme Engine allows you to create fully customized themes with:
- Custom color palettes and gradients
- Typography customization
- Dark mode variants
- CSS variable system
- Tailwind integration
- Theme inheritance

## 📖 Quick Start

### Creating Your First Theme

1. **Create theme directory**:
```bash
mkdir -p resources/themes/my-theme
```

2. **Create theme configuration** (`resources/themes/my-theme/theme.json`):
```json
{
  "name": "my-theme",
  "display_name": "My Custom Theme",
  "version": "1.0.0",
  "author": "Your Name",
  "description": "A beautiful custom theme",
  "colors": {
    "primary": "#3b82f6",
    "secondary": "#8b5cf6",
    "accent": "#ec4899"
  }
}
```

3. **Register theme** in `config/canvastack-ui.php`:
```php
'themes' => [
    'my-theme' => [
        'name' => 'My Custom Theme',
        'path' => 'resources/themes/my-theme',
        'enabled' => true,
    ],
],
```

4. **Activate theme**:
```php
theme()->setActive('my-theme');
```

## 🔧 Theme Configuration Format

### Complete theme.json Structure

```json
{
  "name": "theme-name",
  "display_name": "Theme Display Name",
  "version": "1.0.0",
  "author": "Author Name",
  "description": "Theme description",
  "parent": null,
  
  "colors": {
    "primary": "#6366f1",
    "secondary": "#8b5cf6",
    "accent": "#a855f7",
    "success": "#10b981",
    "warning": "#f59e0b",
    "error": "#ef4444",
    "info": "#3b82f6"
  },
  
  "gradients": {
    "primary": "linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7)",
    "hero": "linear-gradient(to right, #667eea, #764ba2)"
  },
  
  "typography": {
    "font_family": "Inter",
    "font_family_mono": "JetBrains Mono",
    "font_sizes": {
      "xs": "0.75rem",
      "sm": "0.875rem",
      "base": "1rem",
      "lg": "1.125rem",
      "xl": "1.25rem"
    }
  },
  
  "spacing": {
    "container_padding": "1rem",
    "section_spacing": "4rem"
  },
  
  "border_radius": {
    "sm": "0.375rem",
    "md": "0.5rem",
    "lg": "0.75rem",
    "xl": "1rem"
  },
  
  "dark_mode": {
    "enabled": true,
    "colors": {
      "primary": "#818cf8",
      "background": "#0f172a",
      "surface": "#1e293b"
    }
  }
}
```

## 📝 Theme Properties

### Required Properties

| Property | Type | Description |
|----------|------|-------------|
| name | string | Unique theme identifier (kebab-case) |
| display_name | string | Human-readable theme name |
| version | string | Theme version (semver) |
| author | string | Theme author name |
| description | string | Brief theme description |

### Optional Properties

| Property | Type | Description |
|----------|------|-------------|
| parent | string | Parent theme name for inheritance |
| colors | object | Color palette |
| gradients | object | Gradient definitions |
| typography | object | Font settings |
| spacing | object | Spacing values |
| border_radius | object | Border radius values |
| dark_mode | object | Dark mode configuration |

## 🎨 Color System

### Defining Colors

```json
{
  "colors": {
    "primary": "#6366f1",
    "secondary": "#8b5cf6",
    "accent": "#a855f7",
    "success": "#10b981",
    "warning": "#f59e0b",
    "error": "#ef4444",
    "info": "#3b82f6",
    "neutral": {
      "50": "#f9fafb",
      "100": "#f3f4f6",
      "500": "#6b7280",
      "900": "#111827"
    }
  }
}
```

### Using Colors in CSS

Colors are automatically converted to CSS variables:

```css
/* Access theme colors */
background-color: var(--color-primary);
color: var(--color-secondary);
border-color: var(--color-accent);

/* Access neutral shades */
background-color: var(--color-neutral-50);
color: var(--color-neutral-900);
```

### Using Colors in Blade

```blade
<div style="background-color: {{ theme('colors.primary') }}">
  Content
</div>
```

## 🌈 Gradient System

### Defining Gradients

```json
{
  "gradients": {
    "primary": "linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7)",
    "hero": "linear-gradient(to right, #667eea, #764ba2)",
    "sunset": "linear-gradient(to bottom, #ff6b6b, #feca57)",
    "ocean": "linear-gradient(120deg, #89f7fe, #66a6ff)"
  }
}
```

### Using Gradients

```css
/* CSS */
background: var(--gradient-primary);
background-image: var(--gradient-hero);
```

```blade
{{-- Blade --}}
<div class="gradient-bg">Content</div>

<div style="background: {{ theme('gradients.primary') }}">
  Content
</div>
```

## 🔤 Typography

### Font Configuration

```json
{
  "typography": {
    "font_family": "Inter",
    "font_family_mono": "JetBrains Mono",
    "font_sizes": {
      "xs": "0.75rem",
      "sm": "0.875rem",
      "base": "1rem",
      "lg": "1.125rem",
      "xl": "1.25rem",
      "2xl": "1.5rem",
      "3xl": "1.875rem",
      "4xl": "2.25rem"
    },
    "font_weights": {
      "light": 300,
      "normal": 400,
      "medium": 500,
      "semibold": 600,
      "bold": 700
    },
    "line_heights": {
      "tight": 1.25,
      "normal": 1.5,
      "relaxed": 1.75
    }
  }
}
```

### Using Typography

```css
/* CSS */
font-family: var(--font-family);
font-size: var(--font-size-lg);
font-weight: var(--font-weight-semibold);
line-height: var(--line-height-normal);
```

## 🌙 Dark Mode

### Dark Mode Configuration

```json
{
  "dark_mode": {
    "enabled": true,
    "default": "light",
    "colors": {
      "primary": "#818cf8",
      "secondary": "#a78bfa",
      "background": "#0f172a",
      "surface": "#1e293b",
      "text": "#f1f5f9"
    }
  }
}
```

### Dark Mode CSS Variables

```css
/* Light mode (default) */
:root {
  --color-background: #ffffff;
  --color-text: #111827;
}

/* Dark mode */
.dark {
  --color-background: #0f172a;
  --color-text: #f1f5f9;
}
```

### Using Dark Mode in Components

```blade
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
  Content adapts to theme
</div>
```

## 🔗 Theme Inheritance

### Creating Child Themes

```json
{
  "name": "my-theme-dark",
  "display_name": "My Theme (Dark)",
  "parent": "my-theme",
  "version": "1.0.0",
  "author": "Your Name",
  "description": "Dark variant of My Theme",
  
  "colors": {
    "primary": "#818cf8",
    "background": "#0f172a"
  }
}
```

Child themes inherit all properties from parent and can override specific values.

## 📦 Theme Structure

### Recommended Directory Structure

```
resources/themes/my-theme/
├── theme.json              # Theme configuration
├── assets/
│   ├── css/
│   │   ├── theme.css      # Custom CSS
│   │   └── components.css # Component styles
│   ├── js/
│   │   └── theme.js       # Custom JavaScript
│   └── images/
│       ├── logo.svg       # Theme logo
│       └── preview.png    # Theme preview
├── views/
│   ├── layouts/
│   │   └── custom.blade.php
│   └── components/
│       └── custom-button.blade.php
└── README.md              # Theme documentation
```

## 🎮 Theme API

### PHP API

```php
// Get theme manager
$themeManager = app('canvastack.theme');

// Get active theme
$theme = theme()->getActive();

// Set active theme
theme()->setActive('my-theme');

// Get theme property
$primaryColor = theme('colors.primary');
$fontFamily = theme('typography.font_family');

// Check if theme exists
if (theme()->exists('my-theme')) {
    // Theme exists
}

// Get all themes
$themes = theme()->all();

// Get theme metadata
$name = theme()->getName();
$version = theme()->getVersion();
$author = theme()->getAuthor();
```

### Blade Directives

```blade
{{-- Get theme value --}}
@theme('colors.primary')

{{-- Check active theme --}}
@if(theme()->isActive('my-theme'))
    <p>My Theme is active</p>
@endif

{{-- Render theme CSS variables --}}
@themeVars

{{-- Include theme asset --}}
<link rel="stylesheet" href="{{ theme()->asset('css/theme.css') }}">
```

### JavaScript API

```javascript
// Get theme value
const primaryColor = window.theme.get('colors.primary');

// Set theme
window.theme.setActive('my-theme');

// Get active theme
const activeTheme = window.theme.getActive();

// Listen for theme changes
window.addEventListener('theme:changed', (event) => {
    console.log('Theme changed to:', event.detail.theme);
});
```

## 💡 Best Practices

### 1. Use Semantic Color Names

```json
{
  "colors": {
    "primary": "#6366f1",
    "success": "#10b981",
    "warning": "#f59e0b",
    "error": "#ef4444"
  }
}
```

### 2. Provide Dark Mode Variants

Always include dark mode colors for better user experience:

```json
{
  "dark_mode": {
    "enabled": true,
    "colors": {
      "primary": "#818cf8",
      "background": "#0f172a"
    }
  }
}
```

### 3. Use CSS Variables

Leverage CSS variables for dynamic theming:

```css
.button {
  background-color: var(--color-primary);
  color: var(--color-text);
}
```

### 4. Test Accessibility

Ensure color contrast meets WCAG AA standards:
- Normal text: 4.5:1 contrast ratio
- Large text: 3:1 contrast ratio

### 5. Document Your Theme

Include a README.md with:
- Theme description
- Installation instructions
- Customization options
- Screenshots

## 🎭 Common Patterns

### Pattern 1: Brand-Specific Theme

```json
{
  "name": "company-brand",
  "display_name": "Company Brand",
  "colors": {
    "primary": "#FF6B00",
    "secondary": "#00A3FF",
    "accent": "#FFD700"
  },
  "typography": {
    "font_family": "Montserrat"
  }
}
```

### Pattern 2: Seasonal Theme

```json
{
  "name": "winter-theme",
  "display_name": "Winter Theme",
  "colors": {
    "primary": "#4A90E2",
    "secondary": "#7ED3F7",
    "accent": "#FFFFFF"
  },
  "gradients": {
    "hero": "linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
  }
}
```

### Pattern 3: High Contrast Theme

```json
{
  "name": "high-contrast",
  "display_name": "High Contrast",
  "colors": {
    "primary": "#000000",
    "secondary": "#FFFFFF",
    "background": "#FFFFFF",
    "text": "#000000"
  },
  "dark_mode": {
    "enabled": true,
    "colors": {
      "primary": "#FFFFFF",
      "secondary": "#000000",
      "background": "#000000",
      "text": "#FFFFFF"
    }
  }
}
```

## 🧪 Testing Your Theme

### Visual Testing

1. **Test all components** with your theme
2. **Check dark mode** appearance
3. **Verify responsive** design
4. **Test accessibility** (color contrast, focus states)

### Automated Testing

```php
public function test_theme_loads_correctly()
{
    $theme = theme()->load('my-theme');
    
    $this->assertEquals('my-theme', $theme->getName());
    $this->assertEquals('#6366f1', $theme->get('colors.primary'));
}

public function test_theme_css_variables_generated()
{
    theme()->setActive('my-theme');
    
    $css = theme()->generateCssVariables();
    
    $this->assertStringContainsString('--color-primary: #6366f1', $css);
}
```

## 🔍 Troubleshooting

### Theme Not Loading

**Problem**: Theme doesn't appear in theme selector

**Solution**:
1. Check theme.json syntax
2. Verify theme is registered in config
3. Clear theme cache: `php artisan theme:clear-cache`

### Colors Not Applying

**Problem**: Theme colors not showing

**Solution**:
1. Check CSS variable names
2. Verify theme is active
3. Clear browser cache
4. Rebuild assets: `npm run build`

### Dark Mode Not Working

**Problem**: Dark mode colors not applying

**Solution**:
1. Ensure dark_mode.enabled is true
2. Check dark mode toggle functionality
3. Verify dark: prefix in Tailwind classes

## 🔗 Related Documentation

- [Theme Configuration Format](theme-configuration-format.md)
- [Tailwind CSS Integration](../frontend/tailwind-css.md)
- [Dark Mode Guide](../frontend/dark-mode.md)
- [Custom Components](creating-components.md)

## 📚 Resources

- [Tailwind CSS Documentation](https://tailwindcss.com)
- [DaisyUI Themes](https://daisyui.com/docs/themes)
- [CSS Variables Guide](https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties)
- [Color Contrast Checker](https://webaim.org/resources/contrastchecker)

---

**Last Updated**: 2026-02-26  
**Version**: 1.0.0  
**Status**: Published  
**Author**: CanvaStack Team
