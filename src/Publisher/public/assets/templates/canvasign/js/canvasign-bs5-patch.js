/**
 * Canvasign Bootstrap 5 Patch
 *
 * The CanvaStack PHP layer generates filter button and modal HTML using
 * Bootstrap 4 data attributes (data-toggle, data-target, data-dismiss).
 * Bootstrap 5 requires data-bs-toggle, data-bs-target, data-bs-dismiss.
 *
 * This script patches those attributes in the DOM after page load so the
 * filter modal works correctly without modifying the PHP package.
 *
 * Also handles the filter button click → modal open flow for canvasign.
 */
(function () {
    'use strict';

    /**
     * Convert Bootstrap 4 modal attributes to Bootstrap 5 on a given root element.
     * Safe to call multiple times (idempotent).
     */
    function patchBS4toBS5(root) {
        root = root || document;

        // data-toggle="modal"  →  data-bs-toggle="modal"
        root.querySelectorAll('[data-toggle="modal"]').forEach(function (el) {
            el.setAttribute('data-bs-toggle', 'modal');
            el.removeAttribute('data-toggle');
        });

        // data-target=".foo"  →  data-bs-target=".foo"
        root.querySelectorAll('[data-target]').forEach(function (el) {
            el.setAttribute('data-bs-target', el.getAttribute('data-target'));
            el.removeAttribute('data-target');
        });

        // data-dismiss="modal"  →  data-bs-dismiss="modal"
        root.querySelectorAll('[data-dismiss="modal"]').forEach(function (el) {
            el.setAttribute('data-bs-dismiss', 'modal');
            el.removeAttribute('data-dismiss');
        });

        // data-dismiss="alert"  →  data-bs-dismiss="alert"
        root.querySelectorAll('[data-dismiss="alert"]').forEach(function (el) {
            el.setAttribute('data-bs-dismiss', 'alert');
            el.removeAttribute('data-dismiss');
        });
    }

    /**
     * Ensure every filter modal has the correct Bootstrap 5 structure.
     * The PHP generates: <div id="{id}_CanvaStackFILTER" class="modal fade ...">
     * We just need to make sure it has tabindex="-1" and aria-hidden="true".
     */
    function patchFilterModals() {
        document.querySelectorAll('[id$="_CanvaStackFILTER"]').forEach(function (modal) {
            if (!modal.hasAttribute('tabindex')) {
                modal.setAttribute('tabindex', '-1');
            }
            if (!modal.hasAttribute('aria-hidden')) {
                modal.setAttribute('aria-hidden', 'true');
            }
        });
    }

    /**
     * Wire filter buttons that still use class-based targeting
     * (data-bs-target=".ClassName") — Bootstrap 5 supports ID targets better.
     * Convert class targets to ID targets where possible.
     */
    function patchFilterButtonTargets() {
        document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target]').forEach(function (btn) {
            var target = btn.getAttribute('data-bs-target');

            // If target is a class selector (starts with "."), try to find the modal by class
            if (target && target.startsWith('.')) {
                var className = target.slice(1); // remove leading "."
                var modal = document.querySelector('.' + className + '.modal');
                if (modal && modal.id) {
                    // Prefer ID-based targeting
                    btn.setAttribute('data-bs-target', '#' + modal.id);
                }
            }
        });
    }

    /**
     * Run all patches.
     */
    function runPatches() {
        patchBS4toBS5(document);
        patchFilterModals();
        patchFilterButtonTargets();
        console.log('✅ Canvasign BS5 Patch: Bootstrap 4 → 5 attributes patched');
    }

    // Run on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runPatches);
    } else {
        runPatches();
    }

    // Also observe DOM mutations for dynamically injected content (e.g. DataTables init)
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) { // Element node
                        patchBS4toBS5(node);
                        patchFilterButtonTargets();
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            observer.observe(document.body, { childList: true, subtree: true });
        });
    }

})();
