# Environment Variables Documentation

This document provides comprehensive documentation for all environment variables used by CanvaStack.

## Table of Contents

- [Application Settings](#application-settings)
- [Theme & UI Settings](#theme--ui-settings)
- [Localization Settings](#localization-settings)
- [Cache Settings](#cache-settings)
- [RBAC Settings](#rbac-settings)
- [Performance Settings](#performance-settings)
- [Email Settings](#email-settings)
- [Meta Tags Settings](#meta-tags-settings)
- [Copyright Settings](#copyright-settings)

---

## Application Settings

### CANVASTACK_APP_NAME

**Description**: The name of your CanvaStack application.

**Type**: String

**Default**: `CanvaStack`

**Example**:
```env
CANVASTACK_APP_NAME="My Admin Panel"
```

---

### CANVASTACK_APP_DESC

**Description**: A brief description of your application.

**Type**: String

**Default**: `CanvaStack Application`

**Example**:
```env
CANVASTACK_APP_DESC="Enterprise Admin Dashboard"
```

---

### CANVASTACK_MAINTENANCE

**Description**: Enable or disable maintenance mode for the application.

**Type**: Boolean

**Default**: `false`

**Values**: `true`, `false`

**Example**:
```env
CANVASTACK_MAINTENANCE=false
```

---

## Theme & UI Settings

### CANVASTACK_THEME

**Description**: The active theme for the application.

**Type**: String

**Default**: `gradient`

**Available Themes**:
- `gradient` - Modern gradient theme (indigo, purple, fuchsia)
- `ocean` - Cool ocean theme (blue, cyan, teal)
- `sunset` - Warm sunset theme (orange, red, pink)
- `forest` - Natural forest theme (green, emerald, teal)
- `midnight` - Dark midnight theme (slate, blue)

**Example**:
```env
CANVASTACK_THEME=gradient
```

---

### CANVASTACK_THEME_CACHE

**Description**: Enable or disable theme caching for better performance.

**Type**: Boolean

**Default**: `true`

**Values**: `true`, `false`

**Example**:
```env
CANVASTACK_THEME_CACHE=true
```

**Note**: Disable caching during theme development for hot-reload.

---

### CANVASTACK_THEME_CACHE_STORE

**Description**: Cache store to use for theme caching.

**Type**: String

**Default**: `redis`

**Values**: `redis`, `file`, `array`

**Example**:
```env
CANVASTACK_THEME_CACHE_STORE=redis
```

---

### CANVASTACK_THEME_HOT_RELOAD

**Description**: Enable hot-reload for theme development.

**Type**: Boolean

**Default**: `false`

**Values**: `true`, `false`

**Example**:
```env
CANVASTACK_THEME_HOT_RELOAD=true
```

**Note**: Only enable in development environment.

---

### CANVASTACK_DARK_MODE_ENABLED

**Description**: Enable or disable dark mode support.

**Type**: Boolean

**Default**: `true`

**Values**: `true`, `false`

**Example**:
```env
CANVASTACK_DARK_MODE_ENABLED=true
```

---

### CANVASTACK_DARK_MODE_DEFAULT

**Description**: Default color mode when user first visits.

**Type**: String

**Default**: `light`

**Values**: `light`, `dark`

**Example**:
```env
CANVASTACK_DARK_MODE_DEFAULT=light
```

---

### CANVASTACK_TEMPLATE

**Description**: The active template for asset loading.

**Type**: String

**Default**: `default`

**Example**:
```env
CANVASTACK_TEMPLATE=default
```

---

### CANVASTACK_ANIMATIONS_ENABLED

**Description**: Enable or disable animations (GSAP).

**Type**: Boolean

**Default**: `true`

**Values**: `true`, `false`

**Example**:
```env
CANVASTACK_ANIMATIONS_ENABLED=true
```

**Note**: Disable for better performance on low-end devices.

---

## Localization Settings

### CANVASTACK_DEFAULT_LOCALE

**Description**: The default locale for the application.

**Type**: String (2-letter ISO code)

**Default**: `en`

**Available Locales**: `en`, `id`

**Example**:
```env
CANVASTACK_DEFAULT_LOCALE=en
```

---

### CANVASTACK_FALLBACK_LOCALE

**Description**: Fallback locale when translation is missing.

**Type**: String (2-letter ISO code)

**Default**: `en`

**Example**:
```env
CANVASTACK_FALLBACK_LOCALE=en
```

---

### CANVASTACK_LOCALE_STORAGE

**Description**: Storage method for user locale preference.

**Type**: String

**Default**: `session`

**Values**: `session`, `cookie`, `both`

**Example**:
```env
CANVASTACK_LOCALE_STORAGE=session
```

---

### CANVASTACK_DETECT_BROWSER_LOCALE

**Description**: Automatically detect locale from browser Accept-Language header.

**Type**: Boolean

**Default**: `true`

**Values**: `true`, `false`

**Example**:
```env
CANVASTACK_DETECT_BROWSER_LOCALE=true
```

---

### CANVASTACK_TRANSLATION_CACHE

**Description**: Enable or disable translation caching.

**Type**: Boolean

**Default**: `true`

**Values**: `true`, `false`

**Example**:
```env
CANVASTACK_TRANSLATION_CACHE=true
```

---

### CANVASTACK_LOG_MISSING_TRANSLATIONS

**Description**: Log missing translations for debugging.

**Type**: Boolean

**Default**: `false`

**Values**: `true`, `false`

**Example**:
```env
CANVASTACK_LOG_MISSING_TRANSLATIONS=true
```

**Note**: Enable in development to identify missing translations.

---

## Cache Settings

### CANVASTACK_CACHE_ENABLED

**Description**: Enable or disable CanvaStack caching system.

**Type**: Boolean

**Default**: `true`

**Values**: `true`, `false`

**Example**:
```env
CANVASTACK_CACHE_ENABLED=true
```

---

### CANVASTACK_CACHE_DRIVER

**Description**: Cache driver to use for CanvaStack caching.

**Type**: String

**Default**: `redis`

**Values**: `redis`, `file`, `array`

**Example**:
```env
CANVASTACK_CACHE_DRIVER=redis
```

**Recommendations**:
- **Production**: Use `redis` for best performance
- **Development**: Use `array` or `file`
- **Testing**: Use `array`

---

## RBAC Settings

### CANVASTACK_RBAC_CACHE_ENABLED

**Description**: Enable or disable RBAC permission caching.

**Type**: Boolean

**Default**: `true`

**Values**: `true`, `false`

**Example**:
```env
CANVASTACK_RBAC_CACHE_ENABLED=true
```

---

### CANVASTACK_RBAC_LOG_ENABLED

**Description**: Enable or disable RBAC activity logging.

**Type**: Boolean

**Default**: `true`

**Values**: `true`, `false`

**Example**:
```env
CANVASTACK_RBAC_LOG_ENABLED=true
```

---

## Performance Settings

These settings are typically configured in the config files, but can be overridden via environment variables if needed.

### CANVASTACK_CHUNK_SIZE

**Description**: Chunk size for processing large datasets.

**Type**: Integer

**Default**: `100`

**Range**: 10-1000

**Example**:
```env
CANVASTACK_CHUNK_SIZE=100
```

---

## Email Settings

### CANVASTACK_MAIL_CC_ADDRESS

**Description**: CC email address for notifications.

**Type**: String (email)

**Default**: `null`

**Example**:
```env
CANVASTACK_MAIL_CC_ADDRESS=admin@example.com
```

---

### CANVASTACK_MAIL_CC_NAME

**Description**: CC recipient name.

**Type**: String

**Default**: `null`

**Example**:
```env
CANVASTACK_MAIL_CC_NAME="Admin Team"
```

---

### CANVASTACK_MAIL_SIGNATURE

**Description**: Email signature for outgoing emails.

**Type**: String

**Default**: `CanvaStack Team`

**Example**:
```env
CANVASTACK_MAIL_SIGNATURE="My Company Team"
```

---

## Meta Tags Settings

### CANVASTACK_META_AUTHOR

**Description**: Default meta author tag.

**Type**: String

**Default**: `CanvaStack`

**Example**:
```env
CANVASTACK_META_AUTHOR="My Company"
```

---

### CANVASTACK_META_TITLE

**Description**: Default meta title tag.

**Type**: String

**Default**: `CanvaStack`

**Example**:
```env
CANVASTACK_META_TITLE="My Admin Panel"
```

---

### CANVASTACK_META_KEYWORDS

**Description**: Default meta keywords tag.

**Type**: String (comma-separated)

**Default**: `CanvaStack, Laravel, CMS`

**Example**:
```env
CANVASTACK_META_KEYWORDS="admin, dashboard, laravel, cms"
```

---

### CANVASTACK_META_DESCRIPTION

**Description**: Default meta description tag.

**Type**: String

**Default**: `CanvaStack Application`

**Example**:
```env
CANVASTACK_META_DESCRIPTION="Modern admin dashboard built with Laravel"
```

---

## Copyright Settings

### CANVASTACK_COPYRIGHT

**Description**: Copyright text for footer.

**Type**: String

**Default**: `CanvaStack`

**Example**:
```env
CANVASTACK_COPYRIGHT="My Company"
```

---

### CANVASTACK_LOCATION

**Description**: Company location.

**Type**: String

**Default**: `Jakarta`

**Example**:
```env
CANVASTACK_LOCATION="San Francisco"
```

---

### CANVASTACK_LOCATION_ABBR

**Description**: Location abbreviation (country code).

**Type**: String (2-letter ISO code)

**Default**: `ID`

**Example**:
```env
CANVASTACK_LOCATION_ABBR=US
```

---

### CANVASTACK_EMAIL

**Description**: Company contact email.

**Type**: String (email)

**Default**: `info@canvastack.com`

**Example**:
```env
CANVASTACK_EMAIL=contact@mycompany.com
```

---

### CANVASTACK_WEBSITE

**Description**: Company website URL.

**Type**: String (domain)

**Default**: `canvastack.com`

**Example**:
```env
CANVASTACK_WEBSITE=mycompany.com
```

---

## Activity Logging Settings

### CANVASTACK_LOG_ACTIVITY

**Description**: Enable or disable user activity logging.

**Type**: Boolean

**Default**: `true`

**Values**: `true`, `false`

**Example**:
```env
CANVASTACK_LOG_ACTIVITY=true
```

---

## Platform Settings

### CANVASTACK_PLATFORM_TYPE

**Description**: Platform type for multi-platform support.

**Type**: String

**Default**: `single`

**Values**: `single`, `multiple`

**Example**:
```env
CANVASTACK_PLATFORM_TYPE=single
```

---

### CANVASTACK_PLATFORM_TABLE

**Description**: Database table for platform data (multi-platform mode).

**Type**: String

**Default**: `null`

**Example**:
```env
CANVASTACK_PLATFORM_TABLE=platforms
```

---

## Complete .env Example

Here's a complete example of all CanvaStack environment variables:

```env
# Application Settings
CANVASTACK_APP_NAME="My Admin Panel"
CANVASTACK_APP_DESC="Enterprise Admin Dashboard"
CANVASTACK_MAINTENANCE=false

# Theme & UI Settings
CANVASTACK_THEME=gradient
CANVASTACK_THEME_CACHE=true
CANVASTACK_THEME_CACHE_STORE=redis
CANVASTACK_THEME_HOT_RELOAD=false
CANVASTACK_DARK_MODE_ENABLED=true
CANVASTACK_DARK_MODE_DEFAULT=light
CANVASTACK_TEMPLATE=default
CANVASTACK_ANIMATIONS_ENABLED=true

# Localization Settings
CANVASTACK_DEFAULT_LOCALE=en
CANVASTACK_FALLBACK_LOCALE=en
CANVASTACK_LOCALE_STORAGE=session
CANVASTACK_DETECT_BROWSER_LOCALE=true
CANVASTACK_TRANSLATION_CACHE=true
CANVASTACK_LOG_MISSING_TRANSLATIONS=false

# Cache Settings
CANVASTACK_CACHE_ENABLED=true
CANVASTACK_CACHE_DRIVER=redis

# RBAC Settings
CANVASTACK_RBAC_CACHE_ENABLED=true
CANVASTACK_RBAC_LOG_ENABLED=true

# Performance Settings
CANVASTACK_CHUNK_SIZE=100

# Email Settings
CANVASTACK_MAIL_CC_ADDRESS=admin@example.com
CANVASTACK_MAIL_CC_NAME="Admin Team"
CANVASTACK_MAIL_SIGNATURE="My Company Team"

# Meta Tags Settings
CANVASTACK_META_AUTHOR="My Company"
CANVASTACK_META_TITLE="My Admin Panel"
CANVASTACK_META_KEYWORDS="admin, dashboard, laravel, cms"
CANVASTACK_META_DESCRIPTION="Modern admin dashboard built with Laravel"

# Copyright Settings
CANVASTACK_COPYRIGHT="My Company"
CANVASTACK_LOCATION="San Francisco"
CANVASTACK_LOCATION_ABBR=US
CANVASTACK_EMAIL=contact@mycompany.com
CANVASTACK_WEBSITE=mycompany.com

# Activity Logging
CANVASTACK_LOG_ACTIVITY=true

# Platform Settings
CANVASTACK_PLATFORM_TYPE=single
```

---

## Environment-Specific Recommendations

### Development Environment

```env
CANVASTACK_THEME_CACHE=false
CANVASTACK_THEME_HOT_RELOAD=true
CANVASTACK_CACHE_DRIVER=array
CANVASTACK_LOG_MISSING_TRANSLATIONS=true
CANVASTACK_ANIMATIONS_ENABLED=true
```

### Production Environment

```env
CANVASTACK_THEME_CACHE=true
CANVASTACK_THEME_HOT_RELOAD=false
CANVASTACK_CACHE_DRIVER=redis
CANVASTACK_LOG_MISSING_TRANSLATIONS=false
CANVASTACK_ANIMATIONS_ENABLED=true
```

### Testing Environment

```env
CANVASTACK_THEME_CACHE=false
CANVASTACK_CACHE_DRIVER=array
CANVASTACK_LOG_MISSING_TRANSLATIONS=false
CANVASTACK_ANIMATIONS_ENABLED=false
```

---

## Troubleshooting

### Cache Issues

If you experience caching issues:

1. Clear CanvaStack cache:
   ```bash
   php artisan canvastack:cache:clear
   ```

2. Clear Laravel cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

3. Temporarily disable caching:
   ```env
   CANVASTACK_CACHE_ENABLED=false
   CANVASTACK_THEME_CACHE=false
   ```

### Theme Not Loading

If theme changes don't appear:

1. Clear theme cache:
   ```bash
   php artisan canvastack:theme:clear-cache
   ```

2. Enable hot-reload (development only):
   ```env
   CANVASTACK_THEME_HOT_RELOAD=true
   ```

### Translation Issues

If translations are missing:

1. Enable missing translation logging:
   ```env
   CANVASTACK_LOG_MISSING_TRANSLATIONS=true
   ```

2. Clear translation cache:
   ```bash
   php artisan canvastack:translate:clear-cache
   ```

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Published

