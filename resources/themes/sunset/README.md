# Sunset Theme

A warm and vibrant theme inspired by sunset colors with orange and pink gradients.

## Preview

![Sunset Theme Preview](preview.png)

## Features

- 🌅 Sunset-inspired color palette
- 🎨 Orange, pink, and purple gradients
- 🌙 Dark mode support
- 📱 Fully responsive
- ♿ WCAG AA compliant

## Installation

The Sunset theme is included with CanvaStack by default.

### Activate via PHP

```php
theme()->setActive('sunset');
```

### Activate via Config

```php
// config/canvastack-ui.php
'default_theme' => 'sunset',
```

## Color Palette

### Light Mode

- **Primary**: #f97316 (Orange)
- **Secondary**: #ec4899 (Pink)
- **Accent**: #a855f7 (Purple)

### Dark Mode

- **Primary**: #fb923c (Light Orange)
- **Secondary**: #f472b6 (Light Pink)
- **Background**: #7c2d12 (Deep Orange)

## Customization

### Override Colors

```php
// In your theme configuration
theme()->override('colors.primary', '#ea580c');
```

### Extend Theme

Create a child theme:

```json
{
  "name": "sunset-warm",
  "parent": "sunset",
  "colors": {
    "accent": "#dc2626"
  }
}
```

## Use Cases

Perfect for:
- Creative agencies
- Photography portfolios
- Food & beverage businesses
- Event management
- Lifestyle brands
- Fashion websites

## Credits

Created by CanvaStack Team  
Version: 1.0.0  
License: MIT
