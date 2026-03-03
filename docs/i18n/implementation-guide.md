# i18n Implementation Guide

Complete guide for implementing internationalization (i18n) in your CanvaStack application.

## 📦 Overview

CanvaStack provides a comprehensive internationalization system that supports:
- Multiple languages with easy switching
- RTL (Right-to-Left) language support
- Translation management and caching
- Browser locale detection
- Component-level translations
- Developer tools for translation workflow

## 🚀 Quick Start

### Step 1: Configure Available Locales

Edit `config/canvastack.php`:

```php
'localization' => [
    'default_locale' => 'en',
    'fallback_locale' => 'en',
    
    'available_locales' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => '🇺🇸',
        ],
        'id' => [
            'name' => 'Indonesian',
            'native' => 'Bahasa Indonesia',
            'flag' => '🇮🇩',
        ],
        'es' => [
            'name' => 'Spanish',
            'native' => 'Español',
            'flag' => '🇪🇸',
        ],
    ],
    
    'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
    'storage' => 'session', // 'session', 'cookie', or 'both'
    'detect_browser' => true,
],
```

### Step 2: Create Translation Files

Create language files in `resources/lang/{locale}/`:

```
resources/lang/
├── en/
│   ├── ui.php
│   ├── validation.php
│   ├── components.php
│   └── auth.php
├── id/
│   ├── ui.php
│   ├── validation.php
│   ├── components.php
│   └── auth.php
└── es/
    ├── ui.php
    ├── validation.php
    ├── components.php
    └── auth.php
```

**Example `resources/lang/en/ui.php`:**

```php
<?php

return [
    // Common UI elements
    'name' => 'Name',
    'email' => 'Email',
    'password' => 'Password',
    'submit' => 'Submit',
    'cancel' => 'Cancel',
    'save' => 'Save',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'view' => 'View',
    'search' => 'Search',
    'filter' => 'Filter',
    
    // Navigation
    'dashboard' => 'Dashboard',
    'users' => 'Users',
    'settings' => 'Settings',
    'logout' => 'Logout',
    
    // Messages
    'success' => 'Success!',
    'error' => 'Error!',
    'warning' => 'Warning!',
    'info' => 'Information',
    
    // Actions
    'create' => 'Create',
    'update' => 'Update',
    'delete_confirm' => 'Are you sure you want to delete this item?',
];
```

**Example `resources/lang/id/ui.php`:**

```php
<?php

return [
    // Common UI elements
    'name' => 'Nama',
    'email' => 'Email',
    'password' => 'Kata Sandi',
    'submit' => 'Kirim',
    'cancel' => 'Batal',
    'save' => 'Simpan',
    'delete' => 'Hapus',
    'edit' => 'Ubah',
    'view' => 'Lihat',
    'search' => 'Cari',
    'filter' => 'Filter',
    
    // Navigation
    'dashboard' => 'Dasbor',
    'users' => 'Pengguna',
    'settings' => 'Pengaturan',
    'logout' => 'Keluar',
    
    // Messages
    'success' => 'Berhasil!',
    'error' => 'Kesalahan!',
    'warning' => 'Peringatan!',
    'info' => 'Informasi',
    
    // Actions
    'create' => 'Buat',
    'update' => 'Perbarui',
    'delete_confirm' => 'Apakah Anda yakin ingin menghapus item ini?',
];
```

### Step 3: Add Locale Switcher to Your Layout

Add the locale switcher component to your layout:

```blade
@extends('canvastack::layouts.admin')

@section('header-actions')
    <x-canvastack::locale-switcher />
@endsection

@section('content')
    {{-- Your content here --}}
@endsection
```

### Step 4: Use Translations in Your Code

#### In Blade Templates

```blade
{{-- Simple translation --}}
<h1>{{ __('ui.dashboard') }}</h1>

{{-- Translation with parameters --}}
<p>{{ __('ui.welcome_message', ['name' => $user->name]) }}</p>

{{-- Pluralization --}}
<p>{{ trans_choice('ui.items_count', $count) }}</p>
```

#### In Controllers

```php
use Canvastack\Canvastack\Support\Localization\LocaleManager;

class UserController extends Controller
{
    public function index(LocaleManager $localeManager)
    {
        // Get current locale
        $currentLocale = $localeManager->getLocale();
        
        // Set locale
        $localeManager->setLocale('id');
        
        // Get translated message
        $message = __('ui.success');
        
        return view('users.index', compact('message'));
    }
}
```

#### In Components

```php
use Canvastack\Canvastack\Components\Form\FormBuilder;

class UserFormController extends Controller
{
    public function create(FormBuilder $form)
    {
        $form->setContext('admin');
        
        // Use translations for labels
        $form->text('name', __('ui.name'))->required();
        $form->email('email', __('ui.email'))->required();
        $form->password('password', __('ui.password'))->required();
        
        return view('users.create', compact('form'));
    }
}
```

## 🔧 Advanced Implementation

### Custom Locale Detection

Create a middleware to detect and set locale:

```php
<?php

namespace App\Http\Middleware;

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    protected LocaleManager $localeManager;
    
    public function __construct(LocaleManager $localeManager)
    {
        $this->localeManager = $localeManager;
    }
    
    public function handle(Request $request, Closure $next)
    {
        // Priority: URL parameter > Session > Browser > Default
        if ($request->has('locale')) {
            $this->localeManager->setLocale($request->input('locale'));
        }
        
        return $next($request);
    }
}
```

Register in `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \App\Http\Middleware\SetLocale::class,
    ],
];
```

### Locale Switching Route

Create a route for locale switching:

```php
// routes/web.php
use Canvastack\Canvastack\Support\Localization\LocaleManager;

Route::post('/locale/switch', function (Request $request, LocaleManager $localeManager) {
    $locale = $request->input('locale');
    
    if (!$localeManager->isAvailable($locale)) {
        return redirect()->back()->withErrors(['locale' => 'Invalid locale']);
    }
    
    $localeManager->setLocale($locale);
    
    return redirect()->back()->with('success', __('ui.locale_changed'));
})->name('locale.switch');
```

### Database-Driven Translations

Store translations in database for dynamic content:

```php
// Migration
Schema::create('translations', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->string('locale');
    $table->text('value');
    $table->timestamps();
    
    $table->index(['key', 'locale']);
});

// Model
class Translation extends Model
{
    protected $fillable = ['key', 'locale', 'value'];
    
    public static function get(string $key, string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        
        return Cache::remember("translation.{$key}.{$locale}", 3600, function () use ($key, $locale) {
            return static::where('key', $key)
                ->where('locale', $locale)
                ->value('value');
        });
    }
}

// Usage
$translation = Translation::get('custom.message');
```

### RTL Support Implementation

Automatically apply RTL styles based on locale:

```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_manager()->getDirection() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('ui.app_name') }}</title>
    
    @if(locale_manager()->isRtl())
        <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('css/ltr.css') }}">
    @endif
</head>
<body class="{{ locale_manager()->isRtl() ? 'rtl' : 'ltr' }}">
    @yield('content')
</body>
</html>
```

### Translation Caching

Enable translation caching for better performance:

```php
// config/canvastack.php
'localization' => [
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'driver' => 'redis', // or 'file', 'array'
    ],
],
```

## 📝 Best Practices

### 1. Consistent Key Naming

Use a consistent naming convention for translation keys:

```php
// Good
__('ui.button.save')
__('validation.email.required')
__('components.table.actions.edit')

// Bad
__('save_button')
__('email_required')
__('edit_action')
```

### 2. Organize by Context

Group translations by context or feature:

```
resources/lang/en/
├── ui.php              # General UI elements
├── auth.php            # Authentication messages
├── validation.php      # Validation messages
├── components.php      # Component-specific translations
├── errors.php          # Error messages
└── features/
    ├── users.php       # User management
    ├── products.php    # Product management
    └── orders.php      # Order management
```

### 3. Use Parameters for Dynamic Content

```php
// Translation file
'welcome_message' => 'Welcome, :name!',
'items_found' => 'Found :count items',

// Usage
__('ui.welcome_message', ['name' => $user->name])
__('ui.items_found', ['count' => $items->count()])
```

### 4. Handle Pluralization

```php
// Translation file
'items_count' => '{0} No items|{1} One item|[2,*] :count items',

// Usage
trans_choice('ui.items_count', $count)
```

### 5. Provide Fallbacks

Always provide fallback translations:

```php
// Use fallback locale if translation missing
__('ui.some_key', [], 'en')

// Provide default value
__('ui.some_key') ?: 'Default Value'
```

## 🧪 Testing Translations

### Unit Tests

```php
public function test_translation_exists()
{
    $this->assertEquals('Name', __('ui.name'));
    $this->assertEquals('Nama', __('ui.name', [], 'id'));
}

public function test_locale_switching()
{
    $localeManager = app(LocaleManager::class);
    
    $localeManager->setLocale('en');
    $this->assertEquals('Name', __('ui.name'));
    
    $localeManager->setLocale('id');
    $this->assertEquals('Nama', __('ui.name'));
}
```

### Feature Tests

```php
public function test_locale_switcher_works()
{
    $response = $this->post(route('locale.switch'), [
        'locale' => 'id',
    ]);
    
    $response->assertRedirect();
    $this->assertEquals('id', session('canvastack_locale'));
}
```

## 🔍 Troubleshooting

### Translations Not Loading

1. **Clear cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

2. **Check file permissions:**
   ```bash
   chmod -R 755 resources/lang
   ```

3. **Verify file encoding:**
   Ensure all translation files are UTF-8 encoded.

### Missing Translations

Use the translation coverage command:

```bash
php artisan canvastack:translate:coverage
```

### RTL Not Working

1. **Check locale configuration:**
   ```php
   Config::get('canvastack.localization.rtl_locales')
   ```

2. **Verify HTML dir attribute:**
   ```blade
   <html dir="{{ locale_manager()->getDirection() }}">
   ```

## 📚 Related Documentation

- [Locale Switcher](locale-switcher.md) - Locale switcher component
- [RTL Support](rtl-support.md) - Right-to-Left language support
- [Translation API](translation-api.md) - Translation API reference
- [Developer Tools](developer-tools.md) - Translation management tools
- [Advanced Features](advanced-features.md) - Advanced i18n features

---

**Last Updated**: 2026-02-27  
**Version**: 1.0.0  
**Status**: Published
