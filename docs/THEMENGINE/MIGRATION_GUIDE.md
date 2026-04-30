# Migration Guide

**Version:** 2.0.0  
**Last Updated:** April 4, 2026

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

## Overview

This guide provides step-by-step instructions for migrating existing CanvaStack projects to use the Theme Engine. The Theme Engine is designed with **100% backward compatibility**, meaning existing code will continue to work without modifications.

---

## Zero-Effort Migration

### Backward Compatibility Guarantees

The Theme Engine maintains complete backward compatibility:

- ✅ **No Breaking Changes** - All public APIs remain unchanged
- ✅ **Identical Output** - `default` template produces byte-for-byte identical HTML
- ✅ **No Code Changes Required** - Existing views and controllers work as-is
- ✅ **No Configuration Changes Required** - Default configuration is preserved
- ✅ **No Database Changes Required** - No migrations needed

**Result:** Upgrading to Theme Engine v2.0.0 requires **zero code changes** for existing projects using the `default` template.

---

## Migration Scenarios

### Scenario 1: Keep Using Bootstrap 4 (No Changes)

**Use Case:** You want to keep using Bootstrap 4 and don't need multi-framework support.

**Steps:**

1. **Update Package**
   ```bash
   composer update canvastack/canvastack
   ```

2. **Verify Configuration**
   ```php
   // config/canvastack.templates.php
   'template' => 'default', // Already set by default
   ```

3. **Test Application**
   ```bash
   # Visit your application
   # Verify everything works as before
   # No visual changes expected
   ```

**Result:** Your application continues to work exactly as before with zero changes.

---

### Scenario 2: Migrate to Bootstrap 5

**Use Case:** You want to upgrade to Bootstrap 5 for modern features and better performance.

**Steps:**

#### Step 1: Backup

```bash
# Backup database
php artisan backup:run

# Backup code
git commit -am "Backup before Bootstrap 5 migration"
```

#### Step 2: Update Configuration

```php
// config/canvastack.templates.php
return [
    'template' => 'canvasign', // Change from 'default' to 'canvasign'
    
    // ... rest of configuration
];
```

#### Step 3: Clear Caches

```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

#### Step 4: Test Core Functionality

**Test Checklist:**
- [ ] Page loads without errors
- [ ] Forms render correctly
- [ ] Modals open and close
- [ ] Tooltips display
- [ ] Tables render and paginate
- [ ] Alerts display and dismiss
- [ ] Tabs switch correctly
- [ ] Select dropdowns work

#### Step 5: Update Custom Views (Optional)

If you have custom views with hardcoded Bootstrap 4 classes:

```blade
{{-- Before (Bootstrap 4) --}}
<button data-toggle="modal" data-target="#myModal">Open</button>
<div class="pull-right hide">Content</div>

{{-- After (Bootstrap 5) --}}
<button data-bs-toggle="modal" data-bs-target="#myModal">Open</button>
<div class="float-end d-none">Content</div>
```

**Common Class Changes:**

| Bootstrap 4 | Bootstrap 5 |
|-------------|-------------|
| `data-toggle` | `data-bs-toggle` |
| `data-dismiss` | `data-bs-dismiss` |
| `data-target` | `data-bs-target` |
| `pull-right` | `float-end` |
| `pull-left` | `float-start` |
| `hide` | `d-none` |
| `show` | `d-block` |
| `btn-xs` | `btn-sm` |
| `alert-block` | (removed) |

#### Step 6: Update Custom JavaScript (Optional)

If you have custom JavaScript using Bootstrap 4 API:

```javascript
// Before (Bootstrap 4)
$('#myModal').modal('show');
$('[data-toggle="tooltip"]').tooltip();

// After (Bootstrap 5)
var myModal = new bootstrap.Modal(document.getElementById('myModal'));
myModal.show();

var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
```

**Or use CanvaStack adapters:**

```javascript
// Framework-agnostic (works with both BS4 and BS5)
CanvaStackModal.show('myModal');
CanvaStackTooltip.init();
```

#### Step 7: Verify and Deploy

```bash
# Run tests
php artisan test

# Deploy to staging
# Test thoroughly
# Deploy to production
```

---

### Scenario 3: Migrate to TailwindCSS

**Use Case:** You want to use utility-first CSS with TailwindCSS.

**Steps:**

#### Step 1: Backup

```bash
# Backup database
php artisan backup:run

# Backup code
git commit -am "Backup before TailwindCSS migration"
```

#### Step 2: Update Configuration

```php
// config/canvastack.templates.php
return [
    'template' => 'canvas', // Change to 'canvas'
    
    // ... rest of configuration
];
```

#### Step 3: Configure TailwindCSS

**Option A: CDN (Development)**
```php
// config/canvastack.templates.php
'canvas' => [
    'position' => [
        'top' => [
            'js' => ['https://cdn.tailwindcss.com'],
        ],
    ],
],
```

**Option B: Custom Build (Production)**
```bash
# Install Tailwind
npm install -D tailwindcss

# Create config
npx tailwindcss init

# Configure tailwind.config.js
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
    ],
    theme: {
        extend: {},
    },
    plugins: [],
}

# Build CSS
npx tailwindcss -i ./resources/css/app.css -o ./public/css/canvas.css --watch
```

```php
// config/canvastack.templates.php
'canvas' => [
    'position' => [
        'bottom' => [
            'first' => [
                'css' => ['css/canvas.css'], // Custom Tailwind build
            ],
        ],
    ],
],
```

#### Step 4: Clear Caches

```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

#### Step 5: Update Custom Views

TailwindCSS requires more extensive view updates:

```blade
{{-- Before (Bootstrap 4) --}}
<div class="container">
    <div class="row">
        <div class="col-md-6">
            <button class="btn btn-primary">Submit</button>
        </div>
    </div>
</div>

{{-- After (TailwindCSS) --}}
<div class="container mx-auto">
    <div class="flex flex-wrap">
        <div class="w-full md:w-1/2">
            <button class="px-4 py-2 bg-blue-500 text-white rounded">Submit</button>
        </div>
    </div>
</div>
```

**Common Class Conversions:**

| Bootstrap | TailwindCSS |
|-----------|-------------|
| `container` | `container mx-auto` |
| `row` | `flex flex-wrap` |
| `col-md-6` | `w-full md:w-1/2` |
| `btn btn-primary` | `px-4 py-2 bg-blue-500 text-white rounded` |
| `pull-right` | `ml-auto` |
| `hide` | `hidden` |
| `show` | `block` |
| `text-center` | `text-center` (same) |

#### Step 6: Update Custom JavaScript

```javascript
// Use CanvaStack adapters for framework-agnostic code
CanvaStackModal.show('myModal');
CanvaStackClass.get('hide'); // Returns 'hidden' for Tailwind
```

#### Step 7: Verify and Deploy

```bash
# Run tests
php artisan test

# Deploy to staging
# Test thoroughly
# Deploy to production
```

---

## Testing Migration

### Manual Testing Checklist

**Core Functionality:**
- [ ] Application loads without errors
- [ ] No console errors in browser
- [ ] CSS framework assets load correctly
- [ ] Page layout renders correctly

**Forms:**
- [ ] Form fields render correctly
- [ ] Select dropdowns work
- [ ] Checkboxes and radios work
- [ ] Form validation works
- [ ] Form submission works

**Modals:**
- [ ] Modals open correctly
- [ ] Modals close correctly
- [ ] Modal backdrop works
- [ ] Multiple modals work

**Tables:**
- [ ] Tables render correctly
- [ ] Pagination works
- [ ] Sorting works
- [ ] Filtering works
- [ ] Action buttons work

**UI Components:**
- [ ] Alerts display and dismiss
- [ ] Tooltips display
- [ ] Tabs switch correctly
- [ ] Breadcrumbs render
- [ ] Navigation works

### Automated Testing

```bash
# Run Theme Engine tests
php artisan test --filter=ThemeAdapter

# Run property-based tests
php artisan test tests/Property/ThemeAdapterPropertiesTest.php

# Run integration tests
php artisan test tests/Integration/ThemeEngineIntegrationTest.php

# Run full test suite
php artisan test
```

---

## Rollback Procedures

### If Migration Fails

#### Step 1: Revert Configuration

```php
// config/canvastack.templates.php
return [
    'template' => 'default', // Revert to default
];
```

#### Step 2: Clear Caches

```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

#### Step 3: Verify Application

```bash
# Visit application
# Verify everything works
```

#### Step 4: Restore from Backup (If Needed)

```bash
# Restore database
php artisan backup:restore

# Restore code
git revert HEAD
```

---

## Common Migration Issues

### Issue 1: Modals Not Working

**Symptoms:** Modals don't open after switching to Bootstrap 5 or TailwindCSS.

**Cause:** JavaScript using Bootstrap 4 API.

**Solution:**
```javascript
// Use CanvaStack modal adapter
CanvaStackModal.show('myModal');
CanvaStackModal.hide('myModal');

// Or update to Bootstrap 5 API
var myModal = new bootstrap.Modal(document.getElementById('myModal'));
myModal.show();
```

### Issue 2: Tooltips Not Displaying

**Symptoms:** Tooltips don't appear after switching templates.

**Cause:** Different tooltip initialization for each framework.

**Solution:**
```javascript
// Use CanvaStack tooltip adapter
CanvaStackTooltip.init();

// Automatically detects framework and initializes correctly
```

### Issue 3: Select Dropdowns Not Working

**Symptoms:** Select dropdowns don't have search/filter functionality.

**Cause:** Different select plugins for each framework (Chosen.js vs Choices.js).

**Solution:**
```php
// Verify select plugin configuration
// config/canvastack.templates.php
'canvasign' => [
    'select' => [
        'plugin' => 'choices',
        'js' => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js'],
        'css' => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css'],
    ],
],
```

### Issue 4: CSS Classes Not Applied

**Symptoms:** Elements don't have correct styling after switching templates.

**Cause:** Hardcoded Bootstrap 4 classes in custom views.

**Solution:**
```blade
{{-- Update hardcoded classes --}}
{{-- Before --}}
<div class="pull-right hide">Content</div>

{{-- After (Bootstrap 5) --}}
<div class="float-end d-none">Content</div>

{{-- After (TailwindCSS) --}}
<div class="ml-auto hidden">Content</div>
```

### Issue 5: Assets Not Loading

**Symptoms:** CSS/JS files return 404 errors.

**Cause:** Incorrect asset paths or CDN URLs.

**Solution:**
```php
// Verify asset configuration
// config/canvastack.templates.php
'canvasign' => [
    'position' => [
        'top' => [
            'css' => [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            ],
        ],
    ],
],

// Check browser console for 404 errors
// Verify CDN URLs are accessible
// Use local assets if CDN is blocked
```

---

## Best Practices

### 1. Test in Staging First

```bash
# Never test in production
# Always use staging environment
# Test thoroughly before deploying
```

### 2. Gradual Migration

```php
// Migrate one section at a time
// Start with admin panel
// Then migrate frontend
// Finally migrate custom pages
```

### 3. Use Framework-Agnostic Code

```javascript
// Good - works with all frameworks
CanvaStackModal.show('myModal');
CanvaStackClass.get('hide');

// Bad - framework-specific
$('#myModal').modal('show'); // Bootstrap 4 only
```

### 4. Document Custom Changes

```php
// Document any custom template modifications
// Keep a changelog of view updates
// Document JavaScript API changes
```

### 5. Monitor After Deployment

```bash
# Monitor error logs
tail -f storage/logs/laravel.log

# Monitor browser console
# Check for JavaScript errors
# Verify asset loading
```

---

## Migration Checklist

### Pre-Migration

- [ ] Backup database
- [ ] Backup code (git commit)
- [ ] Document current template
- [ ] List custom views
- [ ] List custom JavaScript
- [ ] Review asset dependencies

### Migration

- [ ] Update configuration
- [ ] Clear caches
- [ ] Test core functionality
- [ ] Update custom views (if needed)
- [ ] Update custom JavaScript (if needed)
- [ ] Run automated tests
- [ ] Test in staging

### Post-Migration

- [ ] Deploy to production
- [ ] Monitor error logs
- [ ] Monitor browser console
- [ ] Verify user reports
- [ ] Document changes
- [ ] Update team documentation

---

## Support

If you encounter issues during migration:

1. Check [Troubleshooting Guide](./TROUBLESHOOTING.md)
2. Review [Common Migration Issues](#common-migration-issues)
3. Check application logs: `storage/logs/laravel.log`
4. Check browser console for JavaScript errors
5. Contact support: support@canvastack.com

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may this migration guide help projects transition smoothly.
