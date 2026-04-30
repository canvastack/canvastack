<?php
/**
 * Created on {{ date('d M Y') }}
 *
 * @filesource	footer.blade.php
 *
 * @author		wisnuwidi@canvastack.com
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
?>
		<!-- FOOTER OPEN -->
		<footer class="footer py-3 mt-auto">
			<div class="container-fluid">
				<div class="d-flex flex-wrap justify-content-between align-items-center">
					<span class="text-muted small">
						&copy; {{ date('Y') }} {{ config('app.name', 'CanvaStack') }}. All rights reserved.
					</span>

					@if (!empty($footer_content ?? ''))
					<div class="footer-content text-muted small">
						{!! $footer_content ?? '' !!}
					</div>
					@endif
				</div>
			</div>
		</footer>
		<!-- FOOTER CLOSE -->
