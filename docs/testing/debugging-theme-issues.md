# Debugging Theme Issues

Guide for debugging common theme-related issues in CanvaStack.

## 📦 Overview

This guide covers:
- Enabling debug mode
- Common theme issues
- Debugging techniques
- Troubleshooting steps
- Performance profiling

## 🔧 Enabling Debug Mode

### Configuration

Enable theme debug mode in `config/canvastack-ui.php`:

```php
return [
    'theme' => [
        'debug' => env('THEME_DEBUG', false),
        // ... other config
    ],
];
```

Or set in `.env`:

```env
THEME_DEBUG=true
```

### Programmatic Control

```php
use Canvastack\Canvastack\Support\Theme\ThemeLoader;

$loader = app(ThemeLoader::class);

// Enable debug mode
$loader->setDebug(true);

// Load theme with debug logging
$theme = $loader->load('gradient');

// Get debug log
$log = $loader->getDebugLog();
foreach ($log as $message) {
    echo $message . "\n";
}

// Clear debug log
$loader->clearDebugLog();
```

## 📊 Debug Output Example

When debug mode is enabled, you'll see detailed logging:

```
[2026-02-27 10:30:15] Loading themes from registry
[2026-02-27 10:30:15] Retrieved registry {"theme_count":2}
[2026-02-27 10:30:15] Loading theme from registry {"index":0,"name":"gradient"}
[2026-02-27 10:30:15] Creating theme from config {"name":"gradient"}
[2026-02-27 10:30:15] Using theme config format {"format":"nested","config_keys":["colors","fonts"]}
[2026-02-27 10:30:15] Successfully loaded theme {"name":"gradient"}
[2026-02-27 10:30:15] Loaded themes from registry {"count":2}
```

## 🐛 Common Issues

### Issue 1: Theme Not Found

**Symptoms**:
- Error: "Theme 'theme-name' not found"
- Theme doesn't appear in theme list

**Debug Steps**:

1. Enable debug mode and check log:
```php
$loader->setDebug(true);
$themes = $loader->loadAll();
$log = $loader->getDebugLog();
```

2. Check theme registry:
```php
$registry = config('canvastack-ui.theme.registry', []);
dd($registry);
```

3. Check filesystem:
```php
$basePath = $loader->getBasePath();
$exists = $loader->exists('theme-name');
```

**Solutions**:
- Verify theme is in registry or filesystem
- Check theme name matches exactly (case-sensitive)
- Ensure theme.json or theme.php exists
- Verify file permissions

### Issue 2: Theme Validation Fails

**Symptoms**:
- Error: "Missing required field: config"
- Error: "Missing required color: accent"

**Debug Steps**:

1. Get validation errors:
```php
$validator = new ThemeValidator();
$valid = $validator->validate($config);

if (!$valid) {
    dd($validator->getErrors());
}
```

2. Check theme structure:
```php
// Nested format (file)
$config = [
    'name' => 'theme',
    'config' => [
        'colors' => [...],
    ],
];

// Flat format (registry)
$config = [
    'name' => 'theme',
    'colors' => [...],
];
```

**Solutions**:
- Add missing required fields
- Use correct format (nested or flat)
- Include all required colors: primary, secondary, accent
- Validate color format (#RRGGBB)

### Issue 3: Theme Cache Issues

**Symptoms**:
- Theme changes not reflected
- Old theme still active after switch
- Empty cache returns early

**Debug Steps**:

1. Check cache status:
```php
$manager = app('canvastack.theme');
$cache = $manager->getCache();
$cached = $cache->getAll();
dd($cached);
```

2. Clear cache:
```php
$manager->clearCache();
```

3. Reload themes:
```php
$manager->reload();
```

**Solutions**:
- Clear theme cache
- Reload themes from filesystem
- Check cache driver configuration
- Verify cache permissions

### Issue 4: Vite Assets Not Found

**Symptoms**:
- Error: "Vite manifest not found"
- CSS/JS not loading
- 404 errors for assets

**Debug Steps**:

1. Check manifest location:
```bash
# Production
ls -la public/build/manifest.json
ls -la public/build/.vite/manifest.json

# Test environment
ls -la vendor/orchestra/testbench-core/laravel/public/build/manifest.json
```

2. Verify Vite build:
```bash
npm run build
```

3. Check asset paths in manifest:
```php
$manifest = json_decode(file_get_contents('public/build/manifest.json'), true);
dd($manifest);
```

**Solutions**:
- Run `npm run build` to generate assets
- Copy manifest to both locations (Vite 5+)
- Use `InteractsWithViteAssets` trait in tests
- Check Vite configuration

### Issue 5: Route Not Defined

**Symptoms**:
- Error: "Route [admin.dashboard] not defined"
- 404 errors on theme pages

**Debug Steps**:

1. List all routes:
```bash
php artisan route:list | grep theme
```

2. Check route registration:
```php
Route::get('/admin/themes', [ThemeController::class, 'index'])
    ->name('admin.themes.index');
```

3. Verify middleware:
```php
Route::middleware(['web', 'auth'])->group(function () {
    // Theme routes
});
```

**Solutions**:
- Register missing routes
- Use `InteractsWithRoutes` trait in tests
- Check route middleware
- Verify route names match

## 🔍 Debugging Techniques

### 1. Laravel Debugbar

Install and use Laravel Debugbar for detailed insights:

```bash
composer require barryvdh/laravel-debugbar --dev
```

View:
- Database queries
- View data
- Route information
- Cache operations

### 2. Laravel Telescope

For production debugging:

```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

Monitor:
- Requests
- Exceptions
- Cache operations
- Events

### 3. Ray

For advanced debugging:

```bash
composer require spatie/laravel-ray
```

Usage:
```php
ray($theme)->blue();
ray($config)->green();
ray()->table($themes);
```

### 4. Custom Logging

Add custom logging to theme operations:

```php
use Illuminate\Support\Facades\Log;

Log::channel('theme')->info('Loading theme', [
    'name' => $themeName,
    'source' => 'registry',
]);
```

Configure in `config/logging.php`:

```php
'channels' => [
    'theme' => [
        'driver' => 'daily',
        'path' => storage_path('logs/theme.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

## 🎯 Performance Profiling

### 1. Theme Loading Performance

```php
use Illuminate\Support\Facades\Benchmark;

$result = Benchmark::measure(fn () => $loader->loadAll());
echo "Theme loading took: {$result}ms\n";
```

### 2. Cache Performance

```php
// Without cache
$start = microtime(true);
$themes = $manager->all();
$withoutCache = (microtime(true) - $start) * 1000;

// With cache
$manager->clearCache();
$manager->all(); // Populate cache

$start = microtime(true);
$themes = $manager->all();
$withCache = (microtime(true) - $start) * 1000;

echo "Without cache: {$withoutCache}ms\n";
echo "With cache: {$withCache}ms\n";
echo "Improvement: " . round(($withoutCache - $withCache) / $withoutCache * 100, 2) . "%\n";
```

### 3. Validation Performance

```php
$validator = new ThemeValidator();

$result = Benchmark::measure(fn () => $validator->validate($config));
echo "Validation took: {$result}ms\n";
```

## 🧪 Testing Debug Mode

### Unit Test

```php
public function test_debug_mode_logs_operations(): void
{
    $loader = new ThemeLoader($this->basePath);
    $loader->setDebug(true);
    
    $theme = $loader->load('gradient');
    
    $log = $loader->getDebugLog();
    $this->assertNotEmpty($log);
    $this->assertStringContainsString('Loading theme', $log[0]);
}
```

### Feature Test

```php
public function test_theme_loading_with_debug(): void
{
    config(['canvastack-ui.theme.debug' => true]);
    
    $response = $this->get(route('admin.themes.index'));
    
    $response->assertStatus(200);
    
    // Check Laravel log for debug messages
    $this->assertFileExists(storage_path('logs/laravel.log'));
}
```

## 📋 Troubleshooting Checklist

When debugging theme issues, check:

- [ ] Debug mode is enabled
- [ ] Theme exists in registry or filesystem
- [ ] Theme configuration is valid
- [ ] All required fields are present
- [ ] Colors are in correct format
- [ ] Cache is cleared
- [ ] Routes are registered
- [ ] Vite assets are built
- [ ] File permissions are correct
- [ ] Middleware is configured
- [ ] User is authenticated (if required)
- [ ] Laravel log for errors
- [ ] Browser console for JS errors
- [ ] Network tab for 404s

## 💡 Tips

### 1. Use Artisan Tinker

Quick debugging in console:

```bash
php artisan tinker
```

```php
$manager = app('canvastack.theme');
$themes = $manager->all();
$current = $manager->current();
$manager->activate('gradient');
```

### 2. Dump and Die

Strategic dd() placement:

```php
// In controller
dd($themes, $currentTheme, $meta);

// In service
dd($config, $validator->getErrors());

// In view
@dd($table, $meta)
```

### 3. Query Logging

Enable query logging:

```php
DB::enableQueryLog();

// ... theme operations ...

dd(DB::getQueryLog());
```

### 4. Event Debugging

Listen to theme events:

```php
Event::listen(ThemeLoaded::class, function ($event) {
    Log::info('Theme loaded', [
        'name' => $event->theme->getName(),
        'source' => $event->source,
    ]);
});
```

## 📚 Related Documentation

- [Theme Testing Guide](theme-testing-guide.md) - Complete testing guide
- [Test Environment Setup](test-environment-setup.md) - Test configuration
- [Theme System](../features/theming.md) - Theme system overview

---

**Last Updated**: 2026-02-27  
**Version**: 1.0.0  
**Status**: Published
