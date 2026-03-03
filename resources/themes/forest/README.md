# Forest Theme

A natural and calming theme inspired by forest greens.

## Preview

![Forest Theme Preview](preview.png)

## Features

- 🌲 Forest-inspired color palette
- 🎨 Green and teal gradients
- 🌙 Dark mode support
- 📱 Fully responsive
- ♿ WCAG AA compliant

## Installation

The Forest theme is included with CanvaStack by default.

### Activate via PHP

```php
theme()->setActive('forest');
```

### Activate via Config

```php
// config/canvastack-ui.php
'default_theme' => 'forest',
```

## Color Palette

### Light Mode

- **Primary**: #10b981 (Emerald)
- **Secondary**: #059669 (Dark Emerald)
- **Accent**: #14b8a6 (Teal)

### Dark Mode

- **Primary**: #4ade80 (Light Green)
- **Secondary**: #34d399 (Light Emerald)
- **Background**: #14532d (Deep Green)

## Customization

### Override Colors

```php
// In your theme configuration
theme()->override('colors.primary', '#16a34a');
```

### Extend Theme

Create a child theme:

```json
{
  "name": "forest-dark",
  "parent": "forest",
  "dark_mode": {
    "default": "dark"
  }
}
```

## Use Cases

Perfect for:
- Environmental organizations
- Eco-friendly businesses
- Health & wellness
- Organic products
- Sustainability platforms
- Nature-related content

## Credits

Created by CanvaStack Team  
Version: 1.0.0  
License: MIT
