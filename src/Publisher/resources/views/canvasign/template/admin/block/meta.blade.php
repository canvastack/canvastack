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
	@endif
