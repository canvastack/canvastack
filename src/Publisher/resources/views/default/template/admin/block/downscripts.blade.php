<?php
/**
 * Created on 10 Mar 2021
 * Time Created	: 10:28:28
 *
 * @filesource	downscripts.blade.php
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
$scripts = [];
$scripts['bottom_first'] = [];
$scripts['bottom']       = [];
$scripts['bottom_last']  = [];
if (isset($components) && is_object($components) && isset($components->template) && isset($components->template->scripts)) {
    $scripts['bottom_first'] = $components->template->scripts['js']['bottom_first'] ?? [];
    $scripts['bottom']       = $components->template->scripts['js']['bottom'] ?? [];
    $scripts['bottom_last']  = $components->template->scripts['js']['bottom_last'] ?? [];
} else {
    // Fallback when $components is missing: build js paths from config
    $baseUrl = function_exists('canvastack_config') ? canvastack_config('baseURL') : url('');
    $baseTemplate = function_exists('canvastack_config') ? canvastack_config('base_template') : 'assets/templates/default';
    $template = function_exists('canvastack_config') ? canvastack_config('template') : 'admin';
    $assetBase = rtrim((string)$baseUrl, '/').'/'.trim((string)$baseTemplate, '/').'/'.trim((string)$template, '/');

    $bf = function_exists('canvastack_config') ? (canvastack_config('admin.default.position.bottom.first.js', 'templates') ?? []) : [];
    $bm = function_exists('canvastack_config') ? (canvastack_config('admin.default.position.bottom.js', 'templates') ?? []) : [];
    $bl = function_exists('canvastack_config') ? (canvastack_config('admin.default.position.bottom.last.js', 'templates') ?? []) : [];

    foreach ($bf as $file) {
        if (is_string($file) && $file) $scripts['bottom_first'][] = $__mk('<script type="text/javascript" src="'.$assetBase.'/'.ltrim($file, '/').'"></script>');
    }
    foreach ($bm as $file) {
        if (is_string($file) && $file) $scripts['bottom'][] = $__mk('<script type="text/javascript" src="'.$assetBase.'/'.ltrim($file, '/').'"></script>');
    }
    foreach ($bl as $file) {
        if (is_string($file) && $file) $scripts['bottom_last'][] = $__mk('<script type="text/javascript" src="'.$assetBase.'/'.ltrim($file, '/').'"></script>');
    }
}
?>
    <!-- JS -->
    @foreach ($scripts['bottom_first'] as $script)
    {!! $script->html !!}
    @endforeach
    
    @foreach ($scripts['bottom'] as $script)
    {!! $script->html !!}
    @endforeach
    
    @foreach ($scripts['bottom_last'] as $script)
    {!! $script->html !!}
    @endforeach
