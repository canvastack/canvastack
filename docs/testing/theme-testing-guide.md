# Theme Testing Guide

Complete guide for testing theme functionality in CanvaStack.

## 📦 Location

- **Test Files**: `tests/Feature/Http/Controllers/Admin/ThemeControllerTest.php`
- **Test Traits**: `tests/Concerns/InteractsWithThemes.php`, `tests/Concerns/InteractsWithViteAssets.php`
- **Components**: `src/Support/Theme/`, `src/Http/Controllers/Admin/ThemeController.php`

## 🎯 Overview

Theme testing covers:
- Theme loading and validation
- Theme activation and switching
- Theme caching and reloading
- Theme export and preview
- Theme statistics
- Meta tags configuration
- Vite asset integration

## 📖 Test Environment Setup

### Required Setup

All theme tests require:

1. **Vite Assets**: Copy built assets to Orchestra Testbench public directory
2. **Routes**: Define required routes (admin.dashboard, admin.profile, admin.settings, logout)
3. **Theme Registry**: Configure test themes in registry
4. **Test User**: Authenticated user for testing

### Using Test Traits

```php
use Canvastack\Canvastack\Tests\Concerns\InteractsWithThemes;
use Canvastack\Canvastack\Tests\Concerns\InteractsWithViteAssets;
use Canvastack\Canvastack\Tests\Concerns\InteractsWithRoutes;
use Canvastack\Canvastack\Tests\Concerns\CreatesTestUsers;

class ThemeControllerTest extends TestCase
{
    use InteractsWithThemes;
    use InteractsWithViteAssets;
    use InteractsWithRoutes;
    use CreatesTestUsers;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->copyViteAssets();
        $this->setupRequiredRoutes();
        $this->setupThemeRegistry();
        $this->actingAs($this->createTestUser());
    }
}
```

## 🔧 Test Traits Reference

### InteractsWithThemes

Provides theme-related test helpers.

**Methods**:

```php
// Setup theme registry with test themes
$this->setupThemeRegistry();

// Get theme manager instance
$manager = $this->getThemeManager();

// Assert theme exists
$this->assertThemeExists('gradient');

// Assert theme is active
$this->assertThemeIsActive('gradient');

// Get test theme configuration
$config = $this->getTestThemeConfig('gradient');
```

### InteractsWithViteAssets

Handles Vite asset copying for tests.

**Methods**:

```php
// Copy Vite assets to testbench public directory
$this->copyViteAssets();

// Copy specific asset
$this->copyAsset('build/manifest.json', 'public/build/manifest.json');

// Recursively copy directory
$this->recursiveCopy($source, $target);
```

### InteractsWithRoutes

Defines required routes for testing.

**Methods**:

```php
// Setup all required routes
$this->setupRequiredRoutes();

// Setup specific route
$this->setupRoute('admin.dashboard', '/admin/dashboard', 'canvastack::admin.dashboard');
```

## 📝 Common Test Patterns

### Pattern 1: Testing Theme Index Page

```php
public function test_index_displays_theme_management_page(): void
{
    $response = $this->get(route('admin.themes.index'));

    $response->assertStatus(200);
    $response->assertViewIs('canvastack::admin.themes.index');
    $response->assertViewHas('table');
    $response->assertViewHas('meta');
    $response->assertViewHas('themes');
    $response->assertViewHas('currentTheme');
}
```

### Pattern 2: Testing Theme Activation

```php
public function test_activate_switches_theme(): void
{
    $response = $this->post(route('admin.themes.activate', 'forest'));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    $this->assertThemeIsActive('forest');
}
```

### Pattern 3: Testing Theme Validation

```php
public function test_show_returns_404_for_invalid_theme(): void
{
    $response = $this->get(route('admin.themes.show', 'nonexistent'));

    $response->assertStatus(404);
}
```

### Pattern 4: Testing Cache Operations

```php
public function test_clear_cache_clears_theme_cache(): void
{
    // Activate theme to populate cache
    $this->post(route('admin.themes.activate', 'gradient'));
    
    // Clear cache
    $response = $this->post(route('admin.themes.clear-cache'));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    // Verify cache is cleared
    $manager = $this->getThemeManager();
    $this->assertEmpty($manager->getCache()->getAll());
}
```

### Pattern 5: Testing Meta Tags

```php
public function test_index_configures_meta_tags(): void
{
    $response = $this->get(route('admin.themes.index'));

    $response->assertStatus(200);
    
    $meta = $response->viewData('meta');
    $this->assertInstanceOf(MetaTags::class, $meta);
    
    $tags = $meta->tags('text');
    $this->assertStringContainsString('Themes', $tags['title']);
    $this->assertStringContainsString('theme', $tags['meta_keywords']);
}
```

## 🧪 Testing Theme Components

### Testing ThemeManager

```php
public function test_theme_manager_loads_themes(): void
{
    $manager = $this->getThemeManager();
    
    $themes = $manager->all();
    $this->assertNotEmpty($themes);
    
    $theme = $manager->get('gradient');
    $this->assertInstanceOf(Theme::class, $theme);
    $this->assertEquals('gradient', $theme->getName());
}
```

### Testing ThemeLoader

```php
public function test_theme_loader_loads_from_registry(): void
{
    $loader = app(ThemeLoader::class);
    
    $themes = $loader->loadFromRegistry();
    $this->assertNotEmpty($themes);
    
    foreach ($themes as $theme) {
        $this->assertInstanceOf(Theme::class, $theme);
    }
}
```

### Testing ThemeValidator

```php
public function test_theme_validator_validates_config(): void
{
    $validator = new ThemeValidator();
    
    $config = $this->getTestThemeConfig('gradient');
    $this->assertTrue($validator->validate($config));
    
    $invalidConfig = ['name' => 'test'];
    $this->assertFalse($validator->validate($invalidConfig));
    $this->assertNotEmpty($validator->getErrors());
}
```

## 🎮 Testing Theme Controller Actions

### Index Action

Tests theme listing page with table and meta tags.

```php
public function test_index_displays_theme_management_page(): void
{
    $response = $this->get(route('admin.themes.index'));

    $response->assertStatus(200);
    $response->assertViewIs('canvastack::admin.themes.index');
    $response->assertViewHas(['table', 'meta', 'themes', 'currentTheme']);
}
```

### Show Action

Tests individual theme details page.

```php
public function test_show_displays_theme_details(): void
{
    $response = $this->get(route('admin.themes.show', 'gradient'));

    $response->assertStatus(200);
    $response->assertViewIs('canvastack::admin.themes.show');
    $response->assertViewHas('theme');
    $response->assertViewHas('meta');
}
```

### Activate Action

Tests theme activation with session and cache updates.

```php
public function test_activate_switches_theme(): void
{
    $response = $this->post(route('admin.themes.activate', 'forest'));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Theme activated successfully');
    
    $manager = $this->getThemeManager();
    $this->assertEquals('forest', $manager->current()->getName());
}
```

### Clear Cache Action

Tests cache clearing functionality.

```php
public function test_clear_cache_clears_theme_cache(): void
{
    $response = $this->post(route('admin.themes.clear-cache'));

    $response->assertRedirect();
    $response->assertSessionHas('success');
}
```

### Reload Action

Tests theme reloading from filesystem.

```php
public function test_reload_reloads_themes(): void
{
    $response = $this->post(route('admin.themes.reload'));

    $response->assertRedirect();
    $response->assertSessionHas('success');
}
```

### Export Action

Tests theme export as JSON download.

```php
public function test_export_downloads_theme_json(): void
{
    $response = $this->get(route('admin.themes.export', 'gradient'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/json');
    $response->assertHeader('Content-Disposition', 'attachment; filename="gradient.json"');
}
```

### Preview Action

Tests theme preview API endpoint.

```php
public function test_preview_returns_theme_data(): void
{
    $response = $this->get(route('admin.themes.preview', 'gradient'));

    $response->assertStatus(200);
    $response->assertJson([
        'name' => 'gradient',
        'display_name' => 'Gradient Theme',
    ]);
}
```

### Stats Action

Tests theme statistics API endpoint.

```php
public function test_stats_returns_theme_statistics(): void
{
    $response = $this->get(route('admin.themes.stats'));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'total',
        'active',
        'available',
    ]);
}
```

## 🔍 Common Issues and Solutions

### Issue 1: Vite Manifest Not Found

**Error**: `Vite manifest not found at: .../public/build/manifest.json`

**Solution**: Use `InteractsWithViteAssets` trait and call `$this->copyViteAssets()` in setUp().

```php
protected function setUp(): void
{
    parent::setUp();
    $this->copyViteAssets();
}
```

### Issue 2: Route Not Defined

**Error**: `Route [admin.dashboard] not defined`

**Solution**: Use `InteractsWithRoutes` trait and call `$this->setupRequiredRoutes()` in setUp().

```php
protected function setUp(): void
{
    parent::setUp();
    $this->setupRequiredRoutes();
}
```

### Issue 3: Theme Not Found

**Error**: `Theme 'gradient' not found`

**Solution**: Use `InteractsWithThemes` trait and call `$this->setupThemeRegistry()` in setUp().

```php
protected function setUp(): void
{
    parent::setUp();
    $this->setupThemeRegistry();
}
```

### Issue 4: Empty Cache Returns Early

**Error**: Theme manager returns early with empty cache

**Solution**: Fixed in ThemeManager - now properly checks for empty cache using `!empty()`.

### Issue 5: User Model Method Missing

**Error**: `Call to undefined method setThemePreference()`

**Solution**: Fixed in ThemeController - now checks `method_exists()` before calling.

## 💡 Best Practices

### 1. Use Test Traits

Always use provided test traits for consistent setup:

```php
use InteractsWithThemes;
use InteractsWithViteAssets;
use InteractsWithRoutes;
use CreatesTestUsers;
```

### 2. Setup in setUp() Method

Perform all setup in setUp() method, not in individual tests:

```php
protected function setUp(): void
{
    parent::setUp();
    
    $this->copyViteAssets();
    $this->setupRequiredRoutes();
    $this->setupThemeRegistry();
    $this->actingAs($this->createTestUser());
}
```

### 3. Test Both Success and Failure Cases

Always test both valid and invalid scenarios:

```php
public function test_show_displays_theme_details(): void
{
    $response = $this->get(route('admin.themes.show', 'gradient'));
    $response->assertStatus(200);
}

public function test_show_returns_404_for_invalid_theme(): void
{
    $response = $this->get(route('admin.themes.show', 'nonexistent'));
    $response->assertStatus(404);
}
```

### 4. Verify View Data

Always verify that views receive expected data:

```php
$response->assertViewHas('table');
$response->assertViewHas('meta');
$response->assertViewHas('themes');
```

### 5. Test Meta Tags Configuration

Verify meta tags are properly configured:

```php
$meta = $response->viewData('meta');
$this->assertInstanceOf(MetaTags::class, $meta);

$tags = $meta->tags('text');
$this->assertStringContainsString('expected', $tags['title']);
```

## 🎭 Testing Theme Formats

### Nested Format (File Format)

```php
$config = [
    'name' => 'gradient',
    'display_name' => 'Gradient Theme',
    'version' => '1.0.0',
    'author' => 'CanvaStack',
    'config' => [
        'colors' => [
            'primary' => '#6366f1',
            'secondary' => '#8b5cf6',
            'accent' => '#a855f7',
        ],
    ],
];
```

### Flat Format (Registry Format)

```php
$config = [
    'name' => 'gradient',
    'display_name' => 'Gradient Theme',
    'version' => '1.0.0',
    'author' => 'CanvaStack',
    'colors' => [
        'primary' => '#6366f1',
        'secondary' => '#8b5cf6',
        'accent' => '#a855f7',
    ],
];
```

Both formats are now supported by ThemeValidator and ThemeLoader.

## 📚 Related Documentation

- [Test Environment Setup](test-environment-setup.md) - Complete test environment configuration
- [Test Helper Traits](test-helper-traits.md) - Detailed trait documentation
- [Theme System](../features/theming.md) - Theme system overview
- [Theme Controller](../api/theme-controller.md) - Controller API reference

---

**Last Updated**: 2026-02-27  
**Version**: 1.0.0  
**Status**: Published
