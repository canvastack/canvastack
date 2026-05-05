<?php
/**
 * Created on {{ date('d M Y') }}
 *
 * @filesource	meta.blade.php
 *
 * @author		wisnuwidi@canvastack.com
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
?>
	<!-- CSRF Token -->
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<!-- App Debug Flag -->
	<script>var APP_DEBUG = {{ config('app.debug') ? 'true' : 'false' }};</script>

	@isset($components)
	<!-- CSS from template configuration -->
	@foreach ($components->template->scripts['css']['top'] as $style)
		{!! $style->html !!}
	@endforeach

	<!-- Meta tags from CanvaStack -->
	@foreach ($components->meta->content['html'] as $metaTags)
		{!! $metaTags !!}
	@endforeach

	<!-- JS from template configuration (jQuery, DataTables, theme.js, etc.) -->
	@foreach ($components->template->scripts['js']['top'] as $script)
		{!! $script->html !!}
	@endforeach
	@else
	
	<!-- Fallback CSS when components not available ->
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
	<link href="{{ asset('assets/templates/canvasign/css/theme.css') }}" rel="stylesheet">
	<link href="{{ asset('assets/templates/canvasign/css/app.css') }}" rel="stylesheet">
	<link href="{{ asset('assets/templates/canvasign/css/fonts.css') }}" rel="stylesheet">
	
	<!-- Fallback JS when components not available ->
	<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
	<script src="https://cdn.datatables.net/v/bs5/jszip-2.5.0/dt-1.13.4/b-2.3.6/b-colvis-2.3.6/b-html5-2.3.6/b-print-2.3.6/r-2.4.1/datatables.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
	<script src="{{ asset('assets/templates/canvasign/js/core/theme.js') }}"></script>
	@endisset
