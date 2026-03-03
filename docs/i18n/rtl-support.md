# RTL (Right-to-Left) Support

Complete guide to RTL language support in CanvaStack for languages like Arabic, Hebrew, Persian, and Urdu.

---

## 📦 Location

- **CSS File**: `resources/css/rtl.css`
- **Main CSS**: `resources/css/canvastack.css` (imports rtl.css)
- **JavaScript**: `resources/js/canvastack.js` (RTLManager class)
- **Tailwind Config**: `tailwind.config.js` (RTL plugin)
- **Locale Manager**: `src/Support/Localization/LocaleManager.php`

---

## 🎯 Features

- Automatic RTL detection based on locale
- Comprehensive CSS utilities for RTL layouts
- JavaScript RTL manager for dynamic direction changes
- Tailwind CSS RTL variants and utilities
- Support for all UI components in RTL mode
- Bidirectional text support
- RTL-aware animations and transitions

---

## 📖 Basic Usage

### Automatic RTL Detection

RTL direction is automatically applied based on the current locale:

```php
// In your controller
app('canvastack.locale')->setLocale('ar'); // Arabic (RTL)

// In your Blade templates
<html dir="{{ app('canvastack.locale')->getDirection() }}">
```

### Supported RTL Locales

By default, the following locales are treated as RTL:

- `ar` - Arabic
- `he` - Hebrew
- `fa` - Persian (Farsi)
- `ur` - Urdu

Configure in `config/canvastack.php`:

```php
'localization' => [
    'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
],
```

---

## 🔧 CSS Utilities

### Direction Classes

```html
<!-- Force RTL direction -->
<div class="force-rtl">محتوى عربي</div>

<!-- Force LTR direction (for numbers, codes) -->
<div class="force-ltr">123-456-7890</div>

<!-- Bidirectional text -->
<div class="bidi-text">Mixed content</div>
```

### Margin Utilities

```html
<!-- Margin start (left in LTR, right in RTL) -->
<div class="ms-4">Content</div>

<!-- Margin end (right in LTR, left in RTL) -->
<div class="me-4">Content</div>
```

### Padding Utilities

```html
<!-- Padding start -->
<div class="ps-4">Content</div>

<!-- Padding end -->
<div class="pe-4">Content</div>
```

### Border Utilities

```html
<!-- Border start -->
<div class="border-start">Content</div>

<!-- Border end -->
<div class="border-end">Content</div>

<!-- Rounded start -->
<div class="rounded-start">Content</div>
```

### Position Utilities

```html
<!-- Position start (left in LTR, right in RTL) -->
<div class="start-0">Content</div>

<!-- Position end -->
<div class="end-0">Content</div>
```

### Text Alignment

```html
<!-- Text start (left in LTR, right in RTL) -->
<div class="text-start">Content</div>

<!-- Text end -->
<div class="text-end">Content</div>
```

### Flexbox Utilities

```html
<!-- Flex row start -->
<div class="flex flex-row-start">
    <div>Item 1</div>
    <div>Item 2</div>
</div>

<!-- Justify start -->
<div class="flex justify-start">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

---

## 🎮 JavaScript API

### RTL Manager

The RTL Manager handles dynamic direction changes:

```javascript
// Get current direction
const direction = rtlManager.getDirection(); // 'ltr' or 'rtl'

// Check if RTL
const isRTL = rtlManager.isRTL(); // true or false

// Set direction
rtlManager.setDirection('rtl');

// Toggle direction
rtlManager.toggleDirection();
```

### Global Functions

```javascript
// Set direction (backward compatible)
setDirection('rtl');

// Toggle direction
toggleDirection();
```

### Events

Listen for direction changes:

```javascript
window.addEventListener('rtl:changed', (event) => {
    console.log('Direction changed to:', event.detail.direction);
    console.log('Is RTL:', event.detail.isRTL);
});
```

---

## 🎨 Tailwind CSS RTL Variants

### Using RTL Variants

```html
<!-- Apply styles only in RTL mode -->
<div class="rtl:text-right rtl:mr-4">
    Content
</div>

<!-- Apply styles only in LTR mode -->
<div class="ltr:text-left ltr:ml-4">
    Content
</div>
```

### RTL-Specific Utilities

```html
<!-- Flip horizontally in RTL -->
<img src="arrow.svg" class="rtl-flip" />

<!-- Rotate 180° in RTL -->
<i class="icon rtl-rotate-180"></i>
```

---

## 📝 Component Examples

### Button with Icon

```html
<!-- LTR: Icon on left -->
<!-- RTL: Icon on right (automatically) -->
<button class="btn btn-primary">
    <i data-lucide="plus" class="w-4 h-4 me-2"></i>
    {{ __('ui.buttons.add') }}
</button>
```

### Form Input with Icon

```html
<div class="relative">
    <i data-lucide="search" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4"></i>
    <input 
        type="text" 
        class="ps-10 pe-4 py-2 w-full rounded-xl"
        placeholder="{{ __('ui.form.search') }}"
    >
</div>
```

### Navigation Menu

```html
<nav class="flex items-center gap-4">
    <a href="/" class="flex items-center gap-2">
        <i data-lucide="home" class="w-5 h-5"></i>
        <span>{{ __('ui.navigation.home') }}</span>
    </a>
</nav>
```

### Card with Actions

```html
<div class="card">
    <div class="card-body">
        <h3 class="text-start">{{ __('ui.card.title') }}</h3>
        <p class="text-start">{{ __('ui.card.description') }}</p>
        <div class="flex justify-end gap-2 mt-4">
            <button class="btn btn-secondary">{{ __('ui.buttons.cancel') }}</button>
            <button class="btn btn-primary">{{ __('ui.buttons.save') }}</button>
        </div>
    </div>
</div>
```

---

## 🔍 Component-Specific RTL Support

### Sidebar

The sidebar automatically adjusts for RTL:

```html
<!-- LTR: Sidebar on left, content margin-left -->
<!-- RTL: Sidebar on right, content margin-right -->
<div id="sidebar" class="fixed top-0 start-0 h-full w-64">
    <!-- Sidebar content -->
</div>

<div id="main-content" class="ms-64">
    <!-- Main content -->
</div>
```

### DataTable

Tables automatically align text to the right in RTL:

```php
$table->setContext('admin');
$table->setModel(new User());
$table->setFields(['name:Name', 'email:Email']);
$table->format();
```

### Form Builder

Forms automatically adjust labels and inputs for RTL:

```php
$form->setContext('admin');
$form->text('name', __('ui.form.name'))->required();
$form->email('email', __('ui.form.email'))->required();
```

### Modal

Modals automatically position close buttons correctly:

```html
<div class="modal">
    <div class="modal-header">
        <h3>{{ __('ui.modal.title') }}</h3>
        <button class="btn-close ms-auto"></button>
    </div>
    <div class="modal-body">
        <!-- Content -->
    </div>
</div>
```

### Dropdown

Dropdowns automatically position menus correctly:

```html
<div class="dropdown">
    <button class="btn">{{ __('ui.dropdown.options') }}</button>
    <div class="dropdown-menu">
        <a href="#">{{ __('ui.dropdown.option1') }}</a>
        <a href="#">{{ __('ui.dropdown.option2') }}</a>
    </div>
</div>
```

---

## 💡 Tips & Best Practices

### 1. Use Logical Properties

Always use logical properties instead of physical ones:

```html
<!-- ✅ Good: Uses logical properties -->
<div class="ms-4 ps-2 border-start">Content</div>

<!-- ❌ Bad: Uses physical properties -->
<div class="ml-4 pl-2 border-left">Content</div>
```

### 2. Icon Positioning

Use margin utilities for icon spacing:

```html
<!-- ✅ Good: Icon spacing adapts to direction -->
<button>
    <i class="me-2"></i>
    Text
</button>

<!-- ❌ Bad: Fixed left margin -->
<button>
    <i class="ml-2"></i>
    Text
</button>
```

### 3. Text Alignment

Use start/end instead of left/right:

```html
<!-- ✅ Good: Adapts to direction -->
<p class="text-start">Paragraph</p>

<!-- ❌ Bad: Fixed alignment -->
<p class="text-left">Paragraph</p>
```

### 4. Flexbox Direction

Use flex-row-start for automatic direction:

```html
<!-- ✅ Good: Reverses in RTL -->
<div class="flex flex-row-start">
    <div>First</div>
    <div>Second</div>
</div>

<!-- ❌ Bad: Fixed direction -->
<div class="flex flex-row">
    <div>First</div>
    <div>Second</div>
</div>
```

### 5. Numbers and Codes

Force LTR for numbers, codes, and technical content:

```html
<!-- Phone numbers -->
<span class="force-ltr">+1-234-567-8900</span>

<!-- Code snippets -->
<code class="force-ltr">const x = 10;</code>

<!-- Dates (if using Western format) -->
<time class="force-ltr">2024-02-26</time>
```

### 6. Mixed Content

Use bidirectional text support for mixed content:

```html
<p class="bidi-text">
    This is English text with عربي embedded.
</p>
```

---

## 🎭 Common Patterns

### Pattern 1: Navigation with Icons

```html
<nav class="flex items-center gap-4">
    @foreach($menuItems as $item)
        <a href="{{ $item['url'] }}" class="flex items-center gap-2">
            <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5"></i>
            <span>{{ __($item['label']) }}</span>
        </a>
    @endforeach
</nav>
```

### Pattern 2: Form with Icon Inputs

```html
<form>
    <div class="mb-4">
        <label class="block text-start mb-2">{{ __('ui.form.email') }}</label>
        <div class="relative">
            <i data-lucide="mail" class="absolute start-3 top-1/2 -translate-y-1/2"></i>
            <input type="email" class="ps-10 pe-4 py-2 w-full rounded-xl">
        </div>
    </div>
</form>
```

### Pattern 3: Card with Actions

```html
<div class="card">
    <div class="card-body">
        <div class="flex justify-between items-start mb-4">
            <h3 class="text-start">{{ __('ui.card.title') }}</h3>
            <button class="btn btn-sm">
                <i data-lucide="more-vertical"></i>
            </button>
        </div>
        <p class="text-start">{{ __('ui.card.description') }}</p>
        <div class="flex justify-end gap-2 mt-4">
            <button class="btn btn-secondary">{{ __('ui.buttons.cancel') }}</button>
            <button class="btn btn-primary">{{ __('ui.buttons.save') }}</button>
        </div>
    </div>
</div>
```

### Pattern 4: List with Icons

```html
<ul class="space-y-2">
    @foreach($items as $item)
        <li class="flex items-center gap-2">
            <i data-lucide="check" class="w-4 h-4 text-success"></i>
            <span>{{ $item }}</span>
        </li>
    @endforeach
</ul>
```

---

## 🧪 Testing

### Unit Tests

```php
public function test_rtl_locale_detection(): void
{
    $localeManager = app('canvastack.locale');
    
    // Test RTL locales
    $this->assertTrue($localeManager->isRtl('ar'));
    $this->assertTrue($localeManager->isRtl('he'));
    
    // Test LTR locales
    $this->assertFalse($localeManager->isRtl('en'));
}

public function test_direction_attribute(): void
{
    $localeManager = app('canvastack.locale');
    
    $localeManager->setLocale('ar');
    $this->assertEquals('rtl', $localeManager->getDirection());
    
    $localeManager->setLocale('en');
    $this->assertEquals('ltr', $localeManager->getDirection());
}
```

### Browser Tests

```javascript
// Test RTL direction change
describe('RTL Support', () => {
    it('should change direction to RTL', () => {
        rtlManager.setDirection('rtl');
        expect(document.documentElement.getAttribute('dir')).toBe('rtl');
    });
    
    it('should update layout for RTL', () => {
        rtlManager.setDirection('rtl');
        const mainContent = document.getElementById('main-content');
        expect(mainContent.classList.contains('mr-64')).toBe(true);
    });
});
```

---

## 🔗 Related Documentation

- [Internationalization Guide](./internationalization.md) - Complete i18n implementation
- [Locale Management](./locale-management.md) - Locale switching and detection
- [Translation System](./translation-system.md) - Translation management
- [Tailwind CSS](../frontend/tailwind-css.md) - Tailwind configuration
- [Component Documentation](../components/README.md) - All UI components

---

## 📚 Resources

- [CSS Logical Properties](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Logical_Properties)
- [RTL Styling Best Practices](https://rtlstyling.com/)
- [Bidirectional Text](https://www.w3.org/International/questions/qa-bidi-unicode-controls)
- [Arabic Typography](https://www.w3.org/International/articles/typography/arabic)

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Published
