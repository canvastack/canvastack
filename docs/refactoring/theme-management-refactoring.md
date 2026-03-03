# Theme Management Refactoring

**Date**: 2024-02-26  
**Status**: Completed  
**Type**: Component Integration Refactoring

---

## Overview

Refactored the admin theme management pages to properly use CanvaStack components following the legacy API patterns. This ensures consistency across the application and demonstrates proper usage of the CanvaStack component system.

---

## Changes Made

### 1. Controller Updates

**File**: `src/Http/Controllers/Admin/ThemeController.php`

#### Added Dependencies
- **MetaTags**: Injected `Canvastack\Canvastack\Library\Components\MetaTags` for SEO meta tag management
- **TableBuilder**: Used for rendering theme list with collection support

#### Method Changes

##### `index()` Method
**Before**:
```php
public function index(): View
{
    // No meta tags
    // Direct view rendering
}
```

**After**:
```php
public function index(TableBuilder $table, MetaTags $meta): View
{
    // Configure meta tags
    $meta->title('Theme Management');
    $meta->description('Manage and customize your application themes');
    $meta->keywords('themes, customization, appearance, design, colors');
    
    // Pass meta to view
    return view('canvastack::admin.themes.index', [
        'meta' => $meta,
        // ... other data
    ]);
}
```

**Benefits**:
- ✅ Proper SEO meta tags
- ✅ Consistent with CanvaStack patterns
- ✅ Better search engine visibility
- ✅ Follows dependency injection pattern

##### `show()` Method
**Before**:
```php
public function show(string $theme): View
{
    // No meta tags
    // Direct view rendering
}
```

**After**:
```php
public function show(string $theme, MetaTags $meta): View
{
    // Configure theme-specific meta tags
    $meta->title($themeObj->getDisplayName() . ' Theme');
    $meta->description('View details and configuration for ' . $themeObj->getDisplayName() . ' theme');
    $meta->keywords('theme, ' . $themeObj->getName() . ', colors, design, customization');
    
    // Pass meta to view
    return view('canvastack::admin.themes.show', [
        'meta' => $meta,
        // ... other data
    ]);
}
```

**Benefits**:
- ✅ Dynamic meta tags based on theme
- ✅ Better SEO for individual theme pages
- ✅ Consistent pattern across all pages

---

### 2. View Updates

#### index.blade.php

**File**: `resources/views/admin/themes/index.blade.php`

**Changes**:
```blade
@extends('canvastack::layouts.admin')

@section('title', 'Theme Management')

@push('head')
    {{-- Meta Tags --}}
    {!! $meta->tags() !!}
@endpush

@section('content')
    {{-- Existing content --}}
@endsection
```

**Benefits**:
- ✅ Meta tags rendered in HTML head
- ✅ Proper SEO implementation
- ✅ Uses Blade stack for head content

#### show.blade.php

**File**: `resources/views/admin/themes/show.blade.php`

**Changes**:
```blade
@extends('canvastack::layouts.admin')

@section('title', $themeData['display_name'] . ' Theme')

@push('head')
    {{-- Meta Tags --}}
    {!! $meta->tags() !!}
@endpush

@section('content')
    {{-- Existing content --}}
@endsection
```

**Benefits**:
- ✅ Theme-specific meta tags
- ✅ Dynamic title and description
- ✅ Consistent with index page

---

### 3. Layout Creation

**File**: `resources/views/layouts/admin.blade.php` (NEW)

Created a traditional Blade layout file to support `@extends` directive used by theme views.

**Features**:
- ✅ Supports `@stack('head')` for meta tags
- ✅ Supports `@stack('styles')` for additional CSS
- ✅ Supports `@stack('scripts')` for additional JS
- ✅ Includes sidebar and navbar components
- ✅ Flash message support
- ✅ Breadcrumbs support
- ✅ Dark mode toggle
- ✅ Responsive sidebar

**Structure**:
```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Admin Panel</title>
    
    @stack('head')      <!-- Meta tags go here -->
    @stack('styles')    <!-- Additional styles -->
</head>
<body>
    <!-- Sidebar -->
    <x-canvastack::layouts.partials.sidebar />
    
    <!-- Main Content -->
    <div id="main-content">
        <!-- Navbar -->
        <x-canvastack::layouts.partials.navbar />
        
        <!-- Page Content -->
        <main>
            @yield('content')
        </main>
    </div>
    
    @stack('scripts')   <!-- Additional scripts -->
</body>
</html>
```

---

### 4. Test Updates

**File**: `tests/Feature/Http/Controllers/Admin/ThemeControllerTest.php`

#### Added Test Assertions

**Test**: `test_index_displays_theme_management_page()`
```php
$response->assertViewHas('meta'); // Verify MetaTags component
```

**Test**: `test_show_displays_theme_details()`
```php
$response->assertViewHas('meta'); // Verify MetaTags component
```

#### New Tests

**Test**: `test_index_configures_meta_tags()`
```php
public function test_index_configures_meta_tags(): void
{
    $response = $this->get(route('admin.themes.index'));
    
    // Verify MetaTags component is passed to view
    $meta = $response->viewData('meta');
    $this->assertInstanceOf(\Canvastack\Canvastack\Library\Components\MetaTags::class, $meta);
    
    // Verify meta tags contain expected content
    $metaHtml = $meta->tags();
    $this->assertStringContainsString('Theme Management', $metaHtml);
    $this->assertStringContainsString('themes', $metaHtml);
    $this->assertStringContainsString('customization', $metaHtml);
}
```

**Test**: `test_show_configures_meta_tags_with_theme_name()`
```php
public function test_show_configures_meta_tags_with_theme_name(): void
{
    $response = $this->get(route('admin.themes.show', 'gradient'));
    
    // Verify MetaTags component is passed to view
    $meta = $response->viewData('meta');
    $this->assertInstanceOf(\Canvastack\Canvastack\Library\Components\MetaTags::class, $meta);
    
    // Verify meta tags contain theme-specific content
    $metaHtml = $meta->tags();
    $this->assertStringContainsString('Gradient', $metaHtml);
    $this->assertStringContainsString('theme', $metaHtml);
}
```

**Benefits**:
- ✅ Verifies MetaTags component usage
- ✅ Tests meta tag content
- ✅ Ensures proper dependency injection
- ✅ Validates SEO implementation

---

## TableBuilder Integration (Completed)

### Implementation

The TableBuilder component now supports Collection data sources in addition to Eloquent Models. The theme management page uses this feature to render themes from the ThemeManager service.

### How It Works

```php
// Prepare themes collection
$themes = collect($themeManager->all())->map(function ($theme) use ($themeManager) {
    return [
        'name' => $theme->getName(),
        'display_name' => $theme->getDisplayName(),
        'description' => $theme->getDescription(),
        'version' => $theme->getVersion(),
        'author' => $theme->getAuthor(),
        'dark_mode' => $theme->supportsDarkMode(),
        'is_active' => $theme->getName() === $themeManager->current()->getName(),
        'colors' => $theme->getColors(),
    ];
});

// Configure TableBuilder with collection
$table->setContext('admin');
$table->setCollection($themes);
$table->setFields([
    'display_name:Theme',
    'version:Version',
    'author:Author',
    'dark_mode:Dark Mode',
    'is_active:Status',
]);

// Add custom column renderers
$table->setColumnRenderer('display_name', function ($row) {
    // Custom HTML rendering with color preview
    return "...";
});

$table->format();
```

### Benefits

- ✅ Consistent UI with other admin pages
- ✅ Built-in sorting and filtering
- ✅ Custom column rendering
- ✅ Responsive design
- ✅ Dark mode support
- ✅ Action buttons (view, activate, export)

---

## MetaTags Component API

### Legacy API (Used in Refactoring)

```php
// Basic usage
$meta->title('Page Title');
$meta->description('Page description');
$meta->keywords('keyword1, keyword2, keyword3');
$meta->author('Author Name');

// Render tags
{!! $meta->tags() !!}  // Returns all meta tags as HTML
{!! $meta->tags('html') !!}  // Same as above
{!! $meta->tags('text') !!}  // Returns array of text values

// Individual tags
$meta->getMetaHTML('title');  // Get specific tag HTML
$meta->getMetaText('title');  // Get specific tag text
```

### Configuration Integration

The MetaTags component automatically loads from:
1. **Database**: `Preference` model (if available)
2. **Config**: `canvas.settings.php` configuration
3. **Fallback**: Default values

**Priority**: Database > Config > Defaults

---

## Benefits of Refactoring

### 1. SEO Improvements
- ✅ Proper meta tags for search engines
- ✅ Dynamic titles and descriptions
- ✅ Keyword optimization
- ✅ Better search visibility

### 2. Code Consistency
- ✅ Follows CanvaStack patterns
- ✅ Uses dependency injection
- ✅ Consistent with other controllers
- ✅ Easier to maintain

### 3. Developer Experience
- ✅ Clear component usage examples
- ✅ Documented patterns
- ✅ Type-safe dependencies
- ✅ Better IDE support

### 4. Testing
- ✅ Comprehensive test coverage
- ✅ Component integration tests
- ✅ Meta tag validation
- ✅ Regression prevention

### 5. Maintainability
- ✅ Centralized meta tag management
- ✅ Reusable components
- ✅ Easy to update
- ✅ Clear separation of concerns

---

## Code Quality

### PSR-12 Compliance
All code formatted with Laravel Pint:
```bash
./vendor/bin/pint src/Http/Controllers/Admin/ThemeController.php
./vendor/bin/pint tests/Feature/Http/Controllers/Admin/ThemeControllerTest.php
```

**Result**: ✅ All files pass PSR-12 standards

### PHPDoc Comments
- ✅ All methods documented
- ✅ Parameter types specified
- ✅ Return types documented
- ✅ Examples provided

---

## Migration Guide

### For Other Controllers

To apply similar refactoring to other controllers:

1. **Inject MetaTags**:
```php
public function index(MetaTags $meta): View
{
    // Configure meta tags
    $meta->title('Page Title');
    $meta->description('Page description');
    $meta->keywords('keywords');
    
    return view('view.name', ['meta' => $meta]);
}
```

2. **Update View**:
```blade
@extends('canvastack::layouts.admin')

@push('head')
    {!! $meta->tags() !!}
@endpush
```

3. **Update Tests**:
```php
$response->assertViewHas('meta');
$meta = $response->viewData('meta');
$this->assertInstanceOf(MetaTags::class, $meta);
```

---

## Related Documentation

- [MetaTags Component](../components/meta-tags.md)
- [TableBuilder Component](../components/table-builder.md)
- [Admin Layout](../layouts/admin-layout.md)
- [Testing Guidelines](../testing/feature-tests.md)

---

## Checklist

- [x] Controller updated with MetaTags injection
- [x] Views updated with meta tag rendering
- [x] Layout created with stack support
- [x] Tests updated and expanded
- [x] Code formatted with Pint (PSR-12)
- [x] Documentation created
- [x] TableBuilder integration completed
- [x] MetaTags migrated to Canvastack namespace
- [ ] Performance testing
- [ ] User acceptance testing

---

## Next Steps

1. **Apply Pattern to Other Controllers**
   - Dashboard controller
   - User management
   - Settings pages

2. **Consider TableBuilder Integration**
   - Evaluate database storage for themes
   - Or extend TableBuilder for collections

3. **Performance Monitoring**
   - Measure page load times
   - Monitor SEO improvements
   - Track user engagement

4. **Documentation**
   - Create component usage guide
   - Document best practices
   - Add more examples

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Completed  
**Reviewed By**: Development Team

