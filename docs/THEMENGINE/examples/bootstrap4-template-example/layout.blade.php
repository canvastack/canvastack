{{--
    layout.blade.php — Bootstrap 4 Master Layout Example
    =====================================================
    Template: default (Bootstrap 4)
    Location: resources/views/default/template/admin/index.blade.php

    This is the outermost Blade file. It wraps the entire page and
    includes all block components. Page-specific content is injected
    via @yield('content').

    Bootstrap 4 specific:
    - Body class "page-sound background-content" for custom theme
    - Uses Auth::check() to conditionally render authenticated layout
    - animated fadeInx class for content area animation
--}}
<!DOCTYPE html>
<html class="no-js" lang="en">
    <head>
        {{--
            meta.blade.php renders:
            - CSRF token meta tag
            - App debug flag
            - Dynamic meta tags (title, description, og:*)
            - CSS files from config (top + bottom_first + bottom_last)
            - Top-position JS files (jQuery, Bootstrap 4 bundle)
        --}}
        @include('default.template.admin.block.meta')
    </head>

    <body class="page-sound background-content">
        <!--[if lt IE 8]>
            <p class="browserupgrade">
                You are using an <strong>outdated</strong> browser.
                Please <a href="http://browsehappy.com/">upgrade your browser</a>.
            </p>
        <![endif]-->

        {{-- Optional decorative background image --}}
        <img class="background-img"
             src="{{ asset('assets/templates/default/images/bg/bg-content-001.jpg') }}"
             alt="" aria-hidden="true" />

        {{-- Page loading spinner — hidden by scripts.js after DOM ready --}}
        <div id="preloader" role="status" aria-label="Loading">
            <div class="loader"></div>
        </div>

        @if (Auth::check())
        {{-- ── Authenticated layout ─────────────────────────────────────── --}}
        <div class="page-container">

            {{--
                sidebar.blade.php renders:
                - Logo and app name
                - Sidebar content widget (user profile, etc.)
                - Navigation menu items
            --}}
            @include('default.template.admin.block.sidebar')

            <div class="main-content" role="main" id="main-content">

                {{--
                    header.blade.php renders:
                    - Hamburger menu toggle
                    - Search box
                    - Notification dropdowns (Bootstrap 4 data-toggle="dropdown")
                    - Breadcrumb from canvastack_breadcrumb() via DefaultAdapter
                --}}
                @include('default.template.admin.block.header')

                {{-- Main content area with Bootstrap 4 animation classes --}}
                <div class="main-content-inner animated fadeInx">
                    <div class="content-box">

                        @if (!empty($route_info))
                        {{-- Action buttons (Add New, Export, etc.) from route config --}}
                        {!! canvastack_action_buttons($route_info) !!}
                        @endif

                        <div class="body">
        @endif

                            {{--
                                Page-specific content is injected here.
                                Page views extend 'default.pages.admin' and
                                define @section('content').
                            --}}
                            @yield('content')

        @if (Auth::check())
                        </div>{{-- .body --}}
                    </div>{{-- .content-box --}}
                </div>{{-- .main-content-inner --}}

            </div>{{-- .main-content --}}

            {{--
                footer.blade.php renders:
                - Copyright text with pull-right (Bootstrap 4)
                - Author and email from config
            --}}
            @include('default.template.admin.block.footer')

        </div>{{-- .page-container --}}

        {{-- Back to top button --}}
        <div id="back-top" class="circle show animated pulse" role="button"
             aria-label="Back to top" tabindex="0">
            <i class="fa fa-angle-up" aria-hidden="true"></i>
        </div>

        {{--
            offside.blade.php renders:
            - Right slide-out panel
            - Activity feed tab
            - Settings tab
            Uses Bootstrap 4 data-toggle="tab" for panel tabs
        --}}
        @include('default.template.admin.block.offside')
        @endif

        {{--
            downscripts.blade.php renders:
            - bottom_first.js: plugin libraries (Chosen.js, Flatpickr, etc.)
            - bottom.js: core app scripts
            - bottom_last.js: adapters and custom init scripts
        --}}
        @include('default.template.admin.block.downscripts')
    </body>
</html>
