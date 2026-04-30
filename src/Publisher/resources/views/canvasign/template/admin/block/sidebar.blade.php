<?php
/**
 * Created on {{ date('d M Y') }}
 *
 * @filesource	sidebar.blade.php
 *
 * @author		wisnuwidi@canvastack.com
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
?>
	<!-- ============ SIDEBAR ============ -->
	<aside class="sidebar">

		{{-- Sidebar brand: logo icon and application name --}}
		<div class="sidebar-brand">
			<div class="brand-icon"><i class="bi bi-hexagon-fill"></i></div>
			<span>{{ config('app.name') }}<span class="gradient-text">.</span></span>
		</div>

		{{-- Sidebar navigation: iterate through CanvaStack menu system --}}
		<nav class="sidebar-nav">
			@isset($menu_sidebar)
				@foreach ($menu_sidebar as $menu)
					{!! $menu !!}
				@endforeach
			@else
				{{-- Fallback navigation when menu_sidebar is not provided --}}
				<div class="sidebar-section">Main</div>
				<a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
					<i class="bi bi-grid-1x2"></i>
					<span>Dashboard</span>
				</a>

				<div class="sidebar-section">System</div>
				<a class="sidebar-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="#">
					<i class="bi bi-people"></i>
					<span>Users</span>
				</a>
				<a class="sidebar-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="#">
					<i class="bi bi-gear"></i>
					<span>Settings</span>
				</a>
			@endisset
		</nav>

		{{-- Sidebar footer with copyright and current year --}}
		<div class="sidebar-footer">
			&copy; {{ date('Y') }} {{ config('app.name') }}
		</div>

	</aside>
	<!-- ============ SIDEBAR END ============ -->
