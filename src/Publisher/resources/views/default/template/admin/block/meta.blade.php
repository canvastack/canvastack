<?php
/**
 * Created on 10 Mar 2021
 * Time Created	: 10:04:51
 *
 * @filesource	meta.blade.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */

// Helper to make stdClass with html property
$__mk = function (string $html) {
    return (object) ['html' => $html];
};

// Guard for optional $components (e.g., simple previews)
$styles  = [];
$meta    = [];
$scripts = [];

if (isset($components) && is_object($components)) {
    $meta = (isset($components->meta) && isset($components->meta->content['html'])) ? $components->meta->content['html'] : [];
    $scripts = (isset($components->template) && isset($components->template->scripts['js']['top'])) ? $components->template->scripts['js']['top'] : [];
    $styles['top'] = (isset($components->template) && isset($components->template->scripts['css']['top'])) ? $components->template->scripts['css']['top'] : [];
    $styles['bottom_first'] = (isset($components->template) && isset($components->template->scripts['css']['bottom_first'])) ? $components->template->scripts['css']['bottom_first'] : [];
    $styles['bottom_last']  = (isset($components->template) && isset($components->template->scripts['css']['bottom_last'])) ? $components->template->scripts['css']['bottom_last'] : [];
} else {
    // Fallback: build from templates config when $components not provided
    $styles['top'] = [];
    $styles['bottom_first'] = [];
    $styles['bottom_last'] = [];
    $scripts = [];

    // Compute asset base
    $baseUrl = function_exists('canvastack_config') ? canvastack_config('baseURL') : url('');
    $baseTemplate = function_exists('canvastack_config') ? canvastack_config('base_template') : 'assets/templates/default';
    $template = function_exists('canvastack_config') ? canvastack_config('template') : 'admin';
    $assetBase = rtrim((string)$baseUrl, '/').'/'.trim((string)$baseTemplate, '/').'/'.trim((string)$template, '/');

    $topCss = function_exists('canvastack_config') ? (canvastack_config('admin.default.position.top.css', 'templates') ?? []) : [];
    $bottomFirstCss = function_exists('canvastack_config') ? (canvastack_config('admin.default.position.bottom.first.css', 'templates') ?? []) : [];
    $bottomLastCss = function_exists('canvastack_config') ? (canvastack_config('admin.default.position.bottom.last.css', 'templates') ?? []) : [];
    $topJs = function_exists('canvastack_config') ? (canvastack_config('admin.default.position.top.js', 'templates') ?? []) : [];

    foreach ($topCss as $file) {
        if (is_string($file) && $file) $styles['top'][] = $__mk('<link rel="stylesheet" href="'.$assetBase.'/'.ltrim($file, '/').'" />');
    }
    foreach ($bottomFirstCss as $file) {
        if (is_string($file) && $file) $styles['bottom_first'][] = $__mk('<link rel="stylesheet" href="'.$assetBase.'/'.ltrim($file, '/').'" />');
    }
    foreach ($bottomLastCss as $file) {
        if (is_string($file) && $file) $styles['bottom_last'][] = $__mk('<link rel="stylesheet" href="'.$assetBase.'/'.ltrim($file, '/').'" />');
    }
    foreach ($topJs as $file) {
        if (is_string($file) && $file) $scripts[] = $__mk('<script type="text/javascript" src="'.$assetBase.'/'.ltrim($file, '/').'"></script>');
    }
}
?>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    
    <!-- MetaTags  -->
    @foreach ($meta as $metaTags)
        {!! $metaTags !!}
    @endforeach

    <!-- CSS  -->
    @foreach ($styles['top'] as $style)
        {!! $style->html !!}
    @endforeach
    
    @foreach ($styles['bottom_first'] as $style)
        {!! $style->html !!}
    @endforeach
    
    @foreach ($styles['bottom_last'] as $style)
        {!! $style->html !!}
    @endforeach
    
    <!-- JS  -->
    @foreach ($scripts as $script)
        {!! $script->html !!}
    @endforeach
