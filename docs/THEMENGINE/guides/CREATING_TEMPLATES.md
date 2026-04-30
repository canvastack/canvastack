# Creating Templates for CanvaStack Theme Engine

**Version:** 2.0.0
**Last Updated:** April 28, 2026

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

---

## Section 1: Introduction to Template Creation

### Overview of CanvaStack Theme Engine

The CanvaStack Theme Engine is an adapter-based system that lets you build admin panel templates using any CSS framework — Bootstrap 4, Bootstrap 5, TailwindCSS, or your own custom framework — without changing a single line of PHP business logic.

The engine sits between your Blade views and the PHP helper functions that generate HTML. When a helper like `canvastack_form_create_header_tab()` is called, the engine automatically routes the rendering to the correct adapter for the active template.

```
Your Blade View
      │
      ▼
canvastack_form_create_header_tab()   ← same call regardless of framework
      │
      ▼
ThemeAdapterResolver::resolve()       ← detects active template
      │
      ├── 'default'   → DefaultAdapter   → Bootstrap 4 HTML
      ├── 'canvasign' → Bootstrap5Adapter → Bootstrap 5 HTML
      └── 'canvas'    → TailwindAdapter  → Tailwind HTML
```

### Supported CSS Frameworks

| Template Name | Framework | Adapter Class | Status |
|---|---|---|---|
| `default` | Bootstrap 4.6.x | `DefaultAdapter` | ✅ Production |
| `canvasign` | Bootstrap 5.3.x | `Bootstrap5Adapter` | ✅ Production |
| `canvas` | TailwindCSS 3.x | `TailwindAdapter` | ✅ Production |
| Custom | Any framework | Your custom adapter | ✅ Supported |

### Benefits of Multi-Framework Support

- **No vendor lock-in** — switch frameworks without rewriting PHP logic
- **Gradual migration** — run multiple templates in parallel during migration
- **Team flexibility** — different teams can use their preferred framework
- **Future-proof** — add new framework support without breaking existing code
- **Zero breaking changes** — the `default` template always produces identical output

### When to Create a Custom Template

Create a new template when:
- You need a CSS framework not yet supported (Material Design, Bulma, UIKit)
- You have a proprietary design system with custom components
- You need a white-label version with different branding
- You are migrating from one framework to another and need both running simultaneously

---

## Section 2: Template Architecture Overview

### Common Directory Structure

Every template follows the same two-root structure regardless of CSS framework:

```
resources/views/{template_name}/
├── template/
│   └── admin/
│       ├── index.blade.php          ← Master layout
│       └── block/
│           ├── meta.blade.php       ← <head> section
│           ├── header.blade.php     ← Top navigation
│           ├── sidebar.blade.php    ← Left sidebar
│           ├── footer.blade.php     ← Footer bar
│           ├── offside.blade.php    ← Right panel
│           └── downscripts.blade.php ← Bottom JS
├── pages/
│   └── admin/                       ← Page-level views
│       ├── dashboard.blade.php
│       └── ...
└── emails/
    └── default.blade.php            ← Email layout

public/assets/templates/{template_name}/
├── css/
│   └── app.css
├── js/
│   ├── app.js
│   └── datatables/
│       └── filter.js
├── fonts/
└── images/
```

### Blade Component Hierarchy

All templates follow the same rendering pipeline:

```
index.blade.php (master layout)
├── block/meta.blade.php         → <head> CSS + JS
├── block/sidebar.blade.php      → left navigation
├── block/header.blade.php       → top bar + breadcrumbs
├── @yield('content')            → page-specific content
├── block/footer.blade.php       → footer bar
├── block/offside.blade.php      → right slide-out panel
└── block/downscripts.blade.php  → bottom JS
```

Page views extend the master layout via the pages/admin view:

```blade
@extends('{template_name}.pages.admin')

@section('content')
    {{-- Page content --}}
@endsection
```

### Asset Organization Patterns

| Asset Type | Location | Loaded By |
|---|---|---|
| Framework CSS | CDN (config) | `meta.blade.php` |
| Custom CSS | `public/assets/templates/{name}/css/` | `meta.blade.php` |
| Framework JS | CDN (config) | `meta.blade.php` (top) or `downscripts.blade.php` |
| Plugin JS | CDN (config) | `downscripts.blade.php` (bottom.first) |
| Custom JS | `public/assets/templates/{name}/js/` | `downscripts.blade.php` (bottom.last) |
| Fonts | `public/assets/templates/{name}/fonts/` | CSS `@font-face` |
| Images | `public/assets/templates/{name}/images/` | Blade `asset()` helper |

### Adapter Integration Patterns

Every template needs a corresponding adapter class. The adapter handles all framework-specific HTML generation:

```php
// Minimal adapter structure
class MyFrameworkAdapter implements ThemeAdapterInterface
{
    // Utility methods — return framework-specific strings
    public function getDataToggleAttribute(): string { return 'data-toggle'; }
    public function getDismissAttribute(): string    { return 'data-dismiss'; }
    public function getHideClass(): string           { return 'hidden'; }
    public function getFloatRightClass(): string     { return 'ml-auto'; }
    public function getSelectBoxClass(): string      { return 'form-select'; }
    public function getTableClass(): string          { return 'w-full text-sm'; }

    // Render methods — return complete HTML strings
    public function renderTabHeader(...): string     { /* ... */ }
    public function renderTabContent(...): string    { /* ... */ }
    public function renderAlertMessage(...): string  { /* ... */ }
    public function renderCheckList(...): string     { /* ... */ }
    public function renderSelectBox(...): string     { /* ... */ }
    public function renderModalWrapper(...): string  { /* ... */ }
    public function renderFilterModal(...): string   { /* ... */ }
    public function renderActionButtons(...): string { /* ... */ }
}
```

### Configuration Patterns

Every template needs a configuration block in `config/canvastack.templates.php`:

```php
'{template_name}' => [
    'position' => [
        'top'    => ['css' => [...], 'js' => [...]],
        'bottom' => [
            'first' => ['css' => [...], 'js' => [...]],
            'last'  => ['css' => [...], 'js' => [...]],
        ],
    ],
    'datatable' => ['js' => [...], 'css' => [...]],
    'select'    => ['plugin' => '...', 'js' => [...], 'css' => [...]],
    'date'      => ['js' => [...], 'css' => [...]],
    'datetime'  => ['js' => [...], 'css' => [...]],
    'daterange' => ['js' => [...], 'css' => [...]],
    'chart'     => ['js' => [...], 'css' => [...]],
],
```

---

## Section 3: Choosing Your CSS Framework

### Bootstrap 4 (Default Template)

**Template name:** `default`
**Adapter:** `DefaultAdapter`
**Guide:** [BOOTSTRAP4_TEMPLATE_GUIDE.md](./BOOTSTRAP4_TEMPLATE_GUIDE.md)

**Pros:**
- Mature and battle-tested (released 2018, widely adopted)
- Extensive plugin ecosystem (Chosen.js, DataTables BS4, etc.)
- Large community and Stack Overflow coverage
- 100% backward compatible with existing CanvaStack code
- jQuery-based — familiar to most PHP developers

**Cons:**
- Older syntax (`data-toggle` vs `data-bs-toggle`, `pull-right` vs `float-end`)
- Requires jQuery as a dependency
- Larger bundle size compared to Bootstrap 5
- Some deprecated classes (`alert-block`, `btn-xs`)

**Best for:**
- Existing projects already using Bootstrap 4
- Teams familiar with Bootstrap 4 and jQuery
- Projects requiring Chosen.js or other Bootstrap 4 plugins
- When zero breaking changes are the top priority

---

### Bootstrap 5 (Canvasign Template)

**Template name:** `canvasign`
**Adapter:** `Bootstrap5Adapter`
**Guide:** [BOOTSTRAP5_TEMPLATE_GUIDE.md](./BOOTSTRAP5_TEMPLATE_GUIDE.md)

**Pros:**
- Modern syntax (`data-bs-toggle`, `float-end`, `d-none`)
- No jQuery dependency (vanilla JavaScript)
- Improved accessibility (better ARIA support)
- Smaller bundle size than Bootstrap 4
- Active development and long-term support
- Better RTL support

**Cons:**
- Breaking changes from Bootstrap 4 (requires code updates)
- Some Bootstrap 4 plugins not compatible (Chosen.js → Choices.js)
- Slightly steeper learning curve if coming from Bootstrap 4

**Best for:**
- New projects starting fresh
- Teams wanting modern web standards
- Projects where accessibility is a priority
- When jQuery dependency is undesirable

---

### TailwindCSS (Canvas Template)

**Template name:** `canvas`
**Adapter:** `TailwindAdapter`
**Guide:** [TAILWIND_TEMPLATE_GUIDE.md](./TAILWIND_TEMPLATE_GUIDE.md)

**Pros:**
- Utility-first — no pre-built components to override
- Highly customizable without writing custom CSS
- Excellent performance (PurgeCSS removes unused classes)
- No JavaScript framework dependency
- Consistent design system via `tailwind.config.js`
- Growing ecosystem (Headless UI, Flowbite, etc.)

**Cons:**
- Learning curve for developers used to component-based CSS
- HTML markup becomes verbose with many utility classes
- Requires build process for production (PurgeCSS)
- Fewer pre-built admin UI components than Bootstrap

**Best for:**
- Custom designs that don't fit Bootstrap's component model
- Performance-critical applications
- Teams with strong CSS knowledge
- Projects where design system consistency is critical

---

## Section 4: Framework Comparison Table

### Core Differences

| Feature | Bootstrap 4 (`default`) | Bootstrap 5 (`canvasign`) | TailwindCSS (`canvas`) |
|---|---|---|---|
| **Toggle attribute** | `data-toggle` | `data-bs-toggle` | `data-toggle` (custom JS) |
| **Dismiss attribute** | `data-dismiss` | `data-bs-dismiss` | `data-dismiss` (custom JS) |
| **Hide class** | `hide` | `d-none` | `hidden` |
| **Float right** | `pull-right` | `float-end` | `ml-auto` |
| **Float left** | `pull-left` | `float-start` | `mr-auto` |
| **Select class** | `chosen-select-deselect` | `form-select` | `form-input` |
| **Button XS** | `btn-xs` | `btn-sm` | Custom utility |
| **Alert block** | `alert-block` | *(removed)* | *(not used)* |
| **Checkbox wrapper** | `ckbox ckbox-{color}` | `form-check` | `flex items-center gap-2` |
| **jQuery required** | Yes | Optional | No |

### Grid System

| Feature | Bootstrap 4 | Bootstrap 5 | TailwindCSS |
|---|---|---|---|
| **Container** | `container` | `container` | `container mx-auto` |
| **Row** | `row` | `row` | `flex flex-wrap` |
| **Column** | `col-md-6` | `col-md-6` | `w-1/2` |
| **Breakpoints** | xs, sm, md, lg, xl | xs, sm, md, lg, xl, xxl | sm, md, lg, xl, 2xl |
| **Gutter control** | Limited | `g-*` classes | `gap-*` classes |

### Form Components

| Component | Bootstrap 4 | Bootstrap 5 | TailwindCSS |
|---|---|---|---|
| **Text input** | `form-control` | `form-control` | `form-input` |
| **Select** | `custom-select` + Chosen.js | `form-select` + Choices.js | `form-input` (native) |
| **Checkbox** | `custom-control custom-checkbox` | `form-check` | `flex items-center` |
| **Radio** | `custom-control custom-radio` | `form-check` | `flex items-center` |
| **Switch** | `custom-control custom-switch` | `form-check form-switch` | Custom component |
| **File input** | `custom-file` | `form-control` | Custom component |

### Modal Components

| Feature | Bootstrap 4 | Bootstrap 5 | TailwindCSS |
|---|---|---|---|
| **Open trigger** | `data-toggle="modal"` | `data-bs-toggle="modal"` | Custom JS |
| **Close trigger** | `data-dismiss="modal"` | `data-bs-dismiss="modal"` | Custom JS |
| **JS API** | `$('#id').modal('show')` | `bootstrap.Modal.getInstance('#id').show()` | `CanvaStackModal.show('id')` |
| **Backdrop** | Automatic | Automatic | Custom CSS |
| **Animation** | `fade` class | `fade` class | Custom CSS |

### Plugin Ecosystem

| Plugin Type | Bootstrap 4 | Bootstrap 5 | TailwindCSS |
|---|---|---|---|
| **Select enhancement** | Chosen.js | Choices.js | Tom Select / native |
| **Date picker** | Flatpickr / Datepicker | Flatpickr | Flatpickr |
| **DataTables** | `dataTables.bootstrap4.js` | `dataTables.bootstrap5.js` | `jquery.dataTables.js` |
| **Tooltips** | Bootstrap 4 tooltip | Bootstrap 5 tooltip | Tippy.js / native |
| **Charts** | ApexCharts / ECharts | ApexCharts / ECharts | ApexCharts / ECharts |
| **Icons** | Font Awesome 4/5 | Font Awesome 5/6 | Heroicons / Font Awesome |

### Bundle Size (approximate, CDN)

| Asset | Bootstrap 4 | Bootstrap 5 | TailwindCSS |
|---|---|---|---|
| **CSS (minified)** | ~160 KB | ~140 KB | ~3.8 MB (dev CDN) / ~10 KB (purged) |
| **JS (minified)** | ~60 KB + jQuery ~90 KB | ~60 KB (no jQuery) | ~0 KB (no framework JS) |
| **Total (approx)** | ~310 KB | ~200 KB | ~10–20 KB (production) |

### Browser Support

| Browser | Bootstrap 4 | Bootstrap 5 | TailwindCSS |
|---|---|---|---|
| Chrome | ✅ Latest | ✅ Latest | ✅ Latest |
| Firefox | ✅ Latest | ✅ Latest | ✅ Latest |
| Safari | ✅ Latest | ✅ Latest | ✅ Latest |
| Edge | ✅ Latest | ✅ Latest | ✅ Latest |
| IE 11 | ✅ Supported | ❌ Not supported | ❌ Not supported |
| IE 10 | ⚠️ Partial | ❌ Not supported | ❌ Not supported |

> If IE 11 support is required, use Bootstrap 4 (`default` template).

---

## Section 5: Planning Your Template

### Requirements Gathering Checklist

Before writing any code, answer these questions:

**Framework selection:**
- [ ] Which CSS framework will this template use?
- [ ] Does the framework require jQuery?
- [ ] What is the minimum browser support requirement?
- [ ] Are there existing plugins that must be compatible?

**Design system:**
- [ ] Is there a design file (Figma, Sketch, XD)?
- [ ] What color palette will be used?
- [ ] What typography (fonts, sizes, weights)?
- [ ] What spacing scale (padding, margin)?
- [ ] Are there custom components beyond the framework defaults?

**Performance requirements:**
- [ ] What is the target page load time?
- [ ] Should assets be served from CDN or locally?
- [ ] Is a build process (Vite, Webpack) available?
- [ ] Should CSS be purged of unused classes (critical for Tailwind)?

**Accessibility requirements:**
- [ ] What WCAG level is required (A, AA, AAA)?
- [ ] Must the template support screen readers?
- [ ] Is keyboard-only navigation required?
- [ ] Are there color contrast requirements?

**Plugin requirements:**
- [ ] DataTables — which version and which framework integration?
- [ ] Select enhancement — Chosen.js, Choices.js, Tom Select, or native?
- [ ] Date picker — Flatpickr, Pikaday, or native?
- [ ] Charts — ApexCharts, ECharts, Chart.js?
- [ ] Icons — Font Awesome, Heroicons, or custom?

---

## Section 6: Implementation Workflow

### Step 1: Choose Framework and Create Directory Structure

```bash
# Set your template name (lowercase, no spaces)
TEMPLATE_NAME="mytheme"

# Create Blade view directories
mkdir -p resources/views/${TEMPLATE_NAME}/template/admin/block
mkdir -p resources/views/${TEMPLATE_NAME}/pages/admin
mkdir -p resources/views/${TEMPLATE_NAME}/emails

# Create public asset directories
mkdir -p public/assets/templates/${TEMPLATE_NAME}/css
mkdir -p public/assets/templates/${TEMPLATE_NAME}/js/datatables
mkdir -p public/assets/templates/${TEMPLATE_NAME}/fonts
mkdir -p public/assets/templates/${TEMPLATE_NAME}/images
```

### Step 2: Extract and Organize Assets

Copy your design's static assets into the public directory:

```bash
# Copy CSS, JS, fonts, images from your design
cp design/css/app.css    public/assets/templates/${TEMPLATE_NAME}/css/
cp design/js/app.js      public/assets/templates/${TEMPLATE_NAME}/js/
cp -r design/fonts/      public/assets/templates/${TEMPLATE_NAME}/fonts/
cp -r design/images/     public/assets/templates/${TEMPLATE_NAME}/images/
```

Framework libraries (Bootstrap, Tailwind CDN) are **not** copied — they go in the config file.

### Step 3: Implement Blade Components

Create all six block files and the master layout. See the framework-specific guides for complete examples:
- [Bootstrap 4 examples](./BOOTSTRAP4_TEMPLATE_GUIDE.md#section-3-step-by-step-implementation-guide)
- [Bootstrap 5 examples](./BOOTSTRAP5_TEMPLATE_GUIDE.md)
- [TailwindCSS examples](./TAILWIND_TEMPLATE_GUIDE.md)

### Step 4: Configure Asset Loading

Open `config/canvastack.templates.php` and add your template's configuration block.

#### Understanding the Configuration Structure

```php
'{template_name}' => [
    'position' => [
        // top: loaded in <head>
        'top' => [
            'css' => [ /* CSS files */ ],
            'js'  => [ /* JS files */ ],
        ],
        'bottom' => [
            // bottom.first: loaded before app scripts
            'first' => [
                'css' => [ /* CSS files */ ],
                'js'  => [ /* JS files */ ],
            ],
            // bottom.last: loaded after all other scripts
            'last' => [
                'css' => [ /* CSS files */ ],
                'js'  => [ /* JS files */ ],
            ],
        ],
    ],
    // Plugin-specific (loaded on-demand, not every page)
    'datatable' => ['js' => [...], 'css' => [...]],
    'select'    => ['plugin' => '...', 'js' => [...], 'css' => [...]],
    'date'      => ['js' => [...], 'css' => [...]],
    'datetime'  => ['js' => [...], 'css' => [...]],
    'daterange' => ['js' => [...], 'css' => [...]],
    'chart'     => ['js' => [...], 'css' => [...]],
],
```

#### Configuration Examples by Framework

**Bootstrap 4 configuration:**
```php
'default' => [
    'position' => [
        'top' => [
            'css' => [
                'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css',
                'https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css',
                'assets/templates/default/css/app.css',
            ],
            'js' => [
                'https://code.jquery.com/jquery-3.6.0.min.js',
                'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js',
            ],
        ],
        'bottom' => [
            'first' => [
                'css' => [null],
                'js'  => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js'],
            ],
            'last' => [
                'css' => [null],
                'js'  => [
                    'assets/templates/default/js/canvastack-modal-adapter.js',
                    'assets/templates/default/js/scripts.js',
                ],
            ],
        ],
    ],
    'datatable' => [
        'js'  => ['https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js'],
        'css' => ['https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css'],
    ],
    'select' => [
        'plugin' => 'chosen',
        'js'  => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js'],
        'css' => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css'],
    ],
],
```

**Bootstrap 5 configuration:**
```php
'canvasign' => [
    'position' => [
        'top' => [
            'css' => [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
                'https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css',
                'assets/templates/canvasign/css/app.css',
            ],
            'js' => [
                // Bootstrap 5 does NOT require jQuery, but CanvaStack uses it for DataTables
                'https://code.jquery.com/jquery-3.6.0.min.js',
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            ],
        ],
        'bottom' => [
            'first' => [
                'css' => [null],
                'js'  => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js'],
            ],
            'last' => [
                'css' => [null],
                'js'  => [
                    'assets/templates/canvasign/js/canvastack-modal-adapter.js',
                    'assets/templates/canvasign/js/canvastack-tooltip-adapter.js',
                    'assets/templates/canvasign/js/scripts.js',
                ],
            ],
        ],
    ],
    'datatable' => [
        'js'  => ['https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js'],
        'css' => ['https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css'],
    ],
    'select' => [
        'plugin' => 'choices',
        'js'  => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js'],
        'css' => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css'],
    ],
],
```

**TailwindCSS configuration:**
```php
'canvas' => [
    'position' => [
        'top' => [
            'css' => [null],
            // Tailwind Play CDN for development — use compiled CSS in production
            'js'  => ['https://cdn.tailwindcss.com'],
        ],
        'bottom' => [
            'first' => [
                'css' => ['assets/templates/canvas/css/app.css'],
                'js'  => [null],
            ],
            'last' => [
                'css' => [null],
                'js'  => [
                    'assets/templates/canvas/js/canvastack-modal-adapter.js',
                    'assets/templates/canvas/js/canvastack-tooltip-adapter.js',
                    'assets/templates/canvas/js/canvastack-class-adapter.js',
                    'assets/templates/canvas/js/scripts.js',
                ],
            ],
        ],
    ],
    'datatable' => [
        'js'  => ['https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js'],
        'css' => ['https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css'],
    ],
    'select' => [
        'plugin' => 'native',
        'js'  => [null],
        'css' => [null],
    ],
],
```

### Step 5: Integrate with Appropriate Adapter

Register your adapter in `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;

public function boot(): void
{
    // For built-in templates, no registration needed.
    // For custom templates:
    ThemeAdapterResolver::register('mytheme', \App\Theme\MyThemeAdapter::class);
}
```

Set the active template:

```php
// config/canvastack.templates.php
'template' => 'mytheme',
```

### Step 6: Implement Plugin Integrations

Each plugin needs initialization in your `scripts.js` or `app.js`:

```javascript
// Bootstrap 4 — Chosen.js
$('.chosen-select-deselect').chosen({ allow_single_deselect: true, width: '100%' });

// Bootstrap 5 — Choices.js
document.querySelectorAll('select.form-select').forEach(el => {
    new Choices(el, { removeItemButton: true });
});

// TailwindCSS — Tom Select
document.querySelectorAll('select.form-input').forEach(el => {
    new TomSelect(el, { create: false });
});

// Flatpickr (all frameworks)
flatpickr('.date-picker', { dateFormat: 'Y-m-d' });
flatpickr('.datetime-picker', { enableTime: true, dateFormat: 'Y-m-d H:i' });
```

### Step 7: Test Across Browsers and Devices

```bash
# Clear all caches before testing
php artisan config:clear
php artisan view:clear
php artisan cache:clear

# Verify template is active
php artisan tinker
>>> canvastack_current_template()
=> "mytheme"
```

Test checklist:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Chrome (375px)
- [ ] Mobile Safari (375px)
- [ ] Tablet (768px)

### Step 8: Document and Deploy

Create a brief README for your template:

```markdown
# MyTheme Template

**Framework:** [Framework Name]
**Adapter:** `App\Theme\MyThemeAdapter`
**Template name:** `mytheme`

## Activation
Set `'template' => 'mytheme'` in `config/canvastack.templates.php`.

## Assets
- CSS: `public/assets/templates/mytheme/css/`
- JS: `public/assets/templates/mytheme/js/`

## Plugins
- Select: [Plugin name]
- Date: Flatpickr
- Charts: ApexCharts
```

---

## Section 7: Template Configuration Deep Dive

### Understanding config/canvastack.templates.php Structure

The configuration file uses a nested key structure:

```
config/canvastack.templates.php
└── {template_name}
    └── position
        ├── top
        │   ├── css[]    → <link> tags in <head>
        │   └── js[]     → <script> tags in <head>
        └── bottom
            ├── first
            │   ├── css[]  → <link> tags before </body>
            │   └── js[]   → <script> tags before app scripts
            └── last
                ├── css[]  → <link> tags last before </body>
                └── js[]   → <script> tags last before </body>
```

Plugin configurations are separate keys at the same level as `position`:

```
└── {template_name}
    ├── position    → always-loaded assets
    ├── datatable   → loaded only on DataTables pages
    ├── select      → loaded only on pages with select enhancement
    ├── date        → loaded only on pages with date pickers
    ├── datetime    → loaded only on pages with datetime pickers
    ├── daterange   → loaded only on pages with date range pickers
    └── chart       → loaded only on pages with charts
```

### Asset Path Resolution

**CDN URLs** — detected by `https://` prefix:
```php
'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
// → <link rel="stylesheet" href="https://cdn.jsdelivr.net/...">
```

**Local assets** — resolved via `asset()` helper:
```php
'assets/templates/default/css/app.css'
// → <link rel="stylesheet" href="https://yourapp.com/assets/templates/default/css/app.css">
```

**Null** — skip this position:
```php
[null]
// → no output
```

### Asset Loading Order and Performance

```
Browser request
      │
      ▼
<head>
  ├── top.css[0..n]     ← Framework CSS (render-blocking — keep minimal)
  └── top.js[0..n]      ← Critical JS only (jQuery if needed in <head>)

<body>
  ... page content renders ...

  ├── bottom.first.js[0..n]  ← Plugin libraries (DataTables, Chosen.js)
  ├── bottom.js[0..n]        ← Core app scripts
  └── bottom.last.js[0..n]   ← Final scripts (adapters, custom init)
</body>
```

**Performance tips:**
- Keep `top.js` minimal — only scripts that must be in `<head>`
- Put jQuery and Bootstrap JS in `top.js` only if required by inline scripts
- Otherwise, move jQuery and Bootstrap to `bottom.first.js` for faster page render
- Use CDN for popular libraries (browser may already have them cached)
- Use `[null]` for positions with no assets to avoid empty iterations

### Plugin Configuration Patterns

**DataTables** — framework-specific styling integration:
```php
// Bootstrap 4
'datatable' => [
    'js'  => ['https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js'],
    'css' => ['https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css'],
],

// Bootstrap 5
'datatable' => [
    'js'  => ['https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js'],
    'css' => ['https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css'],
],

// TailwindCSS (no framework integration — use base DataTables)
'datatable' => [
    'js'  => ['https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js'],
    'css' => ['https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css'],
],
```

**Select enhancement** — different plugins per framework:
```php
// Bootstrap 4 — Chosen.js
'select' => ['plugin' => 'chosen', 'js' => [...chosen.js...], 'css' => [...chosen.css...]],

// Bootstrap 5 — Choices.js
'select' => ['plugin' => 'choices', 'js' => [...choices.js...], 'css' => [...choices.css...]],

// TailwindCSS — native or Tom Select
'select' => ['plugin' => 'native', 'js' => [null], 'css' => [null]],
```

### Best Practices for Asset Management

**1. Always version-lock CDN URLs:**
```php
// Good — version locked, predictable
'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'

// Bad — may break on major version release
'https://cdn.jsdelivr.net/npm/bootstrap@latest/dist/css/bootstrap.min.css'
```

**2. CDN vs local decision matrix:**

| Scenario | Use CDN | Use Local |
|---|---|---|
| Popular framework (Bootstrap, Tailwind) | ✅ | ✅ |
| Production with strict CSP | ❌ | ✅ |
| Offline/intranet deployment | ❌ | ✅ |
| Custom/modified library | ❌ | ✅ |
| Development speed | ✅ | ❌ |
| Privacy-sensitive environment | ❌ | ✅ |

**3. Use environment variables for CDN vs local switching:**
```php
'top' => [
    'css' => [
        app()->environment('production')
            ? 'assets/templates/default/css/bootstrap.min.css'  // local in prod
            : 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css', // CDN in dev
    ],
],
```

**4. Security — Subresource Integrity for CDN assets:**
```php
// The Template component supports SRI via the asset config
// Add integrity hash to prevent CDN tampering
'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
// → Add integrity="sha384-..." crossorigin="anonymous" in your meta.blade.php
```

---

## Section 8: Common Patterns and Best Practices

### Naming Conventions

**Template names:** lowercase, no spaces, no special characters
```
default     ✓
canvasign   ✓
canvas      ✓
my-theme    ✓
MyTheme     ✗
my_theme    ✗ (underscores work but hyphens preferred)
```

**Blade file names:** lowercase with hyphens
```
dashboard.blade.php         ✓
user-profile.blade.php      ✓
UserProfile.blade.php       ✗
```

**Asset file names:** lowercase with hyphens
```
app.css                     ✓
canvastack-modal-adapter.js ✓
AppStyles.css               ✗
```

### Asset Organization Strategies

**Single file approach** (simpler, fewer HTTP requests):
```
css/app.css     ← all custom styles in one file
js/app.js       ← all custom scripts in one file
```

**Split file approach** (better for large templates):
```
css/
├── base.css        ← resets, typography, variables
├── layout.css      ← sidebar, header, footer
├── components.css  ← cards, tables, forms
└── utilities.css   ← helpers, overrides

js/
├── sidebar.js      ← sidebar toggle logic
├── scripts.js      ← general UI interactions
└── datatables/
    └── filter.js   ← DataTables filter modal
```

### Performance Optimization Techniques

1. **Defer non-critical JavaScript:**
   Put all JS in `bottom.last.js` position unless it must be in `<head>`.

2. **Inline critical CSS** for above-the-fold content (advanced):
   ```blade
   {{-- In meta.blade.php --}}
   <style>
       /* Critical CSS for initial render */
       body { margin: 0; font-family: sans-serif; }
       .sidebar-menu { width: 250px; }
   </style>
   ```

3. **Use `font-display: swap`** to prevent invisible text during font load:
   ```css
   @font-face {
       font-family: 'CustomFont';
       src: url('../fonts/CustomFont.woff2') format('woff2');
       font-display: swap;
   }
   ```

4. **Lazy-load images** in sidebar and header:
   ```html
   <img src="{{ $logo }}" alt="{{ $appName }}" loading="lazy">
   ```

### Security Considerations

1. **Never output unescaped user input:**
   ```blade
   {{ $userInput }}      ← safe (escaped)
   {!! $userInput !!}    ← dangerous (only for trusted HTML)
   {!! $helperOutput !!} ← safe (CanvaStack helpers escape internally)
   ```

2. **CSRF protection on all state-changing forms:**
   ```blade
   <form method="POST" action="{{ route('users.store') }}">
       @csrf
       ...
   </form>
   ```

3. **Content Security Policy** — if using CDN assets, add CDN domains to CSP:
   ```
   Content-Security-Policy: script-src 'self' https://cdn.jsdelivr.net https://code.jquery.com;
   ```

### Accessibility Best Practices

1. **Semantic HTML landmarks:**
   ```html
   <nav role="navigation" aria-label="Main navigation">
   <main role="main" id="main-content">
   <footer role="contentinfo">
   ```

2. **Skip navigation link** (required for keyboard users):
   ```html
   <a href="#main-content" class="sr-only sr-only-focusable">
       Skip to main content
   </a>
   ```

3. **ARIA labels on icon-only buttons:**
   ```html
   <button aria-label="Close sidebar">
       <i class="ti-close" aria-hidden="true"></i>
   </button>
   ```

4. **Focus visible styles** — ensure keyboard focus is always visible:
   ```css
   :focus-visible {
       outline: 2px solid #0066cc;
       outline-offset: 2px;
   }
   ```

---

## Section 9: Testing Strategy

### Unit Testing for Blade Components

Test that Blade components render without errors:

```php
// tests/Feature/TemplateRenderTest.php
public function test_dashboard_renders_with_default_template(): void
{
    config(['canvastack.templates.template' => 'default']);

    $response = $this->actingAs($this->user)->get('/admin/dashboard');

    $response->assertStatus(200);
    $response->assertSee('data-toggle="tab"');      // Bootstrap 4 attribute
    $response->assertDontSee('data-bs-toggle');     // Not Bootstrap 5
}

public function test_dashboard_renders_with_canvasign_template(): void
{
    config(['canvastack.templates.template' => 'canvasign']);

    $response = $this->actingAs($this->user)->get('/admin/dashboard');

    $response->assertStatus(200);
    $response->assertSee('data-bs-toggle');         // Bootstrap 5 attribute
    $response->assertDontSee('data-toggle="tab"');  // Not Bootstrap 4
}
```

### Integration Testing for Adapters

Test that adapters produce correct output:

```php
// tests/Unit/AdapterIntegrationTest.php
public function test_default_adapter_produces_bootstrap4_html(): void
{
    ThemeAdapterResolver::reset();
    // Mock canvastack_current_template() to return 'default'

    $output = canvastack_form_create_header_tab('Users', 'users-tab', true, false);

    $this->assertStringContainsString('data-toggle="tab"', $output);
    $this->assertStringNotContainsString('data-bs-toggle', $output);
}
```

### Browser Testing Across Frameworks

Use Laravel Dusk for browser testing:

```php
// tests/Browser/TemplateTest.php
public function test_modal_opens_with_default_template(): void
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
                ->visit('/admin/users')
                ->click('[data-toggle="modal"][data-target="#filterModal"]')
                ->waitFor('#filterModal.show')
                ->assertVisible('#filterModal');
    });
}
```

### Responsive Testing

Test at key breakpoints using browser DevTools or automated tools:

```bash
# Using Playwright (example)
npx playwright test --project=mobile-chrome
npx playwright test --project=tablet
npx playwright test --project=desktop
```

### Performance Testing

```bash
# Lighthouse CLI
npx lighthouse https://yourapp.com/admin/dashboard \
    --output=json \
    --output-path=./lighthouse-report.json

# Check scores
# Performance: > 80
# Accessibility: > 90
# Best Practices: > 90
```

---

## Section 10: Deployment and Maintenance

### Template Activation and Switching

```php
// config/canvastack.templates.php
'template' => env('CANVASTACK_TEMPLATE', 'default'),

// .env
CANVASTACK_TEMPLATE=canvasign
```

After changing the template:
```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

### Version Control Strategies

**Track template assets in git:**
```gitignore
# .gitignore — do NOT ignore template assets
# public/assets/templates/ should be committed

# Ignore compiled/generated files only
public/assets/templates/*/css/compiled/
public/assets/templates/*/js/compiled/
```

**Tag releases when changing templates:**
```bash
git tag -a v2.0.0-canvasign -m "Switch to Bootstrap 5 (canvasign template)"
git push origin v2.0.0-canvasign
```

### Update and Maintenance Procedures

When updating framework versions:

1. Test the new version in a branch first
2. Update CDN URLs in `config/canvastack.templates.php`
3. Check for breaking changes in the framework changelog
4. Run the full test suite
5. Test manually in all supported browsers
6. Deploy to staging before production

```php
// Before
'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'

// After update
'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
```

---

## Section 11: Advanced Topics

### Creating Custom Adapters for New Frameworks

To add support for a new CSS framework (e.g., Material Design):

```php
// app/Theme/MaterialAdapter.php
namespace App\Theme;

use Canvastack\Canvastack\Library\Theme\ThemeAdapterInterface;

class MaterialAdapter implements ThemeAdapterInterface
{
    public function getDataToggleAttribute(): string { return 'data-toggle'; }
    public function getDismissAttribute(): string    { return 'data-dismiss'; }
    public function getHideClass(): string           { return 'hidden'; }
    public function getFloatRightClass(): string     { return 'ml-auto'; }
    public function getSelectBoxClass(): string      { return 'mdc-select'; }
    public function getTableClass(): string          { return 'mdc-data-table__table'; }

    public function renderTabHeader(
        string $data, string $pointer,
        string|false $active, string|false $class
    ): string {
        $activeClass  = $active ? ' mdc-tab--active' : '';
        $customClass  = $class  ? ' ' . htmlspecialchars($class) : '';
        $ariaSelected = $active ? 'true' : 'false';

        return sprintf(
            '<button class="mdc-tab%s%s" role="tab" aria-selected="%s" data-tab="%s">
                <span class="mdc-tab__content">
                    <span class="mdc-tab__text-label">%s</span>
                </span>
                <span class="mdc-tab-indicator%s">
                    <span class="mdc-tab-indicator__content mdc-tab-indicator__content--underline"></span>
                </span>
            </button>',
            $activeClass, $customClass, $ariaSelected,
            htmlspecialchars($pointer),
            htmlspecialchars($data),
            $active ? ' mdc-tab-indicator--active' : ''
        );
    }

    // Implement all remaining 13 methods...
    public function renderTabContent(string $data, string $pointer, bool $active): string { /* ... */ }
    public function renderAlertMessage(string|array $message, string $type, string $title, string $prefix, string|false $extra): string { /* ... */ }
    public function renderCheckList(mixed $name, string|false $value, string|false $label, bool $checked, string $class, string|false $id, ?string $inputNode): string { /* ... */ }
    public function renderSelectBox(string $name, array $values, mixed $selected, array $attributes, bool $label, array|bool $set_first_value): string { /* ... */ }
    public function renderModalWrapper(string $name, string $title, array $elements): string { /* ... */ }
    public function renderFilterModal(string $name, string $title, array $elements): string { /* ... */ }
    public function renderActionButtons(object $rowData, string $fieldTarget, string $currentUrl, mixed $action, ?array $removedButtons): string { /* ... */ }
}
```

Register and configure:

```php
// AppServiceProvider::boot()
ThemeAdapterResolver::register('material', MaterialAdapter::class);

// config/canvastack.templates.php
'template' => 'material',
'material' => [
    'position' => [
        'top' => [
            'css' => ['https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css'],
            'js'  => ['https://unpkg.com/material-components-web@latest/dist/material-components-web.min.js'],
        ],
        // ...
    ],
],
```

### Extending Existing Adapters

Extend an existing adapter to override specific methods:

```php
// app/Theme/CustomBootstrap5Adapter.php
namespace App\Theme;

use Canvastack\Canvastack\Library\Theme\Adapters\Bootstrap5Adapter;

class CustomBootstrap5Adapter extends Bootstrap5Adapter
{
    /**
     * Override to use a custom alert style.
     */
    public function renderAlertMessage(
        string|array $message, string $type,
        string $title, string $prefix, string|false $extra
    ): string {
        // Custom implementation
        return '<div class="custom-alert custom-alert-' . htmlspecialchars($type) . '">'
             . htmlspecialchars(is_array($message) ? implode(' ', $message) : $message)
             . '</div>';
    }
}

// Register as override for canvasign template
ThemeAdapterResolver::register('canvasign', CustomBootstrap5Adapter::class);
```

---

## Section 12: Troubleshooting

### Template Not Switching

**Symptom:** Changed `'template'` in config but still seeing old template.

```bash
# Clear all caches
php artisan config:clear
php artisan view:clear
php artisan cache:clear

# Verify the change took effect
php artisan tinker
>>> config('canvastack.templates.template')
>>> canvastack_current_template()
```

### Assets Not Loading for New Template

**Symptom:** CSS/JS 404 errors after adding new template config.

```bash
# Check config is correct
php artisan tinker
>>> config('canvastack.templates.canvasign.position.top.css')

# Check file exists (for local assets)
ls public/assets/templates/canvasign/css/app.css

# Check CDN URL is accessible
curl -I https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css
```

### Custom Adapter Not Being Used

**Symptom:** Registered custom adapter but system still uses DefaultAdapter.

```php
// Verify registration happened before resolve()
// AppServiceProvider::boot() must run before any request handling

// Check registration
php artisan tinker
>>> ThemeAdapterResolver::resolve()
// Should show your custom adapter class

// Verify template name matches exactly
>>> config('canvastack.templates.template')
// Must match the name used in ThemeAdapterResolver::register()
```

### Blade View Not Found

**Symptom:** `View [canvasign.pages.admin] not found` error.

```bash
# Check view directory exists
ls resources/views/canvasign/

# Check view file exists
ls resources/views/canvasign/template/admin/index.blade.php

# The system falls back to default if view not found
# Check View.php fallback logic is working
```

### Performance Issues

**Symptom:** Page loads slowly after switching templates.

1. Check Network tab — identify slow-loading assets
2. Move large JS files from `top.js` to `bottom.last.js`
3. Use CDN for popular libraries (better caching)
4. Enable Laravel response caching for static pages
5. For TailwindCSS: ensure PurgeCSS is configured in production

---

## Section 13: Resources and Community

### Framework-Specific Guides

- **Bootstrap 4 (default):** [BOOTSTRAP4_TEMPLATE_GUIDE.md](./BOOTSTRAP4_TEMPLATE_GUIDE.md)
- **Bootstrap 5 (canvasign):** [BOOTSTRAP5_TEMPLATE_GUIDE.md](./BOOTSTRAP5_TEMPLATE_GUIDE.md)
- **TailwindCSS (canvas):** [TAILWIND_TEMPLATE_GUIDE.md](./TAILWIND_TEMPLATE_GUIDE.md)

### CanvaStack Theme Engine Documentation

- **Overview:** `vendor/canvastack/canvastack/docs/THEMENGINE/README.md`
- **API Reference:** `vendor/canvastack/canvastack/docs/THEMENGINE/API_REFERENCE.md`
- **Architecture:** `vendor/canvastack/canvastack/docs/THEMENGINE/ARCHITECTURE.md`
- **Configuration:** `vendor/canvastack/canvastack/docs/THEMENGINE/TEMPLATE_CONFIGURATION.md`
- **Migration Guide:** `vendor/canvastack/canvastack/docs/THEMENGINE/MIGRATION_GUIDE.md`
- **Troubleshooting:** `vendor/canvastack/canvastack/docs/THEMENGINE/TROUBLESHOOTING.md`

### Framework Documentation

- **Bootstrap 4:** https://getbootstrap.com/docs/4.6/
- **Bootstrap 5:** https://getbootstrap.com/docs/5.3/
- **TailwindCSS:** https://tailwindcss.com/docs
- **Laravel Blade:** https://laravel.com/docs/blade

### Getting Help

1. Check the framework-specific guide for your template
2. Review the [Troubleshooting Guide](../TROUBLESHOOTING.md)
3. Check application logs: `storage/logs/laravel.log`
4. Search GitHub Issues for known problems
5. Contact: support@canvastack.com

---

**Last Updated:** April 28, 2026
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may this guide help developers create excellent templates for all CSS frameworks.

**Built with ❤️ by CanvaStack**
