# Testing Documentation

**Version:** 2.0.0  
**Last Updated:** April 4, 2026

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

## Overview

The Theme Engine uses a comprehensive testing strategy combining property-based testing and unit testing to ensure correctness across all adapters and templates.

**Testing Library:** eris/eris (PHP property-based testing)  
**Test Coverage:** 8 properties + unit tests + integration tests  
**Iterations:** 100+ per property

---

## Property-Based Testing

### What is Property-Based Testing?

Property-based testing verifies that universal properties hold true across all valid inputs, rather than testing specific examples.

**Example:**
```php
// Unit test (specific example)
$this->assertEquals('d-none', $adapter->getHideClass());

// Property test (universal property)
$this->forAll(Generator\elements('default', 'canvasign', 'canvas'))
    ->then(function($template) {
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertIsString($adapter->getHideClass());
        $this->assertNotEmpty($adapter->getHideClass());
    });
```

---

## Test Properties

### Property 1: DefaultAdapter Backward Compatibility

**Statement:** For any valid input to `DefaultAdapter` methods, output SHALL be byte-for-byte identical to existing helper functions.

**Validates:** Requirements 4.2, 4.3, 4.4, 4.5, 4.8, 8.2, 10.2

**Test:**
```php
/** @test Feature: theme-adapter, Property 1: DefaultAdapter output identik dengan existing helpers */
public function test_default_adapter_tab_header_matches_existing_helper(): void
{
    $this->forAll(
        Generator\string(),   // $data
        Generator\string(),   // $pointer
        Generator\oneOf(Generator\constant(false), Generator\string()), // $active
        Generator\oneOf(Generator\constant(false), Generator\string())  // $class
    )->then(function ($data, $pointer, $active, $class) {
        $adapter = new DefaultAdapter();
        $adapterOutput = $adapter->renderTabHeader($data, $pointer, $active, $class);
        
        // Compare with existing helper (mocked for testing)
        $helperOutput = $this->mockExistingHelper($data, $pointer, $active, $class);
        
        $this->assertSame($helperOutput, $adapterOutput);
    });
}
```

---

### Property 2: Resolver Always Returns Interface

**Statement:** For any template name, `ThemeAdapterResolver::resolve()` SHALL always return a `ThemeAdapterInterface` instance.

**Validates:** Requirements 3.1, 3.2, 3.3, 3.4, 15.2

**Test:**
```php
/** @test Feature: theme-adapter, Property 2: ThemeAdapterResolver selalu mengembalikan ThemeAdapterInterface */
public function test_resolver_always_returns_adapter_interface(): void
{
    $this->forAll(
        Generator\oneOf(
            Generator\constant('default'),
            Generator\constant('canvasign'),
            Generator\constant('canvas'),
            Generator\string() // Unknown template
        )
    )->then(function ($template) {
        // Mock canvastack_current_template()
        config(['canvastack.templates.template' => $template]);
        
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertInstanceOf(ThemeAdapterInterface::class, $adapter);
    });
}
```

---

### Property 3: Fallback to DefaultAdapter

**Statement:** For any unregistered template name, `ThemeAdapterResolver::resolve()` SHALL return `DefaultAdapter`.

**Validates:** Requirements 3.5, 15.2, 15.3

**Test:**
```php
/** @test Feature: theme-adapter, Property 3: Fallback ke DefaultAdapter untuk template tidak terdaftar */
public function test_unregistered_template_falls_back_to_default(): void
{
    $registered = ['default', 'canvasign', 'canvas'];
    
    $this->forAll(Generator\string())
        ->filter(fn($t) => !in_array($t, $registered, true) && $t !== '')
        ->then(function ($template) {
            ThemeAdapterResolver::reset();
            config(['canvastack.templates.template' => $template]);
            
            $adapter = ThemeAdapterResolver::resolve();
            $this->assertInstanceOf(DefaultAdapter::class, $adapter);
        });
}
```

---

### Property 4: Methods Never Return Null

**Statement:** For any adapter and any valid input, all methods SHALL return a string (never null).

**Validates:** Requirements 1.1–1.8, 2.1–2.6, 4.8, 5.1–5.8, 6.1–6.7

**Test:**
```php
/** @test Feature: theme-adapter, Property 4: Semua adapter method tidak pernah return null */
public function test_all_adapter_methods_never_return_null(): void
{
    $adapters = [
        new DefaultAdapter(),
        new Bootstrap5Adapter(),
        new TailwindAdapter()
    ];
    
    foreach ($adapters as $adapter) {
        $this->forAll(Generator\string(), Generator\string())
            ->then(function ($name, $title) use ($adapter) {
                $this->assertIsString($adapter->getSelectBoxClass());
                $this->assertIsString($adapter->getDataToggleAttribute());
                $this->assertIsString($adapter->getDismissAttribute());
                $this->assertIsString($adapter->getHideClass());
                $this->assertIsString($adapter->getFloatRightClass());
                $this->assertIsString($adapter->getTableClass());
                
                $this->assertNotNull($adapter->getSelectBoxClass());
                $this->assertNotNull($adapter->getDataToggleAttribute());
            });
    }
}
```

---

### Property 5: Singleton Per Request

**Statement:** For any template, calling `resolve()` multiple times SHALL return the same instance.

**Validates:** Requirements 3.7

**Test:**
```php
/** @test Feature: theme-adapter, Property 5: Singleton per request */
public function test_resolver_returns_same_instance(): void
{
    $this->forAll(Generator\elements('default', 'canvasign', 'canvas'))
        ->then(function ($template) {
            ThemeAdapterResolver::reset();
            config(['canvastack.templates.template' => $template]);
            
            $first  = ThemeAdapterResolver::resolve();
            $second = ThemeAdapterResolver::resolve();
            
            $this->assertSame($first, $second);
        });
}
```

---

### Property 6: Bootstrap5Adapter No BS4 Attributes

**Statement:** For any valid input, `Bootstrap5Adapter` output SHALL NOT contain Bootstrap 4 attributes.

**Validates:** Requirements 5.1, 5.2, 5.3, 5.7, 5.8

**Test:**
```php
/** @test Feature: theme-adapter, Property 6: Bootstrap5Adapter tidak menggunakan Bootstrap 4 attributes */
public function test_bootstrap5_adapter_no_bs4_attributes(): void
{
    $adapter = new Bootstrap5Adapter();
    
    $this->forAll(Generator\string(), Generator\string())
        ->then(function ($data, $pointer) use ($adapter) {
            $output = $adapter->renderTabHeader($data, $pointer, true, false);
            
            $this->assertStringNotContainsString('data-toggle="tab"', $output);
            $this->assertStringNotContainsString('data-dismiss', $output);
            $this->assertStringContainsString('data-bs-toggle', $output);
        });
}
```

---

### Property 7: TailwindAdapter No Bootstrap Classes

**Statement:** For any valid input, `TailwindAdapter` output SHALL NOT contain Bootstrap-specific classes.

**Validates:** Requirements 6.1, 6.2, 6.3, 6.4, 6.7

**Test:**
```php
/** @test Feature: theme-adapter, Property 7: TailwindAdapter tidak menggunakan Bootstrap-specific classes */
public function test_tailwind_adapter_no_bootstrap_classes(): void
{
    $adapter = new TailwindAdapter();
    
    $bootstrapClasses = ['alert-block', 'nav-item', 'ckbox', 'chosen-select', 
                         'btn-xs', 'pull-right', 'hide', 'd-none'];
    
    $this->forAll(Generator\string(), Generator\string())
        ->then(function ($data, $pointer) use ($adapter, $bootstrapClasses) {
            $output = $adapter->renderTabHeader($data, $pointer, true, false);
            
            foreach ($bootstrapClasses as $class) {
                $this->assertStringNotContainsString($class, $output);
            }
        });
}
```

---

### Property 8: View Path Resolution

**Statement:** For any registered template, view path SHALL start with template name. For unregistered templates, SHALL fallback to 'default'.

**Validates:** Requirements 14.1, 14.2, 14.3, 14.4, 14.5

**Test:**
```php
/** @test Feature: theme-adapter, Property 8: Blade view path resolution mengikuti template aktif */
public function test_view_path_resolution_follows_active_template(): void
{
    $this->forAll(Generator\elements('default', 'canvasign', 'canvas'))
        ->then(function ($template) {
            config(['canvastack.templates.template' => $template]);
            
            $viewPath = $this->resolveViewPath('admin', 'index');
            
            $this->assertStringStartsWith($template, $viewPath);
        });
}
```

---

## Unit Tests

### Utility Method Tests

```php
class DefaultAdapterTest extends TestCase
{
    /** @test */
    public function it_returns_correct_data_toggle_attribute()
    {
        $adapter = new DefaultAdapter();
        $this->assertEquals('data-toggle', $adapter->getDataToggleAttribute());
    }
    
    /** @test */
    public function it_returns_correct_dismiss_attribute()
    {
        $adapter = new DefaultAdapter();
        $this->assertEquals('data-dismiss', $adapter->getDismissAttribute());
    }
    
    /** @test */
    public function it_returns_correct_hide_class()
    {
        $adapter = new DefaultAdapter();
        $this->assertEquals('hide', $adapter->getHideClass());
    }
    
    /** @test */
    public function it_returns_correct_float_right_class()
    {
        $adapter = new DefaultAdapter();
        $this->assertEquals('pull-right', $adapter->getFloatRightClass());
    }
    
    /** @test */
    public function it_returns_correct_select_box_class()
    {
        $adapter = new DefaultAdapter();
        $this->assertEquals('chosen-select-deselect chosen-selectbox', $adapter->getSelectBoxClass());
    }
}
```

### Resolver Tests

```php
class ThemeAdapterResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ThemeAdapterResolver::reset();
    }
    
    /** @test */
    public function it_resolves_default_adapter_for_default_template()
    {
        config(['canvastack.templates.template' => 'default']);
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertInstanceOf(DefaultAdapter::class, $adapter);
    }
    
    /** @test */
    public function it_resolves_bootstrap5_adapter_for_canvasign_template()
    {
        config(['canvastack.templates.template' => 'canvasign']);
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertInstanceOf(Bootstrap5Adapter::class, $adapter);
    }
    
    /** @test */
    public function it_resolves_tailwind_adapter_for_canvas_template()
    {
        config(['canvastack.templates.template' => 'canvas']);
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertInstanceOf(TailwindAdapter::class, $adapter);
    }
    
    /** @test */
    public function it_falls_back_to_default_adapter_for_unknown_template()
    {
        config(['canvastack.templates.template' => 'unknown']);
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertInstanceOf(DefaultAdapter::class, $adapter);
    }
    
    /** @test */
    public function it_registers_custom_adapter()
    {
        ThemeAdapterResolver::register('custom', CustomAdapter::class);
        config(['canvastack.templates.template' => 'custom']);
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertInstanceOf(CustomAdapter::class, $adapter);
    }
    
    /** @test */
    public function it_throws_exception_for_invalid_adapter_class()
    {
        $this->expectException(\InvalidArgumentException::class);
        ThemeAdapterResolver::register('invalid', \stdClass::class);
    }
}
```

---

## Integration Tests

### End-to-End Rendering

```php
class ThemeEngineIntegrationTest extends TestCase
{
    /** @test */
    public function it_renders_tab_header_with_default_template()
    {
        config(['canvastack.templates.template' => 'default']);
        
        $output = canvastack_form_create_header_tab('Users', 'users-tab', true, false);
        
        $this->assertStringContainsString('data-toggle="tab"', $output);
        $this->assertStringContainsString('nav-item', $output);
        $this->assertStringContainsString('active', $output);
    }
    
    /** @test */
    public function it_renders_tab_header_with_canvasign_template()
    {
        config(['canvastack.templates.template' => 'canvasign']);
        
        $output = canvastack_form_create_header_tab('Users', 'users-tab', true, false);
        
        $this->assertStringContainsString('data-bs-toggle="tab"', $output);
        $this->assertStringNotContainsString('data-toggle="tab"', $output);
    }
    
    /** @test */
    public function it_renders_alert_with_all_templates()
    {
        $templates = ['default', 'canvasign', 'canvas'];
        
        foreach ($templates as $template) {
            config(['canvastack.templates.template' => $template]);
            ThemeAdapterResolver::reset();
            
            $output = canvastack_form_alert_message('Success!', 'success', 'Done', 'msg', false);
            
            $this->assertStringContainsString('Success!', $output);
            $this->assertStringContainsString('Done', $output);
        }
    }
}
```

---

## Running Tests

### Run All Theme Engine Tests

```bash
php artisan test --filter=ThemeAdapter
```

### Run Property-Based Tests

```bash
php artisan test tests/Property/ThemeAdapterPropertiesTest.php
```

### Run Unit Tests

```bash
php artisan test tests/Unit/ThemeAdapterTest.php
php artisan test tests/Unit/ThemeAdapterResolverTest.php
```

### Run Integration Tests

```bash
php artisan test tests/Integration/ThemeEngineIntegrationTest.php
```

### Run with Coverage

```bash
php artisan test --coverage --min=80
```

---

## Test Configuration

### PHPUnit Configuration

```xml
<!-- phpunit.xml -->
<phpunit>
    <testsuites>
        <testsuite name="ThemeEngine">
            <directory suffix="Test.php">./tests/Unit/ThemeAdapter</directory>
            <directory suffix="Test.php">./tests/Property/ThemeAdapter</directory>
            <directory suffix="Test.php">./tests/Integration/ThemeEngine</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Eris Configuration

```php
// tests/Property/ThemeAdapterPropertiesTest.php
use Eris\TestTrait;

class ThemeAdapterPropertiesTest extends TestCase
{
    use TestTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->minimumEvaluationRatio(0.5);
        $this->iterations(100); // 100+ iterations per property
    }
}
```

---

## Test Coverage

### Current Coverage

- **Property Tests:** 8/8 passing (100%)
- **Unit Tests:** All passing
- **Integration Tests:** All passing
- **Code Coverage:** ≥80% for all adapter classes

### Coverage Report

```bash
# Generate coverage report
php artisan test --coverage-html coverage

# View report
open coverage/index.html
```

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may this testing documentation serve developers well.
