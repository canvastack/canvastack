# Test Helper Traits

This guide documents the test helper traits available in the CanvaStack test suite. These traits provide reusable functionality for common test scenarios.

## 📦 Location

- **Traits Directory**: `tests/Concerns/`
- **Available Traits**:
  - `CreatesTestUsers` - User creation and authentication
  - `InteractsWithThemes` - Theme management
  - `InteractsWithRoutes` - Route registration
  - `InteractsWithViteAssets` - Vite asset management
  - `CreatesTableBuilders` - TableBuilder creation

---

## 🎯 Overview

Test helper traits encapsulate common test setup patterns and provide reusable methods for:
- Creating test users with various roles
- Managing themes in tests
- Registering routes for testing
- Handling Vite assets in Orchestra Testbench
- Creating properly configured TableBuilder instances

---

## 📚 Available Traits

### 1. CreatesTestUsers

**Purpose**: Create test users and handle authentication in tests.

**Location**: `tests/Concerns/CreatesTestUsers.php`

**Usage**:

```php
use Canvastack\Canvastack\Tests\Concerns\CreatesTestUsers;

class MyTest extends TestCase
{
    use CreatesTestUsers;
    
    public function test_user_can_access_dashboard()
    {
        $user = $this->actingAsTestUser();
        
        $response = $this->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
    }
}
```

**Available Methods**:

| Method | Description | Returns |
|--------|-------------|---------|
| `createTestUser($attributes)` | Create a test user | `Authenticatable` |
| `createAdminUser($attributes)` | Create an admin user | `Authenticatable` |
| `createGuestUser()` | Create a guest user | `Authenticatable` |
| `actingAsTestUser($attributes)` | Authenticate as test user | `Authenticatable` |
| `actingAsAdmin($attributes)` | Authenticate as admin | `Authenticatable` |

**Examples**:

```php
// Create a basic test user
$user = $this->createTestUser();

// Create a user with custom attributes
$user = $this->createTestUser([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'role' => 'editor',
]);

// Create and authenticate as admin
$admin = $this->actingAsAdmin();

// Test theme preference
$user = $this->createTestUser();
$user->setThemePreference('dark');
$this->assertEquals('dark', $user->getThemePreference());
```

---

### 2. InteractsWithThemes

**Purpose**: Manage themes and theme-related operations in tests.

**Location**: `tests/Concerns/InteractsWithThemes.php`

**Usage**:

```php
use Canvastack\Canvastack\Tests\Concerns\InteractsWithThemes;

class ThemeTest extends TestCase
{
    use InteractsWithThemes;
    
    public function test_can_activate_theme()
    {
        $this->registerTestTheme('custom-theme');
        $this->activateTheme('custom-theme');
        
        $this->assertThemeActive('custom-theme');
    }
}
```

**Available Methods**:

| Method | Description | Returns |
|--------|-------------|---------|
| `getThemeManager()` | Get theme manager instance | `ThemeManager` |
| `createTestTheme($name, $overrides)` | Create theme config | `array` |
| `registerTestTheme($name, $overrides)` | Register a test theme | `void` |
| `activateTheme($name)` | Activate a theme | `void` |
| `assertThemeActive($name)` | Assert theme is active | `void` |
| `assertThemeExists($name)` | Assert theme exists | `void` |
| `assertThemeDoesNotExist($name)` | Assert theme doesn't exist | `void` |
| `clearThemeCache()` | Clear theme cache | `void` |

**Examples**:

```php
// Create a custom theme
$theme = $this->createTestTheme('my-theme', [
    'config' => [
        'colors' => [
            'primary' => '#ff0000',
        ],
    ],
]);

// Register and activate
$this->registerTestTheme('my-theme');
$this->activateTheme('my-theme');

// Assertions
$this->assertThemeActive('my-theme');
$this->assertThemeExists('my-theme');

// Clear cache
$this->clearThemeCache();
```

---

### 3. InteractsWithRoutes

**Purpose**: Register routes needed for testing.

**Location**: `tests/Concerns/InteractsWithRoutes.php`

**Usage**:

```php
use Canvastack\Canvastack\Tests\Concerns\InteractsWithRoutes;

class ControllerTest extends TestCase
{
    use InteractsWithRoutes;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Register all test routes
        $this->registerAllTestRoutes();
    }
    
    public function test_can_access_dashboard()
    {
        $response = $this->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
    }
}
```

**Available Methods**:

| Method | Description | Returns |
|--------|-------------|---------|
| `registerAdminRoutes()` | Register admin routes | `void` |
| `registerAuthRoutes()` | Register auth routes | `void` |
| `registerThemeRoutes()` | Register theme routes | `void` |
| `registerAllTestRoutes()` | Register all routes | `void` |
| `assertRouteExists($name)` | Assert route exists | `void` |
| `assertRouteDoesNotExist($name)` | Assert route doesn't exist | `void` |

**Examples**:

```php
// Register specific route groups
$this->registerAdminRoutes();
$this->registerAuthRoutes();
$this->registerThemeRoutes();

// Or register all at once
$this->registerAllTestRoutes();

// Assert routes exist
$this->assertRouteExists('admin.dashboard');
$this->assertRouteExists('login');
$this->assertRouteExists('admin.themes.index');

// Test route access
$response = $this->get(route('admin.users.index'));
$response->assertStatus(200);
```

---

### 4. InteractsWithViteAssets

**Purpose**: Manage Vite built assets in Orchestra Testbench environment.

**Location**: `tests/Concerns/InteractsWithViteAssets.php`

**Usage**:

```php
use Canvastack\Canvastack\Tests\Concerns\InteractsWithViteAssets;

class AssetTest extends TestCase
{
    use InteractsWithViteAssets;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Copy Vite assets to testbench public directory
        $this->copyViteAssets();
    }
    
    public function test_vite_assets_are_available()
    {
        $this->assertViteAssetsBuilt();
        $this->assertViteManifestExists();
    }
}
```

**Available Methods**:

| Method | Description | Returns |
|--------|-------------|---------|
| `copyViteAssets()` | Copy assets to testbench | `void` |
| `copyViteManifest($source, $target)` | Copy manifest file | `void` |
| `recursiveCopy($source, $target)` | Copy directory recursively | `void` |
| `getPackagePublicPath()` | Get package public path | `string` |
| `getTestbenchPublicPath()` | Get testbench public path | `string` |
| `assertViteManifestExists()` | Assert manifest exists | `void` |
| `assertViteAssetsBuilt()` | Assert assets are built | `void` |
| `cleanupViteAssets()` | Clean up copied assets | `void` |

**Examples**:

```php
// Copy assets in setUp
protected function setUp(): void
{
    parent::setUp();
    $this->copyViteAssets();
}

// Assert assets are available
$this->assertViteAssetsBuilt();
$this->assertViteManifestExists();

// Get paths
$publicPath = $this->getPackagePublicPath();
$testbenchPath = $this->getTestbenchPublicPath();

// Clean up in tearDown
protected function tearDown(): void
{
    $this->cleanupViteAssets();
    parent::tearDown();
}
```

---

### 5. CreatesTableBuilders

**Purpose**: Create properly configured TableBuilder instances with all dependencies.

**Location**: `tests/Concerns/CreatesTableBuilders.php`

**Usage**:

```php
use Canvastack\Canvastack\Tests\Concerns\CreatesTableBuilders;

class TableTest extends TestCase
{
    use CreatesTableBuilders;
    
    public function test_table_renders_with_model()
    {
        $table = $this->createTableBuilderWithModel(new User());
        $table->setFields(['name:Name', 'email:Email']);
        $table->format();
        
        $html = $table->render();
        
        $this->assertStringContainsString('<table', $html);
    }
}
```

**Available Methods**:

| Method | Description | Returns |
|--------|-------------|---------|
| `createTableBuilder()` | Create basic TableBuilder | `TableBuilder` |
| `createTableBuilderWithModel($model)` | Create with model | `TableBuilder` |
| `createTableBuilderWithCollection($collection)` | Create with collection | `TableBuilder` |
| `createTableBuilderWithData($data)` | Create with array data | `TableBuilder` |
| `createConfiguredTableBuilder($config)` | Create fully configured | `TableBuilder` |
| `assertTableBuilderConfigured($table)` | Assert properly configured | `void` |
| `assertTableBuilderHasModel($table)` | Assert has model | `void` |
| `assertTableBuilderHasData($table)` | Assert has data | `void` |

**Examples**:

```php
// Basic TableBuilder
$table = $this->createTableBuilder();

// With Eloquent model
$table = $this->createTableBuilderWithModel(new User());

// With collection
$collection = collect([
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
]);
$table = $this->createTableBuilderWithCollection($collection);

// With array data
$data = [
    ['name' => 'John', 'email' => 'john@example.com'],
];
$table = $this->createTableBuilderWithData($data);

// Fully configured
$table = $this->createConfiguredTableBuilder([
    'context' => 'admin',
    'model' => new User(),
    'fields' => ['name:Name', 'email:Email'],
    'actions' => [
        'edit' => [
            'label' => 'Edit',
            'icon' => 'edit',
            'url' => fn($row) => route('users.edit', $row->id),
        ],
    ],
    'cache' => 300,
    'eager' => ['profile'],
    'orderBy' => ['created_at', 'desc'],
    'hiddenColumns' => ['password'],
]);

// Assertions
$this->assertTableBuilderConfigured($table);
$this->assertTableBuilderHasModel($table);
```

---

## 🎭 Combining Traits

You can use multiple traits in a single test class:

```php
use Canvastack\Canvastack\Tests\Concerns\CreatesTestUsers;
use Canvastack\Canvastack\Tests\Concerns\InteractsWithThemes;
use Canvastack\Canvastack\Tests\Concerns\InteractsWithRoutes;

class FeatureTest extends TestCase
{
    use CreatesTestUsers;
    use InteractsWithThemes;
    use InteractsWithRoutes;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Register routes
        $this->registerAllTestRoutes();
        
        // Register test theme
        $this->registerTestTheme('test-theme');
    }
    
    public function test_user_can_change_theme()
    {
        $user = $this->actingAsTestUser();
        
        $response = $this->post(route('admin.themes.activate', 'test-theme'));
        
        $response->assertRedirect();
        $this->assertThemeActive('test-theme');
    }
}
```

---

## 💡 Best Practices

### 1. Use Traits in setUp()

Initialize trait functionality in `setUp()` method:

```php
protected function setUp(): void
{
    parent::setUp();
    
    $this->registerAllTestRoutes();
    $this->copyViteAssets();
    $this->registerTestTheme('default');
}
```

### 2. Clean Up in tearDown()

Clean up resources in `tearDown()` method:

```php
protected function tearDown(): void
{
    $this->cleanupViteAssets();
    $this->clearThemeCache();
    
    parent::tearDown();
}
```

### 3. Use Descriptive Test Names

```php
// Good
public function test_admin_can_activate_theme()

// Bad
public function test_theme()
```

### 4. Use Assertions from Traits

```php
// Use trait assertions
$this->assertThemeActive('default');
$this->assertRouteExists('admin.dashboard');
$this->assertViteManifestExists();

// Instead of manual checks
$this->assertEquals('default', $themeManager->current()->getName());
```

### 5. Create Custom Traits for Project-Specific Needs

```php
// tests/Concerns/InteractsWithMyFeature.php
trait InteractsWithMyFeature
{
    protected function setupMyFeature(): void
    {
        // Custom setup logic
    }
}
```

---

## 🧪 Testing the Traits

### Unit Tests for Traits

```php
class CreatesTestUsersTest extends TestCase
{
    use CreatesTestUsers;
    
    public function test_creates_test_user()
    {
        $user = $this->createTestUser();
        
        $this->assertInstanceOf(Authenticatable::class, $user);
        $this->assertEquals('Test User', $user->name);
    }
    
    public function test_creates_admin_user()
    {
        $admin = $this->createAdminUser();
        
        $this->assertEquals('admin', $admin->role);
    }
}
```

---

## 📚 Resources

### Internal Documentation
- [Test Environment Setup](test-environment-setup.md)
- [TestCase Base Class](../../tests/TestCase.php)
- [Trait Source Files](../../tests/Concerns/)

### External Resources
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Orchestra Testbench](https://packages.tools/testbench)

---

**Last Updated**: 2026-02-27  
**Version**: 1.0.0  
**Status**: Published
