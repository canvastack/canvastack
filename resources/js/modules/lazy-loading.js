/**
 * Lazy Loading Module
 * 
 * Provides lazy loading functionality for images, iframes, and other content.
 */

/**
 * Initialize lazy loading for images.
 * 
 * Uses Intersection Observer API for efficient lazy loading.
 */
export function initLazyImages() {
    // Check if Intersection Observer is supported
    if (!('IntersectionObserver' in window)) {
        // Fallback: load all images immediately
        loadAllImages();
        return;
    }

    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                loadImage(img);
                observer.unobserve(img);
            }
        });
    }, {
        // Start loading when image is 200px from viewport
        rootMargin: '200px 0px',
        threshold: 0.01
    });

    // Observe all images with data-src attribute
    document.querySelectorAll('img[data-src], img[loading="lazy"]').forEach(img => {
        imageObserver.observe(img);
    });
}

/**
 * Load a single image.
 * 
 * @param {HTMLImageElement} img - Image element to load
 */
function loadImage(img) {
    // Load srcset if available
    if (img.dataset.srcset) {
        img.srcset = img.dataset.srcset;
        delete img.dataset.srcset;
    }

    // Load src if available
    if (img.dataset.src) {
        img.src = img.dataset.src;
        delete img.dataset.src;
    }

    // Add loaded class for CSS transitions
    img.classList.add('lazy-loaded');

    // Remove loading attribute
    img.removeAttribute('loading');
}

/**
 * Load all images immediately (fallback for old browsers).
 */
function loadAllImages() {
    document.querySelectorAll('img[data-src]').forEach(img => {
        loadImage(img);
    });
}

/**
 * Initialize lazy loading for iframes.
 */
export function initLazyIframes() {
    if (!('IntersectionObserver' in window)) {
        loadAllIframes();
        return;
    }

    const iframeObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const iframe = entry.target;
                loadIframe(iframe);
                observer.unobserve(iframe);
            }
        });
    }, {
        rootMargin: '200px 0px',
        threshold: 0.01
    });

    document.querySelectorAll('iframe[data-src]').forEach(iframe => {
        iframeObserver.observe(iframe);
    });
}

/**
 * Load a single iframe.
 * 
 * @param {HTMLIFrameElement} iframe - Iframe element to load
 */
function loadIframe(iframe) {
    if (iframe.dataset.src) {
        iframe.src = iframe.dataset.src;
        delete iframe.dataset.src;
        iframe.classList.add('lazy-loaded');
    }
}

/**
 * Load all iframes immediately (fallback).
 */
function loadAllIframes() {
    document.querySelectorAll('iframe[data-src]').forEach(iframe => {
        loadIframe(iframe);
    });
}

/**
 * Initialize lazy loading for background images.
 */
export function initLazyBackgrounds() {
    if (!('IntersectionObserver' in window)) {
        loadAllBackgrounds();
        return;
    }

    const bgObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                loadBackground(element);
                observer.unobserve(element);
            }
        });
    }, {
        rootMargin: '200px 0px',
        threshold: 0.01
    });

    document.querySelectorAll('[data-bg]').forEach(element => {
        bgObserver.observe(element);
    });
}

/**
 * Load a single background image.
 * 
 * @param {HTMLElement} element - Element with background image
 */
function loadBackground(element) {
    if (element.dataset.bg) {
        element.style.backgroundImage = `url('${element.dataset.bg}')`;
        delete element.dataset.bg;
        element.classList.add('lazy-loaded');
    }
}

/**
 * Load all background images immediately (fallback).
 */
function loadAllBackgrounds() {
    document.querySelectorAll('[data-bg]').forEach(element => {
        loadBackground(element);
    });
}

/**
 * Initialize lazy loading for components.
 * 
 * Loads heavy components only when they come into view.
 */
export function initLazyComponents() {
    if (!('IntersectionObserver' in window)) {
        loadAllComponents();
        return;
    }

    const componentObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const component = entry.target;
                loadComponent(component);
                observer.unobserve(component);
            }
        });
    }, {
        rootMargin: '100px 0px',
        threshold: 0.01
    });

    document.querySelectorAll('[data-lazy-component]').forEach(component => {
        componentObserver.observe(component);
    });
}

/**
 * Load a single component.
 * 
 * @param {HTMLElement} component - Component element to load
 */
function loadComponent(component) {
    const componentName = component.dataset.lazyComponent;
    
    // Dispatch custom event for component loading
    const event = new CustomEvent('lazy-component-load', {
        detail: { componentName, element: component }
    });
    component.dispatchEvent(event);
    
    // Remove loading state
    component.classList.remove('lazy-loading');
    component.classList.add('lazy-loaded');
    
    delete component.dataset.lazyComponent;
}

/**
 * Load all components immediately (fallback).
 */
function loadAllComponents() {
    document.querySelectorAll('[data-lazy-component]').forEach(component => {
        loadComponent(component);
    });
}

/**
 * Initialize all lazy loading features.
 */
export function initLazyLoading() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initAllLazyFeatures();
        });
    } else {
        initAllLazyFeatures();
    }
}

/**
 * Initialize all lazy loading features.
 */
function initAllLazyFeatures() {
    initLazyImages();
    initLazyIframes();
    initLazyBackgrounds();
    initLazyComponents();
}

/**
 * Manually trigger lazy loading for dynamically added content.
 * 
 * @param {HTMLElement} container - Container element with new content
 */
export function refreshLazyLoading(container = document) {
    // Re-initialize observers for new content
    const images = container.querySelectorAll('img[data-src], img[loading="lazy"]');
    const iframes = container.querySelectorAll('iframe[data-src]');
    const backgrounds = container.querySelectorAll('[data-bg]');
    const components = container.querySelectorAll('[data-lazy-component]');

    if (images.length > 0) initLazyImages();
    if (iframes.length > 0) initLazyIframes();
    if (backgrounds.length > 0) initLazyBackgrounds();
    if (components.length > 0) initLazyComponents();
}

// Auto-initialize on import
initLazyLoading();

// Export for manual control
export default {
    init: initLazyLoading,
    refresh: refreshLazyLoading,
    images: initLazyImages,
    iframes: initLazyIframes,
    backgrounds: initLazyBackgrounds,
    components: initLazyComponents,
};
