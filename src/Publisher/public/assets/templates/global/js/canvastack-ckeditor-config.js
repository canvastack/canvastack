/**
 * CanvaStack CKEditor Configuration
 *
 * Configures CKEditor 4 instances with:
 * - Toolbar groups and removed buttons
 * - 100% width, vertical resize only
 * - Dark/light mode support (auto-detects and reacts to themechange event)
 *
 * Loaded after CKEditor CDN script via last: prefix.
 * Console filter is applied separately in canvastack-console-filter.js
 * which loads BEFORE CKEditor.
 *
 * @package CanvaStack
 * @subpackage Global JS
 */
(function () {
    'use strict';

    if (typeof CKEDITOR === 'undefined') return;

    // Disable version check notification banner and console warning
    // This must be set on the global CKEDITOR object BEFORE any instance is created
    CKEDITOR.config.versionCheck = false;
    
    // Also disable via direct property (some versions check this instead)
    if (CKEDITOR.env) {
        CKEDITOR.env.isCompatible = true; // Prevent compatibility warnings
    }

    // ── Instance created — apply config ──────────────────────────────────

    CKEDITOR.on('instanceCreated', function (e) {
        var config = e.editor.config;
        
        // Ensure versionCheck is disabled for this instance
        config.versionCheck = false;

        config.toolbarGroups = [
            { name: 'document',    groups: ['mode', 'document', 'doctools'] },
            { name: 'clipboard',   groups: ['clipboard', 'undo'] },
            { name: 'editing',     groups: ['find', 'selection', 'spellchecker', 'editing'] },
            { name: 'basicstyles', groups: ['basicstyles'] },
            '/',
            { name: 'basicstyles', groups: ['cleanup'] },
            { name: 'paragraph',   groups: ['list', 'indent', 'blocks', 'align', 'bidi', 'paragraph'] },
            { name: 'links',       groups: ['links'] },
            { name: 'insert',      groups: ['insert'] },
            '/',
            { name: 'styles',      groups: ['styles'] },
            { name: 'colors',      groups: ['colors'] },
            { name: 'tools',       groups: ['tools'] },
            { name: 'others',      groups: ['others'] },
            { name: 'about',       groups: ['about'] },
        ];

        config.removeButtons  = 'Form,Radio,Checkbox,TextField,Textarea,Select,Button,ImageButton,HiddenField,About';
        config.width          = '100%';
        config.height         = 300;
        config.resize_dir     = 'vertical';
    });

    // ── Instance ready — apply theme ──────────────────────────────────────

    CKEDITOR.on('instanceReady', function (e) {
        var editor = e.editor;

        applyTheme(editor);

        // React to theme toggle
        window.addEventListener('themechange', function (ev) {
            applyTheme(editor, ev.detail && ev.detail.theme);
        });
    });

    // ── Theme helpers ─────────────────────────────────────────────────────

    function isDarkMode(overrideTheme) {
        if (overrideTheme) return overrideTheme === 'dark';
        return document.documentElement.getAttribute('data-bs-theme') === 'dark'
            || document.documentElement.getAttribute('data-theme') === 'dark';
    }

    function applyTheme(editor, overrideTheme) {
        if (isDarkMode(overrideTheme)) {
            applyDarkMode(editor);
        } else {
            applyLightMode(editor);
        }
    }

    function applyDarkMode(editor) {
        injectCSS(editor, [
            'body { background:#1e2130 !important; color:#e2e8f0 !important; }',
            'p, span, li, td, th, h1, h2, h3, h4, h5, h6 { color:#e2e8f0 !important; }',
            'a { color:#60a5fa !important; }',
            'table, td, th { border-color:#334155 !important; }',
        ].join(' '));
        styleToolbar(editor, '#1e2130', '#e2e8f0', '#334155');
    }

    function applyLightMode(editor) {
        injectCSS(editor, 'body { background:#ffffff !important; color:#1e293b !important; }');
        styleToolbar(editor, '#f8fafc', '#1e293b', '#e2e8f0');
    }

    function injectCSS(editor, css) {
        try {
            var doc      = editor.document.$;
            var existing = doc.getElementById('canvasign-cke-style');
            if (existing) existing.remove();
            var style       = doc.createElement('style');
            style.id        = 'canvasign-cke-style';
            style.textContent = css;
            doc.head.appendChild(style);
        } catch (e) { /* iframe not ready */ }
    }

    function styleToolbar(editor, bg, color, border) {
        try {
            var container = editor.container.$;
            if (!container) return;

            var top = container.querySelector('.cke_top');
            if (top) {
                top.style.background       = bg;
                top.style.borderBottomColor = border;
            }

            var bottom = container.querySelector('.cke_bottom');
            if (bottom) {
                bottom.style.background    = bg;
                bottom.style.borderTopColor = border;
                bottom.style.color         = color;
            }

            var chrome = container.querySelector('.cke_chrome');
            if (chrome) chrome.style.borderColor = border;

        } catch (e) { /* container not ready */ }
    }

})();
