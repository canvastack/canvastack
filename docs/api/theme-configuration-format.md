# Theme Configuration Format

Complete reference for the CanvaStack theme.json configuration format.

## 📦 Location

- **Theme Configuration**: `resources/themes/{theme-name}/theme.json`
- **System Configuration**: `config/canvastack-ui.php`
- **Theme Manager**: `src/Support/Theme/ThemeManager.php`

## 🎯 Overview

The theme.json file defines all visual aspects of a CanvaStack theme including colors, typography, spacing, and dark mode settings. This document provides a complete reference for all available configuration options.

## 📖 Basic Structure

### Minimal Configuration

```json
{
  "name": "my-theme",
  "display_name": "My Theme",
  "version": "1.0.0",
  "author": "Author Name",
  "description": "Theme description"
}
```

### Complete Configuration

```json
{
  "name": "my-theme",
  "display_name": "My Theme",
  "version": "1.0.0",
  "author": "Author Name",
  "description": "A beautiful custom theme",
  "parent": null,
  "preview_image": "preview.png",
  "tags": ["modern", "colorful", "gradient"],
  
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
      "200": "#e5e7eb",
      "300": "#d1d5db",
      "400": "#9ca3af",
      "500": "#6b7280",
      "600": "#4b5563",
      "700": "#374151",
      "800": "#1f2937",
      "900": "#111827",
      "950": "#030712"
    }
  },
  
  "gradients": {
    "primary": "linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7)",
    "hero": "linear-gradient(to right, #667eea, #764ba2)",
    "sunset": "linear-gradient(to bottom, #ff6b6b, #feca57)"
  },
  
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
      "4xl": "2.25rem",
      "5xl": "3rem",
      "6xl": "3.75rem",
      "7xl": "4.5rem"
    },
    "font_weights": {
      "light": 300,
      "normal": 400,
      "medium": 500,
      "semibold": 600,
      "bold": 700,
      "extrabold": 800,
      "black": 900
    },
    "line_heights": {
      "tight": 1.25,
      "normal": 1.5,
      "relaxed": 1.75,
      "loose": 2
    }
  },
  
  "spacing": {
    "container_padding": "1rem",
    "section_spacing": "4rem",
    "card_padding": "1.5rem",
    "button_padding_x": "1rem",
    "button_padding_y": "0.5rem"
  },
  
  "border_radius": {
    "none": "0",
    "sm": "0.375rem",
    "md": "0.5rem",
    "lg": "0.75rem",
    "xl": "1rem",
    "2xl": "1.5rem",
    "3xl": "2rem",
    "full": "9999px"
  },
  
  "shadows": {
    "sm": "0 1px 2px 0 rgb(0 0 0 / 0.05)",
    "md": "0 4px 6px -1px rgb(0 0 0 / 0.1)",
    "lg": "0 10px 15px -3px rgb(0 0 0 / 0.1)",
    "xl": "0 20px 25px -5px rgb(0 0 0 / 0.1)",
    "2xl": "0 25px 50px -12px rgb(0 0 0 / 0.25)"
  },
  
  "dark_mode": {
    "enabled": true,
    "default": "light",
    "storage": "localStorage",
    "colors": {
      "primary": "#818cf8",
      "secondary": "#a78bfa",
      "accent": "#c084fc",
      "background": "#0f172a",
      "surface": "#1e293b",
      "text": "#f1f5f9",
      "border": "#334155"
    }
  },
  
  "breakpoints": {
    "sm": "640px",
    "md": "768px",
    "lg": "1024px",
    "xl": "1280px",
    "2xl": "1536px"
  },
  
  "animations": {
    "duration": {
      "fast": "150ms",
      "normal": "300ms",
      "slow": "500ms"
    },
    "easing": {
      "ease": "ease",
      "ease_in": "ease-in",
      "ease_out": "ease-out",
      "ease_in_out": "ease-in-out"
    }
  }
}
```

## 🔧 Configuration Properties

### Metadata Properties

#### name (required)

**Type**: `string`  
**Format**: kebab-case  
**Description**: Unique theme identifier

```json
{
  "name": "my-custom-theme"
}
```

**Rules**:
- Must be unique across all themes
- Use kebab-case (lowercase with hyphens)
- No spaces or special characters
- Maximum 50 characters

#### display_name (required)

**Type**: `string`  
**Description**: Human-readable theme name

```json
{
  "display_name": "My Custom Theme"
}
```

**Rules**:
- Can contain spaces and special characters
- Maximum 100 characters
- Used in theme selector UI

#### version (required)

**Type**: `string`  
**Format**: Semantic versioning (semver)  
**Description**: Theme version number

```json
{
  "version": "1.0.0"
}
```

**Rules**:
- Must follow semver format: MAJOR.MINOR.PATCH
- Example: "1.0.0", "2.1.3", "0.9.0-beta"

#### author (required)

**Type**: `string`  
**Description**: Theme author name

```json
{
  "author": "John Doe"
}
```

**Rules**:
- Can be individual or organization name
- Maximum 100 characters

#### description (required)

**Type**: `string`  
**Description**: Brief theme description

```json
{
  "description": "A modern, colorful theme with gradient accents"
}
```

**Rules**:
- Maximum 500 characters
- Should describe theme's visual style

#### parent (optional)

**Type**: `string | null`  
**Description**: Parent theme name for inheritance

```json
{
  "parent": "base-theme"
}
```

**Rules**:
- Must reference an existing theme
- Enables theme inheritance
- Child theme inherits all parent properties
- Child can override specific properties

#### preview_image (optional)

**Type**: `string`  
**Description**: Path to theme preview image

```json
{
  "preview_image": "preview.png"
}
```

**Rules**:
- Relative to theme directory
- Recommended size: 800x600px
- Supported formats: PNG, JPG, WebP

#### tags (optional)

**Type**: `array<string>`  
**Description**: Theme category tags

```json
{
  "tags": ["modern", "colorful", "gradient", "dark-mode"]
}
```

**Rules**:
- Used for theme filtering
- Maximum 10 tags
- Each tag maximum 20 characters

### Color Properties

#### colors (optional)

**Type**: `object`  
**Description**: Theme color palette

```json
{
  "colors": {
    "primary": "#6366f1",
    "secondary": "#8b5cf6",
    "accent": "#a855f7",
    "success": "#10b981",
    "warning": "#f59e0b",
    "error": "#ef4444",
    "info": "#3b82f6"
  }
}
```

**Semantic Colors**:

| Color | Purpose | Example |
|-------|---------|---------|
| primary | Main brand color | Buttons, links |
| secondary | Secondary brand color | Accents, highlights |
| accent | Accent color | Call-to-action elements |
| success | Success states | Success messages, checkmarks |
| warning | Warning states | Warning messages, alerts |
| error | Error states | Error messages, validation |
| info | Informational states | Info messages, tooltips |

**Neutral Colors**:

```json
{
  "colors": {
    "neutral": {
      "50": "#f9fafb",
      "100": "#f3f4f6",
      "200": "#e5e7eb",
      "300": "#d1d5db",
      "400": "#9ca3af",
      "500": "#6b7280",
      "600": "#4b5563",
      "700": "#374151",
      "800": "#1f2937",
      "900": "#111827",
      "950": "#030712"
    }
  }
}
```

**Color Format**:
- Hex: `#6366f1`
- RGB: `rgb(99, 102, 241)`
- RGBA: `rgba(99, 102, 241, 0.5)`
- HSL: `hsl(239, 84%, 67%)`

#### gradients (optional)

**Type**: `object`  
**Description**: Gradient definitions

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

**Gradient Types**:
- Linear: `linear-gradient(direction, color1, color2, ...)`
- Radial: `radial-gradient(shape, color1, color2, ...)`
- Conic: `conic-gradient(from angle, color1, color2, ...)`

### Typography Properties

#### typography (optional)

**Type**: `object`  
**Description**: Font and text styling configuration

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
      "xl": "1.25rem"
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

**Font Family**:
- Use Google Fonts or system fonts
- Provide fallback fonts
- Example: `"Inter, system-ui, sans-serif"`

**Font Sizes**:
- Use rem units for scalability
- Follow consistent scale (1.125x, 1.25x, 1.5x, etc.)

**Font Weights**:
- Standard values: 100, 200, 300, 400, 500, 600, 700, 800, 900
- Ensure font supports specified weights

### Spacing Properties

#### spacing (optional)

**Type**: `object`  
**Description**: Spacing and layout values

```json
{
  "spacing": {
    "container_padding": "1rem",
    "section_spacing": "4rem",
    "card_padding": "1.5rem",
    "button_padding_x": "1rem",
    "button_padding_y": "0.5rem",
    "input_padding_x": "0.75rem",
    "input_padding_y": "0.5rem"
  }
}
```

**Common Spacing Values**:
- Use rem or em for scalability
- Follow 4px or 8px base unit
- Maintain consistent spacing scale

### Border Radius Properties

#### border_radius (optional)

**Type**: `object`  
**Description**: Border radius values

```json
{
  "border_radius": {
    "none": "0",
    "sm": "0.375rem",
    "md": "0.5rem",
    "lg": "0.75rem",
    "xl": "1rem",
    "2xl": "1.5rem",
    "full": "9999px"
  }
}
```

**Usage**:
- `none`: No rounding
- `sm` to `2xl`: Increasing roundness
- `full`: Perfect circle/pill shape

### Shadow Properties

#### shadows (optional)

**Type**: `object`  
**Description**: Box shadow definitions

```json
{
  "shadows": {
    "sm": "0 1px 2px 0 rgb(0 0 0 / 0.05)",
    "md": "0 4px 6px -1px rgb(0 0 0 / 0.1)",
    "lg": "0 10px 15px -3px rgb(0 0 0 / 0.1)",
    "xl": "0 20px 25px -5px rgb(0 0 0 / 0.1)"
  }
}
```

**Shadow Format**:
```
offset-x offset-y blur-radius spread-radius color
```

### Dark Mode Properties

#### dark_mode (optional)

**Type**: `object`  
**Description**: Dark mode configuration

```json
{
  "dark_mode": {
    "enabled": true,
    "default": "light",
    "storage": "localStorage",
    "colors": {
      "primary": "#818cf8",
      "secondary": "#a78bfa",
      "background": "#0f172a",
      "surface": "#1e293b",
      "text": "#f1f5f9",
      "border": "#334155"
    }
  }
}
```

**Properties**:

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| enabled | boolean | true | Enable dark mode |
| default | string | "light" | Default mode ("light" or "dark") |
| storage | string | "localStorage" | Storage method |
| colors | object | {} | Dark mode color overrides |

**Dark Mode Colors**:
- Override any color from main palette
- Typically lighter shades for dark backgrounds
- Ensure sufficient contrast

### Breakpoint Properties

#### breakpoints (optional)

**Type**: `object`  
**Description**: Responsive breakpoints

```json
{
  "breakpoints": {
    "sm": "640px",
    "md": "768px",
    "lg": "1024px",
    "xl": "1280px",
    "2xl": "1536px"
  }
}
```

**Standard Breakpoints**:
- `sm`: Mobile landscape (640px)
- `md`: Tablet (768px)
- `lg`: Desktop (1024px)
- `xl`: Large desktop (1280px)
- `2xl`: Extra large (1536px)

### Animation Properties

#### animations (optional)

**Type**: `object`  
**Description**: Animation timing configuration

```json
{
  "animations": {
    "duration": {
      "fast": "150ms",
      "normal": "300ms",
      "slow": "500ms"
    },
    "easing": {
      "ease": "ease",
      "ease_in": "ease-in",
      "ease_out": "ease-out",
      "ease_in_out": "ease-in-out",
      "bounce": "cubic-bezier(0.68, -0.55, 0.265, 1.55)"
    }
  }
}
```

**Duration Values**:
- `fast`: Quick transitions (150ms)
- `normal`: Standard transitions (300ms)
- `slow`: Slow transitions (500ms)

**Easing Functions**:
- `ease`: Default easing
- `ease_in`: Accelerate
- `ease_out`: Decelerate
- `ease_in_out`: Accelerate then decelerate
- Custom: Use cubic-bezier values

## 📝 Examples

### Example 1: Minimal Theme

```json
{
  "name": "simple-theme",
  "display_name": "Simple Theme",
  "version": "1.0.0",
  "author": "John Doe",
  "description": "A simple, clean theme",
  "colors": {
    "primary": "#3b82f6",
    "secondary": "#8b5cf6"
  }
}
```

### Example 2: Brand Theme

```json
{
  "name": "company-brand",
  "display_name": "Company Brand",
  "version": "1.0.0",
  "author": "Company Inc",
  "description": "Official company brand theme",
  "colors": {
    "primary": "#FF6B00",
    "secondary": "#00A3FF",
    "accent": "#FFD700"
  },
  "typography": {
    "font_family": "Montserrat",
    "font_family_mono": "Roboto Mono"
  },
  "gradients": {
    "primary": "linear-gradient(135deg, #FF6B00, #FF8C00)"
  }
}
```

### Example 3: Dark Theme

```json
{
  "name": "dark-pro",
  "display_name": "Dark Pro",
  "version": "1.0.0",
  "author": "Theme Studio",
  "description": "Professional dark theme",
  "colors": {
    "primary": "#818cf8",
    "secondary": "#a78bfa",
    "background": "#0f172a",
    "surface": "#1e293b",
    "text": "#f1f5f9"
  },
  "dark_mode": {
    "enabled": true,
    "default": "dark"
  }
}
```

### Example 4: Child Theme

```json
{
  "name": "gradient-dark",
  "display_name": "Gradient Dark",
  "parent": "gradient-theme",
  "version": "1.0.0",
  "author": "Theme Studio",
  "description": "Dark variant of Gradient theme",
  "dark_mode": {
    "enabled": true,
    "default": "dark",
    "colors": {
      "primary": "#818cf8",
      "background": "#0f172a"
    }
  }
}
```

## 💡 Best Practices

### 1. Use Semantic Naming

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

### 2. Provide Complete Color Scales

```json
{
  "colors": {
    "neutral": {
      "50": "#f9fafb",
      "100": "#f3f4f6",
      "500": "#6b7280",
      "900": "#111827"
    }
  }
}
```

### 3. Include Dark Mode

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

### 4. Use Consistent Spacing Scale

```json
{
  "spacing": {
    "xs": "0.5rem",
    "sm": "1rem",
    "md": "1.5rem",
    "lg": "2rem",
    "xl": "3rem"
  }
}
```

### 5. Document Custom Properties

Add comments in README.md explaining custom properties and their usage.

## 🔍 Validation

### Required Fields

The following fields are required:
- `name`
- `display_name`
- `version`
- `author`
- `description`

### Validation Rules

```php
// Theme validation
$rules = [
    'name' => 'required|string|max:50|regex:/^[a-z0-9-]+$/',
    'display_name' => 'required|string|max:100',
    'version' => 'required|string|regex:/^\d+\.\d+\.\d+/',
    'author' => 'required|string|max:100',
    'description' => 'required|string|max:500',
    'parent' => 'nullable|string|exists:themes,name',
    'colors' => 'nullable|array',
    'colors.*' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
];
```

## 🔗 Related Documentation

- [Theme Development Guide](../guides/theme-development.md)
- [Tailwind CSS Integration](../frontend/tailwind-css.md)
- [Dark Mode Guide](../frontend/dark-mode.md)
- [CSS Variables](../frontend/css-variables.md)

## 📚 Resources

- [JSON Schema](https://json-schema.org/)
- [Semantic Versioning](https://semver.org/)
- [CSS Color Values](https://developer.mozilla.org/en-US/docs/Web/CSS/color_value)
- [CSS Gradients](https://developer.mozilla.org/en-US/docs/Web/CSS/gradient)

---

**Last Updated**: 2026-02-26  
**Version**: 1.0.0  
**Status**: Published  
**Author**: CanvaStack Team
