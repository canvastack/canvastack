<?php
/**
 * Canvasign Template — Sticky Action Bar
 *
 * Renders per-page action buttons (CRUD, cache, etc.) using the
 * theme-aware helper canvastack_page_action_buttons(), which delegates
 * to Bootstrap5Adapter::renderPageActionButtons() for canvasign.
 *
 * The wrapper <div> provides sticky positioning below the topbar.
 * It is only rendered when $route_info->action_page is non-empty.
 *
 * @filesource  action-bar.blade.php
 * @author      wisnuwidi@canvastack.com
 */
?>
@if (!empty($route_info) && !empty($route_info->action_page))
<div class="action-bar" id="canvasign-action-bar">

    {{-- Left: page title + breadcrumb --}}
    <div class="action-bar-title">
        @if (!empty($route_info->page_info))
            <span class="action-bar-page">{!! $route_info->page_info !!}</span>
        @endif
        @if (!empty($route_info->module_name))
            <nav class="action-bar-crumbs" aria-label="breadcrumb">
                <span>{!! $route_info->module_name !!}</span>
                @if (!empty($route_info->page_info))
                    <i class="bi bi-chevron-right"></i>
                    <span class="active">{!! $route_info->page_info !!}</span>
                @endif
            </nav>
        @endif
    </div>

    {{-- Right: action buttons — rendered by Bootstrap5Adapter via helper --}}
    <div class="action-bar-buttons">
        {!! canvastack_page_action_buttons($route_info) !!}
    </div>

</div>
@endif
