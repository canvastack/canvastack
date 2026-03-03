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

$meta    = $components->meta->content['html'];
$styles  = [];
$scripts = $components->template->scripts['js']['top'];

$styles['top'] = [];
if (!empty($components->template->scripts['css']['top'])) {
	$styles['top'] = $components->template->scripts['css']['top'];
}
$styles['bottom_first']    = [];
if (!empty($components->template->scripts['css']['bottom_first'])) {
	$styles['bottom_first'] = $components->template->scripts['css']['bottom_first'];
}
$styles['bottom_last']     = [];
if (!empty($components->template->scripts['css']['bottom_last'])) {
	$styles['bottom_last']  = $components->template->scripts['css']['bottom_last'];
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

