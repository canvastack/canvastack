# Bootstrap 4 Template Creation Guide (Default Template)

**Version:** 2.0.0
**Last Updated:** April 28, 2026
**Template:** `default`
**Framework:** Bootstrap 4.6.x

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

---

## Section 1: Overview

### Purpose

This guide walks you through creating a complete, production-ready admin template using **Bootstrap 4** with the CanvaStack Theme Engine. You will build the `default` template — the baseline template that all other templates are compared against for backward compatibility.

By the end of this guide you will have:
- A fully functional Blade layout with all required blocks
- Correct asset loading configuration
- DefaultAdapter integration for all form and table components
- A working dashboard and login page

### Prerequisites

Before starting, ensure you have:
- CanvaStack package installed (`composer require canvastack/canvastack`)
- Configuration published (`php artisan vendor:publish --provider="Canvastack\Canvastack\CanvastackServiceProvider"`)
- `DefaultAdapter` available at `vendor/canvastack/canvastack/src/Library/Theme/Adapters/DefaultAdapter.php`
- Laravel 9.x or higher
- PHP 8.0 or higher
- Basic knowledge of Laravel Blade templating

### What You Will Build

```
resources/views/default/
└── template/
    └── admin/
        ├── index.blade.php          ← Main layout
        └── block/
            ├── meta.blade.php       ← <head> section (CSS, JS, meta tags)
            ├── header.blade.php     ← Top navigation bar
            ├── sidebar.blade.php    ← Left sidebar with menu
            ├── footer.blade.php     ← Footer bar
            ├── offside.blade.php    ← Right slide-out panel
            └── downscripts.blade.php ← Bottom JavaScript loading

public/assets/templates/default/
├── css/
│   └── app.css                      ← Custom styles
├── js/
│   └── app.js                       ← Custom scripts
├── fonts/                           ← Custom fonts
└── images/                          ← Template images
```

### Estimated Time

- Directory setup and asset organization: 30 minutes
- Blade component implementation: 2–3 hours
- Configuration and testing: 1 hour
- **Total: 3.5–4.5 hours**

---

## Section 2: Understanding Default Template Architecture

### Directory Structure

The default template follows a strict two-root structure:

**Blade views** live under `resources/views/default/`:
```
resources/views/default/
├── emails/
│   └── default.blade.php            ← Email layout
├── pages/
│   └── admin/                       ← Page-level views (yield content here)
│       ├── dashboard.blade.php
│       └── ...
└── template/
    └── admin/
        ├── index.blade.php          ← Master layout
        └── block/                   ← Reusable layout blocks
            ├── meta.blade.php
            ├── header.blade.php
            ├── sidebar.blade.php
            ├── footer.blade.php
            ├── offside.blade.php
            └── downscripts.blade.php
```

**Static assets** live under `public/assets/templates/default/`:
```
public/assets/templates/default/
├── css/
│   ├── app.css                      ← Main custom stylesheet
│   └── ...
├── js/
│   ├── app.js                       ← Main custom script
│   ├── canvastack-modal-adapter.js  ← Framework-agnostic modal API
│   ├── canvastack-tooltip-adapter.js ← Framework-agnostic tooltip API
│   ├── scripts.js                   ← General UI scripts
│   ├── sidebar.js                   ← Sidebar toggle logic
│   └── datatables/
│       └── filter.js                ← DataTables filter modal logic
├── fonts/                           ← Custom web fonts
├── images/                          ← Template images (bg, avatars, logos)
└── vendor/                          ← Third-party vendor assets
```

### Blade Component Hierarchy

The rendering pipeline flows from outermost to innermost:

```
index.blade.php  (master layout — wraps everything)
├── @include block/meta.blade.php        → renders <head> content
├── @include block/sidebar.blade.php     → renders left sidebar
├── @include block/header.blade.php      → renders top nav bar
│   └── {!! $breadcrumbs !!}             → breadcrumb from controller
├── @yield('content')                    → page-specific content
├── @include block/footer.blade.php      → renders footer bar
├── @include block/offside.blade.php     → renders right slide-out panel
└── @include block/downscripts.blade.php → renders bottom JS tags
```

Page views (e.g., `pages/admin/dashboard.blade.php`) extend the master layout:

```blade
@extends('default.pages.admin')

@section('content')
    {{-- Your page content here --}}
@endsection
```

The `default.pages.admin` view is resolved by `View.php` via `canvastack_current_template()`.

### Asset Organization

Assets are split by purpose:

| Directory | Contents | Loaded via |
|-----------|----------|------------|
| `css/` | Custom stylesheets, theme overrides | `meta.blade.php` |
| `js/` | Custom scripts, adapters, plugins | `downscripts.blade.php` |
| `fonts/` | Web fonts (woff, woff2, ttf) | CSS `@font-face` |
| `images/` | Backgrounds, avatars, logos | Blade `asset()` helper |
| `vendor/` | Third-party assets (icons, etc.) | CSS/JS includes |

CDN-hosted libraries (Bootstrap 4, jQuery, DataTables) are configured in `config/canvastack.templates.php` and injected by the `Template` component — they do **not** live in `public/assets/`.

### DefaultAdapter Integration Points

The `DefaultAdapter` is called automatically by every CanvaStack helper function. You do not call it directly in Blade views. The integration points are:

| Helper Function | DefaultAdapter Method | Used In |
|---|---|---|
| `canvastack_form_create_header_tab()` | `renderTabHeader()` | Form tab navigation |
| `canvastack_form_create_content_tab()` | `renderTabContent()` | Form tab content panes |
| `canvastack_form_alert_message()` | `renderAlertMessage()` | Flash messages |
| `canvastack_form_checkList()` | `renderCheckList()` | Checkbox inputs |
| `canvastack_form_selectbox()` | `renderSelectBox()` | Select dropdowns |
| `canvastack_modal_content_html()` | `renderFilterModal()` | Table filter modals |
| `canvastack_table_action_button()` | `renderActionButtons()` | Table row actions |
| `canvastack_breadcrumb()` | `renderBreadcrumb()` | Page breadcrumbs |
| `canvastack_gird()` | `getContainerClass()` / `getRowClass()` | Grid layout |

All of these produce **Bootstrap 4 HTML** when the active template is `default`.

### Architecture Diagram

```
Request
   │
   ▼
Controller
   │  sets $components, $breadcrumbs, $menu_sidebar, etc.
   ▼
index.blade.php  ──────────────────────────────────────────────────────┐
   │                                                                   │
   ├─ @include meta.blade.php                                          │
   │      reads $components->template->scripts                         │
   │      outputs <link> and <script> tags for CSS/JS                  │
   │                                                                   │
   ├─ @include sidebar.blade.php                                       │
   │      reads $logo, $appName, $menu_sidebar, $sidebar_content       │
   │      outputs Bootstrap 4 sidebar markup                           │
   │                                                                   │
   ├─ @include header.blade.php                                        │
   │      reads $breadcrumbs (from canvastack_breadcrumb())            │
   │      outputs Bootstrap 4 top nav with data-toggle dropdowns       │
   │                                                                   │
   ├─ @yield('content')  ◄── page view extends default.pages.admin     │
   │                                                                   │
   ├─ @include footer.blade.php                                        │
   │      reads $components->meta->preference                          │
   │      outputs Bootstrap 4 footer with pull-right                   │
   │                                                                   │
   ├─ @include offside.blade.php                                       │
   │      static Bootstrap 4 slide-out panel                           │
   │      uses data-toggle="tab" for panel tabs                        │
   │                                                                   │
   └─ @include downscripts.blade.php                                   │
          reads $components->template->scripts['js']                   │
          outputs <script> tags for bottom JS                          │
                                                                       │
ThemeAdapterResolver::resolve()  ──────────────────────────────────────┘
   │  canvastack_current_template() returns 'default'
   ▼
DefaultAdapter
   │  all helper functions delegate here
   │  produces Bootstrap 4 HTML byte-for-byte identical to pre-ThemeEngine
   ▼
HTML Output
```

---

## Section 3: Step-by-Step Implementation Guide

### Step 1: Create Directory Structure

```bash
# Blade views
mkdir -p resources/views/default/template/admin/block
mkdir -p resources/views/default/pages/admin
mkdir -p resources/views/default/emails

# Public assets
mkdir -p public/assets/templates/default/css
mkdir -p public/assets/templates/default/js/datatables
mkdir -p public/assets/templates/default/fonts
mkdir -p public/assets/templates/default/images
mkdir -p public/assets/templates/default/vendor
```

### Step 2: Copy and Organize Assets

Copy your Bootstrap 4 theme assets into the public directory:

```bash
# Copy your design's CSS files
cp path/to/design/css/*.css public/assets/templates/default/css/

# Copy your design's JS files
cp path/to/design/js/*.js public/assets/templates/default/js/

# Copy fonts and images
cp -r path/to/design/fonts/ public/assets/templates/default/fonts/
cp -r path/to/design/images/ public/assets/templates/default/images/
```

**Naming convention for custom files:**
- Main stylesheet: `app.css`
- Main script: `app.js`
- Initialization script: `scripts.js`
- Sidebar script: `sidebar.js`

CDN libraries (Bootstrap 4, jQuery, DataTables) are **not** copied here — they are loaded via config.

### Step 3: Create `index.blade.php` (Master Layout)

Create `resources/views/default/template/admin/index.blade.php`:

```blade
<!DOCTYPE html>
<html class="no-js" lang="en">
    <head>
        {{-- Meta tags, CSS, and top JS are loaded here --}}
        @include('default.template.admin.block.meta')
    </head>

    <body class="page-sound background-content">
        {{-- Optional: background image --}}
        <img class="background-img"
             src="{{ asset('assets/templates/default/images/bg/bg-content-001.jpg') }}" />

        {{-- Page loader --}}
        <div id="preloader"><div class="loader"></div></div>

        @if (Auth::check())
        {{-- Main page container — only shown to authenticated users --}}
        <div class="page-container">

            {{-- Left sidebar with navigation menu --}}
            @include('default.template.admin.block.sidebar')

            <div class="main-content">
                {{-- Top navigation bar with breadcrumbs --}}
                @include('default.template.admin.block.header')

                {{-- Main content area --}}
                <div class="main-content-inner animated fadeInx">
                    <div class="content-box">

                        @if (!empty($route_info))
                        {{-- Action buttons (Add New, Export, etc.) --}}
                        {!! canvastack_action_buttons($route_info) !!}
                        @endif

                        <div class="body">
        @endif

                            {{-- Page-specific content is yielded here --}}
                            @yield('content')

        @if (Auth::check())
                        </div>
                    </div>
                </div>
                {{-- End main content area --}}

            </div>

            {{-- Footer bar --}}
            @include('default.template.admin.block.footer')

        </div>

        {{-- Back to top button --}}
        <div id="back-top" class="circle show animated pulse">
            <i class="fa fa-angle-up"></i>
        </div>

        {{-- Right slide-out panel --}}
        @include('default.template.admin.block.offside')
        @endif

        {{-- Bottom JavaScript loading --}}
        @include('default.template.admin.block.downscripts')
    </body>
</html>
```

### Step 4: Create `meta.blade.php` (Head Section)

Create `resources/views/default/template/admin/block/meta.blade.php`:

```blade
<?php
/**
 * meta.blade.php — <head> section
 *
 * Renders:
 * - CSRF token meta tag
 * - App debug flag for JavaScript
 * - All meta tags from $components->meta
 * - CSS files from template config (top + bottom_first + bottom_last positions)
 * - JavaScript files from template config (top position only)
 *
 * Asset loading order:
 *   1. top.css     → framework CSS (Bootstrap 4, plugins)
 *   2. bottom_first.css → custom CSS
 *   3. bottom_last.css  → override CSS
 *   4. top.js      → framework JS that must load in <head>
 */

// Collect meta HTML from the meta component
$meta = $components->meta->content['html'];

// Collect CSS from all positions
$styles = [];
$styles['top']          = $components->template->scripts['css']['top']          ?? [];
$styles['bottom_first'] = $components->template->scripts['css']['bottom_first'] ?? [];
$styles['bottom_last']  = $components->template->scripts['css']['bottom_last']  ?? [];

// Collect top-position JS (loaded in <head>)
$scripts = $components->template->scripts['js']['top'] ?? [];
?>

<link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />

{{-- CSRF token for AJAX requests --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Expose debug flag to JavaScript --}}
<script>window.APP_DEBUG = {{ config('app.debug') ? 'true' : 'false' }};</script>

{{-- Dynamic meta tags (title, description, og:*, etc.) --}}
@foreach ($meta as $metaTag)
    {!! $metaTag !!}
@endforeach

{{-- Framework CSS (Bootstrap 4, Chosen.js, etc.) from top position --}}
@foreach ($styles['top'] as $style)
    {!! $style->html !!}
@endforeach

{{-- Custom CSS from bottom_first position --}}
@foreach ($styles['bottom_first'] as $style)
    {!! $style->html !!}
@endforeach

{{-- Override CSS from bottom_last position --}}
@foreach ($styles['bottom_last'] as $style)
    {!! $style->html !!}
@endforeach

{{-- Top-position JS (jQuery, Bootstrap 4 bundle) --}}
@foreach ($scripts as $script)
    {!! $script->html !!}
@endforeach
```

### Step 5: Create `header.blade.php` (Top Navigation)

Create `resources/views/default/template/admin/block/header.blade.php`:

```blade
<?php
/**
 * header.blade.php — Top navigation bar
 *
 * Bootstrap 4 specific:
 * - Uses data-toggle="dropdown" (not data-bs-toggle)
 * - Uses pull-right for right-aligned elements
 * - Uses pull-left for left-aligned elements
 */
$baseUrl      = canvastack_config('baseURL');
$baseTemplate = canvastack_config('base_template');
$template     = canvastack_config('template');
$assetURL     = "{$baseUrl}/{$baseTemplate}/{$template}";
?>

{{-- HEADER BLOCK --}}
<div class="shadow">
    <div class="header-area blury blury-blue">
        <div class="row align-items-center">

            {{-- Left: hamburger menu + search --}}
            <div class="col-md-6 col-sm-8 clearfix">
                <div class="nav-btn pull-left">
                    <span></span><span></span><span></span>
                </div>
                <div class="search-box CanvaStack-search-box pull-left">
                    <div class="search-inputbox">
                        <form action="#">
                            <input id="search-input" type="text" name="search"
                                   placeholder="Search..." required />
                            <i class="ti-search"></i>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Right: notifications + profile --}}
            <div class="col-md-6 col-sm-4 clearfix">
                <ul class="notification-area pull-right">
                    <li id="full-view"><i class="ti-fullscreen"></i></li>
                    <li id="full-view-exit"><i class="ti-zoom-out"></i></li>

                    {{-- Notification bell — Bootstrap 4 dropdown --}}
                    <li class="dropdown">
                        {{-- data-toggle="dropdown" is Bootstrap 4 syntax --}}
                        <i class="ti-bell dropdown-toggle" data-toggle="dropdown">
                            <span>2</span>
                        </i>
                        <div class="dropdown-menu bell-notify-box notify-box">
                            <span class="notify-title">Notifications</span>
                        </div>
                    </li>

                    {{-- Messages dropdown --}}
                    <li class="dropdown">
                        <i class="fa fa-envelope-o dropdown-toggle" data-toggle="dropdown">
                            <span>3</span>
                        </i>
                        <div class="dropdown-menu notify-box nt-enveloper-box">
                            <span class="notify-title">Messages</span>
                        </div>
                    </li>

                    <li class="settings-btn"><i class="ti-settings"></i></li>
                </ul>
            </div>

        </div>

        {{-- Breadcrumb — rendered by canvastack_breadcrumb() via DefaultAdapter --}}
        {!! $breadcrumbs !!}
    </div>
</div>
{{-- END HEADER BLOCK --}}
```

### Step 6: Create `sidebar.blade.php` (Left Sidebar)

Create `resources/views/default/template/admin/block/sidebar.blade.php`:

```blade
<?php
/**
 * sidebar.blade.php — Left navigation sidebar
 *
 * Variables provided by controller:
 * - $logo              : path to logo image
 * - $appName           : application name string
 * - $sidebar_content   : HTML from canvastack_sidebar_content() via DefaultAdapter
 * - $menu_sidebar      : array of HTML strings for nav menu items
 * - $components->meta->content['text']['app_name'] : app name from meta component
 */
$fileExists = file_exists(public_path() . $logo);
?>

<div class="sidebar-menu">
    <div class="sidebar-header">
        <div class="logo">
            @if ($fileExists)
                {{-- Logo image exists locally --}}
                <a href="{{ URL::to('admin') }}"
                   class="lights font-congenial-black color-transparent">
                    <img alt="{{ $appName }}" />
                </a>
            @else
                {{-- Logo from URL or fallback to app name text --}}
                <a href="{{ URL::to('admin') }}"
                   class="lights font-congenial-black color-transparent">
                    <img src="{{ $logo }}"
                         alt="{{ $components->meta->content['text']['app_name'] }}" />
                    <span>{{ $components->meta->content['text']['app_name'] }}</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Sidebar content (user profile widget, etc.) from DefaultAdapter --}}
    @if ($sidebar_content)
        {!! $sidebar_content !!}
    @endif

    {{-- Navigation menu items — each item is pre-rendered HTML --}}
    <nav class="menu-inner">
        @foreach ($menu_sidebar as $menu)
            {!! $menu !!}
        @endforeach
    </nav>
</div>
```

### Step 7: Create `footer.blade.php`

Create `resources/views/default/template/admin/block/footer.blade.php`:

```blade
<?php
/**
 * footer.blade.php — Bottom footer bar
 *
 * Bootstrap 4 specific:
 * - Uses pull-right for right-aligned copyright text
 *
 * Variables from $components->meta->preference:
 * - meta_author    : author/company name
 * - email_address  : contact email
 */
$copyrights    = $components->meta->preference;
$author        = $copyrights['meta_author']   ?? canvastack_config('meta_author');
$copyright     = $copyrights['meta_author']   ?? canvastack_config('copyrights');
$email_address = $copyrights['email_address'] ?? canvastack_config('email');
?>

{{-- FOOTER --}}
<footer>
    <div class="footer-area blury">
        {{-- pull-right is Bootstrap 4 — use float-end in Bootstrap 5 --}}
        <span class="pull-right">
            <span id="copyright"></span>&nbsp;
            <font title="{{ $author }} <{{ $email_address }}>">&copy;</font>&nbsp;
            <a href="mailto:{{ $email_address }}" target="_blank">{{ $copyright }}</a>,
            {{ canvastack_config('location') }} {{ canvastack_config('location_abbr') }}
        </span>
    </div>
</footer>
{{-- END FOOTER --}}
```

### Step 8: Create `offside.blade.php` (Right Slide-Out Panel)

Create `resources/views/default/template/admin/block/offside.blade.php`:

```blade
{{--
    offside.blade.php — Right slide-out panel

    Bootstrap 4 specific:
    - Uses data-toggle="tab" for panel tab switching (not data-bs-toggle)
    - Uses "fade in show active" classes for active tab pane
--}}

{{-- OFFSIDE PANEL --}}
<div class="offset-area">
    <div class="offset-close"><i class="ti-close"></i></div>

    {{-- Panel tab navigation — Bootstrap 4 data-toggle --}}
    <ul class="nav offset-menu-tab">
        <li>
            <a class="active" data-toggle="tab" href="#activity">Activity</a>
        </li>
        <li>
            <a data-toggle="tab" href="#settings">Settings</a>
        </li>
    </ul>

    <div class="offset-content tab-content blury">

        {{-- Activity tab pane --}}
        <div id="activity" class="tab-pane fade in show active">
            <div class="recent-activity">
                <div class="timeline-task">
                    <div class="icon bg1">
                        <i class="fa fa-envelope"></i>
                    </div>
                    <div class="tm-title">
                        <h4>Recent activity</h4>
                        <span class="time"><i class="ti-time"></i>09:35</span>
                    </div>
                    <p>Activity feed will appear here.</p>
                </div>
            </div>
        </div>

        {{-- Settings tab pane --}}
        <div id="settings" class="tab-pane fade">
            <div class="offset-settings">
                <h4>General Settings</h4>
                <div class="settings-list">
                    <div class="s-settings">
                        <div class="s-sw-title">
                            <h5>Notifications</h5>
                            <div class="s-swtich">
                                <input type="checkbox" id="switch1" />
                                <label for="switch1">Toggle</label>
                            </div>
                        </div>
                        <p>Keep it On to receive all notifications.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
{{-- END OFFSIDE PANEL --}}
```

### Step 9: Create `downscripts.blade.php` (Bottom JS Loading)

Create `resources/views/default/template/admin/block/downscripts.blade.php`:

```blade
<?php
/**
 * downscripts.blade.php — Bottom JavaScript loading
 *
 * Asset loading order:
 *   1. bottom_first.js → plugin libraries (DataTables, Chosen.js, etc.)
 *   2. bottom.js       → core app scripts
 *   3. bottom_last.js  → final scripts (adapters, custom init)
 *
 * All script tags are pre-rendered by the Template component
 * based on config/canvastack.templates.php
 */
$scripts = [];
$scripts['bottom_first'] = $components->template->scripts['js']['bottom_first'] ?? [];
$scripts['bottom']       = $components->template->scripts['js']['bottom']       ?? [];
$scripts['bottom_last']  = $components->template->scripts['js']['bottom_last']  ?? [];
?>

{{-- Plugin libraries (DataTables, Chosen.js, Flatpickr, etc.) --}}
@foreach ($scripts['bottom_first'] as $script)
    {!! $script->html !!}
@endforeach

{{-- Core app scripts --}}
@foreach ($scripts['bottom'] as $script)
    {!! $script->html !!}
@endforeach

{{-- Final scripts (modal adapter, tooltip adapter, custom init) --}}
@foreach ($scripts['bottom_last'] as $script)
    {!! $script->html !!}
@endforeach
```

### Step 10: Create Page Views

Create `resources/views/default/pages/admin/dashboard.blade.php`:

```blade
@extends('default.pages.admin')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4>Dashboard</h4>
            </div>
            <div class="card-body">
                <p>Welcome to your dashboard.</p>
            </div>
        </div>
    </div>
</div>
@endsection
```

Create `resources/views/default/pages/admin/login.blade.php` (standalone — does not extend admin layout):

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    {{-- Bootstrap 4 CSS --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="{{ asset('assets/templates/default/css/app.css') }}">
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height:100vh">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-center mb-4">Sign In</h4>
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email"
                                       class="form-control" required autofocus>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password"
                                       class="form-control" required>
                            </div>
                            <div class="form-group">
                                {{-- Bootstrap 4 custom-control for checkbox --}}
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input"
                                           id="remember" name="remember">
                                    <label class="custom-control-label" for="remember">
                                        Remember me
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                Sign In
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

### Step 11: Register Template in Config

Open `config/canvastack.php` (or the equivalent CanvaStack config) and set:

```php
'template' => 'default',
```

This tells `canvastack_current_template()` to return `'default'`, which causes `ThemeAdapterResolver` to use `DefaultAdapter`.

### Step 12: Verify DefaultAdapter Integration

No code changes are needed — `DefaultAdapter` is the built-in fallback. Verify it is working:

```bash
php artisan tinker
>>> canvastack_current_template()
=> "default"

>>> ThemeAdapterResolver::resolve()
=> Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter

>>> canvastack_form_create_header_tab('Users', 'users-tab', true, false)
=> "<li class=\"nav-item\"><a class=\"nav-link active\" data-toggle=\"tab\"..."
```

### Step 13: Configuration Population

This is the most important step. Open `config/canvastack.templates.php` and populate the `default` key.

#### Complete Configuration Example

```php
// config/canvastack.templates.php

return [
    // ── Active template ────────────────────────────────────────────────────
    // Change this to 'canvasign' or 'canvas' to switch frameworks
    'template' => 'default',

    // ── Default template (Bootstrap 4) ────────────────────────────────────
    'default' => [
        'position' => [

            // ── top.css ───────────────────────────────────────────────────
            // Loaded in <head> as <link rel="stylesheet"> tags.
            // Load framework CSS here so it is available before body renders.
            // Order matters: framework first, then plugins, then custom.
            'top' => [
                'css' => [
                    // Bootstrap 4 core CSS (CDN — version-locked)
                    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css',
                    // Font Awesome icons
                    'https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css',
                    // Chosen.js select plugin CSS
                    'https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css',
                    // Custom app stylesheet (local — relative to public/)
                    'assets/templates/default/css/app.css',
                ],

                // ── top.js ────────────────────────────────────────────────
                // Loaded in <head> as <script> tags.
                // Only put scripts here that MUST load before body (e.g., theme.js
                // to prevent flash of unstyled content). jQuery and Bootstrap
                // can go here if your template requires them in <head>.
                'js' => [
                    // jQuery (required by Bootstrap 4 and Chosen.js)
                    'https://code.jquery.com/jquery-3.6.0.min.js',
                    // Bootstrap 4 bundle (includes Popper.js)
                    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js',
                ],
            ],

            'bottom' => [
                // ── bottom.first.js ───────────────────────────────────────
                // Loaded before </body>, before app scripts.
                // Put plugin libraries here (DataTables, Chosen.js, Flatpickr).
                // These must load before your app.js which initializes them.
                'first' => [
                    'css' => [null], // No additional CSS at this position
                    'js'  => [
                        // Chosen.js select enhancement
                        'https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js',
                        // Flatpickr date picker
                        'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
                        // MetisMenu for sidebar accordion
                        'assets/templates/default/js/metisMenu.min.js',
                        // SlimScroll for sidebar scrolling
                        'assets/templates/default/js/jquery.slimscroll.min.js',
                    ],
                ],

                // ── bottom.last.js ────────────────────────────────────────
                // Loaded last before </body>, after all other scripts.
                // Put your app initialization scripts here.
                // These run after all plugins are loaded.
                'last' => [
                    'css' => [null],
                    'js'  => [
                        // Framework-agnostic modal API (Bootstrap 4/5 + Tailwind)
                        'assets/templates/default/js/canvastack-modal-adapter.js',
                        // Framework-agnostic tooltip API
                        'assets/templates/default/js/canvastack-tooltip-adapter.js',
                        // Sidebar toggle and navigation
                        'assets/templates/default/js/sidebar.js',
                        // General UI scripts (tooltips, popovers, etc.)
                        'assets/templates/default/js/scripts.js',
                        // CanvaStack-specific scripts
                        'assets/templates/default/js/canvastackscripts.js',
                    ],
                ],
            ],
        ],

        // ── Plugin-specific configurations ────────────────────────────────
        // These are loaded on-demand by specific components (e.g., DataTables
        // pages, date picker fields). They are NOT loaded on every page.

        // DataTables plugin
        'datatable' => [
            'js' => [
                'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
                // Bootstrap 4 DataTables styling integration
                'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js',
                // Responsive extension
                'https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js',
            ],
            'css' => [
                // Bootstrap 4 DataTables CSS
                'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css',
                'https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css',
            ],
        ],

        // Select enhancement plugin (Chosen.js for Bootstrap 4)
        'select' => [
            'plugin' => 'chosen', // tells the system which plugin is active
            'js'  => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js'],
            'css' => ['https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css'],
        ],

        // Date picker (Flatpickr)
        'date' => [
            'js'  => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js'],
            'css' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css'],
        ],

        // Date-time picker (Flatpickr with time enabled)
        'datetime' => [
            'js'  => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js'],
            'css' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css'],
        ],

        // Date range picker
        'daterange' => [
            'js'  => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js'],
            'css' => ['https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css'],
        ],

        // Chart library (ApexCharts)
        'chart' => [
            'js'  => ['https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.js'],
            'css' => [null],
        ],
    ],
];
```

#### Asset Path Types Explained

**CDN URLs** — full HTTPS URLs to external CDN:
```php
'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css'
```
- Resolved as-is into `<link href="...">` or `<script src="...">`
- Always use version-locked URLs (never `@latest`)
- Best for: framework libraries, popular plugins

**Local assets** — paths relative to `public/` directory:
```php
'assets/templates/default/css/app.css'
```
- Resolved via Laravel's `asset()` helper: `asset('assets/templates/default/css/app.css')`
- Results in: `https://yourapp.com/assets/templates/default/css/app.css`
- Best for: custom code, modified themes, proprietary assets

**Null** — skip this position:
```php
[null]
```
- No output generated for this position
- Use when a position has no assets to load

#### Asset Loading Order

```
<head>
  ├── top.css[0]  → Bootstrap 4 CSS
  ├── top.css[1]  → Font Awesome CSS
  ├── top.css[2]  → Chosen.js CSS
  ├── top.css[3]  → app.css (custom)
  ├── top.js[0]   → jQuery
  └── top.js[1]   → Bootstrap 4 bundle
</head>
<body>
  ... page content ...

  ├── bottom.first.js[0]  → Chosen.js
  ├── bottom.first.js[1]  → Flatpickr
  ├── bottom.first.js[2]  → MetisMenu
  ├── bottom.first.js[3]  → SlimScroll
  ├── bottom.last.js[0]   → canvastack-modal-adapter.js
  ├── bottom.last.js[1]   → canvastack-tooltip-adapter.js
  ├── bottom.last.js[2]   → sidebar.js
  ├── bottom.last.js[3]   → scripts.js
  └── bottom.last.js[4]   → canvastackscripts.js
</body>
```

#### CDN vs Local Asset Strategy

| Use CDN for | Use Local for |
|---|---|
| Bootstrap 4 CSS/JS | Custom `app.css` / `app.js` |
| jQuery | Modified theme files |
| Font Awesome | Proprietary fonts |
| DataTables | Custom plugin configurations |
| Flatpickr | Template-specific scripts |
| Popular plugins | Offline-required assets |

### Step 14: Bootstrap 4 Data Attributes

Bootstrap 4 uses `data-*` attributes **without** the `bs-` prefix. This is the key difference from Bootstrap 5.

```html
<!-- Bootstrap 4 — correct for default template -->
<button data-toggle="modal" data-target="#myModal">Open Modal</button>
<button data-dismiss="modal">Close</button>
<a data-toggle="tab" href="#tab1">Tab 1</a>
<button data-toggle="tooltip" title="Tooltip text">Hover me</button>
<button data-toggle="dropdown">Dropdown</button>

<!-- Bootstrap 5 — DO NOT use in default template -->
<button data-bs-toggle="modal" data-bs-target="#myModal">Open Modal</button>
<button data-bs-dismiss="modal">Close</button>
```

The `DefaultAdapter` always returns `'data-toggle'` from `getDataToggleAttribute()` and `'data-dismiss'` from `getDismissAttribute()`.

### Step 15: Bootstrap 4 Grid System

Bootstrap 4 uses a 12-column flexbox grid. The `canvastack_gird()` helper generates grid HTML via `DefaultAdapter`:

```php
// PHP helper usage
echo canvastack_gird('start');           // <div class="container">
echo canvastack_gird('row');             // <div class="row">
echo canvastack_set_gird_column(6);      // <div class="col-6">
echo canvastack_gird('end');             // </div>

// Responsive columns
echo canvastack_set_gird_column('md-6'); // <div class="col-md-6">
```

Bootstrap 4 breakpoints:

| Breakpoint | Class prefix | Min width |
|---|---|---|
| Extra small | `col-` | < 576px |
| Small | `col-sm-` | ≥ 576px |
| Medium | `col-md-` | ≥ 768px |
| Large | `col-lg-` | ≥ 992px |
| Extra large | `col-xl-` | ≥ 1200px |

### Step 16: Bootstrap 4 Form Classes

Bootstrap 4 form classes differ from Bootstrap 5. Key classes for the default template:

```html
<!-- Text input — Bootstrap 4 -->
<div class="form-group">
    <label for="name">Name</label>
    <input type="text" id="name" name="name" class="form-control">
</div>

<!-- Select — Bootstrap 4 uses Chosen.js, class added by DefaultAdapter -->
<!-- canvastack_form_selectbox() outputs: -->
<select class="chosen-select-deselect chosen-selectbox form-control" name="country">
    <option value="US">United States</option>
</select>

<!-- Checkbox — Bootstrap 4 uses ckbox, added by DefaultAdapter -->
<!-- canvastack_form_checkList() outputs: -->
<div class="ckbox ckbox-primary">
    <input type="checkbox" name="terms" id="terms" value="1">
    <label for="terms">I agree</label>
</div>

<!-- Float utilities — Bootstrap 4 -->
<div class="pull-right">Right aligned</div>   <!-- Bootstrap 5: float-end -->
<div class="pull-left">Left aligned</div>     <!-- Bootstrap 5: float-start -->

<!-- Hide utility — Bootstrap 4 -->
<div class="hide">Hidden element</div>        <!-- Bootstrap 5: d-none -->
```

### Step 17: Chosen.js Select Enhancement Setup

Chosen.js is initialized automatically by `canvastackscripts.js`. To manually initialize:

```javascript
// Initialize all Chosen.js selects on the page
$('.chosen-select-deselect').chosen({
    allow_single_deselect: true,  // allows clearing the selection
    width: '100%',                // responsive width
    search_contains: true         // search matches anywhere in option text
});

// Multi-select
$('.chosen-select').chosen({
    width: '100%',
    max_selected_options: 5       // limit selections
});

// Destroy and re-initialize (e.g., after AJAX content load)
$('.chosen-select-deselect').chosen('destroy');
$('.chosen-select-deselect').chosen({ allow_single_deselect: true, width: '100%' });
```

### Step 18: Bootstrap 4 Modal Setup

Modals in the default template use the Bootstrap 4 JavaScript API via `canvastack-modal-adapter.js`:

```javascript
// The modal adapter detects the active template automatically
// and routes to the correct API

// Show a modal
CanvaStackModal.show('myModal');

// Hide a modal
CanvaStackModal.hide('myModal');

// Under the hood for default template, this calls:
$('#myModal').modal('show');
$('#myModal').modal('hide');
```

Modal HTML structure (generated by `DefaultAdapter::renderFilterModal()`):

```html
<div class="modal fade" id="myModal" tabindex="-1" role="dialog"
     aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Modal Title</h5>
                <!-- data-dismiss="modal" is Bootstrap 4 syntax -->
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Modal content here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    Close
                </button>
                <button type="button" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>
```

---

## Section 4: Code Examples

### Complete Tab Navigation Example

```php
// Controller
$tabs = [
    ['data' => 'General', 'pointer' => 'general', 'active' => true,  'class' => false],
    ['data' => 'Security', 'pointer' => 'security', 'active' => false, 'class' => false],
    ['data' => 'Billing',  'pointer' => 'billing',  'active' => false, 'class' => false],
];
```

```blade
{{-- Tab headers --}}
<ul class="nav nav-tabs" role="tablist">
    @foreach ($tabs as $tab)
        {!! canvastack_form_create_header_tab(
            $tab['data'],
            $tab['pointer'],
            $tab['active'] ? $tab['pointer'] : false,
            $tab['class']
        ) !!}
    @endforeach
</ul>

{{-- Tab content panes --}}
<div class="tab-content">
    {!! canvastack_form_create_content_tab('general', 'general', true) !!}
        <p>General settings content here.</p>
    </div>

    {!! canvastack_form_create_content_tab('security', 'security', false) !!}
        <p>Security settings content here.</p>
    </div>

    {!! canvastack_form_create_content_tab('billing', 'billing', false) !!}
        <p>Billing settings content here.</p>
    </div>
</div>
```

**DefaultAdapter output for tab header:**
```html
<li class="nav-item">
    <a class="nav-link active" data-toggle="tab" href="#general">General</a>
</li>
```

### Complete Alert Message Example

```php
// Flash a success message
session()->flash('success', 'Record saved successfully.');

// Flash an error message
session()->flash('error', 'Validation failed.');
```

```blade
@if (session('success'))
    {!! canvastack_form_alert_message(session('success'), 'success', 'Success', 'msg', false) !!}
@endif

@if (session('error'))
    {!! canvastack_form_alert_message(session('error'), 'danger', 'Error', 'msg', false) !!}
@endif
```

**DefaultAdapter output:**
```html
<div class="alert alert-block alert-success alert-dismissible fade show" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    <h4 class="alert-heading">Success</h4>
    <p>Record saved successfully.</p>
</div>
```

Note: `alert-block` is Bootstrap 4 specific. Bootstrap 5 removes this class.

### Complete Form with Select and Checkbox

```php
// In controller
$countries = ['US' => 'United States', 'UK' => 'United Kingdom', 'CA' => 'Canada'];
$roles     = ['admin' => 'Administrator', 'user' => 'Regular User'];
```

```blade
<form method="POST" action="{{ route('users.store') }}">
    @csrf

    {{-- Text input --}}
    <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name"
               class="form-control" value="{{ old('name') }}" required>
    </div>

    {{-- Select with Chosen.js (DefaultAdapter adds chosen-select-deselect class) --}}
    <div class="form-group">
        <label>Country</label>
        {!! canvastack_form_selectbox('country', $countries, old('country'), [], true, false) !!}
    </div>

    {{-- Checkbox (DefaultAdapter adds ckbox wrapper) --}}
    <div class="form-group">
        {!! canvastack_form_checkList('newsletter', '1', 'Subscribe to newsletter',
            old('newsletter') == '1', 'primary', 'newsletter-cb', null) !!}
    </div>

    <button type="submit" class="btn btn-primary">Save</button>
</form>
```

### Complete DataTable Setup

```blade
{{-- Table HTML --}}
<table id="users-table" class="{{ canvastack_table_class() }}" style="width:100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>
</table>

@push('scripts')
<script>
$(document).ready(function() {
    $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("users.datatables") }}',
            type: 'POST',
            data: function(d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
            }
        },
        columns: [
            { data: 'id',      name: 'id' },
            { data: 'name',    name: 'name' },
            { data: 'email',   name: 'email' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });
});
</script>
@endpush
```

---

## Section 5: Testing Your Template

### Manual Testing Checklist

Before deploying, verify each item:

**Layout**
- [ ] Sidebar renders and is collapsible
- [ ] Header renders with breadcrumbs
- [ ] Footer renders with copyright info
- [ ] Offside panel opens and closes
- [ ] Back-to-top button appears on scroll
- [ ] Page loader disappears after load

**Navigation**
- [ ] All sidebar menu items link correctly
- [ ] Active menu item is highlighted
- [ ] Dropdown menus open with `data-toggle="dropdown"`
- [ ] Tab navigation works with `data-toggle="tab"`

**Forms**
- [ ] Chosen.js select elements have search functionality
- [ ] Checkboxes render with `ckbox` wrapper
- [ ] Alert messages show and dismiss with `data-dismiss="alert"`
- [ ] Form validation errors display correctly

**Tables**
- [ ] DataTables renders with Bootstrap 4 styling
- [ ] Filter modal opens and closes
- [ ] Action buttons render with `btn-xs` size
- [ ] Pagination works correctly

**Assets**
- [ ] Bootstrap 4 CSS loaded (check Network tab)
- [ ] jQuery loaded before Bootstrap JS
- [ ] Chosen.js CSS and JS loaded
- [ ] Custom `app.css` loaded
- [ ] No 404 errors in browser console

### Browser Testing

Test in all major browsers:

| Browser | Version | Priority |
|---|---|---|
| Chrome | Latest | High |
| Firefox | Latest | High |
| Safari | Latest | High |
| Edge | Latest | Medium |
| Chrome Mobile | Latest | Medium |
| Safari iOS | Latest | Medium |

### Responsive Testing

Test at these breakpoints:

| Breakpoint | Width | Test |
|---|---|---|
| Mobile | 375px | Sidebar collapses, content stacks |
| Tablet | 768px | Sidebar may be hidden, grid adjusts |
| Desktop | 1024px | Full layout visible |
| Wide | 1440px | Content centered, no overflow |

### Plugin Functionality Testing

- **Chosen.js:** Type in select to search, clear selection with × button
- **DataTables:** Sort columns, search, paginate, filter modal
- **Flatpickr:** Open date picker, select date, clear date
- **Tooltips:** Hover over `data-toggle="tooltip"` elements
- **Modals:** Open via `data-toggle="modal"`, close via `data-dismiss="modal"`

### Accessibility Testing

- [ ] All images have `alt` attributes
- [ ] Form inputs have associated `<label>` elements
- [ ] Modals have `aria-labelledby` and `aria-hidden`
- [ ] Buttons have descriptive text or `aria-label`
- [ ] Color contrast meets WCAG 2.1 AA (4.5:1 for normal text)
- [ ] Keyboard navigation works (Tab, Enter, Escape)
- [ ] Screen reader announces modal open/close

> **Note:** Full WCAG compliance requires manual testing with assistive technologies (NVDA, VoiceOver) and expert accessibility review.

---

## Section 6: Troubleshooting

### Assets Not Loading

**Symptom:** CSS or JS files return 404 in browser Network tab.

**Check 1 — Local asset path:**
```php
// Wrong — absolute path
'css' => ['/assets/templates/default/css/app.css'],

// Correct — relative to public/
'css' => ['assets/templates/default/css/app.css'],
```

**Check 2 — File actually exists:**
```bash
ls public/assets/templates/default/css/app.css
```

**Check 3 — Config cache:**
```bash
php artisan config:clear
php artisan view:clear
```

**Check 4 — CDN URL is accessible:**
Open the CDN URL directly in browser. If blocked, switch to local copy.

### Plugins Not Working

**Symptom:** Chosen.js selects show as plain `<select>`, DataTables not initializing.

**Check 1 — jQuery loaded before plugins:**
In browser console: `typeof jQuery` should return `'function'`. If `undefined`, jQuery is not loaded or loaded after the plugin.

**Check 2 — Plugin JS in correct position:**
Chosen.js must be in `bottom.first.js` (before `canvastackscripts.js` which initializes it).

**Check 3 — Initialization script running:**
```javascript
// In browser console
$('.chosen-select-deselect').length // should be > 0
$('.chosen-select-deselect').data('chosen') // should be an object, not undefined
```

**Check 4 — No JavaScript errors:**
Open browser DevTools → Console. Fix any errors shown before plugin initialization.

### Styling Issues

**Symptom:** Components look unstyled or use wrong Bootstrap version styles.

**Check 1 — Bootstrap 4 loaded, not Bootstrap 5:**
```javascript
// In browser console — Bootstrap 4 uses $.fn.modal
typeof $.fn.modal // should return 'function'

// Check version
$.fn.tooltip.Constructor.VERSION // should start with '4.'
```

**Check 2 — Using Bootstrap 4 classes:**
```html
<!-- Wrong for Bootstrap 4 -->
<div class="d-none">...</div>      <!-- use 'hide' -->
<div class="float-end">...</div>   <!-- use 'pull-right' -->
<button class="btn-sm">...</button> <!-- use 'btn-xs' for extra small -->

<!-- Correct for Bootstrap 4 -->
<div class="hide">...</div>
<div class="pull-right">...</div>
<button class="btn-xs">...</button>
```

**Check 3 — CSS specificity conflict:**
Open DevTools → Elements → Computed styles. Check if Bootstrap 4 styles are being overridden by custom CSS.

### Modal Not Opening

**Symptom:** Clicking modal trigger button does nothing.

```javascript
// Check Bootstrap modal is available
typeof $.fn.modal // should be 'function'

// Check modal element exists
$('#myModal').length // should be 1

// Check trigger has correct attributes
// Must have data-toggle="modal" AND data-target="#myModal"
```

---

## Section 7: Best Practices

### Naming Conventions

**Blade files:** lowercase with hyphens
```
dashboard.blade.php       ✓
user-profile.blade.php    ✓
UserProfile.blade.php     ✗
```

**CSS classes:** follow Bootstrap 4 BEM-like conventions
```css
/* Good */
.sidebar-menu { }
.sidebar-menu__header { }
.sidebar-menu--collapsed { }

/* Avoid */
.sidebarMenu { }
.SidebarMenu { }
```

**JavaScript:** camelCase for variables and functions
```javascript
// Good
const sidebarMenu = document.querySelector('.sidebar-menu');
function initSidebar() { }

// Avoid
const sidebar_menu = ...;
function init_sidebar() { }
```

### Asset Organization

Keep assets organized by type and purpose:
```
public/assets/templates/default/
├── css/
│   ├── app.css          ← All custom styles (single file preferred)
│   └── print.css        ← Print-specific styles (optional)
├── js/
│   ├── app.js           ← App initialization
│   ├── scripts.js       ← UI interactions
│   └── sidebar.js       ← Sidebar-specific logic
├── fonts/
│   └── custom-font/     ← One directory per font family
└── images/
    ├── bg/              ← Background images
    ├── icons/           ← Custom icons
    └── logo.png         ← App logo
```

### Performance Optimization

1. **Version-lock CDN assets** to prevent unexpected breaking changes:
   ```php
   // Good — version locked
   'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css'

   // Bad — may break on major version update
   'https://cdn.jsdelivr.net/npm/bootstrap@latest/dist/css/bootstrap.min.css'
   ```

2. **Use minified files** in production (`.min.css`, `.min.js`).

3. **Load non-critical JS at bottom** — keep `top.js` minimal, put most scripts in `bottom.last.js`.

4. **Avoid loading plugin CSS/JS on every page** — use the plugin-specific config keys (`datatable`, `select`, `date`) so they only load on pages that need them.

### Security Considerations

1. **Always escape user content in Blade:**
   ```blade
   {{ $userInput }}      ← escaped (safe)
   {!! $trustedHtml !!}  ← unescaped (only for trusted HTML from helpers)
   ```

2. **CSRF token on all forms:**
   ```blade
   <form method="POST">
       @csrf
       ...
   </form>
   ```

3. **Subresource Integrity (SRI) for CDN assets:**
   ```html
   <link rel="stylesheet"
         href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
         integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N"
         crossorigin="anonymous">
   ```

### Accessibility Best Practices

1. **Landmark roles on layout elements:**
   ```html
   <nav class="sidebar-menu" role="navigation" aria-label="Main navigation">
   <main class="main-content" role="main">
   <footer role="contentinfo">
   ```

2. **Skip navigation link:**
   ```html
   <a href="#main-content" class="sr-only sr-only-focusable">Skip to main content</a>
   ```

3. **Focus management for modals** — Bootstrap 4 handles this automatically when using `data-toggle="modal"`.

---

## Section 8: Advanced Customization

### Customizing Bootstrap 4 Theme

Override Bootstrap 4 variables in your `app.css`:

```css
/* app.css — Bootstrap 4 variable overrides */
/* These must be loaded AFTER Bootstrap 4 CSS */

:root {
    /* Override Bootstrap 4 primary color */
    --primary: #2c3e50;
    --secondary: #95a5a6;
    --success: #27ae60;
    --danger: #e74c3c;
    --warning: #f39c12;
    --info: #3498db;
}

/* Override specific Bootstrap 4 components */
.btn-primary {
    background-color: var(--primary);
    border-color: var(--primary);
}

.navbar-dark .navbar-brand {
    color: #fff;
    font-weight: 700;
}
```

For deeper customization, use Bootstrap 4's Sass variables (requires a build process):

```scss
// _variables.scss
$primary:   #2c3e50;
$secondary: #95a5a6;
$font-size-base: 0.9rem;
$border-radius: 0.25rem;

// Import Bootstrap 4 after variables
@import "~bootstrap/scss/bootstrap";
```

### Adding Custom Fonts

1. Add font files to `public/assets/templates/default/fonts/`
2. Declare `@font-face` in `app.css`:

```css
@font-face {
    font-family: 'CustomFont';
    src: url('../fonts/custom-font/CustomFont-Regular.woff2') format('woff2'),
         url('../fonts/custom-font/CustomFont-Regular.woff') format('woff');
    font-weight: 400;
    font-style: normal;
    font-display: swap; /* prevents invisible text during font load */
}

body {
    font-family: 'CustomFont', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
```

### Adding Custom JavaScript Functionality

Add custom scripts to `app.js` and register in `bottom.last.js` config:

```javascript
// public/assets/templates/default/js/app.js

(function($) {
    'use strict';

    // Initialize on DOM ready
    $(document).ready(function() {
        App.init();
    });

    var App = {
        init: function() {
            this.initTooltips();
            this.initPopovers();
            this.initCustomComponents();
        },

        initTooltips: function() {
            // Bootstrap 4 tooltip initialization
            $('[data-toggle="tooltip"]').tooltip({
                trigger: 'hover',
                placement: 'top'
            });
        },

        initPopovers: function() {
            $('[data-toggle="popover"]').popover({
                trigger: 'click',
                html: true
            });
        },

        initCustomComponents: function() {
            // Your custom initialization here
        }
    };

})(jQuery);
```

### Extending DefaultAdapter

To add custom rendering behavior while keeping Bootstrap 4 as the base:

```php
// app/Theme/CustomDefaultAdapter.php
namespace App\Theme;

use Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter;

class CustomDefaultAdapter extends DefaultAdapter
{
    /**
     * Override renderAlertMessage to add custom wrapper div.
     */
    public function renderAlertMessage(
        string|array $message,
        string $type,
        string $title,
        string $prefix,
        string|false $extra
    ): string {
        // Get the standard Bootstrap 4 alert from parent
        $alert = parent::renderAlertMessage($message, $type, $title, $prefix, $extra);

        // Wrap with custom container
        return '<div class="alert-wrapper">' . $alert . '</div>';
    }
}
```

Register in `AppServiceProvider`:

```php
use App\Theme\CustomDefaultAdapter;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;

public function boot()
{
    // Override the default adapter with your custom one
    ThemeAdapterResolver::register('default', CustomDefaultAdapter::class);
}
```

---

## Section 9: Migration to Other Templates

### Migrating to Bootstrap 5 (canvasign)

Key changes when moving from `default` to `canvasign`:

| Bootstrap 4 | Bootstrap 5 | Notes |
|---|---|---|
| `data-toggle` | `data-bs-toggle` | All toggle attributes |
| `data-dismiss` | `data-bs-dismiss` | All dismiss attributes |
| `data-target` | `data-bs-target` | Modal/collapse targets |
| `pull-right` | `float-end` | Float utilities |
| `pull-left` | `float-start` | Float utilities |
| `hide` | `d-none` | Hide utility |
| `btn-xs` | `btn-sm` | Smallest button size |
| `alert-block` | *(removed)* | Block alert class |
| `custom-select` | `form-select` | Select element |
| `custom-control` | `form-check` | Checkbox/radio wrapper |
| Chosen.js | Choices.js | Select enhancement plugin |
| jQuery required | jQuery optional | Bootstrap 5 is vanilla JS |

**Migration steps:**
1. Change `'template' => 'canvasign'` in config
2. Update `config/canvastack.templates.php` with Bootstrap 5 CDN URLs
3. Replace Chosen.js with Choices.js in asset config
4. Update any custom Blade files that use Bootstrap 4 specific classes
5. Update any custom JavaScript that uses Bootstrap 4 API directly

The CanvaStack helper functions (`canvastack_form_*`, `canvastack_modal_*`) update automatically — no PHP changes needed.

### Migrating to TailwindCSS (canvas)

Key changes when moving from `default` to `canvas`:

| Bootstrap 4 | TailwindCSS | Notes |
|---|---|---|
| `container` | `container mx-auto` | Container class |
| `row` | `flex flex-wrap` | Row class |
| `col-md-6` | `w-1/2` | Column class |
| `btn btn-primary` | `px-4 py-2 bg-blue-600 text-white rounded` | Button |
| `form-control` | `form-input w-full` | Text input |
| `hide` | `hidden` | Hide utility |
| `pull-right` | `ml-auto` | Float right equivalent |
| Bootstrap modals | Custom JS modals | No Bootstrap dependency |

**Migration steps:**
1. Change `'template' => 'canvas'` in config
2. Update `config/canvastack.templates.php` with TailwindCSS CDN
3. Create new Blade views under `resources/views/canvas/`
4. Replace Bootstrap 4 classes with Tailwind utility classes in custom views
5. Update custom JavaScript to use `CanvaStackModal` and `CanvaStackTooltip` adapters

The CanvaStack helper functions update automatically — no PHP changes needed.

---

## Section 10: Resources and References

### Bootstrap 4 Documentation
- **Official Docs:** https://getbootstrap.com/docs/4.6/
- **Grid System:** https://getbootstrap.com/docs/4.6/layout/grid/
- **Forms:** https://getbootstrap.com/docs/4.6/components/forms/
- **Modals:** https://getbootstrap.com/docs/4.6/components/modal/
- **JavaScript:** https://getbootstrap.com/docs/4.6/getting-started/javascript/

### CanvaStack Theme Engine Documentation
- **Overview:** `vendor/canvastack/canvastack/docs/THEMENGINE/README.md`
- **Getting Started:** `vendor/canvastack/canvastack/docs/THEMENGINE/GETTING_STARTED.md`
- **API Reference:** `vendor/canvastack/canvastack/docs/THEMENGINE/API_REFERENCE.md`
- **Template Configuration:** `vendor/canvastack/canvastack/docs/THEMENGINE/TEMPLATE_CONFIGURATION.md`
- **Architecture:** `vendor/canvastack/canvastack/docs/THEMENGINE/ARCHITECTURE.md`
- **Migration Guide:** `vendor/canvastack/canvastack/docs/THEMENGINE/MIGRATION_GUIDE.md`

### Plugin Documentation
- **DataTables:** https://datatables.net/manual/
- **Chosen.js:** https://harvesthq.github.io/chosen/
- **Flatpickr:** https://flatpickr.js.org/
- **ApexCharts:** https://apexcharts.com/docs/

### Laravel Documentation
- **Blade Templates:** https://laravel.com/docs/blade
- **Asset Bundling:** https://laravel.com/docs/vite
- **Authentication:** https://laravel.com/docs/authentication

### Code Examples
- **Bootstrap 4 examples:** `vendor/canvastack/canvastack/docs/THEMENGINE/examples/bootstrap4-template-example/`
- **Form rendering:** `vendor/canvastack/canvastack/docs/THEMENGINE/examples/form-rendering-example.php`
- **Table rendering:** `vendor/canvastack/canvastack/docs/THEMENGINE/examples/table-rendering-example.php`

---

**Last Updated:** April 28, 2026
**Maintained By:** CanvaStack Team

---

Alhamdulillah, may this guide help developers build excellent Bootstrap 4 templates.

**Built with ❤️ by CanvaStack**
