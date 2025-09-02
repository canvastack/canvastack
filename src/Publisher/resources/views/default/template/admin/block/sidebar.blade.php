<?php
/**
 * Created on 10 Mar 2021
 * Time Created	: 10:05:23
 *
 * @filesource	sidebar.blade.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
// Safe defaults for preview or missing data
$logo = isset($logo) && is_string($logo) ? $logo : '/assets/templates/default/images/logo.png';
$appName = isset($appName) ? $appName : (function_exists('canvastack_config') ? \canvastack_config('site_name') : (config('app.name') ?? 'Application'));
$menu_sidebar = isset($menu_sidebar) && is_array($menu_sidebar) ? $menu_sidebar : [];
$sidebar_content = isset($sidebar_content) ? $sidebar_content : '';
$components = isset($components) && is_object($components) ? $components : null;

$filePath = public_path().$logo;
$fileExists = is_string($filePath) && file_exists($filePath);
?>
<div class="sidebar-menu">
	<div class="sidebar-header">
		<div class="logo">
			@if ($fileExists)
				<a href="{{ URL::to('admin')}}" class="lights font-congenial-black color-transparent"><img alt="{{ $appName }}" /></a>
			@else
				<a href="{{ URL::to('admin')}}" class="lights font-congenial-black color-transparent"><img src="{{ $logo }}" alt="{{ $appName }}" /><span>{{ $appName }}</span></a>
			@endif
		</div>
	</div>
	@if($sidebar_content)
	{!! $sidebar_content !!}
	@endif
	<nav class="menu-inner">@foreach($menu_sidebar as $menu) {!! $menu !!} @endforeach</nav>
</div>