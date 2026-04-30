# Troubleshooting Guide

**Version:** 2.0.0  
**Last Updated:** April 4, 2026

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

## Overview

This guide provides solutions to common issues encountered when using the Theme Engine.

---

## Template Issues

### Template Not Switching

**Symptoms:**
- Changed template in config but still seeing old template
- UI still shows Bootstrap 4 after switching to Bootstrap 5

**Causes:**
- Configuration cache not cleared
- View cache not cleared
- Browser cache

**Solutions:**

1. **Clear All Caches:**
   ```bash
   php artisan config:clear
   php artisan view:clear
   php artisan cache:clear
   ```

2. **Verify Configuration:**
   ```bash
   php artisan tinker
   >>> config('canvastack.templates.template')
   => "canvasign"
   
   >>> canvastack_current_template()
   => "canvasign"
   ```

3. **Clear Browser Cache:**
   - Hard refresh: Ctrl+Shift+R (Windows/Linux) or Cmd+Shift+R (Mac)
   - Or open in incognito/private window

4. **Verify Template Variable:**
   ```blade
   {{-- In your layout --}}
   <script>
       console.log('Template:', '{{ canvastack_current_template() }}');
   </script>
   ```

---

### Wrong Template Being Used

**Symptoms:**
- Template is set to 'canvasign' but Bootstrap 4 is being used
- Adapter is not matching template

**Causes:**
- `canvastack_current_template()` returning wrong value
- Middleware or service provider overriding template

**Solutions:**

1. **Check Template Resolution:**
   ```php
   // Add to controller or view
   dd(canvastack_current_template());
   dd(config('canvastack.templates.template'));
   ```

2. **Check for Overrides:**
   ```bash
   # Search for config() calls that might override template
   grep -r "config\(\['canvastack.templates.template'" app/
   ```

3. **Check Middleware:**
   ```php
   // Check if any middleware is changing template
   // app/Http/Middleware/*
   ```

---

## Asset Issues

### Assets Not Loading

**Symptoms:**
- CSS/JS files return 404 errors
- Broken layout, no styling
- JavaScript errors in console

**Causes:**
- Incorrect asset paths
- CDN URLs blocked or unavailable
- Asset configuration missing

**Solutions:**

1. **Check Browser Console:**
   - Open Developer Tools (F12)
   - Check Console tab for errors
   - Check Network tab for 404 errors

2. **Verify Asset Configuration:**
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

3. **Test CDN URLs:**
   ```bash
   # Test if CDN is accessible
   curl -I https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css
   ```

4. **Use Local Assets:**
   ```php
   // If CDN is blocked, use local assets
   'top' => [
       'css' => ['css/bootstrap.min.css'],
       'js' => ['js/bootstrap.bundle.min.js'],
   ],
   ```

---

### Mixed Content Errors

**Symptoms:**
- Browser console shows "Mixed Content" errors
- Assets not loading on HTTPS site

**Cause:**
- HTTP assets on HTTPS site

**Solution:**

```php
// Use HTTPS URLs for all assets
'top' => [
    'css' => [
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', // HTTPS
    ],
],
```

---

## Modal Issues

### Modals Not Opening

**Symptoms:**
- Clicking modal trigger does nothing
- No errors in console

**Causes:**
- JavaScript not loaded
- Wrong data attributes
- Modal adapter not loaded

**Solutions:**

1. **Check JavaScript Loaded:**
   ```javascript
   // In browser console
   console.log(typeof CanvaStackModal);
   // Should return 'object', not 'undefined'
   ```

2. **Verify Data Attributes:**
   ```html
   <!-- Bootstrap 4 -->
   <button data-toggle="modal" data-target="#myModal">Open</button>
   
   <!-- Bootstrap 5 -->
   <button data-bs-toggle="modal" data-bs-target="#myModal">Open</button>
   
   <!-- Or use adapter -->
   <button onclick="CanvaStackModal.show('myModal')">Open</button>
   ```

3. **Check Modal ID:**
   ```html
   <!-- Ensure modal ID matches trigger target -->
   <div class="modal" id="myModal">...</div>
   ```

4. **Load Modal Adapter:**
   ```html
   <script src="{{ asset('js/canvastack-modal-adapter.js') }}"></script>
   ```

---

### Modals Not Closing

**Symptoms:**
- Modal opens but won't close
- Close button doesn't work

**Causes:**
- Wrong dismiss attribute
- JavaScript error preventing close

**Solutions:**

1. **Check Dismiss Attribute:**
   ```html
   <!-- Bootstrap 4 -->
   <button data-dismiss="modal">Close</button>
   
   <!-- Bootstrap 5 -->
   <button data-bs-dismiss="modal">Close</button>
   
   <!-- Or use adapter -->
   <button onclick="CanvaStackModal.hide('myModal')">Close</button>
   ```

2. **Check Console for Errors:**
   - Open Developer Tools (F12)
   - Check Console tab for JavaScript errors

---

## Tooltip Issues

### Tooltips Not Displaying

**Symptoms:**
- Hovering over elements doesn't show tooltips
- No tooltip initialization

**Causes:**
- Tooltips not initialized
- Wrong data attributes
- Tooltip library not loaded

**Solutions:**

1. **Initialize Tooltips:**
   ```javascript
   // Call after page load
   CanvaStackTooltip.init();
   
   // Or manually for Bootstrap 5
   var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
   tooltipTriggerList.forEach(function (tooltipTriggerEl) {
       new bootstrap.Tooltip(tooltipTriggerEl);
   });
   ```

2. **Check Data Attributes:**
   ```html
   <!-- Bootstrap 4 -->
   <button data-toggle="tooltip" title="Tooltip text">Hover</button>
   
   <!-- Bootstrap 5 -->
   <button data-bs-toggle="tooltip" title="Tooltip text">Hover</button>
   
   <!-- TailwindCSS (Tippy.js) -->
   <button data-tippy-content="Tooltip text">Hover</button>
   ```

3. **Load Tooltip Library:**
   ```html
   <!-- For TailwindCSS -->
   <script src="https://unpkg.com/@popperjs/core@2"></script>
   <script src="https://unpkg.com/tippy.js@6"></script>
   ```

---

## Select Element Issues

### Select Dropdowns Not Working

**Symptoms:**
- Select elements don't have search functionality
- Dropdowns look plain/unstyled

**Causes:**
- Select plugin not loaded
- Wrong plugin for template
- Plugin not initialized

**Solutions:**

1. **Verify Plugin Configuration:**
   ```php
   // config/canvastack.templates.php
   'default' => [
       'select' => [
           'plugin' => 'chosen',
           'js' => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js'],
           'css' => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css'],
       ],
   ],
   
   'canvasign' => [
       'select' => [
           'plugin' => 'choices',
           'js' => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js'],
           'css' => ['https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css'],
       ],
   ],
   ```

2. **Initialize Plugin:**
   ```javascript
   // Bootstrap 4 (Chosen.js)
   $('.chosen-select-deselect').chosen({
       allow_single_deselect: true,
       width: '100%'
   });
   
   // Bootstrap 5 (Choices.js)
   const choices = new Choices('.form-select', {
       removeItemButton: true,
   });
   ```

3. **Check Plugin Loaded:**
   ```javascript
   // In browser console
   console.log(typeof $.fn.chosen); // Bootstrap 4
   console.log(typeof Choices); // Bootstrap 5
   ```

---

## CSS Class Issues

### Wrong CSS Classes Applied

**Symptoms:**
- Elements have Bootstrap 4 classes in Bootstrap 5 template
- Styling doesn't match framework

**Causes:**
- Hardcoded classes in custom views
- Not using helper functions

**Solutions:**

1. **Update Hardcoded Classes:**
   ```blade
   {{-- Before (Bootstrap 4) --}}
   <div class="pull-right hide">Content</div>
   
   {{-- After (Bootstrap 5) --}}
   <div class="float-end d-none">Content</div>
   
   {{-- After (TailwindCSS) --}}
   <div class="ml-auto hidden">Content</div>
   ```

2. **Use Helper Functions:**
   ```php
   // Good - uses adapter
   echo canvastack_form_create_header_tab('Users', 'users-tab', true, false);
   
   // Avoid - hardcoded HTML
   echo '<li class="nav-item"><a data-toggle="tab"...';
   ```

3. **Use CSS Class Adapter:**
   ```javascript
   // Framework-agnostic
   const hideClass = CanvaStackClass.get('hide');
   element.classList.add(hideClass);
   ```

---

## View Issues

### View Not Found

**Symptoms:**
- "View not found" error after switching template
- Missing view files

**Causes:**
- View files don't exist for new template
- View path not resolved correctly

**Solutions:**

1. **Copy Default Views:**
   ```bash
   cp -r resources/views/default resources/views/canvasign
   ```

2. **Rely on Fallback:**
   - System automatically falls back to `default` views if template views don't exist
   - No action needed if you want to use default views

3. **Verify View Path:**
   ```php
   // In controller or view
   dd(view()->exists('canvasign.pages.admin.index'));
   ```

---

## Custom Adapter Issues

### Custom Adapter Not Working

**Symptoms:**
- Registered custom adapter but not being used
- Still using DefaultAdapter

**Causes:**
- Registration not called
- Template name mismatch
- Adapter doesn't implement interface

**Solutions:**

1. **Verify Registration:**
   ```php
   // app/Providers/AppServiceProvider.php
   public function boot()
   {
       ThemeAdapterResolver::register('custom', CustomAdapter::class);
   }
   ```

2. **Verify Template Name:**
   ```php
   // config/canvastack.templates.php
   'template' => 'custom', // Must match registration name
   ```

3. **Verify Interface Implementation:**
   ```php
   class CustomAdapter implements ThemeAdapterInterface
   {
       // Must implement all 14 methods
   }
   ```

4. **Clear Caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

---

## Performance Issues

### Slow Page Load

**Symptoms:**
- Pages load slowly after switching template
- High server response time

**Causes:**
- CDN assets loading slowly
- Too many HTTP requests
- No caching

**Solutions:**

1. **Use Local Assets:**
   ```php
   // Instead of CDN
   'top' => [
       'css' => ['css/bootstrap.min.css'],
       'js' => ['js/bootstrap.bundle.min.js'],
   ],
   ```

2. **Enable Caching:**
   ```bash
   php artisan config:cache
   php artisan view:cache
   php artisan route:cache
   ```

3. **Combine Assets:**
   ```bash
   # Use Laravel Mix or Vite to combine assets
   npm run production
   ```

---

## Debugging Tips

### Enable Debug Mode

```php
// .env
APP_DEBUG=true
```

### Check Logs

```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# Search for Theme Engine errors
grep -i "theme" storage/logs/laravel.log
```

### Add Debug Output

```php
// In controller or view
dd(canvastack_current_template());
dd(ThemeAdapterResolver::resolve());
dd(config('canvastack.templates'));
```

### Browser Console

```javascript
// Check template
console.log(window.canvastackTemplate);

// Check adapters loaded
console.log(typeof CanvaStackModal);
console.log(typeof CanvaStackTooltip);
console.log(typeof CanvaStackClass);
```

---

## Getting Help

If you're still experiencing issues:

1. **Check Documentation:**
   - [Getting Started Guide](./GETTING_STARTED.md)
   - [Architecture Documentation](./ARCHITECTURE.md)
   - [API Reference](./API_REFERENCE.md)

2. **Check Logs:**
   - `storage/logs/laravel.log`
   - Browser console (F12)

3. **Contact Support:**
   - Email: support@canvastack.com
   - GitHub Issues

4. **Provide Information:**
   - Template name
   - Error messages
   - Browser console output
   - Laravel version
   - PHP version

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may this troubleshooting guide help resolve issues quickly.
