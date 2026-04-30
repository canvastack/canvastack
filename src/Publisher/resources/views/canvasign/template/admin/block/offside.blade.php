<?php
/**
 * Created on {{ date('d M Y') }}
 *
 * @filesource	offside.blade.php
 *
 * @author		wisnuwidi@canvastack.com
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
?>
	<!-- ============ OFFSIDE / OVERLAYS OPEN ============ -->

	{{-- Sidebar backdrop — mobile overlay that closes sidebar on click --}}
	<div class="sidebar-backdrop" data-sidebar-backdrop></div>

	{{-- Modal dialogs injected by CanvaStack (Bootstrap 5 modal markup) --}}
	{!! $modal_content ?? '' !!}

	<!-- ============ OFFSIDE / OVERLAYS CLOSE ============ -->
