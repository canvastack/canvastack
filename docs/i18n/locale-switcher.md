# Locale Switcher Implementation

## 📦 Overview

The Locale Switcher feature provides comprehensive internationalization (i18n) support for the CanvaStack package, allowing users to switch between different languages seamlessly.

## 🎯 Features

- **Multi-language Support**: Currently supports English (en) and Indonesian (id)
- **Automatic Detection**: Detects user's preferred language from browser settings
- **Persistent Storage**: Saves language preference in session and/or cookies
- **UI Components**: Beautiful dropdown selector for language switching
- **Admin Management**: Dedicated admin page for locale configuration
- **RTL Support**: Built-in support for right-to-left languages

## 📁 File Structure

```
packages/canvastack/canvastack/
├── src/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── LocaleController.php              # Public locale switching
│   │   │   └── Admin/LocaleController.php        # Admin locale management
│   │   └── Middleware/
│   │       └── SetLocale.php                     # Locale detection middleware
│   └── Support/
│       └── Localization/
│           └── LocaleManager.php                 # Core locale management
│
├── resources/
│   ├── views/
│   │   ├── components/ui/
│   │   │   └── locale-selector.blade.php         # Locale selector component
│   │   ├── components/layouts/partials/
│   │   │   ├── navbar.blade.php                  # Admin navbar (with locale selector)
│   │   │   └── public-navbar.blade.php           # Public navbar (with locale selector)
│   │   └── admin/locales/
│   │       └── index.blade.php                   # Admin locale management page
│   └── lang/
│       ├── en/                                    # English translations
│       │   ├── ui.php
│       │   ├── auth.php
│       │   ├── components.php
│       │   ├── errors.php
│       │   └── validation.php
│       └── id/                                    # Indonesian translations
│           ├── ui.php
│           ├── auth.php
│           ├── components.php
│           ├── errors.php
│           └── validation.php
│
├── routes/
│   └── web.php                                    # Locale routes
│
└── tests/
    └── Feature/
        └── LocaleSwitcherTest.php                 # Feature tests
```

## 🔧 Configuration

### Available Locales

Configure available locales in `config/canvastack.php`:

```php
'localization' => [
    'default_locale' => 'en',
    'fallback_locale' => 'en',
    
    'available_locales' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => '🇺🇸',
            'direction' => 'ltr',
        ],
        'id' => [
            'name' => 'Indonesian',
            'native' => 'Bahasa Indonesia',
            'flag' => '🇮🇩',
            'direction' => 'ltr',
        ],
    ],
    
    // RTL (Right-to-Left) locales
    'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
    
    // Storage driver: 'session', 'cookie', or 'both'
    'storage' => 'session',
    
    // Detect locale from browser
    'detect_browser' => true,
],
```

### Environment Variables

Add to `.env`:

```env
CANVASTACK_DEFAULT_LOCALE=en
CANVASTACK_FALLBACK_LOCALE=en
CANVASTACK_LOCALE_STORAGE=session
CANVASTACK_DETECT_BROWSER_LOCALE=true
```

## 📖 Usage

### Locale Selector Component

The locale selector component can be used anywhere in your views:

```blade
{{-- Full version with name --}}
<x-locale-selector :showName="true" :compact="false" />

{{-- Compact version (flag + code only) --}}
<x-locale-selector :showName="false" :compact="true" />

{{-- Custom position --}}
<x-locale-selector position="bottom-left" />
```

#### Component Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `position` | string | 'bottom-right' | Dropdown position: 'bottom-right', 'bottom-left', 'top-right', 'top-left' |
| `showFlag` | boolean | true | Show flag emoji |
| `showName` | boolean | true | Show language name |
| `compact` | boolean | false | Compact mode (shows only code) |

### Programmatic Locale Switching

```php
use Canvastack\Canvastack\Support\Localization\LocaleManager;

$localeManager = app('canvastack.locale');

// Get current locale
$current = $localeManager->getLocale(); // 'en'

// Set locale
$localeManager->setLocale('id');

// Check if locale is available
if ($localeManager->isAvailable('fr')) {
    $localeManager->setLocale('fr');
}

// Get locale information
$info = $localeManager->getLocaleInfo('id');
// ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => '🇮🇩']

// Get all available locales
$locales = $localeManager->getAvailableLocales();

// Check if RTL
$isRtl = $localeManager->isRtl('ar'); // true

// Get text direction
$direction = $localeManager->getDirection(); // 'ltr' or 'rtl'
```

### Translation Usage

```blade
{{-- In Blade templates --}}
{{ __('ui.buttons.save') }}
{{ __('ui.messages.success') }}
{{ __('ui.labels.language') }}

{{-- With parameters --}}
{{ __('ui.time.minutes_ago', ['count' => 5]) }}
```

```php
// In PHP code
__('ui.buttons.save');
trans('ui.messages.success');
trans_choice('ui.time.minutes_ago', 5);
```

## 🎮 Locale Detection Priority

The locale is detected in the following order:

1. **URL Parameter**: `?locale=id`
2. **Session**: Stored in session (if storage is 'session' or 'both')
3. **Cookie**: Stored in cookie (if storage is 'cookie' or 'both')
4. **Browser**: Detected from `Accept-Language` header (if enabled)
5. **Default**: Falls back to default locale from config

## 🔗 Routes

### Public Routes

```php
// Switch locale
POST /locale/switch
```

### Admin Routes

```php
// Locale management page
GET /admin/locales
```

## 🎨 UI Components

### Navbar Integration

The locale selector is automatically integrated into both admin and public navbars:

- **Admin Navbar**: Located between search and notifications
- **Public Navbar**: Located before dark mode toggle

### Admin Management Page

Access the locale management page at `/admin/locales` to:

- View all available locales
- See current active locale
- View configuration settings
- Access configuration guide

## 🧪 Testing

Run the locale switcher tests:

```bash
cd packages/canvastack/canvastack
./vendor/bin/phpunit tests/Feature/LocaleSwitcherTest.php
```

### Test Coverage

- ✅ Get available locales
- ✅ Check if locale is available
- ✅ Set locale
- ✅ Validate unavailable locale
- ✅ Persist locale to session
- ✅ Get locale information
- ✅ Get locale name and native name
- ✅ Get locale flag
- ✅ Detect RTL locale
- ✅ Get text direction
- ✅ Switch locale via POST request
- ✅ Validate locale code format
- ✅ Detect browser locale
- ✅ Use default locale when browser locale not available

## 🌍 Adding New Locales

### Step 1: Add to Configuration

Edit `config/canvastack.php`:

```php
'localization' => [
    'available_locales' => [
        // ... existing locales
        'fr' => [
            'name' => 'French',
            'native' => 'Français',
            'flag' => '🇫🇷',
            'direction' => 'ltr',
        ],
    ],
],
```

### Step 2: Create Translation Files

Create directory and files:

```
resources/lang/fr/
├── ui.php
├── auth.php
├── components.php
├── errors.php
└── validation.php
```

### Step 3: Translate Content

Copy from `en/` directory and translate all strings:

```php
// resources/lang/fr/ui.php
return [
    'buttons' => [
        'save' => 'Enregistrer',
        'cancel' => 'Annuler',
        // ... more translations
    ],
];
```

### Step 4: Test

1. Clear cache: `php artisan cache:clear`
2. Switch to new locale
3. Verify translations appear correctly

## 🎯 Best Practices

### 1. Translation Keys

Use descriptive, hierarchical keys:

```php
// Good
__('ui.buttons.save')
__('ui.messages.success')
__('components.form.required_field')

// Bad
__('save')
__('success')
__('required')
```

### 2. Pluralization

Use Laravel's pluralization features:

```php
// Translation file
'time' => [
    'minutes_ago' => ':count minute ago|:count minutes ago',
],

// Usage
trans_choice('ui.time.minutes_ago', 5) // "5 minutes ago"
```

### 3. Parameters

Use named parameters for clarity:

```php
// Translation file
'welcome' => 'Welcome, :name!',

// Usage
__('ui.welcome', ['name' => 'John'])
```

### 4. Fallback

Always provide fallback translations in the default locale (English).

## 🔒 Security

- Locale codes are validated (2-character codes only)
- XSS protection via Blade escaping
- CSRF protection on locale switching
- Input validation on all locale-related requests

## ♿ Accessibility

- ARIA labels on all interactive elements
- Keyboard navigation support
- Screen reader friendly
- Semantic HTML structure
- Focus management

## 🎨 Styling

The locale selector uses Tailwind CSS and DaisyUI:

- Responsive design (mobile-friendly)
- Dark mode support
- Smooth transitions and animations
- Consistent with CanvaStack design system

## 📚 Related Documentation

- [Internationalization System](./README.md)
- [Translation Management](./translation-management.md)
- [RTL Support](./rtl-support.md)
- [Component Documentation](../components/README.md)

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Implemented  
**Author**: CanvaStack Team
