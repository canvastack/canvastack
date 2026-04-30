/**
 * Canvasign File Input Handler
 *
 * Replaces jasny-bootstrap fileinput behavior for the canvasign template.
 * Handles:
 *  - Click on preview image → triggers file picker
 *  - File selected → show preview, switch to "exists" state (Change + Remove buttons)
 *  - Remove clicked → clear file, switch back to "new" state (Select Image button)
 *  - Initial state detection → if image already in preview, start in "exists" state
 */
(function () {
    'use strict';

    function initFileinput(container) {
        var fileInput    = container.querySelector('input[type="file"]');
        var preview      = container.querySelector('.fileinput-preview');
        var removeBtn    = container.querySelector('[data-dismiss="fileinput"], .fileinput-exists.btn-danger, a.fileinput-exists');
        var labelNew     = container.querySelector('.fileinput-new');
        var labelExists  = container.querySelector('.fileinput-exists:not(.btn-danger), .fileinput-exists:not(a)');

        if (!fileInput) return;

        // ── Determine initial state ──────────────────────────────────────
        // If preview already contains an <img> with a real src, start in "exists" state
        var existingImg = preview ? preview.querySelector('img') : null;
        var hasImage    = existingImg && existingImg.src && !existingImg.src.endsWith('/');

        if (hasImage) {
            setExists(container);
        } else {
            setNew(container);
        }

        // ── Click on preview → open file picker ──────────────────────────
        if (preview) {
            preview.style.cursor = 'pointer';
            preview.addEventListener('click', function (e) {
                e.preventDefault();
                fileInput.click();
            });
        }

        // ── File selected ────────────────────────────────────────────────
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (!file) return;

            // Validate it's an image
            if (!file.type.startsWith('image/')) {
                showToast('Please select an image file.', 'warning');
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                if (preview) {
                    // Remove existing img or placeholder, insert new img
                    var img = preview.querySelector('img');
                    if (!img) {
                        img = document.createElement('img');
                        img.alt = 'Preview';
                        preview.innerHTML = '';
                        preview.appendChild(img);
                    }
                    img.src = e.target.result;
                }
                setExists(container);
            };
            reader.readAsDataURL(file);
        });

        // ── Remove button ────────────────────────────────────────────────
        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();

                // Clear file input
                fileInput.value = '';

                // Clear preview — restore original img if it was there, or clear
                if (preview) {
                    var img = preview.querySelector('img');
                    if (img) {
                        img.src = '';
                        img.remove();
                    }
                }

                setNew(container);
            });
        }
    }

    // ── State helpers ────────────────────────────────────────────────────

    function setExists(container) {
        container.classList.remove('fileinput-new');
        container.classList.add('fileinput-exists');
    }

    function setNew(container) {
        container.classList.remove('fileinput-exists');
        container.classList.add('fileinput-new');
    }

    // ── Simple toast for validation messages ─────────────────────────────

    function showToast(message, type) {
        var bg = type === 'warning' ? '#f59e0b' : '#dc3545';
        var el = document.createElement('div');
        el.style.cssText = [
            'position:fixed', 'top:80px', 'right:20px', 'z-index:9999',
            'padding:.65rem 1.1rem', 'border-radius:8px', 'color:#fff',
            'font-size:.875rem', 'font-weight:500',
            'box-shadow:0 4px 16px rgba(0,0,0,.2)',
            'background:' + bg, 'transition:opacity .4s',
        ].join(';');
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(function () {
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 3000);
    }

    // ── Init all fileinput containers on page ────────────────────────────

    function initAll() {
        document.querySelectorAll('[data-provides="fileinput"]').forEach(function (container) {
            initFileinput(container);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    // Expose for manual re-init (e.g. after dynamic content load)
    window.canvasignFileinput = { init: initAll, initOne: initFileinput };

})();
