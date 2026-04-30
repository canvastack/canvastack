# Template Configuration Guide

**Version:** 2.0.0  
**Last Updated:** April 4, 2026

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

## Overview

This guide covers the complete configuration of templates and assets in the CanvaStack Theme Engine. Learn how to configure existing templates, add custom templates, and manage asset loading.

---

## Configuration File

**Location:** `config/canvastack.templates.php`

**Purpose:** Centralized configuration for all templates, including active template selection and asset loading.

---

## Basic Configuration

### Setting Active Template

```php
// config/canvastack.templates.php
return [
    // Active template (default, canvasign, or canvas)
    'template' => 'default',
    
    // Template-specific configurations below...
];
```

**Options:**
- `'default'` - Bootstrap 4 (existing behavior)
- `'canvasign'` - Bootstrap 5
- `'canvas'` - TailwindCSS
- Custom template name (if registered)

---

## Asset Configuration Structure

### Position-Based Loading

Assets are loaded in specific positions:

```php
'template_name' => [
    'position' => [
        'top' => [
            'css' => [...],  // Loaded in <head>
            'js' => [...],   // Loaded in <head>
        ],
        'bottom' => [
            'first' => [
                'css' => [...],  // Loaded before </body>
                'js' => [...],   // Loaded before </body>
            ],
            'last' => [
                'css' => [...],  // Loaded last before </body>
                'js' => [...],   // Loaded last before </body>
            ],
        ],
    ],
],
```

**Loading Order:**
1. `top.css` - Framework CSS (Bootstrap, Tailwind)
2. `top.js` - Framework JS (Bootstrap, jQuery)
3. Page content
4. `bottom.first.css` - Custom CSS
5. `bottom.first.js` - Initialization scripts
6. `bottom.last.css` - Override CSS
7. `bottom.last.js` - Final scripts

---

## Default Template Configuration

### Bootstrap 4 (default)

```php
'default' => [
    'position' => [
        'top' => [
            'css' => [
                'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css',
                'https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css',
            ],
            'js' => [
                'https://code.jquery.com/jquery-3.6.0.min.js',
                'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js',
                'https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js',
            ],
        ],
        'bottom' => [
            'first' => [
                'css' => ['css/custom.css'],
                'js' => ['js/init.js'],
            ],
            'last' => [
                'css' => [null],
                'js' => ['js/scripts.js'],
            ],
        ],
    ],
    
    // DataTables configuration
    'datatable' => [
        'js' => [
            'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
            'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js',
        ],
        'css' => [
            'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css',
        ],
    ],
    
    // Select plugin configuration
    'select' => [
        'plugin' => 'chosen',
        'js' => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js'],
        'css' => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css'],
    ],
],
```

---

## Bootstrap 5 Template Configuration

### canvasign Template

```php
'canvasign' => [
    'position' => [
        'top' => [
            'css' => [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
                'https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css',
            ],
            'js' => [
                'https://code.jquery.com/jquery-3.6.0.min.js',
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
                'https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js',
            ],
        ],
        'bottom' => [
            'first' => [
                'css' => ['css/canvasign-custom.css'],
                'js' => ['js/canvasign-init.js'],
            ],
            'last' => [
                'css' => [null],
                'js' => [
                    'js/canvastack-modal-adapter.js',
                    'js/canvastack-tooltip-adapter.js',
                    'js/canvasign-scripts.js',
                ],
            ],
        ],
    ],
    
    // DataTables configuration
    'datatable' => [
        'js' => [
            'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
            'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js',
        ],
        'css' => [
            'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css',
        ],
    ],
    
    // Select plugin configuration
    'select' => [
        'plugin' => 'choices',
        'js' => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js'],
        'css' => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css'],
    ],
],
```

**Key Differences from default:**
- Bootstrap 5 CSS/JS instead of Bootstrap 4
- Choices.js instead of Chosen.js for select elements
- DataTables Bootstrap 5 integration
- JavaScript adapters for modal/tooltip compatibility

---

## TailwindCSS Template Configuration

### canvas Template

```php
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
                'css' => ['css/canvas.css'], // Custom Tailwind styles
                'js' => ['js/canvas-init.js'],
            ],
            'last' => [
                'css' => [null],
                'js' => [
                    'js/canvastack-modal-adapter.js',
                    'js/canvastack-tooltip-adapter.js',
                    'js/canvastack-class-adapter.js',
                    'js/canvas-scripts.js',
                ],
            ],
        ],
    ],
    
    // DataTables configuration
    'datatable' => [
        'js' => [
            'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
        ],
        'css' => [
            'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css',
        ],
    ],
    
    // Select plugin configuration
    'select' => [
        'plugin' => 'native',
        'js' => [null],
        'css' => [null],
    ],
],
```

**Key Differences:**
- TailwindCSS CDN (development) or custom build (production)
- No Bootstrap dependencies
- Custom JavaScript for modals and tooltips
- Native select elements (no plugin)
- CSS class adapter for framework-agnostic JavaScript

---

## Adding Custom Templates

### Step 1: Create Adapter

```php
// app/Theme/MaterialAdapter.php
namespace App\Theme;

use Canvastack\Canvastack\Library\Theme\ThemeAdapterInterface;

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
```

### Step 2: Register Adapter

```php
// app/Providers/AppServiceProvider.php
use App\Theme\MaterialAdapter;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        ThemeAdapterResolver::register('material', MaterialAdapter::class);
    }
}
```

### Step 3: Configure Assets

```php
// config/canvastack.templates.php
return [
    'template' => 'material',
    
    'material' => [
        'position' => [
            'top' => [
                'css' => [
                    'https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css',
                ],
                'js' => [
                    'https://unpkg.com/material-components-web@latest/dist/material-components-web.min.js',
                ],
            ],
            'bottom' => [
                'first' => [
                    'css' => ['css/material-custom.css'],
                    'js' => ['js/material-init.js'],
                ],
                'last' => [
                    'js' => ['js/material-scripts.js'],
                ],
            ],
        ],
        
        'datatable' => [
            'js' => ['https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js'],
            'css' => ['https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css'],
        ],
        
        'select' => [
            'plugin' => 'native',
            'js' => [null],
            'css' => [null],
        ],
    ],
];
```

### Step 4: Create Views

```bash
# Copy default views
cp -r resources/views/default resources/views/material

# Customize views for Material Design
# Update data attributes, CSS classes, etc.
```

### Step 5: Test Configuration

```bash
# Clear caches
php artisan config:clear
php artisan view:clear

# Verify template
php artisan tinker
>>> config('canvastack.templates.template')
=> "material"

>>> ThemeAdapterResolver::resolve()
=> App\Theme\MaterialAdapter
```

---

## Asset Loading Strategies

### CDN vs Local Assets

**CDN (Development):**
```php
'top' => [
    'css' => ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'],
],
```

**Pros:**
- Fast setup
- No build process
- Automatic updates

**Cons:**
- External dependency
- Privacy concerns
- No offline support

**Local (Production):**
```php
'top' => [
    'css' => ['css/bootstrap.min.css'],
],
```

**Pros:**
- No external dependencies
- Better performance (no DNS lookup)
- Offline support
- Privacy compliant

**Cons:**
- Requires build process
- Manual updates
- Larger repository size

### Conditional Loading

```php
'top' => [
    'css' => [
        env('APP_ENV') === 'production' 
            ? 'css/bootstrap.min.css' 
            : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    ],
],
```

### Null Values

Use `[null]` to skip loading for a position:

```php
'top' => [
    'css' => [null], // No CSS in top position
    'js' => ['https://cdn.tailwindcss.com'],
],
```

---

## Advanced Configuration

### Per-Environment Templates

```php
// config/canvastack.templates.php
return [
    'template' => env('CANVASTACK_TEMPLATE', 'default'),
    
    // ... template configurations
];

// .env
CANVASTACK_TEMPLATE=canvasign
```

### Multi-Tenant Templates

```php
// app/Http/Middleware/SetTenantTemplate.php
class SetTenantTemplate
{
    public function handle($request, Closure $next)
    {
        $tenant = auth()->user()->tenant;
        config(['canvastack.templates.template' => $tenant->template]);
        
        return $next($request);
    }
}
```

### Dynamic Asset Loading

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    $template = config('canvastack.templates.template');
    
    if ($template === 'canvas') {
        // Load additional Tailwind plugins
        config([
            'canvastack.templates.canvas.position.top.js' => array_merge(
                config('canvastack.templates.canvas.position.top.js'),
                ['https://cdn.jsdelivr.net/npm/@tailwindcss/forms@0.5.3/dist/forms.min.js']
            ),
        ]);
    }
}
```

---

## Troubleshooting

### Assets Not Loading

**Problem:** CSS/JS files not loading after configuration change.

**Solution:**
```bash
# Clear configuration cache
php artisan config:clear

# Verify configuration
php artisan tinker
>>> config('canvastack.templates.canvasign.position.top.css')

# Check browser console for 404 errors
# Verify CDN URLs are accessible
```

### Template Not Switching

**Problem:** Changed template but still seeing old template.

**Solution:**
```bash
# Clear all caches
php artisan config:clear
php artisan view:clear
php artisan cache:clear

# Verify template setting
php artisan tinker
>>> config('canvastack.templates.template')
>>> canvastack_current_template()
```

### Custom Template Not Working

**Problem:** Registered custom template but not being used.

**Solution:**
```php
// Verify registration in AppServiceProvider::boot()
ThemeAdapterResolver::register('custom', CustomAdapter::class);

// Verify template name matches
config(['canvastack.templates.template' => 'custom']);

// Verify adapter implements interface
class CustomAdapter implements ThemeAdapterInterface { ... }

// Clear caches
php artisan config:clear
```

---

## Best Practices

### 1. Use Environment Variables

```php
// config/canvastack.templates.php
'template' => env('CANVASTACK_TEMPLATE', 'default'),

// .env
CANVASTACK_TEMPLATE=canvasign
```

### 2. Version Lock CDN Assets

```php
// Good - version locked
'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'

// Bad - latest version (breaking changes)
'https://cdn.jsdelivr.net/npm/bootstrap@latest/dist/css/bootstrap.min.css'
```

### 3. Use Local Assets in Production

```php
'top' => [
    'css' => [
        env('APP_ENV') === 'production' 
            ? 'css/bootstrap.min.css' 
            : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    ],
],
```

### 4. Document Custom Templates

```php
// config/canvastack.templates.php

/**
 * Custom Material Design template
 * 
 * Adapter: App\Theme\MaterialAdapter
 * Framework: Material Design Components
 * Version: 14.0.0
 * Documentation: https://material.io/develop/web
 */
'material' => [
    // ... configuration
],
```

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may this configuration guide serve developers well.
