# Test Environment Setup Guide

This guide documents the test environment setup patterns and common issues discovered during the CanvaStack test suite development.

## 📦 Location

- **TestCase Base Class**: `tests/TestCase.php`
- **Test Migrations**: `tests/database/migrations/`
- **Feature Tests**: `tests/Feature/`
- **Unit Tests**: `tests/Unit/`

---

## 🎯 Overview

The CanvaStack test suite uses Orchestra Testbench to provide a Laravel testing environment for the package. This guide covers the setup patterns, common issues, and solutions discovered during test development.

---

## 🔧 Base TestCase Configuration

### Orchestra Testbench Setup

```php
abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run test migrations automatically
        $this->loadTestMigrations(__DIR__ . '/database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            \Canvastack\Canvastack\CanvastackServiceProvider::class,
            \Canvastack\Canvastack\Providers\ThemeServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup application key
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        // Setup SQLite in-memory database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup cache
        $app['config']->set('cache.default', 'array');
    }
}
```

---

## 🚨 Common Issues and Solutions

### Issue 1: Vite Manifest Not Found

**Problem**: Orchestra Testbench uses its own public directory (`vendor/orchestra/testbench-core/laravel/public/`), not the package's public directory.

**Error**:
```
Vite manifest not found at: .../vendor/orchestra/testbench-core/laravel/public/build/manifest.json
```

**Root Cause**:
- Package's built assets are in `packages/canvastack/canvastack/public/build/`
- Testbench looks in `vendor/orchestra/testbench-core/laravel/public/build/`
- Vite 5+ stores manifest in `.vite/manifest.json` subdirectory

**Solution**: Copy assets in `getEnvironmentSetUp()`:

```php
protected function getEnvironmentSetUp($app): void
{
    // ... other config ...
    
    // Configure Vite for tests
    $app['config']->set('vite.build_path', 'build');
    
    // Set public path to package public directory
    $publicPath = realpath(__DIR__ . '/../public');
    $app->instance('path.public', $publicPath);
    
    // Copy built assets to Orchestra Testbench public directory
    $this->copyViteAssets();
}

protected function copyViteAssets(): void
{
    $sourceDir = __DIR__ . '/../public/build';
    $targetDir = __DIR__ . '/../vendor/orchestra/testbench-core/laravel/public/build';
    
    if (!is_dir($sourceDir)) {
        return;
    }
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $this->recursiveCopy($sourceDir, $targetDir);
    
    // Copy manifest to both locations for Vite 5+ compatibility
    $viteManifest = $sourceDir . '/.vite/manifest.json';
    $rootManifest = $targetDir . '/manifest.json';
    
    if (file_exists($viteManifest) && !file_exists($rootManifest)) {
        copy($viteManifest, $rootManifest);
    }
}

protected function recursiveCopy(string $source, string $target): void
{
    $dir = opendir($source);
    
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $sourcePath = $source . '/' . $file;
        $targetPath = $target . '/' . $file;
        
        if (is_dir($sourcePath)) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
            $this->recursiveCopy($sourcePath, $targetPath);
        } else {
            copy($sourcePath, $targetPath);
        }
    }
    
    closedir($dir);
}
```

---

### Issue 2: Missing Routes

**Problem**: Views reference routes that aren't defined in test environment.

**Error**:
```
Route [admin.dashboard] not defined
Route [admin.profile] not defined
```

**Solution**: Define routes in `routes/web.php` or test setup:

```php
// In routes/web.php
Route::get('/admin/dashboard', function () {
    return view('canvastack::admin.dashboard');
})->middleware(['web'])->name('admin.dashboard');

Route::get('/admin/profile', function () {
    return view('canvastack::admin.profile');
})->middleware(['web'])->name('admin.profile');

Route::get('/admin/settings', function () {
    return view('canvastack::admin.settings');
})->middleware(['web'])->name('admin.settings');

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/');
})->middleware(['web'])->name('logout');
```

---

### Issue 3: Theme Registry Not Loading

**Problem**: Theme manager doesn't load themes from registry due to empty cache check.

**Error**:
```
Theme 'gradient' not found
Theme 'forest' not found
```

**Root Causes**:

1. **Empty Cache Returns Early**:
```php
// WRONG - Empty array [] is truthy!
if ($themes = $this->themeCache->getAll()) {
    return $this; // Returns early, never loads from registry
}

// CORRECT - Properly check for empty
$cachedThemes = $this->themeCache->getAll();
if (!empty($cachedThemes)) {
    return $this;
}
```

2. **Registry Structure Mismatch**:
```php
// WRONG - Flat structure fails validation
[
    'name' => 'gradient',
    'colors' => [...], // At root level
]

// CORRECT - Nested structure passes validation
[
    'name' => 'gradient',
    'config' => [
        'colors' => [...], // Under 'config' key
    ],
]
```

**Solution**: Configure theme registry in `getEnvironmentSetUp()`:

```php
protected function getEnvironmentSetUp($app): void
{
    // ... other config ...
    
    // Setup theme configuration
    $app['config']->set('canvastack-ui.theme.registry', [
        [
            'name' => 'default',
            'display_name' => 'Default Theme',
            'version' => '1.0.0',
            'author' => 'CanvaStack',
            'description' => 'Default CanvaStack theme',
            'config' => [ // IMPORTANT: Nested under 'config' key
                'colors' => [
                    'primary' => '#6366f1',
                    'secondary' => '#8b5cf6',
                    'accent' => '#a855f7',
                    'background' => '#ffffff',
                    'text' => '#111827',
                ],
                'fonts' => [
                    'sans' => 'Inter, system-ui, sans-serif',
                    'mono' => 'JetBrains Mono, monospace',
                ],
                'layout' => [
                    'container' => '1280px',
                    'spacing' => '1rem',
                ],
                'dark_mode' => [
                    'enabled' => true,
                    'default' => 'light',
                ],
            ],
        ],
    ]);
}
```

---

### Issue 4: MetaTags Methods Not Working

**Problem**: `keywords()` and `description()` methods ignore provided values.

**Error**:
```
Failed asserting that '<meta name="keywords" content="CanvaStack, Laravel, CMS" />' contains "themes"
```

**Root Cause**: Methods use `renderString()` with `$metaPreferenceName = true`, which ignores provided values.

**Solution**: Check if value provided before calling `renderString()`:

```php
public function keywords(?string $string = null): self
{
    if (!empty($string)) {
        $str = $string; // Use provided value directly
    } else {
        $str = $this->renderString($string, 'meta_keywords', true);
    }
    
    $this->keywords = $str;
    return $this;
}

public function description(?string $string = null): self
{
    if (!empty($string)) {
        $str = $string; // Use provided value directly
    } else {
        $str = $this->renderString($string, 'meta_description', true);
    }
    
    $this->description = $str;
    return $this;
}
```

---

### Issue 5: Missing View Data

**Problem**: Controller doesn't pass all required data to view.

**Error**:
```
Failed asserting that the data contains the key [themes].
Failed asserting that the data contains the key [currentTheme].
```

**Solution**: Pass all required data to view:

```php
return view('canvastack::admin.themes.index', [
    'table' => $table,
    'meta' => $meta,
    'stats' => $stats,
    'themes' => $themes,              // Add for test compatibility
    'currentTheme' => $themeManager->current()->getName(), // Add
]);
```

---

### Issue 6: User Model Method Missing

**Problem**: Test user model doesn't have methods that production user has.

**Error**:
```
Session is missing expected key [success].
```

**Root Cause**: Controller calls `$user->setThemePreference($theme)` but TestUser doesn't have this method.

**Solution**: Use `method_exists()` check:

```php
if (auth()->check()) {
    $user = auth()->user();
    if (method_exists($user, 'setThemePreference')) {
        $user->setThemePreference($theme);
        $user->save();
    }
}
```

---

## 📋 Test Environment Checklist

Before running tests, ensure:

### Build Assets
- [ ] Run `npm run build` to generate Vite assets
- [ ] Verify `public/build/manifest.json` or `public/build/.vite/manifest.json` exists
- [ ] Check that CSS and JS files are in `public/build/assets/`

### Database Setup
- [ ] Test migrations exist in `tests/database/migrations/`
- [ ] Tables are created in correct order
- [ ] Foreign key constraints are satisfied

### Configuration
- [ ] Service providers are registered in `getPackageProviders()`
- [ ] Environment variables are set in `getEnvironmentSetUp()`
- [ ] Theme registry is configured with proper structure
- [ ] Routes are defined for all view references

### Dependencies
- [ ] All required packages are installed (`composer install`)
- [ ] PHPUnit is available (`./vendor/bin/phpunit`)
- [ ] Orchestra Testbench is installed

---

## 🧪 Running Tests

### Run All Tests
```bash
./vendor/bin/phpunit
```

### Run Specific Test Class
```bash
./vendor/bin/phpunit tests/Feature/Http/Controllers/Admin/ThemeControllerTest.php
```

### Run Specific Test Method
```bash
./vendor/bin/phpunit --filter test_index_displays_theme_management_page
```

### Run with Coverage
```bash
./vendor/bin/phpunit --coverage-html coverage
```

### Run with Testdox
```bash
./vendor/bin/phpunit --testdox
```

---

## 💡 Best Practices

### 1. Use Helper Methods

Create helper methods in TestCase for common operations:

```php
protected function createTableBuilder(): TableBuilder
{
    $schemaInspector = new SchemaInspector();
    $columnValidator = new ColumnValidator($schemaInspector);
    $filterBuilder = new FilterBuilder($columnValidator);
    $queryOptimizer = new QueryOptimizer($filterBuilder, $columnValidator);

    return new TableBuilder(
        $queryOptimizer,
        $filterBuilder,
        $schemaInspector,
        $columnValidator
    );
}
```

### 2. Use Test Migrations

Store test-specific migrations in `tests/database/migrations/`:

```php
protected function loadTestMigrations(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $files = glob($path . '/*.php');
    sort($files);

    foreach ($files as $file) {
        $migration = include $file;
        
        try {
            $migration->up();
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                continue;
            }
            throw $e;
        }
    }
}
```

### 3. Use RefreshDatabase Trait

Always use `RefreshDatabase` trait for database tests:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;
    
    // Tests...
}
```

### 4. Mock External Dependencies

Mock external services and APIs:

```php
public function test_external_api_call()
{
    Http::fake([
        'api.example.com/*' => Http::response(['data' => 'test'], 200),
    ]);
    
    // Test code...
}
```

### 5. Use Factories for Test Data

Create factories for consistent test data:

```php
User::factory()->create([
    'name' => 'Test User',
    'email' => 'test@example.com',
]);
```

---

## 🔍 Debugging Tests

### Enable Debug Output

```php
public function test_something()
{
    $this->withoutExceptionHandling(); // Show full stack traces
    
    // Test code...
}
```

### Dump Variables

```php
public function test_something()
{
    $result = $this->someMethod();
    
    dump($result); // Output to console
    dd($result);   // Dump and die
    
    // Test code...
}
```

### Check Database State

```php
public function test_something()
{
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
    
    $this->assertDatabaseMissing('users', [
        'email' => 'missing@example.com',
    ]);
}
```

---

## 📚 Resources

### Internal Documentation
- [TestCase Base Class](../../tests/TestCase.php)
- [ThemeController Test](../../tests/Feature/Http/Controllers/Admin/ThemeControllerTest.php)
- [Test Completion Report](../../../.kiro/specs/unit-test-fixes/tasks-theme-controller-completion.md)

### External Resources
- [Orchestra Testbench Documentation](https://packages.tools/testbench)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Vite Laravel Plugin](https://laravel.com/docs/vite)

---

**Last Updated**: 2026-02-27  
**Version**: 1.0.0  
**Status**: Published
