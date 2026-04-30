# Theme Engine Documentation

**Version:** 2.0.0  
**Last Updated:** April 4, 2026  
**Status:** Production Ready

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

Alhamdulillah, this documentation covers the comprehensive Theme Engine system for CanvaStack framework, enabling multi-framework CSS support with zero breaking changes.

## Overview

The CanvaStack Theme Engine provides a powerful adapter system that enables multiple CSS framework themes to work in parallel. The system abstracts HTML generation from framework-specific implementation, allowing seamless switching between Bootstrap 4, Bootstrap 5, and TailwindCSS.

**Supported Frameworks:**

| Template | Framework | Adapter | Status |
|----------|-----------|---------|--------|
| `default` | Bootstrap 4 | `DefaultAdapter` | ✅ Production |
| `canvasign` | Bootstrap 5 | `Bootstrap5Adapter` | ✅ Production |
| `canvas` | TailwindCSS | `TailwindAdapter` | ✅ Production |

**Key Features:**

- ✅ **Zero Breaking Changes** - `default` template produces byte-for-byte identical output
- ✅ **Multi-Framework Support** - Bootstrap 4, Bootstrap 5, and TailwindCSS
- ✅ **Automatic Resolution** - Template-based adapter selection
- ✅ **Extensible Architecture** - Register custom adapters for new frameworks
- ✅ **Singleton Pattern** - One adapter instance per template per request
- ✅ **Fallback Strategy** - Graceful degradation to DefaultAdapter
- ✅ **JavaScript Adapters** - Framework-agnostic modal, tooltip, and CSS class handling
- ✅ **100% Backward Compatible** - No breaking changes to existing code

## Success Metrics

| Metric | Achievement |
|--------|-------------|
| Framework Support | 3 frameworks (Bootstrap 4, 5, TailwindCSS) |
| Breaking Changes | 0 (100% backward compatible) |
| Adapter Methods | 14+ methods per adapter |
| Test Coverage | 8 property-based tests + unit tests |
| JavaScript Adapters | 3 adapters (modal, tooltip, CSS class) |
| Fallback Strategy | 100% graceful degradation |

## Documentation Structure

### 📋 Core Documentation

- **[README.md](./README.md)** - This file, overview and quick start guide
- **[GETTING_STARTED.md](./GETTING_STARTED.md)** - Step-by-step tutorial for new users
- **[ARCHITECTURE.md](./ARCHITECTURE.md)** - Technical architecture and design decisions
- **[API_REFERENCE.md](./API_REFERENCE.md)** - Complete API documentation with examples
- **[TEMPLATE_CONFIGURATION.md](./TEMPLATE_CONFIGURATION.md)** - Configuration guide for templates and assets
- **[MIGRATION_GUIDE.md](./MIGRATION_GUIDE.md)** - Migration instructions for existing projects

### 📚 Framework-Specific Guides

- **[BOOTSTRAP4_GUIDE.md](./BOOTSTRAP4_GUIDE.md)** - Bootstrap 4 implementation details
- **[BOOTSTRAP5_GUIDE.md](./BOOTSTRAP5_GUIDE.md)** - Bootstrap 5 implementation details
- **[TAILWIND_GUIDE.md](./TAILWIND_GUIDE.md)** - TailwindCSS implementation details

### 🏗️ Creating Templates

Step-by-step guides for building complete templates from scratch:

- **[guides/CREATING_TEMPLATES.md](./guides/CREATING_TEMPLATES.md)** - General overview: choosing a framework, architecture, workflow, configuration deep-dive
- **[guides/BOOTSTRAP4_TEMPLATE_GUIDE.md](./guides/BOOTSTRAP4_TEMPLATE_GUIDE.md)** - E2E guide for building the `default` (Bootstrap 4) template
- Bootstrap 5 Template Guide *(coming soon)*
- TailwindCSS Template Guide *(coming soon)*

**Quick links:**
- I want to create a new template → [CREATING_TEMPLATES.md](./guides/CREATING_TEMPLATES.md)
- I want to build a Bootstrap 4 template → [BOOTSTRAP4_TEMPLATE_GUIDE.md](./guides/BOOTSTRAP4_TEMPLATE_GUIDE.md)
- I want to understand the config file → [CREATING_TEMPLATES.md#section-7](./guides/CREATING_TEMPLATES.md#section-7-template-configuration-deep-dive)
- I want to see working code examples → [examples/bootstrap4-template-example/](./examples/bootstrap4-template-example/)

### 🔧 Advanced Documentation

- **[JAVASCRIPT_INTEGRATION.md](./JAVASCRIPT_INTEGRATION.md)** - JavaScript adapter documentation
- **[TESTING.md](./TESTING.md)** - Testing strategy and instructions
- **[TROUBLESHOOTING.md](./TROUBLESHOOTING.md)** - Common issues and solutions

### 📁 Additional Resources

- **Examples:** `examples/` - Practical code examples
- **Test Suites:** `tests/` - Comprehensive test coverage

## Quick Start

### 1. Set Active Template

```php
// config/canvastack.templates.php
return [
    'template' => 'canvasign', // or 'default', 'canvas'
];
```

### 2. Template Automatically Resolved

```php
// In your views - no code changes needed!
// The system automatically uses the correct adapter

// This helper function works with all templates:
echo canvastack_form_create_header_tab('Users', 'users-tab', true);

// Output varies by template:
// default:   <li class="nav-item"><a data-toggle="tab"...
// canvasign: <li class="nav-item"><a data-bs-toggle="tab"...
// canvas:    <div class="flex border-b cursor-pointer"...
```

### 3. Configure Template Assets

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
    ],
],
```

### 4. Create Custom Adapter (Optional)

```php
use Canvastack\Canvastack\Library\Theme\ThemeAdapterInterface;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;

class MaterialAdapter implements ThemeAdapterInterface
{
    public function renderTabHeader(string $data, string $pointer, 
                                   string|false $active, string|false $class): string
    {
        // Material Design implementation
        return '<div class="mdc-tab">' . htmlspecialchars($data) . '</div>';
    }
    
    // ... implement other methods
}

// Register custom adapter
ThemeAdapterResolver::register('material', MaterialAdapter::class);
```

For detailed instructions, see [Getting Started Guide](./GETTING_STARTED.md).

## Key Features

### 🎨 Multi-Framework Support

**Bootstrap 4 (default template)**
- Uses `data-toggle`, `data-dismiss` attributes
- Chosen.js for select elements
- Bootstrap 4 specific classes (`pull-right`, `hide`, `btn-xs`)
- 100% backward compatible with existing code

**Bootstrap 5 (canvasign template)**
- Uses `data-bs-toggle`, `data-bs-dismiss` attributes
- Native `form-select` elements
- Bootstrap 5 specific classes (`float-end`, `d-none`, `btn-sm`)
- No `alert-block` class (removed in BS5)

**TailwindCSS (canvas template)**
- Utility-first CSS classes
- Custom JavaScript for modals and tooltips
- Tailwind-specific classes (`hidden`, `ml-auto`, `flex`)
- No Bootstrap dependencies

See [Framework-Specific Guides](#framework-specific-guides) for detailed documentation.

### 🔄 Automatic Adapter Resolution

The Theme Engine automatically resolves the correct adapter based on the active template:

```php
// System automatically detects template and uses correct adapter
$adapter = ThemeAdapterResolver::resolve();

// Returns:
// - DefaultAdapter for 'default' template
// - Bootstrap5Adapter for 'canvasign' template
// - TailwindAdapter for 'canvas' template
// - DefaultAdapter for unknown templates (fallback)
```

**Singleton Pattern:**
- One adapter instance per template per request
- Cached for performance
- Reset between requests automatically

See [Architecture Documentation](./ARCHITECTURE.md) for technical details.

### 🛡️ Fallback Strategy

The Theme Engine never breaks when configuration is missing:

1. **Unknown Template** → Falls back to `DefaultAdapter`
2. **Missing View** → Falls back to `default.pages.admin` view
3. **Missing Asset Config** → Falls back to `admin.default` configuration
4. **Null Template Name** → Uses `'default'` as template name

**Zero Breaking Changes:**
- All existing code continues to work
- No exceptions thrown for missing configuration
- Graceful degradation ensures stability

See [Migration Guide](./MIGRATION_GUIDE.md) for upgrade instructions.

### 🔌 JavaScript Adapters

Framework-agnostic JavaScript adapters handle browser-side functionality:

**Modal Adapter** (`canvastack-modal-adapter.js`)
- Detects active template via `window.canvastackTemplate`
- Routes to Bootstrap 4, Bootstrap 5, or custom modal API
- Handles show, hide, toggle operations

**Tooltip Adapter** (`canvastack-tooltip-adapter.js`)
- Initializes tooltips based on active framework
- Supports Bootstrap 4, Bootstrap 5, and Tippy.js (Tailwind)
- Automatic initialization on page load

**CSS Class Adapter** (`canvastack-class-adapter.js`)
- Translates generic class names to framework-specific classes
- Example: `hide` → `hide` (BS4), `d-none` (BS5), `hidden` (Tailwind)
- Enables framework-agnostic JavaScript code

See [JavaScript Integration](./JAVASCRIPT_INTEGRATION.md) for implementation details.

### 📝 ThemeAdapterInterface

All adapters implement a consistent interface with 14+ methods:

**Form Methods:**
- `renderTabHeader()` - Tab navigation headers
- `renderTabContent()` - Tab content panes
- `renderAlertMessage()` - Dismissable alerts
- `renderCheckList()` - Checkbox elements
- `renderSelectBox()` - Select dropdowns
- `renderModalWrapper()` - Modal containers

**Table Methods:**
- `renderFilterModal()` - Table filter modals
- `renderActionButtons()` - Row action buttons
- `getTableClass()` - Table CSS classes

**Utility Methods:**
- `getSelectBoxClass()` - Select element classes
- `getDataToggleAttribute()` - Toggle attribute name
- `getDismissAttribute()` - Dismiss attribute name
- `getHideClass()` - Hide CSS class
- `getFloatRightClass()` - Float right CSS class

See [API Reference](./API_REFERENCE.md) for complete method documentation.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     Existing Helper Functions                    │
│  canvastack_form_create_header_tab()                            │
│  canvastack_form_alert_message()                                │
│  canvastack_form_checkList()                                    │
│  canvastack_form_selectbox()                                    │
│  canvastack_modal_content_html()                                │
│  canvastack_table_action_button()                               │
└──────────────────────────┬──────────────────────────────────────┘
                           │ delegates to
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                    ThemeAdapterResolver                          │
│  resolve() → canvastack_current_template() → cached instance   │
│  register(string $template, string $adapterClass)              │
└──────────────────────────┬──────────────────────────────────────┘
                           │ returns
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                  ThemeAdapterInterface                           │
│  14+ methods for rendering HTML components                      │
└──────────┬────────────────────┬────────────────────┬───────────┘
           │                    │                    │
           ▼                    ▼                    ▼
  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
  │  DefaultAdapter │  │Bootstrap5Adapter│  │ TailwindAdapter │
  │  (Bootstrap 4)  │  │  (Bootstrap 5)  │  │  (TailwindCSS)  │
  └─────────────────┘  └─────────────────┘  └─────────────────┘
```

**Key Components:**

1. **ThemeAdapterInterface** - Contract defining 14+ methods
2. **ThemeAdapterResolver** - Singleton resolver with registry
3. **DefaultAdapter** - Bootstrap 4 implementation (backward compatible)
4. **Bootstrap5Adapter** - Bootstrap 5 implementation
5. **TailwindAdapter** - TailwindCSS implementation

See [Architecture Documentation](./ARCHITECTURE.md) for detailed design.

## Configuration Files

### 1. Template Configuration

**File:** `config/canvastack.templates.php`

Configure active template and asset loading:

```php
return [
    // Active template
    'template' => 'canvasign', // 'default', 'canvasign', or 'canvas'
    
    // Template-specific configurations
    'canvasign' => [
        'position' => [
            'top' => [
                'css' => ['https://cdn.jsdelivr.net/.../bootstrap.min.css'],
                'js' => ['https://cdn.jsdelivr.net/.../bootstrap.bundle.min.js'],
            ],
            'bottom' => [
                'first' => ['js' => ['js/canvasign-init.js']],
                'last' => ['js' => ['js/canvasign-scripts.js']],
            ],
        ],
    ],
];
```

See [Template Configuration Guide](./TEMPLATE_CONFIGURATION.md) for all options.

### 2. Adapter Registration

Register custom adapters programmatically:

```php
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;

// Register custom adapter
ThemeAdapterResolver::register('material', MaterialAdapter::class);

// Now 'material' template will use MaterialAdapter
```

## Testing

The Theme Engine includes comprehensive test coverage:

### Property-Based Tests

8 correctness properties tested with 100+ iterations each:

1. **Property 1:** DefaultAdapter output identical to existing helpers
2. **Property 2:** ThemeAdapterResolver always returns ThemeAdapterInterface
3. **Property 3:** Fallback to DefaultAdapter for unregistered templates
4. **Property 4:** All adapter methods never return null
5. **Property 5:** Singleton per request - resolve() returns same instance
6. **Property 6:** Bootstrap5Adapter doesn't use Bootstrap 4 attributes
7. **Property 7:** TailwindAdapter doesn't use Bootstrap-specific classes
8. **Property 8:** Blade view path resolution follows active template

### Unit Tests

- Deterministic value tests for all utility methods
- Delegation wiring verification
- Edge case handling (null/empty template names)
- Configuration structure validation

### Integration Tests

- Template component initialization with different templates
- View path resolution with existing and missing views
- End-to-end rendering with all three templates

See [Testing Documentation](./TESTING.md) for running tests.

## Migration from Existing Code

**Zero-Effort Migration:**

All existing code continues to work without modifications:
- No breaking changes to public API
- All helper functions work identically
- Default template produces identical output
- No configuration changes required

**Optional Enhancements:**

1. **Switch to Bootstrap 5:**
   ```php
   // config/canvastack.templates.php
   'template' => 'canvasign',
   ```

2. **Switch to TailwindCSS:**
   ```php
   // config/canvastack.templates.php
   'template' => 'canvas',
   ```

3. **Create Custom Adapter:**
   ```php
   ThemeAdapterResolver::register('custom', CustomAdapter::class);
   ```

See [Migration Guide](./MIGRATION_GUIDE.md) for detailed instructions.

## Framework Comparison

| Feature | Bootstrap 4 (`default`) | Bootstrap 5 (`canvasign`) | TailwindCSS (`canvas`) |
|---------|-------------|-------------|-------------|
| Toggle Attribute | `data-toggle` | `data-bs-toggle` | `data-toggle` (custom) |
| Dismiss Attribute | `data-dismiss` | `data-bs-dismiss` | `data-dismiss` (custom) |
| Select Class | `chosen-select` | `form-select` | `form-input` |
| Hide Class | `hide` | `d-none` | `hidden` |
| Float Right | `pull-right` | `float-end` | `ml-auto` |
| Button Size | `btn-xs` | `btn-sm` | Custom utility |
| Alert Block | `alert-block` | Not used | Not used |
| Modal API | Bootstrap 4 | Bootstrap 5 | Custom JS |
| Select Plugin | Chosen.js | Choices.js | Native / Tom Select |
| jQuery Required | Yes | Optional | No |
| IE 11 Support | ✅ | ❌ | ❌ |

See [Creating Templates Guide](./guides/CREATING_TEMPLATES.md#section-4-framework-comparison-table) for the full comparison.

See framework-specific guides for complete comparisons.

## Examples

### Basic Form Rendering

```php
// Works with all templates automatically
echo canvastack_form_create_header_tab('Users', 'users-tab', true);
echo canvastack_form_alert_message('Success!', 'success', 'Done', 'msg');
echo canvastack_form_checkList('terms', '1', 'I agree', false, 'primary');
echo canvastack_form_selectbox('country', $countries, 'US', [], true);
```

### Table Rendering

```php
// Works with all templates automatically
echo canvastack_modal_content_html('filter-modal', 'Filter Users', $elements);
echo canvastack_table_action_button($row, 'id', $url, ['view', 'edit']);
```

### Custom Adapter

```php
class MaterialAdapter implements ThemeAdapterInterface
{
    public function renderTabHeader(string $data, string $pointer, 
                                   string|false $active, string|false $class): string
    {
        $activeClass = $active ? ' mdc-tab--active' : '';
        $customClass = $class ? ' ' . htmlspecialchars($class) : '';
        
        return sprintf(
            '<button class="mdc-tab%s%s" data-tab="%s">
                <span class="mdc-tab__content">
                    <span class="mdc-tab__text-label">%s</span>
                </span>
            </button>',
            $activeClass,
            $customClass,
            htmlspecialchars($pointer),
            htmlspecialchars($data)
        );
    }
    
    // ... implement other 13 methods
}

// Register and use
ThemeAdapterResolver::register('material', MaterialAdapter::class);
```

See `examples/` directory for more practical examples.

## Troubleshooting

### Template Not Switching

**Problem:** Changed template in config but still seeing old template.

**Solution:**
```bash
# Clear configuration cache
php artisan config:clear

# Clear view cache
php artisan view:clear

# Verify template setting
php artisan tinker
>>> config('canvastack.templates.template')
```

### Assets Not Loading

**Problem:** CSS/JS assets not loading for new template.

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
```

### Custom Adapter Not Working

**Problem:** Registered custom adapter but not being used.

**Solution:**
```php
// Verify registration
ThemeAdapterResolver::register('custom', CustomAdapter::class);

// Verify template name matches
// config/canvastack.templates.php
'template' => 'custom', // Must match registration name

// Clear cache
php artisan config:clear
```

See [Troubleshooting Guide](./TROUBLESHOOTING.md) for more solutions.

## Support & Resources

### Documentation

- **Main Documentation:** `vendor/canvastack/canvastack/docs/THEMENGINE/`
- **Getting Started:** [GETTING_STARTED.md](./GETTING_STARTED.md)
- **API Reference:** [API_REFERENCE.md](./API_REFERENCE.md)
- **Architecture:** [ARCHITECTURE.md](./ARCHITECTURE.md)

### Examples

- **Form Examples:** `examples/form-rendering-example.php`
- **Table Examples:** `examples/table-rendering-example.php`
- **Modal Examples:** `examples/modal-example.php`
- **Custom Adapter:** `examples/custom-adapter-example.php`

### Test Coverage

- **Property Tests:** 8 properties, 100+ iterations each
- **Unit Tests:** All adapter methods tested
- **Integration Tests:** End-to-end template rendering

### Getting Help

1. Check the documentation in this directory
2. Review the [Troubleshooting Guide](./TROUBLESHOOTING.md)
3. Check the [Migration Guide](./MIGRATION_GUIDE.md) for upgrade issues
4. Review framework-specific guides for implementation details
5. Check application logs in `storage/logs/`

## Version History

- **v2.0.0** (April 2026) - Theme Engine implementation
  - Multi-framework support (Bootstrap 4, 5, TailwindCSS)
  - Zero breaking changes
  - JavaScript adapters
  - Comprehensive documentation

---

## Roadmap

### Completed (v2.0.0)
- ✅ ThemeAdapterInterface with 14+ methods
- ✅ ThemeAdapterResolver with singleton pattern
- ✅ DefaultAdapter (Bootstrap 4)
- ✅ Bootstrap5Adapter (Bootstrap 5)
- ✅ TailwindAdapter (TailwindCSS)
- ✅ JavaScript adapters (modal, tooltip, CSS class)
- ✅ Property-based testing (8 properties)
- ✅ Comprehensive documentation

### Future Enhancements
- Foundation framework support
- Bulma framework support
- UIKit framework support
- Custom theme builder UI
- Theme preview system
- A/B testing support

---

## Contributing

We welcome contributions! To add a new framework adapter:

1. Implement `ThemeAdapterInterface`
2. Add configuration to `config/canvastack.templates.php`
3. Create framework-specific guide
4. Add property-based tests
5. Submit pull request

See [Contributing Guidelines](../../CONTRIBUTING.md) for details.

---

## License

CanvaStack is proprietary software.  
Copyright © 2018-2026 CanvaStack. All rights reserved.

---

## Acknowledgments

**Development Team:**
- Lead Developer: wisnuwidi@canvastack.com
- Theme Engine Team
- Documentation Team

**Special Thanks:**
- Bootstrap Team
- TailwindCSS Team
- Laravel Community
- All Contributors and Testers

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may this Theme Engine serve the community well.

**Built with ❤️ by CanvaStack**
