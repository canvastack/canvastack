/* Bootstrap 5.3.3 JS - Dynamic Loading */
(function() {
    if (typeof bootstrap === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
        script.async = false;
        document.head.appendChild(script);
    }
})();