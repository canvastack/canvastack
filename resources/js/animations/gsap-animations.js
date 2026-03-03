import { gsap } from 'gsap';

/**
 * GSAP Animations Module
 * Professional-grade animations for CanvaStack
 */

// ============================================
// Page Transitions
// ============================================

/**
 * Fade in animation for elements on page load
 * Usage: Add class "fade-in" to elements
 */
export function initPageTransitions() {
    // Fade in elements with stagger
    gsap.from('.fade-in', {
        opacity: 0,
        y: 20,
        duration: 0.6,
        stagger: 0.1,
        ease: 'power2.out'
    });

    // Fade in from left
    gsap.from('.fade-in-left', {
        opacity: 0,
        x: -30,
        duration: 0.6,
        stagger: 0.1,
        ease: 'power2.out'
    });

    // Fade in from right
    gsap.from('.fade-in-right', {
        opacity: 0,
        x: 30,
        duration: 0.6,
        stagger: 0.1,
        ease: 'power2.out'
    });

    // Scale in animation
    gsap.from('.scale-in', {
        opacity: 0,
        scale: 0.9,
        duration: 0.5,
        stagger: 0.1,
        ease: 'back.out(1.7)'
    });

    // Slide up animation
    gsap.from('.slide-up', {
        opacity: 0,
        y: 40,
        duration: 0.7,
        stagger: 0.15,
        ease: 'power3.out'
    });
}

/**
 * Page transition when navigating
 * Call before page navigation
 */
export function pageTransitionOut(callback) {
    gsap.to('body', {
        opacity: 0,
        duration: 0.3,
        ease: 'power2.in',
        onComplete: callback
    });
}

/**
 * Page transition when entering
 * Call on page load
 */
export function pageTransitionIn() {
    gsap.from('body', {
        opacity: 0,
        duration: 0.4,
        ease: 'power2.out'
    });
}

// ============================================
// Modal Animations
// ============================================

/**
 * Modal enter animation
 * @param {HTMLElement} modal - Modal element
 * @param {HTMLElement} backdrop - Backdrop element
 */
export function modalEnter(modal, backdrop) {
    const timeline = gsap.timeline();

    // Animate backdrop
    timeline.fromTo(backdrop, 
        { opacity: 0 },
        { opacity: 1, duration: 0.2, ease: 'power2.out' }
    );

    // Animate modal
    timeline.fromTo(modal,
        { 
            opacity: 0, 
            scale: 0.95,
            y: 20
        },
        { 
            opacity: 1, 
            scale: 1,
            y: 0,
            duration: 0.3, 
            ease: 'back.out(1.5)' 
        },
        '-=0.1' // Overlap with backdrop animation
    );

    return timeline;
}

/**
 * Modal exit animation
 * @param {HTMLElement} modal - Modal element
 * @param {HTMLElement} backdrop - Backdrop element
 * @param {Function} callback - Callback after animation
 */
export function modalExit(modal, backdrop, callback) {
    const timeline = gsap.timeline({
        onComplete: callback
    });

    // Animate modal
    timeline.to(modal, {
        opacity: 0,
        scale: 0.95,
        y: 20,
        duration: 0.2,
        ease: 'power2.in'
    });

    // Animate backdrop
    timeline.to(backdrop, {
        opacity: 0,
        duration: 0.2,
        ease: 'power2.in'
    }, '-=0.1');

    return timeline;
}

/**
 * Modal shake animation (for validation errors)
 * @param {HTMLElement} modal - Modal element
 */
export function modalShake(modal) {
    gsap.to(modal, {
        x: -10,
        duration: 0.1,
        yoyo: true,
        repeat: 3,
        ease: 'power2.inOut'
    });
}

// ============================================
// Sidebar Animations
// ============================================

/**
 * Sidebar collapse animation
 * @param {HTMLElement} sidebar - Sidebar element
 * @param {HTMLElement} mainContent - Main content element
 * @param {Function} callback - Callback after animation
 */
export function sidebarCollapse(sidebar, mainContent, callback) {
    const timeline = gsap.timeline({
        onComplete: callback
    });

    // Fade out labels
    timeline.to('.sidebar-label', {
        opacity: 0,
        duration: 0.2,
        ease: 'power2.in'
    });

    // Collapse sidebar
    timeline.to(sidebar, {
        width: '4rem', // 16 in Tailwind (w-16)
        duration: 0.3,
        ease: 'power2.inOut'
    }, '-=0.1');

    // Adjust main content
    timeline.to(mainContent, {
        marginLeft: '4rem',
        duration: 0.3,
        ease: 'power2.inOut'
    }, '-=0.3');

    return timeline;
}

/**
 * Sidebar expand animation
 * @param {HTMLElement} sidebar - Sidebar element
 * @param {HTMLElement} mainContent - Main content element
 * @param {Function} callback - Callback after animation
 */
export function sidebarExpand(sidebar, mainContent, callback) {
    const timeline = gsap.timeline({
        onComplete: callback
    });

    // Expand sidebar
    timeline.to(sidebar, {
        width: '16rem', // 64 in Tailwind (w-64)
        duration: 0.3,
        ease: 'power2.inOut'
    });

    // Adjust main content
    timeline.to(mainContent, {
        marginLeft: '16rem',
        duration: 0.3,
        ease: 'power2.inOut'
    }, '-=0.3');

    // Fade in labels
    timeline.to('.sidebar-label', {
        opacity: 1,
        duration: 0.2,
        ease: 'power2.out'
    }, '-=0.1');

    return timeline;
}

/**
 * Mobile sidebar slide in
 * @param {HTMLElement} sidebar - Sidebar element
 * @param {HTMLElement} overlay - Overlay element
 */
export function sidebarSlideIn(sidebar, overlay) {
    const timeline = gsap.timeline();

    // Show overlay
    timeline.fromTo(overlay,
        { opacity: 0 },
        { opacity: 1, duration: 0.2, ease: 'power2.out' }
    );

    // Slide in sidebar
    timeline.fromTo(sidebar,
        { x: '-100%' },
        { x: '0%', duration: 0.3, ease: 'power2.out' },
        '-=0.1'
    );

    return timeline;
}

/**
 * Mobile sidebar slide out
 * @param {HTMLElement} sidebar - Sidebar element
 * @param {HTMLElement} overlay - Overlay element
 * @param {Function} callback - Callback after animation
 */
export function sidebarSlideOut(sidebar, overlay, callback) {
    const timeline = gsap.timeline({
        onComplete: callback
    });

    // Slide out sidebar
    timeline.to(sidebar, {
        x: '-100%',
        duration: 0.3,
        ease: 'power2.in'
    });

    // Hide overlay
    timeline.to(overlay, {
        opacity: 0,
        duration: 0.2,
        ease: 'power2.in'
    }, '-=0.2');

    return timeline;
}

// ============================================
// Loading States
// ============================================

/**
 * Spinner animation
 * @param {HTMLElement} element - Spinner element
 */
export function spinnerAnimation(element) {
    return gsap.to(element, {
        rotation: 360,
        duration: 1,
        repeat: -1,
        ease: 'linear'
    });
}

/**
 * Pulse animation for loading indicators
 * @param {HTMLElement} element - Element to pulse
 */
export function pulseAnimation(element) {
    return gsap.to(element, {
        scale: 1.1,
        opacity: 0.7,
        duration: 0.8,
        yoyo: true,
        repeat: -1,
        ease: 'power2.inOut'
    });
}

/**
 * Skeleton loading animation
 * @param {HTMLElement} element - Skeleton element
 */
export function skeletonAnimation(element) {
    return gsap.to(element, {
        backgroundPosition: '200% 0',
        duration: 1.5,
        repeat: -1,
        ease: 'linear'
    });
}

/**
 * Progress bar animation
 * @param {HTMLElement} element - Progress bar element
 * @param {number} progress - Progress percentage (0-100)
 */
export function progressBarAnimation(element, progress) {
    return gsap.to(element, {
        width: `${progress}%`,
        duration: 0.5,
        ease: 'power2.out'
    });
}

/**
 * Dots loading animation
 * @param {string} containerSelector - Container selector for dots
 */
export function dotsLoadingAnimation(containerSelector) {
    const dots = document.querySelectorAll(`${containerSelector} .dot`);
    
    return gsap.to(dots, {
        y: -10,
        duration: 0.5,
        stagger: 0.1,
        yoyo: true,
        repeat: -1,
        ease: 'power2.inOut'
    });
}

/**
 * Fade loading animation (for content replacement)
 * @param {HTMLElement} element - Element to fade
 * @param {Function} callback - Callback to replace content
 */
export function fadeLoading(element, callback) {
    const timeline = gsap.timeline();

    // Fade out
    timeline.to(element, {
        opacity: 0,
        duration: 0.2,
        ease: 'power2.in',
        onComplete: callback
    });

    // Fade in
    timeline.to(element, {
        opacity: 1,
        duration: 0.3,
        ease: 'power2.out'
    });

    return timeline;
}

// ============================================
// Utility Animations
// ============================================

/**
 * Bounce animation
 * @param {HTMLElement} element - Element to bounce
 */
export function bounceAnimation(element) {
    return gsap.to(element, {
        y: -10,
        duration: 0.3,
        yoyo: true,
        repeat: 1,
        ease: 'power2.out'
    });
}

/**
 * Shake animation (for errors)
 * @param {HTMLElement} element - Element to shake
 */
export function shakeAnimation(element) {
    return gsap.to(element, {
        x: -10,
        duration: 0.1,
        yoyo: true,
        repeat: 3,
        ease: 'power2.inOut'
    });
}

/**
 * Fade in animation
 * @param {HTMLElement} element - Element to fade in
 * @param {number} duration - Animation duration
 */
export function fadeIn(element, duration = 0.3) {
    return gsap.fromTo(element,
        { opacity: 0 },
        { opacity: 1, duration, ease: 'power2.out' }
    );
}

/**
 * Fade out animation
 * @param {HTMLElement} element - Element to fade out
 * @param {number} duration - Animation duration
 */
export function fadeOut(element, duration = 0.3) {
    return gsap.to(element, {
        opacity: 0,
        duration,
        ease: 'power2.in'
    });
}

/**
 * Slide in animation
 * @param {HTMLElement} element - Element to slide in
 * @param {string} direction - Direction: 'left', 'right', 'up', 'down'
 */
export function slideIn(element, direction = 'up') {
    const directions = {
        up: { y: 40 },
        down: { y: -40 },
        left: { x: 40 },
        right: { x: -40 }
    };

    return gsap.from(element, {
        ...directions[direction],
        opacity: 0,
        duration: 0.5,
        ease: 'power2.out'
    });
}

/**
 * Scale animation
 * @param {HTMLElement} element - Element to scale
 * @param {number} scale - Target scale
 */
export function scaleAnimation(element, scale = 1.05) {
    return gsap.to(element, {
        scale,
        duration: 0.3,
        ease: 'power2.out'
    });
}

// ============================================
// Card Animations
// ============================================

/**
 * Card hover animation
 * @param {HTMLElement} card - Card element
 */
export function cardHoverIn(card) {
    return gsap.to(card, {
        y: -4,
        boxShadow: '0 20px 40px -12px rgba(99, 102, 241, 0.25)',
        duration: 0.3,
        ease: 'power2.out'
    });
}

/**
 * Card hover out animation
 * @param {HTMLElement} card - Card element
 */
export function cardHoverOut(card) {
    return gsap.to(card, {
        y: 0,
        boxShadow: '0 1px 3px 0 rgb(0 0 0 / 0.1)',
        duration: 0.3,
        ease: 'power2.out'
    });
}

// ============================================
// Notification Animations
// ============================================

/**
 * Notification slide in
 * @param {HTMLElement} notification - Notification element
 * @param {string} position - Position: 'top-right', 'top-left', 'bottom-right', 'bottom-left'
 */
export function notificationSlideIn(notification, position = 'top-right') {
    const positions = {
        'top-right': { x: 400, y: 0 },
        'top-left': { x: -400, y: 0 },
        'bottom-right': { x: 400, y: 0 },
        'bottom-left': { x: -400, y: 0 }
    };

    return gsap.fromTo(notification,
        { ...positions[position], opacity: 0 },
        { x: 0, y: 0, opacity: 1, duration: 0.4, ease: 'back.out(1.5)' }
    );
}

/**
 * Notification slide out
 * @param {HTMLElement} notification - Notification element
 * @param {Function} callback - Callback after animation
 */
export function notificationSlideOut(notification, callback) {
    return gsap.to(notification, {
        x: 400,
        opacity: 0,
        duration: 0.3,
        ease: 'power2.in',
        onComplete: callback
    });
}

// Export all animations
export default {
    // Page transitions
    initPageTransitions,
    pageTransitionOut,
    pageTransitionIn,
    
    // Modal animations
    modalEnter,
    modalExit,
    modalShake,
    
    // Sidebar animations
    sidebarCollapse,
    sidebarExpand,
    sidebarSlideIn,
    sidebarSlideOut,
    
    // Loading states
    spinnerAnimation,
    pulseAnimation,
    skeletonAnimation,
    progressBarAnimation,
    dotsLoadingAnimation,
    fadeLoading,
    
    // Utility animations
    bounceAnimation,
    shakeAnimation,
    fadeIn,
    fadeOut,
    slideIn,
    scaleAnimation,
    
    // Card animations
    cardHoverIn,
    cardHoverOut,
    
    // Notification animations
    notificationSlideIn,
    notificationSlideOut
};
