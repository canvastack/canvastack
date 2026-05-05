/* Choices.js - Dynamic Loading */
(function() {
    if (typeof Choices === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js';
        script.async = false;
        document.head.appendChild(script);
    }
})();