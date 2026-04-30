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

	{{-- 1. Bootstrap 5.3.3 JavaScript Bundle (CDN) --}}
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

	{{-- 2. bottom_first scripts — plugins loaded before app.js (DataTables, Flatpickr, Choices.js, ECharts, etc.) --}}
	@foreach ($scripts['bottom_first'] as $script)
		{!! $script->html !!}
	@endforeach

	{{-- 3. App JS — application interactions and sidebar toggle --}}
	<script src="{{ asset('assets/templates/canvasign/js/app.js') }}"></script>

	{{-- 4. Template identifier for CanvaStack adapters --}}
	<script>window.canvastackTemplate = 'canvasign';</script>

	{{-- 5. bottom scripts — inline scripts and dynamic scripts (filter cascading, etc.) --}}
	@foreach ($scripts['bottom'] as $script)
		{!! $script->html !!}
	@endforeach

	{{-- 6. bottom_last scripts — includes adapters, page-specific scripts, and initialisation --}}
	@foreach ($scripts['bottom_last'] as $script)
		{!! $script->html !!}
	@endforeach

	<!-- ============ DOWNSCRIPTS CLOSE ============ -->
