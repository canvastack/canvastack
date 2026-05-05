/* Flatpickr - Dynamic Loading */
(function() {
    if (typeof flatpickr === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
        script.async = false;
        document.head.appendChild(script);
    }
})();