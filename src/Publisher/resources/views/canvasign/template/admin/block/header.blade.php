<?php
/**
 * Canvasign Template — Topbar / Header
 *
 * @filesource  header.blade.php
 * @author      wisnuwidi@canvastack.com
 */

// CanvaStack stores user info in session, not Auth::user() columns
$userFullname = session('fullname') ?: (Auth::user()->fullname ?? Auth::user()->name ?? 'User');
$userGroup    = session('user_group') ?: (Auth::user()->role ?? '');
$userInitials = strtoupper(substr(preg_replace('/[^a-zA-Z\s]/', '', $userFullname), 0, 1));
// Try to get two initials from first + last name
$nameParts = array_filter(explode(' ', trim($userFullname)));
if (count($nameParts) >= 2) {
    $userInitials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
}
?>
	<!-- ============ TOPBAR ============ -->
	<header class="topbar">

		{{-- Sidebar toggle button --}}
		<button class="icon-btn" data-sidebar-toggle title="Toggle sidebar">
			<i class="bi bi-list"></i>
		</button>

		{{-- Search field --}}
		<div class="search">
			<i class="bi bi-search"></i>
			<input type="text" placeholder="Search anything…" />
		</div>

		{{-- Spacer --}}
		<div style="flex:1"></div>

		{{-- Theme toggle button --}}
		<button class="icon-btn" data-theme-toggle title="Toggle theme">
			<i data-theme-icon class="bi bi-sun"></i>
		</button>

		{{-- Notifications button with badge indicator --}}
		<button class="icon-btn" title="Notifications">
			<i class="bi bi-bell"></i>
			<span class="badge-dot"></span>
		</button>

		{{-- User chip — click opens dropdown with profile & logout --}}
		<div class="dropdown">
			<div class="user-chip"
				 id="userDropdown"
				 data-bs-toggle="dropdown"
				 aria-expanded="false"
				 role="button"
				 tabindex="0"
				 style="cursor:pointer">
				<div class="avatar">{{ $userInitials }}</div>
				<div class="d-none d-md-block">
					<div style="font-size:.85rem;font-weight:600;line-height:1.2">{{ $userFullname }}</div>
					<div style="font-size:.72rem;color:var(--text-muted)">{{ $userGroup }}</div>
				</div>
				<i class="bi bi-chevron-down d-none d-md-inline-block ms-1" style="font-size:.7rem;opacity:.6"></i>
			</div>

			<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown"
				style="min-width:200px;border-radius:12px;border:1px solid var(--border);background:var(--surface);box-shadow:0 8px 24px rgba(0,0,0,.12);padding:.4rem;">

				{{-- User info header --}}
				<li class="px-3 py-2 mb-1" style="border-bottom:1px solid var(--border)">
					<div style="font-size:.85rem;font-weight:600;color:var(--text)">{{ $userFullname }}</div>
					<div style="font-size:.75rem;color:var(--text-muted)">{{ session('email') ?? '' }}</div>
					@if($userGroup)
					<span style="font-size:.7rem;background:var(--gradient-soft);color:var(--primary);padding:.15rem .5rem;border-radius:999px;font-weight:500">
						{{ $userGroup }}
					</span>
					@endif
				</li>

				{{-- Profile link --}}
				<li>
					<a class="dropdown-item" href="#"
					   style="border-radius:8px;font-size:.875rem;padding:.45rem .75rem;color:var(--text);display:flex;align-items:center;gap:.6rem">
						<i class="bi bi-person-circle" style="font-size:1rem"></i>
						{{ __('My Profile') }}
					</a>
				</li>

				<li><hr class="dropdown-divider" style="border-color:var(--border);margin:.3rem 0"></li>

				{{-- Logout --}}
				<li>
					<a class="dropdown-item" href="{{ route('custom.logout') }}"
					   style="border-radius:8px;font-size:.875rem;padding:.45rem .75rem;color:var(--danger);display:flex;align-items:center;gap:.6rem"
					   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
						<i class="bi bi-box-arrow-right" style="font-size:1rem"></i>
						{{ __('Sign out') }}
					</a>
				</li>
			</ul>
		</div>

		{{-- Hidden logout form (GET route, but using form for consistency) --}}
		<form id="logout-form" action="{{ route('custom.logout') }}" method="GET" style="display:none"></form>

	</header>
	<!-- ============ TOPBAR END ============ -->
