# Midnight Theme

A sleek dark theme with deep blues and purples, perfect for night owls.

## Preview

![Midnight Theme Preview](preview.png)

## Features

- 🌙 Dark-first design
- 🎨 Deep blue and purple gradients
- 💼 Professional appearance
- 📱 Fully responsive
- ♿ WCAG AA compliant
- 👁️ Reduced eye strain

## Installation

The Midnight theme is included with CanvaStack by default.

### Activate via PHP

```php
theme()->setActive('midnight');
```

### Activate via Config

```php
// config/canvastack-ui.php
'default_theme' => 'midnight',
```

## Color Palette

### Dark Mode (Default)

- **Primary**: #818cf8 (Light Indigo)
- **Secondary**: #a78bfa (Light Purple)
- **Background**: #020617 (Deep Navy)
- **Surface**: #0f172a (Dark Slate)

### Light Mode

- **Primary**: #6366f1 (Indigo)
- **Secondary**: #8b5cf6 (Purple)
- **Accent**: #a855f7 (Fuchsia)

## Customization

### Override Colors

```php
// In your theme configuration
theme()->override('dark_mode.colors.background', '#000000');
```

### Extend Theme

Create a child theme:

```json
{
  "name": "midnight-blue",
  "parent": "midnight",
  "colors": {
    "primary": "#3b82f6"
  }
}
```

## Use Cases

Perfect for:
- Developer tools
- Code editors
- Admin dashboards
- Analytics platforms
- Professional applications
- Night-time usage
- Reduced eye strain environments

## Accessibility

This theme is designed with accessibility in mind:
- High contrast ratios
- WCAG AA compliant
- Reduced blue light for night usage
- Clear focus indicators

## Credits

Created by CanvaStack Team  
Version: 1.0.0  
License: MIT
