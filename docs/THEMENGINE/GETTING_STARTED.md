# Getting Started with Theme Engine

**Version:** 2.0.0  
**Difficulty:** Beginner  
**Time:** 20 minutes

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

## Prerequisites

- PHP 8.0 or higher
- Laravel 9.x or higher
- CanvaStack 2.0.0 or higher
- Basic understanding of CSS frameworks

---

## What is the Theme Engine?

The Theme Engine is a powerful adapter system that allows CanvaStack to support multiple CSS frameworks simultaneously. Instead of hardcoding Bootstrap 4 HTML in helper functions, the Theme Engine abstracts HTML generation and delegates it to framework-specific adapters.

**Benefits:**

- ✅ Switch between Bootstrap 4, Bootstrap 5, and TailwindCSS without code changes
- ✅ Zero breaking changes - existing code continues to work
- ✅ Extensible - add custom adapters for new frameworks
- ✅ Automatic - template detection and adapter resolution
- ✅ Fallback - graceful degradation when configuration is missing

---

## Step 1: Understanding Templates

CanvaStack supports three built-in templates:

| Template Name | CSS Framework | Adapter Class | Use Case |
|---------------|---------------|---------------|----------|
| `default` | Bootstrap 4 | `DefaultAdapter` | Existing projects, backward compatibility |
| `canvasign` | Bootstrap 5 | `Bootstrap5Adapter` | Modern Bootstrap 5 projects |
| `canvas` | TailwindCSS | `TailwindAdapter` | Utility-first CSS projects |

**Template Selection:**

The active template is configured in `config/canvastack.templates.php`:

```php
return [
    'template' => 'default', // Change to 'canvasign' or 'canvas'
];
```

---

## Step 2: Check Current Template

Verify your current template configuration:

```bash
# Using Laravel Tinker
php artisan tinker

>>> config('canvastack.templates.template')
=> "default"

>>> canvastack_current_template()
=> "default"
```

---

## Step 3: Switch to Bootstrap 5 (canvasign)

### 3.1 Update Configuration

```php
// config/canvastack.templates.php
return [
    'template' => 'canvasign',
    
    // ... rest of configuration
];
```

### 3.2 Clear Caches

```bash
# Clear configuration cache
php artisan config:clear

# Clear view cache
php artisan view:clear

# Clear application cache
php artisan cache:clear
```

### 3.3 Verify Template Assets

Ensure Bootstrap 5 assets are configured:

```php
// config/canvastack.templates.php
'canvasign' => [
    'position' => [
        'top' => [
            'css' => [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            ],
            'js' => [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            ],
        ],
        'bottom' => [
            'first' => [
                'js' => ['js/canvasign-init.js'],
                'css' => ['css/canvasign-custom.css'],
            ],
            'last' => [
                'js' => ['js/canvasign-scripts.js'],
            ],
        ],
    ],
],
```

### 3.4 Test the Switch

Visit your application and verify:

- ✅ Bootstrap 5 CSS is loaded
- ✅ Modals use `data-bs-toggle` instead of `data-toggle`
- ✅ Alerts use `data-bs-dismiss` instead of `data-dismiss`
- ✅ Select elements use `form-select` class
- ✅ No console errors

**Inspect HTML:**

```html
<!-- Bootstrap 4 (default) -->
<a data-toggle="tab" class="nav-link">Tab</a>

<!-- Bootstrap 5 (canvasign) -->
<a data-bs-toggle="tab" class="nav-link">Tab</a>
```

---

## Step 4: Switch to TailwindCSS (canvas)

### 4.1 Update Configuration

```php
// config/canvastack.templates.php
return [
    'template' => 'canvas',
    
    // ... rest of configuration
];
```

### 4.2 Configure TailwindCSS Assets

```php
// config/canvastack.templates.php
'canvas' => [
    'position' => [
        'top' => [
            'js' => [
                'https://cdn.tailwindcss.com', // CDN for development
            ],
            'css' => [null],
        ],
        'bottom' => [
            'first' => [
                'js' => [null],
                'css' => ['css/canvas.css'], // Custom Tailwind styles
            ],
            'last' => [
                'js' => ['js/canvas-scripts.js'], // Custom JS for modals/tooltips
            ],
        ],
    ],
],
```

### 4.3 Clear Caches

```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

### 4.4 Test the Switch

Visit your application and verify:

- ✅ TailwindCSS is loaded
- ✅ Elements use utility classes (`flex`, `hidden`, `ml-auto`)
- ✅ No Bootstrap-specific classes (`pull-right`, `hide`, `btn-xs`)
- ✅ Custom JavaScript handles modals and tooltips
- ✅ No console errors

**Inspect HTML:**

```html
<!-- Bootstrap 4 (default) -->
<div class="pull-right hide">Content</div>

<!-- TailwindCSS (canvas) -->
<div class="ml-auto hidden">Content</div>
```

---

## Step 5: Understanding Automatic Resolution

The Theme Engine automatically resolves the correct adapter based on the active template. You don't need to change any code in your views or controllers.

### How It Works

```php
// In your view or controller
echo canvastack_form_create_header_tab('Users', 'users-tab', true);

// Behind the scenes:
// 1. Helper function calls ThemeAdapterResolver::resolve()
// 2. Resolver calls canvastack_current_template() → 'canvasign'
// 3. Resolver returns Bootstrap5Adapter instance (cached)
// 4. Helper delegates to Bootstrap5Adapter::renderTabHeader()
// 5. Bootstrap5Adapter generates Bootstrap 5 HTML
```

### Example: Form Alert

```php
// Your code (unchanged)
echo canvastack_form_alert_message('Operation successful!', 'success', 'Success', 'msg');

// Output varies by template:

// default (Bootstrap 4):
// <div class="alert alert-block alert-success" data-dismiss="alert">...</div>

// canvasign (Bootstrap 5):
// <div class="alert alert-success" data-bs-dismiss="alert">...</div>

// canvas (TailwindCSS):
// <div class="flex items-start gap-3 p-4 rounded-lg bg-green-100">...</div>
```

---

## Step 6: Working with Views

### View Path Resolution

The Theme Engine automatically resolves view paths based on the active template:

```php
// Template: default
// View path: resources/views/default/pages/admin/index.blade.php

// Template: canvasign
// View path: resources/views/canvasign/pages/admin/index.blade.php

// Template: canvas
// View path: resources/views/canvas/pages/admin/index.blade.php
```

### Fallback Behavior

If a view doesn't exist for the active template, the system falls back to the `default` template:

```php
// Template: canvasign
// Requested view: canvasign.pages.admin.custom

// If canvasign.pages.admin.custom doesn't exist:
// Falls back to: default.pages.admin.custom
```

### Creating Template-Specific Views

1. **Copy default views:**
   ```bash
   cp -r resources/views/default resources/views/canvasign
   ```

2. **Customize for Bootstrap 5:**
   ```blade
   {{-- resources/views/canvasign/pages/admin/index.blade.php --}}
   
   {{-- Update data attributes --}}
   <button data-bs-toggle="modal" data-bs-target="#myModal">
       Open Modal
   </button>
   
   {{-- Update CSS classes --}}
   <div class="d-none">Hidden content</div>
   <div class="float-end">Right aligned</div>
   ```

3. **Test the view:**
   ```bash
   # Visit the page and verify Bootstrap 5 classes are used
   ```

---

## Step 7: Working with JavaScript

### JavaScript Adapters

The Theme Engine includes JavaScript adapters that automatically detect the active template and use the correct framework API.

#### Modal Adapter

```javascript
// Your code (framework-agnostic)
CanvaStackModal.show('myModal');
CanvaStackModal.hide('myModal');
CanvaStackModal.toggle('myModal');

// Behind the scenes:
// - Detects template via window.canvastackTemplate
// - Routes to Bootstrap 4, Bootstrap 5, or custom modal API
```

#### Tooltip Adapter

```javascript
// Automatic initialization on page load
// Detects template and initializes correct tooltip library:
// - Bootstrap 4: Bootstrap 4 tooltip API
// - Bootstrap 5: Bootstrap 5 tooltip API
// - TailwindCSS: Tippy.js
```

#### CSS Class Adapter

```javascript
// Translate generic class names to framework-specific classes
const hideClass = CanvaStackClass.get('hide');
// Returns: 'hide' (BS4), 'd-none' (BS5), 'hidden' (Tailwind)

const floatRightClass = CanvaStackClass.get('float-right');
// Returns: 'pull-right' (BS4), 'float-end' (BS5), 'ml-auto' (Tailwind)
```

### Setting Template in JavaScript

The active template is exposed to JavaScript via a global variable:

```blade
{{-- In your layout file --}}
<script>
    window.canvastackTemplate = '{{ canvastack_current_template() }}';
</script>
```

---

## Step 8: Testing Your Setup

### Manual Testing Checklist

- [ ] **Configuration**
  - [ ] Template is set in `config/canvastack.templates.php`
  - [ ] Asset configuration exists for the template
  - [ ] Caches are cleared

- [ ] **Visual Verification**
  - [ ] CSS framework assets are loaded
  - [ ] UI components render correctly
  - [ ] No visual glitches or broken layouts

- [ ] **HTML Inspection**
  - [ ] Correct data attributes (`data-toggle` vs `data-bs-toggle`)
  - [ ] Correct CSS classes (`hide` vs `d-none` vs `hidden`)
  - [ ] No Bootstrap 4 classes in Bootstrap 5/Tailwind templates

- [ ] **JavaScript Functionality**
  - [ ] Modals open and close correctly
  - [ ] Tooltips display correctly
  - [ ] No console errors
  - [ ] Form submissions work

- [ ] **Fallback Behavior**
  - [ ] Missing views fall back to default template
  - [ ] Missing asset config falls back to default
  - [ ] No exceptions thrown

### Automated Testing

```bash
# Run Theme Engine tests
php artisan test --filter=ThemeAdapter

# Run property-based tests
php artisan test tests/Property/ThemeAdapterPropertiesTest.php

# Run integration tests
php artisan test tests/Integration/ThemeEngineIntegrationTest.php
```

---

## Step 9: Common Use Cases

### Use Case 1: Gradual Migration to Bootstrap 5

**Scenario:** You have an existing Bootstrap 4 project and want to migrate to Bootstrap 5 gradually.

**Solution:**

1. Keep `template` as `'default'` (Bootstrap 4)
2. Create `canvasign` views for new pages
3. Test new pages with Bootstrap 5
4. Once satisfied, switch `template` to `'canvasign'`
5. Update remaining views as needed

### Use Case 2: Multi-Tenant Application

**Scenario:** Different tenants want different CSS frameworks.

**Solution:**

```php
// In your middleware or service provider
$tenant = auth()->user()->tenant;

config(['canvastack.templates.template' => $tenant->preferred_template]);

// Now each tenant sees their preferred framework
```

### Use Case 3: A/B Testing

**Scenario:** Test user preference between Bootstrap 5 and TailwindCSS.

**Solution:**

```php
// In your controller
$template = session('ab_test_template', 'canvasign');
config(['canvastack.templates.template' => $template]);

// Track user interactions and preferences
```

### Use Case 4: Custom Framework

**Scenario:** You want to use a framework not supported by default (e.g., Foundation, Bulma).

**Solution:**

1. Create custom adapter implementing `ThemeAdapterInterface`
2. Register adapter with `ThemeAdapterResolver`
3. Configure assets in `config/canvastack.templates.php`
4. Set template to your custom name

See [Custom Adapter Example](./examples/custom-adapter-example.php) for implementation.

---

## Step 10: Troubleshooting

### Problem: Template Not Switching

**Symptoms:** Changed template in config but still seeing old template.

**Solution:**
```bash
# Clear all caches
php artisan config:clear
php artisan view:clear
php artisan cache:clear

# Verify configuration
php artisan tinker
>>> config('canvastack.templates.template')
```

### Problem: Assets Not Loading

**Symptoms:** CSS/JS files not loading, broken layout.

**Solution:**
```php
// Verify asset configuration exists
// config/canvastack.templates.php
'canvasign' => [
    'position' => [
        'top' => [
            'css' => ['https://cdn.jsdelivr.net/.../bootstrap.min.css'],
            'js' => ['https://cdn.jsdelivr.net/.../bootstrap.bundle.min.js'],
        ],
    ],
],

// Check browser console for 404 errors
// Verify CDN URLs are accessible
```

### Problem: Modals Not Working

**Symptoms:** Modals don't open, JavaScript errors in console.

**Solution:**
```javascript
// Verify window.canvastackTemplate is set
console.log(window.canvastackTemplate);

// Verify modal adapter is loaded
console.log(typeof CanvaStackModal);

// Check for JavaScript errors
// Ensure Bootstrap/Tailwind JS is loaded before modal adapter
```

### Problem: Views Not Found

**Symptoms:** View not found errors after switching template.

**Solution:**
```bash
# Copy default views to new template directory
cp -r resources/views/default resources/views/canvasign

# Or rely on fallback behavior (automatic)
# System will use default views if template views don't exist
```

### Problem: Custom Adapter Not Working

**Symptoms:** Registered custom adapter but not being used.

**Solution:**
```php
// Verify registration happens before template resolution
// In AppServiceProvider::boot()
ThemeAdapterResolver::register('custom', CustomAdapter::class);

// Verify template name matches
config(['canvastack.templates.template' => 'custom']);

// Clear caches
php artisan config:clear
```

---

## Next Steps

Now that you have a basic understanding of the Theme Engine, explore:

1. **Advanced Features**
   - [Architecture Documentation](./ARCHITECTURE.md) - Technical design details
   - [API Reference](./API_REFERENCE.md) - Complete method documentation
   - [Template Configuration](./TEMPLATE_CONFIGURATION.md) - Advanced configuration

2. **Framework-Specific Guides**
   - [Bootstrap 4 Guide](./BOOTSTRAP4_GUIDE.md) - DefaultAdapter details
   - [Bootstrap 5 Guide](./BOOTSTRAP5_GUIDE.md) - Bootstrap5Adapter details
   - [TailwindCSS Guide](./TAILWIND_GUIDE.md) - TailwindAdapter details

3. **Advanced Topics**
   - [JavaScript Integration](./JAVASCRIPT_INTEGRATION.md) - JavaScript adapters
   - [Testing](./TESTING.md) - Property-based and unit testing
   - [Troubleshooting](./TROUBLESHOOTING.md) - Common issues and solutions

4. **Examples**
   - `examples/form-rendering-example.php` - Form component examples
   - `examples/table-rendering-example.php` - Table component examples
   - `examples/modal-example.php` - Modal implementation
   - `examples/custom-adapter-example.php` - Creating custom adapters

---

## Support

- **Documentation:** `vendor/canvastack/canvastack/docs/THEMENGINE/`
- **Issues:** Report on GitHub
- **Email:** support@canvastack.com

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may this guide help you get started with the Theme Engine.
