<?php
/**
 * Created on 10 Mar 2021
 * Time Created	: 10:25:40
 *
 * @filesource	offside.blade.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 */
?>

		<!-- OFFSIDE OPEN -->
		<div class="offset-area">
			<div class="offset-close"><i class="ti-close"></i></div>
			<ul class="nav offset-menu-tab">
				<li><a class="active" data-toggle="tab" href="#activity">Activity</a></li>
				<li><a data-toggle="tab" href="#settings">Settings</a></li>
			</ul>
			<div class="offset-content tab-content blury">
				<div id="activity" class="tab-pane fade in show active">
					<div class="recent-activity">
						<div class="timeline-task">
							<div class="icon bg1">
								<i class="fa fa-envelope"></i>
							</div>
							<div class="tm-title">
								<h4>Rashed sent you an email</h4>
								<span class="time"><i class="ti-time"></i>09:35</span>
							</div>
							<p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Esse distinctio itaque at.</p>
						</div>
					</div>
				</div>
				<div id="settings" class="tab-pane fade">
					<div class="offset-settings">
						<h4>General Settings</h4>
						<div class="settings-list">
							<div class="s-settings">
								<div class="s-sw-title">
									<h5>Notifications</h5>
									<div class="s-swtich">
										<input type="checkbox" id="switch1" />
										<label for="switch1">Toggle</label>
									</div>
								</div>
								<p>Keep it 'On' When you want to get all the notification.</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- OFFSIDE CLOSE -->


