{{--
    offside.blade.php — Bootstrap 4 Right Slide-Out Panel Example
    ==============================================================
    Template: default (Bootstrap 4)
    Location: resources/views/default/template/admin/block/offside.blade.php

    Renders the right slide-out panel with:
    - Activity feed tab
    - Settings tab

    Bootstrap 4 specific:
    - data-toggle="tab" for tab switching (NOT data-bs-toggle)
    - "fade in show active" classes for active tab pane
    - Tab pane uses "tab-pane fade" classes

    The panel is toggled open/closed by the settings button in header.blade.php
    via sidebar.js or scripts.js.
--}}

{{-- OFFSIDE PANEL --}}
<div class="offset-area" role="complementary" aria-label="Side panel">

    {{-- Close button --}}
    <button class="offset-close btn btn-link" aria-label="Close side panel">
        <i class="ti-close" aria-hidden="true"></i>
    </button>

    {{--
        Tab navigation
        Bootstrap 4: data-toggle="tab" (NOT data-bs-toggle="tab")
        Bootstrap 5 equivalent: data-bs-toggle="tab"
    --}}
    <ul class="nav offset-menu-tab" role="tablist">
        <li role="presentation">
            <a class="active"
               data-toggle="tab"
               href="#activity"
               role="tab"
               aria-controls="activity"
               aria-selected="true">
                Activity
            </a>
        </li>
        <li role="presentation">
            <a data-toggle="tab"
               href="#settings"
               role="tab"
               aria-controls="settings"
               aria-selected="false">
                Settings
            </a>
        </li>
    </ul>

    <div class="offset-content tab-content blury">

        {{--
            Activity tab pane
            Bootstrap 4: "tab-pane fade in show active" for active pane
            Bootstrap 5: "tab-pane fade show active" (no "in" class)
        --}}
        <div id="activity"
             class="tab-pane fade in show active"
             role="tabpanel"
             aria-labelledby="activity-tab">
            <div class="recent-activity">
                <div class="timeline-task">
                    <div class="icon bg1">
                        <i class="fa fa-envelope" aria-hidden="true"></i>
                    </div>
                    <div class="tm-title">
                        <h4>Recent Activity</h4>
                        <span class="time">
                            <i class="ti-time" aria-hidden="true"></i>
                            09:35
                        </span>
                    </div>
                    <p>Activity feed will appear here.</p>
                </div>
            </div>
        </div>

        {{-- Settings tab pane --}}
        <div id="settings"
             class="tab-pane fade"
             role="tabpanel"
             aria-labelledby="settings-tab">
            <div class="offset-settings">
                <h4>General Settings</h4>
                <div class="settings-list">

                    {{-- Toggle switch example --}}
                    <div class="s-settings">
                        <div class="s-sw-title">
                            <h5>Notifications</h5>
                            <div class="s-swtich">
                                <input type="checkbox"
                                       id="switch-notifications"
                                       name="notifications"
                                       aria-label="Toggle notifications" />
                                <label for="switch-notifications">Toggle</label>
                            </div>
                        </div>
                        <p>Keep it On to receive all notifications.</p>
                    </div>

                    <div class="s-settings">
                        <div class="s-sw-title">
                            <h5>Dark Mode</h5>
                            <div class="s-swtich">
                                <input type="checkbox"
                                       id="switch-darkmode"
                                       name="darkmode"
                                       aria-label="Toggle dark mode" />
                                <label for="switch-darkmode">Toggle</label>
                            </div>
                        </div>
                        <p>Switch between light and dark theme.</p>
                    </div>

                </div>
            </div>
        </div>

    </div>{{-- .offset-content --}}
</div>{{-- .offset-area --}}
{{-- END OFFSIDE PANEL --}}
