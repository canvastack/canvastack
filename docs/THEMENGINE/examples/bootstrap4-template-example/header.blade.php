{{--
    header.blade.php — Bootstrap 4 Top Navigation Bar Example
    ==========================================================
    Template: default (Bootstrap 4)
    Location: resources/views/default/template/admin/block/header.blade.php

    Renders the top navigation bar including:
    - Hamburger menu toggle (for sidebar collapse)
    - Search box
    - Notification bell dropdown
    - Messages dropdown
    - Settings button
    - Breadcrumb (from canvastack_breadcrumb() via DefaultAdapter)

    Bootstrap 4 specific:
    - data-toggle="dropdown" (NOT data-bs-toggle)
    - pull-right for right-aligned elements (NOT float-end)
    - pull-left for left-aligned elements (NOT float-start)
    - dropdown-toggle class for dropdown triggers
--}}
<?php
// Build asset URL for template-specific images
$baseUrl      = canvastack_config('baseURL');
$baseTemplate = canvastack_config('base_template');
$template     = canvastack_config('template');
$assetURL     = "{$baseUrl}/{$baseTemplate}/{$template}";
?>

{{-- HEADER BLOCK --}}
<div class="shadow" role="banner">
    <div class="header-area blury blury-blue">
        <div class="row align-items-center">

            {{-- ── Left side: hamburger + search ──────────────────────── --}}
            <div class="col-md-6 col-sm-8 clearfix">

                {{-- Hamburger menu toggle — triggers sidebar collapse via sidebar.js --}}
                {{-- pull-left is Bootstrap 4 (use float-start in Bootstrap 5) --}}
                <div class="nav-btn pull-left" role="button"
                     aria-label="Toggle sidebar" tabindex="0">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                {{-- Search box --}}
                <div class="search-box CanvaStack-search-box pull-left">
                    <div class="search-inputbox">
                        <form action="#" role="search">
                            <label for="search-input" class="sr-only">Search</label>
                            <input id="search-input" type="text" name="search"
                                   placeholder="Search..." required />
                            <i class="ti-search" aria-hidden="true"></i>
                        </form>
                    </div>
                </div>

            </div>

            {{-- ── Right side: notifications + profile ─────────────────── --}}
            <div class="col-md-6 col-sm-4 clearfix">
                {{-- pull-right is Bootstrap 4 (use float-end in Bootstrap 5) --}}
                <ul class="notification-area pull-right" role="list">

                    {{-- Fullscreen toggle --}}
                    <li>
                        <button id="full-view" class="btn btn-link"
                                aria-label="Enter fullscreen">
                            <i class="ti-fullscreen" aria-hidden="true"></i>
                        </button>
                    </li>
                    <li>
                        <button id="full-view-exit" class="btn btn-link"
                                aria-label="Exit fullscreen" style="display:none">
                            <i class="ti-zoom-out" aria-hidden="true"></i>
                        </button>
                    </li>

                    {{--
                        Notification bell dropdown
                        Bootstrap 4: data-toggle="dropdown" (NOT data-bs-toggle)
                    --}}
                    <li class="dropdown">
                        <button class="btn btn-link dropdown-toggle"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false"
                                aria-label="Notifications (2 unread)">
                            <i class="ti-bell" aria-hidden="true"></i>
                            <span class="badge badge-danger">2</span>
                        </button>
                        <div class="dropdown-menu bell-notify-box notify-box"
                             aria-label="Notifications">
                            <span class="notify-title">
                                You have 2 new notifications
                                <a href="{{ route('notifications.index') }}">view all</a>
                            </span>
                            <div class="nofity-list">
                                <a href="#" class="notify-item dropdown-item">
                                    <div class="notify-thumb">
                                        <i class="ti-key btn-danger" aria-hidden="true"></i>
                                    </div>
                                    <div class="notify-text">
                                        <p>Password changed successfully</p>
                                        <span>Just Now</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </li>

                    {{-- Messages dropdown --}}
                    <li class="dropdown">
                        <button class="btn btn-link dropdown-toggle"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false"
                                aria-label="Messages (3 unread)">
                            <i class="fa fa-envelope-o" aria-hidden="true"></i>
                            <span class="badge badge-info">3</span>
                        </button>
                        <div class="dropdown-menu notify-box nt-enveloper-box"
                             aria-label="Messages">
                            <span class="notify-title">
                                You have 3 new messages
                                <a href="{{ route('messages.index') }}">view all</a>
                            </span>
                            <div class="nofity-list">
                                <a href="#" class="notify-item dropdown-item">
                                    <div class="notify-thumb">
                                        <img src="{{ $assetURL }}/images/author/author-img1.jpg"
                                             alt="User avatar" width="40" height="40" />
                                    </div>
                                    <div class="notify-text">
                                        <p>John Doe</p>
                                        <span class="msg">Hey, are you available?</span>
                                        <span>3:15 PM</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </li>

                    {{-- Settings button --}}
                    <li>
                        <button class="settings-btn btn btn-link"
                                aria-label="Open settings panel">
                            <i class="ti-settings" aria-hidden="true"></i>
                        </button>
                    </li>

                </ul>
            </div>

        </div>

        {{--
            Breadcrumb — rendered by canvastack_breadcrumb() via DefaultAdapter.
            DefaultAdapter::renderBreadcrumb() produces Bootstrap 4 breadcrumb HTML
            with pull-right for right-aligned links.

            $breadcrumbs is set by the controller/middleware.
        --}}
        {!! $breadcrumbs !!}

    </div>
</div>
{{-- END HEADER BLOCK --}}
