# TanStack Table Multi-Table & Tab System - Usage Guide

**Status**: Production Ready  
**Version**: 1.0.0  
**i18n Compliant**: ✅ Yes

---

## Quick Start

### Basic Usage (Simplest)

```blade
{{-- In your view --}}
<x-canvastack::table.tabs :tabs="$table->getTabs()" />
```

That's it! The component automatically:
- ✅ Injects all translations
- ✅ Sets up Alpine.js state
- ✅ Configures lazy loading
- ✅ Handles CSRF tokens
- ✅ Provides error handling

### Controller Example

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table): View
{
    // Tab 1: Users
    $table->openTab(__('ui.tabs.users'));
    $table->setModel(new User());
    $table->setFields([
        'name:' . __('ui.labels.name'),
        'email:' . __('ui.labels.email'),
        'created_at:' . __('ui.labels.created_at'),
    ]);
    $table->closeTab();
    
    // Tab 2: Settings
    $table->openTab(__('ui.tabs.settings'));
    $table->setModel(new Setting());
    $table->setFields([
        'key:' . __('ui.labels.key'),
        'value:' . __('ui.labels.value'),
    ]);
    $table->closeTab();
    
    $table->format();
    
    return view('admin.users.index', [
        'table' => $table,
    ]);
}
```

### View Example

```blade
@extends('layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">{{ __('ui.users.title') }}</h1>
        
        {{-- Reusable tab component - automatically i18n compliant --}}
        <x-canvastack::table.tabs :tabs="$table->getTabs()" />
    </div>
@endsection
```

---

## Advanced Usage

### Custom Active Tab

```blade
<x-canvastack::table.tabs 
    :tabs="$table->getTabs()" 
    :activeTab="1" 
/>
```

### Disable Lazy Loading

```blade
<x-canvastack::table.tabs 
    :tabs="$table->getTabs()" 
    :lazyLoad="false" 
/>
```

### All Options

```blade
<x-canvastack::table.tabs 
    :tabs="$table->getTabs()" 
    :activeTab="0"
    :lazyLoad="true"
/>
```

---

## Component Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `tabs` | array | `[]` | Tab configurations from `$table->getTabs()` |
| `activeTab` | int | `0` | Index of initially active tab |
| `lazyLoad` | bool | `true` | Enable lazy loading for non-active tabs |

---

## Features

### ✅ Automatic i18n Support

All error messages, loading states, and UI text are automatically translated based on the current locale:

```php
// Set locale in controller or middleware
app()->setLocale('id'); // Indonesian
app()->setLocale('en'); // English
```

No need to manually pass translations!

### ✅ Lazy Loading

First tab loads immediately, other tabs load on-demand when clicked. This improves:
- Initial page load time
- Server resource usage
- User experience

### ✅ Error Handling

Automatic error handling with user-friendly messages:
- Network errors
- Permission errors
- Session expiration
- Server errors
- Retry functionality

### ✅ Accessibility

Full ARIA support:
- Keyboard navigation (Arrow keys, Home, End)
- Screen reader support
- Focus management
- Semantic HTML

### ✅ Dark Mode

Automatic dark mode support via Tailwind CSS and DaisyUI.

### ✅ URL Hash Bookmarking

Tab state saved in URL hash for bookmarking and sharing:
```
https://example.com/users#tab-1
```

---

## Translation Keys

The component uses these translation keys (automatically included):

### English (`resources/lang/en/ui.php`)
```php
'tabs' => [
    'tab' => 'Tab',
    'container_label' => 'Tabbed content',
    'loading' => 'Loading',
    'loading_description' => 'Please wait while we load the content...',
    'error_title' => 'Failed to Load',
    'error_404' => 'Tab content not found. Please refresh the page.',
    'error_403' => 'You do not have permission to view this tab.',
    'error_419' => 'Your session has expired. Please refresh the page.',
    'error_500' => 'Server error. Please try again later.',
    'error_network' => 'Network error. Please check your connection.',
    'no_content' => 'No Content',
    'no_content_description' => 'This tab has no content to display.',
],
'buttons' => [
    'retry' => 'Retry',
],
```

### Indonesian (`resources/lang/id/ui.php`)
```php
'tabs' => [
    'tab' => 'Tab',
    'container_label' => 'Konten bertab',
    'loading' => 'Memuat',
    'loading_description' => 'Mohon tunggu sementara kami memuat konten...',
    'error_title' => 'Gagal Memuat',
    'error_404' => 'Konten tab tidak ditemukan. Silakan refresh halaman.',
    'error_403' => 'Anda tidak memiliki izin untuk melihat tab ini.',
    'error_419' => 'Sesi Anda telah berakhir. Silakan refresh halaman.',
    'error_500' => 'Kesalahan server. Silakan coba lagi nanti.',
    'error_network' => 'Kesalahan jaringan. Silakan periksa koneksi Anda.',
    'no_content' => 'Tidak Ada Konten',
    'no_content_description' => 'Tab ini tidak memiliki konten untuk ditampilkan.',
],
'buttons' => [
    'retry' => 'Coba Lagi',
],
```

---

## Testing

### Manual Testing

```php
// Test English
app()->setLocale('en');
// Visit page and verify all text is in English

// Test Indonesian
app()->setLocale('id');
// Visit page and verify all text is in Indonesian
```

### Browser Testing

```php
// tests/Browser/TabSystemTest.php
public function test_tab_switching_works()
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/users')
                ->assertSee('Tab 1')
                ->click('@tab-button-1')
                ->waitFor('@tab-panel-1')
                ->assertSee('Tab 2 content');
    });
}
```

---

## Troubleshooting

### Tabs not switching
- Check Alpine.js is loaded
- Check `table-tabs.js` is included
- Check browser console for errors

### Translations not working
- Verify translation files exist
- Check locale is set correctly
- Clear Laravel cache: `php artisan cache:clear`

### Lazy loading not working
- Check CSRF token is valid
- Check route is registered
- Check controller method exists
- Check network tab in browser DevTools

---

## Migration from Old Code

### Before (Non-compliant)
```blade
<div x-data="tabSystem({ tabs: @js($tabs), csrfToken: '{{ csrf_token() }}' })">
    @foreach($tabs as $index => $tab)
        {{-- Manual tab rendering --}}
    @endforeach
</div>
```

### After (Compliant & Reusable)
```blade
<x-canvastack::table.tabs :tabs="$table->getTabs()" />
```

Much simpler and automatically compliant!

---

## Related Documentation

- [i18n System Standards](../../.kiro/steering/i18n-system.md)
- [Theme Engine Standards](../../.kiro/steering/theme-engine-system.md)
- [Component Standards](../../.kiro/steering/canvastack-components.md)
- [I18N Compliance Report](../../.kiro/specs/tanstack-multi-table-tabs/I18N-COMPLIANCE.md)

---

**Last Updated**: 2026-03-08  
**Maintained By**: CanvaStack Team
