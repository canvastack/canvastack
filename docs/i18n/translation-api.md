# Translation API

Complete API reference for CanvaStack's translation system.

## 📦 Overview

The Translation API provides a comprehensive set of tools for managing translations in CanvaStack applications, including helper functions, Blade directives, a facade, and an event system.

---

## 🔧 Helper Functions

### trans_choice_with_count()

Translate a message with pluralization and include the count.

```php
trans_choice_with_count(string $key, int|array $count, array $replace = [], ?string $locale = null): string
```

**Example:**
```php
echo trans_choice_with_count('messages.items', 5);
// Output: "5 items"

echo trans_choice_with_count('messages.items', 1);
// Output: "1 item"
```

---

### trans_fallback()

Translate with fallback to default value.

```php
trans_fallback(string $key, string $default, array $replace = [], ?string $locale = null): string
```

**Example:**
```php
echo trans_fallback('nonexistent.key', 'Default Value');
// Output: "Default Value"
```

---

### trans_if_exists()

Translate only if translation exists, otherwise return null.

```php
trans_if_exists(string $key, array $replace = [], ?string $locale = null): ?string
```

**Example:**
```php
$translation = trans_if_exists('messages.welcome');
if ($translation) {
    echo $translation;
} else {
    echo "Translation not found";
}
```

---

### trans_with_context()

Translate with context (admin/public).

```php
trans_with_context(string $key, string $context, array $replace = [], ?string $locale = null): string
```

**Example:**
```php
// Try admin.button.save first, fallback to button.save
echo trans_with_context('button.save', 'admin');
```

---

### trans_cached()

Get cached translation.

```php
trans_cached(string $key, array $replace = [], ?string $locale = null): string
```

**Example:**
```php
echo trans_cached('messages.welcome');
// Translation is cached for better performance
```

---

### trans_component()

Translate component-specific key.

```php
trans_component(string $component, string $key, array $replace = [], ?string $locale = null): string
```

**Example:**
```php
echo trans_component('form', 'submit_button');
// Translates: canvastack::components.form.submit_button
```

---

### trans_ui()

Translate UI element.

```php
trans_ui(string $key, array $replace = [], ?string $locale = null): string
```

**Example:**
```php
echo trans_ui('buttons.save');
// Translates: canvastack::ui.buttons.save
```

---

### trans_validation()

Translate validation message.

```php
trans_validation(string $key, array $replace = [], ?string $locale = null): string
```

**Example:**
```php
echo trans_validation('required', ['attribute' => 'email']);
// Translates: canvastack::validation.required
```

---

### trans_error()

Translate error message.

```php
trans_error(string $key, array $replace = [], ?string $locale = null): string
```

**Example:**
```php
echo trans_error('not_found');
// Translates: canvastack::errors.not_found
```

---

## 🎨 Blade Directives

### @trans

Basic translation directive.

```blade
@trans('messages.welcome')
@trans('messages.greeting', ['name' => 'John'])
```

---

### @transChoice

Pluralization directive.

```blade
@transChoice('messages.items', $count)
@transChoice('messages.items', $count, ['count' => $count])
```

---

### @transFallback

Translation with fallback.

```blade
@transFallback('messages.custom', 'Default Message')
```

---

### @transContext

Context-aware translation.

```blade
@transContext('button.save', 'admin')
@transContext('button.save', 'public')
```

---

### @transComponent

Component translation.

```blade
@transComponent('form', 'submit_button')
@transComponent('table', 'no_data')
```

---

### @transUi

UI translation.

```blade
@transUi('buttons.save')
@transUi('labels.search')
```

---

### @transValidation

Validation translation.

```blade
@transValidation('required', ['attribute' => 'email'])
```

---

### @transError

Error translation.

```blade
@transError('not_found')
@transError('server_error')
```

---

### @locale / @endlocale

Temporarily change locale.

```blade
@locale('id')
    <p>Content in Indonesian</p>
@endlocale
```

---

### @rtl / @ltr

Conditional rendering based on text direction.

```blade
@rtl
    <div dir="rtl">RTL content</div>
@endrtl

@ltr
    <div dir="ltr">LTR content</div>
@endltr
```

---

## 🎭 Translation Facade

### Basic Usage

```php
use Canvastack\Canvastack\Support\Facades\Translation;

// Get translation
Translation::get('messages.welcome');

// With pluralization
Translation::choice('messages.items', 5);

// With fallback
Translation::fallback('nonexistent.key', 'Default');

// Check if exists
Translation::ifExists('messages.welcome');

// With context
Translation::withContext('button.save', 'admin');
```

---

### Component Translations

```php
// Component translation
Translation::component('form', 'submit');

// UI translation
Translation::ui('buttons.save');

// Validation translation
Translation::validation('required');

// Error translation
Translation::error('not_found');
```

---

### Cached Translations

```php
// Get cached translation
Translation::cached('messages.welcome');
```

---

### Locale Management

```php
// Get current locale
$locale = Translation::getLocale();

// Set locale
Translation::setLocale('id');

// Get fallback locale
$fallback = Translation::getFallback();

// Set fallback locale
Translation::setFallback('id');
```

---

### Translation Checks

```php
// Check if translation exists
if (Translation::has('messages.welcome')) {
    // Translation exists
}

// Get all translations for locale
$translations = Translation::all('en');
```

---

## 📡 Translation Events

### LocaleChanged

Fired when the application locale is changed.

```php
use Canvastack\Canvastack\Events\Translation\LocaleChanged;

Event::listen(LocaleChanged::class, function ($event) {
    $newLocale = $event->locale;
    $previousLocale = $event->previousLocale;
    
    // Handle locale change
});
```

---

### TranslationLoaded

Fired when translations are loaded for a locale.

```php
use Canvastack\Canvastack\Events\Translation\TranslationLoaded;

Event::listen(TranslationLoaded::class, function ($event) {
    $locale = $event->locale;
    $translations = $event->translations;
    
    // Handle translation loaded
});
```

---

### TranslationMissing

Fired when a translation key is not found.

```php
use Canvastack\Canvastack\Events\Translation\TranslationMissing;

Event::listen(TranslationMissing::class, function ($event) {
    $key = $event->key;
    $locale = $event->locale;
    $replace = $event->replace;
    
    // Log missing translation
    Log::warning("Missing translation: {$key} for locale: {$locale}");
});
```

---

### TranslationCacheCleared

Fired when the translation cache is cleared.

```php
use Canvastack\Canvastack\Events\Translation\TranslationCacheCleared;

Event::listen(TranslationCacheCleared::class, function ($event) {
    $locale = $event->locale; // null if all locales cleared
    
    // Handle cache clear
});
```

---

## 🔌 TranslationManager

The `TranslationManager` is the core class that powers the Translation API.

### Accessing the Manager

```php
// Via service container
$manager = app('canvastack.translation');

// Via facade
use Canvastack\Canvastack\Support\Facades\Translation;
```

---

### Available Methods

#### get()

Get a translation.

```php
$manager->get('messages.welcome', ['name' => 'John'], 'en');
```

---

#### choice()

Get a translation with pluralization.

```php
$manager->choice('messages.items', 5, ['count' => 5], 'en');
```

---

#### fallback()

Get a translation with fallback.

```php
$manager->fallback('nonexistent.key', 'Default Value', [], 'en');
```

---

#### ifExists()

Get a translation only if it exists.

```php
$translation = $manager->ifExists('messages.welcome', [], 'en');
```

---

#### withContext()

Get a translation with context.

```php
$manager->withContext('button.save', 'admin', [], 'en');
```

---

#### component()

Get a component translation.

```php
$manager->component('form', 'submit', [], 'en');
```

---

#### ui()

Get a UI translation.

```php
$manager->ui('buttons.save', [], 'en');
```

---

#### validation()

Get a validation translation.

```php
$manager->validation('required', ['attribute' => 'email'], 'en');
```

---

#### error()

Get an error translation.

```php
$manager->error('not_found', [], 'en');
```

---

#### cached()

Get a cached translation.

```php
$manager->cached('messages.welcome', [], 'en');
```

---

#### has()

Check if a translation exists.

```php
if ($manager->has('messages.welcome', 'en')) {
    // Translation exists
}
```

---

#### all()

Get all translations for a locale.

```php
$translations = $manager->all('en');
```

---

#### getLocale()

Get the current locale.

```php
$locale = $manager->getLocale();
```

---

#### setLocale()

Set the current locale.

```php
$manager->setLocale('id');
```

---

#### getFallback()

Get the fallback locale.

```php
$fallback = $manager->getFallback();
```

---

#### setFallback()

Set the fallback locale.

```php
$manager->setFallback('id');
```

---

## 💡 Usage Examples

### Example 1: Context-Aware Translations

```php
// In admin panel
echo trans_with_context('button.save', 'admin');
// Tries: admin.button.save, then button.save

// In public frontend
echo trans_with_context('button.save', 'public');
// Tries: public.button.save, then button.save
```

---

### Example 2: Component Translations

```blade
{{-- In form component --}}
<button type="submit">
    @transComponent('form', 'submit_button')
</button>

{{-- In table component --}}
<div class="empty-state">
    @transComponent('table', 'no_data')
</div>
```

---

### Example 3: Cached Translations

```php
// High-traffic pages benefit from cached translations
$welcomeMessage = trans_cached('messages.welcome');
$menuItems = trans_cached('navigation.menu');
```

---

### Example 4: Event-Driven Translation Management

```php
// Listen for locale changes
Event::listen(LocaleChanged::class, function ($event) {
    // Clear user-specific caches
    Cache::tags(['user-' . auth()->id()])->flush();
    
    // Log locale change
    Log::info("User changed locale to: {$event->locale}");
});

// Listen for missing translations
Event::listen(TranslationMissing::class, function ($event) {
    // Send notification to developers
    Notification::send(
        User::developers(),
        new MissingTranslationNotification($event->key, $event->locale)
    );
});
```

---

### Example 5: Fallback Translations

```php
// Use fallback for optional translations
$customMessage = trans_fallback('custom.message', 'Default message');

// Check if translation exists before using
if ($translation = trans_if_exists('optional.feature')) {
    echo $translation;
}
```

---

## 🧪 Testing

### Unit Tests

```php
use Canvastack\Canvastack\Support\Facades\Translation;

/** @test */
public function it_can_translate_with_fallback()
{
    $result = Translation::fallback('nonexistent.key', 'Default');
    
    $this->assertEquals('Default', $result);
}
```

---

### Feature Tests

```php
use Canvastack\Canvastack\Events\Translation\LocaleChanged;
use Illuminate\Support\Facades\Event;

/** @test */
public function it_fires_locale_changed_event()
{
    Event::fake();
    
    Translation::setLocale('id');
    
    Event::assertDispatched(LocaleChanged::class);
}
```

---

## 🔗 Related Documentation

- [Internationalization (i18n) System](./README.md)
- [Locale Management](./locale-management.md)
- [Translation Files](./translation-files.md)
- [RTL Support](./rtl-support.md)

---

**Last Updated**: 2026-02-27  
**Version**: 1.0.0  
**Status**: Published
