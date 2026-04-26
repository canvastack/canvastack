# Cache Management Dropdown - Complete Documentation

**بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ**

## Table of Contents
1. [Overview](#overview)
2. [Architecture & Flow](#architecture--flow)
3. [Implementation Details](#implementation-details)
4. [UI/UX Flow](#uiux-flow)
5. [Security Features](#security-features)
6. [API Reference](#api-reference)
7. [Troubleshooting](#troubleshooting)

---

## Overview

### What is Cache Management Dropdown?

Cache Management Dropdown adalah fitur developer tools yang memungkinkan root user untuk melakukan cache management Laravel langsung dari UI tanpa perlu akses terminal. Fitur ini muncul sebagai dropdown button di header setiap halaman admin.

### Key Features

- ✅ **Auto-appear**: Otomatis muncul di semua halaman untuk root user di local/development environment
- ✅ **Multiple cache types**: Clear all, config, route, view, compiled, optimize
- ✅ **Modal-based UI**: Menggunakan Bootstrap modal untuk konfirmasi dan feedback
- ✅ **AJAX-based**: Tidak reload halaman saat proses (kecuali user klik "Reload Page")
- ✅ **Security**: Environment check, role check, CSRF protection, rate limiting
- ✅ **Logging**: Semua operasi tercatat di log untuk audit trail

### Technologies Used

- **Backend**: PHP (Laravel), Canvastack Origin Framework
- **Frontend**: JavaScript (Vanilla), Bootstrap 3/4 Modal, jQuery
- **Security**: CSRF Token, Role-based Access Control
- **API**: RESTful JSON API

---

## Architecture & Flow

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    USER INTERFACE (Browser)                  │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Header Action Buttons Area                            │ │
│  │  ┌──────────────────────────────────────────────────┐  │ │
│  │  │  🔧 Cache ▼  (Dropdown Button)                   │  │ │
│  │  │  ├─ 🗑️ Clear All Cache                          │  │ │
│  │  │  ├─ ⚙️ Clear Config                             │  │ │
│  │  │  ├─ 🛣️ Clear Route                              │  │ │
│  │  │  ├─ 👁️ Clear View                               │  │ │
│  │  │  ├─ 📦 Clear Compiled                            │  │ │
│  │  │  └─ 🚀 Optimize                                  │  │ │
│  │  └──────────────────────────────────────────────────┘  │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ Click Event
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              JAVASCRIPT (Inline in HTML)                     │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  1. Show Confirmation Modal (Bootstrap)                │ │
│  │  2. On Confirm: Send POST Request via Fetch API       │ │
│  │  3. Show Loading Modal with Spinner                   │ │
│  │  4. Handle Response: Show Success/Error Modal         │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ POST /system/cache/clear/{type}
                            │ Headers: X-CSRF-TOKEN, Content-Type
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    LARAVEL ROUTING                           │
│  Route::post('clear/{type}', 'CacheManagementController')   │
│  Middleware: web, auth, throttle:5,1                        │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│          CacheManagementController::clear()                  │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  1. verifyAccess() - Check environment & role         │ │
│  │  2. Validate cache type                               │ │
│  │  3. Log operation start                               │ │
│  │  4. executeCacheClear() - Run Artisan commands        │ │
│  │  5. Log operation complete                            │ │
│  │  6. Return JSON response                              │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                  LARAVEL ARTISAN                             │
│  - cache:clear                                              │
│  - config:clear / config:cache                              │
│  - route:clear / route:cache                                │
│  - view:clear                                               │
│  - clear-compiled                                           │
└─────────────────────────────────────────────────────────────┘
```

### Component Interaction Flow

```
Controller.php (setupDeveloperTools)
    │
    ├─> Check: Environment (local/development)
    ├─> Check: User Role (root)
    ├─> Check: Route exists (system.cache.clear)
    │
    └─> addCustomDropdownButton()
            │
            └─> RouteInfo.php (addCustomDropdownButton)
                    │
                    ├─> Validate: color, label, items
                    ├─> Validate: URLs (allow #)
                    ├─> Escape: labels for XSS protection
                    │
                    └─> Store in $this->customDropdownButtons[]
                            │
                            └─> RouteInfo.php (routeInfo)
                                    │
                                    └─> Merge with $action_page['action_page']
                                            │
                                            └─> View.php (render)
                                                    │
                                                    └─> canvastack_action_buttons()
                                                            │
                                                            └─> Detect: is_array($value)
                                                                    │
                                                                    └─> canvastack_dropdown_button_box()
                                                                            │
                                                                            ├─> Generate: HTML dropdown
                                                                            ├─> Generate: Bootstrap modal
                                                                            └─> Generate: Inline JavaScript
```

---

## Implementation Details

### 1. Backend Components

#### A. Controller Setup (`Controller.php`)

**Location**: `vendor/canvastack/canvastack/src/Controllers/Core/Controller.php`

**Method**: `setupDeveloperTools()`

```php
protected function setupDeveloperTools(): void {
    // 1. Environment Check
    if (!in_array(app()->environment(), ['local', 'development'])) {
        return; // Exit if not dev environment
    }
    
    // 2. User Role Check
    if (!isset($this->session['user_group']) || $this->session['user_group'] !== 'root') {
        return; // Exit if not root user
    }
    
    // 3. Route Existence Check
    if (!\Illuminate\Support\Facades\Route::has('system.cache.clear')) {
        return; // Exit if route not registered
    }
    
    // 4. Add Dropdown Button
    $this->addCustomDropdownButton('secondary', '🔧 Cache', [
        ['label' => '🗑️ Clear All Cache', 'url' => '#', 'icon' => 'fa fa-trash', 
         'data' => ['cache-type' => 'all', 'url' => route('system.cache.clear', ['type' => 'all'])]],
        ['divider' => true],
        ['label' => '⚙️ Clear Config', 'url' => '#', 'icon' => 'fa fa-cog',
         'data' => ['cache-type' => 'config', 'url' => route('system.cache.clear', ['type' => 'config'])]],
        // ... more items
    ]);
}
```

**When Called**: 
- Called at the beginning of `routeInfo()` method in `RouteInfo` trait
- Executed on EVERY page load for authenticated users
- Runs BEFORE route info object is built

#### B. Route Info Management (`RouteInfo.php`)

**Location**: `vendor/canvastack/canvastack/src/Controllers/Core/Craft/Includes/RouteInfo.php`

**Key Methods**:

1. **`addCustomDropdownButton()`** - Add single dropdown
2. **`addCustomDropdownButtons()`** - Add multiple dropdowns
3. **`clearCustomDropdownButtons()`** - Remove all dropdowns
4. **`getCustomDropdownButtons()`** - Get all dropdowns
5. **`validateGeneratedUrl()`** - Validate URLs (allows `#`)

**Flow in `routeInfo()`**:

```php
public function routeInfo(): void {
    // 1. Setup developer tools FIRST
    if (method_exists($this, 'setupDeveloperTools')) {
        $this->setupDeveloperTools();
    }
    
    // 2. Build route info
    // ... (privilege checks, action buttons, etc.)
    
    // 3. Merge custom dropdown buttons
    if (!empty($this->customDropdownButtons)) {
        $action_page['action_page'] = array_merge(
            $action_page['action_page'], 
            $this->customDropdownButtons
        );
    }
    
    // 4. Create route info object
    $routeInfoObject = (object) array_merge($routeInfo, $action_page);
    
    // 5. Cache and set data
    $this->setDataValues('route_info', $routeInfoObject);
}
```

#### C. View Rendering (`View.php`)

**Location**: `vendor/canvastack/canvastack/src/Controllers/Core/Craft/View.php`

**Method**: `escapeActionButtons()`

```php
private function escapeActionButtons($routeInfo) {
    foreach ($routeInfo->action_page as $label => $url) {
        // Check if this is a dropdown button (array value)
        if (is_array($url)) {
            // Keep dropdown array as-is (will be escaped in canvastack_dropdown_button_box)
            $escapedActions[$escapedLabel] = $url;
        } else {
            // Escape URL for regular buttons
            $escapedActions[$escapedLabel] = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        }
    }
}
```

#### D. HTML Generation (`App.php`)

**Location**: `vendor/canvastack/canvastack/src/Library/Helpers/App.php`

**Functions**:

1. **`canvastack_action_buttons()`** - Main button renderer
   - Detects dropdown buttons via `is_array($value)`
   - Routes to `canvastack_dropdown_button_box()` for dropdowns

2. **`canvastack_dropdown_button_box()`** - Dropdown renderer
   - Generates Bootstrap dropdown HTML
   - Generates Bootstrap modal HTML
   - Generates inline JavaScript for AJAX

**HTML Structure Generated**:

```html
<!-- Dropdown Button -->
<h3 class="panel-title header-list-panel">
    <div class="btn-group">
        <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
            🔧 Cache <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" role="menu">
            <li><a href="#" class="cache-action" data-cache-type="all" data-url="...">
                <i class="fa fa-trash"></i> 🗑️ Clear All Cache
            </a></li>
            <!-- More items -->
        </ul>
    </div>
</h3>

<!-- Bootstrap Modal -->
<div class="modal fade" id="cacheModal-xxx">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">...</div>
            <div class="modal-body">...</div>
            <div class="modal-footer">...</div>
        </div>
    </div>
</div>

<!-- Inline JavaScript -->
<script>
    // Event handlers, AJAX calls, modal management
</script>
```

#### E. Cache Management Controller

**Location**: `vendor/canvastack/canvastack/src/Controllers/Admin/System/CacheManagementController.php`

**Methods**:

1. **`__construct()`** - Initialize controller (no auth check here)
2. **`verifyAccess()`** - Check environment and role
3. **`clear()`** - Main endpoint for cache clearing
4. **`executeCacheClear()`** - Execute Artisan commands
5. **`status()`** - Get cache status (optional)

**Cache Types Supported**:

| Type | Artisan Commands | Description |
|------|-----------------|-------------|
| `all` | `cache:clear`, `config:clear`, `route:clear`, `view:clear`, `clear-compiled` | Clear all caches |
| `config` | `config:clear` | Clear configuration cache |
| `route` | `route:clear` | Clear route cache |
| `view` | `view:clear` | Clear compiled view cache |
| `compiled` | `clear-compiled` | Clear compiled class cache |
| `app` | `cache:clear` | Clear application cache only |
| `optimize` | `config:cache`, `route:cache` | Cache config and routes for optimization |

#### F. Routes

**Location**: `routes/web.php`

```php
Route::group(['prefix' => 'cache'], function() {
    Route::post('clear/{type}', 'CacheManagementController@clear')
        ->name('system.cache.clear')
        ->middleware('throttle:5,1'); // Rate limit: 5 requests per minute
    
    Route::get('status', 'CacheManagementController@status')
        ->name('system.cache.status');
});
```

**Middleware Stack**:
- `web` - Session, CSRF, cookies
- `auth` - Authentication required
- `throttle:5,1` - Rate limiting (5 requests per minute)

---

## UI/UX Flow

### User Journey

```
┌─────────────────────────────────────────────────────────────┐
│ STEP 1: Page Load                                           │
│                                                              │
│ User navigates to any admin page                            │
│ ↓                                                            │
│ System checks:                                               │
│   ✓ Environment = local/development?                        │
│   ✓ User role = root?                                       │
│   ✓ Route exists?                                           │
│ ↓                                                            │
│ If all checks pass: Dropdown button appears in header       │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 2: User Interaction                                    │
│                                                              │
│ User clicks "🔧 Cache" button                               │
│ ↓                                                            │
│ Dropdown menu opens showing cache options                   │
│ ↓                                                            │
│ User clicks a cache option (e.g., "Clear Config")          │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 3: Confirmation Modal                                  │
│                                                              │
│ Bootstrap modal appears:                                     │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Confirm Cache Clear                              [×]    │ │
│ │                                                          │ │
│ │ Are you sure you want to clear config cache?            │ │
│ │                                                          │ │
│ │                          [Cancel]  [Clear Cache]        │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                              │
│ User has 2 options:                                         │
│   • Cancel → Modal closes, no action                        │
│   • Clear Cache → Proceed to Step 4                        │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 4: Processing                                          │
│                                                              │
│ Modal content changes to loading state:                     │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Processing                                       [×]    │ │
│ │                                                          │ │
│ │              ⟳ (spinning icon)                          │ │
│ │         Clearing config cache...                        │ │
│ │                                                          │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                              │
│ Background: AJAX POST request sent to server               │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│ STEP 5: Result Display                                      │
│                                                              │
│ SUCCESS CASE:                                               │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Success                                          [×]    │ │
│ │                                                          │ │
│ │ ✓ Configuration cache cleared successfully              │ │
│ │                                                          │ │
│ │                    [Reload Page]  [Close]               │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                              │
│ ERROR CASE:                                                 │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Error                                            [×]    │ │
│ │                                                          │ │
│ │ ⚠ Cache clear failed: [error message]                   │ │
│ │                                                          │ │
│ │                                        [Close]          │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Modal States

#### 1. Confirmation Modal
- **Title**: "Confirm Cache Clear"
- **Body**: "Are you sure you want to clear {type} cache?"
- **Footer**: [Cancel] [Clear Cache]
- **Behavior**: Only shown for non-optimize operations

#### 2. Loading Modal
- **Title**: "Processing"
- **Body**: Spinner icon + "Clearing {type} cache..."
- **Footer**: Empty (no buttons)
- **Behavior**: Cannot be dismissed during processing

#### 3. Success Modal
- **Title**: "Success"
- **Body**: Green alert with checkmark + success message
- **Footer**: [Reload Page] [Close]
- **Behavior**: 
  - "Reload Page" → `location.reload()`
  - "Close" → Dismiss modal

#### 4. Error Modal
- **Title**: "Error"
- **Body**: Red alert with warning icon + error message
- **Footer**: [Close]
- **Behavior**: Dismiss modal, no reload

---

## Security Features

### 1. Environment Restriction

```php
// Only works in local/development
if (!in_array(app()->environment(), ['local', 'development'])) {
    abort(403);
}
```

**Why**: Prevents accidental cache clearing in production

### 2. Role-Based Access Control

```php
// Only root users can access
$userGroup = $this->session['user_group'] ?? session('user_group');
if (!$userGroup || $userGroup !== 'root') {
    abort(403);
}
```

**Why**: Restricts powerful cache operations to administrators

### 3. CSRF Protection

```javascript
// CSRF token sent with every request
headers: {
    'X-CSRF-TOKEN': token.content,
    'X-Requested-With': 'XMLHttpRequest'
}
```

**Why**: Prevents Cross-Site Request Forgery attacks

### 4. Rate Limiting

```php
->middleware('throttle:5,1'); // 5 requests per minute
```

**Why**: Prevents abuse and DoS attacks

### 5. XSS Protection

```php
// All user input escaped
$escapedLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
```

**Why**: Prevents Cross-Site Scripting attacks

### 6. URL Validation

```php
// Validates all URLs before use
if (!$this->validateGeneratedUrl($item['url'])) {
    throw RouteException::invalidUrl();
}
```

**Why**: Prevents malicious URL injection

### 7. Audit Logging

```php
Log::info('Cache clear initiated', [
    'type' => $type,
    'user_id' => $this->session['id'],
    'ip_address' => $request->ip(),
]);
```

**Why**: Creates audit trail for security monitoring

---

## API Reference

### Dropdown Button Methods

#### `addCustomDropdownButton()`

Add a single dropdown button to the action button area.

**Signature**:
```php
public function addCustomDropdownButton(
    string $color,      // Button color: primary, success, info, warning, danger, secondary
    string $label,      // Button label (will be escaped)
    array $items,       // Dropdown items
    bool $enabled = true // Whether button is enabled
): void
```

**Item Structure**:
```php
[
    'label' => 'Item Label',           // Required: Item text
    'url' => '#',                      // Required: Item URL (# for JS-handled)
    'icon' => 'fa fa-icon',           // Optional: Font Awesome icon
    'data' => [                        // Optional: Data attributes
        'cache-type' => 'all',
        'url' => 'https://...'
    ]
]

// OR for divider:
['divider' => true]
```

**Example**:
```php
$this->addCustomDropdownButton('primary', 'Actions', [
    ['label' => 'Edit', 'url' => route('edit'), 'icon' => 'fa fa-edit'],
    ['divider' => true],
    ['label' => 'Delete', 'url' => '#', 'icon' => 'fa fa-trash', 
     'data' => ['action' => 'delete', 'id' => 123]],
]);
```

#### `addCustomDropdownButtons()`

Add multiple dropdown buttons at once.

**Signature**:
```php
public function addCustomDropdownButtons(array $dropdowns): void
```

**Example**:
```php
$this->addCustomDropdownButtons([
    [
        'color' => 'primary',
        'label' => 'Actions',
        'items' => [/* items */],
        'enabled' => true
    ],
    [
        'color' => 'secondary',
        'label' => 'Tools',
        'items' => [/* items */]
    ]
]);
```

#### `clearCustomDropdownButtons()`

Remove all custom dropdown buttons.

**Signature**:
```php
public function clearCustomDropdownButtons(): void
```

**Example**:
```php
// In controller constructor to disable developer tools
public function __construct() {
    parent::__construct();
    $this->clearCustomDropdownButtons();
}
```

#### `getCustomDropdownButtons()`

Get all custom dropdown buttons.

**Signature**:
```php
public function getCustomDropdownButtons(): array
```

**Returns**:
```php
[
    'secondary|🔧 Cache|dropdown' => [
        ['label' => '...', 'url' => '...', 'icon' => '...', 'data' => [...]],
        // ...
    ]
]
```

### Cache Management API

#### POST `/system/cache/clear/{type}`

Clear specified cache type.

**Parameters**:
- `{type}` (path): Cache type (all, config, route, view, compiled, app, optimize)

**Headers**:
- `X-CSRF-TOKEN`: CSRF token
- `Content-Type`: application/json
- `X-Requested-With`: XMLHttpRequest

**Request Body**:
```json
{
    "type": "config"
}
```

**Response (Success)**:
```json
{
    "success": true,
    "message": "Configuration cache cleared successfully",
    "type": "config",
    "timestamp": "2026-04-05 10:30:15"
}
```

**Response (Error)**:
```json
{
    "success": false,
    "message": "Cache clear failed: [error details]",
    "type": "config"
}
```

**Status Codes**:
- `200` - Success
- `400` - Invalid cache type
- `403` - Forbidden (not root or wrong environment)
- `429` - Too many requests (rate limit exceeded)
- `500` - Server error

---

## Troubleshooting

### Issue: Dropdown button not appearing

**Possible Causes**:
1. Environment is not local/development
2. User is not root
3. Route `system.cache.clear` not registered
4. Cache is stale

**Solutions**:
```bash
# Check environment
php artisan env

# Check user role in session
# Look for: session('user_group') === 'root'

# Clear cache
php artisan cache:clear

# Check routes
php artisan route:list | grep cache
```

### Issue: 403 Forbidden error

**Possible Causes**:
1. Session not available in constructor
2. User role check failing
3. Environment check failing

**Solutions**:
- Moved authorization check from constructor to `verifyAccess()` method
- Check both `$this->session['user_group']` and `session('user_group')`
- Add debug logging to see which check is failing

### Issue: Modal not showing

**Possible Causes**:
1. Bootstrap/jQuery not loaded
2. JavaScript error
3. Modal ID conflict

**Solutions**:
```javascript
// Check console for errors
console.log('Bootstrap:', typeof $.fn.modal);
console.log('jQuery:', typeof $);

// Check modal ID is unique
// Each dropdown generates unique ID: cacheModal-{uniqid}
```

### Issue: CSRF token not found

**Possible Causes**:
1. Meta tag missing in layout
2. Token expired

**Solutions**:
```html
<!-- Ensure this exists in layout head -->
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### Issue: Rate limit exceeded

**Cause**: More than 5 requests per minute

**Solution**: Wait 1 minute or adjust rate limit in routes:
```php
->middleware('throttle:10,1'); // 10 requests per minute
```

---

## Files Modified/Created

### Created Files
1. `vendor/canvastack/canvastack/src/Controllers/Admin/System/CacheManagementController.php`
2. `.docs/FEATURE_CACHE_MANAGEMENT_DROPDOWN.md`
3. `.docs/FIX_CACHE_DROPDOWN_TIMING_ISSUE.md`
4. `.docs/CACHE_MANAGEMENT_DROPDOWN_COMPLETE_DOCUMENTATION.md` (this file)

### Modified Files
1. `vendor/canvastack/canvastack/src/Controllers/Core/Controller.php`
   - Added `setupDeveloperTools()` method

2. `vendor/canvastack/canvastack/src/Controllers/Core/Craft/Includes/RouteInfo.php`
   - Added `addCustomDropdownButton()` method
   - Added `addCustomDropdownButtons()` method
   - Added `clearCustomDropdownButtons()` method
   - Added `getCustomDropdownButtons()` method
   - Modified `validateGeneratedUrl()` to allow `#`
   - Modified `routeInfo()` to call `setupDeveloperTools()` first

3. `vendor/canvastack/canvastack/src/Controllers/Core/Craft/View.php`
   - Modified `escapeActionButtons()` to handle array values (dropdowns)

4. `vendor/canvastack/canvastack/src/Library/Helpers/App.php`
   - Modified `canvastack_action_buttons()` to detect dropdown buttons
   - Added `canvastack_dropdown_button_box()` function

5. `routes/web.php`
   - Added cache management routes

---

## Summary

Cache Management Dropdown adalah implementasi lengkap dari developer tools yang:

1. **Automatically appears** untuk root users di development environment
2. **Uses existing infrastructure** (action buttons system) dengan extension untuk dropdown
3. **Provides clean UI/UX** dengan Bootstrap modal untuk semua interaksi
4. **Implements security best practices** (RBAC, CSRF, rate limiting, logging)
5. **Fully integrated** dengan Canvastack Origin framework

Sistem ini menggunakan `addCustomDropdownButton()` method yang merupakan extension dari sistem action button yang sudah ada, memungkinkan developer untuk menambahkan dropdown button dengan mudah tanpa perlu modifikasi template atau view files.

---

**Documentation Version**: 1.0.0  
**Last Updated**: 2026-04-05  
**Author**: Canvastack  
**Framework**: Canvastack Origin 2.0
