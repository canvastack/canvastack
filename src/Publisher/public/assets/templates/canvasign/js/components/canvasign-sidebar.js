/**
 * Canvasign Sidebar Toggle (Bootstrap 5)
 * 
 * Enhanced sidebar collapse and mobile toggle functionality.
 * Sesuai dengan design referensi canvasign + UX improvements.
 * 
 * Features:
 * - Desktop: Toggle collapsed sidebar
 * - Mobile: Toggle sidebar open/close with backdrop
 * - Auto-close on navigation click (mobile only)
 * - Responsive resize handler
 * - Responsive breakpoint at 900px
 * - Compatible with CanvaStack menu structure
 * 
 * @author wisnuwidi@canvastack.com
 * @copyright Canvastack
 * @version 2.0.0
 */

(function() {
    'use strict';
    
    /**
     * Initialize sidebar toggle functionality
     */
    function initSidebarToggle() {
        const app = document.querySelector('.app');
        
        if (!app) {
            console.warn('Canvasign Sidebar: .app element not found');
            return;
        }

        // 1. Toggle sidebar on button click (responsive behavior)
        document.querySelectorAll('[data-sidebar-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                // Mobile: toggle sidebar-open
                if (window.innerWidth <= 900) {
                    app.classList.toggle('sidebar-open');
                } 
                // Desktop: toggle collapsed
                else {
                    app.classList.toggle('collapsed');
                }
            });
        });

        // 2. Close sidebar when clicking backdrop (mobile only)
        const backdrop = document.querySelector('.sidebar-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', () => {
                app.classList.remove('sidebar-open');
            });
        }
        
        // 3. Auto-close sidebar on navigation click (mobile only)
        // DISABLED: This interferes with metisMenu accordion behavior
        // metisMenu handles submenu toggle, we should not interfere
        // 
        // Original requirement: Sidebar should only close when:
        // - User clicks actual navigation link (not toggle button)
        // - User clicks backdrop
        // - User clicks toggle button again
        //
        // However, event delegation interferes with metisMenu's accordion behavior.
        // Solution: Let metisMenu handle all menu interactions, only handle backdrop and toggle button.
        
        console.log('ℹ️ Auto-close on navigation disabled (metisMenu handles menu interactions)');
        
        // 4. Handle window resize (close sidebar when resizing to desktop)
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                // Close mobile sidebar when resizing to desktop width
                if (window.innerWidth > 900) {
                    app.classList.remove('sidebar-open');
                }
            }, 250);
        });
        
        console.log('Canvasign Sidebar initialized (enhanced v2.0)');
    }

    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarToggle);
    } else {
        // DOM already loaded
        initSidebarToggle();
    }

    /**
     * Export for manual initialization
     */
    window.CanvasignSidebar = {
        init: initSidebarToggle
    };

})();
