# Tailwind Theme Integration Guide

Step-by-step guide to integrating Tailwind CSS with CanvaStack themes.

---

## Quick Start

### 1. Install Dependencies

```bash
cd packages/canvastack/canvastack
npm install
```

### 2. Generate Tailwind Configuration

```bash
# Generate config from all themes
npm run build:tailwind
```

This creates:
- `tailwind.config.generated.js` - Dynamic Tailwind configuration
- `build/tailwind-theme-plugin.js` - Theme-specific utilities
- `build/themes.json` - Theme metadata

### 3. Build Assets

```bash
# Development
npm run dev

# Production
npm run build
```

---

## Creating a New Theme

### Step 1: Create Theme JSON

Create `resources/themes/my-theme/theme.json`:

```json
{
  "name": "my-theme",
  "display_name": "My Theme",
  "version": "1.0.0",
  "author": "Your Name",
  "description": "My custom theme",
  "colors": {
    "primary": {
      "50": "#f0f9ff",
      "100": "#e0f2fe",
      "200": "#bae6fd",
      "300": "#7dd3fc",
      "400": "#38bdf8",
      "500": "#0ea5e9",
      "600": "#0284c7",
      "700": "#0369a1",
      "800": "#075985",
      "900": "#0c4a6e",
      "950": "#082f49"
    },
    "secondary": {
      "500": "#8b5cf6"
    },
    "accent": {
      "500": "#ec4899"
    }
  },
  "fonts": {
    "sans": "Inter, system-ui, sans-serif",
    "mono": "JetBrains Mono, monospace"
  },
  "layout": {
    "container_max_width": "80rem",
    "border_radius": {
      "sm": "0.375rem",
      "md": "0.5rem",
      "lg": "0.75rem",
      "xl": "1rem"
    }
  },
  "gradient": {
    "primary": "linear-gradient(135deg, #0ea5e9, #8b5cf6)",
    "subtle": "linear-gradient(135deg, #f0f9ff, #faf5ff)"
  },
  "dark_mode": {
    "enabled": true,
    "default": "light"
  }
}
```

### Step 2: Regenerate Tailwind Config

```bash
npm run build:tailwind
```

### Step 3: Use Theme Colors

```html
<!-- Standard Tailwind colors -->
<div class="bg-primary-500 text-white">
    Primary color from theme
</div>

<!-- Theme-specific utilities -->
<div class="theme-my-theme-primary">
    Theme-specific primary color
</div>

<!-- Gradients -->
<div class="theme-my-theme-gradient-primary">
    Theme gradient background
</div>
```

---

## Using Theme Utilities

### Color Utilities

```html
<!-- Text colors -->
<span class="theme-my-theme-primary">Primary text</span>
<span class="theme-my-theme-secondary">Secondary text</span>
<span class="theme-my-theme-accent">Accent text</span>

<!-- Background colors -->
<div class="bg-theme-my-theme-primary">Primary background</div>
<div class="bg-theme-my-theme-secondary">Secondary background</div>

<!-- Border colors -->
<div class="border border-theme-my-theme-primary">Primary border</div>
```

### Gradient Utilities

```html
<!-- Apply theme gradients -->
<div class="theme-my-theme-gradient-primary p-8 text-white">
    <h1>Hero Section</h1>
    <p>With gradient background</p>
</div>

<div class="theme-my-theme-gradient-subtle p-4">
    <p>Subtle gradient card</p>
</div>
```

### Dynamic Theme Selection

```blade
@php
    $theme = app('canvastack.theme')->current();
    $themeName = $theme->getName();
@endphp

<div class="theme-{{ $themeName }}-primary">
    Adapts to active theme
</div>

<div class="bg-theme-{{ $themeName }}-gradient-primary">
    Dynamic gradient
</div>
```

---

## Custom Breakpoints

### Define in Theme JSON

```json
{
  "layout": {
    "breakpoints": {
      "mobile": "480px",
      "tablet": "768px",
      "desktop": "1024px",
      "wide": "1440px",
      "ultrawide": "1920px"
    }
  }
}
```

### Use in HTML

```html
<div class="
    mobile:text-sm
    tablet:text-base
    desktop:text-lg
    wide:text-xl
    ultrawide:text-2xl
">
    Responsive text with custom breakpoints
</div>
```

---

## Advanced Configuration

### Extending Generated Config

Create `tailwind.config.js` to extend generated config:

```javascript
import generatedConfig from './tailwind.config.generated.js';

export default {
  ...generatedConfig,
  theme: {
    ...generatedConfig.theme,
    extend: {
      ...generatedConfig.theme.extend,
      // Add custom extensions
      spacing: {
        '128': '32rem',
        '144': '36rem',
      },
      animation: {
        'spin-slow': 'spin 3s linear infinite',
      }
    }
  }
};
```

### Custom Theme Plugin

Create `build/custom-theme-plugin.js`:

```javascript
const plugin = require('tailwindcss/plugin');

module.exports = plugin(function({ addUtilities, theme }) {
  const newUtilities = {
    '.glass': {
      background: 'rgba(255, 255, 255, 0.1)',
      backdropFilter: 'blur(10px)',
      border: '1px solid rgba(255, 255, 255, 0.2)',
    },
    '.glass-dark': {
      background: 'rgba(0, 0, 0, 0.1)',
      backdropFilter: 'blur(10px)',
      border: '1px solid rgba(0, 0, 0, 0.2)',
    }
  };

  addUtilities(newUtilities);
});
```

Add to `tailwind.config.js`:

```javascript
export default {
  plugins: [
    // ... other plugins
    require('./build/custom-theme-plugin.js')
  ]
};
```

---

## Programmatic Usage

### Generate Config in PHP

```php
use Canvastack\Canvastack\Support\Theme\TailwindConfigGenerator;

// Get generator instance
$generator = app(TailwindConfigGenerator::class);

// Generate for specific theme
$config = $generator->generate('my-theme');

// Generate for all themes
$allConfig = $generator->generateForAllThemes();

// Export as JavaScript
$js = $generator->exportAsJavaScript();
file_put_contents('tailwind.config.generated.js', $js);

// Clear cache
$generator->clearCache();
```

### Generate Theme Plugin

```php
use Canvastack\Canvastack\Support\Theme\TailwindThemePlugin;

$plugin = app(TailwindThemePlugin::class);

// Generate plugin code
$code = $plugin->generate();

// Save to file
$plugin->saveToFile('build/tailwind-theme-plugin.js');
```

---

## Build Optimization

### Development

```javascript
// vite.config.js
export default defineConfig({
  build: {
    // Enable CSS code splitting
    cssCodeSplit: true,
    
    // Optimize chunk size
    chunkSizeWarningLimit: 1000,
  },
  
  // Optimize dependencies
  optimizeDeps: {
    include: ['alpinejs', 'apexcharts', 'gsap'],
  },
});
```

### Production

```bash
# Build with optimizations
npm run build

# Output analysis
du -sh public/build/assets/*.css
```

### Purge Unused CSS

Tailwind JIT automatically purges unused CSS. Ensure content paths are correct:

```javascript
// tailwind.config.generated.js
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './src/**/*.php',
  ],
  // ...
};
```

---

## Troubleshooting

### Config Not Updating

```bash
# Clear cache and regenerate
rm -rf node_modules/.vite
npm run build:tailwind
npm run dev
```

### Theme Utilities Not Working

1. Check theme JSON is valid
2. Regenerate config: `npm run build:tailwind`
3. Restart dev server: `npm run dev`
4. Check browser console for errors

### Build Errors

```bash
# Check Node.js version (requires 18+)
node --version

# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install

# Regenerate config
npm run build:tailwind
```

### Missing Colors

Ensure theme JSON has required color structure:

```json
{
  "colors": {
    "primary": {
      "500": "#color"  // Required
    }
  }
}
```

---

## Best Practices

### 1. Theme Naming

- Use kebab-case: `my-theme`, `ocean-blue`
- Be descriptive: `corporate-blue`, `nature-green`
- Avoid generic names: `theme1`, `new-theme`

### 2. Color Scales

- Always define shade `500` (base color)
- Include `50` (lightest) and `900` (darkest)
- Use consistent scale: 50, 100, 200, ..., 900

### 3. Gradients

- Name gradients semantically: `primary`, `hero`, `card`
- Use consistent direction: `135deg` for diagonal
- Test in both light and dark modes

### 4. Breakpoints

- Start with standard breakpoints
- Add custom only when needed
- Use semantic names: `mobile`, `tablet`, `desktop`

### 5. Performance

- Enable caching in development
- Use JIT mode (enabled by default)
- Minimize custom utilities
- Leverage Tailwind's built-in classes

---

## Examples

### Example 1: Multi-Theme App

```blade
{{-- Layout with theme switcher --}}
<div class="min-h-screen bg-base-100">
    <nav class="bg-theme-{{ $theme }}-primary">
        <x-theme-switcher />
    </nav>
    
    <main class="container mx-auto">
        @yield('content')
    </main>
</div>
```

### Example 2: Gradient Hero

```blade
<div class="theme-{{ $theme }}-gradient-primary min-h-screen flex items-center justify-center">
    <div class="text-center text-white">
        <h1 class="text-6xl font-bold mb-4">
            Welcome
        </h1>
        <p class="text-xl">
            Beautiful gradient hero section
        </p>
    </div>
</div>
```

### Example 3: Theme-Aware Card

```blade
<div class="card bg-base-100 shadow-xl border border-theme-{{ $theme }}-primary">
    <div class="card-body">
        <h2 class="card-title theme-{{ $theme }}-primary">
            Card Title
        </h2>
        <p>Card content adapts to theme</p>
        <div class="card-actions">
            <button class="btn bg-theme-{{ $theme }}-primary">
                Action
            </button>
        </div>
    </div>
</div>
```

---

## Next Steps

- [Theme System Overview](../theme-system.md)
- [Creating Custom Themes](./creating-themes.md)
- [DaisyUI Integration](./daisyui-integration.md)
- [Performance Optimization](./performance.md)

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Published
