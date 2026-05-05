<?php
/**
 * Canvasign Template — Admin Main Layout
 *
 * @filesource  index.blade.php
 *
 * @author      wisnuwidi@canvastack.com
 * @copyright   wisnuwidi
 * @email       wisnuwidi@canvastack.com
 */
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        @include('canvasign.template.admin.block.meta')
    </head>

    <body>
        @if (Auth::check())
        <div class="app">
            @include('canvasign.template.admin.block.sidebar')

            @include('canvasign.template.admin.block.header')
            @include('canvasign.template.admin.block.action-bar')

            <main class="main">
                {{-- Page header: title + breadcrumb from route_info --}}
                @if (!empty($route_info))
                <div class="page-header">
                    <div>
                        <h1>{!! $route_info->page_info ?? '' !!}</h1>
                        @if (!empty($route_info->module_name))
                        <div class="crumbs">
                            <a href="#">{!! $route_info->module_name !!}</a>
                            @if (!empty($route_info->page_info))
                                / {!! $route_info->page_info !!}
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                @yield('content')
            </main>

            @include('canvasign.template.admin.block.footer')
        </div>

        @include('canvasign.template.admin.block.offside')

        @else
            @yield('content')
        @endif

        @include('canvasign.template.admin.block.downscripts')
    </body>
</html>
