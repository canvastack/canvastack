# Corporate Theme

A professional corporate theme with neutral colors and subtle accents, perfect for business applications.

## Preview

![Corporate Theme Preview](preview.png)

## Features

- 💼 Professional appearance
- 🎨 Neutral color palette
- 🌙 Dark mode support
- 📱 Fully responsive
- ♿ WCAG AA compliant
- 📊 Business-focused design

## Installation

The Corporate theme is included with CanvaStack by default.

### Activate via PHP

```php
theme()->setActive('corporate');
```

### Activate via Config

```php
// config/canvastack-ui.php
'default_theme' => 'corporate',
```

## Color Palette

### Light Mode

- **Primary**: #2563eb (Blue)
- **Secondary**: #475569 (Slate)
- **Accent**: #0ea5e9 (Sky Blue)

### Dark Mode

- **Primary**: #3b82f6 (Light Blue)
- **Secondary**: #64748b (Light Slate)
- **Background**: #0f172a (Dark Slate)

## Design Philosophy

The Corporate theme emphasizes:
- **Professionalism**: Clean, minimal design
- **Readability**: High contrast, clear typography
- **Trust**: Conservative color choices
- **Efficiency**: Reduced visual noise

## Customization

### Override Colors

```php
// In your theme configuration
theme()->override('colors.primary', '#1e40af');
```

### Extend Theme

Create a child theme:

```json
{
  "name": "corporate-blue",
  "parent": "corporate",
  "colors": {
    "primary": "#1e3a8a"
  }
}
```

## Use Cases

Perfect for:
- Enterprise applications
- Financial services
- Legal firms
- Consulting businesses
- B2B platforms
- Corporate intranets
- Professional dashboards
- Business intelligence tools

## Typography

Uses Inter font family for:
- Excellent readability
- Professional appearance
- Wide language support
- Optimized for screens

## Accessibility

This theme prioritizes accessibility:
- WCAG AA compliant contrast ratios
- Clear focus indicators
- Readable font sizes
- Sufficient spacing

## Credits

Created by CanvaStack Team  
Version: 1.0.0  
License: MIT
