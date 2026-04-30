/**
 * Canvasign Dynamic Icon Converter
 * 
 * Converts Font Awesome icons to Bootstrap Icons dynamically
 * Useful for icons that are added dynamically or not covered by CSS mapping
 * 
 * @author wisnuwidi@canvastack.com
 * @copyright Canvastack
 * @version 1.0.0
 */

(function() {
    'use strict';
    
    console.log('🎨 Canvasign Icon Converter loaded');
    
    /**
     * Font Awesome to Bootstrap Icons mapping
     */
    const iconMapping = {
        // Dashboard & Home
        'fa-dashboard': 'bi-grid-1x2',
        'fa-tachometer': 'bi-grid-1x2',
        'fa-tachometer-alt': 'bi-grid-1x2',
        'fa-home': 'bi-house',
        'fa-house': 'bi-house',
        
        // Settings & Configuration
        'fa-gear': 'bi-gear',
        'fa-cog': 'bi-gear',
        'fa-cogs': 'bi-gear',
        'fa-settings': 'bi-gear',
        'fa-wrench': 'bi-wrench',
        'fa-tools': 'bi-tools',
        
        // Users & People
        'fa-user': 'bi-person',
        'fa-user-circle': 'bi-person-circle',
        'fa-user-secret': 'bi-people',
        'fa-users': 'bi-people',
        'fa-people': 'bi-people',
        'fa-user-plus': 'bi-person-plus',
        'fa-user-minus': 'bi-person-dash',
        'fa-user-edit': 'bi-person-gear',
        
        // Charts & Analytics
        'fa-chart': 'bi-bar-chart',
        'fa-bar-chart': 'bi-bar-chart',
        'fa-bar-chart-o': 'bi-bar-chart',
        'fa-line-chart': 'bi-graph-up',
        'fa-area-chart': 'bi-graph-up-arrow',
        'fa-pie-chart': 'bi-pie-chart',
        'fa-analytics': 'bi-graph-up',
        'fa-chart-line': 'bi-graph-up',
        
        // Tables & Data
        'fa-table': 'bi-table',
        'fa-list': 'bi-list',
        'fa-list-ul': 'bi-list',
        'fa-list-ol': 'bi-list-ol',
        
        // Files & Documents
        'fa-file': 'bi-file-text',
        'fa-file-o': 'bi-file-text',
        'fa-file-text': 'bi-file-text',
        'fa-file-text-o': 'bi-file-text',
        'fa-folder': 'bi-folder',
        'fa-folder-o': 'bi-folder',
        'fa-folder-open': 'bi-folder-open',
        'fa-folder-open-o': 'bi-folder-open',
        
        // Communication
        'fa-envelope': 'bi-envelope',
        'fa-envelope-o': 'bi-envelope',
        'fa-mail': 'bi-envelope',
        'fa-phone': 'bi-phone',
        'fa-mobile': 'bi-phone',
        'fa-comment': 'bi-chat',
        'fa-comment-o': 'bi-chat',
        'fa-comments': 'bi-chat-dots',
        'fa-comments-o': 'bi-chat-dots',
        
        // Calendar & Time
        'fa-calendar': 'bi-calendar3',
        'fa-calendar-o': 'bi-calendar3',
        'fa-clock': 'bi-clock',
        'fa-clock-o': 'bi-clock',
        'fa-time': 'bi-clock',
        
        // Navigation & Arrows
        'fa-arrow-up': 'bi-arrow-up',
        'fa-arrow-down': 'bi-arrow-down',
        'fa-arrow-left': 'bi-arrow-left',
        'fa-arrow-right': 'bi-arrow-right',
        'fa-chevron-up': 'bi-chevron-up',
        'fa-chevron-down': 'bi-chevron-down',
        'fa-chevron-left': 'bi-chevron-left',
        'fa-chevron-right': 'bi-chevron-right',
        
        // Actions
        'fa-plus': 'bi-plus',
        'fa-minus': 'bi-dash',
        'fa-edit': 'bi-pencil',
        'fa-pencil': 'bi-pencil',
        'fa-trash': 'bi-trash',
        'fa-trash-o': 'bi-trash',
        'fa-save': 'bi-floppy',
        'fa-floppy-o': 'bi-floppy',
        'fa-search': 'bi-search',
        'fa-filter': 'bi-funnel',
        
        // Status & Indicators
        'fa-check': 'bi-check',
        'fa-times': 'bi-x',
        'fa-close': 'bi-x',
        'fa-exclamation': 'bi-exclamation',
        'fa-question': 'bi-question',
        'fa-info': 'bi-info',
        'fa-warning': 'bi-exclamation-triangle',
        'fa-exclamation-triangle': 'bi-exclamation-triangle',
        
        // Media & Content
        'fa-image': 'bi-image',
        'fa-picture-o': 'bi-image',
        'fa-video': 'bi-camera-video',
        'fa-video-camera': 'bi-camera-video',
        'fa-music': 'bi-music-note',
        'fa-volume-up': 'bi-volume-up',
        
        // Security & Lock
        'fa-lock': 'bi-lock',
        'fa-unlock': 'bi-unlock',
        'fa-key': 'bi-key',
        'fa-shield': 'bi-shield',
        'fa-eye': 'bi-eye',
        'fa-eye-slash': 'bi-eye-slash',
        
        // Shopping & Commerce
        'fa-shopping-cart': 'bi-cart',
        'fa-credit-card': 'bi-credit-card',
        'fa-money': 'bi-currency-dollar',
        'fa-dollar': 'bi-currency-dollar',
        
        // Technology
        'fa-database': 'bi-database',
        'fa-server': 'bi-server',
        'fa-cloud': 'bi-cloud',
        'fa-wifi': 'bi-wifi',
        'fa-signal': 'bi-signal',
        
        // Social & Brand (Generic)
        'fa-facebook': 'bi-share',
        'fa-twitter': 'bi-share',
        'fa-instagram': 'bi-share',
        'fa-linkedin': 'bi-share',
        'fa-github': 'bi-github',
        'fa-google': 'bi-google',
        
        // Bookmarks & Favorites
        'fa-bookmark': 'bi-bookmark',
        'fa-bookmark-o': 'bi-bookmark',
        'fa-star': 'bi-star',
        'fa-star-o': 'bi-star',
        'fa-heart': 'bi-heart',
        'fa-heart-o': 'bi-heart',
        
        // Layout & Grid
        'fa-th': 'bi-grid',
        'fa-th-large': 'bi-grid-1x2',
        'fa-th-list': 'bi-list',
        'fa-columns': 'bi-columns',
        'fa-bars': 'bi-list',
        
        // Transport & Location
        'fa-map': 'bi-map',
        'fa-map-o': 'bi-map',
        'fa-location': 'bi-geo-alt',
        'fa-map-marker': 'bi-geo-alt',
        'fa-car': 'bi-car-front',
        'fa-plane': 'bi-airplane'
    };
    
    /**
     * Convert Font Awesome icons to Bootstrap Icons
     */
    function convertIcons() {
        console.log('🔄 Converting Font Awesome icons to Bootstrap Icons...');
        
        // Find all Font Awesome icons in sidebar
        const faIcons = document.querySelectorAll('.sidebar-nav .fa[class*="fa-"]');
        let convertedCount = 0;
        let unmappedCount = 0;
        const unmappedIcons = new Set();
        
        faIcons.forEach(icon => {
            // Get all FA classes
            const faClasses = Array.from(icon.classList).filter(cls => cls.startsWith('fa-'));
            let converted = false;
            
            faClasses.forEach(faClass => {
                if (iconMapping[faClass]) {
                    // Remove all existing classes
                    icon.className = '';
                    
                    // Add Bootstrap Icons classes
                    icon.classList.add('bi', iconMapping[faClass]);
                    
                    console.log(`✅ Converted: ${faClass} → ${iconMapping[faClass]}`);
                    convertedCount++;
                    converted = true;
                }
            });
            
            if (!converted) {
                // Log unmapped icons for future reference
                faClasses.forEach(faClass => {
                    unmappedIcons.add(faClass);
                });
                unmappedCount++;
                
                // Set fallback icon
                icon.className = '';
                icon.classList.add('bi', 'bi-circle');
                console.warn(`⚠️ Unmapped icon: ${faClasses.join(' ')} → using bi-circle fallback`);
            }
        });
        
        console.log(`✅ Icon conversion complete: ${convertedCount} converted, ${unmappedCount} unmapped`);
        
        if (unmappedIcons.size > 0) {
            console.group('📋 Unmapped Icons (add to mapping):');
            unmappedIcons.forEach(icon => console.log(`'${icon}': 'bi-[BOOTSTRAP_ICON]',`));
            console.groupEnd();
        }
    }
    
    /**
     * Initialize icon conversion
     */
    function initIconConverter() {
        // Convert existing icons
        convertIcons();
        
        // Watch for dynamically added icons
        const observer = new MutationObserver(function(mutations) {
            let hasNewIcons = false;
            
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        const newFaIcons = node.querySelectorAll ? node.querySelectorAll('.fa[class*="fa-"]') : [];
                        if (newFaIcons.length > 0) {
                            hasNewIcons = true;
                        }
                    }
                });
            });
            
            if (hasNewIcons) {
                console.log('🔄 New icons detected, converting...');
                convertIcons();
            }
        });
        
        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        console.log('✅ Icon converter initialized with mutation observer');
    }
    
    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initIconConverter);
    } else {
        initIconConverter();
    }
    
    /**
     * Export for manual use
     */
    window.CanvasignIconConverter = {
        convert: convertIcons,
        mapping: iconMapping,
        addMapping: function(faClass, biClass) {
            iconMapping[faClass] = biClass;
            console.log(`✅ Added mapping: ${faClass} → ${biClass}`);
        }
    };
    
})();