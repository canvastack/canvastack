# Theme + Locale Integration

Complete guide for the integrated Theme and Internationalization (i18n) system in CanvaStack.

## 📦 Location

- **Integration Classes**: `src/Support/Integration/`
- **Tests**: `tests/Unit/Support/Integration/`, `tests/Feature/Integration/`
- **Documentation**: `docs/integration/`

## 🎯 Features

### Core Integration
- ✅ Themes work seamlessly with all locales
- ✅ RTL support for all themes
- ✅ Locale-specific fonts
- ✅ Performance optimization with caching
- ✅ Unified settings management
- ✅ User preferences system

### Performance
- ✅ Multi-layer caching (CSS, config)
- ✅ Cache warmup for all combinations
- ✅ Preload common combinations
- ✅ Optimized theme/locale switching
- ✅ Benchmark tools

### User Experience
- ✅ Unified settings page
- ✅ Persistent preferences (session/cookie)
- ✅ Import/export settings
- ✅ Validation and error handling

---

## 📖 Basic Usage

### ThemeLocaleIntegration

```php
use Canvastack\Canvastack\Support\Integration\ThemeLocaleIntegration;

$integration = app(ThemeLocaleIntegration::class);

// Get localized theme CSS
$css = $integration->getLocalizedThemeCss('default', 'ar');

// Get localized theme config
$config = $integration->getLocalizedThemeConfig('default', 'ar');

// Get HTML attributes
$attributes = $integration->getHtmlAttributes();
// ['lang' => 'ar', 'dir' => 'rtl', 'class' => 'rtl theme-default']

// Get body classes
$classes = $integration->getBodyClasses();
// 'locale-ar rtl theme-default'

// Test theme in all locales
$results = $integration->testThemeInAllLocales('default');

// Validate compatibility
$issues = $integration->validateThemeLocaleCompatibility('default', 'ar');

// Clear cache
$integration->clearCache();

// Get cache statistics
$stats = $integration->getCacheStats();
```

---

## 🚀 Performance Optimization

### ThemeLocalePerformance

```php
use Canvastack\Canvastack\Support\Integration\ThemeLocalePerformance;

$performance = app(ThemeLocalePerformance::class);

// Warm up cache for all combinations
$results = $performance->warmupCache();
// ['total' => 12, 'cached' => 12, 'failed' => 0, 'time' => 150.5]

// Preload common combinations
$results = $performance->preloadCommon([
    ['theme' => 'default', 'locale' => 'en'],
    ['theme' => 'default', 'locale' => 'id'],
    ['theme' => 'default', 'locale' => 'ar'],
]);

// Optimize theme switching
$performance->optimizeThemeSwitch('ocean');

// Optimize locale switching
$performance->optimizeLocaleSwitch('ar');

// Measure theme switch performance
$results = $performance->measureThemeSwitch('default', 'ocean');
// [
//     'from_theme' => 'default',
//     'to_theme' => 'ocean',
//     'locale' => 'en',
//     'uncached_time_ms' => 5.2,
//     'cached_time_ms' => 0.3,
//     'improvement' => 94.23
// ]

// Measure locale switch performance
$results = $performance->measureLocaleSwitch('en', 'ar');

// Benchmark all combinations
$results = $performance->benchmark();

// Get recommendations
$recommendations = $performance->getRecommendations();

// Get cache size estimate
$estimate = $performance->getCacheSizeEstimate();
// [
//     'total_combinations' => 12,
//     'total_size_bytes' => 45678,
//     'total_size_kb' => 44.61,
//     'total_size_mb' => 0.04,
//     'avg_size_per_combination_bytes' => 3806.5
// ]
```

---

## ⚙️ Unified Settings Management

### UnifiedSettingsManager

```php
use Canvastack\Canvastack\Support\Integration\UnifiedSettingsManager;

$settings = app(UnifiedSettingsManager::class);

// Get all settings
$all = $settings->getAllSettings();
// [
//     'theme' => [...],
//     'locale' => [...],
//     'integration' => [...]
// ]

// Get theme settings
$themeSettings = $settings->getThemeSettings();

// Get locale settings
$localeSettings = $settings->getLocaleSettings();

// Get integration settings
$integrationSettings = $settings->getIntegrationSettings();

// Apply settings
$results = $settings->applySettings([
    'theme' => 'ocean',
    'locale' => 'ar',
]);

// Reset to defaults
$results = $settings->resetToDefaults();

// Export settings
$exported = $settings->exportSettings();
// [
//     'theme' => 'ocean',
//     'locale' => 'ar',
//     'exported_at' => '2024-02-26T10:30:00Z'
// ]

// Import settings
$results = $settings->importSettings($exported);

// Validate settings
$errors = $settings->validateSettings([
    'theme' => 'nonexistent',
    'locale' => 'xx',
]);
// [
//     'theme' => "Theme 'nonexistent' does not exist",
//     'locale' => "Locale 'xx' is not available"
// ]

// Get settings for UI rendering
$uiSettings = $settings->getSettingsForUI();
// [
//     'current' => ['theme' => 'ocean', 'locale' => 'ar'],
//     'themes' => [...],
//     'locales' => [...],
//     'integration' => [...]
// ]

// Clear all caches
$settings->clearAllCaches();
```

---

## 👤 User Preferences

### UserPreferences

```php
use Canvastack\Canvastack\Support\Integration\UserPreferences;

$preferences = app(UserPreferences::class);

// Get all preferences
$all = $preferences->all();

// Get a preference
$theme = $preferences->get('theme');
$locale = $preferences->get('locale', 'en'); // with default

// Set a preference
$preferences->set('theme', 'ocean');

// Set multiple preferences
$preferences->setMany([
    'theme' => 'ocean',
    'locale' => 'ar',
    'dark_mode' => true,
]);

// Check if preference exists
if ($preferences->has('theme')) {
    // ...
}

// Remove a preference
$preferences->forget('custom_key');

// Clear all preferences
$preferences->clear();

// Reset to defaults
$preferences->reset();

// Convenience methods
$preferences->setTheme('ocean');
$theme = $preferences->getTheme();

$preferences->setLocale('ar');
$locale = $preferences->getLocale();

$preferences->setDarkMode(true);
$darkMode = $preferences->getDarkMode();

// Export preferences
$exported = $preferences->export();

// Import preferences
$preferences->import($exported);

// Configure storage driver
$preferences->setDriver('both'); // session, cookie, both
```

---

## 🎨 RTL Support

### Automatic RTL Handling

The integration automatically handles RTL for supported locales:

```php
// Arabic locale
$integration->getLocalizedThemeCss('default', 'ar');
// Includes:
// - [dir="rtl"] styles
// - Flipped margins/paddings
// - Flipped floats
// - Flipped text alignment
// - Icon flip utilities
// - Locale-specific fonts (Noto Sans Arabic)

// HTML attributes
$attributes = $integration->getHtmlAttributes('ar');
// ['lang' => 'ar', 'dir' => 'rtl', 'class' => 'rtl theme-default']
```

### RTL Locales

Supported RTL locales:
- `ar` - Arabic
- `he` - Hebrew
- `fa` - Persian
- `ur` - Urdu

### Locale-Specific Fonts

Automatically included for:
- **Arabic**: Noto Sans Arabic, Tajawal, Cairo
- **Hebrew**: Noto Sans Hebrew, Rubik
- **Persian**: Noto Sans Arabic, Vazir, Samim
- **Japanese**: Noto Sans JP, Hiragino Sans
- **Chinese**: Noto Sans SC, PingFang SC
- **Korean**: Noto Sans KR, Malgun Gothic

---

## 🧪 Testing

### Unit Tests

```bash
# Test integration
./vendor/bin/phpunit tests/Unit/Support/Integration/ThemeLocaleIntegrationTest.php

# Test performance
./vendor/bin/phpunit tests/Unit/Support/Integration/ThemeLocalePerformanceTest.php

# Test settings
./vendor/bin/phpunit tests/Unit/Support/Integration/UnifiedSettingsManagerTest.php

# Test preferences
./vendor/bin/phpunit tests/Unit/Support/Integration/UserPreferencesTest.php
```

### Feature Tests

```bash
# Test RTL integration
./vendor/bin/phpunit tests/Feature/Integration/RtlThemeIntegrationTest.php
```

### All Integration Tests

```bash
./vendor/bin/phpunit tests/Unit/Support/Integration/ tests/Feature/Integration/
```

---

## 💡 Tips & Best Practices

### 1. Cache Warmup

Warm up cache on application boot for better performance:

```php
// In AppServiceProvider::boot()
if (app()->environment('production')) {
    $performance = app(ThemeLocalePerformance::class);
    $performance->preloadCommon();
}
```

### 2. Optimize Switching

Preload next theme/locale before switching:

```php
// Before theme switch
$performance->optimizeThemeSwitch('ocean');
$themeManager->setCurrentTheme('ocean');

// Before locale switch
$performance->optimizeLocaleSwitch('ar');
$localeManager->setLocale('ar');
```

### 3. User Preferences

Always use UserPreferences for persistent settings:

```php
// Save user choice
$preferences->setTheme($request->input('theme'));
$preferences->setLocale($request->input('locale'));

// Apply on next request
$themeManager->setCurrentTheme($preferences->getTheme());
$localeManager->setLocale($preferences->getLocale());
```

### 4. Validation

Always validate settings before applying:

```php
$errors = $settings->validateSettings($request->all());

if (empty($errors)) {
    $settings->applySettings($request->all());
} else {
    return back()->withErrors($errors);
}
```

### 5. Cache Management

Clear cache when themes or locales change:

```php
// After adding new theme
$integration->clearCache();

// After updating translations
$localeManager->clearCache();

// Clear all
$settings->clearAllCaches();
```

---

## 🎭 Common Patterns

### Pattern 1: Settings Page

```php
public function showSettings(UnifiedSettingsManager $settings)
{
    return view('settings', [
        'settings' => $settings->getSettingsForUI(),
    ]);
}

public function updateSettings(
    Request $request,
    UnifiedSettingsManager $settings,
    UserPreferences $preferences
) {
    $errors = $settings->validateSettings($request->all());

    if (!empty($errors)) {
        return back()->withErrors($errors);
    }

    $results = $settings->applySettings($request->all());

    // Save to user preferences
    $preferences->setMany($request->all());

    return back()->with('success', 'Settings updated successfully');
}
```

### Pattern 2: Theme Switcher

```php
public function switchTheme(
    Request $request,
    ThemeManager $themeManager,
    UserPreferences $preferences,
    ThemeLocalePerformance $performance
) {
    $theme = $request->input('theme');

    // Validate
    if (!$themeManager->has($theme)) {
        return response()->json(['error' => 'Invalid theme'], 400);
    }

    // Optimize
    $performance->optimizeThemeSwitch($theme);

    // Apply
    $themeManager->setCurrentTheme($theme);

    // Save preference
    $preferences->setTheme($theme);

    return response()->json(['success' => true]);
}
```

### Pattern 3: Locale Switcher

```php
public function switchLocale(
    Request $request,
    LocaleManager $localeManager,
    UserPreferences $preferences,
    ThemeLocalePerformance $performance
) {
    $locale = $request->input('locale');

    // Validate
    if (!$localeManager->isAvailable($locale)) {
        return response()->json(['error' => 'Invalid locale'], 400);
    }

    // Optimize
    $performance->optimizeLocaleSwitch($locale);

    // Apply
    $localeManager->setLocale($locale);

    // Save preference
    $preferences->setLocale($locale);

    return response()->json(['success' => true]);
}
```

---

## 🔗 Related Documentation

- [Theme System](../theme/theme-system.md)
- [Internationalization](../i18n/implementation-guide.md)
- [RTL Support](../i18n/rtl-support.md)
- [Performance Optimization](../performance/optimization.md)

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Complete

