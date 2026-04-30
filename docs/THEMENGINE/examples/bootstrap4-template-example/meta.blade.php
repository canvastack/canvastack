{{--
    meta.blade.php — Bootstrap 4 Head Section Example
    ==================================================
    Template: default (Bootstrap 4)
    Location: resources/views/default/template/admin/block/meta.blade.php

    Renders everything inside <head>:
    - Favicon
    - CSRF token (required for AJAX requests)
    - App debug flag (exposed to JavaScript)
    - Dynamic meta tags from $components->meta
    - CSS files from template config (all positions)
    - Top-position JS files (jQuery, Bootstrap 4)

    Asset loading order:
      1. top.css[0..n]          → Bootstrap 4 CSS, Font Awesome, Chosen.js CSS
      2. bottom_first.css[0..n] → Custom CSS (usually empty for default template)
      3. bottom_last.css[0..n]  → Override CSS (usually empty for default template)
      4. top.js[0..n]           → jQuery, Bootstrap 4 bundle (loaded in <head>)

    Note: bottom.js and bottom_last.js are loaded in downscripts.blade.php,
    not here. Only top-position JS is loaded in <head>.
--}}
<?php
// Collect meta HTML tags from the meta component
$meta = $components->meta->content['html'];

// Collect CSS from all three positions
// These are pre-rendered <link> tag objects by the Template component
$styles = [
    'top'          => $components->template->scripts['css']['top']          ?? [],
    'bottom_first' => $components->template->scripts['css']['bottom_first'] ?? [],
    'bottom_last'  => $components->template->scripts['css']['bottom_last']  ?? [],
];

// Collect top-position JS (loaded in <head>)
// These are pre-rendered <script> tag objects
$scripts = $components->template->scripts['js']['top'] ?? [];
?>

{{-- Favicon --}}
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />

{{-- CSRF token — required for all AJAX POST/PUT/DELETE requests --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

{{--
    Expose app debug flag to JavaScript.
    Usage in JS: if (window.APP_DEBUG) { console.log('debug info'); }
--}}
<script>window.APP_DEBUG = {{ config('app.debug') ? 'true' : 'false' }};</script>

{{--
    Dynamic meta tags rendered by the meta component.
    Includes: <title>, <meta name="description">, <meta property="og:*">, etc.
    These are set by the controller via $components->meta.
--}}
@foreach ($meta as $metaTag)
    {!! $metaTag !!}
@endforeach

{{--
    Framework CSS — loaded first so it is available before body renders.
    For Bootstrap 4 default template, this includes:
    - Bootstrap 4 CSS (CDN)
    - Font Awesome CSS (CDN)
    - Chosen.js CSS (CDN)
    - Custom app.css (local)

    Configured in: config/canvastack.templates.php
    Key: default.position.top.css
--}}
@foreach ($styles['top'] as $style)
    {!! $style->html !!}
@endforeach

{{--
    Custom CSS — loaded after framework CSS.
    Usually empty for the default template (custom styles go in app.css).

    Configured in: config/canvastack.templates.php
    Key: default.position.bottom.first.css
--}}
@foreach ($styles['bottom_first'] as $style)
    {!! $style->html !!}
@endforeach

{{--
    Override CSS — loaded last, highest specificity.
    Use for per-page or per-module CSS overrides.

    Configured in: config/canvastack.templates.php
    Key: default.position.bottom.last.css
--}}
@foreach ($styles['bottom_last'] as $style)
    {!! $style->html !!}
@endforeach

{{--
    Top-position JavaScript — loaded in <head>.
    For Bootstrap 4 default template, this includes:
    - jQuery 3.6.0 (required by Bootstrap 4 and Chosen.js)
    - Bootstrap 4 bundle (includes Popper.js)

    These are loaded in <head> because some inline scripts in the page
    may depend on jQuery being available immediately.

    Configured in: config/canvastack.templates.php
    Key: default.position.top.js
--}}
@foreach ($scripts as $script)
    {!! $script->html !!}
@endforeach
