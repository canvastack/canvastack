# Theme API Reference

Complete API reference for CanvaStack theme system including helper functions, Blade directives, facade, and events.

## 📦 Location

- **Helper Functions**: `packages/canvastack/canvastack/src/Support/helpers.php`
- **Blade Directives**: `packages/canvastack/canvastack/src/View/BladeDirectives.php`
- **Facade**: `packages/canvastack/canvastack/src/Facades/Theme.php`
- **Events**: `packages/canvastack/canvastack/src/Events/`

---

## 🎯 Helper Functions

### theme()

Get the theme manager instance or a theme configuration value.

```php
// Get theme manager instance
$manager = theme();

// Get theme configuration value
$value = theme('name');

// Get with default value
$value = theme('colors.primary', '#6366f1');
```

**Parameters:**
- `$key` (string|null): Configuration key using dot notation
- `$default` (mixed): Default value if key not found

**Returns:** `ThemeManager|mixed`

---

### current_theme()

Get the current active theme instance.

```php
$theme = current_theme();

echo $theme->getName();
echo $theme->getVersion();
```

**Returns:** `ThemeInterface`

---

### theme_color()

Get a color value from the current theme.

```php
$primary = theme_color('primary');
$secondary = theme_color('secondary', '#8b5cf6');

// Nested color values
$shade = theme_color('primary.500');
```

**Parameters:**
- `$key` (string): Color key using dot notation
- `$default` (mixed): Default value if key not found

**Returns:** `mixed`

---

### theme_font()

Get a font value from the current theme.

```php
$sans = theme_font('sans');
$mono = theme_font('mono', 'monospace');

// Font properties
$family = theme_font('sans.family');
$weight = theme_font('sans.weight');
```

**Parameters:**
- `$key` (string): Font key using dot notation
- `$default` (mixed): Default value if key not found

**Returns:** `mixed`

---

### theme_css()

Get compiled CSS for the current theme.

```php
// Get CSS with formatting
$css = theme_css();

// Get minified CSS
$css = theme_css(true);
```

**Parameters:**
- `$minify` (bool): Whether to minify the CSS (default: false)

**Returns:** `string`

**Example Output:**
```css
:root {
  --color-primary: #6366f1;
  --color-secondary: #8b5cf6;
  --font-sans: 'Inter', sans-serif;
}
```

---

### theme_inject()

Inject complete theme (CSS + fonts + JS) into the page.

```php
echo theme_inject();
```

**Returns:** `string` - HTML with style tags, font imports, and JavaScript

**Example Output:**
```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
  --color-primary: #6366f1;
  /* ... */
}
</style>
<script>
window.canvastackTheme = { /* ... */ };
</script>
```

---

## 🎨 Blade Directives

### @theme()

Get a theme configuration value in Blade templates.

```blade
<div style="color: @theme('colors.primary')">
    Primary Color Text
</div>

<h1>@theme('name')</h1>
```

---

### @themeColor()

Get a color value from the current theme.

```blade
<div style="background: @themeColor('primary')">
    Primary Background
</div>

<button style="color: @themeColor('secondary', '#8b5cf6')">
    Button
</button>
```

---

### @themeFont()

Get a font value from the current theme.

```blade
<div style="font-family: @themeFont('sans')">
    Sans Serif Text
</div>

<code style="font-family: @themeFont('mono')">
    Monospace Code
</code>
```

---

### @themeCss

Output compiled CSS for the current theme.

```blade
<style>
    @themeCss
</style>
```

---

### @themeInject

Inject complete theme (CSS + fonts + JS).

```blade
<!DOCTYPE html>
<html>
<head>
    @themeInject
</head>
<body>
    <!-- Content -->
</body>
</html>
```

---

### @themeStyles

Output theme CSS wrapped in style tags.

```blade
@themeStyles
```

Equivalent to:
```blade
<style>
    @themeCss
</style>
```

---

### @themeName

Output the current theme name.

```blade
<p>Current theme: @themeName</p>
```

---

### @themeVersion

Output the current theme version.

```blade
<p>Theme version: @themeVersion</p>
```

---

### @themeAuthor

Output the current theme author.

```blade
<p>Theme by: @themeAuthor</p>
```

---

### @darkMode

Check if current theme supports dark mode.

```blade
<script>
    const supportsDarkMode = @darkMode;
    
    if (supportsDarkMode) {
        // Enable dark mode toggle
    }
</script>
```

---

## 🎭 Facade

The `Theme` facade provides static access to the ThemeManager.

### Usage

```php
use Canvastack\Canvastack\Facades\Theme;

// Get current theme
$theme = Theme::current();

// Get all themes
$themes = Theme::all();

// Check if theme exists
if (Theme::has('ocean')) {
    Theme::setCurrentTheme('ocean');
}

// Get theme colors
$colors = Theme::colors();

// Get compiled CSS
$css = Theme::getCompiledCss();
```

### Available Methods

All ThemeManager methods are available through the facade:

| Method | Description |
|--------|-------------|
| `initialize()` | Initialize the theme manager |
| `loadThemes()` | Load all themes from filesystem |
| `current()` | Get current active theme |
| `setCurrentTheme($name)` | Set the current theme |
| `get($name)` | Get a theme by name |
| `has($name)` | Check if theme exists |
| `all()` | Get all registered themes |
| `names()` | Get all theme names |
| `register($theme)` | Register a new theme |
| `loadFromFile($path)` | Load theme from file |
| `loadFromArray($config)` | Load theme from array |
| `getCssVariables()` | Get CSS variables |
| `generateCss($themeName)` | Generate CSS for theme |
| `getCompiledCss($minify)` | Get compiled CSS |
| `getTailwindConfig()` | Get Tailwind config |
| `getJavaScriptConfig()` | Get JavaScript config |
| `config($key, $default)` | Get configuration value |
| `colors()` | Get theme colors |
| `fonts()` | Get theme fonts |
| `layout()` | Get layout configuration |
| `supportsDarkMode()` | Check dark mode support |
| `clearCache()` | Clear theme cache |
| `reload()` | Reload themes from filesystem |
| `getAllMetadata()` | Get metadata for all themes |
| `export($format)` | Export theme configuration |
| `injectCss()` | Inject CSS variables |
| `injectFonts()` | Inject font imports |
| `injectComplete()` | Inject complete theme |

---

## 🔔 Events

The theme system dispatches events for important operations.

### ThemeChanged

Fired when the active theme is changed.

```php
use Canvastack\Canvastack\Events\ThemeChanged;
use Illuminate\Support\Facades\Event;

Event::listen(ThemeChanged::class, function (ThemeChanged $event) {
    $newTheme = $event->newTheme;
    $previousTheme = $event->previousTheme;
    
    // Clear compiled assets
    // Regenerate CSS
    // Update user preferences
});
```

**Properties:**
- `$newTheme` (ThemeInterface): The new active theme
- `$previousTheme` (ThemeInterface|null): The previous theme

**Methods:**
- `getNewThemeName()`: Get new theme name
- `getPreviousThemeName()`: Get previous theme name

---

### ThemeLoaded

Fired when a theme is loaded and registered.

```php
use Canvastack\Canvastack\Events\ThemeLoaded;
use Illuminate\Support\Facades\Event;

Event::listen(ThemeLoaded::class, function (ThemeLoaded $event) {
    $theme = $event->theme;
    $source = $event->source; // 'file', 'array', 'manual'
    
    // Log theme loading
    // Validate theme
    // Compile assets
});
```

**Properties:**
- `$theme` (ThemeInterface): The loaded theme
- `$source` (string): Load source ('file', 'array', 'manual')

**Methods:**
- `getThemeName()`: Get theme name
- `getSource()`: Get load source

---

### ThemesReloaded

Fired when all themes are reloaded from the filesystem.

```php
use Canvastack\Canvastack\Events\ThemesReloaded;
use Illuminate\Support\Facades\Event;

Event::listen(ThemesReloaded::class, function (ThemesReloaded $event) {
    $count = $event->count;
    $themeNames = $event->themeNames;
    
    // Clear all theme caches
    // Regenerate theme registry
    // Update theme selector UI
});
```

**Properties:**
- `$count` (int): Number of themes loaded
- `$themeNames` (array): Array of theme names

**Methods:**
- `getCount()`: Get theme count
- `getThemeNames()`: Get theme names array

---

## 📝 Complete Examples

### Example 1: Using Helper Functions

```php
// In a controller
public function index()
{
    $manager = theme();
    $currentTheme = current_theme();
    
    $primaryColor = theme_color('primary');
    $fontFamily = theme_font('sans');
    
    return view('dashboard', [
        'themeName' => $currentTheme->getName(),
        'primaryColor' => $primaryColor,
        'fontFamily' => $fontFamily,
    ]);
}
```

### Example 2: Using Blade Directives

```blade
<!DOCTYPE html>
<html>
<head>
    <title>@theme('name') - Dashboard</title>
    @themeInject
</head>
<body>
    <header style="background: @themeColor('primary')">
        <h1 style="font-family: @themeFont('sans')">
            @themeName
        </h1>
    </header>
    
    <main>
        <p>Theme version: @themeVersion</p>
        <p>Created by: @themeAuthor</p>
        
        @if(@darkMode)
            <button id="dark-mode-toggle">Toggle Dark Mode</button>
        @endif
    </main>
</body>
</html>
```

### Example 3: Using Facade

```php
use Canvastack\Canvastack\Facades\Theme;

// Switch theme
Theme::setCurrentTheme('ocean');

// Get theme data
$colors = Theme::colors();
$fonts = Theme::fonts();

// Export theme
$json = Theme::export('json');
file_put_contents('theme-backup.json', $json);

// Clear cache
Theme::clearCache();
```

### Example 4: Listening to Events

```php
// In EventServiceProvider
use Canvastack\Canvastack\Events\ThemeChanged;
use Canvastack\Canvastack\Events\ThemeLoaded;
use Canvastack\Canvastack\Events\ThemesReloaded;

protected $listen = [
    ThemeChanged::class => [
        ClearCompiledAssets::class,
        UpdateUserPreferences::class,
    ],
    ThemeLoaded::class => [
        ValidateTheme::class,
        CompileThemeAssets::class,
    ],
    ThemesReloaded::class => [
        ClearAllThemeCaches::class,
        RegenerateThemeRegistry::class,
    ],
];
```

### Example 5: Custom Theme Switcher

```php
// Controller
public function switchTheme(Request $request)
{
    $themeName = $request->input('theme');
    
    if (Theme::has($themeName)) {
        Theme::setCurrentTheme($themeName);
        
        // Save to user preferences
        auth()->user()->update([
            'theme' => $themeName,
        ]);
        
        return response()->json([
            'success' => true,
            'theme' => $themeName,
            'css' => Theme::getCompiledCss(true),
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => 'Theme not found',
    ], 404);
}
```

```blade
<!-- View -->
<select id="theme-selector">
    @foreach(Theme::all() as $theme)
        <option value="{{ $theme->getName() }}" 
                @if($theme->getName() === current_theme()->getName()) selected @endif>
            {{ $theme->getDisplayName() }}
        </option>
    @endforeach
</select>

<script>
document.getElementById('theme-selector').addEventListener('change', function(e) {
    fetch('/api/theme/switch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ theme: e.target.value })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Inject new CSS
            document.getElementById('theme-styles').innerHTML = data.css;
        }
    });
});
</script>
```

---

## 💡 Tips & Best Practices

1. **Use helpers in controllers** - More readable than facade
2. **Use directives in views** - Cleaner Blade syntax
3. **Use facade for static access** - When you need type hints
4. **Listen to events** - For cache invalidation and asset compilation
5. **Cache theme data** - Use `theme_css()` result in production
6. **Validate themes** - Always check `Theme::has()` before switching
7. **Handle errors** - Wrap theme operations in try-catch blocks

---

## 🔗 Related Documentation

- [Theme System Overview](../features/theming.md)
- [Creating Custom Themes](../guides/creating-themes.md)
- [Theme Configuration](../getting-started/configuration.md#theme-configuration)
- [Tailwind Integration](../frontend/tailwind-integration.md)

---

**Last Updated**: 2026-02-26  
**Version**: 1.0.0  
**Status**: Published
