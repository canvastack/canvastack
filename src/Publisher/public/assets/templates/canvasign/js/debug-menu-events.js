/**
 * Debug Menu Events
 * 
 * Comprehensive debugging untuk track semua event listeners pada menu
 */

(function() {
    'use strict';
    
    console.log('🔍 ===== MENU EVENT DEBUG START =====');
    
    // Wait for DOM and other scripts to load
    setTimeout(function() {
        
        // 1. Check if metisMenu is initialized
        console.log('📋 MetisMenu check:', {
            jQueryLoaded: typeof jQuery !== 'undefined',
            metisMenuExists: typeof jQuery !== 'undefined' && typeof jQuery.fn.metisMenu !== 'undefined',
            menuElement: document.getElementById('menu'),
            menuHasMetisMenu: jQuery('#menu').data('metisMenu')
        });
        
        // 2. Get all event listeners on menu (Chrome DevTools method)
        const menu = document.getElementById('menu');
        if (menu) {
            console.log('📋 Menu element found:', menu);
            
            // Get all links in menu
            const allLinks = menu.querySelectorAll('a');
            console.log('📋 Total links in menu:', allLinks.length);
            
            // Check arrow-node links
            const arrowNodes = menu.querySelectorAll('a.arrow-node');
            console.log('📋 Arrow-node links (toggle buttons):', arrowNodes.length);
            
            // Check menu-url links
            const menuUrls = menu.querySelectorAll('a.menu-url');
            console.log('📋 Menu-url links (navigation):', menuUrls.length);
        }
        
        // 3. Monitor all clicks on sidebar-nav
        const sidebarNav = document.querySelector('.sidebar-nav');
        if (sidebarNav) {
            console.log('📋 Sidebar-nav found, adding debug listener');
            
            // Add listener with capture phase to catch events BEFORE other listeners
            sidebarNav.addEventListener('click', function(e) {
                console.log('🔍 CLICK DETECTED:', {
                    target: e.target,
                    targetTag: e.target.tagName,
                    targetClass: e.target.className,
                    targetText: e.target.textContent ? e.target.textContent.trim().substring(0, 30) : '',
                    currentTarget: e.currentTarget,
                    closestLink: e.target.closest('a'),
                    closestLinkClass: e.target.closest('a') ? e.target.closest('a').className : 'none',
                    closestLinkHref: e.target.closest('a') ? e.target.closest('a').getAttribute('href') : 'none',
                    eventPhase: e.eventPhase,
                    bubbles: e.bubbles,
                    defaultPrevented: e.defaultPrevented
                });
            }, true); // Use capture phase
        }
        
        // 4. Monitor collapse events (Bootstrap/metisMenu)
        if (menu) {
            // Bootstrap collapse events
            jQuery(menu).on('show.bs.collapse', '.collapse', function(e) {
                console.log('🟢 COLLAPSE SHOW:', e.target);
            });
            
            jQuery(menu).on('shown.bs.collapse', '.collapse', function(e) {
                console.log('✅ COLLAPSE SHOWN:', e.target);
            });
            
            jQuery(menu).on('hide.bs.collapse', '.collapse', function(e) {
                console.log('🔴 COLLAPSE HIDE:', e.target);
            });
            
            jQuery(menu).on('hidden.bs.collapse', '.collapse', function(e) {
                console.log('❌ COLLAPSE HIDDEN:', e.target);
            });
        }
        
        // 5. Check sidebar-open class changes
        const app = document.querySelector('.app');
        if (app) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        console.log('🔄 APP CLASS CHANGED:', {
                            hasSidebarOpen: app.classList.contains('sidebar-open'),
                            hasCollapsed: app.classList.contains('collapsed'),
                            allClasses: app.className
                        });
                    }
                });
            });
            
            observer.observe(app, {
                attributes: true,
                attributeFilter: ['class']
            });
            
            console.log('📋 Monitoring .app class changes');
        }
        
        console.log('🔍 ===== MENU EVENT DEBUG READY =====');
        console.log('💡 Now click on menu items and watch the console!');
        
    }, 1000); // Wait 1 second for all scripts to load
    
})();
