import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import { gsap } from 'gsap';
import { createIcons, icons } from 'lucide';
import animations from './animations/gsap-animations.js';
import lazyLoading from './modules/lazy-loading.js';
import './flatpickr-init.js'; // Import Flatpickr

// Initialize Alpine.js
window.Alpine = Alpine;
Alpine.start();

// Initialize ApexCharts
window.ApexCharts = ApexCharts;

// Initialize GSAP
window.gsap = gsap;

// Initialize GSAP Animations
window.animations = animations;

// Initialize Lazy Loading
window.lazyLoading = lazyLoading;

// Initialize Lucide Icons
document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });
});

// ============================================
// Dark Mode System
// ============================================

/**
 * Dark Mode Manager
 * Handles dark mode state with localStorage persistence
 */
class DarkModeManager {
    constructor() {
        this.storageKey = 'darkMode';
        this.init();
    }

    init() {
        // Initialize from localStorage or system preference
        const savedMode = localStorage.getItem(this.storageKey);
        
        if (savedMode === 'true') {
            this.enable();
        } else if (savedMode === 'false') {
            this.disable();
        } else {
            // Use system preference if no saved preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                this.enable();
            }
        }

        // Listen for system preference changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem(this.storageKey)) {
                    e.matches ? this.enable() : this.disable();
                }
            });
        }
    }

    enable() {
        document.documentElement.classList.add('dark');
        localStorage.setItem(this.storageKey, 'true');
        this.updateIcons();
        this.dispatchEvent('enabled');
    }

    disable() {
        document.documentElement.classList.remove('dark');
        localStorage.setItem(this.storageKey, 'false');
        this.updateIcons();
        this.dispatchEvent('disabled');
    }

    toggle() {
        if (this.isEnabled()) {
            this.disable();
        } else {
            this.enable();
        }
    }

    isEnabled() {
        return document.documentElement.classList.contains('dark');
    }

    updateIcons() {
        // Re-initialize Lucide icons after dark mode change
        setTimeout(() => {
            createIcons({ icons });
        }, 50);
    }

    dispatchEvent(type) {
        window.dispatchEvent(new CustomEvent('darkmode:' + type, {
            detail: { isDark: this.isEnabled() }
        }));
    }
}

// Initialize Dark Mode Manager
const darkMode = new DarkModeManager();

// Global toggle function (backward compatible)
window.toggleDark = function() {
    darkMode.toggle();
};

// Export dark mode manager
window.darkMode = darkMode;

// Page transition animations
window.addEventListener('load', () => {
    animations.initPageTransitions();
});

// ============================================
// Sidebar Management
// ============================================

/**
 * Sidebar Manager
 * Handles sidebar collapse/expand and mobile menu
 */
class SidebarManager {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.mainContent = document.getElementById('main-content');
        this.overlay = document.getElementById('sidebar-overlay');
        this.storageKey = 'sidebarCollapsed';
        
        if (this.sidebar) {
            this.init();
        }
    }

    init() {
        // Restore sidebar state on desktop
        if (window.innerWidth >= 1024) {
            const isCollapsed = localStorage.getItem(this.storageKey) === 'true';
            if (isCollapsed) {
                this.collapse();
            }
        }

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                this.closeMobile();
            }
        });
    }

    toggle() {
        if (this.isCollapsed()) {
            this.expand();
        } else {
            this.collapse();
        }
    }

    collapse() {
        // Use GSAP animation
        animations.sidebarCollapse(this.sidebar, this.mainContent, () => {
            this.sidebar.classList.remove('w-64');
            this.sidebar.classList.add('w-16');
            this.mainContent.classList.remove('ml-64');
            this.mainContent.classList.add('ml-16');
            
            // Hide labels
            document.querySelectorAll('.sidebar-label').forEach(el => {
                el.classList.add('hidden');
            });
            
            localStorage.setItem(this.storageKey, 'true');
        });
    }

    expand() {
        // Show labels first
        document.querySelectorAll('.sidebar-label').forEach(el => {
            el.classList.remove('hidden');
        });
        
        // Use GSAP animation
        animations.sidebarExpand(this.sidebar, this.mainContent, () => {
            this.sidebar.classList.remove('w-16');
            this.sidebar.classList.add('w-64');
            this.mainContent.classList.remove('ml-16');
            this.mainContent.classList.add('ml-64');
            
            localStorage.setItem(this.storageKey, 'false');
        });
    }

    isCollapsed() {
        return this.sidebar.classList.contains('w-16');
    }

    openMobile() {
        this.overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Use GSAP animation
        animations.sidebarSlideIn(this.sidebar, this.overlay);
    }

    closeMobile() {
        // Use GSAP animation
        animations.sidebarSlideOut(this.sidebar, this.overlay, () => {
            this.overlay.classList.add('hidden');
            document.body.style.overflow = '';
        });
    }
}

// Initialize Sidebar Manager
const sidebar = new SidebarManager();

// Global functions (backward compatible)
window.toggleSidebar = function() {
    sidebar.toggle();
};

window.openSidebarMobile = function() {
    sidebar.openMobile();
};

window.closeSidebarMobile = function() {
    sidebar.closeMobile();
};

// Export sidebar manager
window.sidebar = sidebar;

// ============================================
// Mobile Menu Management
// ============================================

/**
 * Mobile Menu Toggle
 */
window.toggleMobileMenu = function() {
    const menu = document.getElementById('mobile-menu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
};

// ============================================
// Modal Management
// ============================================

/**
 * Modal Helper Functions
 */
window.openModal = function(name) {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: name }));
};

window.closeModal = function(name) {
    window.dispatchEvent(new CustomEvent('close-modal', { detail: name }));
};

// Export for use in other modules
export { Alpine, ApexCharts, gsap };

// ============================================
// RTL (Right-to-Left) Support
// ============================================

/**
 * RTL Manager
 * Handles RTL direction changes based on locale
 */
class RTLManager {
    constructor() {
        this.storageKey = 'textDirection';
        this.init();
    }

    init() {
        // Get initial direction from HTML element
        this.currentDirection = document.documentElement.getAttribute('dir') || 'ltr';
        
        // Listen for locale changes
        window.addEventListener('locale:changed', (event) => {
            const direction = event.detail.direction || 'ltr';
            this.setDirection(direction);
        });
    }

    setDirection(direction) {
        if (direction !== 'ltr' && direction !== 'rtl') {
            console.warn(`Invalid direction: ${direction}. Using 'ltr' as fallback.`);
            direction = 'ltr';
        }

        this.currentDirection = direction;
        
        // Update HTML and body elements
        document.documentElement.setAttribute('dir', direction);
        document.body.setAttribute('dir', direction);
        
        // Store preference
        localStorage.setItem(this.storageKey, direction);
        
        // Update sidebar and main content for RTL
        this.updateLayoutForRTL(direction);
        
        // Re-initialize icons after direction change
        setTimeout(() => {
            createIcons({ icons });
        }, 50);
        
        // Dispatch event
        this.dispatchEvent(direction);
    }

    updateLayoutForRTL(direction) {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        
        if (!sidebar || !mainContent) return;
        
        if (direction === 'rtl') {
            // RTL: Sidebar on right, content margin-right
            mainContent.classList.remove('ml-64', 'ml-16');
            mainContent.classList.add('mr-64');
        } else {
            // LTR: Sidebar on left, content margin-left
            mainContent.classList.remove('mr-64', 'mr-16');
            mainContent.classList.add('ml-64');
        }
    }

    getDirection() {
        return this.currentDirection;
    }

    isRTL() {
        return this.currentDirection === 'rtl';
    }

    toggle() {
        const newDirection = this.isRTL() ? 'ltr' : 'rtl';
        this.setDirection(newDirection);
    }

    dispatchEvent(direction) {
        window.dispatchEvent(new CustomEvent('rtl:changed', {
            detail: { 
                direction: direction,
                isRTL: direction === 'rtl'
            }
        }));
    }
}

// Initialize RTL Manager
const rtlManager = new RTLManager();

// Global functions
window.setDirection = function(direction) {
    rtlManager.setDirection(direction);
};

window.toggleDirection = function() {
    rtlManager.toggle();
};

// Export RTL manager
window.rtlManager = rtlManager;

