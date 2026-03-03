# Ocean Theme

A refreshing ocean-inspired theme with blue and teal gradients.

## Preview

![Ocean Theme Preview](preview.png)

## Features

- 🌊 Ocean-inspired color palette
- 🎨 Blue and teal gradients
- 🌙 Dark mode support
- 📱 Fully responsive
- ♿ WCAG AA compliant

## Installation

The Ocean theme is included with CanvaStack by default.

### Activate via PHP

```php
theme()->setActive('ocean');
```

### Activate via Config

```php
// config/canvastack-ui.php
'default_theme' => 'ocean',
```

## Color Palette

### Light Mode

- **Primary**: #0ea5e9 (Sky Blue)
- **Secondary**: #06b6d4 (Cyan)
- **Accent**: #14b8a6 (Teal)

### Dark Mode

- **Primary**: #22d3ee (Light Cyan)
- **Secondary**: #2dd4bf (Light Teal)
- **Background**: #0c4a6e (Deep Blue)

## Customization

### Override Colors

```php
// In your theme configuration
theme()->override('colors.primary', '#0284c7');
```

### Extend Theme

Create a child theme:

```json
{
  "name": "ocean-dark",
  "parent": "ocean",
  "dark_mode": {
    "default": "dark"
  }
}
```

## Use Cases

Perfect for:
- SaaS applications
- Tech startups
- Data dashboards
- Analytics platforms
- Marine/water-related businesses

## Credits

Created by CanvaStack Team  
Version: 1.0.0  
License: MIT
