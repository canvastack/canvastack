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
$fileExists = file_exists(public_path().$logo);
?>
<div class="sidebar-menu">
	<div class="sidebar-header">
		<div class="logo">
			@if ($fileExists)
				<a href="{{ URL::to('admin')}}" class="lights font-congenial-black color-transparent"><img alt="{{ $appName }}" /></a>
			@else
				<a href="{{ URL::to('admin')}}" class="lights font-congenial-black color-transparent"><img src="{{ $logo }}" alt="{{ $components->meta->content['text']['app_name'] }}" /><span>{{ $components->meta->content['text']['app_name'] }}</span></a>
			@endif
		</div>
	</div>
	@if($sidebar_content)
	{!! $sidebar_content !!}
	@endif
	<nav class="menu-inner">@foreach($menu_sidebar as $menu) {!! $menu !!} @endforeach</nav>
</div>