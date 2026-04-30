/**
 * CanvaStack Cache Manager
 *
 * Handles clicks on .cache-action dropdown items.
 * Replaces browser confirm() with a proper Bootstrap 5 modal.
 *
 * Each item carries:
 *   data-cache-type  — cache type string (all, config, route, view, compiled, optimize)
 *   data-url         — full POST URL to system.cache.clear route
 *
 * Flow:
 *   1. Intercept click on .cache-action
 *   2. Show confirmation modal with contextual info
 *   3. On confirm → POST via fetch with CSRF token
 *   4. Show inline result inside modal (success / error)
 *   5. Reload page after success
 */
(function () {
    'use strict';

    // ── Cache type metadata ───────────────────────────────────────────────────

    var CACHE_META = {
        all: {
            icon    : 'bi-trash3',
            color   : 'danger',
            title   : 'Clear All Cache',
            desc    : 'This will clear <strong>all</strong> caches: application, config, route, view, and compiled classes.',
            warning : 'The next page load may be slower while caches are rebuilt.',
            badge   : 'Full Reset',
        },
        config: {
            icon    : 'bi-gear',
            color   : 'warning',
            title   : 'Clear Config Cache',
            desc    : 'Clears the cached configuration file (<code>bootstrap/cache/config.php</code>).',
            warning : 'Config values will be re-read from source files on next request.',
            badge   : 'Config',
        },
        route: {
            icon    : 'bi-signpost-split',
            color   : 'warning',
            title   : 'Clear Route Cache',
            desc    : 'Clears the cached route file (<code>bootstrap/cache/routes-v7.php</code>).',
            warning : 'Routes will be re-registered on next request.',
            badge   : 'Routes',
        },
        view: {
            icon    : 'bi-eye',
            color   : 'info',
            title   : 'Clear View Cache',
            desc    : 'Removes all compiled Blade templates from <code>storage/framework/views/</code>.',
            warning : 'Views will be recompiled on next render.',
            badge   : 'Views',
        },
        compiled: {
            icon    : 'bi-file-earmark-code',
            color   : 'secondary',
            title   : 'Clear Compiled Classes',
            desc    : 'Removes the compiled class file (<code>vendor/compiled.php</code>).',
            warning : 'Class autoloading will be slightly slower until recompiled.',
            badge   : 'Compiled',
        },
        optimize: {
            icon    : 'bi-rocket-takeoff',
            color   : 'success',
            title   : 'Optimize Application',
            desc    : 'Caches config and routes for maximum performance (<code>config:cache</code> + <code>route:cache</code>).',
            warning : 'Config and route changes will not take effect until cache is cleared again.',
            badge   : 'Optimize',
        },
    };

    // ── Inject styles once ────────────────────────────────────────────────────

    function injectStyles() {
        if (document.getElementById('cs-cache-styles')) return;
        var s = document.createElement('style');
        s.id  = 'cs-cache-styles';
        s.textContent = [
            '@keyframes cs-spin{to{transform:rotate(360deg)}}',
            '@keyframes cs-fadein{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}',

            /* Modal overrides */
            '#cs-cache-modal .modal-content{border:none;border-radius:16px;overflow:hidden;}',
            '#cs-cache-modal .cs-modal-header{padding:1.5rem 1.5rem 1rem;display:flex;align-items:flex-start;gap:1rem;}',
            '#cs-cache-modal .cs-icon-wrap{width:48px;height:48px;border-radius:12px;display:grid;place-items:center;flex-shrink:0;font-size:1.4rem;}',
            '#cs-cache-modal .cs-title{font-size:1.1rem;font-weight:700;margin:0 0 .2rem;}',
            '#cs-cache-modal .cs-badge{font-size:.7rem;font-weight:600;padding:.2rem .55rem;border-radius:999px;letter-spacing:.04em;}',
            '#cs-cache-modal .cs-body{padding:.25rem 1.5rem 1rem;}',
            '#cs-cache-modal .cs-desc{font-size:.9rem;color:var(--bs-secondary-color,#6c757d);line-height:1.6;}',
            '#cs-cache-modal .cs-warning{display:flex;align-items:flex-start;gap:.6rem;background:rgba(255,193,7,.1);border:1px solid rgba(255,193,7,.3);border-radius:8px;padding:.65rem .85rem;margin-top:.85rem;font-size:.82rem;color:#856404;}',
            '[data-bs-theme=dark] #cs-cache-modal .cs-warning{color:#ffc107;background:rgba(255,193,7,.08);}',
            '#cs-cache-modal .cs-warning i{flex-shrink:0;margin-top:1px;}',
            '#cs-cache-modal .modal-footer{padding:.85rem 1.5rem;border-top:1px solid var(--bs-border-color,#dee2e6);}',

            /* Result area */
            '#cs-cache-modal .cs-result{display:none;animation:cs-fadein .3s ease;border-radius:10px;padding:.85rem 1rem;margin-top:.85rem;font-size:.88rem;font-weight:500;}',
            '#cs-cache-modal .cs-result.show{display:flex;align-items:center;gap:.6rem;}',
            '#cs-cache-modal .cs-result.success{background:rgba(25,135,84,.12);color:#0f5132;border:1px solid rgba(25,135,84,.25);}',
            '[data-bs-theme=dark] #cs-cache-modal .cs-result.success{color:#75b798;background:rgba(25,135,84,.15);}',
            '#cs-cache-modal .cs-result.error{background:rgba(220,53,69,.1);color:#842029;border:1px solid rgba(220,53,69,.2);}',
            '[data-bs-theme=dark] #cs-cache-modal .cs-result.error{color:#ea868f;background:rgba(220,53,69,.12);}',

            /* Confirm button spinner */
            '#cs-cache-confirm-btn .cs-spinner{display:none;width:14px;height:14px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:cs-spin .6s linear infinite;margin-right:6px;}',
            '#cs-cache-confirm-btn.loading .cs-spinner{display:inline-block;}',
            '#cs-cache-confirm-btn.loading .cs-btn-text{opacity:.7;}',
        ].join('\n');
        document.head.appendChild(s);
    }

    // ── Build modal DOM once ──────────────────────────────────────────────────

    function buildModal() {
        if (document.getElementById('cs-cache-modal')) return;

        var el = document.createElement('div');
        el.id = 'cs-cache-modal';
        el.className = 'modal fade';
        el.tabIndex  = -1;
        el.setAttribute('aria-hidden', 'true');
        el.innerHTML = [
            '<div class="modal-dialog modal-dialog-centered" style="max-width:440px">',
            '  <div class="modal-content">',

            '    <div class="cs-modal-header">',
            '      <div class="cs-icon-wrap" id="cs-icon-wrap">',
            '        <i class="bi" id="cs-header-icon"></i>',
            '      </div>',
            '      <div>',
            '        <div class="cs-title" id="cs-modal-title">Cache Operation</div>',
            '        <span class="cs-badge" id="cs-modal-badge"></span>',
            '      </div>',
            '      <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>',
            '    </div>',

            '    <div class="cs-body">',
            '      <p class="cs-desc" id="cs-modal-desc"></p>',
            '      <div class="cs-warning" id="cs-modal-warning">',
            '        <i class="bi bi-exclamation-triangle-fill"></i>',
            '        <span id="cs-modal-warning-text"></span>',
            '      </div>',
            '      <div class="cs-result" id="cs-modal-result">',
            '        <i class="bi" id="cs-result-icon"></i>',
            '        <span id="cs-result-text"></span>',
            '      </div>',
            '    </div>',

            '    <div class="modal-footer">',
            '      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" id="cs-cancel-btn">',
            '        <i class="bi bi-x-lg me-1"></i>Cancel',
            '      </button>',
            '      <button type="button" class="btn btn-sm" id="cs-cache-confirm-btn">',
            '        <span class="cs-spinner"></span>',
            '        <span class="cs-btn-text"><i class="bi bi-check2 me-1"></i>Confirm</span>',
            '      </button>',
            '    </div>',

            '  </div>',
            '</div>',
        ].join('');

        document.body.appendChild(el);
    }

    // ── Populate modal with cache-type context ────────────────────────────────

    function populateModal(cacheType, url) {
        var meta = CACHE_META[cacheType] || {
            icon    : 'bi-database',
            color   : 'primary',
            title   : 'Clear Cache',
            desc    : 'This will clear the <strong>' + cacheType + '</strong> cache.',
            warning : 'The application will rebuild this cache on next request.',
            badge   : cacheType,
        };

        var colorHex = {
            danger    : { bg: 'rgba(220,53,69,.12)',   fg: '#dc3545' },
            warning   : { bg: 'rgba(255,193,7,.12)',   fg: '#ffc107' },
            info      : { bg: 'rgba(13,202,240,.12)',  fg: '#0dcaf0' },
            success   : { bg: 'rgba(25,135,84,.12)',   fg: '#198754' },
            secondary : { bg: 'rgba(108,117,125,.12)', fg: '#6c757d' },
            primary   : { bg: 'rgba(13,110,253,.12)',  fg: '#0d6efd' },
        }[meta.color] || { bg: 'rgba(13,110,253,.12)', fg: '#0d6efd' };

        // Icon wrap
        var iconWrap = document.getElementById('cs-icon-wrap');
        iconWrap.style.background = colorHex.bg;
        iconWrap.style.color      = colorHex.fg;

        var headerIcon = document.getElementById('cs-header-icon');
        headerIcon.className = 'bi ' + meta.icon;

        // Title & badge
        document.getElementById('cs-modal-title').textContent = meta.title;

        var badge = document.getElementById('cs-modal-badge');
        badge.textContent = meta.badge;
        badge.className   = 'cs-badge bg-' + meta.color + (meta.color === 'warning' ? ' text-dark' : ' text-white');

        // Description
        document.getElementById('cs-modal-desc').innerHTML = meta.desc;

        // Warning
        document.getElementById('cs-modal-warning-text').textContent = meta.warning;

        // Confirm button color
        var confirmBtn = document.getElementById('cs-cache-confirm-btn');
        confirmBtn.className = 'btn btn-' + meta.color + ' btn-sm';
        confirmBtn.classList.remove('loading');
        confirmBtn.disabled  = false;
        confirmBtn.querySelector('.cs-btn-text').innerHTML =
            '<i class="bi bi-check2 me-1"></i>Confirm';

        // Store pending action
        confirmBtn.dataset.pendingUrl  = url;
        confirmBtn.dataset.cacheType   = cacheType;

        // Reset result area
        var result = document.getElementById('cs-modal-result');
        result.className = 'cs-result';
        result.style.display = 'none';

        // Show cancel button
        document.getElementById('cs-cancel-btn').style.display = '';
    }

    // ── Show result inside modal ──────────────────────────────────────────────

    function showModalResult(success, message) {
        var result = document.getElementById('cs-modal-result');
        var icon   = document.getElementById('cs-result-icon');
        var text   = document.getElementById('cs-result-text');

        result.className = 'cs-result show ' + (success ? 'success' : 'error');
        icon.className   = 'bi ' + (success ? 'bi-check-circle-fill' : 'bi-x-circle-fill');
        text.textContent = message;

        // Hide cancel button after action
        document.getElementById('cs-cancel-btn').style.display = 'none';

        // Change confirm button to "Close"
        var confirmBtn = document.getElementById('cs-cache-confirm-btn');
        confirmBtn.className = 'btn btn-' + (success ? 'success' : 'danger') + ' btn-sm';
        confirmBtn.classList.remove('loading');
        confirmBtn.disabled  = false;
        confirmBtn.querySelector('.cs-btn-text').innerHTML =
            '<i class="bi bi-' + (success ? 'arrow-clockwise' : 'x-lg') + ' me-1"></i>' +
            (success ? 'Reloading…' : 'Close');

        // On success: auto-reload
        if (success) {
            confirmBtn.disabled = true;
            setTimeout(function () { window.location.reload(); }, 1800);
        } else {
            // On error: clicking "Close" dismisses modal
            confirmBtn.dataset.pendingUrl = '';
            confirmBtn.onclick = function () {
                bootstrap.Modal.getInstance(
                    document.getElementById('cs-cache-modal')
                ).hide();
            };
        }
    }

    // ── Execute cache clear ───────────────────────────────────────────────────

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function executeCacheClear(url, cacheType) {
        var confirmBtn = document.getElementById('cs-cache-confirm-btn');
        confirmBtn.classList.add('loading');
        confirmBtn.disabled = true;

        fetch(url, {
            method  : 'POST',
            headers : {
                'X-CSRF-TOKEN' : getCsrfToken(),
                'Accept'       : 'application/json',
                'Content-Type' : 'application/json',
            },
            body: JSON.stringify({ _token: getCsrfToken() }),
        })
        .then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
            }).catch(function () {
                return { ok: res.ok, status: res.status, data: {} };
            });
        })
        .then(function (result) {
            if (result.ok && result.data.success) {
                showModalResult(true, result.data.message || 'Cache cleared successfully.');
            } else if (result.status === 429) {
                showModalResult(false, 'Too many requests — please wait a moment before trying again.');
            } else {
                var msg = (result.data && result.data.message) || 'Cache operation failed.';
                showModalResult(false, msg);
            }
        })
        .catch(function (err) {
            showModalResult(false, 'Network error: ' + err.message);
        });
    }

    // ── Wire confirm button ───────────────────────────────────────────────────

    function wireConfirmButton() {
        var confirmBtn = document.getElementById('cs-cache-confirm-btn');
        confirmBtn.addEventListener('click', function () {
            var url       = this.dataset.pendingUrl;
            var cacheType = this.dataset.cacheType;
            if (!url) return;
            executeCacheClear(url, cacheType);
        });
    }

    // ── Main click handler ────────────────────────────────────────────────────

    function handleCacheAction(e) {
        var link = e.target.closest('.cache-action');
        if (!link) return;

        e.preventDefault();
        e.stopPropagation();

        var url       = link.dataset.url;
        var cacheType = link.dataset.cacheType;

        if (!url) {
            console.warn('[CacheManager] data-url missing on', link);
            return;
        }

        // Close any open dropdown first
        var openDropdown = link.closest('.dropdown');
        if (openDropdown) {
            var bsDropdown = bootstrap.Dropdown.getInstance(
                openDropdown.querySelector('[data-bs-toggle="dropdown"]')
            );
            if (bsDropdown) bsDropdown.hide();
        }

        populateModal(cacheType, url);

        var modalEl = document.getElementById('cs-cache-modal');
        var modal   = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static', keyboard: false });
        modal.show();
    }

    // ── Init ──────────────────────────────────────────────────────────────────

    function init() {
        injectStyles();
        buildModal();
        wireConfirmButton();
        document.addEventListener('click', handleCacheAction);
        console.log('✅ CanvaStack Cache Manager loaded');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
