<?php
/**
 * Created on 10 Mar 2021
 * Time Created	: 10:18:13
 *
 * @filesource	header.blade.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
$baseUrl      = function_exists('canvastack_config') ? canvastack_config('baseURL') : url('');
$baseTemplate = function_exists('canvastack_config') ? canvastack_config('base_template') : 'assets/templates/default';
$template     = function_exists('canvastack_config') ? canvastack_config('template') : 'admin';
$assetURL     = rtrim("{$baseUrl}/{$baseTemplate}/{$template}", '/');
$breadcrumbs  = is_string($breadcrumbs ?? null) ? $breadcrumbs : '';
?>
			
				<!-- HEADER BLOCK OPEN  -->
				<div class="shadow">
					<div class="header-area blury blury-blue">
						<div class="row align-items-center">
							<!-- nav and search button -->
							<div class="col-md-6 col-sm-8 clearfix">
								<div class="nav-btn pull-left">
									<span></span>
									<span></span>
									<span></span>
								</div>
								<div class="search-box IncoDIY-search-box pull-left">
									<div class="search-inputbox">
										<form action="#">
											<input id="search-input" type="text" name="search" placeholder="Search..." required />
											<i class="ti-search"></i>
										</form>
									</div>
								</div>
							</div>
							<!-- profile info & task notification -->
							<div class="col-md-6 col-sm-4 clearfix">
								<ul class="notification-area pull-right">
									<li id="full-view"><i class="ti-fullscreen"></i></li>
									<li id="full-view-exit"><i class="ti-zoom-out"></i></li>
									<li class="dropdown">
										<i class="ti-bell dropdown-toggle" data-toggle="dropdown"><span>2</span></i>
										<div class="dropdown-menu bell-notify-box notify-box">
											<span class="notify-title">You have 3 new notifications <a href="#">view all</a></span>
											<div class="nofity-list">
												<a href="#" class="notify-item">
													<div class="notify-thumb"><i class="ti-key btn-danger"></i></div>
													<div class="notify-text">
														<p>You have Changed Your Password</p>
														<span>Just Now</span>
													</div>
												</a>
											</div>
										</div>
									</li>
									<li class="dropdown">
										<i class="fa fa-envelope-o dropdown-toggle" data-toggle="dropdown"><span>3</span></i>
										<div class="dropdown-menu notify-box nt-enveloper-box">
											<span class="notify-title">You have 3 new notifications <a href="#">view all</a></span>
											<div class="nofity-list">
												<a href="#" class="notify-item">
													<div class="notify-thumb">
														<img src="{{ $assetURL }}/images/author/author-img1.jpg" alt="image" />
													</div>
													<div class="notify-text">
														<p>Aglae Mayer</p>
														<span class="msg">Hey I am waiting for you...</span>
														<span>3:15 PM</span>
													</div>
												</a>
											</div>
										</div>
									</li>
									<li class="settings-btn">
										<i class="ti-settings"></i>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
				{!! $breadcrumbs !!}
				<!-- HEADER BLOCK CLOSE  -->


