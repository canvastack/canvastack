# CanvaStack Template Engine

Template utilities standardize common layout fragments (breadcrumbs, sidebars, grid containers, avatar blocks) and asset injection (CSS/JS) for Blade-based applications.

- Primary class: `Canvastack\\Canvastack\\Library\\Components\\Utility\\Html\\TemplateUi`
- Facade convenience: `Canvatility::breadcrumb()`, `Canvatility::css()`, `Canvatility::js()`, `Canvatility::grid()` etc.
- Helpers wrapper: functions in `src/Library/Helpers/Template.php`

## Configuration

- Published config: `config/canvastack.templates.php`, `config/canvastack.settings.php`.
- `canvastack_template_config($key)` and `canvastack_current_template()` provide easy reads from config.

## CSS/JS Injection

```php
// Top or bottom positioning
echo Canvatility::css(['css/vendor.css', 'css/app.css']);
echo Canvatility::js(['js/vendor.js', 'js/app.js']);

// As inline script code (set $as_script_code = true)
echo Canvatility::js('console.log("hi")', 'bottom', true);
```

## Grid System Helpers

```php
// Start container row and columns
echo Canvatility::grid('container');
echo Canvatility::grid('row');
echo Canvatility::grid('col', ['md' => 6]);

// Wrap content within defined columns
echo Canvatility::gridColumn('<p>Left side</p>', ['md' => 6]);
echo Canvatility::gridColumn('<p>Right side</p>', ['md' => 6]);

echo Canvatility::grid('end'); // Close last opened wrapper as applicable
```

## Breadcrumbs

```php
echo Canvatility::breadcrumb('Users', [
  'Home' => route('home'),
  'Users' => route('users.index')
], 'fa-users');
```

## Sidebar

```php
echo Canvatility::sidebarContent('Admin', 'User Management', 'Roles & Permissions');
echo Canvatility::sidebarMenuOpen();
echo Canvatility::sidebarCategory('Main');
echo Canvatility::sidebarMenu('Users', [ 'List' => route('users.index'), 'Create' => route('users.create') ], ['icon' => 'fa-users']);
echo Canvatility::sidebarMenuClose();
```

## Avatar

```php
echo Canvatility::avatar('Alice', route('profile.show'), '/images/alice.png', 'online');
```

## Best Practices

- Keep template logic in utility layer; views stay clean and focused on composition.
- Centralize color/class schemes via config to avoid scattering styles.
- Prefer semantic HTML and accessibility attributes when generating components.