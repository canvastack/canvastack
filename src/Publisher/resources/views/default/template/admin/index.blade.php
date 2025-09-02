<?php
/**
 * Created on 10 Mar 2021
 * Time Created	: 10:00:40
 *
 * @filesource	index.blade.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
?>
<!DOCTYPE html>
<html class="no-js" lang="en">
	<head>
		@include('default.template.admin.block.meta')
	</head>
		
	<body class="page-sound background-content">
		<!--[if lt IE 8]><p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p><![endif]-->
		<img class="background-img" src="{{ asset('assets/templates/default/images/bg/bg-content-001.jpg') }}" />
		<div id="preloader"><div class="loader"></div></div>
		
		@if (Auth::check())
		<div class="page-container">
			@include('default.template.admin.block.sidebar')
			
			<div class="main-content">
				@include('default.template.admin.block.header')
				
				<!-- CONTENT OPEN -->
				<div class="main-content-inner animated fadeInx">
					<div class="content-box">
						
						@if (!empty($route_info))
						<!-- START ACTION BUTTON BLOCK -->
						{!! canvastack_action_buttons($route_info) !!}
						<!-- END ACTION BUTTON BLOCK -->
						@endif
						
						<div class="body">
		@endif
		
							<!-- CONTENTS -->
							@yield('content')
							<!-- CONTENTS -->
							
		@if (Auth::check())
						</div>
						
					</div>
				</div>
				<!-- CONTENT CLOSE -->
				
			</div>
			@include('default.template.admin.block.footer')
			
		</div>
		<div id="back-top" class="circle show animated pulse">
			<i class="fa fa-angle-up"></i>
		</div>
		
		@include('default.template.admin.block.offside')
		@endif
		
		@include('default.template.admin.block.downscripts')
	</body>
</html>


