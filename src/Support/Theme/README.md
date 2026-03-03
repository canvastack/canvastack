# CanvaStack Theme System

## Overview

The CanvaStack Theme System provides a flexible, extensible architecture for managing application themes. It supports multiple themes, runtime switching, caching, and seamless integration with Laravel and Blade.

## Architecture

### Core Components

1. **ThemeInterface** (`Contracts/ThemeInterface.php`)
   - Defines the contract for all theme implementations
   - Ensures consistent API across different theme types

2. **Theme** (`Support/Theme/Theme.php`)
   - Concrete implementation of ThemeInterface
   - Represents a single theme with configuration and metadata

3. **ThemeRepository** (`Support/Theme/ThemeRepository.php`)
   - Manages the collection of registered themes
   - Provides methods to register, retrieve, and query themes

4. **ThemeLoader** (`Support/Theme/ThemeLoader.php`)
   - Handles loading themes from various sources (JSON, PHP, directories)
   - Validates theme configurations
   - Supports hot-reloading during development

5. **ThemeManager** (`Support/Theme/ThemeManager.php`)
   - Central manager for all theme operations
   - Handles theme switching, caching, and CSS generation
   - Provides convenient API for accessing theme data

6. **ThemeServiceProvider** (`Support/Theme/ThemeServiceProvider.php`)
   - Registers theme services in Laravel container
   - Bootstraps theme system on application boot
   - Publishes configuration and theme files

## Features

### ✅ Multiple Theme Support
- Load and manage multiple themes simultaneously
- Switch between themes at runtime
- Default theme fallback

### ✅ Flexible Configuration
- JSON or PHP configuration files
- Hierarchical configuration structure
- Support for colors, fonts, layouts, and components

### ✅ Performance Optimized
- Theme caching with configurable TTL
- Lazy loading of theme resources
- CSS variable generation and caching

### ✅ Developer Friendly
- Fluent API for theme operations
- Blade directives for easy access
- Helper functions for convenience
- Comprehensive validation

### ✅ Dark Mode Support
- Built-in dark mode configuration
- Per-theme dark mode settings
- Automatic CSS variable generation

## Installation

The theme system is automatically registered when you install CanvaStack. To publish configuration and themes:

```bash
# Publish configuration
php artisan vendor:publish --tag=canvastack-config

# Publish default themes
php artisan vendor:publish --tag=canvastack-themes
```

## Configuration

Configure the theme system in `config/canvastack-ui.php`:

```php
'theme' => [
    'active' => env('CANVASTACK_THEME', 'default'),
    'path' => resource_path('themes'),
    'cache_enabled' => env('CANVASTACK_THEME_CACHE', true),
    'cache_ttl' => 3600, // 1 hour
],
```

## Usage

### Basic Usage

```php
use Canvastack\Canvastack\Support\Facades\Theme;

// Get current theme
$theme = Theme::current();

// Get theme name
$name = $theme->getName(); // 'default'

// Get theme colors
$colors = $theme->getColors();

// Get specific color
$primary = Theme::config('colors.primary.500'); // '#6366f1'
```

### Switching Themes

```php
// Switch to a different theme
Theme::setCurrentTheme('ocean');

// Check if theme exists before switching
if (Theme::has('sunset')) {
    Theme::setCurrentTheme('sunset');
}

// Get all available themes
$themes = Theme::all();

// Get theme names
$names = Theme::names(); // ['default', 'ocean', 'sunset']
```

### Accessing Theme Data

```php
// Get colors
$colors = Theme::colors();

// Get fonts
$fonts = Theme::fonts();

// Get layout configuration
$layout = Theme::layout();

// Check dark mode support
if (Theme::supportsDarkMode()) {
    // Enable dark mode toggle
}
```

### CSS Generation

```php
// Get CSS variables for current theme
$variables = Theme::getCssVariables();
// Returns: ['--color-primary-500' => '#6366f1', ...]

// Generate CSS
$css = Theme::generateCss();
// Returns: ":root { --color-primary-500: #6366f1; ... }"

// Generate CSS for specific theme
$css = Theme::generateCss('ocean');
```

### Blade Integration

```blade
{{-- Get theme value --}}
{{ theme('colors.primary.500') }}

{{-- Use theme directive --}}
@theme('colors.primary.500')

{{-- Get current theme name --}}
{{ theme()->current()->getName() }}

{{-- Inject CSS variables --}}
@themeVariables

{{-- Inject complete theme CSS --}}
@themeCss

{{-- Conditional based on theme --}}
@if(theme()->current()->getName() === 'ocean')
    <div class="ocean-specific-content"></div>
@endif

{{-- Check dark mode support --}}
@if(theme()->supportsDarkMode())
    <button onclick="toggleDark()">Toggle Dark Mode</button>
@endif
```

### Helper Function

```php
// Get theme manager
$manager = theme();

// Get theme value
$color = theme('colors.primary.500');

// With default value
$color = theme('colors.custom', '#000000');
```

## Creating Custom Themes

### Step 1: Create Theme Directory

```bash
mkdir resources/themes/my-theme
```

### Step 2: Create Configuration

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
      "50": "#f0f9ff",
      "500": "#0ea5e9",
      "900": "#0c4a6e"
    },
    "secondary": {
      "500": "#8b5cf6"
    }
  },
  "fonts": {
    "sans": "Inter, sans-serif"
  },
  "layout": {
    "sidebar_width": "16rem",
    "navbar_height": "4rem"
  },
  "dark_mode": {
    "enabled": true,
    "default": "light"
  }
}
```

### Step 3: Load and Activate

```php
// Themes are auto-loaded from resources/themes/
// Just activate it:
Theme::setCurrentTheme('my-theme');
```

## Theme Configuration Structure

### Required Fields

- `name`: Unique identifier (kebab-case)
- `display_name`: Human-readable name
- `version`: Semantic version
- `author`: Creator name
- `description`: Brief description

### Optional Fields

- `colors`: Color palette with shades
- `fonts`: Font family definitions
- `layout`: Layout dimensions and settings
- `components`: Component-specific styles
- `dark_mode`: Dark mode configuration
- `gradient`: Gradient definitions

### Color Shades

Use Tailwind-style shades (50-950):

```json
{
  "colors": {
    "primary": {
      "50": "#lightest",
      "100": "#lighter",
      "500": "#base",
      "900": "#darker",
      "950": "#darkest"
    }
  }
}
```

## Advanced Features

### Caching

```php
// Clear theme cache
Theme::clearCache();

// Reload themes from filesystem
Theme::reload();

// Disable caching (development)
config(['canvastack-ui.theme.cache_enabled' => false]);
```

### Metadata

```php
// Get metadata for all themes
$metadata = Theme::getAllMetadata();

// Get metadata for current theme
$metadata = Theme::current()->getMetadata();
```

### Export

```php
// Export current theme as JSON
$json = Theme::export('json');

// Export as PHP array
$array = Theme::export('array');
```

### Validation

```php
// Check if theme is valid
$isValid = Theme::current()->isValid();

// Themes are automatically validated on load
```

## API Reference

### ThemeManager Methods

- `initialize()`: Initialize theme system
- `current()`: Get current active theme
- `setCurrentTheme(string $name)`: Switch theme
- `get(string $name)`: Get theme by name
- `has(string $name)`: Check if theme exists
- `all()`: Get all themes
- `names()`: Get all theme names
- `register(ThemeInterface $theme)`: Register theme
- `getCssVariables()`: Get CSS variables
- `generateCss(?string $themeName)`: Generate CSS
- `config(string $key, $default)`: Get config value
- `colors()`: Get colors
- `fonts()`: Get fonts
- `layout()`: Get layout config
- `supportsDarkMode()`: Check dark mode support
- `clearCache()`: Clear cache
- `reload()`: Reload themes
- `getAllMetadata()`: Get all metadata
- `export(string $format)`: Export theme

### ThemeInterface Methods

- `getName()`: Get theme name
- `getDisplayName()`: Get display name
- `getVersion()`: Get version
- `getAuthor()`: Get author
- `getDescription()`: Get description
- `getConfig()`: Get all configuration
- `get(string $key, $default)`: Get config value
- `getColors()`: Get colors
- `getFonts()`: Get fonts
- `getLayout()`: Get layout
- `getComponents()`: Get components
- `supportsDarkMode()`: Check dark mode
- `isValid()`: Validate theme
- `getCssVariables()`: Get CSS variables
- `getMetadata()`: Get metadata

## Best Practices

1. **Use Semantic Colors**: Stick to primary, secondary, accent, success, warning, error, info
2. **Provide All Shades**: Include shades 50-950 for flexibility
3. **Test Dark Mode**: If enabled, test all components in dark mode
4. **Cache in Production**: Enable caching for better performance
5. **Version Your Themes**: Use semantic versioning
6. **Validate Configurations**: Ensure all required fields are present
7. **Document Custom Themes**: Add README for custom themes

## Troubleshooting

### Theme Not Found

```php
// Check if theme exists
if (!Theme::has('my-theme')) {
    // Theme not loaded
    Theme::reload(); // Try reloading
}
```

### Cache Issues

```php
// Clear cache after theme changes
Theme::clearCache();
php artisan cache:clear
```

### Invalid Configuration

```php
// Check theme validity
if (!Theme::current()->isValid()) {
    // Theme configuration is invalid
    // Check for missing required fields
}
```

## Examples

See the included themes for examples:
- `resources/themes/default/` - Default gradient theme
- `resources/themes/ocean/` - Ocean-inspired theme
- `resources/themes/sunset/` - Sunset-inspired theme

## Contributing

When creating themes for CanvaStack:

1. Follow the configuration structure
2. Test with both light and dark modes
3. Ensure WCAG AA color contrast
4. Document any custom features
5. Include preview screenshots

## License

The CanvaStack Theme System is part of the CanvaStack package and follows the same license.
