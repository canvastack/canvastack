/**
 * Canvasign Login — Smart Email/Username Detection + Password Toggle
 *
 * 1. Detects on blur whether the user typed an email address or a username,
 *    then switches the input's `name` attribute accordingly.
 * 2. Handles password visibility toggle for [data-toggle-password] buttons.
 */
(function () {
    'use strict';

    var EMAIL_PATTERN = /^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i;

    function isEmail(value) {
        return EMAIL_PATTERN.test(String(value).toLowerCase());
    }

    function initEmailUsernameDetection() {
        var input = document.getElementById('login-key');
        if (!input) return;

        var label = document.querySelector('label[for="login-key"]');
        var icon  = document.getElementById('login-key-icon');

        input.addEventListener('blur', function () {
            var val = input.value.trim();
            if (!val) return;

            if (isEmail(val)) {
                input.setAttribute('name', 'email');
                input.setAttribute('type', 'email');
                input.setAttribute('autocomplete', 'email');
                if (label) label.textContent = 'E-Mail Address';
                if (icon)  { icon.className = 'bi bi-envelope'; }
            } else {
                input.setAttribute('name', 'username');
                input.setAttribute('type', 'text');
                input.setAttribute('autocomplete', 'username');
                if (label) label.textContent = 'Username';
                if (icon)  { icon.className = 'bi bi-person'; }
            }
        });
    }

    function initPasswordToggle() {
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var selector = btn.getAttribute('data-toggle-password');
                var target   = document.querySelector(selector);
                if (!target) return;

                var isPassword = target.type === 'password';
                target.type    = isPassword ? 'text' : 'password';

                var icon = btn.querySelector('i');
                if (icon) icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });
    }

    function init() {
        initEmailUsernameDetection();
        initPasswordToggle();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
