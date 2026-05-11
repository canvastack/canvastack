/**
 * Canvasign Menu - Bootstrap 5 Native Accordion
 * 
 * Replaces metisMenu with Bootstrap 5 native Collapse API
 * for better compatibility and reliability.
 * 
 * Features:
 * - Accordion behavior (one submenu open at a time)
 * - Smooth animations
 * - Bootstrap 5 native (no external dependencies)
 * - Compatible with CanvaStack menu structure
 * 
 * @author wisnuwidi@canvastack.com
 * @copyright Canvastack
 * @version 1.0.0
 */

(function() {
    'use strict';
    
    console.log('🎯 Canvasign Menu script loaded - DISABLING metisMenu');
    
    // CRITICAL: Disable metisMenu if it exists
    if (typeof jQuery !== 'undefined' && jQuery.fn.metisMenu) {
        console.log('⚠️ metisMenu detected - DISABLING it');
        
        // Override metisMenu to prevent initialization
        jQuery.fn.metisMenu = function() {
            console.log('🚫 metisMenu initialization BLOCKED by canvasign-menu.js');
            return this;
        };
    }
    
    /**
     * Initialize menu accordion
     */
    function initMenuAccordion() {
        console.log('🎯 initMenuAccordion called');
        
        const menu = document.getElementById('menu');
        
        if (!menu) {
            console.warn('❌ Canvasign Menu: #menu element not found');
            return;
        }
        
        console.log('✅ Menu element found:', menu);
        console.log('🔍 Menu HTML preview:', menu.outerHTML.substring(0, 300));
        
        // Get all toggle buttons (arrow-node)
        const toggleButtons = menu.querySelectorAll('a.arrow-node');
        
        console.log('🎯 Canvasign Menu: Found', toggleButtons.length, 'toggle buttons');
        
        if (toggleButtons.length === 0) {
            console.warn('❌ No toggle buttons found');
            return;
        }
        
        toggleButtons.forEach((button, index) => {
            console.log('🎯 Attaching listener to button', index, ':', button.textContent.trim());
            
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                console.log('🎯 Toggle clicked:', this.textContent.trim());
                console.log('🎯 Event:', e);
                console.log('🎯 This element:', this);
                console.log('🎯 This HTML:', this.outerHTML.substring(0, 200));
                
                const parentLi = this.closest('li.submenu');
                console.log('🎯 Parent LI:', parentLi);
                
                if (!parentLi) {
                    console.warn('❌ No parent li.submenu found');
                    return;
                }
                
                const submenu = parentLi.querySelector('ul');  // Remove .collapse - HTML doesn't have it
                console.log('🎯 Submenu:', submenu);
                console.log('🎯 Submenu HTML:', submenu ? submenu.outerHTML.substring(0, 200) : 'NULL');
                
                if (!submenu) {
                    console.warn('❌ No ul found');
                    return;
                }
                
                // Check if submenu is visible (has 'in' class or style display)
                const isExpanded = submenu.classList.contains('in') || 
                                  submenu.style.display === 'block' ||
                                  (!submenu.style.display && submenu.offsetHeight > 0);
                
                console.log('🎯 Toggle clicked:', {
                    text: this.textContent.trim(),
                    isExpanded: isExpanded,
                    action: isExpanded ? 'collapse' : 'expand',
                    submenuClasses: submenu.className
                });
                
                if (isExpanded) {
                    // Collapse this submenu
                    console.log('🔴 Collapsing submenu');
                    submenu.classList.remove('in');
                    submenu.style.display = 'none';
                    this.setAttribute('aria-expanded', 'false');
                    parentLi.classList.remove('active');
                } else {
                    // Collapse all other submenus (accordion behavior)
                    console.log('🟢 Expanding submenu (and collapsing others)');
                    const allSubmenus = menu.querySelectorAll('li.submenu > ul');
                    console.log('🔵 Found', allSubmenus.length, 'submenus total');
                    
                    allSubmenus.forEach(otherSubmenu => {
                        if (otherSubmenu !== submenu) {
                            otherSubmenu.classList.remove('in');
                            otherSubmenu.style.display = 'none';
                            const otherButton = otherSubmenu.closest('li').querySelector('a.arrow-node');
                            if (otherButton) {
                                otherButton.setAttribute('aria-expanded', 'false');
                            }
                            otherSubmenu.closest('li').classList.remove('active');
                        }
                    });
                    
                    // Expand this submenu
                    submenu.classList.add('in');
                    submenu.style.display = 'block';
                    this.setAttribute('aria-expanded', 'true');
                    parentLi.classList.add('active');
                    
                    console.log('✅ Submenu expanded, display:', submenu.style.display, 'classes:', submenu.className);
                }
            });
        });
        
        // Initialize: Close all submenus EXCEPT those with active class
        console.log('🔒 Initializing: Closing inactive submenus, keeping active ones open');
        const allSubmenus = menu.querySelectorAll('li.submenu > ul');
        const allToggleButtons = menu.querySelectorAll('a.arrow-node');
        
        console.log('🔍 Found submenus to process:', allSubmenus.length);
        console.log('🔍 Found toggle buttons:', allToggleButtons.length);
        
        allSubmenus.forEach((submenu, index) => {
            const parentLi = submenu.closest('li.submenu');
            const hasActiveClass = parentLi && parentLi.classList.contains('active');
            
            if (hasActiveClass) {
                // Keep active submenu open
                console.log(`✅ Keeping submenu ${index} OPEN (has active child):`, submenu.previousElementSibling?.textContent?.trim());
                submenu.classList.add('in');
                submenu.style.display = 'block';
                const button = parentLi.querySelector('a.arrow-node');
                if (button) {
                    button.setAttribute('aria-expanded', 'true');
                }
            } else {
                // Close inactive submenu
                console.log(`🔒 Closing submenu ${index}:`, submenu.previousElementSibling?.textContent?.trim());
                submenu.classList.remove('in');
                submenu.style.display = 'none';
            }
        });
        
        allToggleButtons.forEach((button, index) => {
            const parentLi = button.closest('li.submenu');
            const hasActiveClass = parentLi && parentLi.classList.contains('active');
            
            if (!hasActiveClass) {
                console.log(`🔒 Setting button ${index} to collapsed:`, button.textContent?.trim());
                button.setAttribute('aria-expanded', 'false');
            }
        });
        
        console.log('✅ Menu initialization complete - active submenus kept open');
        
        console.log('✅ All submenus closed by default:', allSubmenus.length, 'submenus hidden');
        
        console.log('✅ Canvasign Menu initialized (Bootstrap 5 native accordion)');
    }
    
    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        console.log('🎯 DOM still loading, waiting...');
        document.addEventListener('DOMContentLoaded', initMenuAccordion);
    } else {
        // DOM already loaded
        console.log('🎯 DOM already loaded, initializing now');
        initMenuAccordion();
    }
    
    /**
     * Export for manual initialization
     */
    window.CanvasignMenu = {
        init: initMenuAccordion
    };
    
})();
