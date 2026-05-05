<?php
/**
 * Created on {{ date('d M Y') }}
 *
 * @filesource	downscripts.blade.php
 *
 * @author		wisnuwidi@canvastack.com
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */

$scripts = [];
$scripts['bottom_first'] = [];
if (!empty($components->template->scripts['js']['bottom_first'])) $scripts['bottom_first'] = $components->template->scripts['js']['bottom_first'];
$scripts['bottom']       = [];
if (!empty($components->template->scripts['js']['bottom']))       $scripts['bottom']       = $components->template->scripts['js']['bottom'];
$scripts['bottom_last']  = [];
if (!empty($components->template->scripts['js']['bottom_last']))  $scripts['bottom_last']  = $components->template->scripts['js']['bottom_last'];
?>
	<!-- ============ DOWNSCRIPTS OPEN ============ -->

	{{-- 1. bottom_first scripts — Bootstrap CDN, plugins, and core utilities --}}
	@foreach ($scripts['bottom_first'] as $script)
		{!! $script->html !!}
	@endforeach

	{{-- 2. Template identifier for CanvaStack adapters (must load before bottom scripts) --}}
	<script>window.canvastackTemplate = 'canvasign';</script>

	{{-- 3. bottom scripts — inline scripts and dynamic scripts (filter cascading, etc.) --}}
	@foreach ($scripts['bottom'] as $script)
		{!! $script->html !!}
	@endforeach

	{{-- 4. bottom_last scripts — includes app.js, adapters, page-specific scripts, and initialisation --}}
	@foreach ($scripts['bottom_last'] as $script)
		{!! $script->html !!}
	@endforeach

	<!-- ============ DOWNSCRIPTS CLOSE ============ -->
