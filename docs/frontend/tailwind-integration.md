# Tailwind CSS Integration

Complete guide to CanvaStack's dynamic Tailwind CSS integration system that generates configuration from theme JSON files.

## 📦 Location

- **Config Generator**: `src/Support/Theme/TailwindConfigGenerator.php`
- **Theme Plugin**: `src/Support/Theme/TailwindThemePlugin.php`
- **Build Script**: `build/generate-tailwind-config.php`
- **Vite Config**: `vite.config.js`
- **PostCSS Config**: `postcss.config.js`

---

## 🎯 Features

- **Dynamic Configuration**: Generates Tailwind config from theme JSON files
- **JIT Compilation**: Just-In-Time compilation for theme-specific styles
- **Theme-Specific Utilities**: Custom utility classes for each theme
- **DaisyUI Integration**: Automatic DaisyUI theme generation
- **Multi-Theme Support**: Build process optimized for multiple themes
- **Caching**: Configuration caching for improved build performance
- **Custom Breakpoints**: Support for theme-specific responsive breakpoints

---

## 📖 Basic Usage

### Generating Tailwind Configuration

```bash
# Generate dynamic Tailwind config
npm run build:tailwind

# Build with Tailwind config generation
npm run build

# Development with auto-regeneration
npm run dev
```

### Using in PHP

```php
use Canvastack\Canvastack\Support\Theme\TailwindConfigGenerator;

// Get the generator
$generator = app(TailwindConfigGenerator::class);

// Generate config for a specific theme
$config = $generator->generate('gradient');

// Generate config for all themes
$config = $generator->generateForAllThemes();

// Export as JavaScript
$js = $generator->exportAsJavaScript();
file_put_contents('tailwind.config.generated.js', $js);
```

---

## 🔧 Configuration

### Theme JSON Structure

Themes define Tailwind-compatible configuration in their JSON files:

```json
{
  "name": "my-theme",
  "colors": {
    "primary": {
      "50": "#eef2ff",
      "500": "#6366f1",
      "900": "#312e81"
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
      "lg": "0.75rem"
    },
    "breakpoints": {
      "sm": "640px",
      "md": "768px",
      "lg": "1024px",
      "xl": "1280px",
      "2xl": "1536px"
    }
  },
  "gradient": {
    "primary": "linear-gradient(135deg, #6366f1, #8b5cf6)",
    "subtle": "linear-gradient(135deg, #eef2ff, #f5f3ff)"
  }
}
```

### Generated Tailwind Config

The system generates a complete Tailwind configuration:

```javascript
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './src/**/*.php',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eef2ff',
          500: '#6366f1',
          900: '#312e81'
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace']
      },
      maxWidth: {
        container: '80rem'
      },
      borderRadius: {
        sm: '0.375rem',
        md: '0.5rem',
        lg: '0.75rem'
      }
    },
    screens: {
      sm: '640px',
      md: '768px',
      lg: '1024px',
      xl: '1280px',
      '2xl': '1536px'
    }
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
    require('daisyui'),
    require('./build/tailwind-theme-plugin.js')
  ],
  daisyui: {
    themes: [
      {
        'my-theme': {
          primary: '#6366f1',
          secondary: '#8b5cf6',
          accent: '#a855f7',
          // ... more colors
        }
      }
    ]
  }
};
```

---

## 🎨 Theme-Specific Utilities

The system generates custom utility classes for each theme:

### Color Utilities

```html
<!-- Text colors -->
<div class="theme-gradient-primary">Primary color text</div>
<div class="theme-ocean-secondary">Ocean secondary text</div>

<!-- Background colors -->
<div class="bg-theme-gradient-primary">Primary background</div>
<div class="bg-theme-ocean-accent">Ocean accent background</div>

<!-- Border colors -->
<div class="border-theme-gradient-primary">Primary border</div>
```

### Gradient Utilities

```html
<!-- Apply theme gradients -->
<div class="theme-gradient-gradient-primary">Primary gradient</div>
<div class="theme-gradient-gradient-subtle">Subtle gradient</div>
<div class="theme-ocean-gradient-primary">Ocean gradient</div>
```

### Usage in Blade

```blade
<div class="theme-{{ $theme->getName() }}-primary">
    Dynamic theme color
</div>

<div class="bg-theme-{{ $theme->getName() }}-gradient-primary">
    Dynamic gradient background
</div>
```

---

## 🚀 Build Process

### Development Workflow

```bash
# 1. Start development server (auto-generates config)
npm run dev

# 2. Make changes to theme JSON files
# 3. Config is automatically regenerated
# 4. Vite hot-reloads the changes
```

### Production Build

```bash
# 1. Generate Tailwind config from all themes
npm run build:tailwind

# 2. Build optimized assets
npm run build

# Output:
# - tailwind.config.generated.js (Tailwind config)
# - build/tailwind-theme-plugin.js (Theme utilities)
# - build/themes.json (Theme metadata)
# - public/build/* (Compiled assets)
```

### Build Script Details

The `build/generate-tailwind-config.php` script:

1. Loads all themes from `resources/themes/`
2. Generates Tailwind configuration
3. Creates theme-specific utility plugin
4. Exports configuration as JavaScript
5. Saves theme metadata for reference

---

## 🎮 Programmatic API

### TailwindConfigGenerator

```php
use Canvastack\Canvastack\Support\Theme\TailwindConfigGenerator;

$generator = new TailwindConfigGenerator($themeManager, $cache);

// Generate for specific theme
$config = $generator->generate('gradient', useCache: false);

// Generate for all themes
$config = $generator->generateForAllThemes();

// Generate complete config with DaisyUI
$config = $generator->generateComplete();

// Generate DaisyUI theme
$daisyUI = $generator->generateDaisyUITheme($theme);

// Export formats
$js = $generator->exportAsJavaScript();
$commonJS = $generator->exportAsCommonJS();

// Cache management
$generator->clearCache();
$generator->setCacheTtl(7200);
```

### TailwindThemePlugin

```php
use Canvastack\Canvastack\Support\Theme\TailwindThemePlugin;

$plugin = new TailwindThemePlugin($themeManager);

// Generate plugin code
$code = $plugin->generate(); // CommonJS format
$code = $plugin->generateAsModule(); // ES Module format

// Save to file
$plugin->saveToFile('build/theme-plugin.js', 'commonjs');
$plugin->saveToFile('build/theme-plugin.mjs', 'module');
```

---

## 🔍 Implementation Details

### Configuration Extraction

The generator extracts configuration from themes:

**Colors**: Converts theme color palettes to Tailwind format
```php
'primary' => [
    '50' => '#eef2ff',
    '500' => '#6366f1',
    '900' => '#312e81'
]
```

**Fonts**: Converts font strings to arrays
```php
'sans' => ['Inter', 'system-ui', 'sans-serif']
```

**Layout**: Extracts spacing, container, and border radius
```php
'maxWidth' => ['container' => '80rem'],
'borderRadius' => ['sm' => '0.375rem']
```

**Breakpoints**: Custom responsive breakpoints per theme
```php
'screens' => [
    'sm' => '640px',
    'md' => '768px'
]
```

### JIT Compilation

Tailwind's JIT compiler works seamlessly with generated config:

1. Config is generated before build
2. Tailwind scans content files
3. Only used utilities are compiled
4. Theme-specific utilities included on-demand
5. Optimized CSS output

### Caching Strategy

**Configuration Cache**:
- Cached per theme: `canvastack.tailwind.config.{theme}`
- Cached for all themes: `canvastack.tailwind.config.all`
- Default TTL: 3600 seconds (1 hour)
- Cleared on theme changes

**Build Cache**:
- Generated config cached during development
- Regenerated on theme file changes
- Production builds always regenerate

---

## 💡 Tips & Best Practices

### 1. Theme Design

**Use consistent color scales**:
```json
{
  "colors": {
    "primary": {
      "50": "#lightest",
      "100": "#lighter",
      "500": "#base",
      "900": "#darkest"
    }
  }
}
```

**Define semantic colors**:
```json
{
  "colors": {
    "success": {"400": "#34d399"},
    "warning": {"400": "#fbbf24"},
    "error": {"400": "#f87171"}
  }
}
```

### 2. Performance Optimization

**Enable caching in development**:
```php
$config = $generator->generate('theme', useCache: true);
```

**Disable caching in production builds**:
```bash
npm run build:tailwind # Always regenerates
```

**Use CSS code splitting**:
```javascript
// vite.config.js
export default {
  build: {
    cssCodeSplit: true
  }
}
```

### 3. Custom Utilities

**Add theme-specific utilities**:
```json
{
  "gradient": {
    "hero": "linear-gradient(135deg, #667eea, #764ba2)",
    "card": "linear-gradient(135deg, #f093fb, #f5576c)"
  }
}
```

**Use in templates**:
```html
<div class="theme-gradient-gradient-hero">
    Hero section with gradient
</div>
```

### 4. Breakpoint Customization

**Define custom breakpoints per theme**:
```json
{
  "layout": {
    "breakpoints": {
      "mobile": "480px",
      "tablet": "768px",
      "desktop": "1024px",
      "wide": "1440px"
    }
  }
}
```

**Use in Tailwind classes**:
```html
<div class="mobile:text-sm tablet:text-base desktop:text-lg">
    Responsive text
</div>
```

---

## 🎭 Common Patterns

### Pattern 1: Multi-Theme Application

```php
// Generate config for all themes
$generator = app(TailwindConfigGenerator::class);
$config = $generator->generateForAllThemes();

// All theme colors available
// Use theme-specific utilities
```

```html
<!-- Switch between themes dynamically -->
<div class="theme-{{ $currentTheme }}-primary">
    Content adapts to active theme
</div>
```

### Pattern 2: Theme Preview

```php
// Generate config for preview
$previewConfig = $generator->generate($previewTheme);

// Apply to preview iframe
$css = $generator->getCompiledCss();
```

### Pattern 3: Custom Theme Builder

```php
// User creates custom theme
$customTheme = [
    'name' => 'user-theme',
    'colors' => $userColors,
    'fonts' => $userFonts
];

// Load and generate config
$themeManager->loadFromArray($customTheme);
$config = $generator->generate('user-theme', useCache: false);
```

---

## 🧪 Testing

### Unit Tests

```php
public function test_generates_config_for_theme(): void
{
    $config = $this->generator->generate('test');
    
    $this->assertArrayHasKey('theme', $config);
    $this->assertArrayHasKey('colors', $config['theme']['extend']);
}

public function test_generates_theme_utilities(): void
{
    $code = $this->plugin->generate();
    
    $this->assertStringContainsString('.theme-test-primary', $code);
}
```

### Integration Tests

```bash
# Test build process
npm run build:tailwind
test -f tailwind.config.generated.js

# Test theme utilities
npm run build
grep "theme-gradient-primary" public/build/assets/*.css
```

---

## 🔗 Related Documentation

- [Theme System](../theme-system.md) - Core theme architecture
- [Theme Management](../theme-management.md) - Managing themes
- [DaisyUI Integration](./daisyui.md) - DaisyUI configuration
- [Vite Configuration](./vite.md) - Build configuration
- [CSS Architecture](./css-architecture.md) - CSS organization

---

## 📚 Resources

- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Tailwind JIT Mode](https://tailwindcss.com/docs/just-in-time-mode)
- [Tailwind Plugins](https://tailwindcss.com/docs/plugins)
- [DaisyUI Themes](https://daisyui.com/docs/themes/)
- [Vite CSS Features](https://vitejs.dev/guide/features.html#css)

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Published
